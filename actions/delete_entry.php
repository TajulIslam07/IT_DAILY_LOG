<?php
require __DIR__ . '/../config.php';

$returnQs = $_POST['return_qs'] ?? '';
$redirectBase = '../index.php' . ($returnQs ? '?' . $returnQs : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $stmt = $pdo->prepare("DELETE FROM problems WHERE id = ?");
    $stmt->execute([(int)$_POST['id']]);
    $sep = strpos($redirectBase, '?') !== false ? '&' : '?';
    header('Location: ' . $redirectBase . $sep . 'msg=' . urlencode('Entry deleted.') . '&ok=1');
    exit;
}

header('Location: ' . $redirectBase);
exit;
