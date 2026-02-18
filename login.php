<?php
session_start();
require_once 'config/config.php';
require_once 'includes/db.php';

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'login') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$matric = trim($_POST['matric_number'] ?? '');

if ($matric === '') {
    echo json_encode(['success' => false, 'message' => 'Please enter your Matric number.']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, name, role FROM users WHERE matric_number = :m LIMIT 1');
    $stmt->execute([':m' => $matric]);
    $row = $stmt->fetch();

    if ($row) {
        // Store session
        $_SESSION['student_id']   = (int)$row['id'];
        $_SESSION['student_name'] = $row['name'];
        // Gender removed from schema and session
        $_SESSION['is_admin']     = ($row['role'] === 'director') ? 1 : 0;

        $redirect = $_SESSION['is_admin'] ? 'admin_dashboard.php' : 'dashboard.php';

        echo json_encode([
            'success'  => true,
            'redirect' => $redirect,
            'name'     => $row['name'],
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Matric number not recognised. Please check and try again.',
        ]);
    }

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'A system error occurred. Please try again later.',
    ]);
}
exit;