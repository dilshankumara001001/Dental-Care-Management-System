<?php
require_once __DIR__ . '/../../config/database.php';

$id = (int)($_GET['id'] ?? 0);
$p = $conn->query("SELECT p.*, pt.full_name patient_name, pt.patient_code, pt.age, pt.gender, pt.phone,
    u.full_name doctor_name FROM prescriptions p 
    LEFT JOIN patients pt ON p.patient_id = pt.id 
    LEFT JOIN users u ON p.doctor_id = u.id 
    WHERE p.id = $id")->fetch_assoc();

if (!$p) die('Prescription not found');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Prescription <?= htmlspecialchars($p['prescription_no']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f4f6f9; padding: 20px; }
        .rx { max-width: 800px; margin: auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
        .rx-header { border-bottom: 3px double #333; padding-bottom: 16px; margin-bottom: 24px; text-align: center; }
        .rx-symbol { font-size: 3rem; color: #667eea; font-weight: 700; }
        @media print { body { background: white; padding: 0; } .rx { box-shadow: none; } .no-print { display: none !important; } }
    </style>
</head>
<body>

<div class="no-print text-center mb-3">
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
    <button class="btn btn-secondary" onclick="window.close()">Close</button>
</div>

<div class="rx">
    <div class="rx-header">
        <div class="rx-symbol">℞</div>
        <h2>DENTAL CARE SYSTEM</h2>
        <p class="mb-0 text-muted">123 Main Street, Colombo | Tel: +94 11 234 5678</p>
    </div>
    
    <div class="row mb-4">
        <div class="col-6">
            <b>Patient:</b> <?= htmlspecialchars($p['patient_name']) ?><br>
            <b>Code:</b> <?= htmlspecialchars($p['patient_code']) ?><br>
            <b>Age/Gender:</b> <?= $p['age'] ?>Y / <?= ucfirst($p['gender']) ?><br>
            <b>Phone:</b> <?= htmlspecialchars($p['phone']) ?>
        </div>
        <div class="col-6 text-end">
            <b>Rx No:</b> <?= htmlspecialchars($p['prescription_no']) ?><br>
            <b>Date:</b> <?= date('F d, Y', strtotime($p['prescription_date'])) ?><br>
            <b>Doctor:</b> Dr. <?= htmlspecialchars($p['doctor_name']) ?>
        </div>
    </div>
    
    <h5>Diagnosis</h5>
    <p><?= nl2br(htmlspecialchars($p['diagnosis'])) ?></p>
    
    <h5 class="mt-4">℞ Medicines</h5>
    <pre style="font-family: 'Georgia', serif; font-size: 1rem; background: #f8f9fa; padding: 16px; border-left: 4px solid #667eea;"><?= htmlspecialchars($p['medicines']) ?></pre>
    
    <h5 class="mt-4">Instructions</h5>
    <p><?= nl2br(htmlspecialchars($p['instructions'])) ?></p>
    
    <?php if ($p['next_visit']): ?>
        <div style="background: #e7f3ff; padding: 12px; border-radius: 6px; margin-top: 20px;">
            <b>Next Visit:</b> <?= date('F d, Y', strtotime($p['next_visit'])) ?>
        </div>
    <?php endif; ?>
    
    <div class="mt-5 pt-4 text-end">
        <div style="border-top: 2px solid #333; width: 200px; display: inline-block;"></div>
        <p class="mt-1 mb-0"><b>Dr. <?= htmlspecialchars($p['doctor_name']) ?></b></p>
        <small class="text-muted">Signature</small>
    </div>
</div>

</body>
</html>