<?php
$page_title = 'Advanced Analytics';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// ============================================
// PATIENT GROWTH (12 months)
// ============================================
$patient_growth = [];
for ($i = 11; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $c = countRows($conn, 'patients', "DATE_FORMAT(created_at, '%Y-%m')='$m'");
    $patient_growth[$m] = $c;
}

// ============================================
// REVENUE GROWTH (12 months)
// ============================================
$revenue_growth = [];
for ($i = 11; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $s = sumColumn($conn, 'invoices', 'paid_amount', "DATE_FORMAT(invoice_date, '%Y-%m')='$m'");
    $revenue_growth[$m] = $s;
}

// ============================================
// KPI CALCULATIONS
// ============================================
$this_month_patients = $patient_growth[date('Y-m')] ?? 0;
$last_month_patients = $patient_growth[date('Y-m', strtotime('-1 month'))] ?? 0;
$patient_change = $last_month_patients > 0 
    ? round((($this_month_patients - $last_month_patients) / $last_month_patients) * 100, 1) 
    : 0;

$this_month_rev = $revenue_growth[date('Y-m')] ?? 0;
$last_month_rev = $revenue_growth[date('Y-m', strtotime('-1 month'))] ?? 0;
$rev_change = $last_month_rev > 0 
    ? round((($this_month_rev - $last_month_rev) / $last_month_rev) * 100, 1) 
    : 0;

// ============================================
// TREATMENT REVENUE (FIXED — treatment_name)
// ============================================
$treatment_revenue = $conn->query("SELECT treatment_name, SUM(cost) t, COUNT(*) c 
    FROM treatments WHERE treatment_name IS NOT NULL 
    GROUP BY treatment_name ORDER BY t DESC LIMIT 8");

// ============================================
// BUSIEST DAYS
// ============================================
$busy_days = [];
$res = $conn->query("SELECT DAYNAME(appointment_date) d, COUNT(*) c 
    FROM appointments 
    GROUP BY DAYOFWEEK(appointment_date), d 
    ORDER BY DAYOFWEEK(appointment_date)");
while ($row = $res->fetch_assoc()) {
    $busy_days[$row['d']] = (int)$row['c'];
}

// ============================================
// ADDITIONAL ANALYTICS
// ============================================
// Treatment category revenue
$treatment_categories = [];
$res = $conn->query("SELECT tc.category, SUM(t.cost) t 
    FROM treatments t 
    LEFT JOIN treatments_catalog tc ON tc.name = t.treatment_name 
    WHERE tc.category IS NOT NULL 
    GROUP BY tc.category ORDER BY t DESC");
while ($row = $res->fetch_assoc()) {
    $treatment_categories[$row['category']] = (float)$row['t'];
}

// Average treatment cost
$avg_treatment_cost = $conn->query("SELECT IFNULL(AVG(cost),0) a FROM treatments")->fetch_assoc()['a'];

// Patient retention (patients with > 1 visit)
$returning_patients = $conn->query("SELECT COUNT(DISTINCT patient_id) c 
    FROM appointments 
    GROUP BY patient_id 
    HAVING COUNT(*) > 1")->num_rows;

$total_unique_patients = countRows($conn, 'patients');
$retention_rate = $total_unique_patients > 0 
    ? round(($returning_patients / $total_unique_patients) * 100, 1) 
    : 0;

// Monthly appointment trend
$appt_trend = [];
for ($i = 11; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $c = countRows($conn, 'appointments', "DATE_FORMAT(appointment_date, '%Y-%m')='$m'");
    $appt_trend[$m] = $c;
}

// Top doctors
$top_doctors_analytics = $conn->query("SELECT u.full_name, 
    COUNT(a.id) total_appts,
    (SELECT IFNULL(SUM(t.cost),0) FROM treatments t WHERE t.doctor_id = u.id) revenue
    FROM users u 
    LEFT JOIN appointments a ON u.id = a.doctor_id 
    WHERE u.role='doctor' 
    GROUP BY u.id 
    ORDER BY total_appts DESC LIMIT 5");
?>

<!-- ============================================
     HEADER
============================================ -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-graph-up-arrow text-primary"></i> Advanced Analytics</h4>
        <small class="text-muted">Business intelligence & insights</small>
    </div>
    <div>
        <button class="btn btn-outline-success" onclick="window.print()">
            <i class="bi bi-printer"></i> Print
        </button>
        <button class="btn btn-primary" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>
</div>

<!-- ============================================
     KPI CARDS
============================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card p-3" style="background:linear-gradient(135deg,#667eea,#764ba2);color:white;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small style="opacity:0.9;">New Patients (This Month)</small>
                    <h2 class="mb-1"><?= $this_month_patients ?></h2>
                    <span class="badge bg-<?= $patient_change >= 0 ? 'success' : 'danger' ?>">
                        <i class="bi bi-arrow-<?= $patient_change >= 0 ? 'up' : 'down' ?>"></i>
                        <?= abs($patient_change) ?>% vs last month
                    </span>
                </div>
                <i class="bi bi-person-plus-fill" style="font-size:40px;opacity:0.4;"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card p-3" style="background:linear-gradient(135deg,#28a745,#20c997);color:white;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small style="opacity:0.9;">Revenue (This Month)</small>
                    <h2 class="mb-1" style="font-size:22px;"><?= money($this_month_rev) ?></h2>
                    <span class="badge bg-<?= $rev_change >= 0 ? 'success' : 'danger' ?>">
                        <i class="bi bi-arrow-<?= $rev_change >= 0 ? 'up' : 'down' ?>"></i>
                        <?= abs($rev_change) ?>% vs last month
                    </span>
                </div>
                <i class="bi bi-cash-stack" style="font-size:40px;opacity:0.4;"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card p-3" style="background:linear-gradient(135deg,#0d6efd,#6610f2);color:white;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small style="opacity:0.9;">Total Patients</small>
                    <h2 class="mb-1"><?= $total_unique_patients ?></h2>
                    <small style="opacity:0.9;"><?= countRows($conn, 'patients', "status='active'") ?> active</small>
                </div>
                <i class="bi bi-people-fill" style="font-size:40px;opacity:0.4;"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card p-3" style="background:linear-gradient(135deg,#ffc107,#fd7e14);color:white;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small style="opacity:0.9;">Retention Rate</small>
                    <h2 class="mb-1"><?= $retention_rate ?>%</h2>
                    <small style="opacity:0.9;"><?= $returning_patients ?> returning patients</small>
                </div>
                <i class="bi bi-heart-fill" style="font-size:40px;opacity:0.4;"></i>
            </div>
        </div>
    </div>
</div>

<!-- ============================================
     GROWTH CHART
============================================ -->
<div class="card p-3 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0"><i class="bi bi-graph-up text-primary"></i> 12-Month Growth (Patients vs Revenue)</h6>
        <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-primary active" onclick="toggleDataset(0, 1)">Both</button>
            <button class="btn btn-outline-primary" onclick="toggleDataset(1, 0)">Patients</button>
            <button class="btn btn-outline-primary" onclick="toggleDataset(0, 1)">Revenue</button>
        </div>
    </div>
    <hr class="my-2">
    <canvas id="growthChart" height="80"></canvas>
</div>

<!-- ============================================
     APPOINTMENTS TREND
============================================ -->
<div class="card p-3 mb-4">
    <h6><i class="bi bi-calendar-check text-info"></i> Monthly Appointments Trend</h6>
    <hr class="my-2">
    <canvas id="apptTrendChart" height="60"></canvas>
</div>

<!-- ============================================
     TREATMENT + BUSIEST DAYS
============================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card p-3 h-100">
            <h6><i class="bi bi-cash-coin text-success"></i> Top Treatment Revenue</h6>
            <hr class="my-2">
            <?php if ($treatment_revenue->num_rows === 0): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox" style="font-size:40px;"></i>
                    <p class="mb-0 mt-2">No treatment data yet</p>
                    <small>Treatments module එකෙන් දත්ත add කරන්න</small>
                </div>
            <?php else: ?>
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Treatment</th>
                            <th class="text-center">Count</th>
                            <th class="text-end">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($t = $treatment_revenue->fetch_assoc()): ?>
                            <tr>
                                <td><b><?= e($t['treatment_name']) ?></b></td>
                                <td class="text-center">
                                    <span class="badge bg-secondary"><?= $t['c'] ?></span>
                                </td>
                                <td class="text-end text-success fw-bold"><?= money($t['t']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card p-3 h-100">
            <h6><i class="bi bi-calendar-week text-warning"></i> Busiest Days of Week</h6>
            <hr class="my-2">
            <?php if (empty($busy_days)): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-calendar-x" style="font-size:40px;"></i>
                    <p class="mb-0 mt-2">No appointment data</p>
                </div>
            <?php else: ?>
                <canvas id="busyDaysChart" height="180"></canvas>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================
     TREATMENT CATEGORIES + TOP DOCTORS
============================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card p-3 h-100">
            <h6><i class="bi bi-pie-chart-fill text-primary"></i> Revenue by Category</h6>
            <hr class="my-2">
            <?php if (empty($treatment_categories)): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-pie-chart" style="font-size:40px;"></i>
                    <p class="mb-0 mt-2">No category data</p>
                </div>
            <?php else: ?>
                <canvas id="categoryChart" height="200"></canvas>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card p-3 h-100">
            <h6><i class="bi bi-trophy-fill text-warning"></i> Top Performing Doctors</h6>
            <hr class="my-2">
            <?php if ($top_doctors_analytics->num_rows === 0): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-person-x" style="font-size:40px;"></i>
                    <p class="mb-0 mt-2">No doctor data</p>
                </div>
            <?php else: ?>
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th width="40">#</th>
                            <th>Doctor</th>
                            <th class="text-center">Appointments</th>
                            <th class="text-end">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i=1; while ($d = $top_doctors_analytics->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php if ($i <= 3): ?>
                                        <i class="bi bi-trophy-fill text-<?= $i==1?'warning':($i==2?'secondary':'danger') ?>"></i>
                                    <?php else: ?>
                                        <?= $i ?>
                                    <?php endif; ?>
                                </td>
                                <td><b><?= e($d['full_name']) ?></b></td>
                                <td class="text-center"><span class="badge bg-primary"><?= $d['total_appts'] ?></span></td>
                                <td class="text-end text-success fw-bold"><?= money($d['revenue']) ?></td>
                            </tr>
                        <?php $i++; endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================
     QUICK INSIGHTS
============================================ -->
<div class="card p-3 mb-4">
    <h6><i class="bi bi-lightbulb-fill text-warning"></i> Quick Insights</h6>
    <hr class="my-2">
    <div class="row g-3">
        <div class="col-md-3 col-6">
            <div class="p-2 border rounded text-center">
                <small class="text-muted">Avg Treatment Cost</small>
                <h5 class="mb-0"><?= money($avg_treatment_cost) ?></h5>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="p-2 border rounded text-center">
                <small class="text-muted">Returning Patients</small>
                <h5 class="mb-0"><?= $returning_patients ?></h5>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="p-2 border rounded text-center">
                <small class="text-muted">This Month Appointments</small>
                <h5 class="mb-0"><?= $appt_trend[date('Y-m')] ?? 0 ?></h5>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="p-2 border rounded text-center">
                <small class="text-muted">Total Revenue (Year)</small>
                <h5 class="mb-0" style="font-size:16px;"><?= money(array_sum($revenue_growth)) ?></h5>
            </div>
        </div>
    </div>
</div>

<!-- ============================================
     FOOTER INFO
============================================ -->
<div class="text-center text-muted small py-2">
    <i class="bi bi-info-circle"></i> Analytics generated on <?= date('F d, Y \a\t h:i A') ?> 
    | <?= SITE_NAME ?>
</div>

<!-- ============================================
     CHART.JS
============================================ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Global chart defaults
Chart.defaults.font.family = "'Segoe UI', sans-serif";
Chart.defaults.animation.duration = 1500;
Chart.defaults.animation.easing = 'easeOutQuart';

// ============================================
// GROWTH CHART (Patients vs Revenue)
// ============================================
const growthCtx = document.getElementById('growthChart').getContext('2d');
const growthGradient1 = growthCtx.createLinearGradient(0, 0, 0, 400);
growthGradient1.addColorStop(0, 'rgba(102,126,234,0.4)');
growthGradient1.addColorStop(1, 'rgba(102,126,234,0.05)');

const growthGradient2 = growthCtx.createLinearGradient(0, 0, 0, 400);
growthGradient2.addColorStop(0, 'rgba(40,167,69,0.4)');
growthGradient2.addColorStop(1, 'rgba(40,167,69,0.05)');

const growthChart = new Chart(growthCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_keys($patient_growth)) ?>,
        datasets: [
            {
                label: 'New Patients',
                data: <?= json_encode(array_values($patient_growth)) ?>,
                borderColor: '#667eea',
                backgroundColor: growthGradient1,
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointBackgroundColor: '#667eea',
                pointRadius: 5,
                pointHoverRadius: 8,
                yAxisID: 'y'
            },
            {
                label: 'Revenue (Rs.)',
                data: <?= json_encode(array_values($revenue_growth)) ?>,
                borderColor: '#28a745',
                backgroundColor: growthGradient2,
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointBackgroundColor: '#28a745',
                pointRadius: 5,
                pointHoverRadius: 8,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                backgroundColor: 'rgba(0,0,0,0.8)',
                padding: 12,
                titleFont: { size: 14 },
                bodyFont: { size: 13 },
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        let value = context.parsed.y;
                        if (context.datasetIndex === 1) {
                            return label + ': Rs. ' + value.toLocaleString();
                        }
                        return label + ': ' + value;
                    }
                }
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: { display: true, text: 'New Patients' },
                grid: { color: 'rgba(0,0,0,0.05)' }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: { display: true, text: 'Revenue (Rs.)' },
                grid: { drawOnChartArea: false }
            }
        }
    }
});

// Toggle datasets
function toggleDataset(showPatients, showRevenue) {
    growthChart.data.datasets[0].hidden = !showPatients;
    growthChart.data.datasets[1].hidden = !showRevenue;
    growthChart.update();
    document.querySelectorAll('.btn-group .btn').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
}

// ============================================
// APPOINTMENTS TREND
// ============================================
new Chart(document.getElementById('apptTrendChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($appt_trend)) ?>,
        datasets: [{
            label: 'Appointments',
            data: <?= json_encode(array_values($appt_trend)) ?>,
            backgroundColor: 'rgba(23,162,184,0.7)',
            borderColor: '#17a2b8',
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false } }
        }
    }
});

// ============================================
// BUSIEST DAYS
// ============================================
<?php if (!empty($busy_days)): ?>
new Chart(document.getElementById('busyDaysChart'), {
    type: 'polarArea',
    data: {
        labels: <?= json_encode(array_keys($busy_days)) ?>,
        datasets: [{
            data: <?= json_encode(array_values($busy_days)) ?>,
            backgroundColor: [
                'rgba(102,126,234,0.7)',
                'rgba(118,75,162,0.7)',
                'rgba(240,147,251,0.7)',
                'rgba(79,172,254,0.7)',
                'rgba(0,242,254,0.7)',
                'rgba(255,193,7,0.7)',
                'rgba(40,167,69,0.7)'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
<?php endif; ?>

// ============================================
// CATEGORY CHART
// ============================================
<?php if (!empty($treatment_categories)): ?>
new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($treatment_categories)) ?>,
        datasets: [{
            data: <?= json_encode(array_values($treatment_categories)) ?>,
            backgroundColor: [
                '#667eea', '#764ba2', '#f093fb', '#4facfe',
                '#00f2fe', '#28a745', '#ffc107', '#dc3545'
            ],
            borderWidth: 3,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'right' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': Rs. ' + context.parsed.toLocaleString();
                    }
                }
            }
        },
        cutout: '60%'
    }
});
<?php endif; ?>

// ============================================
// LOG
// ============================================
console.log('%c📊 Analytics Loaded', 'color:#667eea;font-size:16px;font-weight:bold;');
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>