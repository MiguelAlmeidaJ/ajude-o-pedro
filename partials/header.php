<?php
$campaignForHeader = $campaignForHeader ?? null;
$cssVersion = @filemtime(__DIR__ . '/../assets/css/app.css') ?: '1';
?><!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d6efd">
    <meta name="description" content="Rifa solidária para ajudar o Pedro a conquistar seu respirador portátil.">
    <title><?= e($pageTitle ?? config('app.name')) ?></title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>?v=<?= e((string) $cssVersion) ?>">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
    <div class="container py-2">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= e(url('/')) ?>">
            <span class="brand-heart"><i class="bi bi-heart-fill"></i></span>
            Ajude o Pedro
        </a>
        <?php if ($campaignForHeader): ?>
            <a class="btn btn-primary rounded-pill px-4 d-none d-sm-inline-flex" href="#numeros">
                <i class="bi bi-ticket-perforated me-2"></i>Participar
            </a>
        <?php endif; ?>
    </div>
</nav>
<?php foreach (pull_flashes() as $flash): ?>
<div class="container mt-3">
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endforeach; ?>