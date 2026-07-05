<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\PlacementTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlacementController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user->isStudent()) {
            return redirect()->route('dashboard');
        }

        if ($user->placementTests()->exists()) {
            return redirect()->route('levels.index')
                ->with('info', 'Ya completaste el placement test.');
        }

        $questions = [
            ['id' => 1, 'level' => 'A1', 'question' => 'How do you say "Hola" in English?', 'options' => [['text' => 'Hello', 'correct' => true], ['text' => 'Goodbye', 'correct' => false], ['text' => 'Thanks', 'correct' => false], ['text' => 'Sorry', 'correct' => false]]],
            ['id' => 2, 'level' => 'A1', 'question' => 'Complete: "I ___ a student."', 'options' => [['text' => 'am', 'correct' => true], ['text' => 'is', 'correct' => false], ['text' => 'are', 'correct' => false], ['text' => 'be', 'correct' => false]]],
            ['id' => 3, 'level' => 'A2', 'question' => 'Complete: "She ___ to school every day."', 'options' => [['text' => 'go', 'correct' => false], ['text' => 'goes', 'correct' => true], ['text' => 'going', 'correct' => false], ['text' => 'gone', 'correct' => false]]],
            ['id' => 4, 'level' => 'A2', 'question' => '"Mother" is the same as...', 'options' => [['text' => 'Dad', 'correct' => false], ['text' => 'Mom', 'correct' => true], ['text' => 'Sister', 'correct' => false], ['text' => 'Aunt', 'correct' => false]]],
            ['id' => 5, 'level' => 'B1', 'question' => 'Complete: "She ___ visit her grandmother tomorrow."', 'options' => [['text' => 'will', 'correct' => true], ['text' => 'is', 'correct' => false], ['text' => 'was', 'correct' => false], ['text' => 'has', 'correct' => false]]],
            ['id' => 6, 'level' => 'B1', 'question' => '"The blue car is ___ than the red one."', 'options' => [['text' => 'fast', 'correct' => false], ['text' => 'faster', 'correct' => true], ['text' => 'fastest', 'correct' => false], ['text' => 'more fast', 'correct' => false]]],
            ['id' => 7, 'level' => 'B2', 'question' => '"The book ___ by Mark Twain."', 'options' => [['text' => 'wrote', 'correct' => false], ['text' => 'was written', 'correct' => true], ['text' => 'is writing', 'correct' => false], ['text' => 'has written', 'correct' => false]]],
            ['id' => 8, 'level' => 'B2', 'question' => '"I need to ___ up this word in the dictionary."', 'options' => [['text' => 'look', 'correct' => true], ['text' => 'give', 'correct' => false], ['text' => 'run', 'correct' => false], ['text' => 'take', 'correct' => false]]],
            ['id' => 9, 'level' => 'C1', 'question' => '"Break the ice" means...', 'options' => [['text' => 'Romper el hielo', 'correct' => true], ['text' => 'Tener miedo', 'correct' => false], ['text' => 'Enfriar algo', 'correct' => false], ['text' => 'Empezar una pelea', 'correct' => false]]],
            ['id' => 10, 'level' => 'C1', 'question' => '"Not until the bell rang did he leave." This is:', 'options' => [['text' => 'Inversion', 'correct' => true], ['text' => 'Passive voice', 'correct' => false], ['text' => 'Reported speech', 'correct' => false], ['text' => 'Conditional', 'correct' => false]]],
        ];

        return view('placement.index', compact('questions'));
    }

    public function submit(Request $request)
    {
        $user = Auth::user();

        if (!$user->isStudent()) {
            return redirect()->route('dashboard');
        }

        if ($user->placementTests()->exists()) {
            return redirect()->route('levels.index');
        }

        $request->validate([
            'answers' => 'required|array',
        ]);

        $questions = [
            'A1' => [1, 2],
            'A2' => [3, 4],
            'B1' => [5, 6],
            'B2' => [7, 8],
            'C1' => [9, 10],
        ];

        $correctAnswers = [
            1 => 0, 2 => 0, 3 => 1, 4 => 1,
            5 => 0, 6 => 1, 7 => 1, 8 => 0, 9 => 0, 10 => 0,
        ];

        $totalCorrect = 0;
        $totalQuestions = count($correctAnswers);

        foreach ($correctAnswers as $qId => $correctIndex) {
            $userAnswer = $request->answers[$qId] ?? null;
            if ($userAnswer == $correctIndex) {
                $totalCorrect++;
            }
        }

        $cefrOrder = ['A1', 'A2', 'B1', 'B2', 'C1'];
        $placedLevel = 'A1';
        $qPerLevel = 2;

        foreach ($cefrOrder as $level) {
            $correctInLevel = 0;
            foreach ($questions[$level] as $qId) {
                $userAnswer = $request->answers[$qId] ?? null;
                if ($userAnswer == $correctAnswers[$qId]) {
                    $correctInLevel++;
                }
            }
            if ($correctInLevel >= 1) {
                $placedLevel = $level;
            } else {
                break;
            }
        }

        $score = ($totalCorrect / $totalQuestions) * 100;

        PlacementTest::create([
            'student_id' => $user->user_id,
            'result_level' => $placedLevel,
            'score' => $score,
        ]);

        return redirect()->route('levels.index')
            ->with('success', "Placement completado! Nivel: <strong>{$placedLevel}</strong>.");
    }

    public function skip()
    {
        $user = Auth::user();

        if (!$user->isStudent()) {
            return redirect()->route('dashboard');
        }

        if ($user->placementTests()->exists()) {
            return redirect()->route('levels.index');
        }

        PlacementTest::create([
            'student_id' => $user->user_id,
            'result_level' => 'A1',
            'score' => 0,
        ]);

        return redirect()->route('levels.index')
            ->with('info', 'Comienzas desde el principio (A1). ¡Buena suerte!');
    }
}
