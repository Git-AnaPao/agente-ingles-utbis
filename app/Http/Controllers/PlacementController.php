<?php

namespace App\Http\Controllers;

use App\Models\PlacementTest;
use App\Models\Questionnaire;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PlacementController extends Controller
{
    private const LEVEL_RANGES = [
        'A1' => [1, 11],
        'A2' => [12, 30],
        'B1' => [31, 43],
        'B2' => [44, 56],
        'C1' => [57, 75],
    ];

    private const CEFR_ORDER = ['A1', 'A2', 'B1', 'B2', 'C1'];

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return redirect()->route('dashboard');
        }

        $latestResult = $user->placementTests()
            ->latest('taken_at')
            ->latest('placement_test_id')
            ->first();
        $isRetaking = (bool) $request->session()->get('placement.retake', false);
        $questionnaire = $this->questionnaire();

        if ($latestResult && $isRetaking && ! $questionnaire) {
            $request->session()->forget('placement.retake');
            $isRetaking = false;
        }

        $questions = $this->formatQuestions($questionnaire);
        $resultsData = $latestResult && ! $isRetaking
            ? $this->formatResult($latestResult)
            : null;
        $history = $user->placementTests()
            ->latest('taken_at')
            ->latest('placement_test_id')
            ->get()
            ->map(fn (PlacementTest $test): array => $this->formatResult($test));

        return view('placement.index', compact(
            'questions',
            'resultsData',
            'history',
            'isRetaking',
        ));
    }

    public function submit(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return redirect()->route('dashboard');
        }

        if ($user->placementTests()->exists() && ! $request->session()->get('placement.retake', false)) {
            return redirect()->route('placement.index')
                ->with('info', 'Tu resultado ya estaba guardado. Usa “Repetir examen” para crear un intento nuevo.');
        }

        $questionnaire = $this->questionnaire();
        if (! $questionnaire) {
            return redirect()->route('placement.index')
                ->with('error', 'El examen de nivel todavía no está configurado.');
        }

        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'string'],
        ]);
        $studentAnswers = $validated['answers'];
        $questions = $questionnaire->questions->sortBy('question_order')->values();

        if ($questions->contains(fn ($question): bool => $question->options->isEmpty())) {
            throw ValidationException::withMessages([
                'answers' => 'El examen contiene preguntas sin opciones. Contacta al administrador.',
            ]);
        }

        $missing = $questions->filter(
            fn ($question): bool => ! array_key_exists($question->question_id, $studentAnswers)
                || trim((string) $studentAnswers[$question->question_id]) === '',
        );

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'answers' => "Faltan {$missing->count()} preguntas por responder. Tu borrador sigue guardado.",
            ]);
        }

        $invalid = $questions->first(function ($question) use ($studentAnswers): bool {
            $answer = (string) $studentAnswers[$question->question_id];

            return ! $question->options->contains(
                fn ($option): bool => (string) $option->option_id === $answer,
            );
        });

        if ($invalid) {
            throw ValidationException::withMessages([
                'answers' => 'Una de las respuestas no pertenece a la pregunta enviada.',
            ]);
        }

        $totalCorrect = 0;
        $levelBreakdown = [];

        foreach (self::CEFR_ORDER as $level) {
            $levelQuestions = $questions->filter(
                fn ($question): bool => $this->questionBelongsToLevel($question, $level),
            );
            $correctInLevel = $levelQuestions->filter(
                fn ($question): bool => $this->isAnswerCorrect(
                    $question,
                    $studentAnswers[$question->question_id],
                ),
            )->count();

            $totalCorrect += $correctInLevel;
            $levelBreakdown[$level] = [
                'correct' => $correctInLevel,
                'total' => $levelQuestions->count(),
            ];
        }

        $placedLevel = 'A1';
        foreach (self::CEFR_ORDER as $level) {
            $result = $levelBreakdown[$level];
            if ($result['total'] === 0 || $result['correct'] < (int) ceil($result['total'] * 0.6)) {
                break;
            }

            $placedLevel = $level;
        }

        $totalQuestions = $questions->count();
        $score = $totalQuestions > 0 ? ($totalCorrect / $totalQuestions) * 100 : 0;

        PlacementTest::create([
            'student_id' => $user->user_id,
            'result_level' => $placedLevel,
            'score' => $score,
            'correct_answers' => $totalCorrect,
            'total_questions' => $totalQuestions,
            'level_breakdown' => json_encode($levelBreakdown, JSON_THROW_ON_ERROR),
        ]);

        $request->session()->forget('placement.retake');

        return redirect()->route('placement.index')
            ->with('success', 'Resultado guardado. Tu mapa comienza en el nivel recomendado.');
    }

    public function skip(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            return redirect()->route('dashboard');
        }

        if ($user->placementTests()->exists() && ! $request->session()->get('placement.retake', false)) {
            return redirect()->route('placement.index');
        }

        PlacementTest::create([
            'student_id' => $user->user_id,
            'result_level' => 'A1',
            'score' => 0,
            'correct_answers' => 0,
            'total_questions' => 0,
            'level_breakdown' => json_encode($this->emptyBreakdown(), JSON_THROW_ON_ERROR),
        ]);

        $request->session()->forget('placement.retake');

        return redirect()->route('placement.index')
            ->with('info', 'Elegiste comenzar desde A1. Puedes repetir el examen cuando quieras.');
    }

    public function retake(Request $request): RedirectResponse
    {
        if (! $request->user()->isStudent()) {
            return redirect()->route('dashboard');
        }

        $request->session()->put('placement.retake', true);

        return redirect()->route('placement.index')
            ->with('info', 'Nuevo intento iniciado. Tus resultados anteriores se conservan.');
    }

    private function questionnaire(): ?Questionnaire
    {
        return Questionnaire::query()
            ->with(['questions.options'])
            ->where('title', 'Placement Test')
            ->whereNull('lesson_id')
            ->first();
    }

    private function formatQuestions(?Questionnaire $questionnaire): array
    {
        if (! $questionnaire) {
            return [];
        }

        return $questionnaire->questions
            ->sortBy('question_order')
            ->values()
            ->map(fn ($question): array => [
                'question_id' => $question->question_id,
                'id' => $question->question_order,
                'level' => $this->levelForOrder($question->question_order),
                'question' => $question->question_text,
                'passage' => $question->question_passage,
                'options' => $question->options
                    ->sortBy('option_order')
                    ->values()
                    ->map(fn ($option): array => [
                        'id' => $option->option_id,
                        'text' => $option->option_text,
                    ])
                    ->all(),
            ])
            ->all();
    }

    private function formatResult(PlacementTest $test): array
    {
        $breakdown = json_decode((string) $test->level_breakdown, true);

        return [
            'id' => $test->placement_test_id,
            'level' => $test->result_level,
            'score' => round((float) $test->score, 1),
            'correct' => (int) $test->correct_answers,
            'total' => (int) $test->total_questions,
            'breakdown' => is_array($breakdown) ? array_replace($this->emptyBreakdown(), $breakdown) : $this->emptyBreakdown(),
            'taken_at' => $test->taken_at?->format('d/m/Y H:i'),
            'was_skipped' => (int) $test->total_questions === 0,
        ];
    }

    private function emptyBreakdown(): array
    {
        return collect(self::CEFR_ORDER)
            ->mapWithKeys(fn (string $level): array => [$level => ['correct' => 0, 'total' => 0]])
            ->all();
    }

    private function levelForOrder(int $order): string
    {
        foreach (self::LEVEL_RANGES as $level => [$min, $max]) {
            if ($order >= $min && $order <= $max) {
                return $level;
            }
        }

        return 'A1';
    }

    private function questionBelongsToLevel($question, string $level): bool
    {
        return $this->levelForOrder((int) $question->question_order) === $level;
    }

    private function isAnswerCorrect($question, string $studentAnswer): bool
    {
        $correctOption = $question->options->firstWhere('is_correct', true);

        return $correctOption !== null && (string) $correctOption->option_id === $studentAnswer;
    }
}
