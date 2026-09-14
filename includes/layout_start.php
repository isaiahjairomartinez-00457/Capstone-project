<?php
declare(strict_types=1);
/** @var string $pageTitle */
/** @var string $navActive */
if (!isset($pageTitle)) {
    $pageTitle = APP_NAME;
}
if (!isset($navActive)) {
    $navActive = '';
}
if (!isset($extraScripts)) {
    $extraScripts = '';
}
$cu = tg_current_user();
$initials = 'AD';
if (($cu['fullName'] ?? '') !== '') {
    $parts = preg_split('/\s+/', trim($cu['fullName']));
    if (is_array($parts) && count($parts) >= 2) {
        $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
    } else {
        $initials = strtoupper(substr($cu['fullName'], 0, 2));
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?> · <?= h(APP_BRAND) ?></title>
    <?php $assetV = (string)@filemtime(__DIR__ . '/../assets/css/app.css'); ?>
    <link rel="stylesheet" href="assets/css/app.css?v=<?= h($assetV) ?>">
</head>
<body class="app-body">
<button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">&#9776;</button>
<div class="app-shell">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <div class="app-main">
        <?php require __DIR__ . '/partials/topbar.php'; ?>
        <main class="app-content">
