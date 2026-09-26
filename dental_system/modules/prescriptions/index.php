<?php
$page_title = 'Prescriptions';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$msg = '';

// Create prescription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $no = 'RX-' . date('Ymd') . '-' . rand(100,999);
    $stmt = $conn->prepare("INSERT INTO prescriptions 
        (prescription_no, patient_id, doctor_id, diagnosis, medicines, instructions, next_visit, prescription_date) 
        VALUES (?,?,?,?,?,?,?,?)");
    $next = !empty($_POST['next_visit']) ? $_POST['next_visit'] : null;
    $stmt->bind_param("siisssss", 
        $no, $_POST['patient_id'], $_POST['doctor_id'],
        $_POST['diagnosis'], $_POST['medicines'], $_POST['instructions'],
        $next, $_POST['prescription_date']
    );
    if ($stmt->execute()) {
        $msg = '<div class="alert alert-success">Prescription created! ' . $no . '</div>';
    }
}

if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM prescriptions WHERE id=" . (int)$_GET['delete']);
    $msg = '<div class="alert alert-success">Prescription deleted.</div>';
}

$prescriptions = $conn->query("SELECT p.*, pt.full_name patient_name, pt.patient_code, u.full_name doctor_name 
    FROM prescriptions p 
    LEFT JOIN patients pt ON p.patient_id = pt.id 
    LEFT JOIN users u ON p.doctor_id = u.id 
    ORDER BY p.id DESC");

$patients = $conn->query("SELECT id, full_name, patient_code FROM patients ORDER BY full_name");
$doctors  = $conn->query("SELECT id, full_name FROM users WHERE role='doctor' ORDER BY full_name");
?>

<?= $msg ?>

<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-file-medical-fill"></i> Prescriptions</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-circle-fill"></i> New Prescription
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Rx #</th>
                    <th>Date</th>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Diagnosis</th>
                    <th>Next Visit</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($prescriptions->num_rows === 0): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No prescriptions yet</td></tr>
                <?php endif; ?>
                <?php while ($p = $prescriptions->fetch_assoc()): ?>
                    <tr>
                        <td><b><?= e($p['prescription_no']) ?></b></td>
                        <td><?= date('M d, Y', strtotime($p['prescription_date'])) ?></td>
                        <td>
                            <b><?= e($p['patient_name']) ?></b><br>
                            <small class="text-muted"><?= e($p['patient_code']) ?></small>
                        </td>
                        <td>Dr. <?= e($p['doctor_name']) ?></td>
                        <td><?= e(substr($p['diagnosis'], 0, 40)) ?></td>
                        <td><?= $p['next_visit'] ? date('M d, Y', strtotime($p['next_visit'])) : '-' ?></td>
                        <td>
                            <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#viewModal<?= $p['id'] ?>">
                                <i class="bi bi-eye"></i>
                            </button>
                            <a href="print.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-sm btn-success">
                                <i class="bi bi-printer"></i>
                            </a>
                            <a href="?delete=<?= $p['id'] ?>" data-name="prescription <?= e($p['prescription_no']) ?>" class="btn btn-sm btn-danger">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>

                    <div class="modal fade" id="viewModal<?= $p['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h5><i class="bi bi-file-medical"></i> <?= e($p['prescription_no']) ?></h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <b>Patient:</b> <?= e($p['patient_name']) ?><br>
                                            <b>Code:</b> <?= e($p['patient_code']) ?>
                                        </div>
                                        <div class="col-md-6 text-md-end">
                                            <b>Doctor:</b> Dr. <?= e($p['doctor_name']) ?><br>
                                            <b>Date:</b> <?= date('M d, Y', strtotime($p['prescription_date'])) ?>
                                        </div>
                                    </div>
                                    <hr>
                                    <h6>Diagnosis</h6>
                                    <p><?= nl2br(e($p['diagnosis'])) ?></p>
                                    <h6>Medicines (Rx)</h6>
                                    <pre class="bg-light p-3 rounded"><?= e($p['medicines']) ?></pre>
                                    <h6>Instructions</h6>
                                    <p><?= nl2br(e($p['instructions'])) ?></p>
                                    <?php if ($p['next_visit']): ?>
                                        <div class="alert alert-info">
                                            <i class="bi bi-calendar"></i> Next Visit: <b><?= date('F d, Y', strtotime($p['next_visit'])) ?></b>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5><i class="bi bi-file-medical-fill"></i> New Prescription</h5>
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
                                while ($pt = $patients->fetch_assoc()): ?>
                                    <option value="<?= $pt['id'] ?>"><?= e($pt['patient_code'].' - '.$pt['full_name']) ?></option>
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
                        <div class="col-md-6">
                            <label class="form-label">Date *</label>
                            <input type="date" name="prescription_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Next Visit</label>
                            <input type="date" name="next_visit" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Diagnosis</label>
                            <textarea name="diagnosis" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Medicines (Rx) *</label>
                            <textarea name="medicines" class="form-control" rows="5" required
                                placeholder="1. Amoxicillin 500mg - 3x daily - 5 days&#10;2. Paracetamol 500mg - 2x daily - 3 days"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Instructions</label>
                            <textarea name="instructions" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>