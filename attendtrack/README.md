# AttendTrack — Classroom Attendance Monitoring System

A full-featured web application for teachers to monitor student attendance,
built with **PHP, MySQL, HTML, CSS, and JavaScript**.

---

## Features

- **Teacher Auth** — Register and log in to a personal account
- **Year Levels** — Create, edit, and delete year levels (e.g. Grade 7, Year 1)
- **Sections** — Each year level holds multiple sections with custom subject names
- **Student CRUD** — Add, edit, and remove students per section; includes optional student number
- **Daily Attendance** — Pick a date, mark each student Present / Absent / Late / Excused
- **Auto-save / Update** — Re-recording on the same date updates that session's records
- **Reports & Statistics**
  - Per-student attendance rate with progress bar
  - "Most Present" and "Most Absent" highlights
  - "For Dropping" flag for students below 70% attendance
  - "At Risk" flag for students 70–79%
  - Full attendance log per section (all sessions)

---

## Requirements

- PHP 7.4+ (PHP 8.x recommended)
- MySQL 5.7+ or MariaDB 10.3+
- A web server: Apache (XAMPP / WAMP / Laragon) or Nginx

---

## Installation

### 1. Copy files
Place the `attendtrack/` folder inside your web server's root:
- **XAMPP / WAMP**: `htdocs/attendtrack/`
- **Laragon**: `www/attendtrack/`

### 2. Configure database credentials
Open `includes/db.php` and update if needed:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // your MySQL username
define('DB_PASS', '');       // your MySQL password
define('DB_NAME', 'attendtrack');
```

### 3. Run the installer
Open your browser and go to:
```
http://localhost/attendtrack/install.php
```
This creates the database and all 6 tables automatically.

### 4. Delete the installer (important!)
After successful installation, delete `install.php` to prevent re-use.

### 5. Open the app
```
http://localhost/attendtrack/
```
Register a teacher account and start using the system.

---

## File Structure

```
attendtrack/
├── index.php              ← Main SPA shell (all views rendered here)
├── api.php                ← All AJAX endpoints (single file API)
├── install.php            ← One-time DB installer (delete after use)
├── schema.sql             ← Raw SQL schema (alternative to installer)
├── includes/
│   ├── db.php             ← PDO database connection
│   └── auth.php           ← Session helpers
└── assets/
    ├── css/
    │   └── style.css      ← Full stylesheet
    └── js/
        └── app.js         ← All front-end logic
```

---

## Database Schema

| Table                  | Purpose                                    |
|------------------------|--------------------------------------------|
| `teachers`             | Teacher accounts (email + hashed password) |
| `year_levels`          | Grade/year levels per teacher              |
| `sections`             | Sections with subject name per year level  |
| `students`             | Students per section                       |
| `attendance_sessions`  | One record per section per date            |
| `attendance_records`   | One status per student per session         |

---

## How to Use

1. **Register** a teacher account then log in
2. Go to **Year Levels** → Add a year level (e.g. "Grade 8")
3. Click a year level → Add sections with subject names (e.g. "Section A — Mathematics")
4. Click a section → Add students
5. Click **Take Attendance** → Select date → Mark statuses → Save
6. Click **Reports** to view per-student stats, attendance rates, and session logs

---

## Attendance Statuses

| Code | Meaning  |
|------|----------|
| P    | Present  |
| A    | Absent   |
| L    | Late     |
| E    | Excused  |

---

## Report Flags

| Flag           | Condition              |
|----------------|------------------------|
| ✓ Good Standing | Attendance rate ≥ 80% |
| ⚠ At Risk       | 70% – 79%             |
| ✗ For Dropping  | Below 70%             |
