<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

// ─── AUTH ────────────────────────────────────────────────────────────────────
if ($action === 'register') {
    $name  = sanitize($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';
    if (!$name || !$email || !$pass) jsonResponse(['error' => 'All fields required'], 400);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(['error' => 'Invalid email'], 400);
    if (strlen($pass) < 6) jsonResponse(['error' => 'Password must be at least 6 characters'], 400);
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM teachers WHERE email=?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) jsonResponse(['error' => 'Email already registered'], 409);
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $db->prepare("INSERT INTO teachers (full_name,email,password) VALUES (?,?,?)")->execute([$name,$email,$hash]);
    jsonResponse(['success' => true]);
}

if ($action === 'login') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';
    if (!$email || !$pass) jsonResponse(['error' => 'All fields required'], 400);
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM teachers WHERE email=?");
    $stmt->execute([$email]);
    $teacher = $stmt->fetch();
    if (!$teacher || !password_verify($pass, $teacher['password'])) {
        jsonResponse(['error' => 'Invalid email or password'], 401);
    }
    loginTeacher($teacher);
    jsonResponse(['success' => true, 'name' => $teacher['full_name']]);
}

if ($action === 'logout') {
    logoutTeacher();
    jsonResponse(['success' => true]);
}

if ($action === 'check_session') {
    if (isLoggedIn()) {
        $t = currentTeacher();
        jsonResponse(['loggedIn' => true, 'name' => $t['full_name']]);
    }
    jsonResponse(['loggedIn' => false]);
}

// All actions below require login
if (!isLoggedIn()) jsonResponse(['error' => 'Unauthorized'], 401);
$tid = currentTeacher()['id'];
$db  = getDB();

// ─── YEAR LEVELS ─────────────────────────────────────────────────────────────
if ($action === 'get_year_levels') {
    $rows = $db->prepare("SELECT yl.*, COUNT(s.id) AS section_count FROM year_levels yl LEFT JOIN sections s ON s.year_level_id=yl.id WHERE yl.teacher_id=? GROUP BY yl.id ORDER BY yl.name");
    $rows->execute([$tid]);
    jsonResponse($rows->fetchAll());
}

if ($action === 'add_year_level') {
    $name = sanitize($_POST['name'] ?? '');
    if (!$name) jsonResponse(['error' => 'Name required'], 400);
    $db->prepare("INSERT INTO year_levels (teacher_id,name) VALUES (?,?)")->execute([$tid,$name]);
    jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
}

if ($action === 'edit_year_level') {
    $id   = (int)($_POST['id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    if (!$id || !$name) jsonResponse(['error' => 'Invalid data'], 400);
    $db->prepare("UPDATE year_levels SET name=? WHERE id=? AND teacher_id=?")->execute([$name,$id,$tid]);
    jsonResponse(['success' => true]);
}

if ($action === 'delete_year_level') {
    $id = (int)($_POST['id'] ?? 0);
    $db->prepare("DELETE FROM year_levels WHERE id=? AND teacher_id=?")->execute([$id,$tid]);
    jsonResponse(['success' => true]);
}

// ─── SECTIONS ─────────────────────────────────────────────────────────────────
if ($action === 'get_sections') {
    $ylid = (int)($_GET['year_level_id'] ?? 0);
    $stmt = $db->prepare("SELECT s.*, COUNT(st.id) AS student_count FROM sections s LEFT JOIN students st ON st.section_id=s.id WHERE s.year_level_id=? AND s.teacher_id=? GROUP BY s.id ORDER BY s.name");
    $stmt->execute([$ylid,$tid]);
    jsonResponse($stmt->fetchAll());
}

if ($action === 'add_section') {
    $ylid    = (int)($_POST['year_level_id'] ?? 0);
    $name    = sanitize($_POST['name'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    if (!$ylid || !$name || !$subject) jsonResponse(['error' => 'All fields required'], 400);
    $db->prepare("INSERT INTO sections (year_level_id,teacher_id,name,subject) VALUES (?,?,?,?)")->execute([$ylid,$tid,$name,$subject]);
    jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
}

if ($action === 'edit_section') {
    $id      = (int)($_POST['id'] ?? 0);
    $name    = sanitize($_POST['name'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    if (!$id || !$name || !$subject) jsonResponse(['error' => 'Invalid data'], 400);
    $db->prepare("UPDATE sections SET name=?,subject=? WHERE id=? AND teacher_id=?")->execute([$name,$subject,$id,$tid]);
    jsonResponse(['success' => true]);
}

if ($action === 'delete_section') {
    $id = (int)($_POST['id'] ?? 0);
    $db->prepare("DELETE FROM sections WHERE id=? AND teacher_id=?")->execute([$id,$tid]);
    jsonResponse(['success' => true]);
}

// ─── STUDENTS ─────────────────────────────────────────────────────────────────
if ($action === 'get_students') {
    $sid  = (int)($_GET['section_id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM students WHERE section_id=? ORDER BY full_name");
    $stmt->execute([$sid]);
    jsonResponse($stmt->fetchAll());
}

if ($action === 'add_student') {
    $sid   = (int)($_POST['section_id'] ?? 0);
    $name  = sanitize($_POST['full_name'] ?? '');
    $sno   = sanitize($_POST['student_no'] ?? '');
    if (!$sid || !$name) jsonResponse(['error' => 'All fields required'], 400);
    $db->prepare("INSERT INTO students (section_id,full_name,student_no) VALUES (?,?,?)")->execute([$sid,$name,$sno]);
    jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
}

if ($action === 'edit_student') {
    $id   = (int)($_POST['id'] ?? 0);
    $name = sanitize($_POST['full_name'] ?? '');
    $sno  = sanitize($_POST['student_no'] ?? '');
    if (!$id || !$name) jsonResponse(['error' => 'Invalid data'], 400);
    $db->prepare("UPDATE students SET full_name=?,student_no=? WHERE id=?")->execute([$name,$sno,$id]);
    jsonResponse(['success' => true]);
}

if ($action === 'delete_student') {
    $id = (int)($_POST['id'] ?? 0);
    $db->prepare("DELETE FROM students WHERE id=?")->execute([$id]);
    jsonResponse(['success' => true]);
}

// ─── ATTENDANCE ───────────────────────────────────────────────────────────────
if ($action === 'get_session') {
    $sid  = (int)($_GET['section_id'] ?? 0);
    $date = $_GET['date'] ?? '';
    $stmt = $db->prepare("SELECT * FROM attendance_sessions WHERE section_id=? AND session_date=?");
    $stmt->execute([$sid,$date]);
    $session = $stmt->fetch();
    if (!$session) jsonResponse(['session' => null]);
    $recs = $db->prepare("SELECT ar.student_id, ar.status FROM attendance_records ar WHERE ar.session_id=?");
    $recs->execute([$session['id']]);
    jsonResponse(['session' => $session, 'records' => $recs->fetchAll()]);
}

if ($action === 'save_attendance') {
    $sid     = (int)($_POST['section_id'] ?? 0);
    $date    = $_POST['date'] ?? '';
    $records = json_decode($_POST['records'] ?? '[]', true);
    if (!$sid || !$date || !is_array($records)) jsonResponse(['error' => 'Invalid data'], 400);

    // Upsert session
    $stmt = $db->prepare("SELECT id FROM attendance_sessions WHERE section_id=? AND session_date=?");
    $stmt->execute([$sid,$date]);
    $existing = $stmt->fetch();
    if ($existing) {
        $session_id = $existing['id'];
    } else {
        $db->prepare("INSERT INTO attendance_sessions (section_id,teacher_id,session_date) VALUES (?,?,?)")->execute([$sid,$tid,$date]);
        $session_id = $db->lastInsertId();
    }

    // Upsert records
    $upsert = $db->prepare("INSERT INTO attendance_records (session_id,student_id,status) VALUES (?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status)");
    foreach ($records as $r) {
        $upsert->execute([$session_id, (int)$r['student_id'], $r['status']]);
    }
    jsonResponse(['success' => true, 'session_id' => $session_id]);
}

if ($action === 'get_attendance_log') {
    $sid  = (int)($_GET['section_id'] ?? 0);
    $stmt = $db->prepare("
        SELECT als.session_date, 
               COUNT(ar.id) AS total,
               SUM(ar.status='present') AS present,
               SUM(ar.status='absent') AS absent,
               SUM(ar.status='late') AS late,
               SUM(ar.status='excused') AS excused
        FROM attendance_sessions als
        LEFT JOIN attendance_records ar ON ar.session_id=als.id
        WHERE als.section_id=?
        GROUP BY als.session_date
        ORDER BY als.session_date DESC
    ");
    $stmt->execute([$sid]);
    jsonResponse($stmt->fetchAll());
}

if ($action === 'get_report') {
    $sid = (int)($_GET['section_id'] ?? 0);
    $stmt = $db->prepare("
        SELECT s.id, s.full_name, s.student_no,
            COUNT(ar.id) AS total_sessions,
            SUM(ar.status='present') AS present_count,
            SUM(ar.status='absent') AS absent_count,
            SUM(ar.status='late') AS late_count,
            SUM(ar.status='excused') AS excused_count,
            ROUND(SUM(ar.status='present') / NULLIF(COUNT(ar.id),0) * 100, 1) AS attendance_rate
        FROM students s
        LEFT JOIN attendance_records ar ON ar.student_id=s.id
        LEFT JOIN attendance_sessions als ON als.id=ar.session_id AND als.section_id=?
        WHERE s.section_id=?
        GROUP BY s.id
        ORDER BY attendance_rate ASC
    ");
    $stmt->execute([$sid, $sid]);
    jsonResponse($stmt->fetchAll());
}

if ($action === 'get_section_info') {
    $sid  = (int)($_GET['section_id'] ?? 0);
    $stmt = $db->prepare("SELECT s.*, yl.name AS year_level_name FROM sections s JOIN year_levels yl ON yl.id=s.year_level_id WHERE s.id=? AND s.teacher_id=?");
    $stmt->execute([$sid,$tid]);
    jsonResponse($stmt->fetch() ?: []);
}

jsonResponse(['error' => 'Unknown action'], 400);
