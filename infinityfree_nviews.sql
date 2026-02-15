-- ============================================================
-- InfinityFree — PRODUCTION SQL (No Views, No Create DB)
-- Database : if0_41165725_productivityhub
-- Host     : sql107.infinityfree.com
-- User     : if0_41165725
-- !! Select your DB in phpMyAdmin BEFORE importing !!
-- ============================================================

SET SQL_MODE           = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;
SET time_zone          = "+00:00";

-- ============================================================
-- SECTION 1 — DROP TABLES (safe clean slate)
-- ============================================================

DROP TABLE IF EXISTS `user_quiz_attempts`;
DROP TABLE IF EXISTS `quiz_answers`;
DROP TABLE IF EXISTS `quiz_questions`;
DROP TABLE IF EXISTS `quizzes`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `user_progress`;
DROP TABLE IF EXISTS `lesson_resources`;
DROP TABLE IF EXISTS `lessons`;
DROP TABLE IF EXISTS `subjects`;
DROP TABLE IF EXISTS `levels`;
DROP TABLE IF EXISTS `users`;

-- ============================================================
-- SECTION 2 — CREATE TABLES
-- ============================================================

CREATE TABLE `users` (
  `id`                 INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `email`              VARCHAR(255)  NOT NULL,
  `password`           VARCHAR(255)  NOT NULL,
  `first_name`         VARCHAR(100)  NOT NULL,
  `last_name`          VARCHAR(100)  NOT NULL,
  `role`               ENUM('student','teacher','admin') NOT NULL DEFAULT 'student',
  `preferred_lang`     ENUM('fr','en','ar')              NOT NULL DEFAULT 'fr',
  `avatar`             VARCHAR(255)  DEFAULT NULL,
  `xp_points`          INT UNSIGNED  NOT NULL DEFAULT 0,
  `session_token`      VARCHAR(64)   DEFAULT NULL,
  `session_expires`    DATETIME      DEFAULT NULL,
  `reset_token`        VARCHAR(64)   DEFAULT NULL,
  `reset_expires`      DATETIME      DEFAULT NULL,
  `email_verified`     TINYINT(1)    NOT NULL DEFAULT 0,
  `verification_token` VARCHAR(64)   DEFAULT NULL,
  `is_active`          TINYINT(1)    NOT NULL DEFAULT 1,
  `last_login`         DATETIME      DEFAULT NULL,
  `last_activity`      DATETIME      DEFAULT NULL,
  `created_at`         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email`     (`email`),
  KEY `idx_session_token`       (`session_token`),
  KEY `idx_role`                (`role`),
  KEY `idx_active`              (`is_active`),
  KEY `idx_session_expires`     (`session_expires`),
  KEY `idx_xp_points`           (`xp_points`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `levels` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name_fr`        VARCHAR(100)  NOT NULL,
  `name_en`        VARCHAR(100)  NOT NULL,
  `name_ar`        VARCHAR(100)  NOT NULL,
  `description_fr` TEXT          DEFAULT NULL,
  `description_en` TEXT          DEFAULT NULL,
  `description_ar` TEXT          DEFAULT NULL,
  `order_position` INT UNSIGNED  NOT NULL DEFAULT 0,
  `is_active`      TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_active` (`order_position`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `subjects` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `level_id`       INT UNSIGNED  NOT NULL,
  `name_fr`        VARCHAR(100)  NOT NULL,
  `name_en`        VARCHAR(100)  NOT NULL,
  `name_ar`        VARCHAR(100)  NOT NULL,
  `description_fr` TEXT          DEFAULT NULL,
  `description_en` TEXT          DEFAULT NULL,
  `description_ar` TEXT          DEFAULT NULL,
  `icon`           VARCHAR(100)  DEFAULT NULL,
  `color`          VARCHAR(7)    DEFAULT '#3B82F6',
  `order_position` INT UNSIGNED  NOT NULL DEFAULT 0,
  `is_active`      TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_level_id`    (`level_id`),
  KEY `idx_level_order` (`level_id`, `order_position`, `is_active`),
  CONSTRAINT `fk_subjects_level`
    FOREIGN KEY (`level_id`) REFERENCES `levels` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lessons` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `subject_id`       INT UNSIGNED  NOT NULL,
  `title_fr`         VARCHAR(255)  NOT NULL,
  `title_en`         VARCHAR(255)  NOT NULL,
  `title_ar`         VARCHAR(255)  NOT NULL,
  `description_fr`   TEXT          DEFAULT NULL,
  `description_en`   TEXT          DEFAULT NULL,
  `description_ar`   TEXT          DEFAULT NULL,
  `content_fr`       LONGTEXT      DEFAULT NULL,
  `content_en`       LONGTEXT      DEFAULT NULL,
  `content_ar`       LONGTEXT      DEFAULT NULL,
  `duration_minutes` INT UNSIGNED  DEFAULT 0,
  `difficulty`       ENUM('easy','medium','hard') NOT NULL DEFAULT 'medium',
  `thumbnail`        VARCHAR(255)  DEFAULT NULL,
  `video_url`        VARCHAR(500)  DEFAULT NULL,
  `order_position`   INT UNSIGNED  NOT NULL DEFAULT 0,
  `is_free`          TINYINT(1)    NOT NULL DEFAULT 0,
  `is_active`        TINYINT(1)    NOT NULL DEFAULT 1,
  `views_count`      INT UNSIGNED  NOT NULL DEFAULT 0,
  `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_subject_id`    (`subject_id`),
  KEY `idx_subject_order` (`subject_id`, `order_position`, `is_active`),
  KEY `idx_difficulty`    (`difficulty`),
  KEY `idx_is_free`       (`is_free`),
  KEY `idx_views_count`   (`views_count`),
  FULLTEXT KEY `ft_title_fr` (`title_fr`),
  FULLTEXT KEY `ft_title_en` (`title_en`),
  FULLTEXT KEY `ft_title_ar` (`title_ar`),
  CONSTRAINT `fk_lessons_subject`
    FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lesson_resources` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `lesson_id`      INT UNSIGNED  NOT NULL,
  `name_fr`        VARCHAR(255)  NOT NULL,
  `name_en`        VARCHAR(255)  NOT NULL,
  `name_ar`        VARCHAR(255)  NOT NULL,
  `resource_type`  ENUM('pdf','video','link','image','document') NOT NULL,
  `file_path`      VARCHAR(500)  DEFAULT NULL,
  `external_url`   VARCHAR(500)  DEFAULT NULL,
  `file_size`      INT UNSIGNED  DEFAULT NULL,
  `order_position` INT UNSIGNED  NOT NULL DEFAULT 0,
  `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lesson_id`     (`lesson_id`),
  KEY `idx_resource_type` (`resource_type`),
  CONSTRAINT `fk_resources_lesson`
    FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_progress` (
  `id`                  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `user_id`             INT UNSIGNED     NOT NULL,
  `lesson_id`           INT UNSIGNED     NOT NULL,
  `status`              ENUM('not_started','in_progress','completed') NOT NULL DEFAULT 'not_started',
  `progress_percentage` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `time_spent_seconds`  INT UNSIGNED     NOT NULL DEFAULT 0,
  `score`               DECIMAL(5,2)     DEFAULT NULL,
  `completed_at`        DATETIME         DEFAULT NULL,
  `last_accessed`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_lesson` (`user_id`, `lesson_id`),
  KEY `idx_user_status`   (`user_id`, `status`),
  KEY `idx_lesson_id`     (`lesson_id`),
  KEY `idx_completed_at`  (`completed_at`),
  KEY `idx_last_accessed` (`last_accessed`),
  CONSTRAINT `fk_progress_user`
    FOREIGN KEY (`user_id`)   REFERENCES `users`   (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_progress_lesson`
    FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `quizzes` (
  `id`                 INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `lesson_id`          INT UNSIGNED     NOT NULL,
  `title_fr`           VARCHAR(255)     NOT NULL,
  `title_en`           VARCHAR(255)     NOT NULL,
  `title_ar`           VARCHAR(255)     NOT NULL,
  `description_fr`     TEXT             DEFAULT NULL,
  `description_en`     TEXT             DEFAULT NULL,
  `description_ar`     TEXT             DEFAULT NULL,
  `passing_score`      TINYINT UNSIGNED NOT NULL DEFAULT 70,
  `time_limit_minutes` INT UNSIGNED     DEFAULT NULL,
  `max_attempts`       TINYINT UNSIGNED DEFAULT NULL,
  `is_active`          TINYINT(1)       NOT NULL DEFAULT 1,
  `created_at`         DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lesson_id` (`lesson_id`),
  CONSTRAINT `fk_quizzes_lesson`
    FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `quiz_questions` (
  `id`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `quiz_id`        INT UNSIGNED     NOT NULL,
  `question_fr`    TEXT             NOT NULL,
  `question_en`    TEXT             NOT NULL,
  `question_ar`    TEXT             NOT NULL,
  `question_type`  ENUM('multiple_choice','true_false','short_answer') NOT NULL DEFAULT 'multiple_choice',
  `points`         TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `order_position` INT UNSIGNED     NOT NULL DEFAULT 0,
  `created_at`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_quiz_id`    (`quiz_id`),
  KEY `idx_quiz_order` (`quiz_id`, `order_position`),
  CONSTRAINT `fk_questions_quiz`
    FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `quiz_answers` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `question_id`    INT UNSIGNED  NOT NULL,
  `answer_fr`      TEXT          NOT NULL,
  `answer_en`      TEXT          NOT NULL,
  `answer_ar`      TEXT          NOT NULL,
  `is_correct`     TINYINT(1)    NOT NULL DEFAULT 0,
  `order_position` INT UNSIGNED  NOT NULL DEFAULT 0,
  `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_question_id` (`question_id`),
  KEY `idx_is_correct`  (`is_correct`),
  CONSTRAINT `fk_answers_question`
    FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_quiz_attempts` (
  `id`                 INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`            INT UNSIGNED  NOT NULL,
  `quiz_id`            INT UNSIGNED  NOT NULL,
  `score`              DECIMAL(5,2)  NOT NULL,
  `total_points`       INT UNSIGNED  NOT NULL,
  `earned_points`      INT UNSIGNED  NOT NULL,
  `time_spent_seconds` INT UNSIGNED  NOT NULL DEFAULT 0,
  `is_passed`          TINYINT(1)    NOT NULL DEFAULT 0,
  `completed_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_quiz`    (`user_id`, `quiz_id`),
  KEY `idx_quiz_id`      (`quiz_id`),
  KEY `idx_completed_at` (`completed_at`),
  CONSTRAINT `fk_attempts_user`
    FOREIGN KEY (`user_id`) REFERENCES `users`   (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_attempts_quiz`
    FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
  `id`                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`           INT UNSIGNED  NOT NULL,
  `title_fr`          VARCHAR(255)  NOT NULL,
  `title_en`          VARCHAR(255)  NOT NULL,
  `title_ar`          VARCHAR(255)  NOT NULL,
  `message_fr`        TEXT          NOT NULL,
  `message_en`        TEXT          NOT NULL,
  `message_ar`        TEXT          NOT NULL,
  `notification_type` ENUM('info','success','warning','error') NOT NULL DEFAULT 'info',
  `link`              VARCHAR(500)  DEFAULT NULL,
  `is_read`           TINYINT(1)    NOT NULL DEFAULT 0,
  `created_at`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_unread` (`user_id`, `is_read`),
  KEY `idx_created_at`  (`created_at`),
  CONSTRAINT `fk_notifications_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SECTION 3 — SEED DATA
-- ============================================================

-- Admin account (password: admin123 — change immediately after login)
INSERT INTO `users`
  (`email`, `password`, `first_name`, `last_name`,
   `role`, `preferred_lang`, `email_verified`, `is_active`, `xp_points`)
VALUES (
  'admin@infinityfree.com',
  '$argon2id$v=19$m=65536,t=4,p=3$WEhQS0ZTNDBFMjlZSFBGRg$kHfXqNZ8TqVGvLPZN6fJ8Q3pGxW5H8FqYvN2Z7K1m9E',
  'Admin', 'InfinityFree',
  'admin', 'fr', 1, 1, 0
);

-- Educational levels
INSERT INTO `levels` (`name_fr`, `name_en`, `name_ar`, `order_position`) VALUES
  ('Primaire',  'Elementary',    'الابتدائي', 1),
  ('Collège',   'Middle School', 'الإعدادي',  2),
  ('Lycée',     'High School',   'الثانوي',   3);

-- ============================================================
-- SECTION 4 — RE-ENABLE FOREIGN KEYS
-- ============================================================

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SECTION 5 — VERIFY (run after import to confirm success)
-- ============================================================

SELECT
  (SELECT COUNT(*) FROM users)         AS users,
  (SELECT COUNT(*) FROM levels)        AS levels,
  (SELECT COUNT(*) FROM subjects)      AS subjects,
  (SELECT COUNT(*) FROM lessons)       AS lessons,
  NOW()                                AS imported_at;

-- ============================================================
-- IMPORT COMPLETE — 11 tables created, 4 rows seeded
-- Login: admin@infinityfree.com / admin123
-- ============================================================
