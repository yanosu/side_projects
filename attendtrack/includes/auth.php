<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn() {
    return isset($_SESSION['teacher_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function currentTeacher() {
    return [
        'id'        => $_SESSION['teacher_id'] ?? null,
        'full_name' => $_SESSION['teacher_name'] ?? '',
        'email'     => $_SESSION['teacher_email'] ?? ''
    ];
}

function loginTeacher($teacher) {
    $_SESSION['teacher_id']    = $teacher['id'];
    $_SESSION['teacher_name']  = $teacher['full_name'];
    $_SESSION['teacher_email'] = $teacher['email'];
}

function logoutTeacher() {
    session_destroy();
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function sanitize($value) {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}
