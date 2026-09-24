<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}

verify_csrf();

$campaignId = (int) ($_POST['campaign_id'] ?? 0);
$name = trim((string) ($_POST['name'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$rawAmount = trim((string) ($_POST['amount'] ?? '0'));
$amount = (float) str_replace(',', '.', $rawAmount);

$campaignQ = db()->prepare("SELECT id, slug, status FROM campaigns WHERE id = ? LIMIT 1");
$campaignQ->execute([$campaignId]);
$campaign = $campaignQ->fetch() ?: null;
$returnPath = $campaign ? campaign_path($campaign) . '#doar' : '/';

if ($name === '' || mb_strlen($name) < 2) {
    flash('danger', 'Informe seu nome para gerar a doação.');
    redirect($returnPath);
}

try {
    $donation = create_donation($campaignId, $name, $phone ?: null, $amount);
    redirect('/doacao.php?doacao=' . urlencode($donation['token']));
} catch (Throwable $e) {
    flash('warning', $e->getMessage());
    redirect($returnPath);
}
