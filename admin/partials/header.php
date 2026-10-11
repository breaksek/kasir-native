<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title><?= $pageTitle ?? 'Admin' ?> - Aplikasi Kasir</title>
<link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
<div class="admin-layout">