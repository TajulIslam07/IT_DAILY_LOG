<?php
require __DIR__ . '/../config.php';

$returnQs = $_POST['return_qs'] ?? '';
$redirectBase = '../index.php' . ($returnQs ? '?' . $returnQs : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id']) && in_array($_POST['status'] ?? '', STATUSES, true)) {
    // Only the status field is changed here. Solved Time is never auto-filled —
    // set or adjust it manually via Edit if needed.
    $stmt = $pdo->prepare("UPDATE problems SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['status'], (int)$_POST['id']]);
}

header('Location: ' . $redirectBase);
exit;
