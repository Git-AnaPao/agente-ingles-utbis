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
            ['lesson_cefr_level' => 'A1', 'lesson_sub_level' => 1, 'lesson_prompt_payload' => ['topic' => 'Greetings', 'prompt' => 'Hello, how are you? Practice introducing yourself.']],
            ['lesson_cefr_level' => 'A1', 'lesson_sub_level' => 2, 'lesson_prompt_payload' => ['topic' => 'The Alphabet & Verb To Be', 'prompt' => 'Spell common words. I am, you are, he/she/it is.']],
            ['lesson_cefr_level' => 'A1', 'lesson_sub_level' => 3, 'lesson_prompt_payload' => ['topic' => 'Numbers & Introductions', 'prompt' => 'Listen and repeat numbers 1-20. Say your name and where you are from.']],
            // A2
            ['lesson_cefr_level' => 'A2', 'lesson_sub_level' => 1, 'lesson_prompt_payload' => ['topic' => 'Present Simple & Daily Routine', 'prompt' => 'I eat, you run, she works. Describe your daily routine.']],
            ['lesson_cefr_level' => 'A2', 'lesson_sub_level' => 2, 'lesson_prompt_payload' => ['topic' => 'Family Members', 'prompt' => 'Mother, father, brother. Describe your family.']],
            // B1
            ['lesson_cefr_level' => 'B1', 'lesson_sub_level' => 1, 'lesson_prompt_payload' => ['topic' => 'Future Tense & Travel', 'prompt' => 'I will go, I am going to. Describe your future travel plans.']],
            ['lesson_cefr_level' => 'B1', 'lesson_sub_level' => 2, 'lesson_prompt_payload' => ['topic' => 'Comparatives & Hotel', 'prompt' => 'Bigger, more beautiful. I have a reservation.']],
            // B2
            ['lesson_cefr_level' => 'B2', 'lesson_sub_level' => 1, 'lesson_prompt_payload' => ['topic' => 'Passive Voice & News', 'prompt' => 'The book was written by. Discuss a news article.']],
            ['lesson_cefr_level' => 'B2', 'lesson_sub_level' => 2, 'lesson_prompt_payload' => ['topic' => 'Phrasal Verbs & Debate', 'prompt' => 'Give up, look after. Express and defend your ideas.']],
            // C1
            ['lesson_cefr_level' => 'C1', 'lesson_sub_level' => 1, 'lesson_prompt_payload' => ['topic' => 'Idioms & Conversation', 'prompt' => 'Break the ice, piece of cake. Speak fluently on any topic.']],
            ['lesson_cefr_level' => 'C1', 'lesson_sub_level' => 2, 'lesson_prompt_payload' => ['topic' => 'Nuanced Grammar & Exam Prep', 'prompt' => 'Inversions, cleft sentences. TOEFL/IELTS strategies.']],
            // C2
            ['lesson_cefr_level' => 'C2', 'lesson_sub_level' => 1, 'lesson_prompt_payload' => ['topic' => 'Advanced Listening & Speaking', 'prompt' => 'Academic lectures. Present complex ideas fluently.']],
        ];

        foreach ($lessons as $data) {
            Lesson::firstOrCreate(
                [
                    'lesson_cefr_level' => $data['lesson_cefr_level'],
                    'lesson_sub_level' => $data['lesson_sub_level'],
                ],
                ['lesson_prompt_payload' => $data['lesson_prompt_payload']],
            );
        }
    }
}
