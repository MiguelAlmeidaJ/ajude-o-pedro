<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$token = preg_replace('/[^a-f0-9]/i', '', (string) ($_GET['doacao'] ?? ''));
$donation = $token ? donation_by_token($token) : null;

if (!$donation) {
    http_response_code(404);
    exit('Doação não encontrada.');
}

$txid = 'DOACAO' . str_pad((string) $donation['id'], 8, '0', STR_PAD_LEFT);
$pix = ($donation['status'] === 'pending' && trim((string) $donation['pix_key']) !== '')
    ? pix_payload(
        $donation['pix_key'],
        $donation['pix_receiver_name'],
        $donation['pix_receiver_city'],
        (float) $donation['amount'],
        $txid
    )
    : '';

$pageTitle = 'Doação via Pix • ' . $donation['campaign_title'];
$campaignForHeader = null;
require __DIR__ . '/partials/header.php';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string) ($_SERVER['HTTP_HOST'] ?? ''));
$paymentPath = url('/doacao.php?doacao=' . urlencode($token));
$paymentLink = $host !== '' ? $scheme . '://' . $host . $paymentPath : $paymentPath;
?>
<main class="container py-5">
    <div class="payment-card">
        <div class="soft-card p-4 p-md-5 text-center">
            <?php if ($donation['status'] === 'pending'): ?>
                <?php if (trim((string) $donation['pix_key']) === ''): ?>
                    <div class="alert alert-warning text-start">O Pix desta campanha ainda não foi configurado.</div>
                <?php else: ?>
                    <span class="section-kicker">Doação direta</span>
                    <h1 class="h2 fw-bold mt-2">Obrigado por ajudar 💙</h1>
                    <p class="text-secondary">
                        Doação de <strong><?= money($donation['amount']) ?></strong> para
                        <strong><?= e($donation['campaign_title']) ?></strong>.
                    </p>

                    <div id="qrcode" class="qr-box my-4"></div>

                    <div class="d-grid gap-2 mb-3">
                        <button class="btn btn-primary btn-lg" type="button" data-copy-pix>
                            <i class="bi bi-copy me-2"></i>Copiar Pix Copia e Cola
                        </button>
                        <button class="btn btn-outline-secondary" type="button" data-copy-key>
                            <i class="bi bi-key me-2"></i>Copiar chave Pix
                        </button>
                    </div>

                    <textarea class="form-control pix-code text-start" rows="4" readonly data-pix-code><?= e($pix) ?></textarea>

                    <div class="border-top mt-4 pt-4 text-start">
                        <label class="form-label fw-semibold">Link deste pagamento</label>
                        <div class="input-group">
                            <input class="form-control" id="paymentLink" value="<?= e($paymentLink) ?>" readonly>
                            <button class="btn btn-outline-primary" type="button" data-copy-link>
                                <i class="bi bi-link-45deg me-1"></i>Copiar link
                            </button>
                        </div>
                        <div class="form-text">Você pode guardar ou compartilhar este link para abrir novamente o pagamento.</div>
                    </div>

                    <div class="alert alert-info text-start mt-4 mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Depois do Pix, a equipe confere o recebimento e confirma a doação no painel.
                    </div>
                <?php endif; ?>
            <?php elseif ($donation['status'] === 'paid'): ?>
                <i class="bi bi-check-circle-fill text-success display-3"></i>
                <h1 class="h2 fw-bold mt-3">Doação confirmada!</h1>
                <p class="text-secondary mb-0">Muito obrigado por fazer parte dessa campanha.</p>
            <?php else: ?>
                <i class="bi bi-x-circle text-secondary display-3"></i>
                <h1 class="h2 fw-bold mt-3">Esta doação foi cancelada.</h1>
            <?php endif; ?>

            <div class="border-top mt-4 pt-4 text-start">
                <div class="small text-secondary">Doador</div>
                <div class="fw-semibold"><?= e($donation['donor_name']) ?></div>
                <div class="small text-secondary mt-3">Valor</div>
                <div class="fw-bold"><?= money($donation['amount']) ?></div>
            </div>

            <div class="mt-4">
                <a class="btn btn-light border" href="<?= e(url(campaign_path((string) $donation['campaign_slug']))) ?>">
                    Voltar à campanha
                </a>
            </div>
        </div>
    </div>
</main>

<?php if ($donation['status'] === 'pending' && $pix !== ''): ?>
<script src="<?= e(url('/assets/vendor/qrcode.min.js')) ?>"></script>
<script src="<?= e(url('/assets/js/pix-payment.js')) ?>"></script>
<script>
PixPayment.init({
    payload: <?= json_encode($pix, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    key: <?= json_encode($donation['pix_key'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    link: <?= json_encode($paymentLink, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
});
</script>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
