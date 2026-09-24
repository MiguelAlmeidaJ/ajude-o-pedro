<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $donationId = (int) ($_POST['donation_id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'paid') {
            change_donation_status($donationId, 'paid');
            flash('success', 'Doação confirmada como recebida.');
        } elseif ($action === 'cancelled') {
            change_donation_status($donationId, 'cancelled');
            flash('success', 'Doação cancelada.');
        }
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
    }

    redirect('/admin/doacoes.php');
}

$status = (string) ($_GET['status'] ?? '');
$campaignId = (int) ($_GET['campaign_id'] ?? 0);

$where = [];
$params = [];

if (in_array($status, ['pending', 'paid', 'cancelled'], true)) {
    $where[] = 'd.status = ?';
    $params[] = $status;
}

if ($campaignId > 0) {
    $where[] = 'd.campaign_id = ?';
    $params[] = $campaignId;
}

$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$q = db()->prepare(
    "SELECT d.*, c.title campaign_title
     FROM donations d
     JOIN campaigns c ON c.id = d.campaign_id
     $sqlWhere
     ORDER BY d.id DESC
     LIMIT 300"
);
$q->execute($params);
$donations = $q->fetchAll();

$campaigns = db()->query("SELECT id,title FROM campaigns ORDER BY id DESC")->fetchAll();

$pageTitle = 'Doações • Painel';
require __DIR__ . '/partials/header.php';
?>
<div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Doações diretas</h1>
        <p class="text-secondary mb-0">Confira o Pix e confirme as contribuições feitas sem compra de números.</p>
    </div>
</div>

<div class="admin-card p-3 mb-4">
    <form class="row g-2 align-items-end" method="get">
        <div class="col-md-4">
            <label class="form-label small">Campanha</label>
            <select class="form-select" name="campaign_id">
                <option value="0">Todas</option>
                <?php foreach ($campaigns as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $campaignId === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Status</label>
            <select class="form-select" name="status">
                <option value="">Todos</option>
                <?php foreach (['pending'=>'Aguardando','paid'=>'Pago','cancelled'=>'Cancelado'] as $v=>$label): ?>
                    <option value="<?= e($v) ?>" <?= $status === $v ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-auto">
            <button class="btn btn-outline-primary">Filtrar</button>
        </div>
    </form>
</div>

<div class="admin-card overflow-hidden">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Doador</th>
                    <th>Campanha</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$donations): ?>
                <tr><td colspan="7" class="text-center text-secondary py-5">Nenhuma doação encontrada.</td></tr>
            <?php endif; ?>

            <?php foreach ($donations as $donation): ?>
                <tr>
                    <td><?= (int) $donation['id'] ?></td>
                    <td>
                        <div class="fw-semibold"><?= e($donation['donor_name']) ?></div>
                        <?php if ($donation['donor_phone']): ?>
                            <div class="small text-secondary"><?= e($donation['donor_phone']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><span class="small"><?= e($donation['campaign_title']) ?></span></td>
                    <td><strong><?= money($donation['amount']) ?></strong></td>
                    <td>
                        <?php $badge = ['pending'=>'warning','paid'=>'success','cancelled'=>'secondary'][$donation['status']] ?? 'secondary'; ?>
                        <span class="badge text-bg-<?= e($badge) ?>"><?= e($donation['status']) ?></span>
                    </td>
                    <td><span class="small"><?= e(date('d/m/Y H:i', strtotime($donation['created_at']))) ?></span></td>
                    <td class="text-end">
                        <?php if ($donation['status'] === 'pending'): ?>
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="donation_id" value="<?= (int) $donation['id'] ?>">
                                <input type="hidden" name="action" value="paid">
                                <button class="btn btn-sm btn-success" onclick="return confirm('Confirma que este Pix foi recebido?')">
                                    <i class="bi bi-check2"></i> Confirmar
                                </button>
                            </form>
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="donation_id" value="<?= (int) $donation['id'] ?>">
                                <input type="hidden" name="action" value="cancelled">
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancelar esta doação?')">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="text-secondary small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
