<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require_login();

cleanup_expired_reservations();

$campaignCount = (int) db()->query("SELECT COUNT(*) FROM campaigns")->fetchColumn();
$orderCount = (int) db()->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$paidOrdersTotal = (float) db()->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status='paid'")->fetchColumn();
$paidDonationsTotal = (float) db()->query("SELECT COALESCE(SUM(amount),0) FROM donations WHERE status='paid'")->fetchColumn();
$paidTotal = $paidOrdersTotal + $paidDonationsTotal;
$pendingCount = (int) db()->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$donationCount = (int) db()->query("SELECT COUNT(*) FROM donations")->fetchColumn();
$pendingDonations = (int) db()->query("SELECT COUNT(*) FROM donations WHERE status='pending'")->fetchColumn();

$recent = db()->query(
    "SELECT o.id,o.customer_name,o.total_amount,o.status,o.created_at,c.title campaign_title
     FROM orders o
     JOIN campaigns c ON c.id=o.campaign_id
     ORDER BY o.id DESC
     LIMIT 8"
)->fetchAll();

$pageTitle = 'Visão geral • Painel';
require __DIR__ . '/partials/header.php';
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Visão geral</h1>
        <p class="text-secondary mb-0">Acompanhe a campanha e as participações.</p>
    </div>
    <?php if (is_dev()): ?>
        <a class="btn btn-primary" href="<?= e(url('/admin/campanha.php')) ?>"><i class="bi bi-plus-lg me-2"></i>Nova campanha</a>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="admin-card p-4 h-100">
            <div class="small text-secondary">Campanhas</div>
            <div class="display-6 fw-bold"><?= $campaignCount ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-card p-4 h-100">
            <div class="small text-secondary">Participações</div>
            <div class="display-6 fw-bold"><?= $orderCount ?></div>
            <div class="small text-secondary mt-1"><?= $donationCount ?> doação(ões)</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-card p-4 h-100">
            <div class="small text-secondary">Arrecadado confirmado</div>
            <div class="h3 fw-bold mb-0"><?= money($paidTotal) ?></div>
            <div class="small text-secondary mt-1">Rifa + doações</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-card p-4 h-100">
            <div class="small text-secondary">Aguardando</div>
            <div class="display-6 fw-bold"><?= $pendingCount + $pendingDonations ?></div>
            <div class="small text-secondary mt-1"><?= $pendingCount ?> rifa • <?= $pendingDonations ?> doação</div>
        </div>
    </div>
</div>

<div class="admin-card overflow-hidden">
    <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
        <h2 class="h5 fw-bold mb-0">Participações recentes</h2>
        <a href="<?= e(url('/admin/pedidos.php')) ?>" class="btn btn-sm btn-outline-primary">Ver todas</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>#</th><th>Participante</th><th>Campanha</th><th>Valor</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (!$recent): ?>
                <tr><td colspan="5" class="text-center text-secondary py-5">Ainda não há participações.</td></tr>
            <?php endif; ?>
            <?php foreach ($recent as $item): ?>
                <tr>
                    <td><?= (int) $item['id'] ?></td>
                    <td><?= e($item['customer_name']) ?></td>
                    <td><?= e($item['campaign_title']) ?></td>
                    <td><?= money($item['total_amount']) ?></td>
                    <td>
                        <?php
                        $badge = ['paid'=>'success','pending'=>'warning','cancelled'=>'secondary','expired'=>'secondary'][$item['status']] ?? 'secondary';
                        ?>
                        <span class="badge text-bg-<?= e($badge) ?>"><?= e($item['status']) ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>