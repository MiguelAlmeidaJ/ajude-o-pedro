<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$token = preg_replace('/[^a-f0-9]/i', '', (string) ($_GET['pedido'] ?? ''));
$order = $token ? order_by_token($token) : null;

if (!$order) {
    http_response_code(404);
    exit('Pedido não encontrado.');
}

$numbers = order_numbers((int) $order['id']);
$txid = 'PEDRO' . str_pad((string) $order['id'], 8, '0', STR_PAD_LEFT);
$normalizedPixKey = pix_normalize_key((string) $order['pix_key']);
$pix = ($order['status'] === 'pending' && $normalizedPixKey !== '')
    ? pix_payload($normalizedPixKey, $order['pix_receiver_name'], $order['pix_receiver_city'], (float) $order['total_amount'], $txid)
    : '';

$pageTitle = 'Pagamento via Pix • Ajude o Pedro';
$campaignForHeader = null;
require __DIR__ . '/partials/header.php';
?>
<main class="container py-5">
    <div class="payment-card">
        <div class="soft-card p-4 p-md-5 text-center">
            <?php if ($order['status'] === 'pending'): ?>
                <?php if (trim((string) $order['pix_key']) === ''): ?>
                    <div class="alert alert-warning text-start">O Pix desta campanha ainda não foi configurado. Entre em contato com os responsáveis pela campanha.</div>
                <?php else: ?>
                    <span class="section-kicker">Pagamento por Pix</span>
                    <h1 class="h2 fw-bold mt-2">Finalize sua participação</h1>
                    <p class="text-secondary">Seu total é <strong><?= money($order['total_amount']) ?></strong>. Seus números ficarão reservados até a confirmação ou o cancelamento manual do pagamento.</p>

                    <div class="qr-box my-4" data-qr-code aria-label="QR Code Pix"></div>

                    <div class="d-grid gap-2 mb-3">
                        <button class="btn btn-primary btn-lg" type="button" data-copy-pix><i class="bi bi-copy me-2"></i>Copiar Pix Copia e Cola</button>
                        <button class="btn btn-outline-secondary" type="button" data-copy-key><i class="bi bi-key me-2"></i>Copiar chave Pix</button>
                    </div>
                    <textarea class="form-control pix-code text-start" rows="4" readonly data-pix-code><?= e($pix) ?></textarea>

                    <div class="alert alert-info text-start mt-4 mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Depois do pagamento, seus números continuam reservados até a equipe conferir o Pix e confirmar a participação no painel.
                    </div>
                <?php endif; ?>
            <?php elseif ($order['status'] === 'paid'): ?>
                <i class="bi bi-check-circle-fill text-success display-3"></i>
                <h1 class="h2 fw-bold mt-3">Pagamento confirmado!</h1>
                <p class="text-secondary">Obrigado por ajudar o Pedro. Seus números estão confirmados na rifa.</p>
            <?php else: ?>
                <i class="bi bi-clock-history text-secondary display-3"></i>
                <h1 class="h2 fw-bold mt-3">Esta reserva não está mais ativa.</h1>
                <p class="text-secondary">Você pode voltar à campanha e escolher novos números.</p>
            <?php endif; ?>

            <div class="border-top mt-4 pt-4 text-start">
                <div class="small text-secondary">Participante</div>
                <div class="fw-semibold"><?= e($order['customer_name']) ?></div>
                <div class="small text-secondary mt-3">Números</div>
                <div class="selected-list mt-2">
                    <?php foreach ($numbers as $number): ?>
                        <span class="selected-pill">#<?= e(str_pad((string) $number, 3, '0', STR_PAD_LEFT)) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-center flex-wrap mt-4">
                <a class="btn btn-outline-primary" href="<?= e(url('/status.php?pedido=' . urlencode($token))) ?>">Ver status</a>
                <a class="btn btn-light border" href="<?= e(url(campaign_path((string) $order['campaign_slug']))) ?>">Voltar à rifa</a>
            </div>
        </div>
    </div>
</main>

<?php if ($order['status'] === 'pending' && $pix !== ''): ?>
<?php
$qrcodeVersion = @filemtime(__DIR__ . '/assets/vendor/qrcode.min.js') ?: '1';
$pixJsVersion = @filemtime(__DIR__ . '/assets/js/pix-payment.js') ?: '1';
?>
<script src="<?= e(url('/assets/vendor/qrcode.min.js')) ?>?v=<?= e((string) $qrcodeVersion) ?>"></script>
<script src="<?= e(url('/assets/js/pix-payment.js')) ?>?v=<?= e((string) $pixJsVersion) ?>"></script>
<script>
PixPayment.init({
    payload: <?= json_encode($pix, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    key: <?= json_encode($normalizedPixKey, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
});
</script>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>