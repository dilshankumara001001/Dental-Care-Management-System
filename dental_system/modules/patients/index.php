<?php
$page_title = 'Patients Management';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$msg = '';

// Add Patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $code = generatePatientCode($conn);
    $age = $_POST['dob'] ? calculateAge($_POST['dob']) : null;
    $stmt = $conn->prepare("INSERT INTO patients (patient_code, full_name, nic, phone, email, address, gender, dob, age, blood_group, medical_history, allergies, emergency_contact, occupation) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssssisssss", $code, $_POST['full_name'], $_POST['nic'], $_POST['phone'], $_POST['email'], $_POST['address'], $_POST['gender'], $_POST['dob'], $age, $_POST['blood_group'], $_POST['medical_history'], $_POST['allergies'], $_POST['emergency_contact'], $_POST['occupation']);
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'ADD_PATIENT', "Added: {$_POST['full_name']}");
        $msg = '<div class="alert alert-success">Patient added! Code: <b>'.$code.'</b></div>';
    }
}

// Edit Patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $age = $_POST['dob'] ? calculateAge($_POST['dob']) : null;
    $stmt = $conn->prepare("UPDATE patients SET full_name=?, nic=?, phone=?, email=?, address=?, gender=?, dob=?, age=?, blood_group=?, medical_history=?, allergies=?, emergency_contact=?, occupation=? WHERE id=?");
    $stmt->bind_param("ssssssssissssi", $_POST['full_name'], $_POST['nic'], $_POST['phone'], $_POST['email'], $_POST['address'], $_POST['gender'], $_POST['dob'], $age, $_POST['blood_group'], $_POST['medical_history'], $_POST['allergies'], $_POST['emergency_contact'], $_POST['occupation'], $id);
    if ($stmt->execute()) {
        $msg = '<div class="alert alert-success">Patient updated!</div>';
    }
}

// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM patients WHERE id = $id");
    $msg = '<div class="alert alert-success">Patient deleted.</div>';
}

// Toggle
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $conn->query("UPDATE patients SET status = IF(status='active','inactive','active') WHERE id=$id");
    $msg = '<div class="alert alert-info">Status changed.</div>';
}

// Filters
$search    = trim($_GET['search'] ?? '');
$gender_f  = $_GET['gender'] ?? '';
$status_f  = $_GET['status'] ?? '';
$page      = max(1, (int)($_GET['page'] ?? 1));
$per_page  = 10;
$offset    = ($page - 1) * $per_page;

$where = "1";
$params = [];
$types = '';

if ($search) {
    $where .= " AND (full_name LIKE ? OR phone LIKE ? OR patient_code LIKE ? OR nic LIKE ? OR email LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s,$s,$s,$s,$s]);
    $types .= 'sssss';
}
if ($gender_f) { $where .= " AND gender = ?"; $params[] = $gender_f; $types .= 's'; }
if ($status_f) { $where .= " AND status = ?"; $params[] = $status_f; $types .= 's'; }

$count_sql = "SELECT COUNT(*) c FROM patients WHERE $where";
if ($params) {
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_records = $stmt->get_result()->fetch_assoc()['c'];
} else {
    $total_records = $conn->query($count_sql)->fetch_assoc()['c'];
}
$total_pages = ceil($total_records / $per_page);

$sql = "SELECT * FROM patients WHERE $where ORDER BY id DESC LIMIT $per_page OFFSET $offset";
if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $patients = $stmt->get_result();
} else {
    $patients = $conn->query($sql);
}

// Save all patients in array for modals later
$all_patients = [];
$patients_copy = $conn->query("SELECT * FROM patients WHERE $where ORDER BY id DESC LIMIT $per_page OFFSET $offset");
while ($row = $patients_copy->fetch_assoc()) $all_patients[] = $row;

$stat_total  = countRows($conn, 'patients');
$stat_active = countRows($conn, 'patients', "status='active'");
$stat_male   = countRows($conn, 'patients', "gender='male'");
$stat_female = countRows($conn, 'patients', "gender='female'");
?>

<?= $msg ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card p-3 text-center stat-card">
            <i class="bi bi-people-fill text-primary" style="font-size:30px;"></i>
            <small class="text-muted">Total</small>
            <h4 class="mb-0"><?= $stat_total ?></h4>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card p-3 text-center stat-card">
            <i class="bi bi-person-check-fill text-success" style="font-size:30px;"></i>
            <small class="text-muted">Active</small>
            <h4 class="mb-0"><?= $stat_active ?></h4>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card p-3 text-center stat-card">
            <i class="bi bi-gender-male text-info" style="font-size:30px;"></i>
            <small class="text-muted">Male</small>
            <h4 class="mb-0"><?= $stat_male ?></h4>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card p-3 text-center stat-card">
            <i class="bi bi-gender-female text-danger" style="font-size:30px;"></i>
            <small class="text-muted">Female</small>
            <h4 class="mb-0"><?= $stat_female ?></h4>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-people-fill"></i> All Patients <span class="badge bg-secondary"><?= $total_records ?></span></h4>
    <div>
        <button class="btn btn-outline-success" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-person-plus-fill"></i> Add New Patient
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card p-3 mb-3">
    <form method="GET" class="row g-2">
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= e($search) ?>">
            </div>
        </div>
        <div class="col-md-2">
            <select name="gender" class="form-select">
                <option value="">All Genders</option>
                <option value="male" <?= $gender_f=='male'?'selected':'' ?>>Male</option>
                <option value="female" <?= $gender_f=='female'?'selected':'' ?>>Female</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">All Status</option>
                <option value="active" <?= $status_f=='active'?'selected':'' ?>>Active</option>
                <option value="inactive" <?= $status_f=='inactive'?'selected':'' ?>>Inactive</option>
            </select>
        </div>
        <div class="col-md-4 d-flex gap-1">
            <button class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Filter</button>
            <a href="?" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i></a>
        </div>
    </form>
</div>

<!-- TABLE -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Photo</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Blood</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($patients->num_rows === 0): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">No patients found</td></tr>
                <?php endif; ?>
                <?php $i = $offset + 1; while ($p = $patients->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td>
                            <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);color:white;display:flex;align-items:center;justify-content:center;font-weight:bold;">
                                <?= strtoupper(substr($p['full_name'], 0, 1)) ?>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark"><?= e($p['patient_code']) ?></span></td>
                        <td>
                            <b><?= e($p['full_name']) ?></b>
                            <?php if ($p['email']): ?><br><small class="text-muted"><?= e($p['email']) ?></small><?php endif; ?>
                        </td>
                        <td><?= e($p['phone']) ?></td>
                        <td><?= $p['age'] ?: '-' ?></td>
                        <td><i class="bi bi-gender-<?= $p['gender']==='female'?'female':'male' ?>"></i> <?= ucfirst($p['gender']) ?></td>
                        <td><?= $p['blood_group'] ? '<span class="badge bg-danger">'.e($p['blood_group']).'</span>' : '-' ?></td>
                        <td>
                            <a href="?toggle=<?= $p['id'] ?>" class="badge bg-<?= $p['status']=='active'?'success':'secondary' ?> text-decoration-none">
                                <?= ucfirst($p['status']) ?>
                            </a>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#viewModal<?= $p['id'] ?>"><i class="bi bi-eye"></i></button>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $p['id'] ?>"><i class="bi bi-pencil"></i></button>
                            <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-danger" data-name="<?= e($p['full_name']) ?>"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="card-footer">
        <nav>
            <ul class="pagination mb-0 justify-content-center">
                <?php for ($i=1; $i<=$total_pages; $i++): ?>
                    <li class="page-item <?= $i==$page?'active':'' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$i])) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- ==========================================
     MODALS (OUTSIDE TABLE!)
========================================== -->
<?php foreach ($all_patients as $p): ?>
    <!-- VIEW MODAL -->
    <div class="modal fade" id="viewModal<?= $p['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#17a2b8,#0dcaf0);color:white;">
                    <h5 class="modal-title"><i class="bi bi-person-badge"></i> Patient Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div style="width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);color:white;display:flex;align-items:center;justify-content:center;font-size:40px;font-weight:bold;margin:auto;">
                                <?= strtoupper(substr($p['full_name'], 0, 1)) ?>
                            </div>
                            <h5 class="mt-2"><?= e($p['full_name']) ?></h5>
                            <span class="badge bg-primary"><?= e($p['patient_code']) ?></span>
                        </div>
                        <div class="col-md-8">
                            <table class="table table-sm">
                                <tr><th width="40%">NIC</th><td><?= e($p['nic'] ?: '-') ?></td></tr>
                                <tr><th>Phone</th><td><?= e($p['phone']) ?></td></tr>
                                <tr><th>Email</th><td><?= e($p['email'] ?: '-') ?></td></tr>
                                <tr><th>Gender</th><td><?= ucfirst($p['gender']) ?></td></tr>
                                <tr><th>DOB</th><td><?= e($p['dob'] ?: '-') ?></td></tr>
                                <tr><th>Age</th><td><?= $p['age'] ?: '-' ?> years</td></tr>
                                <tr><th>Blood</th><td><?= e($p['blood_group'] ?: '-') ?></td></tr>
                                <tr><th>Occupation</th><td><?= e($p['occupation'] ?: '-') ?></td></tr>
                                <tr><th>Emergency</th><td><?= e($p['emergency_contact'] ?: '-') ?></td></tr>
                                <tr><th>Address</th><td><?= e($p['address'] ?: '-') ?></td></tr>
                                <tr><th>Allergies</th><td><?= e($p['allergies'] ?: '-') ?></td></tr>
                                <tr><th>History</th><td><?= e($p['medical_history'] ?: '-') ?></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div class="modal fade" id="editModal<?= $p['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <div class="modal-header" style="background:linear-gradient(135deg,#ffc107,#fd7e14);color:white;">
                        <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Patient</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control" value="<?= e($p['full_name']) ?>" required></div>
                            <div class="col-md-6"><label class="form-label">NIC</label><input type="text" name="nic" class="form-control" value="<?= e($p['nic']) ?>"></div>
                            <div class="col-md-6"><label class="form-label">Phone *</label><input type="text" name="phone" class="form-control" value="<?= e($p['phone']) ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($p['email']) ?>"></div>
                            <div class="col-md-4"><label class="form-label">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="male" <?= $p['gender']=='male'?'selected':'' ?>>Male</option>
                                    <option value="female" <?= $p['gender']=='female'?'selected':'' ?>>Female</option>
                                    <option value="other" <?= $p['gender']=='other'?'selected':'' ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-4"><label class="form-label">DOB</label><input type="date" name="dob" class="form-control" value="<?= e($p['dob']) ?>"></div>
                            <div class="col-md-4"><label class="form-label">Blood</label><input type="text" name="blood_group" class="form-control" value="<?= e($p['blood_group']) ?>"></div>
                            <div class="col-md-6"><label class="form-label">Occupation</label><input type="text" name="occupation" class="form-control" value="<?= e($p['occupation']) ?>"></div>
                            <div class="col-md-6"><label class="form-label">Emergency</label><input type="text" name="emergency_contact" class="form-control" value="<?= e($p['emergency_contact']) ?>"></div>
                            <div class="col-12"><label class="form-label">Address</label><input type="text" name="address" class="form-control" value="<?= e($p['address']) ?>"></div>
                            <div class="col-12"><label class="form-label">Allergies</label><textarea name="allergies" class="form-control" rows="2"><?= e($p['allergies']) ?></textarea></div>
                            <div class="col-12"><label class="form-label">Medical History</label><textarea name="medical_history" class="form-control" rows="2"><?= e($p['medical_history']) ?></textarea></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit" class="btn btn-warning"><i class="bi bi-save"></i> Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- ADD PATIENT MODAL -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header" style="background:linear-gradient(135deg,#667eea,#764ba2);color:white;">
                    <h5 class="modal-title"><i class="bi bi-person-plus-fill"></i> Add New Patient</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control" required autofocus></div>
                        <div class="col-md-6"><label class="form-label">NIC</label><input type="text" name="nic" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Phone *</label><input type="text" name="phone" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Gender *</label>
                            <select name="gender" class="form-select" required>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label">DOB</label><input type="date" name="dob" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Blood</label>
                            <select name="blood_group" class="form-select">
                                <option value="">--</option>
                                <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                                    <option value="<?= $bg ?>"><?= $bg ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Occupation</label><input type="text" name="occupation" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Emergency</label><input type="text" name="emergency_contact" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Address</label><input type="text" name="address" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Allergies</label><textarea name="allergies" class="form-control" rows="2"></textarea></div>
                        <div class="col-12"><label class="form-label">Medical History</label><textarea name="medical_history" class="form-control" rows="2"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add" class="btn btn-primary"><i class="bi bi-save"></i> Save Patient</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>