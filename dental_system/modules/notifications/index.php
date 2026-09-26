<?php
$page_title = 'Notifications';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$msg = '';

// Send notification (mock)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
    $stmt = $conn->prepare("INSERT INTO notifications_log (type, recipient, subject, message, status, sent_at) VALUES (?,?,?,?,?,NOW())");
    $status = 'sent';
    $stmt->bind_param("sssss", $_POST['type'], $_POST['recipient'], $_POST['subject'], $_POST['message'], $status);
    if ($stmt->execute()) {
        $msg = '<div class="alert alert-success">Notification sent successfully! ✅</div>';
    }
}

$logs = $conn->query("SELECT * FROM notifications_log ORDER BY id DESC LIMIT 50");
$patients = $conn->query("SELECT id, full_name, phone, email FROM patients WHERE status='active' ORDER BY full_name");
?>

<?= $msg ?>

<div class="d-flex justify-content-between mb-3">
    <h4 class="mb-0"><i class="bi bi-bell-fill"></i> Notifications (SMS / Email)</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sendModal">
        <i class="bi bi-send-fill"></i> Send Notification
    </button>
</div>

<!-- Quick templates -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3 hover-lift" style="cursor:pointer;" onclick="quickTemplate('appointment')">
            <i class="bi bi-calendar-check text-primary" style="font-size:30px;"></i>
            <h6 class="mt-2">Appointment Reminder</h6>
            <small class="text-muted">Send SMS reminder for upcoming appointment</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 hover-lift" style="cursor:pointer;" onclick="quickTemplate('payment')">
            <i class="bi bi-cash-coin text-success" style="font-size:30px;"></i>
            <h6 class="mt-2">Payment Reminder</h6>
            <small class="text-muted">Notify pending balance</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 hover-lift" style="cursor:pointer;" onclick="quickTemplate('followup')">
            <i class="bi bi-heart-pulse text-danger" style="font-size:30px;"></i>
            <h6 class="mt-2">Follow-up Message</h6>
            <small class="text-muted">Post-treatment checkup</small>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-clock-history"></i> Recent Notifications</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Recipient</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Sent</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($logs->num_rows === 0): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No notifications sent yet</td></tr>
                <?php endif; ?>
                <?php while ($l = $logs->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <span class="badge bg-<?= $l['type']=='sms'?'primary':'info' ?>">
                                <i class="bi bi-<?= $l['type']=='sms'?'phone':'envelope' ?>"></i>
                                <?= strtoupper($l['type']) ?>
                            </span>
                        </td>
                        <td><?= e($l['recipient']) ?></td>
                        <td><?= e(substr($l['subject'] ?? $l['message'], 0, 50)) ?></td>
                        <td><span class="badge bg-<?= $l['status']=='sent'?'success':'warning' ?>"><?= ucfirst($l['status']) ?></span></td>
                        <td><?= timeAgo($l['created_at']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Send Modal -->
<div class="modal fade" id="sendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5><i class="bi bi-send"></i> Send Notification</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" required>
                            <option value="sms">📱 SMS</option>
                            <option value="email">📧 Email</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Recipient</label>
                        <input type="text" name="recipient" id="recipient" class="form-control" required 
                            placeholder="Phone number or email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quick select patient</label>
                        <select class="form-select" onchange="selectPatient(this)">
                            <option value="">-- Patient --</option>
                            <?php 
                            $patients->data_seek(0);
                            while ($p = $patients->fetch_assoc()): ?>
                                <option value="<?= $p['id'] ?>" data-phone="<?= e($p['phone']) ?>" data-email="<?= e($p['email']) ?>">
                                    <?= e($p['full_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" id="subject" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message *</label>
                        <textarea name="message" id="message" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="send" class="btn btn-primary"><i class="bi bi-send"></i> Send</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function selectPatient(sel) {
    const opt = sel.options[sel.selectedIndex];
    const type = document.querySelector('select[name="type"]').value;
    document.getElementById('recipient').value = type === 'sms' ? opt.dataset.phone : opt.dataset.email;
}

function quickTemplate(type) {
    const templates = {
        appointment: { subject: 'Appointment Reminder', message: 'Dear Patient, this is a reminder of your dental appointment. Please arrive 10 minutes early.' },
        payment: { subject: 'Payment Reminder', message: 'Dear Patient, you have a pending payment on your account. Please settle at your earliest convenience.' },
        followup: { subject: 'Post-Treatment Follow-up', message: 'Dear Patient, we hope you are recovering well. Please contact us if you experience any discomfort.' }
    };
    const t = templates[type];
    document.getElementById('subject').value = t.subject;
    document.getElementById('message').value = t.message;
    new bootstrap.Modal(document.getElementById('sendModal')).show();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>