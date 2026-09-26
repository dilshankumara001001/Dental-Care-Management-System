<?php
$page_title = 'Dashboard';
require_once 'includes/header.php';
require_once 'includes/functions.php';
require_once 'includes/sidebar.php';

$today = today();

// ====== 30 DASHBOARD FEATURES ======

// 1. Total Patients
$total_patients = countRows($conn, 'patients', "status='active'");

// 2. Active Patients (visited last 30 days)
$active_patients = countRows($conn, 'appointments', 
    "appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");

// 3. Today's Appointments
$today_appts = countRows($conn, 'appointments', "appointment_date = CURDATE()");

// 4. Pending Appointments
$pending_appts = countRows($conn, 'appointments', "status='pending'");

// 5. Completed Today
$completed_today = countRows($conn, 'appointments', 
    "status='completed' AND appointment_date = CURDATE()");

// 6. Chairs Total
$total_chairs = countRows($conn, 'chairs');

// 7. Chairs Occupied
$occupied_chairs = countRows($conn, 'chairs', "status='occupied'");

// 8. Chairs Available
$available_chairs = countRows($conn, 'chairs', "status='available'");

// 9. Today's Revenue
$today_revenue = sumColumn($conn, 'invoices', 'paid_amount', "invoice_date = CURDATE()");

// 10. This Month Revenue
$this_month_revenue = sumColumn($conn, 'invoices', 'paid_amount', 
    "MONTH(invoice_date) = MONTH(CURDATE()) AND YEAR(invoice_date) = YEAR(CURDATE())");

// 11. This Year Revenue
$this_year_revenue = sumColumn($conn, 'invoices', 'paid_amount', 
    "YEAR(invoice_date) = YEAR(CURDATE())");

// 12. Total Pending Payments
$pending_payments = sumColumn($conn, 'invoices', 'balance', "balance > 0");

// 13. Total Doctors
$total_doctors = countRows($conn, 'users', "role='doctor' AND status=1");

// 14. Total Treatments Done
$total_treatments = countRows($conn, 'treatments');

// 15. Average Treatment Cost
$avg_treatment = $conn->query("SELECT IFNULL(AVG(cost),0) a FROM treatments")->fetch_assoc()['a'];

// 16. Recent Patients
$recent_patients = $conn->query("SELECT * FROM patients ORDER BY id DESC LIMIT 5");

// 17. Today's Appointments List
$today_appt_list = $conn->query("SELECT a.*, p.full_name patient_name, u.full_name doctor_name, c.chair_name 
    FROM appointments a 
    LEFT JOIN patients p ON a.patient_id = p.id 
    LEFT JOIN users u ON a.doctor_id = u.id 
    LEFT JOIN chairs c ON a.chair_id = c.id 
    WHERE a.appointment_date = CURDATE() 
    ORDER BY a.appointment_time LIMIT 8");

// 18. All Chairs
$chairs = $conn->query("SELECT * FROM chairs ORDER BY id");

// 19. Recent Invoices
$recent_invoices = $conn->query("SELECT i.*, p.full_name patient_name 
    FROM invoices i LEFT JOIN patients p ON i.patient_id = p.id 
    ORDER BY i.id DESC LIMIT 5");

// 20. Top 5 Doctors by appointments
$top_doctors = $conn->query("SELECT u.full_name, COUNT(a.id) total 
    FROM users u LEFT JOIN appointments a ON u.id = a.doctor_id 
    WHERE u.role='doctor' 
    GROUP BY u.id ORDER BY total DESC LIMIT 5");

// 21. Upcoming Appointments (next 7 days)
$upcoming = $conn->query("SELECT a.*, p.full_name patient_name 
    FROM appointments a LEFT JOIN patients p ON a.patient_id = p.id 
    WHERE a.appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    AND a.status IN ('pending','confirmed')
    ORDER BY a.appointment_date, a.appointment_time LIMIT 5");

// 22. Overdue Invoices
$overdue = $conn->query("SELECT COUNT(*) c FROM invoices WHERE status='unpaid'");

// 23. Low Stock Items
$low_stock = $conn->query("SELECT * FROM inventory WHERE quantity <= min_stock LIMIT 5");

// 24. Today's Birthdays
$birthdays = $conn->query("SELECT * FROM patients 
    WHERE MONTH(dob) = MONTH(CURDATE()) AND DAY(dob) = DAY(CURDATE())");

// 25. Recent Activities
$activities = $conn->query("SELECT * FROM activity_log ORDER BY id DESC LIMIT 5");

// 26. Appointments by Status (chart data)
$appt_status_data = [];
$res = $conn->query("SELECT status, COUNT(*) c FROM appointments GROUP BY status");
while ($row = $res->fetch_assoc()) $appt_status_data[$row['status']] = $row['c'];

// 27. Monthly Revenue (last 6 months) - Chart data
$monthly_revenue = [];
$res = $conn->query("SELECT DATE_FORMAT(invoice_date, '%Y-%m') m, SUM(paid_amount) t 
    FROM invoices WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY m ORDER BY m");
while ($row = $res->fetch_assoc()) $monthly_revenue[$row['m']] = $row['t'];

// 28. New Patients This Month
$new_patients_month = countRows($conn, 'patients', 
    "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");

// 29. Chair Utilization %
$chair_utilization = $total_chairs > 0 ? round(($occupied_chairs / $total_chairs) * 100) : 0;

// 30. Unread Notifications
$unread_notif = countRows($conn, 'notifications', "is_read = 0");
?>

<!-- ===== WELCOME BANNER ===== -->
<div class="card p-4 mb-4" style="background: linear-gradient(135deg, #667eea, #764ba2); color:white;">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h3 class="mb-1">👋 Welcome back, <?= e($_SESSION['full_name']) ?>!</h3>
            <p class="mb-0" style="opacity:0.9;">
                <i class="bi bi-calendar"></i> <?= date('l, F d, Y') ?> | 
                <i class="bi bi-clock"></i> <span id="live-clock"><?= date('h:i A') ?></span>
            </p>
        </div>
        <div class="text-end">
            <div class="fs-6">Today's Revenue</div>
            <div class="fs-2 fw-bold"><?= money($today_revenue) ?></div>
        </div>
    </div>
</div>

<!-- ===== 1-4: MAIN STAT CARDS ===== -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card p-3 bg-primary text-white">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small>Total Patients</small>
                    <h2 class="mb-0"><?= $total_patients ?></h2>
                    <small class="text-white-50">+<?= $new_patients_month ?> this month</small>
                </div>
                <i class="bi bi-people-fill" style="font-size: 40px; opacity: 0.4;"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card p-3 bg-success text-white">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small>Today's Appointments</small>
                    <h2 class="mb-0"><?= $today_appts ?></h2>
                    <small class="text-white-50"><?= $completed_today ?> completed</small>
                </div>
                <i class="bi bi-calendar-check-fill" style="font-size: 40px; opacity: 0.4;"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card p-3 bg-warning text-white">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small>Chairs Occupied</small>
                    <h2 class="mb-0"><?= $occupied_chairs ?>/<?= $total_chairs ?></h2>
                    <small class="text-white-50"><?= $chair_utilization ?>% utilization</small>
                </div>
                <i class="bi bi-grid-3x3-gap-fill" style="font-size: 40px; opacity: 0.4;"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card p-3 bg-danger text-white">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small>Pending Payments</small>
                    <h2 class="mb-0" style="font-size: 22px;"><?= money($pending_payments) ?></h2>
                    <small class="text-white-50"><?= $overdue->fetch_assoc()['c'] ?> unpaid invoices</small>
                </div>
                <i class="bi bi-exclamation-triangle-fill" style="font-size: 40px; opacity: 0.4;"></i>
            </div>
        </div>
    </div>
</div>

<!-- ===== 5-8: SECONDARY STATS ===== -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card p-3 text-center">
            <i class="bi bi-currency-dollar text-primary" style="font-size: 28px;"></i>
            <small class="text-muted">This Month</small>
            <div class="fw-bold"><?= money($this_month_revenue) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card p-3 text-center">
            <i class="bi bi-graph-up text-success" style="font-size: 28px;"></i>
            <small class="text-muted">This Year</small>
            <div class="fw-bold"><?= money($this_year_revenue) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card p-3 text-center">
            <i class="bi bi-person-badge text-info" style="font-size: 28px;"></i>
            <small class="text-muted">Active Doctors</small>
            <div class="fw-bold"><?= $total_doctors ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card p-3 text-center">
            <i class="bi bi-clipboard-pulse text-warning" style="font-size: 28px;"></i>
            <small class="text-muted">Total Treatments</small>
            <div class="fw-bold"><?= $total_treatments ?></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- ===== FEATURE 9: LIVE CHAIR STATUS ===== -->
    <div class="col-lg-8">
        <div class="card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0"><i class="bi bi-grid-3x3-gap-fill"></i> Live Chair Status</h5>
                <a href="<?= BASE_URL ?>modules/chairs/index.php" class="btn btn-sm btn-outline-primary">
                    Manage <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <hr>
            <div class="row g-2">
                <?php while ($c = $chairs->fetch_assoc()): ?>
                    <div class="col-md-2 col-sm-3 col-4">
                        <div class="chair-box chair-<?= $c['status'] ?>" style="padding: 10px;">
                            <i class="bi bi-<?= $c['status']=='available' ? 'check-circle' : ($c['status']=='occupied' ? 'x-circle' : 'tools') ?>"></i>
                            <div style="font-size: 12px;"><?= e($c['chair_name']) ?></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- ===== FEATURE 10: QUICK ACTIONS ===== -->
    <div class="col-lg-4">
        <div class="card p-3 h-100">
            <h5 class="mb-3"><i class="bi bi-lightning-charge-fill text-warning"></i> Quick Actions</h5>
            <div class="d-grid gap-2">
                <a href="<?= BASE_URL ?>modules/patients/index.php?action=add" class="btn btn-primary">
                    <i class="bi bi-person-plus"></i> New Patient
                </a>
                <a href="<?= BASE_URL ?>modules/appointments/index.php?action=add" class="btn btn-success">
                    <i class="bi bi-calendar-plus"></i> Book Appointment
                </a>
                <a href="<?= BASE_URL ?>modules/billing/index.php?action=add" class="btn btn-warning">
                    <i class="bi bi-receipt"></i> Create Invoice
                </a>
                <a href="<?= BASE_URL ?>modules/patients/index.php" class="btn btn-info text-white">
                    <i class="bi bi-search"></i> Find Patient
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- ===== FEATURE 11: TODAY'S APPOINTMENTS ===== -->
    <div class="col-lg-6">
        <div class="card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0"><i class="bi bi-calendar-event text-primary"></i> Today's Schedule</h5>
                <span class="badge bg-primary"><?= $today_appt_list->num_rows ?> items</span>
            </div>
            <hr>
            <?php if ($today_appt_list->num_rows == 0): ?>
                <p class="text-muted text-center py-3">No appointments today 🎉</p>
            <?php else: ?>
                <?php while ($a = $today_appt_list->fetch_assoc()): ?>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>
                            <div class="fw-bold"><?= e($a['patient_name']) ?></div>
                            <small class="text-muted">
                                <i class="bi bi-clock"></i> <?= date('h:i A', strtotime($a['appointment_time'])) ?>
                                | Dr. <?= e($a['doctor_name']) ?>
                                | <?= e($a['chair_name'] ?? '-') ?>
                            </small>
                        </div>
                        <span class="badge bg-<?= $a['status']=='completed' ? 'success' : ($a['status']=='pending' ? 'warning' : 'info') ?>">
                            <?= ucfirst($a['status']) ?>
                        </span>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== FEATURE 12: RECENT PATIENTS ===== -->
    <div class="col-lg-6">
        <div class="card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0"><i class="bi bi-clock-history text-info"></i> Recent Patients</h5>
                <a href="<?= BASE_URL ?>modules/patients/index.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <hr>
            <?php while ($p = $recent_patients->fetch_assoc()): ?>
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <div>
                        <div class="fw-bold"><?= e($p['full_name']) ?></div>
                        <small class="text-muted">
                            <i class="bi bi-telephone"></i> <?= e($p['phone']) ?> 
                            | <?= ucfirst($p['gender']) ?>
                        </small>
                    </div>
                    <span class="badge bg-light text-dark"><?= e($p['patient_code']) ?></span>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- ===== FEATURE 13: REVENUE CHART ===== -->
    <div class="col-lg-8">
        <div class="card p-3">
            <h5><i class="bi bi-graph-up-arrow text-success"></i> Monthly Revenue (Last 6 Months)</h5>
            <hr>
            <canvas id="revenueChart" height="90"></canvas>
        </div>
    </div>

    <!-- ===== FEATURE 14: APPOINTMENTS PIE CHART ===== -->
    <div class="col-lg-4">
        <div class="card p-3">
            <h5><i class="bi bi-pie-chart-fill text-warning"></i> Appointments Status</h5>
            <hr>
            <canvas id="apptChart"></canvas>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- ===== FEATURE 15: TOP DOCTORS ===== -->
    <div class="col-lg-6">
        <div class="card p-3">
            <h5><i class="bi bi-trophy-fill text-warning"></i> Top Doctors</h5>
            <hr>
            <?php while ($d = $top_doctors->fetch_assoc()): ?>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <i class="bi bi-person-circle"></i> <?= e($d['full_name']) ?>
                    </div>
                    <span class="badge bg-primary"><?= $d['total'] ?> appointments</span>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- ===== FEATURE 16: UPCOMING ===== -->
    <div class="col-lg-6">
        <div class="card p-3">
            <h5><i class="bi bi-calendar-week text-primary"></i> Upcoming (7 days)</h5>
            <hr>
            <?php if ($upcoming->num_rows == 0): ?>
                <p class="text-muted text-center">No upcoming appointments</p>
            <?php else: ?>
                <?php while ($u = $upcoming->fetch_assoc()): ?>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <div>
                            <b><?= e($u['patient_name']) ?></b><br>
                            <small class="text-muted"><?= date('M d', strtotime($u['appointment_date'])) ?> 
                            @ <?= date('h:i A', strtotime($u['appointment_time'])) ?></small>
                        </div>
                        <span class="badge bg-<?= $u['priority']=='high' ? 'danger' : 'secondary' ?>">
                            <?= ucfirst($u['priority']) ?>
                        </span>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- ===== FEATURE 17: RECENT INVOICES ===== -->
    <div class="col-lg-6">
        <div class="card p-3">
            <h5><i class="bi bi-receipt text-success"></i> Recent Invoices</h5>
            <hr>
            <?php while ($i = $recent_invoices->fetch_assoc()): ?>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <div>
                        <b><?= e($i['invoice_no']) ?></b><br>
                        <small class="text-muted"><?= e($i['patient_name']) ?></small>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold"><?= money($i['total_amount']) ?></div>
                        <span class="badge bg-<?= $i['status']=='paid' ? 'success' : ($i['status']=='partial' ? 'warning' : 'danger') ?>">
                            <?= ucfirst($i['status']) ?>
                        </span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- ===== FEATURE 18: BIRTHDAYS + LOW STOCK ===== -->
    <div class="col-lg-6">
        <div class="card p-3 mb-3">
            <h5><i class="bi bi-gift-fill text-danger"></i> Today's Birthdays</h5>
            <hr>
            <?php if ($birthdays->num_rows == 0): ?>
                <p class="text-muted mb-0">No birthdays today</p>
            <?php else: ?>
                <?php while ($b = $birthdays->fetch_assoc()): ?>
                    <div class="py-1">🎂 <b><?= e($b['full_name']) ?></b> - <?= e($b['phone']) ?></div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <div class="card p-3">
            <h5><i class="bi bi-exclamation-triangle-fill text-warning"></i> Low Stock Alerts</h5>
            <hr>
            <?php if ($low_stock->num_rows == 0): ?>
                <p class="text-muted mb-0">All items are in stock ✅</p>
            <?php else: ?>
                <?php while ($s = $low_stock->fetch_assoc()): ?>
                    <div class="d-flex justify-content-between py-1">
                        <span><?= e($s['item_name']) ?></span>
                        <span class="badge bg-danger"><?= $s['quantity'] ?> <?= e($s['unit']) ?></span>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ===== FEATURE 19: RECENT ACTIVITY ===== -->
<div class="card p-3 mb-3">
    <h5><i class="bi bi-activity text-info"></i> Recent Activity</h5>
    <hr>
    <?php if ($activities->num_rows == 0): ?>
        <p class="text-muted mb-0">No activity yet</p>
    <?php else: ?>
        <?php while ($a = $activities->fetch_assoc()): ?>
            <div class="d-flex justify-content-between py-1 border-bottom">
                <div>
                    <span class="badge bg-light text-dark"><?= e($a['action']) ?></span>
                    <?= e($a['description']) ?>
                </div>
                <small class="text-muted"><?= timeAgo($a['created_at']) ?></small>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// FEATURE: Monthly Revenue Chart
const revCtx = document.getElementById('revenueChart');
new Chart(revCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_keys($monthly_revenue)) ?>,
        datasets: [{
            label: 'Revenue (Rs.)',
            data: <?= json_encode(array_values($monthly_revenue)) ?>,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102,126,234,0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});

// FEATURE: Appointments Pie Chart
const apptCtx = document.getElementById('apptChart');
new Chart(apptCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($appt_status_data)) ?>,
        datasets: [{
            data: <?= json_encode(array_values($appt_status_data)) ?>,
            backgroundColor: ['#ffc107','#0dcaf0','#198754','#dc3545']
        }]
    },
    options: { responsive: true }
});

// FEATURE: Live Clock
setInterval(() => {
    const now = new Date();
    document.getElementById('live-clock').innerText = 
        now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}, 1000);
</script>

<?php require_once 'includes/footer.php'; ?>