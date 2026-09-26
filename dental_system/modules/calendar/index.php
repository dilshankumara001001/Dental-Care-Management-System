<?php
$page_title = 'Calendar';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$month = (int)($_GET['m'] ?? date('n'));
$year  = (int)($_GET['y'] ?? date('Y'));

// Build events array
$events = [];
$res = $conn->query("SELECT a.*, p.full_name patient_name, u.full_name doctor_name 
    FROM appointments a 
    LEFT JOIN patients p ON a.patient_id = p.id 
    LEFT JOIN users u ON a.doctor_id = u.id 
    WHERE MONTH(a.appointment_date)=$month AND YEAR(a.appointment_date)=$year");
while ($row = $res->fetch_assoc()) {
    $d = $row['appointment_date'];
    if (!isset($events[$d])) $events[$d] = [];
    $events[$d][] = [
        'time' => date('h:i A', strtotime($row['appointment_time'])),
        'patient' => $row['patient_name'],
        'status' => $row['status'],
        'treatment' => $row['treatment']
    ];
}
?>

<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-calendar-month"></i> Appointment Calendar</h4>
    <div class="d-flex gap-2 align-items-center">
        <a href="?m=<?= $month-1 < 1 ? 12 : $month-1 ?>&y=<?= $month-1 < 1 ? $year-1 : $year ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-chevron-left"></i> Prev
        </a>
        <h5 class="mb-0"><?= date('F Y', strtotime("$year-$month-01")) ?></h5>
        <a href="?m=<?= $month+1 > 12 ? 1 : $month+1 ?>&y=<?= $month+1 > 12 ? $year+1 : $year ?>" class="btn btn-outline-primary btn-sm">
            Next <i class="bi bi-chevron-right"></i>
        </a>
        <a href="?" class="btn btn-primary btn-sm">Today</a>
    </div>
</div>

<div class="card p-3">
    <div id="calendar-container"></div>
</div>

<div class="row g-3 mt-3">
    <div class="col-md-8">
        <div class="card p-3">
            <h6><i class="bi bi-list"></i> This Month Summary</h6>
            <hr>
            <?php 
            $total = 0;
            $by_status = [];
            foreach ($events as $dayEvents) {
                foreach ($dayEvents as $e) {
                    $total++;
                    $by_status[$e['status']] = ($by_status[$e['status']] ?? 0) + 1;
                }
            }
            ?>
            <div class="row text-center">
                <div class="col-3">
                    <h4 class="mb-0"><?= $total ?></h4>
                    <small class="text-muted">Total</small>
                </div>
                <?php foreach (['pending','confirmed','completed','cancelled'] as $s): ?>
                    <div class="col-3">
                        <h4 class="mb-0 text-<?= $s=='completed'?'success':($s=='pending'?'warning':($s=='cancelled'?'danger':'info')) ?>">
                            <?= $by_status[$s] ?? 0 ?>
                        </h4>
                        <small class="text-muted"><?= ucfirst($s) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3">
            <h6><i class="bi bi-info-circle"></i> Legend</h6>
            <hr>
            <div><span class="badge event-pending">Pending</span></div>
            <div class="mt-1"><span class="badge event-confirmed">Confirmed</span></div>
            <div class="mt-1"><span class="badge event-completed">Completed</span></div>
            <div class="mt-1"><span class="badge event-cancelled">Cancelled</span></div>
        </div>
    </div>
</div>

<script>
const events = <?= json_encode($events) ?>;
initCalendar('#calendar-container', <?= $month-1 ?>, <?= $year ?>, events);
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>