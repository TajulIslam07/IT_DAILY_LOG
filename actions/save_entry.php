<?php
require __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$returnQs = $_POST['return_qs'] ?? '';
$redirectBase = '../index.php' . ($returnQs ? '?' . $returnQs : '');
function redirectWith($base, $msg, $ok = '1') {
    $sep = strpos($base, '?') !== false ? '&' : '?';
    header('Location: ' . $base . $sep . 'msg=' . urlencode($msg) . '&ok=' . $ok);
    exit;
}

$id        = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
$engineer  = trim($_POST['engineer'] ?? '');
$startTime = $_POST['start_time'] ?? '';
$userName  = trim($_POST['user_name'] ?? '');
$department= trim($_POST['department'] ?? '');
$source    = $_POST['source'] ?? '';
$priority  = $_POST['priority'] ?? 'Medium';
$pcName    = trim($_POST['pc_name'] ?? '');
$pcIp      = trim($_POST['pc_ip'] ?? '');
$status    = $_POST['status'] ?? 'Open';
$solvedRaw = trim($_POST['solved_time'] ?? '');
$problem   = trim($_POST['problem'] ?? '');
$solution  = trim($_POST['solution'] ?? '');
$solveMethod = $_POST['solve_method'] ?? '';
if (!in_array($solveMethod, SOLVE_METHODS, true)) { $solveMethod = ''; }
$hoTo      = trim($_POST['handover_to'] ?? '');
$hoDate    = $_POST['handover_date'] ?? '';
$hoReason  = trim($_POST['handover_reason'] ?? '');
$customIn  = $_POST['custom'] ?? [];

// basic validation
if (!in_array($engineer, ENGINEERS, true) || $problem === '' || $startTime === '') {
    redirectWith($redirectBase, 'Please select your name, a start time, and describe the problem.', '0');
}

$solvedTime = $solvedRaw !== '' ? str_replace('T', ' ', $solvedRaw) . ':00' : null;
$startTimeSql = str_replace('T', ' ', $startTime) . ':00';

$customJson = null;
if ($customIn) {
    $clean = [];
    foreach ($customIn as $k => $v) { $clean[$k] = trim($v); }
    $customJson = json_encode($clean, JSON_UNESCAPED_UNICODE);
}

$handoverTo = $hoTo !== '' ? $hoTo : null;
$handoverDate = ($hoTo !== '' && $hoDate !== '') ? $hoDate : null;
$handoverReason = $hoTo !== '' ? $hoReason : null;

if ($id) {
    $sql = "UPDATE problems SET engineer=?, start_time=?, user_name=?, department=?, source=?, priority=?,
            pc_name=?, pc_ip=?, status=?, solved_time=?, problem=?, solution=?, solve_method=?,
            handover_to=?, handover_date=?, handover_reason=?, custom_data=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $engineer, $startTimeSql, $userName, $department, $source, $priority,
        $pcName, $pcIp, $status, $solvedTime, $problem, $solution, $solveMethod,
        $handoverTo, $handoverDate, $handoverReason, $customJson, $id
    ]);
    redirectWith($redirectBase, 'Entry updated.');
} else {
    $sql = "INSERT INTO problems (engineer, start_time, user_name, department, source, priority,
            pc_name, pc_ip, status, solved_time, problem, solution, solve_method,
            handover_to, handover_date, handover_reason, custom_data)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $engineer, $startTimeSql, $userName, $department, $source, $priority,
        $pcName, $pcIp, $status, $solvedTime, $problem, $solution, $solveMethod,
        $handoverTo, $handoverDate, $handoverReason, $customJson
    ]);
    redirectWith($redirectBase, 'Entry added.');
}
