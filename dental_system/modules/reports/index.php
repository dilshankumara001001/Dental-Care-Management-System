<?php
$page_title = 'Reports & Analytics';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// ============================================
// FILTERS (FEATURES 1-4)
// ============================================
$date_from = $_GET['from'] ?? date('Y-m-01');
$date_to   = $_GET['to']   ?? date('Y-m-d');
$report_type = $_GET['type'] ?? 'overview';

// ============================================
// FEATURE 5-10: OVERVIEW STATS
// ============================================
$total_patients    = countRows($conn, 'patients');
$active_patients   = countRows($conn, 'patients', "status='active'");
$total_appts       = countRows($conn, 'appointments', "appointment_date BETWEEN '$date_from' AND '$date_to'");
$completed_appts   = countRows($conn, 'appointments', "status='completed' AND appointment_date BETWEEN '$date_from' AND '$date_to'");
$cancelled_appts   = countRows($conn, 'appointments', "status='cancelled' AND appointment_date BETWEEN '$date_from' AND '$date_to'");
$total_invoices    = countRows($conn, 'invoices', "invoice_date BETWEEN '$date_from' AND '$date_to'");

// FEATURE 11-15: REVENUE STATS
$total_revenue  = sumColumn($conn, 'invoices', 'paid_amount', "invoice_date BETWEEN '$date_from' AND '$date_to'");
$total_pending  = sumColumn($conn, 'invoices', 'balance', "balance > 0 AND invoice_date BETWEEN '$date_from' AND '$date_to'");
$total_discount = sumColumn($conn, 'invoices', 'discount', "invoice_date BETWEEN '$date_from' AND '$date_to'");
$total_tax      = sumColumn($conn, 'invoices', 'tax', "invoice_date BETWEEN '$date_from' AND '$date_to'");
$avg_invoice    = $total_invoices > 0 ? $total_revenue / $total_invoices : 0;

// FEATURE 16: Today/Week/Month/Year Revenue
$rev_today = sumColumn($conn, 'invoices', 'paid_amount', "invoice_date = CURDATE()");
$rev_week  = sumColumn($conn, 'invoices', 'paid_amount', "YEARWEEK(invoice_date)=YEARWEEK(CURDATE())");
$rev_month = sumColumn($conn, 'invoices', 'paid_amount', "MONTH(invoice_date)=MONTH(CURDATE()) AND YEAR(invoice_date)=YEAR(CURDATE())");
$rev_year  = sumColumn($conn, 'invoices', 'paid_amount', "YEAR(invoice_date)=YEAR(CURDATE())");

// ============================================
// FEATURE 17: DAILY REVENUE (chart)
// ============================================
$daily_revenue = [];
$res = $conn->query("SELECT invoice_date d, SUM(paid_amount) t FROM invoices 
    WHERE invoice_date BETWEEN '$date_from' AND '$date_to' 
    GROUP BY invoice_date ORDER BY invoice_date");
while ($row = $res->fetch_assoc()) $daily_revenue[$row['d']] = $row['t'];

// ============================================
// FEATURE 18: MONTHLY REVENUE (12 months)
// ============================================
$monthly_revenue = [];
$res = $conn->query("SELECT DATE_FORMAT(invoice_date, '%Y-%m') m, SUM(paid_amount) t 
    FROM invoices WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY m ORDER BY m");
while ($row = $res->fetch_assoc()) $monthly_revenue[$row['m']] = $row['t'];

// ============================================
// FEATURE 19: APPOINTMENTS BY STATUS
// ============================================
$appt_status = [];
$res = $conn->query("SELECT status, COUNT(*) c FROM appointments 
    WHERE appointment_date BETWEEN '$date_from' AND '$date_to' 
    GROUP BY status");
while ($row = $res->fetch_assoc()) $appt_status[$row['status']] = $row['c'];

// ============================================
// FEATURE 20: DOCTOR-WISE APPOINTMENTS
// ============================================
$doctor_stats = $conn->query("SELECT u.id, u.full_name, 
    COUNT(a.id) total_appts,
    SUM(CASE WHEN a.status='completed' THEN 1 ELSE 0 END) completed,
    SUM(CASE WHEN a.status='cancelled' THEN 1 ELSE 0 END) cancelled,
    (SELECT IFNULL(SUM(t.cost),0) FROM treatments t WHERE t.doctor_id=u.id) revenue
    FROM users u LEFT JOIN appointments a ON u.id=a.doctor_id 
        AND a.appointment_date BETWEEN '$date_from' AND '$date_to'
    WHERE u.role='doctor' 
    GROUP BY u.id ORDER BY total_appts DESC");

// ============================================
// FEATURE 21: CHAIR UTILIZATION REPORT
// ============================================
$chair_stats = $conn->query("SELECT c.id, c.chair_name, c.status,
    COUNT(a.id) total_uses,
    SUM(CASE WHEN a.appointment_date = CURDATE() THEN 1 ELSE 0 END) today_uses
    FROM chairs c LEFT JOIN appointments a ON c.id=a.chair_id 
        AND a.appointment_date BETWEEN '$date_from' AND '$date_to'
    GROUP BY c.id ORDER BY total_uses DESC");

// ============================================
// FEATURE 22: TOP TREATMENTS
// ============================================
$top_treatments = $conn->query("SELECT treatment, COUNT(*) c 
    FROM appointments 
    WHERE treatment IS NOT NULL AND treatment != '' 
    AND appointment_date BETWEEN '$date_from' AND '$date_to'
    GROUP BY treatment ORDER BY c DESC LIMIT 10");

// ============================================
// FEATURE 23: PAYMENT METHOD BREAKDOWN
// ============================================
$payment_methods = $conn->query("SELECT payment_method, COUNT(*) c, SUM(paid_amount) t 
    FROM invoices 
    WHERE invoice_date BETWEEN '$date_from' AND '$date_to'
    GROUP BY payment_method");

// ============================================
// FEATURE 24: PATIENT DEMOGRAPHICS
// ============================================
$gender_stats = $conn->query("SELECT gender, COUNT(*) c FROM patients GROUP BY gender");
$blood_stats  = $conn->query("SELECT blood_group, COUNT(*) c FROM patients 
    WHERE blood_group IS NOT NULL AND blood_group != '' 
    GROUP BY blood_group ORDER BY c DESC");

// ============================================
// FEATURE 25: AGE GROUPS
// ============================================
$age_groups = $conn->query("SELECT 
    SUM(CASE WHEN age < 18 THEN 1 ELSE 0 END) child,
    SUM(CASE WHEN age BETWEEN 18 AND 35 THEN 1 ELSE 0 END) young,
    SUM(CASE WHEN age BETWEEN 36 AND 55 THEN 1 ELSE 0 END) adult,
    SUM(CASE WHEN age > 55 THEN 1 ELSE 0 END) senior
    FROM patients WHERE age IS NOT NULL")->fetch_assoc();

// ============================================
// FEATURE 26: NEW PATIENTS TREND
// ============================================
$new_patients_trend = [];
$res = $conn->query("SELECT DATE(created_at) d, COUNT(*) c FROM patients 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
    GROUP BY d ORDER BY d");
while ($row = $res->fetch_assoc()) $new_patients_trend[$row['d']] = $row['c'];

// ============================================
// FEATURE 27: TOP SPENDING PATIENTS
// ============================================
$top_patients = $conn->query("SELECT p.full_name, p.patient_code, p.phone,
    COUNT(i.id) total_invoices, IFNULL(SUM(i.paid_amount),0) total_paid
    FROM patients p LEFT JOIN invoices i ON p.id=i.patient_id 
    GROUP BY p.id HAVING total_paid > 0 
    ORDER BY total_paid DESC LIMIT 10");

// ============================================
// FEATURE 28: OUTSTANDING BALANCES
// ============================================
$outstanding = $conn->query("SELECT p.full_name, p.patient_code, p.phone,
    COUNT(i.id) invoices, SUM(i.balance) total_due
    FROM patients p LEFT JOIN invoices i ON p.id=i.patient_id 
    WHERE i.balance > 0 
    GROUP BY p.id ORDER BY total_due DESC LIMIT 10");

// ============================================
// FEATURE 29: HOURLY APPOINTMENT DISTRIBUTION
// ============================================
$hourly = [];
$res = $conn->query("SELECT HOUR(appointment_time) h, COUNT(*) c 
    FROM appointments 
    WHERE appointment_date BETWEEN '$date_from' AND '$date_to'
    GROUP BY h ORDER BY h");
while ($row = $res->fetch_assoc()) $hourly[(int)$row['h']] = $row['c'];

// ============================================
// FEATURE 30: PROFITABILITY / KPI
// ============================================
$completion_rate = $total_appts > 0 ? round(($completed_appts / $total_appts) * 100, 1) : 0;
$cancel_rate     = $total_appts > 0 ? round(($cancelled_appts / $total_appts) * 100, 1) : 0;
$collection_rate = ($total_revenue + $total_pending) > 0 
    ? round(($total_revenue / ($total_revenue + $total_pending)) * 100, 1) : 0;
?>

<!-- ============================================
     HEADER
============================================ -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-bar-chart-line-fill"></i> Reports & Analytics</h4>
        <small class="text-muted">Business intelligence dashboard</small>
    </div>
    <div>
        <button class="btn btn-outline-success" onclick="window.print()">
            <i class="bi bi-printer"></i> Print Report
        </button>
        <button class="btn btn-outline-primary" onclick="exportCSV()">
            <i class="bi bi-file-earmark-excel"></i> Export CSV
        </button>
    </div>
</div>

<!-- ============================================
     FEATURES 1-4: DATE RANGE FILTER
============================================ -->
<div class="card p-3 mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small"><i class="bi bi-calendar"></i> From Date</label>
            <input type="date" name="from" class="form-control" value="<?= e($date_from) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small"><i class="bi bi-calendar"></i> To Date</label>
            <input type="date" name="to" class="form-control" value="<?= e($date_to) ?>">
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary w-100">
                <i class="bi bi-funnel"></i> Generate Report
            </button>
        </div>
        <div class="col-md-3">
            <div class="btn-group w-100">
                <a href="?from=<?= date('Y-m-d') ?>&to=<?= date('Y-m-d') ?>" class="btn btn-sm btn-outline-primary">Today</a>
                <a href="?from=<?= date('Y-m-d', strtotime('-7 days')) ?>&to=<?= date('Y-m-d') ?>" class="btn btn-sm btn-outline-primary">7 Days</a>
                <a href="?from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-t') ?>" class="btn btn-sm btn-outline-primary">Month</a>
                <a href="?from=<?= date('Y-01-01') ?>&to=<?= date('Y-12-31') ?>" class="btn btn-sm btn-outline-primary">Year</a>
            </div>
        </div>
    </form>
    <small class="text-muted mt-2">
        <i class="bi bi-info-circle"></i> Showing data from 
        <b><?= date('M d, Y', strtotime($date_from)) ?></b> to <b><?= date('M d, Y', strtotime($date_to)) ?></b>
    </small>
</div>

<!-- ============================================
     FEATURES 5-10: OVERVIEW STAT CARDS
============================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card p-3" style="background:linear-gradient(135deg,#667eea,#764ba2);color:white;">
            <div class="d-flex justify-content-between">
                <div>
                    <small>Total Patients</small>
                    <h2 class="mb-0"><?= $total_patients ?></h2>
                    <small style="opacity:0.8;"><?= $active_patients ?> active</small>
                </div>
                <i class="bi bi-people-fill" style="font-size:40px;opacity:0.4;"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card p-3" style="background:linear-gradient(135deg,#28a745,#20c997);color:white;">
            <div class="d-flex justify-content-between">
                <div>
                    <small>Total Revenue</small>
                    <h2 class="mb-0" style="font-size:24px;"><?= money($total_revenue) ?></h2>
                    <small style="opacity:0.8;"><?= $total_invoices ?> invoices</small>
                </div>
                <i class="bi bi-cash-stack" style="font-size:40px;opacity:0.4;"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card p-3" style="background:linear-gradient(135deg,#0d6efd,#6610f2);color:white;">
            <div class="d-flex justify-content-between">
                <div>
                    <small>Appointments</small>
                    <h2 class="mb-0"><?= $total_appts ?></h2>
                    <small style="opacity:0.8;"><?= $completed_appts ?> completed</small>
                </div>
                <i class="bi bi-calendar-check-fill" style="font-size:40px;opacity:0.4;"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card p-3" style="background:linear-gradient(135deg,#dc3545,#fd7e14);color:white;">
            <div class="d-flex justify-content-between">
                <div>
                    <small>Outstanding</small>
                    <h2 class="mb-0" style="font-size:24px;"><?= money($total_pending) ?></h2>
                    <small style="opacity:0.8;">pending payments</small>
                </div>
                <i class="bi bi-exclamation-triangle-fill" style="font-size:40px;opacity:0.4;"></i>
            </div>
        </div>
    </div>
</div>

<!-- ============================================
     FEATURES 30: KPI CARDS
============================================ -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card p-3 text-center">
            <i class="bi bi-check2-circle text-success" style="font-size:28px;"></i>
            <small class="text-muted">Completion Rate</small>
            <h3 class="mb-0 text-success"><?= $completion_rate ?>%</h3>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card p-3 text-center">
            <i class="bi bi-x-circle text-danger" style="font-size:28px;"></i>
            <small class="text-muted">Cancel Rate</small>
            <h3 class="mb-0 text-danger"><?= $cancel_rate ?>%</h3>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card p-3 text-center">
            <i class="bi bi-graph-up-arrow text-primary" style="font-size:28px;"></i>
            <small class="text-muted">Collection Rate</small>
            <h3 class="mb-0 text-primary"><?= $collection_rate ?>%</h3>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card p-3 text-center">
            <i class="bi bi-receipt text-info" style="font-size:28px;"></i>
            <small class="text-muted">Avg Invoice</small>
            <h3 class="mb-0 text-info" style="font-size:18px;"><?= money($avg_invoice) ?></h3>
        </div>
    </div>
</div>

<!-- ============================================
     FEATURE 16: REVENUE SNAPSHOTS
============================================ -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card p-3 text-center bg-light">
            <small class="text-muted">Today</small>
            <h5 class="mb-0 text-success"><?= money($rev_today) ?></h5>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card p-3 text-center bg-light">
            <small class="text-muted">This Week</small>
            <h5 class="mb-0 text-success"><?= money($rev_week) ?></h5>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card p-3 text-center bg-light">
            <small class="text-muted">This Month</small>
            <h5 class="mb-0 text-success"><?= money($rev_month) ?></h5>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card p-3 text-center bg-light">
            <small class="text-muted">This Year</small>
            <h5 class="mb-0 text-success"><?= money($rev_year) ?></h5>
        </div>
    </div>
</div>

<!-- ============================================
     FEATURES 17-18: REVENUE CHARTS
============================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card p-3">
            <h6><i class="bi bi-graph-up-arrow text-primary"></i> Daily Revenue Trend</h6>
            <hr class="my-2">
            <canvas id="dailyChart" height="80"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-3">
            <h6><i class="bi bi-pie-chart-fill text-warning"></i> Appointments Status</h6>
            <hr class="my-2">
            <canvas id="apptStatusChart"></canvas>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-12">
        <div class="card p-3">
            <h6><i class="bi bi-bar-chart-line-fill text-success"></i> Monthly Revenue (Last 12 Months)</h6>
            <hr class="my-2">
            <canvas id="monthlyChart" height="70"></canvas>
        </div>
    </div>
</div>

<!-- ============================================
     FEATURE 19: APPOINTMENTS BY STATUS
============================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card p-3">
            <h6><i class="bi bi-clipboard-check text-info"></i> Appointments Summary</h6>
            <hr class="my-2">
            <table class="table table-sm">
                <tr>
                    <td><i class="bi bi-hourglass text-warning"></i> Pending</td>
                    <td class="text-end fw-bold"><?= $appt_status['pending'] ?? 0 ?></td>
                </tr>
                <tr>
                    <td><i class="bi bi-check text-info"></i> Confirmed</td>
                    <td class="text-end fw-bold"><?= $appt_status['confirmed'] ?? 0 ?></td>
                </tr>
                <tr>
                    <td><i class="bi bi-check2-all text-success"></i> Completed</td>
                    <td class="text-end fw-bold text-success"><?= $appt_status['completed'] ?? 0 ?></td>
                </tr>
                <tr>
                    <td><i class="bi bi-x-circle text-danger"></i> Cancelled</td>
                    <td class="text-end fw-bold text-danger"><?= $appt_status['cancelled'] ?? 0 ?></td>
                </tr>
                <tr class="table-primary">
                    <td><b>Total</b></td>
                    <td class="text-end fw-bold"><?= $total_appts ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- ============================================
         FEATURE 23: PAYMENT METHODS
    ============================================ -->
    <div class="col-lg-6">
        <div class="card p-3">
            <h6><i class="bi bi-credit-card text-primary"></i> Payment Methods</h6>
            <hr class="my-2">
            <?php if ($payment_methods->num_rows == 0): ?>
                <p class="text-muted mb-0">No payment data</p>
            <?php else: ?>
                <?php 
                $mc = ['cash'=>'success','card'=>'primary','online'=>'info','cheque'=>'warning'];
                while ($m = $payment_methods->fetch_assoc()): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <span class="badge bg-<?= $mc[$m['payment_method']] ?? 'secondary' ?>">
                                <?= ucfirst($m['payment_method']) ?>
                            </span>
                            <small class="text-muted">(<?= $m['c'] ?> invoices)</small>
                        </div>
                        <b class="text-success"><?= money($m['t']) ?></b>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================
     FEATURE 20: DOCTOR PERFORMANCE
============================================ -->
<div class="card p-3 mb-4">
    <h6><i class="bi bi-person-badge-fill text-success"></i> Doctor Performance Report</h6>
    <hr class="my-2">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Doctor</th>
                    <th>Total Appts</th>
                    <th>Completed</th>
                    <th>Cancelled</th>
                    <th>Revenue</th>
                    <th>Completion %</th>
                </tr>
            </thead>
            <tbody>
                <?php $i=1; while ($d = $doctor_stats->fetch_assoc()): 
                    $rate = $d['total_appts'] > 0 ? round(($d['completed']/$d['total_appts'])*100) : 0;
                ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><i class="bi bi-person-circle"></i> Dr. <?= e($d['full_name']) ?></td>
                        <td><b><?= $d['total_appts'] ?></b></td>
                        <td><span class="text-success"><?= $d['completed'] ?></span></td>
                        <td><span class="text-danger"><?= $d['cancelled'] ?></span></td>
                        <td><b><?= money($d['revenue']) ?></b></td>
                        <td>
                            <div class="progress" style="height:18px;">
                                <div class="progress-bar bg-success" style="width: <?= $rate ?>%">
                                    <?= $rate ?>%
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================
     FEATURE 21: CHAIR UTILIZATION
============================================ -->
<div class="card p-3 mb-4">
    <h6><i class="bi bi-grid-3x3-gap-fill text-warning"></i> Chair Utilization Report</h6>
    <hr class="my-2">
    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr><th>Chair</th><th>Status</th><th>Total Uses</th><th>Today</th></tr>
            </thead>
            <tbody>
                <?php while ($c = $chair_stats->fetch_assoc()): ?>
                    <tr>
                        <td><b><?= e($c['chair_name']) ?></b></td>
                        <td>
                            <span class="badge bg-<?= $c['status']=='available'?'success':($c['status']=='occupied'?'danger':'secondary') ?>">
                                <?= ucfirst($c['status']) ?>
                            </span>
                        </td>
                        <td><?= $c['total_uses'] ?></td>
                        <td><?= $c['today_uses'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- ============================================
         FEATURE 22: TOP TREATMENTS
    ============================================ -->
    <div class="col-lg-6">
        <div class="card p-3">
            <h6><i class="bi bi-award-fill text-warning"></i> Top 10 Treatments</h6>
            <hr class="my-2">
            <?php if ($top_treatments->num_rows == 0): ?>
                <p class="text-muted mb-0">No treatment data</p>
            <?php else: ?>
                <table class="table table-sm">
                    <?php $i=1; while ($t = $top_treatments->fetch_assoc()): ?>
                        <tr>
                            <td width="30"><?= $i++ ?>.</td>
                            <td><?= e($t['treatment']) ?></td>
                            <td class="text-end">
                                <span class="badge bg-primary"><?= $t['c'] ?></span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================
         FEATURE 24: PATIENT DEMOGRAPHICS
    ============================================ -->
    <div class="col-lg-6">
        <div class="card p-3">
            <h6><i class="bi bi-people-fill text-info"></i> Patient Demographics</h6>
            <hr class="my-2">
            <div class="row">
                <div class="col-6">
                    <h6 class="small text-muted">By Gender</h6>
                    <?php 
                    $gender_stats->data_seek(0);
                    while ($g = $gender_stats->fetch_assoc()): ?>
                        <div class="d-flex justify-content-between">
                            <span><i class="bi bi-gender-<?= $g['gender']=='female'?'female':'male' ?>"></i> <?= ucfirst($g['gender']) ?></span>
                            <b><?= $g['c'] ?></b>
                        </div>
                    <?php endwhile; ?>
                </div>
                <div class="col-6">
                    <h6 class="small text-muted">By Blood Group</h6>
                    <?php 
                    $blood_stats->data_seek(0);
                    while ($b = $blood_stats->fetch_assoc()): ?>
                        <div class="d-flex justify-content-between">
                            <span class="badge bg-danger"><?= e($b['blood_group']) ?></span>
                            <b><?= $b['c'] ?></b>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- ============================================
         FEATURE 25: AGE GROUPS
    ============================================ -->
    <div class="col-lg-6">
        <div class="card p-3">
            <h6><i class="bi bi-bar-chart-fill text-primary"></i> Age Distribution</h6>
            <hr class="my-2">
            <canvas id="ageChart" height="150"></canvas>
        </div>
    </div>

    <!-- ============================================
         FEATURE 29: HOURLY DISTRIBUTION
    ============================================ -->
    <div class="col-lg-6">
        <div class="card p-3">
            <h6><i class="bi bi-clock-history text-warning"></i> Peak Hours (Appointments)</h6>
            <hr class="my-2">
            <canvas id="hourlyChart" height="150"></canvas>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- ============================================
         FEATURE 27: TOP SPENDING PATIENTS
    ============================================ -->
    <div class="col-lg-6">
        <div class="card p-3">
            <h6><i class="bi bi-trophy-fill text-success"></i> Top Spending Patients</h6>
            <hr class="my-2">
            <table class="table table-sm">
                <thead><tr><th>#</th><th>Patient</th><th>Invoices</th><th>Total</th></tr></thead>
                <?php $i=1; while ($p = $top_patients->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td>
                            <b><?= e($p['full_name']) ?></b><br>
                            <small class="text-muted"><?= e($p['patient_code']) ?></small>
                        </td>
                        <td><?= $p['total_invoices'] ?></td>
                        <td class="text-success fw-bold"><?= money($p['total_paid']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <!-- ============================================
         FEATURE 28: OUTSTANDING BALANCES
    ============================================ -->
    <div class="col-lg-6">
        <div class="card p-3">
            <h6><i class="bi bi-exclamation-triangle-fill text-danger"></i> Outstanding Balances</h6>
            <hr class="my-2">
            <?php if ($outstanding->num_rows == 0): ?>
                <p class="text-success text-center py-3">
                    <i class="bi bi-check-circle" style="font-size:30px;"></i><br>
                    No outstanding balances 🎉
                </p>
            <?php else: ?>
                <table class="table table-sm">
                    <thead><tr><th>#</th><th>Patient</th><th>Invoices</th><th>Due</th></tr></thead>
                    <?php $i=1; while ($o = $outstanding->fetch_assoc()): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td>
                                <b><?= e($o['full_name']) ?></b><br>
                                <small class="text-muted"><i class="bi bi-telephone"></i> <?= e($o['phone']) ?></small>
                            </td>
                            <td><?= $o['invoices'] ?></td>
                            <td class="text-danger fw-bold"><?= money($o['total_due']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================
     FOOTER
============================================ -->
<div class="text-center text-muted small py-3">
    <i class="bi bi-info-circle"></i> Report generated on <?= date('F d, Y \a\t h:i A') ?>
    | <?= SITE_NAME ?>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// FEATURE 17: Daily Revenue Chart
new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_keys($daily_revenue)) ?>,
        datasets: [{
            label: 'Revenue (Rs.)',
            data: <?= json_encode(array_values($daily_revenue)) ?>,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102,126,234,0.15)',
            fill: true,
            tension: 0.4
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});

// FEATURE 18: Monthly Revenue Chart
new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($monthly_revenue)) ?>,
        datasets: [{
            label: 'Revenue (Rs.)',
            data: <?= json_encode(array_values($monthly_revenue)) ?>,
            backgroundColor: 'rgba(40,167,69,0.7)',
            borderRadius: 6
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});

// Appointments Status Doughnut
new Chart(document.getElementById('apptStatusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Pending','Confirmed','Completed','Cancelled'],
        datasets: [{
            data: [
                <?= $appt_status['pending'] ?? 0 ?>,
                <?= $appt_status['confirmed'] ?? 0 ?>,
                <?= $appt_status['completed'] ?? 0 ?>,
                <?= $appt_status['cancelled'] ?? 0 ?>
            ],
            backgroundColor: ['#ffc107','#0dcaf0','#198754','#dc3545']
        }]
    }
});

// FEATURE 25: Age Distribution
new Chart(document.getElementById('ageChart'), {
    type: 'bar',
    data: {
        labels: ['Children (<18)', 'Young (18-35)', 'Adult (36-55)', 'Senior (55+)'],
        datasets: [{
            data: [
                <?= $age_groups['child'] ?? 0 ?>,
                <?= $age_groups['young'] ?? 0 ?>,
                <?= $age_groups['adult'] ?? 0 ?>,
                <?= $age_groups['senior'] ?? 0 ?>
            ],
            backgroundColor: ['#0dcaf0','#198754','#ffc107','#dc3545']
        }]
    },
    options: { plugins: { legend: { display: false } } }
});

// FEATURE 29: Hourly Distribution
new Chart(document.getElementById('hourlyChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_map(fn($h) => $h.':00', array_keys($hourly))) ?>,
        datasets: [{
            label: 'Appointments',
            data: <?= json_encode(array_values($hourly)) ?>,
            borderColor: '#ffc107',
            backgroundColor: 'rgba(255,193,7,0.2)',
            fill: true,
            tension: 0.4
        }]
    }
});

// FEATURE: CSV Export
function exportCSV() {
    let rows = [['Report Type','Value']];
    rows.push(['Total Patients', '<?= $total_patients ?>']);
    rows.push(['Total Revenue', '<?= $total_revenue ?>']);
    rows.push(['Total Appointments', '<?= $total_appts ?>']);
    rows.push(['Pending Amount', '<?= $total_pending ?>']);
    
    let csv = rows.map(r => r.join(',')).join('\n');
    let blob = new Blob([csv], {type: 'text/csv'});
    let url = window.URL.createObjectURL(blob);
    let a = document.createElement('a');
    a.href = url;
    a.download = 'report_<?= date("Y-m-d") ?>.csv';
    a.click();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>