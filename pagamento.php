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
$txid = 'PEDRO' . str_pad((string) $order['id'], 8, '0', STR_PAD_LEFT);
$pix = ($order['status'] === 'pending' && trim((string) $order['pix_key']) !== '')
    ? pix_payload($order['pix_key'], $order['pix_receiver_name'], $order['pix_receiver_city'], (float) $order['total_amount'], $txid)
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
                    <p class="text-secondary">Seu total é <strong><?= money($order['total_amount']) ?></strong>. A reserva vale até <strong><?= e(date('H:i', strtotime($order['reserved_until']))) ?></strong>.</p>

                    <div id="qrcode" class="qr-box my-4"></div>

                    <div class="d-grid gap-2 mb-3">
                        <button class="btn btn-primary btn-lg" type="button" id="copyPix"><i class="bi bi-copy me-2"></i>Copiar Pix Copia e Cola</button>
                        <button class="btn btn-outline-secondary" type="button" id="copyKey"><i class="bi bi-key me-2"></i>Copiar chave Pix</button>
                    </div>
                    <div class="pix-code text-start" id="pixCode"><?= e($pix) ?></div>

                    <div class="alert alert-info text-start mt-4 mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Depois do pagamento, a equipe confere o Pix e confirma seus números no painel.
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
                <a class="btn btn-light border" href="<?= e(url('/rifa/')) ?>">Voltar à rifa</a>
            </div>
        </div>
    </div>
</main>

<?php if ($order['status'] === 'pending' && $pix !== ''): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const pixPayload = <?= json_encode($pix, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
const pixKey = <?= json_encode($order['pix_key'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
new QRCode(document.getElementById('qrcode'), {text: pixPayload, width:240, height:240, correctLevel:QRCode.CorrectLevel.M});

document.getElementById('copyPix').addEventListener('click', async () => {
    await navigator.clipboard.writeText(pixPayload);
    document.getElementById('copyPix').innerHTML = '<i class="bi bi-check2 me-2"></i>Pix copiado';
});
document.getElementById('copyKey').addEventListener('click', async () => {
    await navigator.clipboard.writeText(pixKey);
    document.getElementById('copyKey').innerHTML = '<i class="bi bi-check2 me-2"></i>Chave copiada';
});
</script>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>