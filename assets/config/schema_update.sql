-- ============================================================
-- Schema Update: Teacher-Student Enrollment System
-- Run this on existing donma_db
-- ============================================================

USE donma_db;

-- Add enrollment columns to students table
ALTER TABLE students
  ADD COLUMN IF NOT EXISTS enrolled_by      INT UNSIGNED DEFAULT NULL COMMENT 'user.id who enrolled this student',
  ADD COLUMN IF NOT EXISTS teacher_id       INT UNSIGNED DEFAULT NULL COMMENT 'assigned teacher user.id',
  ADD COLUMN IF NOT EXISTS enrollment_status ENUM('approved','pending') NOT NULL DEFAULT 'approved' COMMENT 'pending = teacher-enrolled, awaiting admin approval',
  ADD INDEX IF NOT EXISTS idx_teacher (teacher_id),
  ADD INDEX IF NOT EXISTS idx_enrollment (enrollment_status);

-- For fresh installs — full students table with new columns
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
  enrolled_by       INT UNSIGNED DEFAULT NULL,
  teacher_id        INT UNSIGNED DEFAULT NULL,
  enrollment_status ENUM('approved','pending') NOT NULL DEFAULT 'approved',
  created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_lrn (lrn),
  INDEX idx_section (grade_section),
  INDEX idx_teacher (teacher_id),
  INDEX idx_enrollment (enrollment_status)
) ENGINE=InnoDB;
