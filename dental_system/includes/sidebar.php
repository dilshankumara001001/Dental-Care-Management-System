<!-- Sidebar -->
<div id="sidebar-wrapper">
    <div class="sidebar-heading">
        <i class="bi bi-heart-pulse-fill"></i> <?= SITE_NAME ?>
    </div>
    
    <div class="list-group list-group-flush">
        <a href="<?= BASE_URL ?>dashboard.php" class="list-group-item">
            <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
        </a>
        <a href="<?= BASE_URL ?>modules/patients/index.php" class="list-group-item">
            <i class="bi bi-people-fill"></i> <span>Patients</span>
        </a>
        <a href="<?= BASE_URL ?>modules/appointments/index.php" class="list-group-item">
            <i class="bi bi-calendar-check-fill"></i> <span>Appointments</span>
        </a>
        <a href="<?= BASE_URL ?>modules/chairs/index.php" class="list-group-item">
            <i class="bi bi-grid-3x3-gap-fill"></i> <span>Chairs (12)</span>
        </a>
        <a href="<?= BASE_URL ?>modules/billing/index.php" class="list-group-item">
            <i class="bi bi-receipt-cutoff"></i> <span>Billing</span>
        </a>
        <a href="<?= BASE_URL ?>modules/reports/index.php" class="list-group-item">
            <i class="bi bi-bar-chart-line-fill"></i> <span>Reports</span>
        </a>
        <a href="<?= BASE_URL ?>modules/prescriptions/index.php" class="list-group-item">
            <i class="bi bi-file-medical-fill"></i> <span>Prescriptions</span>
        </a>
        <a href="<?= BASE_URL ?>modules/dental_chart/index.php" class="list-group-item">
            <i class="bi bi-grid-3x3"></i> <span>Dental Chart</span>
        </a>
        <a href="<?= BASE_URL ?>modules/calendar/index.php" class="list-group-item">
            <i class="bi bi-calendar-month-fill"></i> <span>Calendar</span>
        </a>
        <a href="<?= BASE_URL ?>modules/analytics/index.php" class="list-group-item">
            <i class="bi bi-graph-up-arrow"></i> <span>Analytics</span>
        </a>
        <a href="<?= BASE_URL ?>modules/notifications/index.php" class="list-group-item">
            <i class="bi bi-bell-fill"></i> <span>Notifications</span>
        </a>
    </div>
    
    <div class="p-3 mt-auto" style="border-top: 1px solid rgba(255,255,255,0.08);">
        <div class="text-white-50 small mb-1">Logged in as:</div>
        <div class="text-white fw-bold mb-1">
            <i class="bi bi-person-circle"></i> <?= $_SESSION['full_name'] ?? 'Guest' ?>
        </div>
        <div class="badge bg-primary mb-2"><?= ucfirst($_SESSION['role'] ?? '') ?></div>
        <a href="<?= BASE_URL ?>logout.php" class="btn btn-danger btn-sm w-100">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div>

<!-- Page Content -->
<div id="page-content-wrapper">
    <nav class="navbar">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?= isset($page_title) ? $page_title : 'Dashboard' ?></h5>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted small">
                    <i class="bi bi-clock"></i> <?= date('M d, Y • h:i A') ?>
                </span>
            </div>
        </div>
    </nav>
    <div class="container-fluid p-4">