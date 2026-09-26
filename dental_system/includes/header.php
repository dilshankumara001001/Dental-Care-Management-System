<?php
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in (except on login page)
$current_page = basename($_SERVER['PHP_SELF']);
$public_pages = ['index.php', 'login.php'];

if (!in_array($current_page, $public_pages) && !isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title : 'Dashboard' ?> - <?= SITE_NAME ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<div class="d-flex" id="wrapper">