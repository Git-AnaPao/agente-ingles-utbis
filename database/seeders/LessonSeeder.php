<?php

namespace Database\Seeders;

use App\Models\Lesson;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    public function run(): void
    {
        $lessons = [
            // A1
            ['lesson_cefr_level' => 'A1', 'lesson_sub_level' => 1, 'lesson_skill_type' => 'listening', 'lesson_prompt_payload' => ['topic' => 'Greetings', 'prompt' => 'Hello, how are you?']],
            ['lesson_cefr_level' => 'A1', 'lesson_sub_level' => 1, 'lesson_skill_type' => 'speaking', 'lesson_prompt_payload' => ['topic' => 'Introduce yourself', 'prompt' => 'Say your name and where you are from']],
            ['lesson_cefr_level' => 'A1', 'lesson_sub_level' => 2, 'lesson_skill_type' => 'listening', 'lesson_prompt_payload' => ['topic' => 'The Alphabet', 'prompt' => 'Spell common words']],
            ['lesson_cefr_level' => 'A1', 'lesson_sub_level' => 2, 'lesson_skill_type' => 'speaking', 'lesson_prompt_payload' => ['topic' => 'Verb To Be', 'prompt' => 'I am, you are, he/she/it is']],
            ['lesson_cefr_level' => 'A1', 'lesson_sub_level' => 3, 'lesson_skill_type' => 'listening', 'lesson_prompt_payload' => ['topic' => 'Numbers 1-20', 'prompt' => 'Listen and repeat numbers']],
            ['lesson_cefr_level' => 'A1', 'lesson_sub_level' => 3, 'lesson_skill_type' => 'speaking', 'lesson_prompt_payload' => ['topic' => 'Introduce Yourself', 'prompt' => 'My name is, I am from']],
            // A2
            ['lesson_cefr_level' => 'A2', 'lesson_sub_level' => 1, 'lesson_skill_type' => 'listening', 'lesson_prompt_payload' => ['topic' => 'Present Simple', 'prompt' => 'I eat, you run, she works']],
            ['lesson_cefr_level' => 'A2', 'lesson_sub_level' => 1, 'lesson_skill_type' => 'speaking', 'lesson_prompt_payload' => ['topic' => 'Daily Routine', 'prompt' => 'Describe your daily routine']],
            ['lesson_cefr_level' => 'A2', 'lesson_sub_level' => 2, 'lesson_skill_type' => 'listening', 'lesson_prompt_payload' => ['topic' => 'Family Members', 'prompt' => 'Mother, father, brother']],
            ['lesson_cefr_level' => 'A2', 'lesson_sub_level' => 2, 'lesson_skill_type' => 'speaking', 'lesson_prompt_payload' => ['topic' => 'My Family', 'prompt' => 'Describe your family']],
            // B1
            ['lesson_cefr_level' => 'B1', 'lesson_sub_level' => 1, 'lesson_skill_type' => 'listening', 'lesson_prompt_payload' => ['topic' => 'Future Tense', 'prompt' => 'I will go, I am going to']],
            ['lesson_cefr_level' => 'B1', 'lesson_sub_level' => 1, 'lesson_skill_type' => 'speaking', 'lesson_prompt_payload' => ['topic' => 'Travel Plans', 'prompt' => 'Describe your future travel plans']],
            ['lesson_cefr_level' => 'B1', 'lesson_sub_level' => 2, 'lesson_skill_type' => 'listening', 'lesson_prompt_payload' => ['topic' => 'Comparatives', 'prompt' => 'Bigger, more beautiful']],
            ['lesson_cefr_level' => 'B1', 'lesson_sub_level' => 2, 'lesson_skill_type' => 'speaking', 'lesson_prompt_payload' => ['topic' => 'At the Hotel', 'prompt' => 'I have a reservation']],
            // B2
            ['lesson_cefr_level' => 'B2', 'lesson_sub_level' => 1, 'lesson_skill_type' => 'listening', 'lesson_prompt_payload' => ['topic' => 'Passive Voice', 'prompt' => 'The book was written by']],
            ['lesson_cefr_level' => 'B2', 'lesson_sub_level' => 1, 'lesson_skill_type' => 'speaking', 'lesson_prompt_payload' => ['topic' => 'News & Media', 'prompt' => 'Discuss a news article']],
            ['lesson_cefr_level' => 'B2', 'lesson_sub_level' => 2, 'lesson_skill_type' => 'listening', 'lesson_prompt_payload' => ['topic' => 'Phrasal Verbs', 'prompt' => 'Give up, look after']],
            ['lesson_cefr_level' => 'B2', 'lesson_sub_level' => 2, 'lesson_skill_type' => 'speaking', 'lesson_prompt_payload' => ['topic' => 'Debate & Opinions', 'prompt' => 'Express and defend your ideas']],
            // C1
            ['lesson_cefr_level' => 'C1', 'lesson_sub_level' => 1, 'lesson_skill_type' => 'listening', 'lesson_prompt_payload' => ['topic' => 'Idioms & Proverbs', 'prompt' => 'Break the ice, piece of cake']],
            ['lesson_cefr_level' => 'C1', 'lesson_sub_level' => 1, 'lesson_skill_type' => 'speaking', 'lesson_prompt_payload' => ['topic' => 'Fluent Conversation', 'prompt' => 'Speak fluently on any topic']],
            ['lesson_cefr_level' => 'C1', 'lesson_sub_level' => 2, 'lesson_skill_type' => 'listening', 'lesson_prompt_payload' => ['topic' => 'Nuanced Grammar', 'prompt' => 'Inversions, cleft sentences']],
            ['lesson_cefr_level' => 'C1', 'lesson_sub_level' => 2, 'lesson_skill_type' => 'speaking', 'lesson_prompt_payload' => ['topic' => 'TOEFL/IELTS Prep', 'prompt' => 'Exam strategies and practice']],
            // C2
            ['lesson_cefr_level' => 'C2', 'lesson_sub_level' => 1, 'lesson_skill_type' => 'listening', 'lesson_prompt_payload' => ['topic' => 'Advanced Listening', 'prompt' => 'Academic lectures']],
            ['lesson_cefr_level' => 'C2', 'lesson_sub_level' => 1, 'lesson_skill_type' => 'speaking', 'lesson_prompt_payload' => ['topic' => 'Advanced Speaking', 'prompt' => 'Present complex ideas fluently']],
        ];

        foreach ($lessons as $data) {
            Lesson::create($data);
        }
    }
}
