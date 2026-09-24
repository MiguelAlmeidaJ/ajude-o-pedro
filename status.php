<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$token = preg_replace('/[^a-f0-9]/i', '', (string) ($_GET['pedido'] ?? ''));
$order = $token ? order_by_token($token) : null;

if (!$order) {
    http_response_code(404);
    exit('Pedido não encontrado.');
}

cleanup_expired_reservations((int) $order['campaign_id']);
$order = order_by_token($token);
$numbers = order_numbers((int) $order['id']);

$labels = [
    'pending' => ['warning', 'Aguardando confirmação do Pix', 'clock-history'],
    'paid' => ['success', 'Pagamento confirmado', 'check-circle-fill'],
    'cancelled' => ['secondary', 'Reserva cancelada', 'x-circle'],
    'expired' => ['secondary', 'Reserva expirada', 'hourglass-bottom'],
];
[$color, $label, $icon] = $labels[$order['status']] ?? ['secondary', 'Status desconhecido', 'question-circle'];

$pageTitle = 'Status da participação • Ajude o Pedro';
$campaignForHeader = null;
require __DIR__ . '/partials/header.php';
?>
<main class="container py-5" style="max-width:760px">
    <div class="soft-card p-4 p-md-5 text-center">
        <i class="bi bi-<?= e($icon) ?> text-<?= e($color) ?> display-4"></i>
        <h1 class="h3 fw-bold mt-3"><?= e($label) ?></h1>
        <p class="text-secondary">Pedido #<?= (int) $order['id'] ?> • <?= money($order['total_amount']) ?></p>

        <div class="selected-list justify-content-center my-4">
            <?php foreach ($numbers as $number): ?>
                <span class="selected-pill">#<?= e(str_pad((string) $number, 3, '0', STR_PAD_LEFT)) ?></span>
            <?php endforeach; ?>
        </div>

        <?php if ($order['status'] === 'pending'): ?>
            <a class="btn btn-primary" href="<?= e(url('/pagamento.php?pedido=' . urlencode($token))) ?>">Voltar ao pagamento</a>
        <?php else: ?>
            <a class="btn btn-outline-primary" href="<?= e(url('/rifa/')) ?>">Voltar à rifa</a>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>