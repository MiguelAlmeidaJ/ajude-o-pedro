<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require_login();

$campaigns = db()->query(
    "SELECT c.*,
        (SELECT COUNT(*) FROM raffle_numbers rn WHERE rn.campaign_id=c.id AND rn.status='paid') sold,
        (SELECT COALESCE(SUM(o.total_amount),0) FROM orders o WHERE o.campaign_id=c.id AND o.status='paid') raised
     FROM campaigns c
     ORDER BY c.id DESC"
)->fetchAll();

$pageTitle = 'Campanhas • Painel';
require __DIR__ . '/partials/header.php';
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Campanhas</h1>
        <p class="text-secondary mb-0">DEV cria novas campanhas; ADMIN pode visualizar e ajustar as existentes.</p>
    </div>
    <?php if (is_dev()): ?>
        <a class="btn btn-primary" href="<?= e(url('/admin/campanha.php')) ?>"><i class="bi bi-plus-lg me-2"></i>Nova campanha</a>
    <?php endif; ?>
</div>

<div class="row g-3">
    <?php if (!$campaigns): ?>
        <div class="col-12"><div class="admin-card p-5 text-center text-secondary">Nenhuma campanha criada.</div></div>
    <?php endif; ?>
    <?php foreach ($campaigns as $campaign): ?>
    <div class="col-md-6 col-xl-4">
        <div class="admin-card p-4 h-100">
            <div class="d-flex justify-content-between gap-3 mb-3">
                <div>
                    <div class="small text-secondary">#<?= (int) $campaign['id'] ?></div>
                    <h2 class="h5 fw-bold mb-1"><?= e($campaign['title']) ?></h2>
                </div>
                <?php $color=['active'=>'success','draft'=>'secondary','closed'=>'dark'][$campaign['status']] ?? 'secondary'; ?>
                <span class="badge text-bg-<?= e($color) ?> align-self-start"><?= e($campaign['status']) ?></span>
            </div>
            <div class="row g-2 small mb-4">
                <div class="col-6"><div class="text-secondary">Confirmados</div><strong><?= (int) $campaign['sold'] ?></strong></div>
                <div class="col-6"><div class="text-secondary">Arrecadado</div><strong><?= money($campaign['raised']) ?></strong></div>
                <div class="col-6"><div class="text-secondary">Valor/número</div><strong><?= money($campaign['number_price']) ?></strong></div>
                <div class="col-6"><div class="text-secondary">Total de números</div><strong><?= (int) $campaign['total_numbers'] ?></strong></div>
            </div>
            <div class="d-grid gap-2">
                <a class="btn btn-outline-primary" href="<?= e(url('/admin/campanha.php?id=' . (int) $campaign['id'])) ?>">
                    <i class="bi bi-pencil-square me-2"></i>Abrir campanha
                </a>
                <?php if ($campaign['status'] === 'active'): ?>
                    <a class="btn btn-light border" target="_blank" rel="noopener" href="<?= e(url(campaign_path($campaign))) ?>">
                        <i class="bi bi-box-arrow-up-right me-2"></i>Ver rifa pública
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>