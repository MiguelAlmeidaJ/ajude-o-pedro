<?php
declare(strict_types=1);

function create_donation(int $campaignId, string $name, ?string $phone, float $amount): array
{
    if ($amount < 1 || $amount > 100000) {
        throw new RuntimeException('Informe um valor entre R$ 1,00 e R$ 100.000,00.');
    }

    $pdo = db();
    $q = $pdo->prepare("SELECT id,status FROM campaigns WHERE id = ? LIMIT 1");
    $q->execute([$campaignId]);
    $campaign = $q->fetch();

    if (!$campaign || $campaign['status'] !== 'active') {
        throw new RuntimeException('Esta campanha não está disponível para doações.');
    }

    $token = public_token();
    $insert = $pdo->prepare(
        "INSERT INTO donations (campaign_id, public_token, donor_name, donor_phone, amount, status)
         VALUES (?, ?, ?, ?, ?, 'pending')"
    );
    $insert->execute([
        $campaignId,
        $token,
        trim($name),
        $phone ? normalize_phone($phone) : null,
        $amount,
    ]);

    return [
        'id' => (int) $pdo->lastInsertId(),
        'token' => $token,
        'amount' => $amount,
    ];
}

function donation_by_token(string $token): ?array
{
    $q = db()->prepare(
        "SELECT d.*, c.title campaign_title, c.slug campaign_slug,
                c.pix_key, c.pix_receiver_name, c.pix_receiver_city
         FROM donations d
         JOIN campaigns c ON c.id = d.campaign_id
         WHERE d.public_token = ?
         LIMIT 1"
    );
    $q->execute([$token]);
    return $q->fetch() ?: null;
}

function change_donation_status(int $donationId, string $status): void
{
    if (!in_array($status, ['paid', 'cancelled'], true)) {
        throw new InvalidArgumentException('Status inválido.');
    }

    $pdo = db();
    $q = $pdo->prepare("SELECT * FROM donations WHERE id = ? LIMIT 1");
    $q->execute([$donationId]);
    $donation = $q->fetch();

    if (!$donation) {
        throw new RuntimeException('Doação não encontrada.');
    }

    if ($donation['status'] === 'paid' && $status === 'cancelled') {
        throw new RuntimeException('Uma doação paga não pode ser cancelada sem uma reversão administrativa.');
    }

    if ($status === 'paid') {
        $u = $pdo->prepare(
            "UPDATE donations
             SET status = 'paid', paid_at = COALESCE(paid_at, NOW())
             WHERE id = ?"
        );
    } else {
        $u = $pdo->prepare("UPDATE donations SET status = 'cancelled' WHERE id = ?");
    }

    $u->execute([$donationId]);
}
