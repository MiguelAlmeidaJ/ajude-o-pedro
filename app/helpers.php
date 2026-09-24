<?php
declare(strict_types=1);

function config(?string $key = null, mixed $default = null): mixed
{
    global $CONFIG;
    if ($key === null) {
        return $CONFIG;
    }

    $value = $CONFIG;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }

    return $value;
}

function url(string $path = ''): string
{
    $base = rtrim((string) config('app.base_path', ''), '/');
    $path = '/' . ltrim($path, '/');
    return $base . ($path === '/' ? '/' : $path);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money(float|int|string $value): string
{
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

function slugify(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?: '';
    return trim($text, '-') ?: 'campanha';
}

function normalize_phone(string $phone): string
{
    return preg_replace('/\D+/', '', $phone) ?: '';
}

function campaign_stats(int $campaignId): array
{
    $pdo = db();

    $q = $pdo->prepare(
        "SELECT
            COUNT(*) total,
            SUM(status = 'paid') sold,
            SUM(status = 'reserved') reserved,
            SUM(status = 'available') available
         FROM raffle_numbers
         WHERE campaign_id = ?"
    );
    $q->execute([$campaignId]);
    $row = $q->fetch() ?: [];

    $v = $pdo->prepare(
        "SELECT COALESCE(SUM(total_amount), 0)
         FROM orders
         WHERE campaign_id = ? AND status = 'paid'"
    );
    $v->execute([$campaignId]);

    return [
        'total' => (int) ($row['total'] ?? 0),
        'sold' => (int) ($row['sold'] ?? 0),
        'reserved' => (int) ($row['reserved'] ?? 0),
        'available' => (int) ($row['available'] ?? 0),
        'raised' => (float) $v->fetchColumn(),
    ];
}

function campaign_by_slug(string $slug): ?array
{
    $q = db()->prepare("SELECT * FROM campaigns WHERE slug = ? LIMIT 1");
    $q->execute([$slug]);
    return $q->fetch() ?: null;
}

function active_campaign(): ?array
{
    $q = db()->query("SELECT * FROM campaigns WHERE status = 'active' ORDER BY id DESC LIMIT 1");
    return $q->fetch() ?: null;
}

function cleanup_expired_reservations(?int $campaignId = null): void
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $sql = "SELECT id FROM orders WHERE status = 'pending' AND reserved_until < NOW()";
        $params = [];
        if ($campaignId !== null) {
            $sql .= " AND campaign_id = ?";
            $params[] = $campaignId;
        }
        $sql .= " FOR UPDATE";

        $q = $pdo->prepare($sql);
        $q->execute($params);
        $orderIds = array_map('intval', array_column($q->fetchAll(), 'id'));

        if ($orderIds) {
            $marks = implode(',', array_fill(0, count($orderIds), '?'));

            $u1 = $pdo->prepare(
                "UPDATE raffle_numbers
                 SET status = 'available', order_id = NULL, reserved_until = NULL
                 WHERE order_id IN ($marks) AND status = 'reserved'"
            );
            $u1->execute($orderIds);

            $u2 = $pdo->prepare("UPDATE orders SET status = 'expired' WHERE id IN ($marks)");
            $u2->execute($orderIds);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function order_by_token(string $token): ?array
{
    $q = db()->prepare(
        "SELECT o.*, c.title campaign_title, c.pix_key, c.pix_receiver_name,
                c.pix_receiver_city, c.slug campaign_slug
         FROM orders o
         JOIN campaigns c ON c.id = o.campaign_id
         WHERE o.public_token = ?
         LIMIT 1"
    );
    $q->execute([$token]);
    return $q->fetch() ?: null;
}

function order_numbers(int $orderId): array
{
    $q = db()->prepare(
        "SELECT rn.number
         FROM order_numbers onum
         JOIN raffle_numbers rn ON rn.id = onum.raffle_number_id
         WHERE onum.order_id = ?
         ORDER BY rn.number"
    );
    $q->execute([$orderId]);
    return array_map('intval', array_column($q->fetchAll(), 'number'));
}

function public_token(): string
{
    return bin2hex(random_bytes(20));
}