<?php
declare(strict_types=1);

function sync_campaign_numbers(int $campaignId, int $totalNumbers): void
{
    $pdo = db();

    $q = $pdo->prepare("SELECT COALESCE(MAX(number), 0) FROM raffle_numbers WHERE campaign_id = ?");
    $q->execute([$campaignId]);
    $currentMax = (int) $q->fetchColumn();

    if ($totalNumbers < $currentMax) {
        $check = $pdo->prepare(
            "SELECT COUNT(*) FROM raffle_numbers
             WHERE campaign_id = ? AND number > ? AND status <> 'available'"
        );
        $check->execute([$campaignId, $totalNumbers]);

        if ((int) $check->fetchColumn() > 0) {
            throw new RuntimeException('Não é possível reduzir a quantidade porque existem números reservados ou pagos acima do novo limite.');
        }

        $del = $pdo->prepare(
            "DELETE FROM raffle_numbers
             WHERE campaign_id = ? AND number > ? AND status = 'available'"
        );
        $del->execute([$campaignId, $totalNumbers]);
        return;
    }

    if ($totalNumbers <= $currentMax) {
        return;
    }

    $insert = $pdo->prepare(
        "INSERT IGNORE INTO raffle_numbers (campaign_id, number, status)
         VALUES (?, ?, 'available')"
    );

    for ($n = $currentMax + 1; $n <= $totalNumbers; $n++) {
        $insert->execute([$campaignId, $n]);
    }
}

function reserve_numbers(
    int $campaignId,
    array $numbers,
    string $name,
    string $phone,
    ?string $email
): array {
    $numbers = array_values(array_unique(array_map('intval', $numbers)));
    $numbers = array_values(array_filter($numbers, static fn($n) => $n > 0));

    if (!$numbers) {
        throw new RuntimeException('Selecione pelo menos um número.');
    }

    if (count($numbers) > 50) {
        throw new RuntimeException('É possível reservar até 50 números por compra.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $campaignQ = $pdo->prepare(
            "SELECT * FROM campaigns
             WHERE id = ? AND status = 'active'
             LIMIT 1 FOR UPDATE"
        );
        $campaignQ->execute([$campaignId]);
        $campaign = $campaignQ->fetch();

        if (!$campaign) {
            throw new RuntimeException('Esta campanha não está disponível para compras.');
        }

        $marks = implode(',', array_fill(0, count($numbers), '?'));
        $params = array_merge([$campaignId], $numbers);

        $q = $pdo->prepare(
            "SELECT id, number, status
             FROM raffle_numbers
             WHERE campaign_id = ? AND number IN ($marks)
             FOR UPDATE"
        );
        $q->execute($params);
        $rows = $q->fetchAll();

        if (count($rows) !== count($numbers)) {
            throw new RuntimeException('Um ou mais números selecionados são inválidos.');
        }

        foreach ($rows as $row) {
            if ($row['status'] !== 'available') {
                throw new RuntimeException('Um dos números acabou de ser reservado por outra pessoa. Escolha outro número.');
            }
        }

        $minutes = max(5, (int) config('app.reservation_minutes', 30));
        $token = public_token();
        $total = count($rows) * (float) $campaign['number_price'];
        $expires = (new DateTimeImmutable("+{$minutes} minutes"))->format('Y-m-d H:i:s');

        $o = $pdo->prepare(
            "INSERT INTO orders
                (campaign_id, public_token, customer_name, customer_phone, customer_email,
                 total_amount, status, reserved_until)
             VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)"
        );
        $o->execute([
            $campaignId,
            $token,
            trim($name),
            normalize_phone($phone),
            $email ? strtolower(trim($email)) : null,
            $total,
            $expires,
        ]);
        $orderId = (int) $pdo->lastInsertId();

        $rel = $pdo->prepare(
            "INSERT INTO order_numbers (order_id, raffle_number_id) VALUES (?, ?)"
        );
        $upd = $pdo->prepare(
            "UPDATE raffle_numbers
             SET status = 'reserved', order_id = ?, reserved_until = ?
             WHERE id = ?"
        );

        foreach ($rows as $row) {
            $rel->execute([$orderId, $row['id']]);
            $upd->execute([$orderId, $expires, $row['id']]);
        }

        $pdo->commit();

        return [
            'id' => $orderId,
            'token' => $token,
            'total' => $total,
            'expires' => $expires,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function change_order_status(int $orderId, string $status): void
{
    if (!in_array($status, ['paid', 'cancelled'], true)) {
        throw new InvalidArgumentException('Status inválido.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $q = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1 FOR UPDATE");
        $q->execute([$orderId]);
        $order = $q->fetch();

        if (!$order) {
            throw new RuntimeException('Pedido não encontrado.');
        }

        if ($status === 'paid') {
            if (!in_array($order['status'], ['pending', 'paid'], true)) {
                throw new RuntimeException('Este pedido não pode mais ser confirmado.');
            }

            $u = $pdo->prepare(
                "UPDATE orders
                 SET status = 'paid', paid_at = COALESCE(paid_at, NOW())
                 WHERE id = ?"
            );
            $u->execute([$orderId]);

            $n = $pdo->prepare(
                "UPDATE raffle_numbers
                 SET status = 'paid', reserved_until = NULL
                 WHERE order_id = ?"
            );
            $n->execute([$orderId]);
        } else {
            if ($order['status'] === 'paid') {
                throw new RuntimeException('Pedido pago não pode ser cancelado sem uma reversão administrativa.');
            }

            $u = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $u->execute([$orderId]);

            $n = $pdo->prepare(
                "UPDATE raffle_numbers
                 SET status = 'available', order_id = NULL, reserved_until = NULL
                 WHERE order_id = ? AND status = 'reserved'"
            );
            $n->execute([$orderId]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}