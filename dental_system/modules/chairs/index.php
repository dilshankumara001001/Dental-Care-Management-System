<?php
$page_title = 'Chairs Management';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$msg = '';

// ============================================
// HANDLE ACTIONS
// ============================================

// FEATURE 1: Update Chair Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_chair'])) {
    $id = (int)$_POST['chair_id'];
    $status = $_POST['status'];
    $patient_id = !empty($_POST['patient_id']) ? (int)$_POST['patient_id'] : null;
    $doctor_id  = !empty($_POST['doctor_id'])  ? (int)$_POST['doctor_id']  : null;
    $notes      = $_POST['notes'] ?? '';
    
    $stmt = $conn->prepare("UPDATE chairs SET status=?, current_patient_id=?, current_doctor_id=?, notes=? WHERE id=?");
    $stmt->bind_param("siisi", $status, $patient_id, $doctor_id, $notes, $id);
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'UPDATE_CHAIR', "Chair ID $id → $status");
        $msg = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Chair updated!</div>';
    }
}

// FEATURE 2: Free Chair (quick action)
if (isset($_GET['free'])) {
    $id = (int)$_GET['free'];
    $conn->query("UPDATE chairs SET status='available', current_patient_id=NULL, current_doctor_id=NULL WHERE id=$id");
    logActivity($conn, $_SESSION['user_id'], 'FREE_CHAIR', "Chair ID $id freed");
    $msg = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Chair is now available.</div>';
}

// FEATURE 3: Set Maintenance
if (isset($_GET['maintenance'])) {
    $id = (int)$_GET['maintenance'];
    $conn->query("UPDATE chairs SET status='maintenance', current_patient_id=NULL, current_doctor_id=NULL WHERE id=$id");
    logActivity($conn, $_SESSION['user_id'], 'MAINTENANCE_CHAIR', "Chair ID $id → maintenance");
    $msg = '<div class="alert alert-warning"><i class="bi bi-tools"></i> Chair set to maintenance.</div>';
}

// FEATURE 4: Add New Chair
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_chair'])) {
    $stmt = $conn->prepare("INSERT INTO chairs (chair_name, location) VALUES (?,?)");
    $stmt->bind_param("ss", $_POST['chair_name'], $_POST['location']);
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'ADD_CHAIR', "Added: {$_POST['chair_name']}");
        $msg = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> New chair added!</div>';
    }
}

// FEATURE 5: Delete Chair
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM chairs WHERE id=$id");
    logActivity($conn, $_SESSION['user_id'], 'DELETE_CHAIR', "Chair ID $id deleted");
    $msg = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Chair deleted.</div>';
}

// ============================================
// GET DATA
// ============================================
$filter_status = $_GET['status'] ?? '';
$where_chair = "1";
if ($filter_status) $where_chair = "c.status = '" . $conn->real_escape_string($filter_status) . "'";

$chairs = $conn->query("SELECT c.*, p.full_name AS patient_name, p.patient_code, 
    u.full_name AS doctor_name,
    (SELECT COUNT(*) FROM appointments a WHERE a.chair_id = c.id AND a.appointment_date = CURDATE() AND a.status IN ('pending','confirmed')) AS today_appts
    FROM chairs c 
    LEFT JOIN patients p ON c.current_patient_id = p.id 
    LEFT JOIN users u ON c.current_doctor_id = u.id 
    WHERE $where_chair
    ORDER BY c.id");

$patients = $conn->query("SELECT id, full_name, patient_code FROM patients WHERE status='active' ORDER BY full_name");
$doctors  = $conn->query("SELECT id, full_name FROM users WHERE role='doctor' AND status=1 ORDER BY full_name");

// ============================================
// FEATURE 6-10: STATS
// ============================================
$stat_total       = countRows($conn, 'chairs');
$stat_available   = countRows($conn, 'chairs', "status='available'");
$stat_occupied    = countRows($conn, 'chairs', "status='occupied'");
$stat_maintenance = countRows($conn, 'chairs', "status='maintenance'");
$utilization      = $stat_total > 0 ? round(($stat_occupied / $stat_total) * 100) : 0;

// FEATURE 11: Chair utilization today
$today_chair_usage = $conn->query("SELECT chair_id, COUNT(*) c FROM appointments 
    WHERE appointment_date = CURDATE() AND chair_id IS NOT NULL 
    GROUP BY chair_id");

// FEATURE 12: Most used chair
$most_used = $conn->query("SELECT c.chair_name, COUNT(a.id) total 
    FROM chairs c LEFT JOIN appointments a ON c.id = a.chair_id 
    GROUP BY c.id ORDER BY total DESC LIMIT 1")->fetch_assoc();

// FEATURE 13: Recent chair activities
$recent_activities = $conn->query("SELECT * FROM activity_log 
    WHERE action LIKE '%CHAIR%' ORDER BY id DESC LIMIT 8");
?>

<?= $msg ?>

<!-- ============================================
     FEATURE 14: HEADER
============================================ -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-grid-3x3-gap-fill"></i> Chairs Management</h4>
        <small class="text-muted">Real-time status of all dental chairs</small>
    </div>
    <div>
        <button class="btn btn-outline-primary" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addChairModal">
            <i class="bi bi-plus-circle"></i> Add Chair
        </button>
    </div>
</div>

<!-- ============================================
     FEATURE 15-19: STATS CARDS
============================================ -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card p-3 stat-card text-center">
            <i class="bi bi-grid-3x3-gap-fill text-primary" style="font-size:30px;"></i>
            <small class="text-muted">Total Chairs</small>
            <h3 class="mb-0"><?= $stat_total ?></h3>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card p-3 stat-card text-center">
            <i class="bi bi-check-circle-fill text-success" style="font-size:30px;"></i>
            <small class="text-muted">Available</small>
            <h3 class="mb-0 text-success"><?= $stat_available ?></h3>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card p-3 stat-card text-center">
            <i class="bi bi-x-circle-fill text-danger" style="font-size:30px;"></i>
            <small class="text-muted">Occupied</small>
            <h3 class="mb-0 text-danger"><?= $stat_occupied ?></h3>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card p-3 stat-card text-center">
            <i class="bi bi-tools text-secondary" style="font-size:30px;"></i>
            <small class="text-muted">Maintenance</small>
            <h3 class="mb-0 text-secondary"><?= $stat_maintenance ?></h3>
        </div>
    </div>
</div>

<!-- ============================================
     FEATURE 20: UTILIZATION BAR
============================================ -->
<div class="card p-3 mb-4">
    <div class="d-flex justify-content-between mb-2">
        <h6 class="mb-0"><i class="bi bi-speedometer2"></i> Overall Chair Utilization</h6>
        <span class="badge bg-<?= $utilization > 70 ? 'danger' : ($utilization > 40 ? 'warning' : 'success') ?> fs-6">
            <?= $utilization ?>%
        </span>
    </div>
    <div class="progress" style="height: 20px;">
        <div class="progress-bar bg-<?= $utilization > 70 ? 'danger' : ($utilization > 40 ? 'warning' : 'success') ?>" 
             style="width: <?= $utilization ?>%">
        </div>
    </div>
    <small class="text-muted mt-2">
        <i class="bi bi-info-circle"></i> <?= $stat_occupied ?> out of <?= $stat_total ?> chairs currently occupied
        <?php if ($most_used): ?>
            | Most used: <b><?= e($most_used['chair_name']) ?></b> (<?= $most_used['total'] ?> appointments)
        <?php endif; ?>
    </small>
</div>

<!-- ============================================
     FEATURE 21: STATUS FILTER
============================================ -->
<div class="mb-3">
    <a href="?" class="btn btn-sm <?= !$filter_status?'btn-primary':'btn-outline-primary' ?>">
        All (<?= $stat_total ?>)
    </a>
    <a href="?status=available" class="btn btn-sm <?= $filter_status=='available'?'btn-success':'btn-outline-success' ?>">
        <i class="bi bi-check-circle"></i> Available (<?= $stat_available ?>)
    </a>
    <a href="?status=occupied" class="btn btn-sm <?= $filter_status=='occupied'?'btn-danger':'btn-outline-danger' ?>">
        <i class="bi bi-x-circle"></i> Occupied (<?= $stat_occupied ?>)
    </a>
    <a href="?status=maintenance" class="btn btn-sm <?= $filter_status=='maintenance'?'btn-secondary':'btn-outline-secondary' ?>">
        <i class="bi bi-tools"></i> Maintenance (<?= $stat_maintenance ?>)
    </a>
</div>

<!-- ============================================
     FEATURE 22-25: CHAIRS GRID
============================================ -->
<div class="row g-3 mb-4">
    <?php if ($chairs->num_rows == 0): ?>
        <div class="col-12">
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle"></i> No chairs found with this filter.
            </div>
        </div>
    <?php endif; ?>
    
    <?php while ($c = $chairs->fetch_assoc()): ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="card chair-card h-100" style="border-top: 5px solid <?= $c['status']=='available' ? '#28a745' : ($c['status']=='occupied' ? '#dc3545' : '#6c757d') ?>;">
                <div class="card-body">
                    <!-- Chair Header -->
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="mb-0">
                            <i class="bi bi-grid-3x3-gap-fill"></i> <?= e($c['chair_name']) ?>
                        </h5>
                        <span class="badge bg-<?= $c['status']=='available' ? 'success' : ($c['status']=='occupied' ? 'danger' : 'secondary') ?>">
                            <?= ucfirst($c['status']) ?>
                        </span>
                    </div>
                    
                    <?php if ($c['location']): ?>
                        <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= e($c['location']) ?></small>
                    <?php endif; ?>
                    
                    <hr class="my-2">
                    
                    <!-- Patient Info -->
                    <?php if ($c['patient_name']): ?>
                        <div class="mb-2">
                            <small class="text-muted">Patient:</small>
                            <div class="fw-bold">
                                <i class="bi bi-person-circle text-primary"></i> 
                                <?= e($c['patient_name']) ?>
                            </div>
                            <span class="badge bg-light text-dark"><?= e($c['patient_code']) ?></span>
                        </div>
                    <?php else: ?>
                        <div class="text-muted text-center py-2">
                            <i class="bi bi-dash-circle"></i> No patient assigned
                        </div>
                    <?php endif; ?>
                    
                    <!-- Doctor Info -->
                    <?php if ($c['doctor_name']): ?>
                        <div class="mb-2">
                            <small class="text-muted">Doctor:</small>
                            <div class="fw-bold">
                                <i class="bi bi-person-badge text-success"></i> 
                                Dr. <?= e($c['doctor_name']) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Today's appointments -->
                    <?php if ($c['today_appts'] > 0): ?>
                        <div class="alert alert-info py-1 px-2 mb-2 small">
                            <i class="bi bi-calendar-check"></i> 
                            <?= $c['today_appts'] ?> appointment(s) today
                        </div>
                    <?php endif; ?>
                    
                    <!-- Last updated -->
                    <small class="text-muted d-block">
                        <i class="bi bi-clock-history"></i> 
                        <?= timeAgo($c['updated_at']) ?>
                    </small>
                    
                    <!-- Action Buttons -->
                    <div class="d-flex gap-1 mt-3 flex-wrap">
                        <button class="btn btn-sm btn-primary flex-fill" 
                                data-bs-toggle="modal" 
                                data-bs-target="#chairModal"
                                data-id="<?= $c['id'] ?>"
                                data-name="<?= e($c['chair_name']) ?>"
                                data-status="<?= $c['status'] ?>"
                                data-patient="<?= $c['current_patient_id'] ?>"
                                data-doctor="<?= $c['current_doctor_id'] ?>"
                                data-notes="<?= e($c['notes']) ?>">
                            <i class="bi bi-pencil"></i> Update
                        </button>
                        
                        <?php if ($c['status'] == 'occupied'): ?>
                            <a href="?free=<?= $c['id'] ?>" class="btn btn-sm btn-success" 
                               onclick="return confirm('Free this chair?')" title="Mark as Available">
                                <i class="bi bi-check2-circle"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($c['status'] != 'maintenance'): ?>
                            <a href="?maintenance=<?= $c['id'] ?>" class="btn btn-sm btn-secondary" 
                               onclick="return confirm('Set to maintenance?')" title="Maintenance">
                                <i class="bi bi-tools"></i>
                            </a>
                        <?php endif; ?>
                        
                        <a href="?delete=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger" 
                           onclick="return confirmDelete('Delete this chair?')" title="Delete">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<!-- ============================================
     FEATURE 26: RECENT ACTIVITIES
============================================ -->
<div class="card p-3 mb-4">
    <h6><i class="bi bi-activity"></i> Recent Chair Activities</h6>
    <hr>
    <?php if ($recent_activities->num_rows == 0): ?>
        <p class="text-muted mb-0">No recent activities.</p>
    <?php else: ?>
        <div class="row">
            <?php while ($a = $recent_activities->fetch_assoc()): ?>
                <div class="col-md-6 mb-2">
                    <div class="d-flex justify-content-between border-bottom py-1">
                        <div>
                            <span class="badge bg-primary"><?= e($a['action']) ?></span>
                            <small><?= e($a['description']) ?></small>
                        </div>
                        <small class="text-muted"><?= timeAgo($a['created_at']) ?></small>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================
     FEATURE 27-30: UPDATE MODAL
============================================ -->
<div class="modal fade" id="chairModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="chair_id" id="chair_id">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="bi bi-pencil-square"></i> Update Chair
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="status" id="s_avail" value="available" required>
                            <label class="btn btn-outline-success" for="s_avail">
                                <i class="bi bi-check-circle"></i> Available
                            </label>
                            
                            <input type="radio" class="btn-check" name="status" id="s_occ" value="occupied">
                            <label class="btn btn-outline-danger" for="s_occ">
                                <i class="bi bi-x-circle"></i> Occupied
                            </label>
                            
                            <input type="radio" class="btn-check" name="status" id="s_maint" value="maintenance">
                            <label class="btn btn-outline-secondary" for="s_maint">
                                <i class="bi bi-tools"></i> Maintenance
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-person"></i> Patient (optional)</label>
                        <select name="patient_id" id="patient_id" class="form-select">
                            <option value="">-- None --</option>
                            <?php 
                            $patients->data_seek(0);
                            while ($p = $patients->fetch_assoc()): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= e($p['patient_code'] . ' - ' . $p['full_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-person-badge"></i> Doctor (optional)</label>
                        <select name="doctor_id" id="doctor_id" class="form-select">
                            <option value="">-- None --</option>
                            <?php 
                            $doctors->data_seek(0);
                            while ($d = $doctors->fetch_assoc()): ?>
                                <option value="<?= $d['id'] ?>">Dr. <?= e($d['full_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-journal-text"></i> Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2" 
                                  placeholder="Any additional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_chair" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ADD CHAIR MODAL -->
<div class="modal fade" id="addChairModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Chair</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Chair Name *</label>
                        <input type="text" name="chair_name" class="form-control" 
                               placeholder="e.g., Chair 13" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" 
                               placeholder="e.g., Room A, Floor 1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_chair" class="btn btn-success">
                        <i class="bi bi-save"></i> Add Chair
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================
     FEATURE 30: MODAL AUTO-FILL SCRIPT
============================================ -->
<script>
// Auto-fill modal on open
document.getElementById('chairModal').addEventListener('show.bs.modal', function(event) {
    let btn = event.relatedTarget;
    
    document.getElementById('modalTitle').innerHTML = 
        '<i class="bi bi-pencil-square"></i> Update ' + btn.dataset.name;
    document.getElementById('chair_id').value = btn.dataset.id;
    document.getElementById('notes').value = btn.dataset.notes || '';
    
    // Set status radio
    document.querySelectorAll('input[name="status"]').forEach(r => r.checked = false);
    let statusMap = { 'available': 's_avail', 'occupied': 's_occ', 'maintenance': 's_maint' };
    let targetId = statusMap[btn.dataset.status];
    if (targetId) document.getElementById(targetId).checked = true;
    
    // Set patient
    document.getElementById('patient_id').value = btn.dataset.patient || '';
    
    // Set doctor
    document.getElementById('doctor_id').value = btn.dataset.doctor || '';
});

// Auto-refresh every 60 seconds (real-time feel)
setTimeout(() => {
    location.reload();
}, 60000);
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>