<?php

namespace App\Http\Controllers;

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

        $questions = $this->getQuestions();

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
            'answers' => 'required|array|min:75',
        ]);

        $questions = [
            'A1' => range(1, 11),
            'A2' => range(12, 30),
            'B1' => range(31, 43),
            'B2' => range(44, 56),
            'C1' => range(57, 75),
        ];

        $correctAnswers = [
            1 => 3, 2 => 1, 3 => 0, 4 => 1, 5 => 2, 6 => 0, 7 => 1,
            8 => 1, 9 => 2, 10 => 1, 11 => 0,
            12 => 3, 13 => 0, 14 => 2, 15 => 0, 16 => 1, 17 => 3,
            18 => 1, 19 => 0, 20 => 0, 21 => 2, 22 => 3, 23 => 1,
            24 => 0, 25 => 3, 26 => 0, 27 => 1, 28 => 2, 29 => 2,
            30 => 0,
            31 => 1, 32 => 2, 33 => 3, 34 => 3, 35 => 1, 36 => 2,
            37 => 1, 38 => 1, 39 => 2, 40 => 1, 41 => 1, 42 => 0,
            43 => 2,
            44 => 3, 45 => 1, 46 => 3, 47 => 2, 48 => 0, 49 => 2,
            50 => 1, 51 => 2, 52 => 0, 53 => 0, 54 => 2, 55 => 1,
            56 => 1,
            57 => 0, 58 => 0, 59 => 1, 60 => 2, 61 => 1, 62 => 1,
            63 => 0, 64 => 1, 65 => 0, 66 => 2, 67 => 1, 68 => 0,
            69 => 0, 70 => 1,
            71 => 1, 72 => 1, 73 => 2, 74 => 2, 75 => 2,
        ];

        $cefrOrder = ['A1', 'A2', 'B1', 'B2', 'C1'];
        $totalCorrect = 0;
        $levelBreakdown = [];

        foreach ($cefrOrder as $level) {
            $levelQuestions = $questions[$level];
            $correctInLevel = 0;

            foreach ($levelQuestions as $qId) {
                $userAnswer = $request->answers[$qId] ?? null;
                if ($userAnswer == $correctAnswers[$qId]) {
                    $correctInLevel++;
                    $totalCorrect++;
                }
            }

            $levelBreakdown[$level] = [
                'correct' => $correctInLevel,
                'total' => count($levelQuestions),
            ];
        }

        $placedLevel = 'A1';
        foreach ($cefrOrder as $level) {
            $info = $levelBreakdown[$level];
            if ($info['correct'] >= ceil($info['total'] * 0.6)) {
                $placedLevel = $level;
            } else {
                break;
            }
        }

        $score = ($totalCorrect / 75) * 100;

        PlacementTest::create([
            'student_id' => $user->user_id,
            'result_level' => $placedLevel,
            'score' => $score,
            'correct_answers' => $totalCorrect,
            'total_questions' => 75,
            'level_breakdown' => json_encode($levelBreakdown),
        ]);

        $resultsData = [
            'level' => $placedLevel,
            'score' => round($score, 1),
            'correct' => $totalCorrect,
            'total' => 75,
            'breakdown' => $levelBreakdown,
        ];

        return view('placement.index', [
            'questions' => $this->getQuestions(),
            'resultsData' => $resultsData,
        ]);
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

    private function getQuestions(): array
    {
        return [
            // ═══════════════════════════════════════════════
            // A1 (11 questions)
            // ═══════════════════════════════════════════════
            ['id' => 1, 'level' => 'A1', 'question' => 'I ______ bus on Mondays.',
                'options' => ["'m going to work with", "'m going to work by", "go to work with", "go to work by"]],
            ['id' => 2, 'level' => 'A1', 'question' => 'Sorry, but this chair is ______.',
                'options' => ['Me', 'mine', 'my', 'our']],
            ['id' => 3, 'level' => 'A1', 'question' => "'How old ______?' — 'I ______!'",
                'options' => ['are you / am 20 years old.', 'have you / have 20 years old', 'are you / am 20 years.', 'do you have / have 20 years.']],
            ['id' => 4, 'level' => 'A1', 'question' => 'I ______ to the cinema.',
                'options' => ['not usually go', "don't usually go", "don't go usually", 'do not go usually']],
            ['id' => 5, 'level' => 'A1', 'question' => 'Where ______?',
                'options' => ['your sister works', 'your sister work', 'does your sister work', 'do your sister work']],
            ['id' => 6, 'level' => 'A1', 'question' => 'The test is ______ February.',
                'options' => ['in', 'at', 'on', 'over']],
            ['id' => 7, 'level' => 'A1', 'question' => 'I eat pasta ______ week.',
                'options' => ['twice in a', 'twice a', 'one time a', 'once in a']],
            ['id' => 8, 'level' => 'A1', 'question' => "I don't have ______ free time.",
                'options' => ['many', 'any', 'a lot', 'some']],
            ['id' => 9, 'level' => 'A1', 'question' => 'Somebody stole ______ yesterday.',
                'options' => ['the car of my mother', 'my car mother', "my mother's car", 'my mother car']],
            ['id' => 10, 'level' => 'A1', 'question' => "I ______ this coffee. It tastes horrible.",
                'options' => ["am not like", "don't like", "'m not liking", 'not like']],
            ['id' => 11, 'level' => 'A1', 'question' => 'We ______ yesterday.',
                'options' => ['arrived', 'did arrive', 'have arrive', 'have arrived']],

            // ═══════════════════════════════════════════════
            // A2 (19 questions)
            // ═══════════════════════════════════════════════
            ['id' => 12, 'level' => 'A2', 'question' => "'______ to the cinema tomorrow?'",
                'options' => ['We will go', 'Do we go', 'We go', 'Shall we go']],
            ['id' => 13, 'level' => 'A2', 'question' => 'We went to the market ______ some vegetables.',
                'options' => ['to buy', 'for buy', 'for to buy', 'for buying']],
            ['id' => 14, 'level' => 'A2', 'question' => "Sorry, but when you called I ______ a shower.",
                'options' => ['had', 'did have', 'was having', 'were having']],
            ['id' => 15, 'level' => 'A2', 'question' => '______ are very friendly and very intelligent.',
                'options' => ['Dolphins', 'The dolphins', 'A Dolphin', 'The dolphin']],
            ['id' => 16, 'level' => 'A2', 'question' => '______ with me?',
                'options' => ['Do you like to dance', 'Would you like to dance', 'Do you like dance', 'Would you like dancing']],
            ['id' => 17, 'level' => 'A2', 'question' => 'She is ______ her sister, I think.',
                'options' => ['more happier tan', 'more happy that', 'happier that', 'happier than']],
            ['id' => 18, 'level' => 'A2', 'question' => "I couldn't eat ______ before the exam.",
                'options' => ['Nothing', 'Anything', 'Everything', 'something']],
            ['id' => 19, 'level' => 'A2', 'question' => "Please, pass me the remote. ______ TV.",
                'options' => ["I'm watching", 'I will watch', "I'm going to watch", 'I might watch']],
            ['id' => 20, 'level' => 'A2', 'question' => "I'll call you when I ______ home.",
                'options' => ['arrive', "'m going to arrive", 'will arrive', 'arrived']],
            ['id' => 21, 'level' => 'A2', 'question' => '______ Japan?',
                'options' => ['Have you ever gone in', 'Do you have been in', 'Have you ever been to', 'Have you ever been into']],
            ['id' => 22, 'level' => 'A2', 'question' => 'He drives very ______.',
                'options' => ['slow', 'slower', 'more slowly', 'slowly']],
            ['id' => 23, 'level' => 'A2', 'question' => "Can you ______ the lights? I can't see.",
                'options' => ['open', 'turn on', 'start', 'put on']],
            ['id' => 24, 'level' => 'A2', 'question' => "We couldn't find a taxi, ______ we walked home.",
                'options' => ['so', 'because', 'but', 'although']],
            ['id' => 25, 'level' => 'A2', 'question' => "Tomorrow I ______ get up early; it's my day off.",
                'options' => ["mustn't", 'must', "haven't to", "don't have to"]],
            ['id' => 26, 'level' => 'A2', 'question' => "When I arrive home, I'm going to have a ______ bath.",
                'options' => ['relaxing', 'relaxed', 'relax', 'relaxation']],
            ['id' => 27, 'level' => 'A2', 'question' => "A: 'We don't have any milk.' B: 'Really? I ______ more.'",
                'options' => ["'m going to buy", "'ll buy", "'m buying", 'buy']],
            ['id' => 28, 'level' => 'A2', 'question' => 'We ______ to seeing you next Thursday.',
                'options' => ['really want', 'hope', 'are looking forward', 'really wish']],
            ['id' => 29, 'level' => 'A2', 'question' => "I'd like to go ______ in the park.",
                'options' => ['to walking', 'for walk', 'for a walk', 'to walk']],
            ['id' => 30, 'level' => 'A2', 'question' => 'German ______ in Germany, Austria and Switzerland.',
                'options' => ['is spoken', 'spoken', 'speaks', 'is speak']],

            // ═══════════════════════════════════════════════
            // B1 (13 questions)
            // ═══════════════════════════════════════════════
            ['id' => 31, 'level' => 'B1', 'question' => 'James has considerable _________ about advertising.',
                'options' => ['information', 'knowledge', 'communication', 'intelligence']],
            ['id' => 32, 'level' => 'B1', 'question' => "He worked very long hours, but the ________ was a higher salary than anyone else he knew.",
                'options' => ['Repay', 'Refund', 'Reward', 'Revenue']],
            ['id' => 33, 'level' => 'B1', 'question' => "Her job kept her very busy and she certainly had a hectic travel schedule, but she was such a high-energy person that she never really found it ______.",
                'options' => ['inspiring', 'stimulating', 'disappointing', 'stressful']],
            ['id' => 34, 'level' => 'B1', 'question' => "After multiple field trials with healthy adults who reported no benefit, the drug company decided to withdraw its claim that the new pain reliever was ______.",
                'options' => ['dynamic', 'vigorous', 'operational', 'effective']],
            ['id' => 35, 'level' => 'B1', 'question' => "My sister isn't interested ______ watching horror movies.",
                'options' => ['On', 'in', 'at', 'for']],
            ['id' => 36, 'level' => 'B1', 'question' => 'By the time we arrived, the concert ______.',
                'options' => ['started', 'has started', 'had started', 'was starting']],
            ['id' => 37, 'level' => 'B1', 'question' => 'She apologized ______ being late.',
                'options' => ['Of', 'For', 'With', 'about']],
            ['id' => 38, 'level' => 'B1', 'question' => 'If I ______ enough money, I would travel around Europe.',
                'options' => ['have', 'had', 'will have', 'would have']],
            ['id' => 39, 'level' => 'B1', 'question' => 'According to the survey mentioned in paragraph 1, how many hours do people typically spend sitting per day?',
                'options' => ['7', '10', '12', '19'], 'passage' => 'b1_reading'],
            ['id' => 40, 'level' => 'B1', 'question' => 'Why does the article mention watching TV and sleeping in paragraph 1?',
                'options' => ['To show how even active adults may be at risk', 'To emphasize just how inactive many people are', 'To contrast effects of sitting and standing', 'To explain the full results of the survey'], 'passage' => 'b1_reading'],
            ['id' => 41, 'level' => 'B1', 'question' => 'How did some of the volunteers feel at the beginning of the University of Chester experiment?',
                'options' => ['Hurt', 'Worried', 'Upset', 'Unhappy'], 'passage' => 'b1_reading'],
            ['id' => 42, 'level' => 'B1', 'question' => "The University of Chester researchers found that standing caused volunteers'",
                'options' => ['blood sugar levels to drop to normal much faster after a meal.', 'blood sugar levels to stay stable longer during the course of the workday.', 'weight to go down over the period of the experiment.', 'appetite to increase on the days they spent standing.'], 'passage' => 'b1_reading'],
            ['id' => 43, 'level' => 'B1', 'question' => 'What is the article mainly about?',
                'options' => ['The health benefits of sitting and resting', 'The health benefits of sleeping seven hours a night', 'The health benefits of standing more', 'The health benefits of regular exercise'], 'passage' => 'b1_reading'],

            // ═══════════════════════════════════════════════
            // B2 (14 questions)
            // ═══════════════════════════════════════════════
            ['id' => 44, 'level' => 'B2', 'question' => 'A new gaming company is about to ____ the video game industry.',
                'options' => ['Bully', 'Govern', 'Administer', 'Monopolize']],
            ['id' => 45, 'level' => 'B2', 'question' => 'The number of students has _____ in less than a year and now our student body has already doubled.',
                'options' => ['added', 'multiplied', 'raised', 'boosted']],
            ['id' => 46, 'level' => 'B2', 'question' => "The company's total debt was _______, and that alone made it impossible to imagine a successful future.",
                'options' => ['profuse', 'ample', 'generous', 'massive']],
            ['id' => 47, 'level' => 'B2', 'question' => 'If you want to create a sound argument that can withstand challenges from opponents, you cannot ______ evidence that might undermine your claims.',
                'options' => ['deplete', 'accentuate', 'ignore', 'safeguard']],
            ['id' => 48, 'level' => 'B2', 'question' => 'He eventually ______ the difficulties and finished the project successfully.',
                'options' => ['got over', 'looked after', 'ran into', 'came across']],
            ['id' => 49, 'level' => 'B2', 'question' => 'The report is believed ______ by one of the senior researchers.',
                'options' => ['Writing', 'to have written', 'to have been written', 'to write']],
            ['id' => 50, 'level' => 'B2', 'question' => 'Not only ______ late, but he also forgot the documents.',
                'options' => ['he arrived', 'did he arrive', 'he did arrive', 'arrived he']],
            ['id' => 51, 'level' => 'B2', 'question' => 'The company is considering several measures ______ costs.',
                'options' => ['Reducing', 'Reduce', 'to reduce', 'reduced']],
            ['id' => 52, 'level' => 'B2', 'question' => 'What is the article mainly about?',
                'options' => ['The current market for chocolate', 'The market for chocolate in China', 'The ways cocoa is processed to make chocolate', 'The reasons chocolate is more and more popular'], 'passage' => 'b2_reading'],
            ['id' => 53, 'level' => 'B2', 'question' => 'Why does the author believe that the price of chocolate is likely to increase in the future?',
                'options' => ['The supply of chocolate will be smaller than the demand for it.', 'Cocoa beans are difficult to grow.', 'Chocolate manufacturers will enter new markets around the world.', 'There will be a bigger demand for luxury foods.'], 'passage' => 'b2_reading'],
            ['id' => 54, 'level' => 'B2', 'question' => 'Why will Greg Johnson continue to buy chocolates?',
                'options' => ['He loves chocolate for himself.', 'He has a lot of money to spend.', 'He thinks chocolate makes many people feel happy.', 'He thinks chocolate is not an expensive gift.'], 'passage' => 'b2_reading'],
            ['id' => 55, 'level' => 'B2', 'question' => 'What does the author think that some chocolate companies will do in the future?',
                'options' => ['Make more candy bars', 'Make smaller chocolate bars', 'Make other types of candy', 'Make new contracts with cocoa farmers'], 'passage' => 'b2_reading'],
            ['id' => 56, 'level' => 'B2', 'question' => 'What will the author of the article probably do soon?',
                'options' => ['Stop buying chocolate for a gift', 'Buy a lot of chocolate before it becomes more expensive', 'Stop eating chocolate', 'Do further research on the chocolate industry'], 'passage' => 'b2_reading'],

            // ═══════════════════════════════════════════════
            // C1 (19 questions)
            // ═══════════════════════════════════════════════
            ['id' => 57, 'level' => 'C1', 'question' => 'Hardly ______ the meeting when the CEO announced his resignation.',
                'options' => ['had we started', 'we had started', 'did we start', 'we started']],
            ['id' => 58, 'level' => 'C1', 'question' => 'If the company had invested in new technology five years ago, it ______ much more competitive today.',
                'options' => ['would be', 'would have been', 'will be', 'had been']],
            ['id' => 59, 'level' => 'C1', 'question' => 'Never before ______ such a remarkable scientific discovery.',
                'options' => ['I have witnessed', 'have I witnessed', 'I had witnessed', 'had I witnessed']],
            ['id' => 60, 'level' => 'C1', 'question' => 'The proposal was rejected, ______ surprised everyone in the department.',
                'options' => ['that', 'what', 'which', 'whose']],
            ['id' => 61, 'level' => 'C1', 'question' => "She speaks English so fluently that people often mistake her ______ a native speaker.",
                'options' => ['by', 'for', 'as', 'with']],
            ['id' => 62, 'level' => 'C1', 'question' => "You ______ the instructions more carefully; the mistake could have been avoided.",
                'options' => ['should read', 'should have read', 'must read', 'ought read']],
            ['id' => 63, 'level' => 'C1', 'question' => 'The new policy has had far-reaching ______ for the education system.',
                'options' => ['Consequences', 'Conclusions', 'Circumstances', 'conditions']],
            ['id' => 64, 'level' => 'C1', 'question' => 'Had the weather been better, the event ______ outdoors.',
                'options' => ['would hold', 'would have been held', 'would be holding', 'had held']],
            ['id' => 65, 'level' => 'C1', 'question' => "His explanation didn't ______ with the evidence presented during the investigation.",
                'options' => ['match up', 'get over', 'look after', 'bring out']],
            ['id' => 66, 'level' => 'C1', 'question' => 'The researcher insisted that every result ______ verified before publication.',
                'options' => ['is', 'was', 'be', 'being']],
            ['id' => 67, 'level' => 'C1', 'question' => "It was not until the data had been analyzed ______ the researchers realized the significance of their findings.",
                'options' => ['when', 'that', 'which', 'where']],
            ['id' => 68, 'level' => 'C1', 'question' => 'Although the evidence was largely ______, the jury reached a unanimous verdict.',
                'options' => ['circumstantial', 'temporary', 'optional', 'theoretical']],
            ['id' => 69, 'level' => 'C1', 'question' => 'Little ______ that the small startup would eventually become one of the world\'s leading technology companies.',
                'options' => ['did anyone know', 'anyone knew', 'had anyone known', 'anyone had known']],
            ['id' => 70, 'level' => 'C1', 'question' => "Despite ______ extensive research, the scientists were unable to determine the exact cause of the phenomenon.",
                'options' => ['conducting', 'having conducted', 'conduct', 'conducted']],
            ['id' => 71, 'level' => 'C1', 'question' => 'What is the main purpose of the article?',
                'options' => ['To explain why technology should be avoided in the workplace.', 'To discuss both the benefits and drawbacks of constant digital connectivity.', 'To encourage companies to eliminate remote work.', 'To describe recent technological innovations.'], 'passage' => 'c1_reading'],
            ['id' => 72, 'level' => 'C1', 'question' => 'According to the author, why do many employees continue answering emails after work?',
                'options' => ['Their employers require them to work overtime.', 'They worry that ignoring messages may affect how committed they appear.', 'They enjoy working outside office hours.', 'They receive financial rewards for answering quickly.'], 'passage' => 'c1_reading'],
            ['id' => 73, 'level' => 'C1', 'question' => 'What does the author imply about digital notifications?',
                'options' => ['They rarely influence productivity.', 'They improve people\'s concentration.', "Their combined effect can reduce people's ability to focus deeply.", 'They should be permanently disabled.'], 'passage' => 'c1_reading'],
            ['id' => 74, 'level' => 'C1', 'question' => "Which statement best reflects the author's opinion?",
                'options' => ['Technology is the primary cause of stress in modern society.', 'Technology should be replaced with traditional communication methods.', 'The way people use technology is more important than the technology itself.', 'Social media is responsible for lower workplace productivity.'], 'passage' => 'c1_reading'],
            ['id' => 75, 'level' => 'C1', 'question' => 'Which title would best summarize the article?',
                'options' => ['Why Smartphones Are Destroying Modern Society', 'The Future of Artificial Intelligence', 'Finding Balance in an Always-Connected World', 'The History of Digital Communication'], 'passage' => 'c1_reading'],
        ];
    }
}
