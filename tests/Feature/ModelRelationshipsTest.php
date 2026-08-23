<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_resources_and_resource_questions_use_questionnaire_keys(): void
    {
        $lesson = Lesson::create([
            'lesson_cefr_level' => 'A1',
            'lesson_sub_level' => 1,
            'lesson_prompt_payload' => ['topic' => 'Introductions'],
        ]);
        $questionnaire = Questionnaire::create([
            'lesson_id' => $lesson->lesson_id,
            'title' => 'Introductions',
        ]);
        $resource = Resource::create([
            'questionnaire_id' => $questionnaire->questionnaire_id,
            'resource_type' => 'text',
            'resource_url' => 'local://introductions',
        ]);
        $question = Question::create([
            'questionnaire_id' => $questionnaire->questionnaire_id,
            'question_type' => 'fill_blank',
            'question_skill_type' => 'reading',
            'question_text' => 'My name ___ Alex.',
            'correct_answer' => 'is',
        ]);

        $otherQuestionnaire = Questionnaire::create([
            'lesson_id' => $lesson->lesson_id,
            'title' => 'Other questionnaire',
        ]);
        $otherQuestion = Question::create([
            'questionnaire_id' => $otherQuestionnaire->questionnaire_id,
            'question_type' => 'fill_blank',
            'question_skill_type' => 'reading',
            'question_text' => 'Unrelated question',
            'correct_answer' => 'answer',
        ]);

        $this->assertInstanceOf(HasManyThrough::class, $lesson->resources());
        $this->assertTrue($lesson->resources->contains($resource));
        $this->assertTrue($resource->questions->contains($question));
        $this->assertFalse($resource->questions->contains($otherQuestion));
    }
}
