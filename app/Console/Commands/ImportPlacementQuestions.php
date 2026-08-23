<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionOption;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportPlacementQuestions extends Command
{
    private const CEFR_LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1'];

    protected $signature = 'import:placement-questions
        {--dry-run : Muestra lo que se importaria sin escribir}
        {--force : Re-importa opciones (borra y recrea)}';

    protected $description = 'Importa las 75 preguntas del placement test a questions/question_options';

    public function handle(): int
    {
        $questions = $this->questions();
        $validationErrors = $this->validationErrors($questions);

        foreach ($validationErrors as $error) {
            $this->error($error);
        }

        if ($validationErrors !== []) {
            return self::FAILURE;
        }

        $questionnaire = Questionnaire::query()
            ->where('title', 'Placement Test')
            ->whereNull('lesson_id')
            ->first();

        $dryRun = (bool) $this->option('dry-run');

        if (!$questionnaire) {
            if ($dryRun) {
                $this->line('Se crearia el cuestionario "Placement Test".');
            } else {
                $questionnaire = Questionnaire::create([
                    'lesson_id' => null,
                    'title' => 'Placement Test',
                ]);
                $this->info('Cuestionario "Placement Test" creado.');
            }
        }

        if ($dryRun) {
            $this->info('[dry-run] Se importarian '.count($questions).' preguntas.');

            return self::SUCCESS;
        }

        $imported = 0;
        $force = (bool) $this->option('force');

        DB::transaction(function () use ($questionnaire, $force, $questions, &$imported) {
            foreach ($questions as $data) {
                $question = Question::query()
                    ->where('questionnaire_id', $questionnaire->questionnaire_id)
                    ->where('question_order', $data['order'])
                    ->first();

                if (!$question) {
                    $question = Question::create([
                        'questionnaire_id' => $questionnaire->questionnaire_id,
                        'question_type' => 'multiple_choice',
                        'question_skill_type' => 'reading',
                        'question_order' => $data['order'],
                        'question_passage' => $data['passage'] ?? null,
                        'question_text' => $data['question'],
                        'correct_answer' => $data['options'][$data['correct']],
                    ]);
                } elseif ($force) {
                    $question->update([
                        'question_passage' => $data['passage'] ?? null,
                        'question_text' => $data['question'],
                        'correct_answer' => $data['options'][$data['correct']],
                    ]);
                }

                if ($force || $question->options()->count() === 0) {
                    $question->options()->delete();
                    foreach ($data['options'] as $i => $optionText) {
                        QuestionOption::create([
                            'question_id' => $question->question_id,
                            'option_text' => $optionText,
                            'is_correct' => $i === $data['correct'],
                            'option_order' => $i,
                        ]);
                    }
                }

                $imported++;
            }
        });

        $this->info("Placement importado: {$imported} preguntas en '{$questionnaire->title}'.");

        return self::SUCCESS;
    }

    /**
     * Preguntas del placement: order 1-75, options en orden, indice de la correcta.
     */
    private function questions(): array
    {
        return [
            ['order' => 1, 'level' => 'A1', 'question' => 'I ______ bus on Mondays.', 'correct' => 3,
                'options' => ["'m going to work with", "'m going to work by", "go to work with", "go to work by"]],
            ['order' => 2, 'level' => 'A1', 'question' => 'Sorry, but this chair is ______.', 'correct' => 1,
                'options' => ['Me', 'mine', 'my', 'our']],
            ['order' => 3, 'level' => 'A1', 'question' => "'How old ______?' — 'I ______!'", 'correct' => 0,
                'options' => ['are you / am 20 years old.', 'have you / have 20 years old', 'are you / am 20 years.', 'do you have / have 20 years.']],
            ['order' => 4, 'level' => 'A1', 'question' => 'I ______ to the cinema.', 'correct' => 1,
                'options' => ['not usually go', "don't usually go", "don't go usually", 'do not go usually']],
            ['order' => 5, 'level' => 'A1', 'question' => 'Where ______?', 'correct' => 2,
                'options' => ['your sister works', 'your sister work', 'does your sister work', 'do your sister work']],
            ['order' => 6, 'level' => 'A1', 'question' => 'The test is ______ February.', 'correct' => 0,
                'options' => ['in', 'at', 'on', 'over']],
            ['order' => 7, 'level' => 'A1', 'question' => 'I eat pasta ______ week.', 'correct' => 1,
                'options' => ['twice in a', 'twice a', 'one time a', 'once in a']],
            ['order' => 8, 'level' => 'A1', 'question' => "I don't have ______ free time.", 'correct' => 1,
                'options' => ['many', 'any', 'a lot', 'some']],
            ['order' => 9, 'level' => 'A1', 'question' => 'Somebody stole ______ yesterday.', 'correct' => 2,
                'options' => ['the car of my mother', 'my car mother', "my mother's car", 'my mother car']],
            ['order' => 10, 'level' => 'A1', 'question' => "I ______ this coffee. It tastes horrible.", 'correct' => 1,
                'options' => ["am not like", "don't like", "'m not liking", 'not like']],
            ['order' => 11, 'level' => 'A1', 'question' => 'We ______ yesterday.', 'correct' => 0,
                'options' => ['arrived', 'did arrive', 'have arrive', 'have arrived']],

            ['order' => 12, 'level' => 'A2', 'question' => "'______ to the cinema tomorrow?'", 'correct' => 3,
                'options' => ['We will go', 'Do we go', 'We go', 'Shall we go']],
            ['order' => 13, 'level' => 'A2', 'question' => 'We went to the market ______ some vegetables.', 'correct' => 0,
                'options' => ['to buy', 'for buy', 'for to buy', 'for buying']],
            ['order' => 14, 'level' => 'A2', 'question' => "Sorry, but when you called I ______ a shower.", 'correct' => 2,
                'options' => ['had', 'did have', 'was having', 'were having']],
            ['order' => 15, 'level' => 'A2', 'question' => '______ are very friendly and very intelligent.', 'correct' => 0,
                'options' => ['Dolphins', 'The dolphins', 'A Dolphin', 'The dolphin']],
            ['order' => 16, 'level' => 'A2', 'question' => '______ with me?', 'correct' => 1,
                'options' => ['Do you like to dance', 'Would you like to dance', 'Do you like dance', 'Would you like dancing']],
            ['order' => 17, 'level' => 'A2', 'question' => 'She is ______ her sister, I think.', 'correct' => 3,
                'options' => ['more happier tan', 'more happy that', 'happier that', 'happier than']],
            ['order' => 18, 'level' => 'A2', 'question' => "I couldn't eat ______ before the exam.", 'correct' => 1,
                'options' => ['Nothing', 'Anything', 'Everything', 'something']],
            ['order' => 19, 'level' => 'A2', 'question' => "Please, pass me the remote. ______ TV.", 'correct' => 0,
                'options' => ["I'm watching", 'I will watch', "I'm going to watch", 'I might watch']],
            ['order' => 20, 'level' => 'A2', 'question' => "I'll call you when I ______ home.", 'correct' => 0,
                'options' => ['arrive', "'m going to arrive", 'will arrive', 'arrived']],
            ['order' => 21, 'level' => 'A2', 'question' => '______ Japan?', 'correct' => 2,
                'options' => ['Have you ever gone in', 'Do you have been in', 'Have you ever been to', 'Have you ever been into']],
            ['order' => 22, 'level' => 'A2', 'question' => 'He drives very ______.', 'correct' => 3,
                'options' => ['slow', 'slower', 'more slowly', 'slowly']],
            ['order' => 23, 'level' => 'A2', 'question' => "Can you ______ the lights? I can't see.", 'correct' => 1,
                'options' => ['open', 'turn on', 'start', 'put on']],
            ['order' => 24, 'level' => 'A2', 'question' => "We couldn't find a taxi, ______ we walked home.", 'correct' => 0,
                'options' => ['so', 'because', 'but', 'although']],
            ['order' => 25, 'level' => 'A2', 'question' => "Tomorrow I ______ get up early; it's my day off.", 'correct' => 3,
                'options' => ["mustn't", 'must', "haven't to", "don't have to"]],
            ['order' => 26, 'level' => 'A2', 'question' => "When I arrive home, I'm going to have a ______ bath.", 'correct' => 0,
                'options' => ['relaxing', 'relaxed', 'relax', 'relaxation']],
            ['order' => 27, 'level' => 'A2', 'question' => "A: 'We don't have any milk.' B: 'Really? I ______ more.'", 'correct' => 1,
                'options' => ["'m going to buy", "'ll buy", "'m buying", 'buy']],
            ['order' => 28, 'level' => 'A2', 'question' => 'We ______ to seeing you next Thursday.', 'correct' => 2,
                'options' => ['really want', 'hope', 'are looking forward', 'really wish']],
            ['order' => 29, 'level' => 'A2', 'question' => "I'd like to go ______ in the park.", 'correct' => 2,
                'options' => ['to walking', 'for walk', 'for a walk', 'to walk']],
            ['order' => 30, 'level' => 'A2', 'question' => 'German ______ in Germany, Austria and Switzerland.', 'correct' => 0,
                'options' => ['is spoken', 'spoken', 'speaks', 'is speak']],

            ['order' => 31, 'level' => 'B1', 'question' => 'James has considerable _________ about advertising.', 'correct' => 1,
                'options' => ['information', 'knowledge', 'communication', 'intelligence']],
            ['order' => 32, 'level' => 'B1', 'question' => "He worked very long hours, but the ________ was a higher salary than anyone else he knew.", 'correct' => 2,
                'options' => ['Repay', 'Refund', 'Reward', 'Revenue']],
            ['order' => 33, 'level' => 'B1', 'question' => "Her job kept her very busy and she certainly had a hectic travel schedule, but she was such a high-energy person that she never really found it ______.", 'correct' => 3,
                'options' => ['inspiring', 'stimulating', 'disappointing', 'stressful']],
            ['order' => 34, 'level' => 'B1', 'question' => "After multiple field trials with healthy adults who reported no benefit, the drug company decided to withdraw its claim that the new pain reliever was ______.", 'correct' => 3,
                'options' => ['dynamic', 'vigorous', 'operational', 'effective']],
            ['order' => 35, 'level' => 'B1', 'question' => "My sister isn't interested ______ watching horror movies.", 'correct' => 1,
                'options' => ['On', 'in', 'at', 'for']],
            ['order' => 36, 'level' => 'B1', 'question' => 'By the time we arrived, the concert ______.', 'correct' => 2,
                'options' => ['started', 'has started', 'had started', 'was starting']],
            ['order' => 37, 'level' => 'B1', 'question' => 'She apologized ______ being late.', 'correct' => 1,
                'options' => ['Of', 'For', 'With', 'about']],
            ['order' => 38, 'level' => 'B1', 'question' => 'If I ______ enough money, I would travel around Europe.', 'correct' => 1,
                'options' => ['have', 'had', 'will have', 'would have']],
            ['order' => 39, 'level' => 'B1', 'question' => 'According to the survey mentioned in paragraph 1, how many hours do people typically spend sitting per day?', 'correct' => 2,
                'options' => ['7', '10', '12', '19'], 'passage' => 'b1_reading'],
            ['order' => 40, 'level' => 'B1', 'question' => 'Why does the article mention watching TV and sleeping in paragraph 1?', 'correct' => 1,
                'options' => ['To show how even active adults may be at risk', 'To emphasize just how inactive many people are', 'To contrast effects of sitting and standing', 'To explain the full results of the survey'], 'passage' => 'b1_reading'],
            ['order' => 41, 'level' => 'B1', 'question' => 'How did some of the volunteers feel at the beginning of the University of Chester experiment?', 'correct' => 1,
                'options' => ['Excited', 'Worried', 'Confident', 'Surprised'], 'passage' => 'b1_reading'],
            ['order' => 42, 'level' => 'B1', 'question' => "The University of Chester researchers found that standing caused volunteers'", 'correct' => 0,
                'options' => ['blood sugar levels to drop to normal much faster after a meal.', 'blood sugar levels to stay stable longer during the course of the workday.', 'weight to go down over the period of the experiment.', 'appetite to increase on the days they spent standing.'], 'passage' => 'b1_reading'],
            ['order' => 43, 'level' => 'B1', 'question' => 'What is the article mainly about?', 'correct' => 2,
                'options' => ['The health benefits of sitting and resting', 'The health benefits of sleeping seven hours a night', 'The health benefits of standing more', 'The health benefits of regular exercise'], 'passage' => 'b1_reading'],

            ['order' => 44, 'level' => 'B2', 'question' => 'A new gaming company is about to ____ the video game industry.', 'correct' => 3,
                'options' => ['Bully', 'Govern', 'Administer', 'Monopolize']],
            ['order' => 45, 'level' => 'B2', 'question' => 'The number of students has _____ in less than a year and now our student body has already doubled.', 'correct' => 1,
                'options' => ['added', 'multiplied', 'raised', 'boosted']],
            ['order' => 46, 'level' => 'B2', 'question' => "The company's total debt was _______, and that alone made it impossible to imagine a successful future.", 'correct' => 3,
                'options' => ['profuse', 'ample', 'generous', 'massive']],
            ['order' => 47, 'level' => 'B2', 'question' => 'If you want to create a sound argument that can withstand challenges from opponents, you cannot ______ evidence that might undermine your claims.', 'correct' => 2,
                'options' => ['deplete', 'accentuate', 'ignore', 'safeguard']],
            ['order' => 48, 'level' => 'B2', 'question' => 'He eventually ______ the difficulties and finished the project successfully.', 'correct' => 0,
                'options' => ['got over', 'looked after', 'ran into', 'came across']],
            ['order' => 49, 'level' => 'B2', 'question' => 'The report is believed ______ by one of the senior researchers.', 'correct' => 2,
                'options' => ['Writing', 'to have written', 'to have been written', 'to write']],
            ['order' => 50, 'level' => 'B2', 'question' => 'Not only ______ late, but he also forgot the documents.', 'correct' => 1,
                'options' => ['he arrived', 'did he arrive', 'he did arrive', 'arrived he']],
            ['order' => 51, 'level' => 'B2', 'question' => 'The company is considering several measures ______ costs.', 'correct' => 2,
                'options' => ['Reducing', 'Reduce', 'to reduce', 'reduced']],
            ['order' => 52, 'level' => 'B2', 'question' => 'What is the article mainly about?', 'correct' => 1,
                'options' => ['How cocoa beans are processed into chocolate', 'Why chocolate prices are expected to rise worldwide', 'Why chocolate is becoming popular only in China', 'How chocolate companies advertise their products'], 'passage' => 'b2_reading'],
            ['order' => 53, 'level' => 'B2', 'question' => 'Why does the author believe that the price of chocolate is likely to increase in the future?', 'correct' => 0,
                'options' => ['The supply of chocolate will be smaller than the demand for it.', 'Cocoa beans are difficult to grow.', 'Chocolate manufacturers will enter new markets around the world.', 'There will be a bigger demand for luxury foods.'], 'passage' => 'b2_reading'],
            ['order' => 54, 'level' => 'B2', 'question' => 'Why will Greg Johnson continue to buy chocolates?', 'correct' => 2,
                'options' => ['He loves chocolate for himself.', 'He has a lot of money to spend.', 'He thinks chocolate makes many people feel happy.', 'He thinks chocolate is not an expensive gift.'], 'passage' => 'b2_reading'],
            ['order' => 55, 'level' => 'B2', 'question' => 'What does the author think that some chocolate companies will do in the future?', 'correct' => 1,
                'options' => ['Make more candy bars', 'Make smaller chocolate bars', 'Make other types of candy', 'Make new contracts with cocoa farmers'], 'passage' => 'b2_reading'],
            ['order' => 56, 'level' => 'B2', 'question' => 'What will the author of the article probably do soon?', 'correct' => 1,
                'options' => ['Stop buying chocolate for a gift', 'Buy a lot of chocolate before it becomes more expensive', 'Stop eating chocolate', 'Do further research on the chocolate industry'], 'passage' => 'b2_reading'],

            ['order' => 57, 'level' => 'C1', 'question' => 'Hardly ______ the meeting when the CEO announced his resignation.', 'correct' => 0,
                'options' => ['had we started', 'we had started', 'did we start', 'we started']],
            ['order' => 58, 'level' => 'C1', 'question' => 'If the company had invested in new technology five years ago, it ______ much more competitive today.', 'correct' => 0,
                'options' => ['would be', 'would have been', 'will be', 'had been']],
            ['order' => 59, 'level' => 'C1', 'question' => 'Never before ______ such a remarkable scientific discovery.', 'correct' => 1,
                'options' => ['I have witnessed', 'have I witnessed', 'I had witnessed', 'had I witnessed']],
            ['order' => 60, 'level' => 'C1', 'question' => 'The proposal was rejected, ______ surprised everyone in the department.', 'correct' => 2,
                'options' => ['that', 'what', 'which', 'whose']],
            ['order' => 61, 'level' => 'C1', 'question' => "She speaks English so fluently that people often mistake her ______ a native speaker.", 'correct' => 1,
                'options' => ['by', 'for', 'as', 'with']],
            ['order' => 62, 'level' => 'C1', 'question' => "You ______ the instructions more carefully; the mistake could have been avoided.", 'correct' => 1,
                'options' => ['should read', 'should have read', 'must read', 'ought read']],
            ['order' => 63, 'level' => 'C1', 'question' => 'The new policy has had far-reaching ______ for the education system.', 'correct' => 0,
                'options' => ['Consequences', 'Conclusions', 'Circumstances', 'conditions']],
            ['order' => 64, 'level' => 'C1', 'question' => 'Had the weather been better, the event ______ outdoors.', 'correct' => 1,
                'options' => ['would hold', 'would have been held', 'would be holding', 'had held']],
            ['order' => 65, 'level' => 'C1', 'question' => "His explanation didn't ______ with the evidence presented during the investigation.", 'correct' => 0,
                'options' => ['match up', 'get over', 'look after', 'bring out']],
            ['order' => 66, 'level' => 'C1', 'question' => 'The researcher insisted that every result ______ verified before publication.', 'correct' => 2,
                'options' => ['is', 'was', 'be', 'being']],
            ['order' => 67, 'level' => 'C1', 'question' => "It was not until the data had been analyzed ______ the researchers realized the significance of their findings.", 'correct' => 1,
                'options' => ['when', 'that', 'which', 'where']],
            ['order' => 68, 'level' => 'C1', 'question' => 'Although the evidence was largely ______, the jury reached a unanimous verdict.', 'correct' => 0,
                'options' => ['circumstantial', 'temporary', 'optional', 'theoretical']],
            ['order' => 69, 'level' => 'C1', 'question' => 'Little ______ that the small startup would eventually become one of the world\'s leading technology companies.', 'correct' => 0,
                'options' => ['did anyone know', 'anyone knew', 'had anyone known', 'anyone had known']],
            ['order' => 70, 'level' => 'C1', 'question' => "Despite ______ extensive research, the scientists were unable to determine the exact cause of the phenomenon.", 'correct' => 1,
                'options' => ['conducting', 'having conducted', 'conduct', 'conducted']],
            ['order' => 71, 'level' => 'C1', 'question' => 'What is the main purpose of the article?', 'correct' => 1,
                'options' => ['To explain why technology should be avoided in the workplace.', 'To discuss both the benefits and drawbacks of constant digital connectivity.', 'To encourage companies to eliminate remote work.', 'To describe recent technological innovations.'], 'passage' => 'c1_reading'],
            ['order' => 72, 'level' => 'C1', 'question' => 'According to the author, why do many employees continue answering emails after work?', 'correct' => 1,
                'options' => ['Their employers require them to work overtime.', 'They worry that ignoring messages may affect how committed they appear.', 'They enjoy working outside office hours.', 'They receive financial rewards for answering quickly.'], 'passage' => 'c1_reading'],
            ['order' => 73, 'level' => 'C1', 'question' => 'What does the author imply about digital notifications?', 'correct' => 2,
                'options' => ['They rarely influence productivity.', 'They improve people\'s concentration.', "Their combined effect can reduce people's ability to focus deeply.", 'They should be permanently disabled.'], 'passage' => 'c1_reading'],
            ['order' => 74, 'level' => 'C1', 'question' => "Which statement best reflects the author's opinion?", 'correct' => 2,
                'options' => ['Technology is the primary cause of stress in modern society.', 'Technology should be replaced with traditional communication methods.', 'The way people use technology is more important than the technology itself.', 'Social media is responsible for lower workplace productivity.'], 'passage' => 'c1_reading'],
            ['order' => 75, 'level' => 'C1', 'question' => 'Which title would best summarize the article?', 'correct' => 2,
                'options' => ['Why Smartphones Are Destroying Modern Society', 'The Future of Artificial Intelligence', 'Finding Balance in an Always-Connected World', 'The History of Digital Communication'], 'passage' => 'c1_reading'],
        ];
    }

    private function validationErrors(array $questions): array
    {
        $errors = [];

        if (count($questions) !== 75) {
            $errors[] = 'El placement debe contener exactamente 75 preguntas.';
        }

        foreach ($questions as $index => $data) {
            $expectedOrder = $index + 1;
            $order = $data['order'] ?? null;

            if ($order !== $expectedOrder) {
                $errors[] = "Orden invalido en placement: se esperaba {$expectedOrder}.";
            }

            if (!in_array($data['level'] ?? null, self::CEFR_LEVELS, true)) {
                $errors[] = "Nivel CEFR invalido en placement Q{$expectedOrder}.";
            }

            if (!is_string($data['question'] ?? null) || trim($data['question']) === '') {
                $errors[] = "Texto vacio en placement Q{$expectedOrder}.";
            }

            $options = $data['options'] ?? null;
            if (!is_array($options) || count($options) < 2) {
                $errors[] = "Opciones invalidas en placement Q{$expectedOrder}.";
                continue;
            }

            if (count(array_unique($options)) !== count($options)
                || collect($options)->contains(fn ($option): bool => !is_string($option) || trim($option) === '')) {
                $errors[] = "Opciones vacias o duplicadas en placement Q{$expectedOrder}.";
            }

            $correct = $data['correct'] ?? null;
            if (!is_int($correct) || !array_key_exists($correct, $options)) {
                $errors[] = "Clave correcta invalida en placement Q{$expectedOrder}.";
            }
        }

        return $errors;
    }
}
