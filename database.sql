-- ============================================
-- InfinityFree Database Schema
-- MySQL 8.0+ | InnoDB | utf8mb4_unicode_ci
-- Optimized for Performance & Security
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================
-- Database Creation
-- ============================================
CREATE DATABASE IF NOT EXISTS `infinityfree_db` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `infinityfree_db`;

-- ============================================
-- Table: users
-- Stores user accounts with encrypted session tokens
-- ============================================
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `role` ENUM('student', 'teacher', 'admin') NOT NULL DEFAULT 'student',
  `preferred_lang` ENUM('fr', 'en', 'ar') NOT NULL DEFAULT 'fr',
  `avatar` VARCHAR(255) DEFAULT NULL,
  `session_token` VARCHAR(64) DEFAULT NULL,
  `session_expires` DATETIME DEFAULT NULL,
  `reset_token` VARCHAR(64) DEFAULT NULL,
  `reset_expires` DATETIME DEFAULT NULL,
  `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `verification_token` VARCHAR(64) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login` DATETIME DEFAULT NULL,
  `last_activity` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `idx_session_token` (`session_token`),
  KEY `idx_email_password` (`email`, `password`),
  KEY `idx_role` (`role`),
  KEY `idx_active` (`is_active`),
  KEY `idx_session_expires` (`session_expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: levels
-- Educational levels (e.g., Elementary, Middle School)
-- ============================================
CREATE TABLE `levels` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name_fr` VARCHAR(100) NOT NULL,
  `name_en` VARCHAR(100) NOT NULL,
  `name_ar` VARCHAR(100) NOT NULL,
  `description_fr` TEXT DEFAULT NULL,
  `description_en` TEXT DEFAULT NULL,
  `description_ar` TEXT DEFAULT NULL,
  `order_position` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_active` (`order_position`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: subjects
-- School subjects linked to levels
-- ============================================
CREATE TABLE `subjects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `level_id` INT UNSIGNED NOT NULL,
  `name_fr` VARCHAR(100) NOT NULL,
  `name_en` VARCHAR(100) NOT NULL,
  `name_ar` VARCHAR(100) NOT NULL,
  `description_fr` TEXT DEFAULT NULL,
  `description_en` TEXT DEFAULT NULL,
  `description_ar` TEXT DEFAULT NULL,
  `icon` VARCHAR(100) DEFAULT NULL,
  `color` VARCHAR(7) DEFAULT '#3B82F6',
  `order_position` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_level_id` (`level_id`),
  KEY `idx_level_order` (`level_id`, `order_position`, `is_active`),
  CONSTRAINT `fk_subjects_level` 
    FOREIGN KEY (`level_id`) 
    REFERENCES `levels` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: lessons
-- Individual lessons with multilingual support
-- ============================================
CREATE TABLE `lessons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_id` INT UNSIGNED NOT NULL,
  `title_fr` VARCHAR(255) NOT NULL,
  `title_en` VARCHAR(255) NOT NULL,
  `title_ar` VARCHAR(255) NOT NULL,
  `description_fr` TEXT DEFAULT NULL,
  `description_en` TEXT DEFAULT NULL,
  `description_ar` TEXT DEFAULT NULL,
  `content_fr` LONGTEXT DEFAULT NULL,
  `content_en` LONGTEXT DEFAULT NULL,
  `content_ar` LONGTEXT DEFAULT NULL,
  `duration_minutes` INT UNSIGNED DEFAULT 0,
  `difficulty` ENUM('easy', 'medium', 'hard') NOT NULL DEFAULT 'medium',
  `thumbnail` VARCHAR(255) DEFAULT NULL,
  `video_url` VARCHAR(500) DEFAULT NULL,
  `order_position` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_free` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_subject_id` (`subject_id`),
  KEY `idx_subject_order` (`subject_id`, `order_position`, `is_active`),
  KEY `idx_difficulty` (`difficulty`),
  KEY `idx_is_free` (`is_free`),
  FULLTEXT KEY `ft_title_fr` (`title_fr`),
  FULLTEXT KEY `ft_title_en` (`title_en`),
  FULLTEXT KEY `ft_title_ar` (`title_ar`),
  CONSTRAINT `fk_lessons_subject` 
    FOREIGN KEY (`subject_id`) 
    REFERENCES `subjects` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: lesson_resources
-- Additional resources for lessons (PDFs, links, etc.)
-- ============================================
CREATE TABLE `lesson_resources` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lesson_id` INT UNSIGNED NOT NULL,
  `name_fr` VARCHAR(255) NOT NULL,
  `name_en` VARCHAR(255) NOT NULL,
  `name_ar` VARCHAR(255) NOT NULL,
  `resource_type` ENUM('pdf', 'video', 'link', 'image', 'document') NOT NULL,
  `file_path` VARCHAR(500) DEFAULT NULL,
  `external_url` VARCHAR(500) DEFAULT NULL,
  `file_size` INT UNSIGNED DEFAULT NULL,
  `order_position` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lesson_id` (`lesson_id`),
  KEY `idx_resource_type` (`resource_type`),
  CONSTRAINT `fk_resources_lesson` 
    FOREIGN KEY (`lesson_id`) 
    REFERENCES `lessons` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: user_progress
-- Track student progress through lessons
-- ============================================
CREATE TABLE `user_progress` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `lesson_id` INT UNSIGNED NOT NULL,
  `status` ENUM('not_started', 'in_progress', 'completed') NOT NULL DEFAULT 'not_started',
  `progress_percentage` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `time_spent_seconds` INT UNSIGNED NOT NULL DEFAULT 0,
  `score` DECIMAL(5,2) DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `last_accessed` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_lesson` (`user_id`, `lesson_id`),
  KEY `idx_user_status` (`user_id`, `status`),
  KEY `idx_lesson_id` (`lesson_id`),
  KEY `idx_completed_at` (`completed_at`),
  CONSTRAINT `fk_progress_user` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_progress_lesson` 
    FOREIGN KEY (`lesson_id`) 
    REFERENCES `lessons` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: quizzes
-- Quizzes associated with lessons
-- ============================================
CREATE TABLE `quizzes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lesson_id` INT UNSIGNED NOT NULL,
  `title_fr` VARCHAR(255) NOT NULL,
  `title_en` VARCHAR(255) NOT NULL,
  `title_ar` VARCHAR(255) NOT NULL,
  `description_fr` TEXT DEFAULT NULL,
  `description_en` TEXT DEFAULT NULL,
  `description_ar` TEXT DEFAULT NULL,
  `passing_score` TINYINT UNSIGNED NOT NULL DEFAULT 70,
  `time_limit_minutes` INT UNSIGNED DEFAULT NULL,
  `max_attempts` TINYINT UNSIGNED DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lesson_id` (`lesson_id`),
  CONSTRAINT `fk_quizzes_lesson` 
    FOREIGN KEY (`lesson_id`) 
    REFERENCES `lessons` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: quiz_questions
-- Questions for quizzes
-- ============================================
CREATE TABLE `quiz_questions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `quiz_id` INT UNSIGNED NOT NULL,
  `question_fr` TEXT NOT NULL,
  `question_en` TEXT NOT NULL,
  `question_ar` TEXT NOT NULL,
  `question_type` ENUM('multiple_choice', 'true_false', 'short_answer') NOT NULL DEFAULT 'multiple_choice',
  `points` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `order_position` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_quiz_id` (`quiz_id`),
  KEY `idx_quiz_order` (`quiz_id`, `order_position`),
  CONSTRAINT `fk_questions_quiz` 
    FOREIGN KEY (`quiz_id`) 
    REFERENCES `quizzes` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: quiz_answers
-- Answer options for quiz questions
-- ============================================
CREATE TABLE `quiz_answers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_id` INT UNSIGNED NOT NULL,
  `answer_fr` TEXT NOT NULL,
  `answer_en` TEXT NOT NULL,
  `answer_ar` TEXT NOT NULL,
  `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
  `order_position` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_question_id` (`question_id`),
  KEY `idx_is_correct` (`is_correct`),
  CONSTRAINT `fk_answers_question` 
    FOREIGN KEY (`question_id`) 
    REFERENCES `quiz_questions` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: user_quiz_attempts
-- Track user quiz attempts and scores
-- ============================================
CREATE TABLE `user_quiz_attempts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `quiz_id` INT UNSIGNED NOT NULL,
  `score` DECIMAL(5,2) NOT NULL,
  `total_points` INT UNSIGNED NOT NULL,
  `earned_points` INT UNSIGNED NOT NULL,
  `time_spent_seconds` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_passed` TINYINT(1) NOT NULL DEFAULT 0,
  `completed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_quiz` (`user_id`, `quiz_id`),
  KEY `idx_quiz_id` (`quiz_id`),
  KEY `idx_completed_at` (`completed_at`),
  CONSTRAINT `fk_attempts_user` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_attempts_quiz` 
    FOREIGN KEY (`quiz_id`) 
    REFERENCES `quizzes` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: notifications
-- User notifications
-- ============================================
CREATE TABLE `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `title_fr` VARCHAR(255) NOT NULL,
  `title_en` VARCHAR(255) NOT NULL,
  `title_ar` VARCHAR(255) NOT NULL,
  `message_fr` TEXT NOT NULL,
  `message_en` TEXT NOT NULL,
  `message_ar` TEXT NOT NULL,
  `notification_type` ENUM('info', 'success', 'warning', 'error') NOT NULL DEFAULT 'info',
  `link` VARCHAR(500) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_unread` (`user_id`, `is_read`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_notifications_user` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Sample Data Insertion
-- ============================================

-- Insert default admin user (password: admin123 - CHANGE IN PRODUCTION!)
INSERT INTO `users` (`email`, `password`, `first_name`, `last_name`, `role`, `email_verified`, `is_active`) 
VALUES (
  'admin@infinityfree.com',
  '$argon2id$v=19$m=65536,t=4,p=3$WEhQS0ZTNDBFMjlZSFBGRg$kHfXqNZ8TqVGvLPZN6fJ8Q3pGxW5H8FqYvN2Z7K1m9E',
  'Admin',
  'InfinityFree',
  'admin',
  1,
  1
);

-- Insert sample levels
INSERT INTO `levels` (`name_fr`, `name_en`, `name_ar`, `order_position`) VALUES
('Primaire', 'Elementary', 'الابتدائي', 1),
('Collège', 'Middle School', 'الإعدادي', 2),
('Lycée', 'High School', 'الثانوي', 3);

-- ============================================
-- Performance Optimization Views
-- ============================================

-- View: User Progress Summary
CREATE OR REPLACE VIEW `v_user_progress_summary` AS
SELECT 
  u.id AS user_id,
  u.first_name,
  u.last_name,
  COUNT(DISTINCT up.lesson_id) AS total_lessons_started,
  SUM(CASE WHEN up.status = 'completed' THEN 1 ELSE 0 END) AS lessons_completed,
  AVG(up.progress_percentage) AS avg_progress,
  SUM(up.time_spent_seconds) AS total_time_spent,
  MAX(up.last_accessed) AS last_activity
FROM users u
LEFT JOIN user_progress up ON u.id = up.user_id
WHERE u.role = 'student'
GROUP BY u.id;

-- View: Popular Lessons
CREATE OR REPLACE VIEW `v_popular_lessons` AS
SELECT 
  l.id,
  l.title_fr,
  l.title_en,
  l.title_ar,
  s.name_fr AS subject_name,
  l.views_count,
  COUNT(DISTINCT up.user_id) AS enrolled_users,
  AVG(up.progress_percentage) AS avg_completion,
  AVG(up.score) AS avg_score
FROM lessons l
INNER JOIN subjects s ON l.subject_id = s.id
LEFT JOIN user_progress up ON l.id = up.lesson_id
WHERE l.is_active = 1
GROUP BY l.id
ORDER BY l.views_count DESC;

-- ============================================
-- End of Schema
-- ============================================
