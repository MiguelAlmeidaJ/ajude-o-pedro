<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/rifa/');
}

verify_csrf();

$campaignId = (int) ($_POST['campaign_id'] ?? 0);
$name = trim((string) ($_POST['name'] ?? ''));
$phone = normalize_phone((string) ($_POST['phone'] ?? ''));
$email = trim((string) ($_POST['email'] ?? '')) ?: null;
$numbers = $_POST['numbers'] ?? [];

if ($name === '' || strlen($name) < 2 || strlen($phone) < 10) {
    flash('danger', 'Informe seu nome e um WhatsApp válido para continuar.');
    redirect('/rifa/#numeros');
}

if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('danger', 'O e-mail informado não é válido.');
    redirect('/rifa/#numeros');
}

try {
    cleanup_expired_reservations($campaignId);
    $order = reserve_numbers($campaignId, is_array($numbers) ? $numbers : [], $name, $phone, $email);
    redirect('/pagamento.php?pedido=' . urlencode($order['token']));
} catch (Throwable $e) {
    flash('warning', $e->getMessage());
    redirect('/rifa/#numeros');
}