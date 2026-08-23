<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\ListeningLesson;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionOption;
use App\Models\Resource;
use Illuminate\Console\Command;

class ImportListeningQuestions extends Command
{
    private const CEFR_LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    private const MAX_SPEAKING_LENGTH = 5000;

    protected $signature = 'import:listening-questions
        {--level= : Importar solo un nivel CEFR (A1, A2, ...)}
        {--force : Re-importar cuestionarios existentes}
        {--dry-run : Vista previa sin guardar en BD}';

    protected $description = 'Importa las preguntas de listening_lessons hacia questions/question_options vinculadas a lessons';

    private const TYPE_MAP = [
        'multiple_choice' => 'multiple_choice',
        'fill_blank' => 'fill_blank',
        'speaking' => 'speaking',
        'listening' => 'listening',
    ];

    public function handle(): int
    {
        $level = $this->option('level');
        $level = $level === null || trim((string) $level) === ''
            ? null
            : strtoupper(trim((string) $level));
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if ($level !== null && !in_array($level, self::CEFR_LEVELS, true)) {
            $this->error("Nivel CEFR invalido '{$level}'. Esperados: ".implode(', ', self::CEFR_LEVELS).'.');

            return self::FAILURE;
        }

        $query = ListeningLesson::ordered();

        if ($level) {
            $query->byLevel($level);
        }

        $listeningLessons = $query->get();

        if ($listeningLessons->isEmpty()) {
            $this->error('No hay lecciones de listening para procesar.');

            return self::FAILURE;
        }

        $this->info("Procesando {$listeningLessons->count()} lecciones de listening" . ($dryRun ? ' (dry-run)' : '') . '...');

        $stats = [
            'questionnaires_created' => 0,
            'questionnaires_skipped' => 0,
            'questionnaires_reimported' => 0,
            'questions_created' => 0,
            'options_created' => 0,
            'resources_created' => 0,
            'lessons_created' => 0,
            'lessons_invalid' => 0,
            'questions_skipped' => 0,
            'unmatched_answers' => 0,
        ];

        $this->newLine();

        foreach ($listeningLessons as $listeningLesson) {
            [$questions, $validationErrors] = $this->prepareQuestions($listeningLesson);

            if ($validationErrors !== []) {
                $stats['lessons_invalid']++;
                $stats['questions_skipped'] += is_array($listeningLesson->questions_data)
                    ? count($listeningLesson->questions_data)
                    : 0;
                $this->warn("  [{$listeningLesson->cefr_level}.{$listeningLesson->sub_level}] {$listeningLesson->title} omitida:");
                foreach ($validationErrors as $error) {
                    $this->warn("    - {$error}");
                }
                continue;
            }

            $lesson = $this->findOrCreateLesson($listeningLesson, $dryRun);
            if ($lesson && $lesson->wasRecentlyCreated) {
                $stats['lessons_created']++;
            }

            $existingQuestionnaire = Questionnaire::where('listening_lesson_id', $listeningLesson->listening_lesson_id)->first();

            if ($existingQuestionnaire && !$force) {
                $stats['questionnaires_skipped']++;
                $this->line("  ⏭  [{$listeningLesson->full_level}] {$listeningLesson->title} — ya importado");

                if (!$dryRun) {
                    $listeningLesson->update(['lesson_id' => $lesson->lesson_id]);
                }

                continue;
            }

            if ($existingQuestionnaire && $force) {
                if (!$dryRun) {
                    $existingQuestionnaire->delete();
                }
                $stats['questionnaires_reimported']++;
            }

            $result = $this->importListeningLesson($listeningLesson, $lesson, $questions, $dryRun);

            $stats['questionnaires_created'] += $result['questionnaire'] ? 1 : 0;
            $stats['questions_created'] += $result['questions'];
            $stats['options_created'] += $result['options'];
            $stats['resources_created'] += $result['resources'];
            $stats['unmatched_answers'] += $result['unmatched'];

            $this->line("  ✓  [{$listeningLesson->full_level}] {$listeningLesson->title} — {$result['questions']} preguntas, {$result['options']} opciones, {$result['resources']} recursos" . ($result['unmatched'] ? ", {$result['unmatched']} sin respuesta marcada" : ''));
        }

        $this->newLine();
        $this->info('Resumen:');

        $labels = [
            'lessons_created' => 'Lecciones creadas',
            'questionnaires_created' => 'Cuestionarios creados',
            'questionnaires_reimported' => 'Cuestionarios re-importados',
            'questionnaires_skipped' => 'Cuestionarios omitidos (ya existían)',
            'questions_created' => 'Preguntas importadas',
            'options_created' => 'Opciones creadas',
            'resources_created' => 'Recursos creados',
            'lessons_invalid' => 'Lecciones omitidas por datos invalidos',
            'questions_skipped' => 'Preguntas omitidas por datos invalidos',
            'unmatched_answers' => 'Preguntas MC sin opción correcta marcada',
        ];

        foreach ($labels as $key => $label) {
            $this->line("  {$label}: {$stats[$key]}");
        }

        return self::SUCCESS;
    }

    private function findOrCreateLesson(ListeningLesson $listeningLesson, bool $dryRun): ?Lesson
    {
        $lesson = Lesson::where('lesson_cefr_level', $listeningLesson->cefr_level)
            ->where('lesson_sub_level', $listeningLesson->sub_level)
            ->first();

        if ($lesson) {
            return $lesson;
        }

        if ($dryRun) {
            return null;
        }

        return Lesson::create([
            'lesson_cefr_level' => $listeningLesson->cefr_level,
            'lesson_sub_level' => $listeningLesson->sub_level,
            'lesson_prompt_payload' => [
                'topic' => 'Listening Practice',
                'prompt' => "Comprensión auditiva y ejercicios del nivel {$listeningLesson->cefr_level}.{$listeningLesson->sub_level}.",
            ],
        ]);
    }

    private function importListeningLesson(
        ListeningLesson $listeningLesson,
        ?Lesson $lesson,
        array $questions,
        bool $dryRun,
    ): array {
        $result = [
            'questionnaire' => null,
            'questions' => 0,
            'options' => 0,
            'resources' => 0,
            'unmatched' => 0,
        ];

        if ($dryRun || !$lesson) {
            $result['questions'] = count($questions);
            $result['options'] = collect($questions)
                ->sum(fn ($q) => count($q['options'] ?? []));
            $result['resources'] = collect($questions)
                ->filter(fn ($q) => $this->isRealResource($q))
                ->unique('resource_url')
                ->count();

            return $result;
        }

        $questionnaire = Questionnaire::create([
            'lesson_id' => $lesson->lesson_id,
            'title' => $listeningLesson->title,
            'listening_lesson_id' => $listeningLesson->listening_lesson_id,
        ]);
        $result['questionnaire'] = $questionnaire;

        foreach ($questions as $data) {
            $answer = $data['answer'];
            $isSpeakingPrompt = $data['question_type'] === 'speaking';

            $question = Question::create([
                'questionnaire_id' => $questionnaire->questionnaire_id,
                'question_type' => $data['question_type'],
                'question_skill_type' => $data['skill'],
                'question_text' => $data['text'],
                'correct_answer' => $isSpeakingPrompt ? null : $answer,
            ]);
            $result['questions']++;

            $options = $data['options'];
            $matched = false;

            foreach ($options as $optionText) {
                if (trim($optionText) === 'N/A') {
                    continue;
                }

                $isCorrect = !$isSpeakingPrompt
                    && $answer !== null
                    && $this->normalize($optionText) === $this->normalize($answer);

                QuestionOption::create([
                    'question_id' => $question->question_id,
                    'option_text' => $optionText,
                    'is_correct' => $isCorrect,
                ]);
                $result['options']++;

                if ($isCorrect) {
                    $matched = true;
                }
            }

            if ($data['question_type'] === 'multiple_choice' && !$matched) {
                $result['unmatched']++;
            }

            if ($this->isRealResource($data) && $this->createResource($questionnaire, $listeningLesson, $data)) {
                $result['resources']++;
            }
        }

        $listeningLesson->update(['lesson_id' => $lesson->lesson_id]);

        return $result;
    }

    private function prepareQuestions(ListeningLesson $listeningLesson): array
    {
        $errors = [];
        $prepared = [];

        if (!in_array($listeningLesson->cefr_level, self::CEFR_LEVELS, true)) {
            $errors[] = "Nivel CEFR invalido '{$listeningLesson->cefr_level}'.";
        }

        if (filter_var($listeningLesson->sub_level, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $errors[] = "Subnivel invalido '{$listeningLesson->sub_level}'.";
        }

        if (filter_var($listeningLesson->sort_order, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $errors[] = "Orden de leccion invalido '{$listeningLesson->sort_order}'.";
        }

        $questions = $listeningLesson->questions_data;
        $answers = $listeningLesson->answers_data;

        if (!is_array($questions) || $questions === []) {
            $errors[] = 'questions_data debe contener al menos una pregunta.';

            return [$prepared, $errors];
        }

        if (!is_array($answers)) {
            $errors[] = 'answers_data debe ser un objeto o arreglo.';

            return [$prepared, $errors];
        }

        $seenNumbers = [];

        foreach (array_values($questions) as $index => $data) {
            $position = $index + 1;

            if (!is_array($data)) {
                $errors[] = "Pregunta {$position}: los datos deben ser un objeto.";
                continue;
            }

            $rawNumber = $data['number'] ?? null;
            if ((!is_int($rawNumber) && !(is_string($rawNumber) && ctype_digit($rawNumber)))
                || (int) $rawNumber < 1) {
                $errors[] = "Pregunta {$position}: numero invalido.";
                continue;
            }

            $number = (int) $rawNumber;
            if (isset($seenNumbers[$number])) {
                $errors[] = "Pregunta {$position}: numero {$number} duplicado.";
                continue;
            }
            $seenNumbers[$number] = true;

            $text = $data['text'] ?? null;
            if (!is_string($text) || trim($text) === '') {
                $errors[] = "Pregunta {$number}: texto vacio o invalido.";
                continue;
            }

            $type = $this->normalizeType($data['type'] ?? null);
            if ($type === null) {
                $displayType = is_scalar($data['type'] ?? null) ? (string) $data['type'] : '(missing)';
                $errors[] = "Pregunta {$number}: tipo invalido '{$displayType}'.";
                continue;
            }

            $skill = $this->normalizeSkill($data['skill'] ?? null);
            if ($skill === null) {
                $displaySkill = is_scalar($data['skill'] ?? null) ? (string) $data['skill'] : '(missing)';
                $errors[] = "Pregunta {$number}: habilidad invalida '{$displaySkill}'.";
                continue;
            }

            $answerKey = (string) $rawNumber;
            $hasAnswer = array_key_exists($answerKey, $answers) || array_key_exists($number, $answers);
            $answer = array_key_exists($answerKey, $answers)
                ? $answers[$answerKey]
                : ($answers[$number] ?? null);

            if ($answer !== null && !is_string($answer) && !is_int($answer) && !is_float($answer)) {
                $errors[] = "Pregunta {$number}: respuesta invalida.";
                continue;
            }

            $answer = $answer === null ? null : trim((string) $answer);
            $answerIsNotApplicable = $answer !== null && mb_strtolower($answer) === 'n/a';

            if ($answerIsNotApplicable && $skill !== 'speaking') {
                $errors[] = "Pregunta {$number}: N/A solo es valido para speaking.";
                continue;
            }

            if ($answerIsNotApplicable) {
                $type = 'speaking';
            }

            if ($type === 'speaking' && $skill !== 'speaking') {
                $errors[] = "Pregunta {$number}: el tipo speaking requiere habilidad speaking.";
                continue;
            }

            if ($type !== 'speaking' && (!$hasAnswer || $answer === '')) {
                $errors[] = "Pregunta {$number}: falta la respuesta correcta.";
                continue;
            }

            if ($type === 'speaking' && mb_strlen($text) > self::MAX_SPEAKING_LENGTH) {
                $errors[] = 'Pregunta '.$number.': speaking excede '.self::MAX_SPEAKING_LENGTH.' caracteres.';
                continue;
            }

            $rawOptions = $data['options'] ?? [];
            if (!is_array($rawOptions)) {
                $errors[] = "Pregunta {$number}: options debe ser un arreglo.";
                continue;
            }

            $options = [];
            $invalidOption = false;
            foreach ($rawOptions as $option) {
                if (!is_string($option) && !is_int($option) && !is_float($option)) {
                    $invalidOption = true;
                    break;
                }

                $option = trim((string) $option);
                if ($option === '') {
                    $invalidOption = true;
                    break;
                }

                if ($type !== 'speaking' || mb_strtolower($option) !== 'n/a') {
                    $options[] = $option;
                }
            }

            if ($invalidOption) {
                $errors[] = "Pregunta {$number}: contiene una opcion vacia o invalida.";
                continue;
            }

            if ($type === 'multiple_choice') {
                if (count($options) < 2) {
                    $errors[] = "Pregunta {$number}: una pregunta MC requiere al menos dos opciones.";
                    continue;
                }

                $normalizedOptions = array_map(fn (string $option): string => $this->normalize($option), $options);
                if (count(array_unique($normalizedOptions)) !== count($normalizedOptions)) {
                    $errors[] = "Pregunta {$number}: contiene opciones MC duplicadas.";
                    continue;
                }

                $matches = array_filter(
                    $options,
                    fn (string $option): bool => $this->normalize($option) === $this->normalize((string) $answer),
                );

                if (count($matches) !== 1) {
                    $errors[] = "Pregunta {$number}: la clave MC '{$answer}' no coincide con exactamente una opcion.";
                    continue;
                }
            }

            foreach (['resource_type', 'resource_url', 'transcription'] as $resourceField) {
                if (array_key_exists($resourceField, $data)
                    && $data[$resourceField] !== null
                    && !is_string($data[$resourceField])) {
                    $errors[] = "Pregunta {$number}: {$resourceField} debe ser texto o null.";
                    continue 2;
                }
            }

            $prepared[] = [
                'number' => $number,
                'text' => trim($text),
                'question_type' => $type,
                'skill' => $skill,
                'answer' => $type === 'speaking' ? null : $answer,
                'options' => $options,
                'resource_type' => $data['resource_type'] ?? null,
                'resource_url' => $data['resource_url'] ?? null,
                'transcription' => $data['transcription'] ?? null,
            ];
        }

        return [$prepared, $errors];
    }

    private function normalizeType(mixed $type): ?string
    {
        if (!is_string($type)) {
            return null;
        }

        $type = mb_strtolower(trim($type));
        $aliases = [
            'multiple choice' => 'multiple_choice',
            'true_false' => 'multiple_choice',
            'true or false' => 'multiple_choice',
            'fill in the blank' => 'fill_blank',
        ];
        $type = $aliases[$type] ?? $type;

        return self::TYPE_MAP[$type] ?? null;
    }

    private function normalizeSkill(mixed $skill): ?string
    {
        if (!is_string($skill)) {
            return null;
        }

        $skill = mb_strtolower(trim($skill));

        return in_array($skill, ['reading', 'listening', 'writing', 'speaking'], true) ? $skill : null;
    }

    private function normalize(string $text): string
    {
        $normalized = mb_strtolower(trim($text));
        $normalized = preg_replace('/[\s]+/u', ' ', $normalized);
        $normalized = ltrim($normalized, '$€£¥¢');
        $normalized = rtrim($normalized, '.,!?;:');

        return $normalized;
    }

    private function isRealResource(array $data): bool
    {
        $url = trim((string) ($data['resource_url'] ?? ''));
        $type = strtolower(trim((string) ($data['resource_type'] ?? '')));

        return $url !== '' && strtolower($url) !== 'n/a' && in_array($type, ['audio', 'text'], true);
    }

    private function createResource(
        Questionnaire $questionnaire,
        ListeningLesson $listeningLesson,
        array $data,
    ): bool {
        $resourceType = strtolower(trim((string) ($data['resource_type'] ?? ''))) === 'audio' ? 'audio' : 'text';

        $existing = Resource::where('questionnaire_id', $questionnaire->questionnaire_id)
            ->where('resource_type', $resourceType)
            ->where('resource_url', $data['resource_url'])
            ->exists();

        if ($existing) {
            return false;
        }

        Resource::create([
            'questionnaire_id' => $questionnaire->questionnaire_id,
            'resource_type' => $resourceType,
            'resource_url' => $data['resource_url'],
            'resource_title' => $listeningLesson->title,
            'resource_transcript' => in_array(trim((string) ($data['transcription'] ?? '')), ['N/A', ''], true) ? null : $data['transcription'],
        ]);

        return true;
    }
}
