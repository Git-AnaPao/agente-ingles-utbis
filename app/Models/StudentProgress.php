<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class StudentProgress extends Model
{
    use HasUuids;

    public const CEFR_LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    public const LEARNING_SKILLS = ['reading', 'listening', 'speaking'];

    public const PASSING_SCORE = 70;

    protected $table = 'student_progress';

    protected $primaryKey = 'student_progress_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'student_id',
        'placement_test_id',
        'lesson_id',
        'student_cefr_level',
        'student_sub_level',
        'student_skill_type',
    ];

    public static function latestPlacementFor(User $student): ?PlacementTest
    {
        return PlacementTest::query()
            ->where('student_id', $student->user_id)
            ->latest('taken_at')
            ->latest('placement_test_id')
            ->first();
    }

    public static function masterSkill(
        User $student,
        Lesson $lesson,
        string $skill,
        ?string $placementTestId = null,
    ): self {
        if (! in_array($skill, self::LEARNING_SKILLS, true)) {
            throw new InvalidArgumentException("Unsupported learning skill: {$skill}");
        }

        return self::query()->firstOrCreate(
            [
                'student_id' => $student->user_id,
                'lesson_id' => $lesson->lesson_id,
                'student_skill_type' => $skill,
            ],
            [
                'placement_test_id' => $placementTestId,
                'student_cefr_level' => $lesson->lesson_cefr_level,
                'student_sub_level' => $lesson->lesson_sub_level,
            ],
        );
    }

    public static function masterSkillWhenEligible(
        User $student,
        Lesson $lesson,
        string $skill,
        ?string $placementTestId = null,
    ): ?self {
        if (! in_array($skill, self::LEARNING_SKILLS, true)) {
            throw new InvalidArgumentException("Unsupported learning skill: {$skill}");
        }

        $existing = self::query()
            ->where('student_id', $student->user_id)
            ->where('lesson_id', $lesson->lesson_id)
            ->where('student_skill_type', $skill)
            ->first();

        // Existing markers are historical certifications and remain authoritative.
        if ($existing) {
            return $existing;
        }

        $activities = self::evaluableActivitiesForSkill($lesson, $skill);
        if ($activities === []) {
            return null;
        }

        $attempts = AttemptLog::query()
            ->with('responses')
            ->where('user_id', $student->user_id)
            ->where('lesson_id', $lesson->lesson_id)
            ->where('passed', true)
            ->get();

        $allActivitiesPassed = collect($activities)->every(
            fn (array $activity): bool => $attempts->contains(
                fn (AttemptLog $attempt): bool => self::attemptPassesActivity($attempt, $activity, $skill),
            ),
        );

        if (! $allActivitiesPassed) {
            return null;
        }

        return self::masterSkill($student, $lesson, $skill, $placementTestId);
    }

    /**
     * @return array{attempt_skill_type: string, questionnaire_id: ?string, listening_lesson_id: ?string}
     */
    public static function attemptMetadataForQuestion(Question $question): array
    {
        $question->loadMissing('questionnaire');
        $questionnaire = $question->questionnaire;

        return [
            'attempt_skill_type' => self::normalizeSkill(
                $question->question_skill_type,
                $question->question_type,
            ) ?? 'reading',
            'questionnaire_id' => $questionnaire?->questionnaire_id,
            'listening_lesson_id' => $questionnaire?->listening_lesson_id,
        ];
    }

    /**
     * @return list<array{type: string, id: string, questionnaire_id: ?string, listening_lesson_id: ?string, question_ids: list<string>}>
     */
    public static function evaluableActivitiesForSkill(Lesson $lesson, string $skill): array
    {
        if (! in_array($skill, self::LEARNING_SKILLS, true)) {
            throw new InvalidArgumentException("Unsupported learning skill: {$skill}");
        }

        self::prepareLessonsForProgress([$lesson]);
        $activities = [];
        $contentById = $lesson->listeningLessons->keyBy('listening_lesson_id');
        $questionnaires = $lesson->questionnaires
            ->concat($lesson->listeningLessons->pluck('questionnaire')->filter())
            ->unique('questionnaire_id');

        foreach ($questionnaires as $questionnaire) {
            $linkedContent = $questionnaire->listening_lesson_id
                ? $contentById->get($questionnaire->listening_lesson_id)
                : null;
            $questions = $questionnaire->questions->filter(
                fn (Question $question): bool => self::normalizeSkill(
                    $question->question_skill_type,
                    $question->question_type,
                ) === $skill,
            );

            if ($skill === 'speaking') {
                if ($linkedContent && $linkedContent->lesson_id === null) {
                    continue;
                }

                $gradableQuestions = $questions->filter(
                    fn (Question $question): bool => $question->question_type === 'speaking'
                        || $question->question_skill_type === 'speaking',
                );
            } else {
                $gradableQuestions = $questions->filter(
                    fn (Question $question): bool => self::questionIsDeterministicallyGradable($question),
                );
            }

            if ($gradableQuestions->isEmpty()) {
                continue;
            }

            self::addActivity(
                $activities,
                $questionnaire->listening_lesson_id ? 'listening' : 'questionnaire',
                $questionnaire->listening_lesson_id ?? $questionnaire->questionnaire_id,
                $questionnaire->questionnaire_id,
                $questionnaire->listening_lesson_id,
                $gradableQuestions->pluck('question_id')->all(),
            );
        }

        foreach ($lesson->listeningLessons as $content) {
            if ($skill === 'listening') {
                [$hasGradableAnswers, $questionIds] = self::gradableListeningAnswers($content);

                if ($hasGradableAnswers) {
                    self::addActivity(
                        $activities,
                        'listening',
                        $content->listening_lesson_id,
                        $content->questionnaire?->questionnaire_id,
                        $content->listening_lesson_id,
                        $questionIds,
                    );
                }
            }

            if (
                $skill === 'speaking'
                && $content->lesson_id === $lesson->lesson_id
                && self::hasText($content->speaking_text)
            ) {
                $speakingQuestionIds = $content->questionnaire?->questions
                    ->filter(fn (Question $question): bool => self::normalizeSkill(
                        $question->question_skill_type,
                        $question->question_type,
                    ) === 'speaking')
                    ->pluck('question_id')
                    ->all() ?? [];

                self::addActivity(
                    $activities,
                    'listening',
                    $content->listening_lesson_id,
                    $content->questionnaire?->questionnaire_id,
                    $content->listening_lesson_id,
                    $speakingQuestionIds,
                );
            }
        }

        return array_values($activities);
    }

    /**
     * @return list<string>
     */
    public static function availableSkillsForLesson(Lesson $lesson): array
    {
        self::prepareLessonsForProgress([$lesson]);
        $available = [];

        foreach ($lesson->listeningLessons as $content) {
            if (self::hasText($content->reading_text)) {
                $available['reading'] = true;
            }

            if (
                self::hasText($content->listening_script)
                || self::hasText($content->audio_drive_file_id)
                || self::hasText($content->audio_drive_url)
                || self::hasText($content->audio_local_path)
            ) {
                $available['listening'] = true;
            }

            if (self::hasText($content->speaking_text)) {
                $available['speaking'] = true;
            }

            foreach ($content->questions_data ?? [] as $question) {
                if (! is_array($question)) {
                    continue;
                }

                $normalized = self::normalizeSkill($question['skill'] ?? null, $question['type'] ?? null);
                if ($normalized === null && strtolower((string) ($question['type'] ?? '')) !== 'speaking') {
                    $normalized = 'listening';
                }
                if ($normalized !== null) {
                    $available[$normalized] = true;
                }
            }
        }

        foreach ($lesson->questionnaires as $questionnaire) {
            foreach ($questionnaire->questions as $question) {
                $normalized = self::normalizeSkill($question->question_skill_type, $question->question_type);
                if ($normalized !== null) {
                    $available[$normalized] = true;
                }
            }
        }

        return array_values(array_filter(
            self::LEARNING_SKILLS,
            fn (string $skill): bool => isset($available[$skill]),
        ));
    }

    /**
     * Only deterministic activities block completion. Reading also covers
     * legacy writing questions, while AI-only speaking remains optional.
     *
     * @return list<string>
     */
    public static function requiredSkillsForLesson(Lesson $lesson): array
    {
        return array_values(array_filter(
            ['reading', 'listening'],
            fn (string $skill): bool => self::evaluableActivitiesForSkill($lesson, $skill) !== [],
        ));
    }

    /**
     * Loads direct and legacy fallback content in bulk and attaches it to each lesson.
     *
     * @param  iterable<int, Lesson>  $lessons
     */
    public static function prepareLessonsForProgress(iterable $lessons): void
    {
        $lessons = collect($lessons)
            ->filter(fn (mixed $lesson): bool => $lesson instanceof Lesson)
            ->values();

        if ($lessons->isEmpty()) {
            return;
        }

        $lessons->each(fn (Lesson $lesson) => $lesson->loadMissing([
            'listeningLessons.questionnaire.questions.options',
            'questionnaires.questions.options',
        ]));

        $missingFallbackFor = $lessons->reject(
            fn (Lesson $lesson): bool => $lesson->relationLoaded('progressEvaluationContent'),
        );

        if ($missingFallbackFor->isEmpty()) {
            return;
        }

        $pairs = $missingFallbackFor
            ->map(fn (Lesson $lesson): string => $lesson->lesson_cefr_level.'|'.$lesson->lesson_sub_level)
            ->unique()
            ->values();
        $fallbackContent = ListeningLesson::query()
            ->with('questionnaire.questions.options')
            ->whereNull('lesson_id')
            ->where(function ($query) use ($pairs): void {
                foreach ($pairs as $pair) {
                    [$level, $subLevel] = explode('|', $pair, 2);
                    $query->orWhere(function ($pairQuery) use ($level, $subLevel): void {
                        $pairQuery->where('cefr_level', $level)
                            ->where('sub_level', (int) $subLevel);
                    });
                }
            })
            ->get()
            ->groupBy(fn (ListeningLesson $content): string => $content->cefr_level.'|'.$content->sub_level);

        foreach ($missingFallbackFor as $lesson) {
            $key = $lesson->lesson_cefr_level.'|'.$lesson->lesson_sub_level;
            $content = $lesson->listeningLessons
                ->concat($fallbackContent->get($key, collect()))
                ->unique('listening_lesson_id')
                ->values();

            $lesson->setRelation('listeningLessons', $content);
            $lesson->setRelation('progressEvaluationContent', $content);
        }
    }

    /**
     * @param  iterable<int, string|StudentProgress>  $masteredSkills
     */
    public static function lessonIsComplete(Lesson $lesson, iterable $masteredSkills): bool
    {
        $required = self::requiredSkillsForLesson($lesson);
        if ($required === []) {
            return false;
        }

        $mastered = collect($masteredSkills)
            ->map(fn (string|self $progress): string => $progress instanceof self
                ? $progress->student_skill_type
                : $progress)
            ->unique()
            ->all();

        return array_diff($required, $mastered) === [];
    }

    /**
     * @param  iterable<int, Lesson>  $lessons
     * @param  iterable<int, StudentProgress>  $progress
     * @return Collection<int, string>
     */
    public static function completedLessonIds(iterable $lessons, iterable $progress): Collection
    {
        $progressByLesson = collect($progress)->groupBy('lesson_id');

        return collect($lessons)
            ->filter(fn (Lesson $lesson): bool => self::lessonIsComplete(
                $lesson,
                $progressByLesson->get($lesson->lesson_id, collect()),
            ))
            ->pluck('lesson_id')
            ->values();
    }

    /**
     * Placement opens its assigned level and every lower level. From there,
     * each subsequent level requires full mastery of the preceding level.
     *
     * @return list<string>
     */
    public static function unlockedCefrLevels(User $student): array
    {
        $placement = self::latestPlacementFor($student);
        if (! $placement) {
            return [];
        }

        $placementIndex = array_search($placement->result_level, self::CEFR_LEVELS, true);
        $placementIndex = $placementIndex === false ? 0 : $placementIndex;

        $lessons = Lesson::query()
            ->with([
                'listeningLessons.questionnaire.questions.options',
                'questionnaires.questions.options',
            ])
            ->get()
            ->groupBy('lesson_cefr_level');

        self::prepareLessonsForProgress($lessons->flatten(1));

        $masteredByLesson = self::query()
            ->where('student_id', $student->user_id)
            ->get()
            ->groupBy('lesson_id');

        $unlockedThrough = $placementIndex;

        for ($index = $placementIndex; $index < count(self::CEFR_LEVELS) - 1; $index++) {
            /** @var Collection<int, Lesson> $allLevelLessons */
            $allLevelLessons = $lessons->get(self::CEFR_LEVELS[$index], collect());

            if ($allLevelLessons->isEmpty()) {
                break;
            }

            $levelLessons = $allLevelLessons->filter(
                fn (Lesson $lesson): bool => self::requiredSkillsForLesson($lesson) !== [],
            );

            if ($levelLessons->isEmpty()) {
                $unlockedThrough = $index + 1;

                continue;
            }

            $levelComplete = $levelLessons->every(
                fn (Lesson $lesson): bool => self::lessonIsComplete(
                    $lesson,
                    $masteredByLesson->get($lesson->lesson_id, collect()),
                ),
            );

            if (! $levelComplete) {
                break;
            }

            $unlockedThrough = $index + 1;
        }

        return array_slice(self::CEFR_LEVELS, 0, $unlockedThrough + 1);
    }

    public static function levelIsUnlocked(User $student, string $cefrLevel): bool
    {
        return in_array($cefrLevel, self::unlockedCefrLevels($student), true);
    }

    public static function normalizeSkill(mixed $skill, mixed $type = null): ?string
    {
        $skill = strtolower(trim((string) $skill));
        $type = strtolower(trim((string) $type));

        if ($type === 'speaking' || $skill === 'speaking') {
            return 'speaking';
        }

        if ($type === 'listening' || $skill === 'listening') {
            return 'listening';
        }

        if (in_array($skill, ['reading', 'writing'], true)) {
            return 'reading';
        }

        return null;
    }

    private static function hasText(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    /**
     * @param  array<string, array{type: string, id: string, questionnaire_id: ?string, listening_lesson_id: ?string, question_ids: list<string>}>  $activities
     * @param  list<string>  $questionIds
     */
    private static function addActivity(
        array &$activities,
        string $type,
        string $id,
        ?string $questionnaireId,
        ?string $listeningLessonId,
        array $questionIds,
    ): void {
        $key = $type.':'.$id;

        if (isset($activities[$key])) {
            $activities[$key]['question_ids'] = array_values(array_unique([
                ...$activities[$key]['question_ids'],
                ...$questionIds,
            ]));

            return;
        }

        $activities[$key] = [
            'type' => $type,
            'id' => $id,
            'questionnaire_id' => $questionnaireId,
            'listening_lesson_id' => $listeningLessonId,
            'question_ids' => array_values(array_unique($questionIds)),
        ];
    }

    private static function questionIsDeterministicallyGradable(Question $question): bool
    {
        if ($question->question_type === 'speaking' || $question->question_skill_type === 'speaking') {
            return false;
        }

        if ($question->question_type === 'multiple_choice') {
            return $question->options->contains('is_correct', true);
        }

        return self::hasText($question->correct_answer);
    }

    /**
     * @return array{bool, list<string>}
     */
    private static function gradableListeningAnswers(ListeningLesson $content): array
    {
        $answers = collect($content->answers_data ?? []);
        $questionData = collect($content->questions_data ?? [])->values();
        $questionModels = $content->questionnaire?->questions?->values() ?? collect();
        $questionIds = [];
        $hasGradableAnswer = false;

        foreach ($answers as $number => $answer) {
            if (! self::hasText((string) $answer) || strtolower(trim((string) $answer)) === 'n/a') {
                continue;
            }

            $index = max(0, (int) $number - 1);
            $data = $questionData->first(
                fn (mixed $candidate): bool => is_array($candidate)
                    && (string) ($candidate['number'] ?? '') === (string) $number,
            ) ?? $questionData->get($index, []);
            $data = is_array($data) ? $data : [];
            $question = $questionModels->first(
                fn (Question $candidate): bool => (int) $candidate->question_order > 0
                    && (string) $candidate->question_order === (string) $number,
            ) ?? $questionModels->get($index);
            $isSpeaking = $question?->question_type === 'speaking'
                || $question?->question_skill_type === 'speaking'
                || strtolower((string) ($data['type'] ?? '')) === 'speaking'
                || strtolower((string) ($data['skill'] ?? '')) === 'speaking';

            if ($isSpeaking) {
                continue;
            }

            $hasGradableAnswer = true;
            if ($question) {
                $questionIds[] = $question->question_id;
            }
        }

        return [$hasGradableAnswer, array_values(array_unique($questionIds))];
    }

    /**
     * @param  array{type: string, id: string, questionnaire_id: ?string, listening_lesson_id: ?string, question_ids: list<string>}  $activity
     */
    private static function attemptPassesActivity(
        AttemptLog $attempt,
        array $activity,
        string $skill,
    ): bool {
        $metadataMatches = $attempt->attempt_skill_type === $skill
            && match ($activity['type']) {
                'questionnaire' => $attempt->questionnaire_id === $activity['id'],
                'listening' => $attempt->listening_lesson_id === $activity['id'],
                default => false,
            };

        if ($activity['question_ids'] === []) {
            return $metadataMatches;
        }

        $responses = $attempt->responses
            ->whereIn('question_id', $activity['question_ids'])
            ->keyBy('question_id');

        if (array_diff($activity['question_ids'], $responses->keys()->all()) !== []) {
            return false;
        }

        $score = ($responses->where('is_correct', true)->count() / count($activity['question_ids'])) * 100;

        return $score >= self::PASSING_SCORE;
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function placementTest(): BelongsTo
    {
        return $this->belongsTo(PlacementTest::class, 'placement_test_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }
}
