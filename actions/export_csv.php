<?php
require __DIR__ . '/../config.php';

$q       = trim($_GET['q'] ?? '');
$statusF = $_GET['status'] ?? '';
$prioF   = $_GET['priority'] ?? '';
$fromF   = $_GET['from'] ?? '';
$toF     = $_GET['to'] ?? '';

$where = [];
$params = [];
if ($q !== '') {
    $where[] = "(user_name LIKE ? OR department LIKE ? OR pc_name LIKE ? OR pc_ip LIKE ? OR problem LIKE ? OR engineer LIKE ?)";
    $like = "%$q%";
    array_push($params, $like, $like, $like, $like, $like, $like);
}
if ($statusF !== '') { $where[] = "status = ?"; $params[] = $statusF; }
if ($prioF   !== '') { $where[] = "priority = ?"; $params[] = $prioF; }
if ($fromF   !== '') { $where[] = "start_time >= ?"; $params[] = $fromF . " 00:00:00"; }
if ($toF     !== '') { $where[] = "start_time <= ?"; $params[] = $toF . " 23:59:59"; }

$sql = "SELECT * FROM problems";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY start_time ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$customColumns = $pdo->query("SELECT name FROM custom_columns ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="it-support-log-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows special characters correctly

$header = ['SL','Start Time','Engineer','User Name','Department','Source','PC Name','PC IP',
           'Problem','Solution','Solve Method','Priority','Status','Solved Time'];
$header = array_merge($header, $customColumns);
$header = array_merge($header, ['Handover To','Handover Reason','Handover Date']);
fputcsv($out, $header);

$sl = 1;
foreach ($rows as $r) {
    $custom = $r['custom_data'] ? json_decode($r['custom_data'], true) : [];
    $line = [
        $sl++, $r['start_time'], $r['engineer'], $r['user_name'], $r['department'], $r['source'],
        $r['pc_name'], $r['pc_ip'], $r['problem'], $r['solution'], $r['solve_method'] ?? '', $r['priority'], $r['status'],
        $r['solved_time'] ?? '',
    ];
    foreach ($customColumns as $col) { $line[] = $custom[$col] ?? ''; }
    $line[] = $r['handover_to'] ?? '';
    $line[] = $r['handover_reason'] ?? '';
    $line[] = $r['handover_date'] ?? '';
    fputcsv($out, $line);
}
fclose($out);
exit;
