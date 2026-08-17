-- ============================================================
-- DONMA Automated Attendance Tracking System
-- Don Marcelo C. Marty Elementary School
-- Database: donma_db
-- ============================================================

CREATE DATABASE IF NOT EXISTS donma_db
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE donma_db;

-- ── Users (Admin & Teachers) ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(120) NOT NULL,
  email          VARCHAR(120) NOT NULL UNIQUE,
  teacher_id     VARCHAR(20)  DEFAULT NULL UNIQUE,
  role           ENUM('admin','teacher') NOT NULL DEFAULT 'teacher',
  password_hash  VARCHAR(255) NOT NULL,
  section        VARCHAR(60)  DEFAULT NULL,
  is_active      TINYINT(1)   NOT NULL DEFAULT 0,
  created_at     DATETIME     DEFAULT NULL,
  INDEX idx_email (email),
  INDEX idx_teacher_id (teacher_id)
) ENGINE=InnoDB;

-- Default admin account
-- Login: admin@donma.edu / Admin@123
INSERT IGNORE INTO users (name, email, role, password_hash, is_active, created_at)
VALUES (
  'System Administrator',
  'admin@donma.edu',
  'admin',
  '$2y$10$HDRtMcY0M4/T1RsHYc.Gq.K1aO9f8xNDa1KpIYAcVfd.tYlf3KWgS',
  1,
  NOW()
);

-- ── Students ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS students (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lrn               CHAR(12)     NOT NULL UNIQUE,
  first_name        VARCHAR(80)  NOT NULL,
  middle_name       VARCHAR(80)  DEFAULT NULL,
  last_name         VARCHAR(80)  NOT NULL,
  grade_section     VARCHAR(60)  NOT NULL,
  birthdate         DATE         DEFAULT NULL,
  gender            ENUM('M','F','Other') DEFAULT NULL,
  guardian_name     VARCHAR(120) DEFAULT NULL,
  guardian_phone    VARCHAR(20)  DEFAULT NULL,
  password_hash     VARCHAR(255) DEFAULT NULL,
  terms_accepted    TINYINT(1)   NOT NULL DEFAULT 0,
  registered_at     DATETIME     DEFAULT NULL,
  enrolled_by       INT UNSIGNED DEFAULT NULL COMMENT 'user.id who enrolled this student',
  teacher_id        INT UNSIGNED DEFAULT NULL COMMENT 'assigned teacher user.id',
  enrollment_status ENUM('approved','pending') NOT NULL DEFAULT 'approved',
  created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_lrn (lrn),
  INDEX idx_section (grade_section),
  INDEX idx_teacher (teacher_id),
  INDEX idx_enrollment (enrollment_status)
) ENGINE=InnoDB;

-- ── Attendance ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS attendance (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id       INT UNSIGNED NOT NULL,
  time_in          DATETIME     NOT NULL,
  time_out         DATETIME     DEFAULT NULL,
  status           ENUM('present','late','absent') NOT NULL DEFAULT 'present',
  face_image       VARCHAR(255) DEFAULT NULL,
  face_image_out   VARCHAR(255) DEFAULT NULL,
  recorded_by      INT UNSIGNED DEFAULT NULL,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  INDEX idx_student_date (student_id, time_in),
  INDEX idx_date (time_in)
) ENGINE=InnoDB;

-- ── Settings ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS settings (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_name     VARCHAR(200) NOT NULL DEFAULT 'Don Marcelo C. Marty Elementary School',
  school_year     VARCHAR(20)  DEFAULT NULL,
  cut_off_time    TIME         NOT NULL DEFAULT '08:00:00',
  biometric_api   VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO settings (id, school_name, school_year, cut_off_time)
VALUES (1, 'Don Marcelo C. Marty Elementary School', '2025-2026', '08:00:00');
