<?php
require_login();
$user = current_user();
$current = basename($_SERVER['PHP_SELF']);
$cssVersion = @filemtime(__DIR__ . '/../../assets/css/app.css') ?: '1';
?><!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f1e33">
    <title><?= e($pageTitle ?? 'Painel • Ajude o Pedro') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>?v=<?= e((string) $cssVersion) ?>">
</head>
<body>
<div class="admin-mobile-bar d-lg-none">
    <a class="admin-mobile-brand" href="<?= e(url('/admin/')) ?>">
        <span class="brand-heart"><i class="bi bi-heart-fill"></i></span>
        <span><strong>Ajude o Pedro</strong><small>Painel</small></span>
    </a>
    <button
        class="btn admin-menu-button"
        type="button"
        data-bs-toggle="offcanvas"
        data-bs-target="#adminMobileMenu"
        aria-controls="adminMobileMenu"
        aria-label="Abrir menu"
    >
        <i class="bi bi-list"></i>
    </button>
</div>

<div class="offcanvas offcanvas-start admin-mobile-menu d-lg-none" tabindex="-1" id="adminMobileMenu" aria-labelledby="adminMobileMenuLabel">
    <div class="offcanvas-header">
        <div class="d-flex align-items-center gap-2">
            <span class="brand-heart"><i class="bi bi-heart-fill"></i></span>
            <div>
                <div class="fw-bold" id="adminMobileMenuLabel">Ajude o Pedro</div>
                <div class="small text-white-50">Painel administrativo</div>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
    </div>
    <div class="offcanvas-body">
        <nav class="admin-mobile-nav">
            <a class="admin-link <?= $current === 'index.php' ? 'active' : '' ?>" href="<?= e(url('/admin/')) ?>"><i class="bi bi-grid"></i>Visão geral</a>
            <a class="admin-link <?= in_array($current, ['campanhas.php','campanha.php'], true) ? 'active' : '' ?>" href="<?= e(url('/admin/campanhas.php')) ?>"><i class="bi bi-megaphone"></i>Campanhas</a>
            <a class="admin-link <?= $current === 'pedidos.php' ? 'active' : '' ?>" href="<?= e(url('/admin/pedidos.php')) ?>"><i class="bi bi-receipt"></i>Participações</a>
            <a class="admin-link <?= $current === 'doacoes.php' ? 'active' : '' ?>" href="<?= e(url('/admin/doacoes.php')) ?>"><i class="bi bi-cash-heart"></i>Doações</a>
            <?php if (is_dev()): ?>
                <a class="admin-link <?= $current === 'usuarios.php' ? 'active' : '' ?>" href="<?= e(url('/admin/usuarios.php')) ?>"><i class="bi bi-people"></i>Usuários</a>
            <?php endif; ?>
            <a class="admin-link" href="<?= e(url('/')) ?>" target="_blank"><i class="bi bi-box-arrow-up-right"></i>Ver site</a>
        </nav>

        <div class="admin-mobile-account">
            <div class="small text-white-50">Conectado como</div>
            <div class="fw-semibold"><?= e($user['name']) ?></div>
            <div class="small text-white-50 mb-3"><?= e(strtoupper($user['role'])) ?></div>
            <a class="btn btn-outline-light btn-sm w-100" href="<?= e(url('/admin/logout.php')) ?>"><i class="bi bi-box-arrow-right me-2"></i>Sair</a>
        </div>
    </div>
</div>

<div class="admin-shell">
    <aside class="admin-sidebar d-none d-lg-block">
        <div class="d-flex align-items-center gap-2 mb-4">
            <span class="brand-heart"><i class="bi bi-heart-fill"></i></span>
            <div><div class="fw-bold">Ajude o Pedro</div><div class="small text-white-50">Painel administrativo</div></div>
        </div>
        <nav>
            <a class="admin-link <?= $current === 'index.php' ? 'active' : '' ?>" href="<?= e(url('/admin/')) ?>"><i class="bi bi-grid"></i>Visão geral</a>
            <a class="admin-link <?= in_array($current, ['campanhas.php','campanha.php'], true) ? 'active' : '' ?>" href="<?= e(url('/admin/campanhas.php')) ?>"><i class="bi bi-megaphone"></i>Campanhas</a>
            <a class="admin-link <?= $current === 'pedidos.php' ? 'active' : '' ?>" href="<?= e(url('/admin/pedidos.php')) ?>"><i class="bi bi-receipt"></i>Participações</a>
            <a class="admin-link <?= $current === 'doacoes.php' ? 'active' : '' ?>" href="<?= e(url('/admin/doacoes.php')) ?>"><i class="bi bi-cash-heart"></i>Doações</a>
            <?php if (is_dev()): ?>
            <a class="admin-link <?= $current === 'usuarios.php' ? 'active' : '' ?>" href="<?= e(url('/admin/usuarios.php')) ?>"><i class="bi bi-people"></i>Usuários</a>
            <?php endif; ?>
            <a class="admin-link" href="<?= e(url('/')) ?>" target="_blank"><i class="bi bi-box-arrow-up-right"></i>Ver site</a>
        </nav>
        <hr class="border-light opacity-25 my-4">
        <div class="small text-white-50">Conectado como</div>
        <div class="fw-semibold"><?= e($user['name']) ?></div>
        <div class="small text-white-50 mb-3"><?= e(strtoupper($user['role'])) ?></div>
        <a class="btn btn-outline-light btn-sm w-100" href="<?= e(url('/admin/logout.php')) ?>"><i class="bi bi-box-arrow-right me-2"></i>Sair</a>
    </aside>

    <main class="admin-main">
        <div class="container-fluid p-3 p-md-4 p-xl-5">
            <?php foreach (pull_flashes() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endforeach; ?>