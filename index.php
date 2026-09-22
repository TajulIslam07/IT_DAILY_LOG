<?php
session_start();

$authTimeout = 12 * 60 * 60; // 12 hour

if (!isset($_SESSION['auth_start'])) {
    $_SESSION['auth_start'] = time();
}

if (time() - $_SESSION['auth_start'] >= $authTimeout) {
    unset($_SESSION['auth_start']);

    http_response_code(401);
    header('WWW-Authenticate: Basic realm="Wakanda Support"');
    exit('Authentication expired. Please refresh and login again.');
}
date_default_timezone_set('Asia/Dhaka');
require __DIR__ . '/config.php';

// ---------- load entry to edit (if any) ----------
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
$editEntry = null;
$editCustom = [];
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM problems WHERE id = ?");
    $stmt->execute([$editId]);
    $editEntry = $stmt->fetch();
    if ($editEntry && $editEntry['custom_data']) {
        $editCustom = json_decode($editEntry['custom_data'], true) ?: [];
    }
}

// ---------- custom columns ----------
$customColumns = $pdo->query("SELECT name FROM custom_columns ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);

// ---------- filters ----------
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
$sql .= " ORDER BY start_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$problems = $stmt->fetchAll();

// ---------- summary stats ----------
// All-time totals: always computed, never affected by the month filter or table filters.
$allRows = $pdo->query("SELECT status FROM problems")->fetchAll();
$total = count($allRows);
$openC = 0; $progC = 0; $doneC = 0;
foreach ($allRows as $r) {
    if ($r['status'] === 'Open') $openC++;
    elseif ($r['status'] === 'In Progress') $progC++;
    elseif ($r['status'] === 'Resolved') $doneC++;
}

// Month filter (Summary panel only): "" = All (no month selected), otherwise "YYYY-MM".
$monthSel = $_GET['month'] ?? '';
if ($monthSel !== '' && !preg_match('/^\d{4}-\d{2}$/', $monthSel)) { $monthSel = ''; }

$monthTotal = $monthOpen = $monthProg = $monthDone = 0;
if ($monthSel !== '') {
    $mStmt = $pdo->prepare("SELECT status FROM problems WHERE DATE_FORMAT(start_time, '%Y-%m') = ?");
    $mStmt->execute([$monthSel]);
    $monthRows = $mStmt->fetchAll();
    $monthTotal = count($monthRows);
    foreach ($monthRows as $r) {
        if ($r['status'] === 'Open') $monthOpen++;
        elseif ($r['status'] === 'In Progress') $monthProg++;
        elseif ($r['status'] === 'Resolved') $monthDone++;
    }
}

// Build the list of months for the dropdown: all months from the earliest entry's
// year (or this year if no entries yet) through one year ahead, so it's always
// "all months", not just months that already have data.
$earliestStart = $pdo->query("SELECT MIN(start_time) FROM problems")->fetchColumn();
$startYear = $earliestStart ? (int)date('Y', strtotime($earliestStart)) : (int)date('Y');
$endYear = (int)date('Y') + 1;
$monthOptions = [];
for ($y = $startYear; $y <= $endYear; $y++) {
    for ($m = 1; $m <= 12; $m++) {
        $val = sprintf('%04d-%02d', $y, $m);
        $monthOptions[$val] = date('F Y', strtotime($val . '-01'));
    }
}

// preserve current filters in querystring, minus 'edit'
$qs = $_GET;
unset($qs['edit']);
function qsWith($extra) {
    global $qs;
    return htmlspecialchars('index.php?' . http_build_query(array_merge($qs, $extra)));
}
$baseQs = $qs;

$flash = $_GET['msg'] ?? '';
$flashType = $_GET['ok'] ?? '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>IT Support Daily Log</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body data-flash-msg="<?= htmlspecialchars($flash, ENT_QUOTES) ?>" data-flash-ok="<?= htmlspecialchars($flashType, ENT_QUOTES) ?>">
<div class="wrap">
  <h1>🛠️ IT Support Daily Log</h1>
  <div class="sub">Shared team log — open this page from any PC on the network.</div>

  <!-- SUMMARY -->
   
 <div class="card">
    <div class="toolbar">
      <h2 style="margin:0;">Summary</h2>
     <a href="index.php" class="btn btn-primary">← Back to Home</a>
      <!-- <form method="get" action="index.php" class="month-filter-form">
        <?php foreach ($baseQs as $k => $v): if ($k === 'month') continue; ?>
          <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
        <?php endforeach; ?>
        <select name="month" onchange="this.form.submit()">
          <option value="">All (total count)</option>
          <?php foreach ($monthOptions as $val => $label): ?>
            <option value="<?= $val ?>" <?= $monthSel === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </form> -->
    </div>
    <div class="meta" style="margin-bottom:6px;">All-time (always shown)</div>
    <div class="stat-grid">
      <div class="stat-box"><div class="stat-num"><?= $total ?></div><div class="stat-label">Total</div></div>
      <div class="stat-box"><div class="stat-num" style="color:var(--open)"><?= $openC ?></div><div class="stat-label">Open</div></div>
      <div class="stat-box"><div class="stat-num" style="color:var(--prog)"><?= $progC ?></div><div class="stat-label">In Progress</div></div>
      <div class="stat-box"><div class="stat-num" style="color:var(--done)"><?= $doneC ?></div><div class="stat-label">Resolved</div></div>
    </div>

    <?php if ($monthSel !== ''): ?>
      <div class="meta" style="margin:14px 0 6px;"><?= htmlspecialchars($monthOptions[$monthSel] ?? $monthSel) ?></div>
      <div class="stat-grid">
        <div class="stat-box"><div class="stat-num"><?= $monthTotal ?></div><div class="stat-label">Total</div></div>
        <div class="stat-box"><div class="stat-num" style="color:var(--open)"><?= $monthOpen ?></div><div class="stat-label">Open</div></div>
        <div class="stat-box"><div class="stat-num" style="color:var(--prog)"><?= $monthProg ?></div><div class="stat-label">In Progress</div></div>
        <div class="stat-box"><div class="stat-num" style="color:var(--done)"><?= $monthDone ?></div><div class="stat-label">Resolved</div></div>
      </div>
    <?php endif; ?>

  </div> 
    

  <!-- ENTRY FORM -->
  <div class="card">
    <div class="toolbar">
      <h2 style="margin:0;"><?= $editEntry ? 'Edit Entry' : 'New Entry' ?></h2>
      <button type="button" class="btn-ghost" onclick="document.getElementById('modal-bg').classList.add('show')">⚙ Manage Columns</button>
    </div>
    <form method="post" action="actions/save_entry.php">
      <?php if ($editEntry): ?><input type="hidden" name="id" value="<?= $editEntry['id'] ?>"><?php endif; ?>
      <input type="hidden" name="return_qs" value="<?= htmlspecialchars(http_build_query($baseQs)) ?>">
      <div class="grid">
        <div class="field"><label>Your Name (Engineer)</label>
          <select name="engineer" required>
            <option value="">Select...</option>
            <?php foreach (ENGINEERS as $eng): ?>
              <option <?= ($editEntry['engineer'] ?? '') === $eng ? 'selected' : '' ?>><?= $eng ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Problem Start Time</label>
          <input type="datetime-local"
                name="start_time"
                required
                value="<?= $editEntry
                    ? date('Y-m-d\TH:i', strtotime($editEntry['start_time']))
                    : date('Y-m-d\TH:i') ?>">
      </div>

        <div class="field"><label>User Name</label><input name="user_name" value="<?= htmlspecialchars($editEntry['user_name'] ?? '') ?>" placeholder="Requester's name"></div>
        <div class="field"><label>Department</label><input name="department" value="<?= htmlspecialchars($editEntry['department'] ?? '') ?>" placeholder="e.g. Accounts"></div>

        <div class="field"><label>Source</label>
          <select name="source">
            <?php foreach (SOURCES as $s): ?>
              <option <?= ($editEntry['source'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label>Priority</label>
          <select name="priority">
            <?php foreach (PRIORITIES as $p): ?>
              <option <?= ($editEntry['priority'] ?? 'Medium') === $p ? 'selected' : '' ?>><?= $p ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field"><label>PC Name</label><input name="pc_name" value="<?= htmlspecialchars($editEntry['pc_name'] ?? '') ?>" placeholder="e.g. ACC-PC-05"></div>
        <div class="field"><label>PC IP</label><input name="pc_ip" value="<?= htmlspecialchars($editEntry['pc_ip'] ?? '') ?>" placeholder="e.g. 192.168.1.25"></div>

        <div class="field"><label>Status</label>
          <select name="status">
            <?php foreach (STATUSES as $s): ?>
              <option <?= ($editEntry['status'] ?? 'Open') === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
            <label>Problem End /Solved Time</label>
            <input type="datetime-local"
                  name="start_time"
                  required
                  value="<?= $editEntry
                      ? date('Y-m-d\TH:i', strtotime($editEntry['start_time']))
                      : date('Y-m-d\TH:i') ?>">
        </div>

        <div class="field full"><label>Problem</label><textarea name="problem" required><?= htmlspecialchars($editEntry['problem'] ?? '') ?></textarea></div>
        <div class="field full"><label>Solution</label><textarea name="solution"><?= htmlspecialchars($editEntry['solution'] ?? '') ?></textarea></div>
        <div class="field"><label>Solve Method</label>
          <select name="solve_method">
            <option value="">Select...</option>
            <?php foreach (SOLVE_METHODS as $sm): ?>
              <option <?= ($editEntry['solve_method'] ?? '') === $sm ? 'selected' : '' ?>><?= $sm ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if ($customColumns): ?>
          <div class="full grid" style="grid-column:1/-1;">
            <?php foreach ($customColumns as $col): ?>
              <div class="field">
                <label><?= htmlspecialchars($col) ?></label>
                <input name="custom[<?= htmlspecialchars($col) ?>]" value="<?= htmlspecialchars($editCustom[$col] ?? '') ?>">
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="field full">
          <label><input type="checkbox" id="ho-toggle" style="width:auto;display:inline-block;" onclick="document.getElementById('handover-box').style.display=this.checked?'block':'none'" <?= !empty($editEntry['handover_to']) ? 'checked' : '' ?>> Handover this problem?</label>
          <div id="handover-box" style="display:<?= !empty($editEntry['handover_to']) ? 'block' : 'none' ?>; border:1px dashed var(--hover); border-radius:8px; padding:10px; margin-top:6px;">
            <div class="grid">
              <input name="handover_to" placeholder="Handed over to (name / dept)" value="<?= htmlspecialchars($editEntry['handover_to'] ?? '') ?>">
              <input type="date" name="handover_date" value="<?= htmlspecialchars($editEntry['handover_date'] ?? '') ?>">
              <textarea name="handover_reason" class="full" placeholder="Reason for handover" style="grid-column:1/-1;"><?= htmlspecialchars($editEntry['handover_reason'] ?? '') ?></textarea>
            </div>
          </div>
        </div>
      </div>
      <div style="display:flex; gap:10px; align-items:center; margin-top:6px; flex-wrap:wrap;">
        <button type="submit" class="btn-primary"><?= $editEntry ? 'Update Entry' : '+ Add Entry' ?></button>
        <?php if ($editEntry): ?><a class="btn-secondary" href="index.php?<?= htmlspecialchars(http_build_query($baseQs)) ?>">Cancel Edit</a><?php endif; ?>
      </div>
    </form>
  </div>

  <!-- FILTERS -->
  <form method="get" action="index.php">
    <?php if ($monthSel !== ''): ?><input type="hidden" name="month" value="<?= htmlspecialchars($monthSel) ?>"><?php endif; ?>
    <div class="toolbar">
      <div class="filters">
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search...">
        <select name="status">
          <option value="">All Status</option>
          <?php foreach (STATUSES as $s): ?><option <?= $statusF===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
        </select>
        <select name="priority">
          <option value="">All Priority</option>
          <?php foreach (PRIORITIES as $p): ?><option <?= $prioF===$p?'selected':'' ?>><?= $p ?></option><?php endforeach; ?>
        </select>
        <label style="margin:0;">From <input type="date" name="from" value="<?= htmlspecialchars($fromF) ?>"></label>
        <label style="margin:0;">To <input type="date" name="to" value="<?= htmlspecialchars($toF) ?>"></label>
        <button type="submit" class="btn-secondary">Filter</button>
      </div>
      <div style="display:flex; gap:8px; align-items:center;">
        <span class="count-pill"><?= count($problems) ?> entries</span>
        <a class="btn-secondary" href="actions/export_csv.php?<?= htmlspecialchars(http_build_query($baseQs)) ?>">⬇ Export Excel (CSV)</a>
      </div>
    </div>
  </form>

  <div class="card tblwrap">
    <table>
      <thead>
        <tr>
          <th>SL</th>
          <th>Engineer</th><th>User</th><th>Dept</th><th>Source</th>
          <th>PC Name</th><th>PC IP</th><th>Problem</th><th>Solution</th><th>Solve Method</th><th>Priority</th>
          <th>Status</th><th>Start Time</th><th>Solved Time</th><th>Total Time</th>
          <?php foreach ($customColumns as $col): ?><th><?= htmlspecialchars($col) ?></th><?php endforeach; ?>
          <th>Handover</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php $sl = 1; foreach ($problems as $e):
          $custom = $e['custom_data'] ? json_decode($e['custom_data'], true) : [];
        ?>
        <tr>
          <td class="meta"><?= $sl++ ?></td>
          
          
          <td><?= htmlspecialchars($e['engineer']) ?></td>
          <td><?= htmlspecialchars($e['user_name']) ?></td>
          <td><?= htmlspecialchars($e['department']) ?></td>
          <td><?= htmlspecialchars($e['source']) ?></td>
          <td><?= htmlspecialchars($e['pc_name']) ?></td>
          <td><?= htmlspecialchars($e['pc_ip']) ?></td>
          <td style="max-width:150px;"><?= nl2br(htmlspecialchars($e['problem'])) ?></td>
          <td style="max-width:150px;"><?= nl2br(htmlspecialchars($e['solution'])) ?></td>
          <td><?= htmlspecialchars($e['solve_method'] ?? '') ?></td>
          <td><span class="badge p-<?= str_replace(' ','',$e['priority']) ?>"><?= htmlspecialchars($e['priority']) ?></span></td>
          <td><?= htmlspecialchars($e['status'] ?? '') ?></td>
          <!-- <td>
            <form method="post" action="actions/update_status.php" class="status-form">
              <input type="hidden" name="id" value="<?= $e['id'] ?>">
              <input type="hidden" name="return_qs" value="<?= htmlspecialchars(http_build_query($baseQs)) ?>">
              <select name="status" class="status-select s-<?= str_replace(' ','',$e['status']) ?>" onchange="this.form.submit()">
                <?php foreach (STATUSES as $s): ?><option <?= $e['status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
              </select>
            </form>
          </td> -->
          <td class="meta"><?= htmlspecialchars(date('M j, g:i A', strtotime($e['start_time']))) ?></td>
          <td class="meta"><?= $e['solved_time'] ? htmlspecialchars(date('M j, g:i A', strtotime($e['solved_time']))) : '' ?></td>
          <td class="meta">
          <?php
          if (!empty($e['solved_time'])) {
              $start = new DateTime($e['start_time']);
              $solve = new DateTime($e['solved_time']);
              $diff = $start->diff($solve);

              if ($diff->days > 0) {
                  echo $diff->days . 'd ' . $diff->h . 'h ' . $diff->i . 'm';
              } elseif ($diff->h > 0) {
                  echo $diff->h . 'h ' . $diff->i . 'm';
              } else {
                  echo $diff->i . ' minutes';
              }
          }
          ?>
          </td>
          <?php foreach ($customColumns as $col): ?><td><?= htmlspecialchars($custom[$col] ?? '') ?></td><?php endforeach; ?>
          <td><?php if ($e['handover_to']): ?><span class="handover-tag" title="<?= htmlspecialchars($e['handover_reason']) ?>">→ <?= htmlspecialchars($e['handover_to']) ?> (<?= htmlspecialchars($e['handover_date']) ?>)</span><?php endif; ?></td>
          <td>
            <div class="actions-cell">
              <a class="btn-edit" href="index.php?<?= htmlspecialchars(http_build_query(array_merge($baseQs, ['edit'=>$e['id']]))) ?>#top">Edit</a>
              <button type="button" class="btn-danger" onclick="confirmDelete(<?= $e['id'] ?>)">Delete</button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$problems): ?>
        <tr><td colspan="100"><div class="empty">No entries found for this filter.</div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Manage columns modal -->
<div class="modal-bg" id="modal-bg">
  <div class="modal">
    <h3>Add a new column</h3>
    <form method="post" action="actions/manage_columns.php">
      <input type="hidden" name="op" value="add">
      <input type="hidden" name="return_qs" value="<?= htmlspecialchars(http_build_query($baseQs)) ?>">
      <input type="text" name="name" placeholder="Column name, e.g. Ticket No." required>
      <div class="row"><button type="submit" class="btn-primary">Add Column</button></div>
    </form>
    <hr>
    <h3>Remove a column</h3>
    <form method="post" action="actions/manage_columns.php">
      <input type="hidden" name="op" value="delete">
      <input type="hidden" name="return_qs" value="<?= htmlspecialchars(http_build_query($baseQs)) ?>">
      <select name="name">
        <option value="">Select column...</option>
        <?php foreach ($customColumns as $col): ?><option><?= htmlspecialchars($col) ?></option><?php endforeach; ?>
      </select>
      <div class="row"><button type="submit" class="btn-danger">Delete Column</button></div>
    </form>
    <div class="row"><button type="button" class="btn-secondary" onclick="document.getElementById('modal-bg').classList.remove('show')">Close</button></div>
  </div>
</div>

<!-- Delete confirm modal -->
<div class="modal-bg" id="del-modal-bg">
  <div class="modal">
    <h3>⚠️ Delete this entry?</h3>
    <p class="meta">This cannot be undone. Are you sure you want to permanently delete this problem log entry?</p>
    <form method="post" action="actions/delete_entry.php" id="del-form">
      <input type="hidden" name="id" id="del-id" value="">
      <input type="hidden" name="return_qs" value="<?= htmlspecialchars(http_build_query($baseQs)) ?>">
      <div class="row">
        <button type="button" class="btn-secondary" onclick="document.getElementById('del-modal-bg').classList.remove('show')">Cancel</button>
        <button type="submit" class="btn-warn">Yes, Delete</button>
      </div>
    </form>
  </div>
</div>

<!-- Flash / confirmation popup (shown after Add, Update, Delete, Column changes) -->
<div class="modal-bg" id="flash-modal-bg">
  <div class="modal">
    <h3 id="flash-modal-title">✅ Done</h3>
    <p class="meta" id="flash-modal-text"></p>
    <div class="row"><button type="button" class="btn-primary" onclick="document.getElementById('flash-modal-bg').classList.remove('show')">OK</button></div>
  </div>
</div>

<script src="assets/app.js"></script>
</body>
</html>
