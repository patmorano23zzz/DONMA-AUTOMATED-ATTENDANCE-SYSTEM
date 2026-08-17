-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql202.infinityfree.com
-- Generation Time: Aug 17, 2026 at 07:22 AM
-- Server version: 11.4.12-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_42613522_donma_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `time_in` datetime NOT NULL,
  `time_out` datetime DEFAULT NULL,
  `status` enum('present','late','absent') NOT NULL DEFAULT 'present',
  `face_image` varchar(255) DEFAULT NULL,
  `face_image_out` varchar(255) DEFAULT NULL,
  `recorded_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `time_in`, `time_out`, `status`, `face_image`, `face_image_out`, `recorded_by`, `created_at`) VALUES
(1, 1, '2026-08-02 17:46:18', NULL, 'late', 'assets/faces/1/2026-08-02_1785685578.jpg', NULL, NULL, '2026-08-02 23:46:18'),
(2, 2, '2026-08-03 15:50:45', NULL, 'late', 'assets/faces/2/2026-08-03_1785765045.jpg', NULL, NULL, '2026-08-03 21:50:45');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_name` varchar(200) NOT NULL DEFAULT 'Don Marcelo C. Marty Elementary School',
  `school_year` varchar(20) DEFAULT NULL,
  `cut_off_time` time NOT NULL DEFAULT '08:00:00',
  `biometric_api` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `school_name`, `school_year`, `cut_off_time`, `biometric_api`) VALUES
(1, 'Don Marcelo C. Marty Elementary School', '2025-2026', '08:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(10) UNSIGNED NOT NULL,
  `lrn` char(12) NOT NULL,
  `first_name` varchar(80) NOT NULL,
  `middle_name` varchar(80) DEFAULT NULL,
  `last_name` varchar(80) NOT NULL,
  `grade_section` varchar(60) NOT NULL,
  `birthdate` date DEFAULT NULL,
  `gender` enum('M','F','Other') DEFAULT NULL,
  `guardian_name` varchar(120) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `terms_accepted` tinyint(1) NOT NULL DEFAULT 0,
  `registered_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `enrolled_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'user.id who enrolled this student',
  `teacher_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'assigned teacher user.id',
  `enrollment_status` enum('approved','pending') NOT NULL DEFAULT 'approved' COMMENT 'pending = teacher-enrolled, awaiting admin approval'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `lrn`, `first_name`, `middle_name`, `last_name`, `grade_section`, `birthdate`, `gender`, `guardian_name`, `guardian_phone`, `password_hash`, `terms_accepted`, `registered_at`, `created_at`, `enrolled_by`, `teacher_id`, `enrollment_status`) VALUES
(1, '1014580001', 'sample', 'student', 'one', 'Grade 1', '2015-02-24', 'M', 'guardian', '099123456789', NULL, 0, NULL, '2026-08-02 23:11:35', 1, 2, 'approved'),
(2, '1014580002', 'sample', 'student', 'two', 'Grade 1', '2015-02-24', 'F', 'guardian', '099123456789', '$2y$10$2qjQG1JomNJkIlbUPZ27.eS436SScGeeivuYMLkgZC.wKvQEfduNK', 1, '2026-08-03 21:49:59', '2026-08-03 21:21:08', 1, 2, 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(120) NOT NULL,
  `teacher_id` varchar(20) DEFAULT NULL,
  `role` enum('admin','teacher') NOT NULL DEFAULT 'teacher',
  `password_hash` varchar(255) NOT NULL,
  `section` varchar(60) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `teacher_id`, `role`, `password_hash`, `section`, `is_active`, `created_at`) VALUES
(1, 'System Administrator', 'admin@donma.edu', NULL, 'admin', '$2y$10$HDRtMcY0M4/T1RsHYc.Gq.K1aO9f8xNDa1KpIYAcVfd.tYlf3KWgS', NULL, 1, '2026-08-02 19:20:05'),
(2, 'Teacher One', 'teacher1@gmail.com', 'Teacher_1', 'teacher', '$2y$10$EZU3uG3FbmrWf8tJMRCBje6kpUB.99SAJfHT1OvbAtX1ORQQSArGC', 'Grade 1', 1, '2026-08-02 20:25:07');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_date` (`student_id`,`time_in`),
  ADD KEY `idx_date` (`time_in`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lrn` (`lrn`),
  ADD KEY `idx_lrn` (`lrn`),
  ADD KEY `idx_section` (`grade_section`),
  ADD KEY `idx_teacher` (`teacher_id`),
  ADD KEY `idx_enrollment` (`enrollment_status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `teacher_id` (`teacher_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_teacher_id` (`teacher_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
