<?php
require __DIR__ . '/../config.php';

$returnQs = $_POST['return_qs'] ?? '';
$redirectBase = '../index.php' . ($returnQs ? '?' . $returnQs : '');
function redirectWith2($base, $msg, $ok = '1') {
    $sep = strpos($base, '?') !== false ? '&' : '?';
    header('Location: ' . $base . $sep . 'msg=' . urlencode($msg) . '&ok=' . $ok);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . $redirectBase); exit; }

$op   = $_POST['op'] ?? '';
$name = trim($_POST['name'] ?? '');

if ($op === 'add' && $name !== '') {
    try {
        $stmt = $pdo->prepare("INSERT INTO custom_columns (name) VALUES (?)");
        $stmt->execute([$name]);
        redirectWith2($redirectBase, "Column \"$name\" added.");
    } catch (PDOException $e) {
        redirectWith2($redirectBase, "Column \"$name\" already exists or is invalid.", '0');
    }
} elseif ($op === 'delete' && $name !== '') {
    $stmt = $pdo->prepare("DELETE FROM custom_columns WHERE name = ?");
    $stmt->execute([$name]);
    redirectWith2($redirectBase, "Column \"$name\" removed. (Existing data for it is kept in the database, just hidden.)");
} else {
    redirectWith2($redirectBase, 'No column name given.', '0');
}
