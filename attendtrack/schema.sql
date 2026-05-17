-- AttendTrack Database Schema
-- Run this SQL file once to set up the database

CREATE DATABASE IF NOT EXISTS `attendtrack` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `attendtrack`;

-- Teachers table
CREATE TABLE IF NOT EXISTS `teachers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Year levels table
CREATE TABLE IF NOT EXISTS `year_levels` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `teacher_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE CASCADE
);

-- Sections table
CREATE TABLE IF NOT EXISTS `sections` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `year_level_id` INT NOT NULL,
    `teacher_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `subject` VARCHAR(150) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`year_level_id`) REFERENCES `year_levels`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE CASCADE
);

-- Students table
CREATE TABLE IF NOT EXISTS `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `section_id` INT NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `student_no` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE CASCADE
);

-- Attendance sessions (one per date per section)
CREATE TABLE IF NOT EXISTS `attendance_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `section_id` INT NOT NULL,
    `teacher_id` INT NOT NULL,
    `session_date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_session` (`section_id`, `session_date`),
    FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE CASCADE
);

-- Attendance records (per student per session)
CREATE TABLE IF NOT EXISTS `attendance_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `session_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `status` ENUM('present','absent','late','excused') NOT NULL DEFAULT 'present',
    UNIQUE KEY `unique_record` (`session_id`, `student_id`),
    FOREIGN KEY (`session_id`) REFERENCES `attendance_sessions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
);
