<?php
// ============================================
// HELPER FUNCTIONS
// ============================================

// Get user by ID
function getUser($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Count rows
function countRows($conn, $table, $where = "1") {
    $result = $conn->query("SELECT COUNT(*) c FROM $table WHERE $where");
    return $result ? $result->fetch_assoc()['c'] : 0;
}

// Sum column
function sumColumn($conn, $table, $column, $where = "1") {
    $result = $conn->query("SELECT IFNULL(SUM($column),0) s FROM $table WHERE $where");
    return $result ? $result->fetch_assoc()['s'] : 0;
}

// Log activity
function logActivity($conn, $user_id, $action, $description = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, description, ip_address) VALUES (?,?,?,?)");
    $stmt->bind_param("isss", $user_id, $action, $description, $ip);
    $stmt->execute();
}

// Send notification
function addNotification($conn, $user_id, $title, $message, $type = 'info') {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)");
    $stmt->bind_param("isss", $user_id, $title, $message, $type);
    $stmt->execute();
}

// Format money
function money($amount) {
    return 'Rs. ' . number_format((float)$amount, 2);
}

// Time ago
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M d, Y', $time);
}

// Generate invoice number
function generateInvoiceNo() {
    return 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
}

// Get patient code
function generatePatientCode($conn) {
    $result = $conn->query("SELECT MAX(id) m FROM patients");
    $next = ($result->fetch_assoc()['m'] ?? 0) + 1;
    return 'P' . str_pad($next, 4, '0', STR_PAD_LEFT);
}

// Sanitize
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Get today date
function today() {
    return date('Y-m-d');
}

// Get age from DOB
function calculateAge($dob) {
    if (!$dob) return '-';
    $birth = new DateTime($dob);
    $now = new DateTime();
    return $birth->diff($now)->y;
}
?>