<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

$campaignId = (int) ($_GET['campaign_id'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 120;
$search = trim((string) ($_GET['q'] ?? ''));

$q = db()->prepare("SELECT id,total_numbers,status FROM campaigns WHERE id = ? LIMIT 1");
$q->execute([$campaignId]);
$campaign = $q->fetch();

if (!$campaign || $campaign['status'] !== 'active') {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Campanha não encontrada.']);
    exit;
}

if ($search !== '') {
    $number = (int) preg_replace('/\D+/', '', $search);

    if ($number < 1 || $number > (int) $campaign['total_numbers']) {
        echo json_encode([
            'ok' => true,
            'items' => [],
            'page' => 1,
            'pages' => 1,
            'available_total' => 0,
            'from' => 0,
            'to' => 0,
            'search' => true,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $one = db()->prepare(
        "SELECT number,status
         FROM raffle_numbers
         WHERE campaign_id = ?
           AND number = ?
           AND status = 'available'
         LIMIT 1"
    );
    $one->execute([$campaignId, $number]);
    $item = $one->fetch();

    echo json_encode([
        'ok' => true,
        'items' => $item ? [$item] : [],
        'page' => 1,
        'pages' => 1,
        'available_total' => $item ? 1 : 0,
        'from' => $item ? $number : 0,
        'to' => $item ? $number : 0,
        'search' => true,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$count = db()->prepare(
    "SELECT COUNT(*)
     FROM raffle_numbers
     WHERE campaign_id = ?
       AND status = 'available'"
);
$count->execute([$campaignId]);
$availableTotal = (int) $count->fetchColumn();

$pages = max(1, (int) ceil($availableTotal / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$list = db()->prepare(
    "SELECT number,status
     FROM raffle_numbers
     WHERE campaign_id = ?
       AND status = 'available'
     ORDER BY number
     LIMIT ? OFFSET ?"
);
$list->bindValue(1, $campaignId, PDO::PARAM_INT);
$list->bindValue(2, $perPage, PDO::PARAM_INT);
$list->bindValue(3, $offset, PDO::PARAM_INT);
$list->execute();
$items = $list->fetchAll();

echo json_encode([
    'ok' => true,
    'items' => $items,
    'page' => $page,
    'pages' => $pages,
    'available_total' => $availableTotal,
    'from' => $items ? (int) $items[0]['number'] : 0,
    'to' => $items ? (int) $items[count($items) - 1]['number'] : 0,
    'search' => false,
], JSON_UNESCAPED_UNICODE);
