<?php
/**
 * AttendTrack — One-click Database Installer
 * Visit this page ONCE to create all tables, then delete it.
 * URL: http://your-site/attendtrack/install.php
 */

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'attendtrack';

$messages = [];
$success  = true;

try {
    // Connect without selecting DB first
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $messages[] = ['ok', 'Connected to MySQL server'];

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $messages[] = ['ok', "Database `$db` ready"];

    $pdo->exec("USE `$db`");

    $sql = "
    CREATE TABLE IF NOT EXISTS `teachers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `full_name` VARCHAR(150) NOT NULL,
        `email` VARCHAR(150) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS `year_levels` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `teacher_id` INT NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE CASCADE
    );
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
    CREATE TABLE IF NOT EXISTS `students` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `section_id` INT NOT NULL,
        `full_name` VARCHAR(150) NOT NULL,
        `student_no` VARCHAR(50),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE CASCADE
    );
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
    CREATE TABLE IF NOT EXISTS `attendance_records` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `session_id` INT NOT NULL,
        `student_id` INT NOT NULL,
        `status` ENUM('present','absent','late','excused') NOT NULL DEFAULT 'present',
        UNIQUE KEY `unique_record` (`session_id`, `student_id`),
        FOREIGN KEY (`session_id`) REFERENCES `attendance_sessions`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
    );";

    foreach (array_filter(array_map('trim', explode(';', $sql))) as $q) {
        if ($q) { $pdo->exec($q); }
    }
    $messages[] = ['ok', 'All 6 tables created successfully'];

} catch (PDOException $e) {
    $success = false;
    $messages[] = ['err', 'Database error: ' . $e->getMessage()];
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>AttendTrack Installer</title>
<style>
  body { font-family: 'Segoe UI', sans-serif; background: #0f0f14; color: #e8e6f0; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
  .box { background: #16161e; border: 1px solid #2a2a3a; border-radius: 14px; padding: 2rem; max-width: 480px; width: 100%; }
  h1 { font-size: 1.4rem; margin-bottom: 1.5rem; color: #a594f9; }
  .msg { display: flex; align-items: flex-start; gap: 10px; padding: 10px 14px; border-radius: 8px; margin-bottom: 8px; font-size: 14px; }
  .ok  { background: rgba(29,158,117,0.12); border: 1px solid rgba(29,158,117,0.25); }
  .err { background: rgba(226,75,74,0.12);  border: 1px solid rgba(226,75,74,0.25); }
  .ok .icon  { color: #1d9e75; font-size: 16px; }
  .err .icon { color: #e24b4a; font-size: 16px; }
  .actions { margin-top: 1.5rem; display: flex; gap: 10px; }
  .btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; border-radius: 8px; border: none; font-size: 14px; font-weight: 500; cursor: pointer; text-decoration: none; }
  .btn-primary { background: #7c6af7; color: #fff; }
  .btn-ghost { background: transparent; color: #8884a0; border: 1px solid #2a2a3a; }
  .note { margin-top: 1.25rem; font-size: 13px; color: #8884a0; padding: 10px 14px; background: rgba(239,159,39,0.08); border: 1px solid rgba(239,159,39,0.2); border-radius: 8px; }
</style>
</head>
<body>
<div class="box">
  <h1>⚙ AttendTrack Installer</h1>
  <?php foreach ($messages as [$type, $text]): ?>
    <div class="msg <?= $type ?>">
      <span class="icon"><?= $type === 'ok' ? '✓' : '✗' ?></span>
      <span><?= htmlspecialchars($text) ?></span>
    </div>
  <?php endforeach; ?>

  <?php if ($success): ?>
    <p style="color:#1d9e75;font-weight:600;margin-top:1rem">Installation complete!</p>
    <div class="note">⚠ For security, delete <code>install.php</code> before going live.</div>
    <div class="actions">
      <a href="index.php" class="btn btn-primary">Go to App →</a>
    </div>
  <?php else: ?>
    <p style="color:#e24b4a;margin-top:1rem">Installation failed. Check your database credentials in <code>install.php</code> and <code>includes/db.php</code>.</p>
  <?php endif; ?>
</div>
</body>
</html>
