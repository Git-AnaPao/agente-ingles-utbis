<?php

namespace Database\Seeders;

use App\Models\ListeningLesson;
use App\Models\PlacementTest;
use App\Models\Role;
use App\Models\StudentProgress;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoStudentSeeder extends Seeder
{
	public function run(): void
	{
		$students = [
			[
				'email' => 'jcmiguelv@utbispuebla.edu.mx',
				'name' => 'Juan',
				'middle_name' => 'Carlos',
				'last_name' => 'Miguel',
				'password' => '12345678',
				'phone' => '0000000001',
				'completed_lessons' => 0,
			],
			[
				'email' => 'rodrigogdeita@utbispuebla.edu.mx',
				'name' => 'Luis',
				'middle_name' => 'Rodrigo',
				'last_name' => 'Garcia',
				'password' => '12345678',
				'phone' => '0000000002',
				'completed_lessons' => 9,
			],
			[
				'email' => 'stephany_espinoza@utbispuebla.edu.mx',
				'name' => 'Stephany',
				'middle_name' => '',
				'last_name' => 'Espinoza',
				'password' => '12345678',
				'phone' => '0000000003',
				'completed_lessons' => 18,
			],
			[
				'email' => 'arojas@utbispuebla.edu.mx',
				'name' => 'Alberto',
				'middle_name' => '',
				'last_name' => 'Rojas',
				'password' => '12345678',
				'phone' => '0000000004',
				'completed_lessons' => 26,
			],
			[
				'email' => 'natalipacheco@utbispuebla.edu.mx',
				'name' => 'Natali',
				'middle_name' => '',
				'last_name' => 'Pacheco',
				'password' => '12345678',
				'phone' => '0000000005',
				'completed_lessons' => 34,
			],
			[
				'email' => 'manuel0906@utbispuebla.edu.mx',
				'name' => 'Manuel',
				'middle_name' => 'Escobedo',
				'last_name' => 'Naal',
				'password' => '12345678',
				'phone' => '0000000006',
				'completed_lessons' => 42,
			],
		];

		$lessons = ListeningLesson::query()->ordered()->get();
		$maximumRequired = max(array_column($students, 'completed_lessons'));

		if ($lessons->count() < $maximumRequired) {
			throw new RuntimeException(
				"Se requieren {$maximumRequired} listening lessons importadas para ejecutar este seeder; "
				.'ejecuta primero php artisan import:listening.',
			);
		}

		$studentRole = Role::query()->firstOrCreate(
			['role_name' => 'student'],
			['role_description' => 'Estudiante'],
		);

		DB::transaction(function () use ($students, $lessons, $studentRole): void {
			foreach ($students as $studentData) {
				$student = User::query()->updateOrCreate(
					['user_email' => $studentData['email']],
					[
						'user_cel' => $studentData['phone'],
						'user_password' => Hash::make($studentData['password']),
						'user_name' => $studentData['name'],
						'user_middle_name' => $studentData['middle_name'],
						'user_last_name' => $studentData['last_name'],
						'user_status' => 'active',
						'email_verified_at' => now(),
					],
				);

				$student->roles()->syncWithoutDetaching([$studentRole->role_id]);
				PlacementTest::query()->where('student_id', $student->user_id)->delete();
				$placementTest = PlacementTest::query()->create([
					'student_id' => $student->user_id,
					'result_level' => 'A1',
					'score' => 100,
					'correct_answers' => 11,
					'total_questions' => 11,
					'level_breakdown' => json_encode([
						'A1' => ['correct' => 11, 'total' => 11],
						'A2' => ['correct' => 0, 'total' => 0],
						'B1' => ['correct' => 0, 'total' => 0],
						'B2' => ['correct' => 0, 'total' => 0],
						'C1' => ['correct' => 0, 'total' => 0],
					], JSON_THROW_ON_ERROR),
				]);
				StudentProgress::query()->where('student_id', $student->user_id)->delete();

				foreach ($lessons->take($studentData['completed_lessons']) as $lesson) {
					$skills = StudentProgress::requiredSkillsForListeningLesson($lesson);

					if ($skills === []) {
						throw new RuntimeException(
							"La lección {$lesson->listening_lesson_id} no tiene habilidades evaluables.",
						);
					}

					foreach ($skills as $skill) {
						StudentProgress::query()->create([
							'student_id' => $student->user_id,
							'placement_test_id' => $placementTest->placement_test_id,
							'lesson_id' => null,
							'listening_lesson_id' => $lesson->listening_lesson_id,
							'student_cefr_level' => $lesson->cefr_level,
							'student_sub_level' => $lesson->sub_level,
							'student_skill_type' => $skill,
						]);
					}
				}
			}
		});
	}
}
