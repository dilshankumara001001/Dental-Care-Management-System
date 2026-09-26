<?php
$page_title = 'Dental Chart';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$patient_id = (int)($_GET['patient'] ?? 0);
$patients = $conn->query("SELECT id, full_name, patient_code FROM patients ORDER BY full_name");

// Update tooth
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_tooth'])) {
    $pid = (int)$_POST['patient_id'];
    $tooth = $_POST['tooth_number'];
    $cond = $_POST['condition'];
    $notes = $_POST['notes'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO dental_chart (patient_id, tooth_number, tooth_condition, notes) 
        VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE tooth_condition=?, notes=?, updated_at=NOW()");
    $stmt->bind_param("isssss", $pid, $tooth, $cond, $notes, $cond, $notes);
    $stmt->execute();
}

// Get chart data
$chart_data = [];
if ($patient_id) {
    $res = $conn->query("SELECT * FROM dental_chart WHERE patient_id = $patient_id");
    while ($row = $res->fetch_assoc()) {
        $chart_data[$row['tooth_number']] = $row;
    }
}

// FDI tooth numbering (Upper: 18-11, 21-28; Lower: 48-41, 31-38)
$upper_right = ['18','17','16','15','14','13','12','11'];
$upper_left  = ['21','22','23','24','25','26','27','28'];
$lower_right = ['48','47','46','45','44','43','42','41'];
$lower_left  = ['31','32','33','34','35','36','37','38'];

$conditions = [
    'healthy'    => ['label'=>'Healthy',    'class'=>'healthy',    'color'=>'#d4edda'],
    'cavity'     => ['label'=>'Cavity',     'class'=>'cavity',     'color'=>'#f8d7da'],
    'filled'     => ['label'=>'Filled',     'class'=>'filled',     'color'=>'#cfe2ff'],
    'crown'      => ['label'=>'Crown',      'class'=>'crown',      'color'=>'#fff3cd'],
    'root_canal' => ['label'=>'Root Canal', 'class'=>'root_canal', 'color'=>'#e7d4f8'],
    'missing'    => ['label'=>'Missing',    'class'=>'missing',    'color'=>'#e2e3e5'],
    'implant'    => ['label'=>'Implant',    'class'=>'implant',    'color'=>'#d1ecf1'],
    'extraction' => ['label'=>'Extraction', 'class'=>'extraction', 'color'=>'#f5c2c7']
];
?>

<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-grid-3x3"></i> Interactive Dental Chart</h4>
    <form method="GET" class="d-flex gap-2">
        <select name="patient" class="form-select" onchange="this.form.submit()" style="min-width:250px;">
            <option value="">-- Select Patient --</option>
            <?php while ($p = $patients->fetch_assoc()): ?>
                <option value="<?= $p['id'] ?>" <?= $patient_id==$p['id']?'selected':'' ?>>
                    <?= e($p['patient_code'] . ' - ' . $p['full_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </form>
</div>

<?php if (!$patient_id): ?>
    <div class="card p-5 text-center">
        <i class="bi bi-person-bounding-box text-muted" style="font-size: 80px;"></i>
        <h4 class="mt-3">Select a Patient</h4>
        <p class="text-muted">Choose a patient from the dropdown above to view/edit their dental chart.</p>
    </div>
<?php else: ?>

<!-- Legend -->
<div class="card p-3 mb-3">
    <div class="d-flex flex-wrap gap-3">
        <?php foreach ($conditions as $key => $c): ?>
            <div class="d-flex align-items-center gap-2">
                <div style="width:20px;height:20px;border-radius:4px;background:<?= $c['color'] ?>;border:2px solid #ccc;"></div>
                <small><?= $c['label'] ?></small>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Dental Chart -->
<div class="card p-3 mb-3">
    <h6 class="text-center mb-3"><i class="bi bi-arrow-up"></i> UPPER JAW</h6>
    <div class="tooth-chart">
        <?php foreach ($upper_right as $t): 
            $c = $chart_data[$t]['tooth_condition'] ?? 'healthy';
        ?>
            <div class="tooth <?= $c ?>" onclick="openToothModal('<?= $t ?>', '<?= $c ?>', '<?= e($chart_data[$t]['notes'] ?? '') ?>')">
                <div style="font-size:1.1rem;">🦷</div>
                <div class="tooth-label"><?= $t ?></div>
            </div>
        <?php endforeach; ?>
        <?php foreach ($upper_left as $t): 
            $c = $chart_data[$t]['tooth_condition'] ?? 'healthy';
        ?>
            <div class="tooth <?= $c ?>" onclick="openToothModal('<?= $t ?>', '<?= $c ?>', '<?= e($chart_data[$t]['notes'] ?? '') ?>')">
                <div style="font-size:1.1rem;">🦷</div>
                <div class="tooth-label"><?= $t ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <hr>
    
    <h6 class="text-center mb-3"><i class="bi bi-arrow-down"></i> LOWER JAW</h6>
    <div class="tooth-chart">
        <?php foreach ($lower_right as $t): 
            $c = $chart_data[$t]['tooth_condition'] ?? 'healthy';
        ?>
            <div class="tooth <?= $c ?>" onclick="openToothModal('<?= $t ?>', '<?= $c ?>', '<?= e($chart_data[$t]['notes'] ?? '') ?>')">
                <div style="font-size:1.1rem;">🦷</div>
                <div class="tooth-label"><?= $t ?></div>
            </div>
        <?php endforeach; ?>
        <?php foreach ($lower_left as $t): 
            $c = $chart_data[$t]['tooth_condition'] ?? 'healthy';
        ?>
            <div class="tooth <?= $c ?>" onclick="openToothModal('<?= $t ?>', '<?= $c ?>', '<?= e($chart_data[$t]['notes'] ?? '') ?>')">
                <div style="font-size:1.1rem;">🦷</div>
                <div class="tooth-label"><?= $t ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <p class="text-center text-muted mt-3 mb-0">
        <i class="bi bi-info-circle"></i> Click on any tooth to update its condition
    </p>
</div>

<!-- Summary -->
<div class="row g-3">
    <?php 
    $summary = [];
    foreach ($chart_data as $c) $summary[$c['tooth_condition']] = ($summary[$c['tooth_condition']] ?? 0) + 1;
    ?>
    <?php foreach ($conditions as $key => $c): if (($summary[$key] ?? 0) == 0) continue; ?>
        <div class="col-md-3 col-6">
            <div class="card p-2 text-center">
                <small class="text-muted"><?= $c['label'] ?></small>
                <h4 class="mb-0"><?= $summary[$key] ?></h4>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Tooth Modal -->
<div class="modal fade" id="toothModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                <input type="hidden" name="tooth_number" id="modal_tooth">
                <div class="modal-header bg-primary text-white">
                    <h5><i class="bi bi-tooth"></i> Tooth #<span id="modal_tooth_label"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-bold">Condition</label>
                    <div class="row g-2 mb-3">
                        <?php foreach ($conditions as $key => $c): ?>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="condition" id="cond_<?= $key ?>" value="<?= $key ?>" required>
                                <label class="btn w-100 text-start" for="cond_<?= $key ?>" style="border:2px solid #ccc;">
                                    <span style="display:inline-block;width:16px;height:16px;background:<?= $c['color'] ?>;border-radius:4px;margin-right:8px;vertical-align:middle;"></span>
                                    <?= $c['label'] ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" id="modal_notes" class="form-control" rows="3" 
                            placeholder="Additional observations..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_tooth" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openToothModal(tooth, condition, notes) {
    document.getElementById('modal_tooth').value = tooth;
    document.getElementById('modal_tooth_label').innerText = tooth;
    document.getElementById('modal_notes').value = notes;
    document.querySelectorAll('input[name="condition"]').forEach(r => r.checked = false);
    const r = document.getElementById('cond_' + condition);
    if (r) r.checked = true;
    new bootstrap.Modal(document.getElementById('toothModal')).show();
}
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>