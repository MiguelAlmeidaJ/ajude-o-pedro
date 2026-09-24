<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require_login();
cleanup_expired_reservations();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'paid') {
            change_order_status($orderId, 'paid');
            flash('success', 'Pagamento confirmado e números marcados como pagos.');
        } elseif ($action === 'cancelled') {
            change_order_status($orderId, 'cancelled');
            flash('success', 'Reserva cancelada e números liberados.');
        }
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
    }
    redirect('/admin/pedidos.php');
}

$status = (string) ($_GET['status'] ?? '');
$campaignId = (int) ($_GET['campaign_id'] ?? 0);

$where = [];
$params = [];
if (in_array($status, ['pending','paid','cancelled','expired'], true)) {
    $where[] = 'o.status = ?';
    $params[] = $status;
}
if ($campaignId > 0) {
    $where[] = 'o.campaign_id = ?';
    $params[] = $campaignId;
}
$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$q = db()->prepare(
    "SELECT o.*,c.title campaign_title,
        GROUP_CONCAT(rn.number ORDER BY rn.number SEPARATOR ', ') numbers
     FROM orders o
     JOIN campaigns c ON c.id=o.campaign_id
     LEFT JOIN order_numbers onn ON onn.order_id=o.id
     LEFT JOIN raffle_numbers rn ON rn.id=onn.raffle_number_id
     $sqlWhere
     GROUP BY o.id
     ORDER BY o.id DESC
     LIMIT 300"
);
$q->execute($params);
$orders = $q->fetchAll();
$campaigns = db()->query("SELECT id,title FROM campaigns ORDER BY id DESC")->fetchAll();

$pageTitle = 'Participações • Painel';
require __DIR__ . '/partials/header.php';
?>
<div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Participações</h1>
        <p class="text-secondary mb-0">Confira o Pix e confirme manualmente as compras.</p>
    </div>
</div>

<div class="admin-card p-3 mb-4">
    <form class="row g-2 align-items-end" method="get">
        <div class="col-md-4">
            <label class="form-label small">Campanha</label>
            <select class="form-select" name="campaign_id">
                <option value="0">Todas</option>
                <?php foreach ($campaigns as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $campaignId===(int)$c['id']?'selected':'' ?>><?= e($c['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Status</label>
            <select class="form-select" name="status">
                <option value="">Todos</option>
                <?php foreach (['pending'=>'Aguardando','paid'=>'Pago','cancelled'=>'Cancelado','expired'=>'Expirado'] as $v=>$label): ?>
                    <option value="<?= e($v) ?>" <?= $status===$v?'selected':'' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-auto"><button class="btn btn-outline-primary">Filtrar</button></div>
    </form>
</div>

<div class="admin-card overflow-hidden">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>#</th><th>Participante</th><th>Números</th><th>Valor</th><th>Status</th><th>Data</th><th class="text-end">Ações</th></tr></thead>
            <tbody>
            <?php if (!$orders): ?><tr><td colspan="7" class="text-center text-secondary py-5">Nenhuma participação encontrada.</td></tr><?php endif; ?>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= (int) $order['id'] ?></td>
                <td>
                    <div class="fw-semibold"><?= e($order['customer_name']) ?></div>
                    <div class="small text-secondary"><?= e($order['customer_phone']) ?></div>
                </td>
                <td style="max-width:260px"><span class="small"><?= e((string) $order['numbers']) ?></span></td>
                <td><strong><?= money($order['total_amount']) ?></strong></td>
                <td>
                    <?php $badge=['pending'=>'warning','paid'=>'success','cancelled'=>'secondary','expired'=>'secondary'][$order['status']]??'secondary'; ?>
                    <span class="badge text-bg-<?= e($badge) ?>"><?= e($order['status']) ?></span>
                </td>
                <td><span class="small"><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></span></td>
                <td class="text-end">
                    <?php if ($order['status'] === 'pending'): ?>
                    <form method="post" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                        <input type="hidden" name="action" value="paid">
                        <button class="btn btn-sm btn-success" onclick="return confirm('Confirma que o Pix deste pedido foi recebido?')"><i class="bi bi-check2"></i> Confirmar</button>
                    </form>
                    <form method="post" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                        <input type="hidden" name="action" value="cancelled">
                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancelar esta reserva e liberar os números?')"><i class="bi bi-x-lg"></i></button>
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