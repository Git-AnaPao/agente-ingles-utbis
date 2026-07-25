-- ============================================================
-- Base de datos: ingles_db
-- Generada desde migraciones Laravel - 14 de julio de 2026
-- ============================================================

DROP DATABASE IF EXISTS `ingles_db`;
CREATE DATABASE `ingles_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ingles_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- TABLAS DE APLICACION
-- ============================================================

CREATE TABLE `users` (
    `user_id` CHAR(36) NOT NULL,
    `user_email` VARCHAR(255) NOT NULL,
    `user_cel` VARCHAR(12) NOT NULL,
    `user_password` VARCHAR(255) NOT NULL,
    `user_name` VARCHAR(255) NOT NULL,
    `user_last_name` VARCHAR(255) NOT NULL,
    `user_middle_name` VARCHAR(255) NOT NULL,
    `user_status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `remember_token` VARCHAR(100) NULL,
    `email_verified_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `users_user_email_unique` (`user_email`),
    UNIQUE KEY `users_user_cel_unique` (`user_cel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
    `email` VARCHAR(255) NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL,
    PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
    `id` VARCHAR(255) NOT NULL,
    `user_id` CHAR(36) NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `payload` LONGTEXT NOT NULL,
    `last_activity` INT NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `sessions_user_id_index` (`user_id`),
    INDEX `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
    `key` VARCHAR(255) NOT NULL,
    `value` MEDIUMTEXT NOT NULL,
    `expiration` INT NOT NULL,
    PRIMARY KEY (`key`),
    INDEX `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
    `key` VARCHAR(255) NOT NULL,
    `owner` VARCHAR(255) NOT NULL,
    `expiration` INT NOT NULL,
    PRIMARY KEY (`key`),
    INDEX `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `queue` VARCHAR(255) NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `attempts` TINYINT UNSIGNED NOT NULL,
    `reserved_at` INT UNSIGNED NULL,
    `available_at` INT UNSIGNED NOT NULL,
    `created_at` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
    `id` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `total_jobs` INT NOT NULL,
    `pending_jobs` INT NOT NULL,
    `failed_jobs` INT NOT NULL,
    `failed_job_ids` LONGTEXT NOT NULL,
    `options` MEDIUMTEXT NULL,
    `cancelled_at` INT NULL,
    `created_at` INT NOT NULL,
    `finished_at` INT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` VARCHAR(255) NOT NULL,
    `connection` TEXT NOT NULL,
    `queue` TEXT NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `exception` LONGTEXT NOT NULL,
    `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
    `role_id` CHAR(36) NOT NULL,
    `role_name` VARCHAR(255) NOT NULL,
    `role_description` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`role_id`),
    UNIQUE KEY `roles_role_name_unique` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_roles` (
    `user_id` CHAR(36) NOT NULL,
    `role_id` CHAR(36) NOT NULL,
    PRIMARY KEY (`user_id`, `role_id`),
    CONSTRAINT `user_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
    CONSTRAINT `user_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lessons` (
    `lesson_id` CHAR(36) NOT NULL,
    `lesson_cefr_level` ENUM('A1','A2','B1','B2','C1','C2') NOT NULL,
    `lesson_sub_level` INT NOT NULL,
    `lesson_prompt_payload` JSON NOT NULL,
    PRIMARY KEY (`lesson_id`),
    UNIQUE KEY `unique_lesson` (`lesson_cefr_level`, `lesson_sub_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `placement_tests` (
    `placement_test_id` CHAR(36) NOT NULL,
    `student_id` CHAR(36) NOT NULL,
    `result_level` ENUM('A1','A2','B1','B2','C1','C2') NOT NULL,
    `score` DECIMAL(5,2) NOT NULL,
    `correct_answers` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `total_questions` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `level_breakdown` TEXT NULL,
    `taken_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`placement_test_id`),
    CONSTRAINT `placement_tests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `student_progress` (
    `student_progress_id` CHAR(36) NOT NULL,
    `student_id` CHAR(36) NOT NULL,
    `placement_test_id` CHAR(36) NULL,
    `lesson_id` CHAR(36) NOT NULL,
    `student_cefr_level` ENUM('A1','A2','B1','B2','C1','C2') NOT NULL,
    `student_sub_level` INT NOT NULL DEFAULT 1,
    `student_skill_type` ENUM('listening','speaking','writing','reading') NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`student_progress_id`),
    UNIQUE KEY `unique_progress` (`student_id`, `student_cefr_level`, `student_skill_type`),
    CONSTRAINT `student_progress_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
    CONSTRAINT `student_progress_placement_test_id_foreign` FOREIGN KEY (`placement_test_id`) REFERENCES `placement_tests` (`placement_test_id`) ON DELETE SET NULL,
    CONSTRAINT `student_progress_lesson_id_foreign` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`lesson_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `questionnaires` (
    `questionnaire_id` CHAR(36) NOT NULL,
    `lesson_id` CHAR(36) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`questionnaire_id`),
    CONSTRAINT `questionnaires_lesson_id_foreign` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`lesson_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `resources` (
    `resource_id` CHAR(36) NOT NULL,
    `questionnaire_id` CHAR(36) NOT NULL,
    `resource_type` ENUM('audio','text','image') NOT NULL,
    `resource_url` VARCHAR(500) NOT NULL,
    `resource_title` VARCHAR(255) NULL,
    `resource_transcript` TEXT NULL COMMENT 'Transcripcion del audio para speaking',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`resource_id`),
    CONSTRAINT `resources_questionnaire_id_foreign` FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires` (`questionnaire_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `questions` (
    `question_id` CHAR(36) NOT NULL,
    `questionnaire_id` CHAR(36) NOT NULL,
    `question_type` ENUM('multiple_choice','fill_blank','speaking','listening') NOT NULL,
    `question_skill_type` ENUM('reading','listening','speaking','writing') NOT NULL,
    `question_text` TEXT NOT NULL,
    `correct_answer` TEXT NULL COMMENT 'null para preguntas de speaking evaluadas por IA',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`question_id`),
    CONSTRAINT `questions_questionnaire_id_foreign` FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires` (`questionnaire_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `question_options` (
    `option_id` CHAR(36) NOT NULL,
    `question_id` CHAR(36) NOT NULL,
    `option_text` VARCHAR(500) NOT NULL,
    `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`option_id`),
    CONSTRAINT `question_options_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `attempt_logs` (
    `attempt_id` CHAR(36) NOT NULL,
    `user_id` CHAR(36) NOT NULL,
    `lesson_id` CHAR(36) NOT NULL,
    `attempt_score` DECIMAL(5,2) NOT NULL,
    `ai_feedback` TEXT NULL,
    `passed` TINYINT(1) NOT NULL,
    `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`attempt_id`),
    CONSTRAINT `attempt_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
    CONSTRAINT `attempt_logs_lesson_id_foreign` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`lesson_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `student_responses` (
    `response_id` CHAR(36) NOT NULL,
    `attempt_id` CHAR(36) NOT NULL,
    `question_id` CHAR(36) NOT NULL,
    `student_answer_text` TEXT NOT NULL COMMENT 'Transcripcion de voz o respuesta escrita del alumno',
    `is_correct` TINYINT(1) NULL COMMENT 'null mientras la IA no haya calificado',
    `ai_question_feedback` TEXT NULL COMMENT 'Retroalimentacion individual por pregunta de la IA',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`response_id`),
    CONSTRAINT `student_responses_attempt_id_foreign` FOREIGN KEY (`attempt_id`) REFERENCES `attempt_logs` (`attempt_id`) ON DELETE CASCADE,
    CONSTRAINT `student_responses_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLAS DE INFRAESTRUCTURA (Laravel)
-- ============================================================

CREATE TABLE `migrations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `migration` VARCHAR(255) NOT NULL,
    `batch` INT NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATOS SEED
-- ============================================================

-- Roles
INSERT INTO `roles` (`role_id`, `role_name`, `role_description`, `created_at`, `updated_at`) VALUES
('a1b2c3d4-e5f6-7890-abcd-ef1234567801', 'admin', 'Administrador del sistema', NOW(), NOW()),
('a1b2c3d4-e5f6-7890-abcd-ef1234567802', 'professor', 'Profesor / instructor', NOW(), NOW()),
('a1b2c3d4-e5f6-7890-abcd-ef1234567803', 'student', 'Estudiante', NOW(), NOW());

-- Lessons
INSERT INTO `lessons` (`lesson_id`, `lesson_cefr_level`, `lesson_sub_level`, `lesson_prompt_payload`) VALUES
('b1000001-0000-0000-0000-000000000001', 'A1', 1, '{"topic":"Greetings","prompt":"Hello, how are you? Practice introducing yourself."}'),
('b1000001-0000-0000-0000-000000000002', 'A1', 2, '{"topic":"The Alphabet & Verb To Be","prompt":"Spell common words. I am, you are, he/she/it is."}'),
('b1000001-0000-0000-0000-000000000003', 'A1', 3, '{"topic":"Numbers & Introductions","prompt":"Listen and repeat numbers 1-20. Say your name and where you are from."}'),
('b1000001-0000-0000-0000-000000000004', 'A2', 1, '{"topic":"Present Simple & Daily Routine","prompt":"I eat, you run, she works. Describe your daily routine."}'),
('b1000001-0000-0000-0000-000000000005', 'A2', 2, '{"topic":"Family Members","prompt":"Mother, father, brother. Describe your family."}'),
('b1000001-0000-0000-0000-000000000006', 'B1', 1, '{"topic":"Future Tense & Travel","prompt":"I will go, I am going to. Describe your future travel plans."}'),
('b1000001-0000-0000-0000-000000000007', 'B1', 2, '{"topic":"Comparatives & Hotel","prompt":"Bigger, more beautiful. I have a reservation."}'),
('b1000001-0000-0000-0000-000000000008', 'B2', 1, '{"topic":"Passive Voice & News","prompt":"The book was written by. Discuss a news article."}'),
('b1000001-0000-0000-0000-000000000009', 'B2', 2, '{"topic":"Phrasal Verbs & Debate","prompt":"Give up, look after. Express and defend your ideas."}'),
('b1000001-0000-0000-0000-000000000010', 'C1', 1, '{"topic":"Idioms & Conversation","prompt":"Break the ice, piece of cake. Speak fluently on any topic."}'),
('b1000001-0000-0000-0000-000000000011', 'C1', 2, '{"topic":"Nuanced Grammar & Exam Prep","prompt":"Inversions, cleft sentences. TOEFL/IELTS strategies."}'),
('b1000001-0000-0000-0000-000000000012', 'C2', 1, '{"topic":"Advanced Listening & Speaking","prompt":"Academic lectures. Present complex ideas fluently."}');

-- Usuarios (contrasenas hasheadas con bcrypt)
-- admin123 -> $2y$12$... (se genero con Hash::make)
-- profesor123
-- password
INSERT INTO `users` (`user_id`, `user_email`, `user_cel`, `user_password`, `user_name`, `user_last_name`, `user_middle_name`, `user_status`, `created_at`, `updated_at`) VALUES
('c1000001-0000-0000-0000-000000000001', 'admin@utbis.edu', '70000000', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Sistema', '', 'active', NOW(), NOW()),
('c1000001-0000-0000-0000-000000000002', 'profesor@utbis.edu', '70000001', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Profesor', 'UTBIS', '', 'active', NOW(), NOW()),
('c1000001-0000-0000-0000-000000000003', 'test@example.com', '70000002', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test', 'User', '', 'active', NOW(), NOW());

-- User Roles
INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES
('c1000001-0000-0000-0000-000000000001', 'a1b2c3d4-e5f6-7890-abcd-ef1234567801'),
('c1000001-0000-0000-0000-000000000002', 'a1b2c3d4-e5f6-7890-abcd-ef1234567802'),
('c1000001-0000-0000-0000-000000000003', 'a1b2c3d4-e5f6-7890-abcd-ef1234567803');

SET FOREIGN_KEY_CHECKS = 1;
