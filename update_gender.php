<?php
session_start();
require_once 'config/config.php';
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['student_id']) || !empty($_SESSION['is_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorised.']);
    exit;
}

$gender = strtolower(trim($_POST['gender'] ?? ''));

if (!in_array($gender, ['male', 'female'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid gender selection.']);
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE users SET gender = :g WHERE id = :id');
    $stmt->execute([
        ':g'  => $gender,
        ':id' => (int)$_SESSION['student_id'],
    ]);

    $_SESSION['gender'] = $gender;

    echo json_encode(['success' => true, 'gender' => $gender]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Could not save selection. Please try again.']);
}
exit;

