<?php
$page_title = 'Appointments';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$msg = '';

// Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $chair_id = !empty($_POST['chair_id']) ? (int)$_POST['chair_id'] : null;
    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, chair_id, appointment_date, appointment_time, treatment, status, notes, duration_minutes, priority) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("iiisssssis", $_POST['patient_id'], $_POST['doctor_id'], $chair_id, $_POST['appointment_date'], $_POST['appointment_time'], $_POST['treatment'], $_POST['status'], $_POST['notes'], $_POST['duration_minutes'], $_POST['priority']);
    if ($stmt->execute()) $msg = '<div class="alert alert-success">Appointment booked!</div>';
}

// Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = (int)$_POST['id'];
    $chair_id = !empty($_POST['chair_id']) ? (int)$_POST['chair_id'] : null;
    $stmt = $conn->prepare("UPDATE appointments SET patient_id=?, doctor_id=?, chair_id=?, appointment_date=?, appointment_time=?, treatment=?, status=?, notes=?, duration_minutes=?, priority=? WHERE id=?");
    $stmt->bind_param("iiisssssisi", $_POST['patient_id'], $_POST['doctor_id'], $chair_id, $_POST['appointment_date'], $_POST['appointment_time'], $_POST['treatment'], $_POST['status'], $_POST['notes'], $_POST['duration_minutes'], $_POST['priority'], $id);
    if ($stmt->execute()) $msg = '<div class="alert alert-success">Updated!</div>';
}

// Delete
if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM appointments WHERE id=" . (int)$_GET['delete']);
    $msg = '<div class="alert alert-success">Deleted.</div>';
}

// Status change
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $st = $conn->real_escape_string($_GET['status']);
    $conn->query("UPDATE appointments SET status='$st' WHERE id=$id");
    $msg = '<div class="alert alert-info">Status: <b>' . ucfirst($st) . '</b></div>';
}

// Filters
$filter_date = $_GET['date'] ?? '';
$filter_st   = $_GET['status_f'] ?? '';
$search      = trim($_GET['search'] ?? '');
$page        = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 10;
$offset      = ($page - 1) * $per_page;

$where = "1";
$params = [];
$types = '';

if ($filter_date) { $where .= " AND a.appointment_date = ?"; $params[] = $filter_date; $types .= 's'; }
if ($filter_st)   { $where .= " AND a.status = ?"; $params[] = $filter_st; $types .= 's'; }
if ($search) {
    $where .= " AND (p.full_name LIKE ? OR p.phone LIKE ? OR p.patient_code LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s,$s,$s]);
    $types .= 'sss';
}

$count_sql = "SELECT COUNT(*) c FROM appointments a LEFT JOIN patients p ON a.patient_id=p.id WHERE $where";
if ($params) {
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_records = $stmt->get_result()->fetch_assoc()['c'];
} else {
    $total_records = $conn->query($count_sql)->fetch_assoc()['c'];
}
$total_pages = ceil($total_records / $per_page);

$sql = "SELECT a.*, p.full_name AS patient_name, p.patient_code, p.phone AS patient_phone, u.full_name AS doctor_name, c.chair_name 
    FROM appointments a 
    LEFT JOIN patients p ON a.patient_id = p.id 
    LEFT JOIN users u ON a.doctor_id = u.id 
    LEFT JOIN chairs c ON a.chair_id = c.id 
    WHERE $where 
    ORDER BY a.appointment_date DESC, a.appointment_time DESC 
    LIMIT $per_page OFFSET $offset";

if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $appts = $stmt->get_result();
} else {
    $appts = $conn->query($sql);
}

// Save all in array for modals
$all_appts = [];
$appts_copy = $conn->query($sql);
while ($row = $appts_copy->fetch_assoc()) $all_appts[] = $row;

// Dropdowns
$patients = $conn->query("SELECT id, full_name, patient_code FROM patients WHERE status='active' ORDER BY full_name");
$doctors  = $conn->query("SELECT id, full_name FROM users WHERE role='doctor' AND status=1 ORDER BY full_name");
$chairs   = $conn->query("SELECT id, chair_name FROM chairs WHERE status != 'maintenance' ORDER BY id");

// Stats
$stat_today     = countRows($conn, 'appointments', "appointment_date = CURDATE()");
$stat_pending   = countRows($conn, 'appointments', "status='pending'");
$stat_confirmed = countRows($conn, 'appointments', "status='confirmed'");
$stat_completed = countRows($conn, 'appointments', "status='completed'");
$stat_cancelled = countRows($conn, 'appointments', "status='cancelled'");
$stat_all       = countRows($conn, 'appointments');

// Upcoming today
$upcoming_today = $conn->query("SELECT a.*, p.full_name patient_name, u.full_name doctor_name, c.chair_name 
    FROM appointments a 
    LEFT JOIN patients p ON a.patient_id = p.id 
    LEFT JOIN users u ON a.doctor_id = u.id 
    LEFT JOIN chairs c ON a.chair_id = c.id 
    WHERE a.appointment_date = CURDATE() AND a.status IN ('pending','confirmed')
    ORDER BY a.appointment_time LIMIT 5");
?>

<?= $msg ?>

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-calendar-check-fill"></i> Appointments</h4>
        <small class="text-muted">Manage all patient appointments</small>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-calendar-plus-fill"></i> Book Appointment
    </button>
</div>

<!-- STATS -->
<div class="row g-3 mb-4">
    <div class="col-lg-2 col-md-4 col-6">
        <div class="card p-3 text-center stat-card">
            <i class="bi bi-calendar-day text-primary" style="font-size:24px;"></i>
            <small class="text-muted">Today</small>
            <h4 class="mb-0"><?= $stat_today ?></h4>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
        <div class="card p-3 text-center stat-card">
            <i class="bi bi-hourglass-split text-warning" style="font-size:24px;"></i>
            <small class="text-muted">Pending</small>
            <h4 class="mb-0 text-warning"><?= $stat_pending ?></h4>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
        <div class="card p-3 text-center stat-card">
            <i class="bi bi-check-circle text-info" style="font-size:24px;"></i>
            <small class="text-muted">Confirmed</small>
            <h4 class="mb-0 text-info"><?= $stat_confirmed ?></h4>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
        <div class="card p-3 text-center stat-card">
            <i class="bi bi-check2-all text-success" style="font-size:24px;"></i>
            <small class="text-muted">Completed</small>
            <h4 class="mb-0 text-success"><?= $stat_completed ?></h4>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
        <div class="card p-3 text-center stat-card">
            <i class="bi bi-x-circle text-danger" style="font-size:24px;"></i>
            <small class="text-muted">Cancelled</small>
            <h4 class="mb-0 text-danger"><?= $stat_cancelled ?></h4>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
        <div class="card p-3 text-center stat-card">
            <i class="bi bi-list-check text-secondary" style="font-size:24px;"></i>
            <small class="text-muted">All</small>
            <h4 class="mb-0"><?= $stat_all ?></h4>
        </div>
    </div>
</div>

<!-- FILTERS -->
<div class="card p-3 mb-3">
    <form method="GET" class="row g-2">
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search patient..." value="<?= e($search) ?>">
            </div>
        </div>
        <div class="col-md-3">
            <input type="date" name="date" class="form-control" value="<?= e($filter_date) ?>">
        </div>
        <div class="col-md-3">
            <select name="status_f" class="form-select">
                <option value="">All Status</option>
                <?php foreach (['pending','confirmed','completed','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $filter_st==$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button class="btn btn-primary w-100"><i class="bi bi-funnel"></i></button>
            <a href="?" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i></a>
        </div>
    </form>
    <div class="mt-2">
        <a href="?date=<?= date('Y-m-d') ?>" class="btn btn-sm btn-outline-primary">Today</a>
        <a href="?" class="btn btn-sm btn-outline-secondary">Clear</a>
    </div>
</div>

<!-- TABLE -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Chair</th>
                    <th>Treatment</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($appts->num_rows === 0): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">
                        <i class="bi bi-calendar-x" style="font-size: 40px;"></i>
                        <p class="mb-0 mt-2">No appointments found</p>
                    </td></tr>
                <?php endif; ?>
                <?php $i = $offset + 1; while ($a = $appts->fetch_assoc()): 
                    $is_today = $a['appointment_date'] == date('Y-m-d');
                    $sc = ['pending'=>'warning','confirmed'=>'info','completed'=>'success','cancelled'=>'danger'];
                ?>
                    <tr class="<?= $is_today ? 'table-info' : '' ?>">
                        <td><?= $i++ ?></td>
                        <td>
                            <b><?= date('M d, Y', strtotime($a['appointment_date'])) ?></b>
                            <?php if ($is_today): ?><span class="badge bg-primary ms-1">Today</span><?php endif; ?>
                        </td>
                        <td><i class="bi bi-clock"></i> <?= date('h:i A', strtotime($a['appointment_time'])) ?></td>
                        <td>
                            <b><?= e($a['patient_name']) ?></b><br>
                            <small class="text-muted"><?= e($a['patient_phone']) ?></small>
                        </td>
                        <td><i class="bi bi-person-badge"></i> Dr. <?= e($a['doctor_name']) ?></td>
                        <td><?= $a['chair_name'] ? '<span class="badge bg-secondary">'.e($a['chair_name']).'</span>' : '-' ?></td>
                        <td><?= e($a['treatment']) ?></td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm badge bg-<?= $sc[$a['status']] ?> dropdown-toggle" data-bs-toggle="dropdown">
                                    <?= ucfirst($a['status']) ?>
                                </button>
                                <ul class="dropdown-menu">
                                    <?php foreach (['pending','confirmed','completed','cancelled'] as $s): ?>
                                        <li><a class="dropdown-item" href="?id=<?= $a['id'] ?>&status=<?= $s ?>"><?= ucfirst($s) ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#viewModal<?= $a['id'] ?>"><i class="bi bi-eye"></i></button>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $a['id'] ?>"><i class="bi bi-pencil"></i></button>
                            <a href="?delete=<?= $a['id'] ?>" class="btn btn-sm btn-danger" data-name="appointment"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="card-footer">
        <nav><ul class="pagination mb-0 justify-content-center">
            <?php for ($i=1; $i<=$total_pages; $i++): ?>
                <li class="page-item <?= $i==$page?'active':'' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$i])) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>

<!-- ==========================================
     MODALS (OUTSIDE TABLE!)
========================================== -->
<?php foreach ($all_appts as $a): 
    $sc = ['pending'=>'warning','confirmed'=>'info','completed'=>'success','cancelled'=>'danger'];
?>
    <!-- VIEW MODAL -->
    <div class="modal fade" id="viewModal<?= $a['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#17a2b8,#0dcaf0);color:white;">
                    <h5 class="modal-title"><i class="bi bi-eye"></i> Appointment Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm">
                        <tr><th width="40%">Patient</th><td><b><?= e($a['patient_name']) ?></b></td></tr>
                        <tr><th>Code</th><td><?= e($a['patient_code']) ?></td></tr>
                        <tr><th>Phone</th><td><?= e($a['patient_phone']) ?></td></tr>
                        <tr><th>Doctor</th><td>Dr. <?= e($a['doctor_name']) ?></td></tr>
                        <tr><th>Chair</th><td><?= e($a['chair_name'] ?? '-') ?></td></tr>
                        <tr><th>Date</th><td><?= date('l, F d, Y', strtotime($a['appointment_date'])) ?></td></tr>
                        <tr><th>Time</th><td><?= date('h:i A', strtotime($a['appointment_time'])) ?></td></tr>
                        <tr><th>Duration</th><td><?= $a['duration_minutes'] ?> minutes</td></tr>
                        <tr><th>Treatment</th><td><?= e($a['treatment']) ?></td></tr>
                        <tr><th>Status</th><td><span class="badge bg-<?= $sc[$a['status']] ?>"><?= ucfirst($a['status']) ?></span></td></tr>
                        <tr><th>Priority</th><td><?= ucfirst($a['priority']) ?></td></tr>
                        <tr><th>Notes</th><td><?= nl2br(e($a['notes'])) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div class="modal fade" id="editModal<?= $a['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                    <div class="modal-header" style="background:linear-gradient(135deg,#ffc107,#fd7e14);color:white;">
                        <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Appointment</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Patient *</label>
                                <select name="patient_id" class="form-select" required>
                                    <?php 
                                    $patients->data_seek(0);
                                    while ($p = $patients->fetch_assoc()): ?>
                                        <option value="<?= $p['id'] ?>" <?= $a['patient_id']==$p['id']?'selected':'' ?>>
                                            <?= e($p['patient_code'] . ' - ' . $p['full_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Doctor *</label>
                                <select name="doctor_id" class="form-select" required>
                                    <?php 
                                    $doctors->data_seek(0);
                                    while ($d = $doctors->fetch_assoc()): ?>
                                        <option value="<?= $d['id'] ?>" <?= $a['doctor_id']==$d['id']?'selected':'' ?>>
                                            Dr. <?= e($d['full_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Chair</label>
                                <select name="chair_id" class="form-select">
                                    <option value="">-- None --</option>
                                    <?php 
                                    $chairs->data_seek(0);
                                    while ($c = $chairs->fetch_assoc()): ?>
                                        <option value="<?= $c['id'] ?>" <?= $a['chair_id']==$c['id']?'selected':'' ?>>
                                            <?= e($c['chair_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date *</label>
                                <input type="date" name="appointment_date" class="form-control" value="<?= e($a['appointment_date']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Time *</label>
                                <input type="time" name="appointment_time" class="form-control" value="<?= e($a['appointment_time']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Treatment</label>
                                <input type="text" name="treatment" class="form-control" value="<?= e($a['treatment']) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Duration</label>
                                <input type="number" name="duration_minutes" class="form-control" value="<?= $a['duration_minutes'] ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-select">
                                    <option value="low" <?= $a['priority']=='low'?'selected':'' ?>>Low</option>
                                    <option value="normal" <?= $a['priority']=='normal'?'selected':'' ?>>Normal</option>
                                    <option value="high" <?= $a['priority']=='high'?'selected':'' ?>>High</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <?php foreach (['pending','confirmed','completed','cancelled'] as $s): ?>
                                        <option value="<?= $s ?>" <?= $a['status']==$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Notes</label>
                                <input type="text" name="notes" class="form-control" value="<?= e($a['notes']) ?>">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update" class="btn btn-warning"><i class="bi bi-save"></i> Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- ADD MODAL -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header" style="background:linear-gradient(135deg,#667eea,#764ba2);color:white;">
                    <h5 class="modal-title"><i class="bi bi-calendar-plus-fill"></i> Book New Appointment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Patient *</label>
                            <select name="patient_id" class="form-select" required>
                                <option value="">-- Select --</option>
                                <?php 
                                $patients->data_seek(0);
                                while ($p = $patients->fetch_assoc()): ?>
                                    <option value="<?= $p['id'] ?>"><?= e($p['patient_code'] . ' - ' . $p['full_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Doctor *</label>
                            <select name="doctor_id" class="form-select" required>
                                <option value="">-- Select --</option>
                                <?php 
                                $doctors->data_seek(0);
                                while ($d = $doctors->fetch_assoc()): ?>
                                    <option value="<?= $d['id'] ?>">Dr. <?= e($d['full_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Chair</label>
                            <select name="chair_id" class="form-select">
                                <option value="">-- Auto --</option>
                                <?php 
                                $chairs->data_seek(0);
                                while ($c = $chairs->fetch_assoc()): ?>
                                    <option value="<?= $c['id'] ?>"><?= e($c['chair_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date *</label>
                            <input type="date" name="appointment_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Time *</label>
                            <input type="time" name="appointment_time" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Treatment</label>
                            <input type="text" name="treatment" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Duration</label>
                            <select name="duration_minutes" class="form-select">
                                <option value="30" selected>30 min</option>
                                <option value="45">45 min</option>
                                <option value="60">1 hour</option>
                                <option value="90">1.5 hours</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="low">Low</option>
                                <option value="normal" selected>Normal</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="pending">Pending</option>
                                <option value="confirmed" selected>Confirmed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Notes</label>
                            <input type="text" name="notes" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add" class="btn btn-primary"><i class="bi bi-save"></i> Book</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>