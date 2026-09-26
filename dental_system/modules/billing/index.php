<?php
$page_title = 'Billing & Invoices';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$msg = '';

// ============================================
// HANDLE ACTIONS
// ============================================

// FEATURE 1: Create Invoice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $invoice_no = generateInvoiceNo();
    $total = (float)$_POST['total_amount'];
    $disc  = (float)$_POST['discount'];
    $tax   = (float)$_POST['tax'];
    $paid  = (float)$_POST['paid_amount'];
    $balance = $total + $tax - $disc - $paid;
    
    $status = $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
    
    $stmt = $conn->prepare("INSERT INTO invoices 
        (invoice_no, patient_id, total_amount, discount, tax, paid_amount, balance, payment_method, status, invoice_date, created_by) 
        VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("sidddddsssi",
        $invoice_no, $_POST['patient_id'], $total, $disc, $tax, $paid, $balance,
        $_POST['payment_method'], $status, $_POST['invoice_date'], $_SESSION['user_id']
    );
    if ($stmt->execute()) {
        $inv_id = $conn->insert_id;
        // Record payment
        if ($paid > 0) {
            $stmt2 = $conn->prepare("INSERT INTO payments (invoice_id, amount, payment_method, reference_no) VALUES (?,?,?,?)");
            $ref = $_POST['reference_no'] ?? '';
            $stmt2->bind_param("idss", $inv_id, $paid, $_POST['payment_method'], $ref);
            $stmt2->execute();
        }
        logActivity($conn, $_SESSION['user_id'], 'CREATE_INVOICE', "Invoice: $invoice_no");
        $msg = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Invoice <b>'.$invoice_no.'</b> created!</div>';
    }
}

// FEATURE 2: Update Invoice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = (int)$_POST['id'];
    $total = (float)$_POST['total_amount'];
    $disc  = (float)$_POST['discount'];
    $tax   = (float)$_POST['tax'];
    $paid  = (float)$_POST['paid_amount'];
    $balance = $total + $tax - $disc - $paid;
    $status = $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
    
    $stmt = $conn->prepare("UPDATE invoices SET 
        patient_id=?, total_amount=?, discount=?, tax=?, paid_amount=?, balance=?, 
        payment_method=?, status=?, invoice_date=? WHERE id=?");
    $stmt->bind_param("idddddsssi",
        $_POST['patient_id'], $total, $disc, $tax, $paid, $balance,
        $_POST['payment_method'], $status, $_POST['invoice_date'], $id
    );
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'UPDATE_INVOICE', "Invoice ID: $id");
        $msg = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Invoice updated!</div>';
    }
}

// FEATURE 3: Delete Invoice
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM payments WHERE invoice_id=$id");
    $conn->query("DELETE FROM invoices WHERE id=$id");
    logActivity($conn, $_SESSION['user_id'], 'DELETE_INVOICE', "Invoice ID: $id");
    $msg = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Invoice deleted.</div>';
}

// FEATURE 4: Add Payment to Invoice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $inv_id = (int)$_POST['invoice_id'];
    $amount = (float)$_POST['pay_amount'];
    $method = $_POST['pay_method'];
    $ref    = $_POST['pay_reference'] ?? '';
    
    // Get current invoice
    $inv = $conn->query("SELECT * FROM invoices WHERE id=$inv_id")->fetch_assoc();
    if ($inv) {
        $new_paid = $inv['paid_amount'] + $amount;
        $new_balance = $inv['total_amount'] + $inv['tax'] - $inv['discount'] - $new_paid;
        $new_status = $new_balance <= 0 ? 'paid' : 'partial';
        
        $stmt = $conn->prepare("UPDATE invoices SET paid_amount=?, balance=?, status=? WHERE id=?");
        $stmt->bind_param("ddsi", $new_paid, $new_balance, $new_status, $inv_id);
        $stmt->execute();
        
        $stmt2 = $conn->prepare("INSERT INTO payments (invoice_id, amount, payment_method, reference_no) VALUES (?,?,?,?)");
        $stmt2->bind_param("idss", $inv_id, $amount, $method, $ref);
        $stmt2->execute();
        
        logActivity($conn, $_SESSION['user_id'], 'ADD_PAYMENT', "Invoice: {$inv['invoice_no']}, Amount: $amount");
        $msg = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Payment of '.money($amount).' recorded!</div>';
    }
}

// ============================================
// FILTERS
// ============================================
$search     = trim($_GET['search'] ?? '');
$status_f   = $_GET['status_f'] ?? '';
$method_f   = $_GET['method'] ?? '';
$date_from  = $_GET['from'] ?? '';
$date_to    = $_GET['to'] ?? '';
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 10;
$offset     = ($page - 1) * $per_page;

$where = "1";
$params = [];
$types = '';

if ($search) {
    $where .= " AND (i.invoice_no LIKE ? OR p.full_name LIKE ? OR p.patient_code LIKE ?)";
    $s = "%$search%";
    $params[] = $s; $params[] = $s; $params[] = $s;
    $types .= 'sss';
}
if ($status_f) { $where .= " AND i.status = ?"; $params[] = $status_f; $types .= 's'; }
if ($method_f) { $where .= " AND i.payment_method = ?"; $params[] = $method_f; $types .= 's'; }
if ($date_from) { $where .= " AND i.invoice_date >= ?"; $params[] = $date_from; $types .= 's'; }
if ($date_to)   { $where .= " AND i.invoice_date <= ?"; $params[] = $date_to; $types .= 's'; }

$count_sql = "SELECT COUNT(*) c FROM invoices i LEFT JOIN patients p ON i.patient_id = p.id WHERE $where";
if ($params) {
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_records = $stmt->get_result()->fetch_assoc()['c'];
} else {
    $total_records = $conn->query($count_sql)->fetch_assoc()['c'];
}
$total_pages = ceil($total_records / $per_page);

$sql = "SELECT i.*, p.full_name AS patient_name, p.patient_code, p.phone,
        (SELECT COUNT(*) FROM payments WHERE invoice_id = i.id) AS payment_count
        FROM invoices i LEFT JOIN patients p ON i.patient_id = p.id 
        WHERE $where 
        ORDER BY i.id DESC LIMIT $per_page OFFSET $offset";

if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $invoices = $stmt->get_result();
} else {
    $invoices = $conn->query($sql);
}

$patients = $conn->query("SELECT id, full_name, patient_code FROM patients WHERE status='active' ORDER BY full_name");

// ============================================
// STATS (FEATURES 5-14)
// ============================================
$stat_total_invoices = countRows($conn, 'invoices');
$stat_paid           = countRows($conn, 'invoices', "status='paid'");
$stat_partial        = countRows($conn, 'invoices', "status='partial'");
$stat_unpaid         = countRows($conn, 'invoices', "status='unpaid'");
$stat_total_revenue  = sumColumn($conn, 'invoices', 'paid_amount');
$stat_today_revenue  = sumColumn($conn, 'invoices', 'paid_amount', "invoice_date = CURDATE()");
$stat_month_revenue  = sumColumn($conn, 'invoices', 'paid_amount', "MONTH(invoice_date)=MONTH(CURDATE()) AND YEAR(invoice_date)=YEAR(CURDATE())");
$stat_pending_amt    = sumColumn($conn, 'invoices', 'balance', "balance > 0");
$stat_avg_invoice    = $stat_total_invoices > 0 ? $stat_total_revenue / $stat_total_invoices : 0;

// FEATURE 15: Payment method breakdown
$methods = $conn->query("SELECT payment_method, COUNT(*) c, SUM(paid_amount) t 
    FROM invoices WHERE paid_amount > 0 GROUP BY payment_method");

// FEATURE 16: Recent payments
$recent_payments = $conn->query("SELECT pay.*, i.invoice_no, p.full_name patient_name 
    FROM payments pay 
    LEFT JOIN invoices i ON pay.invoice_id = i.id 
    LEFT JOIN patients p ON i.patient_id = p.id 
    ORDER BY pay.id DESC LIMIT 5");

// FEATURE 17: Top debtors
$top_debtors = $conn->query("SELECT p.full_name, p.patient_code, p.phone, 
    SUM(i.balance) total_due, COUNT(i.id) invoice_count
    FROM invoices i 
    LEFT JOIN patients p ON i.patient_id = p.id 
    WHERE i.balance > 0 
    GROUP BY i.patient_id 
    ORDER BY total_due DESC LIMIT 5");
?>

<?= $msg ?>

<!-- ============================================
     HEADER
============================================ -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-receipt-cutoff"></i> Billing & Invoices</h4>
        <small class="text-muted">Manage patient invoices and payments</small>
    </div>
    <div>
        <button class="btn btn-outline-success" onclick="window.print()">
            <i class="bi bi-printer"></i> Print
        </button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle-fill"></i> New Invoice
        </button>
    </div>
</div>

<!-- ============================================
     FEATURES 18-25: STATS CARDS
============================================ -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card p-3" style="background:linear-gradient(135deg,#28a745,#20c997);color:white;">
            <div class="d-flex justify-content-between">
                <div>
                    <small>Total Revenue</small>
                    <h3 class="mb-0"><?= money($stat_total_revenue) ?></h3>
                </div>
                <i class="bi bi-cash-stack" style="font-size:36px;opacity:0.4;"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card p-3" style="background:linear-gradient(135deg,#0d6efd,#6610f2);color:white;">
            <div class="d-flex justify-content-between">
                <div>
                    <small>Today's Revenue</small>
                    <h3 class="mb-0"><?= money($stat_today_revenue) ?></h3>
                </div>
                <i class="bi bi-calendar-check" style="font-size:36px;opacity:0.4;"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card p-3" style="background:linear-gradient(135deg,#ffc107,#fd7e14);color:white;">
            <div class="d-flex justify-content-between">
                <div>
                    <small>This Month</small>
                    <h3 class="mb-0"><?= money($stat_month_revenue) ?></h3>
                </div>
                <i class="bi bi-graph-up" style="font-size:36px;opacity:0.4;"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card p-3" style="background:linear-gradient(135deg,#dc3545,#c82333);color:white;">
            <div class="d-flex justify-content-between">
                <div>
                    <small>Pending</small>
                    <h3 class="mb-0"><?= money($stat_pending_amt) ?></h3>
                </div>
                <i class="bi bi-exclamation-triangle" style="font-size:36px;opacity:0.4;"></i>
            </div>
        </div>
    </div>
</div>

<!-- Small stats -->
<div class="row g-3 mb-4">
    <div class="col-md-2 col-6">
        <div class="card p-2 text-center">
            <small class="text-muted">Total Invoices</small>
            <h5 class="mb-0"><?= $stat_total_invoices ?></h5>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card p-2 text-center">
            <small class="text-success">Paid</small>
            <h5 class="mb-0 text-success"><?= $stat_paid ?></h5>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card p-2 text-center">
            <small class="text-warning">Partial</small>
            <h5 class="mb-0 text-warning"><?= $stat_partial ?></h5>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card p-2 text-center">
            <small class="text-danger">Unpaid</small>
            <h5 class="mb-0 text-danger"><?= $stat_unpaid ?></h5>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card p-2 text-center">
            <small class="text-muted">Avg Invoice</small>
            <h5 class="mb-0"><?= money($stat_avg_invoice) ?></h5>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card p-2 text-center">
            <small class="text-muted">Payments</small>
            <h5 class="mb-0"><?= countRows($conn, 'payments') ?></h5>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- ============================================
         FEATURE 26: PAYMENT METHODS BREAKDOWN
    ============================================ -->
    <div class="col-lg-4">
        <div class="card p-3 h-100">
            <h6><i class="bi bi-credit-card"></i> Payment Methods</h6>
            <hr class="my-2">
            <?php if ($methods->num_rows == 0): ?>
                <p class="text-muted mb-0">No payments yet</p>
            <?php else: ?>
                <?php 
                $method_icons = ['cash'=>'cash-coin','card'=>'credit-card','online'=>'globe','cheque'=>'journal-check'];
                $method_colors = ['cash'=>'success','card'=>'primary','online'=>'info','cheque'=>'warning'];
                while ($m = $methods->fetch_assoc()): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <i class="bi bi-<?= $method_icons[$m['payment_method']] ?? 'credit-card' ?> text-<?= $method_colors[$m['payment_method']] ?? 'secondary' ?>"></i>
                            <b><?= ucfirst($m['payment_method']) ?></b>
                            <small class="text-muted">(<?= $m['c'] ?>)</small>
                        </div>
                        <span class="badge bg-<?= $method_colors[$m['payment_method']] ?? 'secondary' ?>">
                            <?= money($m['t']) ?>
                        </span>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================
         FEATURE 27: TOP DEBTORS
    ============================================ -->
    <div class="col-lg-4">
        <div class="card p-3 h-100">
            <h6><i class="bi bi-person-exclamation text-danger"></i> Top Debtors</h6>
            <hr class="my-2">
            <?php if ($top_debtors->num_rows == 0): ?>
                <p class="text-muted mb-0">No pending balances 🎉</p>
            <?php else: ?>
                <?php while ($d = $top_debtors->fetch_assoc()): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-1">
                        <div>
                            <b><?= e($d['full_name']) ?></b><br>
                            <small class="text-muted">
                                <?= e($d['patient_code']) ?> | <?= e($d['phone']) ?>
                                (<?= $d['invoice_count'] ?> inv.)
                            </small>
                        </div>
                        <span class="badge bg-danger"><?= money($d['total_due']) ?></span>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================
         FEATURE 28: RECENT PAYMENTS
    ============================================ -->
    <div class="col-lg-4">
        <div class="card p-3 h-100">
            <h6><i class="bi bi-clock-history"></i> Recent Payments</h6>
            <hr class="my-2">
            <?php if ($recent_payments->num_rows == 0): ?>
                <p class="text-muted mb-0">No payments yet</p>
            <?php else: ?>
                <?php while ($p = $recent_payments->fetch_assoc()): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-1">
                        <div>
                            <b class="text-success"><?= money($p['amount']) ?></b><br>
                            <small class="text-muted">
                                <?= e($p['patient_name']) ?><br>
                                <?= e($p['invoice_no']) ?>
                            </small>
                        </div>
                        <small class="text-muted"><?= timeAgo($p['paid_at']) ?></small>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================
     SEARCH & FILTERS
============================================ -->
<div class="card p-3 mb-3">
    <form method="GET" class="row g-2">
        <div class="col-md-3">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" 
                       placeholder="Invoice#, patient..." value="<?= e($search) ?>">
            </div>
        </div>
        <div class="col-md-2">
            <select name="status_f" class="form-select">
                <option value="">All Status</option>
                <option value="paid" <?= $status_f=='paid'?'selected':'' ?>>Paid</option>
                <option value="partial" <?= $status_f=='partial'?'selected':'' ?>>Partial</option>
                <option value="unpaid" <?= $status_f=='unpaid'?'selected':'' ?>>Unpaid</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="method" class="form-select">
                <option value="">All Methods</option>
                <option value="cash" <?= $method_f=='cash'?'selected':'' ?>>Cash</option>
                <option value="card" <?= $method_f=='card'?'selected':'' ?>>Card</option>
                <option value="online" <?= $method_f=='online'?'selected':'' ?>>Online</option>
                <option value="cheque" <?= $method_f=='cheque'?'selected':'' ?>>Cheque</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="from" class="form-control" value="<?= e($date_from) ?>" placeholder="From">
        </div>
        <div class="col-md-2">
            <input type="date" name="to" class="form-control" value="<?= e($date_to) ?>" placeholder="To">
        </div>
        <div class="col-md-1 d-flex gap-1">
            <button class="btn btn-primary w-100"><i class="bi bi-funnel"></i></button>
        </div>
    </form>
    <div class="mt-2">
        <a href="?" class="btn btn-sm btn-outline-secondary">Clear</a>
        <a href="?status_f=unpaid" class="btn btn-sm btn-outline-danger">Unpaid Only</a>
        <a href="?from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-t') ?>" class="btn btn-sm btn-outline-info">This Month</a>
    </div>
</div>

<!-- ============================================
     INVOICES TABLE
============================================ -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th>Patient</th>
                    <th>Total</th>
                    <th>Disc.</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($invoices->num_rows === 0): ?>
                    <tr><td colspan="11" class="text-center text-muted py-4">
                        <i class="bi bi-receipt" style="font-size: 40px;"></i>
                        <p class="mb-0">No invoices found</p>
                    </td></tr>
                <?php endif; ?>
                <?php $i = $offset + 1; while ($inv = $invoices->fetch_assoc()): 
                    $sc = ['paid'=>'success','partial'=>'warning','unpaid'=>'danger'];
                ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td>
                            <b><?= e($inv['invoice_no']) ?></b>
                            <?php if ($inv['payment_count'] > 0): ?>
                                <br><small class="text-muted">
                                    <i class="bi bi-credit-card"></i> <?= $inv['payment_count'] ?> payment(s)
                                </small>
                            <?php endif; ?>
                        </td>
                        <td><?= date('M d, Y', strtotime($inv['invoice_date'])) ?></td>
                        <td>
                            <b><?= e($inv['patient_name']) ?></b><br>
                            <small class="text-muted"><?= e($inv['patient_code']) ?></small>
                        </td>
                        <td><b><?= money($inv['total_amount']) ?></b></td>
                        <td class="text-danger"><?= $inv['discount'] > 0 ? '-'.money($inv['discount']) : '-' ?></td>
                        <td class="text-success"><b><?= money($inv['paid_amount']) ?></b></td>
                        <td>
                            <span class="<?= $inv['balance'] > 0 ? 'text-danger fw-bold' : 'text-muted' ?>">
                                <?= money($inv['balance']) ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $mc = ['cash'=>'success','card'=>'primary','online'=>'info','cheque'=>'warning'];
                            ?>
                            <span class="badge bg-<?= $mc[$inv['payment_method']] ?? 'secondary' ?>">
                                <?= ucfirst($inv['payment_method']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $sc[$inv['status']] ?>">
                                <?= ucfirst($inv['status']) ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" 
                                    data-bs-target="#viewModal<?= $inv['id'] ?>" title="View">
                                <i class="bi bi-eye"></i>
                            </button>
                            <?php if ($inv['balance'] > 0): ?>
                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" 
                                        data-bs-target="#payModal<?= $inv['id'] ?>" title="Add Payment">
                                    <i class="bi bi-cash-coin"></i>
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                                    data-bs-target="#editModal<?= $inv['id'] ?>" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <a href="?delete=<?= $inv['id'] ?>" class="btn btn-sm btn-danger" 
                               onclick="return confirmDelete('Delete invoice?')" title="Delete">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>

                    <!-- VIEW MODAL -->
                    <div class="modal fade" id="viewModal<?= $inv['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-info text-white">
                                    <h5><i class="bi bi-receipt"></i> Invoice <?= e($inv['invoice_no']) ?></h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Patient Info</h6>
                                            <table class="table table-sm">
                                                <tr><th>Name</th><td><?= e($inv['patient_name']) ?></td></tr>
                                                <tr><th>Code</th><td><?= e($inv['patient_code']) ?></td></tr>
                                                <tr><th>Phone</th><td><?= e($inv['phone']) ?></td></tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Invoice Info</h6>
                                            <table class="table table-sm">
                                                <tr><th>Invoice#</th><td><?= e($inv['invoice_no']) ?></td></tr>
                                                <tr><th>Date</th><td><?= $inv['invoice_date'] ?></td></tr>
                                                <tr><th>Method</th><td><?= ucfirst($inv['payment_method']) ?></td></tr>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <h6 class="mt-3">Payment Details</h6>
                                    <table class="table">
                                        <tr><td>Sub Total</td><td class="text-end"><?= money($inv['total_amount']) ?></td></tr>
                                        <?php if ($inv['discount'] > 0): ?>
                                            <tr><td>Discount</td><td class="text-end text-danger">- <?= money($inv['discount']) ?></td></tr>
                                        <?php endif; ?>
                                        <?php if ($inv['tax'] > 0): ?>
                                            <tr><td>Tax</td><td class="text-end">+ <?= money($inv['tax']) ?></td></tr>
                                        <?php endif; ?>
                                        <tr class="fw-bold"><td>Total</td><td class="text-end"><?= money($inv['total_amount'] + $inv['tax'] - $inv['discount']) ?></td></tr>
                                        <tr class="text-success"><td>Paid</td><td class="text-end"><?= money($inv['paid_amount']) ?></td></tr>
                                        <tr class="text-danger fw-bold fs-5"><td>Balance</td><td class="text-end"><?= money($inv['balance']) ?></td></tr>
                                    </table>
                                    
                                    <!-- Payment history -->
                                    <?php 
                                    $payments = $conn->query("SELECT * FROM payments WHERE invoice_id = {$inv['id']} ORDER BY id DESC");
                                    if ($payments->num_rows > 0): ?>
                                        <h6 class="mt-3">Payment History</h6>
                                        <table class="table table-sm">
                                            <?php while ($pay = $payments->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?= date('M d, Y h:i A', strtotime($pay['paid_at'])) ?></td>
                                                    <td><?= ucfirst($pay['payment_method']) ?></td>
                                                    <td class="text-end text-success"><?= money($pay['amount']) ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </table>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <button class="btn btn-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
                                    <button class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PAYMENT MODAL -->
                    <?php if ($inv['balance'] > 0): ?>
                    <div class="modal fade" id="payModal<?= $inv['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <input type="hidden" name="invoice_id" value="<?= $inv['id'] ?>">
                                    <div class="modal-header bg-success text-white">
                                        <h5><i class="bi bi-cash-coin"></i> Add Payment</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="alert alert-info">
                                            <b>Invoice:</b> <?= e($inv['invoice_no']) ?><br>
                                            <b>Patient:</b> <?= e($inv['patient_name']) ?><br>
                                            <b>Balance Due:</b> <?= money($inv['balance']) ?>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Amount (Rs.) *</label>
                                            <input type="number" step="0.01" name="pay_amount" 
                                                   class="form-control form-control-lg" 
                                                   max="<?= $inv['balance'] ?>" value="<?= $inv['balance'] ?>" required>
                                            <small class="text-muted">Maximum: <?= money($inv['balance']) ?></small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Payment Method</label>
                                            <select name="pay_method" class="form-select">
                                                <option value="cash">Cash</option>
                                                <option value="card">Card</option>
                                                <option value="online">Online</option>
                                                <option value="cheque">Cheque</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Reference No.</label>
                                            <input type="text" name="pay_reference" class="form-control" 
                                                   placeholder="Optional (cheque/transaction #)">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="add_payment" class="btn btn-success">
                                            <i class="bi bi-check-circle"></i> Record Payment
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- EDIT MODAL -->
                    <div class="modal fade" id="editModal<?= $inv['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <form method="POST">
                                    <input type="hidden" name="id" value="<?= $inv['id'] ?>">
                                    <div class="modal-header bg-warning">
                                        <h5><i class="bi bi-pencil"></i> Edit Invoice <?= e($inv['invoice_no']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Patient</label>
                                                <select name="patient_id" class="form-select" required>
                                                    <?php 
                                                    $patients->data_seek(0);
                                                    while ($p = $patients->fetch_assoc()): ?>
                                                        <option value="<?= $p['id'] ?>" <?= $inv['patient_id']==$p['id']?'selected':'' ?>>
                                                            <?= e($p['patient_code'] . ' - ' . $p['full_name']) ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Invoice Date</label>
                                                <input type="date" name="invoice_date" class="form-control" 
                                                       value="<?= e($inv['invoice_date']) ?>" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Total (Rs.)</label>
                                                <input type="number" step="0.01" name="total_amount" class="form-control" 
                                                       value="<?= $inv['total_amount'] ?>" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Discount</label>
                                                <input type="number" step="0.01" name="discount" class="form-control" 
                                                       value="<?= $inv['discount'] ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Tax</label>
                                                <input type="number" step="0.01" name="tax" class="form-control" 
                                                       value="<?= $inv['tax'] ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Paid</label>
                                                <input type="number" step="0.01" name="paid_amount" class="form-control" 
                                                       value="<?= $inv['paid_amount'] ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Payment Method</label>
                                                <select name="payment_method" class="form-select">
                                                    <?php foreach (['cash','card','online','cheque'] as $m): ?>
                                                        <option value="<?= $m ?>" <?= $inv['payment_method']==$m?'selected':'' ?>>
                                                            <?= ucfirst($m) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="update" class="btn btn-warning">
                                            <i class="bi bi-save"></i> Update
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <?php if ($total_pages > 1): ?>
    <div class="card-footer">
        <nav>
            <ul class="pagination mb-0 justify-content-center">
                <li class="page-item <?= $page<=1?'disabled':'' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$page-1])) ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
                <?php for ($i=1; $i<=$total_pages; $i++): ?>
                    <li class="page-item <?= $i==$page?'active':'' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$i])) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page>=$total_pages?'disabled':'' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$page+1])) ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================
     ADD INVOICE MODAL (Live calculation)
============================================ -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5><i class="bi bi-receipt"></i> Create New Invoice</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Patient *</label>
                            <select name="patient_id" class="form-select" required>
                                <option value="">-- Select Patient --</option>
                                <?php 
                                $patients->data_seek(0);
                                while ($p = $patients->fetch_assoc()): ?>
                                    <option value="<?= $p['id'] ?>">
                                        <?= e($p['patient_code'] . ' - ' . $p['full_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Invoice Date *</label>
                            <input type="date" name="invoice_date" class="form-control" 
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sub Total (Rs.) *</label>
                            <input type="number" step="0.01" name="total_amount" id="calc_total" 
                                   class="form-control" value="0" required oninput="calcTotal()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Discount (Rs.)</label>
                            <input type="number" step="0.01" name="discount" id="calc_disc" 
                                   class="form-control" value="0" oninput="calcTotal()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tax (Rs.)</label>
                            <input type="number" step="0.01" name="tax" id="calc_tax" 
                                   class="form-control" value="0" oninput="calcTotal()">
                        </div>
                        
                        <div class="col-12">
                            <div class="alert alert-info py-2 mb-0">
                                <div class="d-flex justify-content-between">
                                    <span><b>Grand Total:</b></span>
                                    <span id="grand_total" class="fw-bold">Rs. 0.00</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Paid Amount (Rs.)</label>
                            <input type="number" step="0.01" name="paid_amount" id="calc_paid" 
                                   class="form-control form-control-lg" value="0" oninput="calcTotal()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select form-select-lg">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="online">Online</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reference No.</label>
                            <input type="text" name="reference_no" class="form-control" 
                                   placeholder="Optional">
                        </div>
                        
                        <div class="col-12">
                            <div class="alert alert-warning py-2 mb-0 d-flex justify-content-between">
                                <span><b>Balance Due:</b></span>
                                <span id="balance_due" class="fw-bold text-danger">Rs. 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add" class="btn btn-primary btn-lg">
                        <i class="bi bi-save"></i> Create Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// FEATURE 30: Live calculation
function calcTotal() {
    const total = parseFloat(document.getElementById('calc_total').value) || 0;
    const disc  = parseFloat(document.getElementById('calc_disc').value)  || 0;
    const tax   = parseFloat(document.getElementById('calc_tax').value)   || 0;
    const paid  = parseFloat(document.getElementById('calc_paid').value)  || 0;
    
    const grand = total + tax - disc;
    const balance = grand - paid;
    
    document.getElementById('grand_total').innerText = 'Rs. ' + grand.toFixed(2);
    document.getElementById('balance_due').innerText = 'Rs. ' + balance.toFixed(2);
    document.getElementById('balance_due').className = 
        balance > 0 ? 'fw-bold text-danger' : 'fw-bold text-success';
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>