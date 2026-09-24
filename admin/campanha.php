<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require_login();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$defaultStory = <<<'TEXT'
O Pedro tem apenas 3 aninhos e foi diagnosticado com AME — Atrofia Muscular Espinhal.

Por causa da sua condição, ele precisa de cuidados especiais e do suporte de um respirador. Hoje, nossa família vive uma situação muito difícil: esta não é a primeira vez que enfrentamos problemas relacionados ao fornecimento e ao pagamento do aparelho. O Estado novamente não realizou o pagamento do aluguel e a empresa responsável entrou com um pedido judicial para retirá-lo.

Estamos cansados de viver nessa incerteza e com o medo constante de perder um equipamento tão importante para o cuidado do nosso filho.

Por isso, decidimos realizar esta rifa com um objetivo muito importante: conseguir comprar um respirador portátil para o Pedro e, assim, ter mais segurança e tranquilidade para cuidar dele.

Sabemos que é um equipamento de alto custo e, sozinhos, não conseguimos arcar com esse valor. Por isso, estamos pedindo a ajuda de vocês. ❤️

Cada número comprado, cada contribuição e cada compartilhamento fazem diferença e nos aproximam do nosso objetivo.

🙏 Ajude o Pedro a conquistar seu próprio respirador portátil.

Se puder participar da rifa, seremos eternamente gratos. E, se não puder, compartilhar essa campanha já será uma enorme ajuda para nossa família.

Muito obrigado por fazer parte dessa luta conosco. 💙
TEXT;

if ($id === 0 && !is_dev()) {
    http_response_code(403);
    exit('Somente usuários DEV podem criar novas campanhas.');
}

$campaign = [
    'id' => 0,
    'title' => 'Ajude o Pedro a conquistar seu respirador portátil',
    'slug' => 'ajude-o-pedro',
    'subtitle' => 'Uma rifa solidária por mais segurança, cuidado e tranquilidade para o Pedro.',
    'story' => $defaultStory,
    'goal_amount' => '0.00',
    'number_price' => '10.00',
    'total_numbers' => 1000,
    'pix_key' => '',
    'pix_receiver_name' => 'PEDRO',
    'pix_receiver_city' => '',
    'hero_image' => 'assets/img/pedro-familia.webp',
    'gallery_json' => json_encode(['assets/img/pedro-1.webp','assets/img/pedro-2.webp','assets/img/pedro-3.webp']),
    'whatsapp' => '',
    'instagram' => '',
    'draw_date' => '',
    'status' => 'draft',
];

if ($id > 0) {
    $q = db()->prepare("SELECT * FROM campaigns WHERE id = ? LIMIT 1");
    $q->execute([$id]);
    $campaign = $q->fetch() ?: null;
    if (!$campaign) {
        http_response_code(404);
        exit('Campanha não encontrada.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $data = [
        'title' => trim((string) ($_POST['title'] ?? '')),
        'slug' => slugify((string) ($_POST['slug'] ?? '')),
        'subtitle' => trim((string) ($_POST['subtitle'] ?? '')),
        'story' => trim((string) ($_POST['story'] ?? '')),
        'goal_amount' => max(0, (float) str_replace(',', '.', (string) ($_POST['goal_amount'] ?? 0))),
        'number_price' => (float) str_replace(',', '.', (string) ($_POST['number_price'] ?? 0)),
        'total_numbers' => (int) ($_POST['total_numbers'] ?? 0),
        'pix_key' => trim((string) ($_POST['pix_key'] ?? '')),
        'pix_receiver_name' => pix_ascii((string) ($_POST['pix_receiver_name'] ?? ''), 25),
        'pix_receiver_city' => pix_ascii((string) ($_POST['pix_receiver_city'] ?? ''), 15),
        'hero_image' => trim((string) ($_POST['hero_image'] ?? '')),
        'whatsapp' => trim((string) ($_POST['whatsapp'] ?? '')),
        'instagram' => trim((string) ($_POST['instagram'] ?? '')),
        'draw_date' => trim((string) ($_POST['draw_date'] ?? '')) ?: null,
        'status' => (string) ($_POST['status'] ?? 'draft'),
    ];

    $galleryLines = preg_split('/\R+/', (string) ($_POST['gallery'] ?? '')) ?: [];
    $galleryLines = array_values(array_filter(array_map('trim', $galleryLines)));
    $galleryJson = json_encode($galleryLines, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $errors = [];
    if ($data['title'] === '') $errors[] = 'Informe o título.';
    if ($data['number_price'] <= 0) $errors[] = 'O valor de cada número deve ser maior que zero.';
    if ($data['total_numbers'] < 1 || $data['total_numbers'] > 10000) $errors[] = 'Use entre 1 e 10.000 números.';
    if (!in_array($data['status'], ['draft','active','closed'], true)) $errors[] = 'Status inválido.';
    if ($data['status'] === 'active' && ($data['pix_key'] === '' || $data['pix_receiver_name'] === '' || $data['pix_receiver_city'] === '')) {
        $errors[] = 'Para ativar a campanha, configure chave Pix, nome do recebedor e cidade.';
    }

    if ($errors) {
        foreach ($errors as $error) flash('danger', $error);
        $campaign = array_merge($campaign, $data, ['gallery_json' => $galleryJson]);
    } else {
        try {
            $pdo = db();
            $pdo->beginTransaction();

            if ($id > 0) {
                $q = $pdo->prepare(
                    "UPDATE campaigns SET
                        title=?,slug=?,subtitle=?,story=?,goal_amount=?,number_price=?,total_numbers=?,
                        pix_key=?,pix_receiver_name=?,pix_receiver_city=?,hero_image=?,gallery_json=?,
                        whatsapp=?,instagram=?,draw_date=?,status=?
                     WHERE id=?"
                );
                $q->execute([
                    $data['title'],$data['slug'],$data['subtitle'],$data['story'],$data['goal_amount'],
                    $data['number_price'],$data['total_numbers'],$data['pix_key'],$data['pix_receiver_name'],
                    $data['pix_receiver_city'],$data['hero_image'],$galleryJson,$data['whatsapp'],
                    $data['instagram'],$data['draw_date'],$data['status'],$id
                ]);
                $campaignId = $id;
            } else {
                if (!is_dev()) {
                    throw new RuntimeException('Somente DEV pode criar campanhas.');
                }
                $q = $pdo->prepare(
                    "INSERT INTO campaigns
                        (title,slug,subtitle,story,goal_amount,number_price,total_numbers,pix_key,
                         pix_receiver_name,pix_receiver_city,hero_image,gallery_json,whatsapp,instagram,
                         draw_date,status,created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
                );
                $q->execute([
                    $data['title'],$data['slug'],$data['subtitle'],$data['story'],$data['goal_amount'],
                    $data['number_price'],$data['total_numbers'],$data['pix_key'],$data['pix_receiver_name'],
                    $data['pix_receiver_city'],$data['hero_image'],$galleryJson,$data['whatsapp'],
                    $data['instagram'],$data['draw_date'],$data['status'],current_user()['id']
                ]);
                $campaignId = (int) $pdo->lastInsertId();
            }

            sync_campaign_numbers($campaignId, $data['total_numbers']);
            $pdo->commit();

            flash('success', $id > 0 ? 'Campanha atualizada.' : 'Campanha criada.');
            redirect('/admin/campanha.php?id=' . $campaignId);
        } catch (Throwable $e) {
            if (db()->inTransaction()) db()->rollBack();
            flash('danger', 'Não foi possível salvar: ' . $e->getMessage());
            $campaign = array_merge($campaign, $data, ['gallery_json' => $galleryJson]);
        }
    }
}

$gallery = implode("\n", json_decode($campaign['gallery_json'] ?: '[]', true) ?: []);
$pageTitle = ($id ? 'Editar' : 'Nova') . ' campanha • Painel';
require __DIR__ . '/partials/header.php';
?>
<div class="d-flex align-items-center gap-3 mb-4">
    <a class="btn btn-light border" href="<?= e(url('/admin/campanhas.php')) ?>"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 class="h3 fw-bold mb-1"><?= $id ? 'Editar campanha' : 'Nova campanha' ?></h1>
        <p class="text-secondary mb-0"><?= $id ? 'Ajuste conteúdo, Pix, valores e disponibilidade.' : 'Crie uma nova campanha solidária.' ?></p>
    </div>
</div>

<form method="post" class="row g-4">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $campaign['id'] ?>">

    <div class="col-xl-8">
        <div class="admin-card p-4 mb-4">
            <h2 class="h5 fw-bold mb-3">Conteúdo</h2>
            <div class="mb-3">
                <label class="form-label">Título</label>
                <input class="form-control" name="title" value="<?= e($campaign['title']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Slug / endereço</label>
                <input class="form-control" name="slug" value="<?= e($campaign['slug']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Subtítulo</label>
                <textarea class="form-control" name="subtitle" rows="2"><?= e($campaign['subtitle']) ?></textarea>
            </div>
            <div>
                <label class="form-label">História da campanha</label>
                <textarea class="form-control" name="story" rows="16"><?= e($campaign['story']) ?></textarea>
            </div>
        </div>

        <div class="admin-card p-4 mb-4">
            <h2 class="h5 fw-bold mb-3">Imagens</h2>
            <div class="mb-3">
                <label class="form-label">Imagem principal</label>
                <input class="form-control" name="hero_image" value="<?= e($campaign['hero_image']) ?>" placeholder="assets/img/pedro-familia.webp">
            </div>
            <div>
                <label class="form-label">Galeria <span class="text-secondary">(uma imagem por linha)</span></label>
                <textarea class="form-control font-monospace" name="gallery" rows="5"><?= e($gallery) ?></textarea>
            </div>
        </div>

        <div class="admin-card p-4">
            <h2 class="h5 fw-bold mb-3">Pix</h2>
            <div class="mb-3">
                <label class="form-label">Chave Pix</label>
                <input class="form-control" name="pix_key" value="<?= e($campaign['pix_key']) ?>" autocomplete="off">
            </div>
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label">Nome do recebedor</label>
                    <input class="form-control" name="pix_receiver_name" maxlength="25" value="<?= e($campaign['pix_receiver_name']) ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Cidade</label>
                    <input class="form-control" name="pix_receiver_city" maxlength="15" value="<?= e($campaign['pix_receiver_city']) ?>">
                </div>
            </div>
            <div class="form-text mt-2">O sistema monta o Pix Copia e Cola e o QR Code com o valor exato da compra.</div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="admin-card p-4 mb-4">
            <h2 class="h5 fw-bold mb-3">Configurações</h2>
            <div class="mb-3">
                <label class="form-label">Valor por número</label>
                <div class="input-group"><span class="input-group-text">R$</span><input class="form-control" name="number_price" value="<?= e((string) $campaign['number_price']) ?>" required></div>
            </div>
            <div class="mb-3">
                <label class="form-label">Quantidade de números</label>
                <input class="form-control" type="number" min="1" max="10000" name="total_numbers" value="<?= (int) $campaign['total_numbers'] ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Meta de arrecadação</label>
                <div class="input-group"><span class="input-group-text">R$</span><input class="form-control" name="goal_amount" value="<?= e((string) $campaign['goal_amount']) ?>"></div>
                <div class="form-text">Use 0 para acompanhar pelo total de números vendidos.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Data do sorteio</label>
                <input class="form-control" type="date" name="draw_date" value="<?= e((string) $campaign['draw_date']) ?>">
            </div>
            <div>
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <?php foreach (['draft'=>'Rascunho','active'=>'Ativa','closed'=>'Encerrada'] as $value=>$label): ?>
                        <option value="<?= e($value) ?>" <?= $campaign['status']===$value?'selected':'' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="admin-card p-4 mb-4">
            <h2 class="h5 fw-bold mb-3">Contato</h2>
            <div class="mb-3">
                <label class="form-label">WhatsApp</label>
                <input class="form-control" name="whatsapp" value="<?= e((string) $campaign['whatsapp']) ?>">
            </div>
            <div>
                <label class="form-label">Instagram</label>
                <input class="form-control" name="instagram" value="<?= e((string) $campaign['instagram']) ?>">
            </div>
        </div>

        <button class="btn btn-primary btn-lg w-100"><i class="bi bi-check2-circle me-2"></i>Salvar campanha</button>
    </div>
</form>
<?php require __DIR__ . '/partials/footer.php'; ?>