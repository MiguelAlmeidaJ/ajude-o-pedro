<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));

if ($slug === '') {
    $active = active_campaign();
    if ($active) {
        redirect(campaign_path($active));
    }
    $campaign = null;
} else {
    $campaign = campaign_by_slug($slug);
    if (!$campaign || $campaign['status'] !== 'active') {
        http_response_code(404);
        $campaign = null;
    }
}

if ($campaign) {
    cleanup_expired_reservations((int) $campaign['id']);
    $stats = campaign_stats((int) $campaign['id']);
    $gallery = json_decode($campaign['gallery_json'] ?: '[]', true) ?: [];
    $digits = max(3, strlen((string) $campaign['total_numbers']));
    $percent = $campaign['goal_amount'] > 0
        ? min(100, ($stats['raised'] / (float) $campaign['goal_amount']) * 100)
        : (($stats['total'] ?? 0) > 0 ? ($stats['sold'] / $stats['total']) * 100 : 0);
}

$pageTitle = $campaign ? 'Rifa • ' . $campaign['title'] : 'Rifa • Ajude o Pedro';
$campaignForHeader = $campaign;
require __DIR__ . '/../partials/header.php';
?>

<?php if (!$campaign): ?>
<main class="container py-5">
    <div class="soft-card p-5 text-center">
        <div class="brand-heart mx-auto mb-3"><i class="bi bi-heart-fill"></i></div>
        <h1 class="h2 fw-bold"><?= $slug !== '' ? 'Rifa não encontrada ou encerrada.' : 'A rifa está sendo preparada.' ?></h1>
        <p class="text-secondary mb-3"><?= $slug !== '' ? 'Confira se o endereço está correto ou volte para a página inicial.' : 'Em breve você poderá escolher seus números e participar.' ?></p>
        <a class="btn btn-outline-primary" href="<?= e(url('/')) ?>">Voltar ao início</a>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; exit; endif; ?>

<section class="hero">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <span class="section-kicker">Rifa solidária</span>
                <h1 class="display-title mt-3 mb-4">Um número pode nos aproximar do <span class="text-gradient">respirador do Pedro.</span></h1>
                <p class="lead-copy mb-4"><?= e($campaign['subtitle']) ?></p>

                <div class="d-flex flex-wrap gap-2 mb-4">
                    <a class="btn btn-primary btn-lg rounded-pill px-4" href="#numeros">
                        <i class="bi bi-ticket-perforated me-2"></i>Escolher meus números
                    </a>
                    <a class="btn btn-light btn-lg rounded-pill px-4 border" href="#historia">
                        <i class="bi bi-heart me-2"></i>Conhecer a história
                    </a>
                    <a class="btn btn-outline-primary btn-lg rounded-pill px-4" href="#doar">
                        <i class="bi bi-cash-heart me-2"></i>Doar sem comprar rifa
                    </a>
                </div>

                <div class="soft-card p-3 p-md-4">
                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <div>
                            <div class="small text-secondary">Arrecadado</div>
                            <div class="stat-number"><?= money($stats['raised']) ?></div>
                        </div>
                        <?php if ((float) $campaign['goal_amount'] > 0): ?>
                            <div class="text-end">
                                <div class="small text-secondary">Meta</div>
                                <div class="stat-number"><?= money($campaign['goal_amount']) ?></div>
                            </div>
                        <?php else: ?>
                            <div class="text-end">
                                <div class="small text-secondary">Números confirmados</div>
                                <div class="stat-number"><?= (int) $stats['sold'] ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="progress" role="progressbar" aria-valuenow="<?= (int) $percent ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar" style="width:<?= (float) $percent ?>%"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="hero-photo">
                    <img src="<?= e(url('/' . ltrim((string) $campaign['hero_image'], '/'))) ?>" alt="Pedro com sua família">
                    <div class="hero-badge">
                        <i class="bi bi-heart-pulse-fill text-primary me-2"></i><strong>Ajude o Pedro</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5" id="historia">
    <div class="container">
        <div class="row g-5 align-items-start">
            <div class="col-lg-7">
                <span class="section-kicker">A história do Pedro</span>
                <h2 class="display-6 fw-bold mt-2 mb-4">Por que essa rifa existe?</h2>
                <div class="story-copy"><?= e($campaign['story']) ?></div>
            </div>

            <div class="col-lg-5">
                <div class="row g-3">
                    <?php foreach (array_slice($gallery, 0, 3) as $image): ?>
                    <div class="<?= count($gallery) > 1 ? 'col-6' : 'col-12' ?>">
                        <img class="gallery-img" src="<?= e(url('/' . ltrim((string) $image, '/'))) ?>" alt="Pedro">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-white border-top border-bottom" id="numeros">
    <div class="container">
        <div class="text-center mx-auto mb-5" style="max-width:760px">
            <span class="section-kicker">Participe da rifa</span>
            <h2 class="display-6 fw-bold mt-2">Escolha seus números</h2>
            <p class="text-secondary">
                Cada número custa <?= money($campaign['number_price']) ?>.
                Os números ficam reservados por <?= (int) config('app.reservation_minutes', 30) ?> minutos enquanto você realiza o Pix.
            </p>
        </div>

        <form method="post" action="<?= e(url('/reservar.php')) ?>" class="row g-4" data-raffle-form data-price="<?= e((string) $campaign['number_price']) ?>" data-digits="<?= $digits ?>" data-campaign-id="<?= (int) $campaign['id'] ?>" data-numbers-api="<?= e(url('/api/numeros.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">

            <div class="col-lg-8">
                <div class="soft-card p-3 p-md-4">
                    <div class="number-toolbar mb-3">
                        <div class="d-flex flex-wrap gap-3 small">
                            <span><i class="bi bi-square text-primary"></i> Disponível</span>
                            <span><i class="bi bi-square-fill text-secondary opacity-50"></i> Reservado/vendido</span>
                        </div>

                        <div class="input-group number-search">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input class="form-control" type="number" min="1" max="<?= (int) $campaign['total_numbers'] ?>" placeholder="Buscar número" data-number-search>
                            <button class="btn btn-outline-secondary" type="button" data-number-search-clear aria-label="Limpar busca"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </div>

                    <div class="number-grid" data-number-grid>
                        <div class="number-grid-loading">
                            <span class="spinner-border spinner-border-sm me-2"></span>Carregando números...
                        </div>
                    </div>

                    <div class="number-pagination mt-3">
                        <button class="btn btn-light border" type="button" data-number-prev>
                            <i class="bi bi-chevron-left"></i><span class="d-none d-sm-inline ms-1">Anterior</span>
                        </button>
                        <span class="small text-secondary text-center" data-number-page-label>Carregando...</span>
                        <button class="btn btn-light border" type="button" data-number-next>
                            <span class="d-none d-sm-inline me-1">Próxima</span><i class="bi bi-chevron-right"></i>
                        </button>
                    </div>

                    <div data-selected-inputs></div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="soft-card p-4 checkout-card">
                    <h3 class="h5 fw-bold">Sua participação</h3>
                    <div class="selected-list mb-3" data-selected-list></div>

                    <div class="d-flex justify-content-between border-top border-bottom py-3 mb-3">
                        <span><strong data-selected-count>0</strong> número(s)</span>
                        <strong data-selected-total>R$ 0,00</strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Seu nome</label>
                        <input class="form-control" name="name" autocomplete="name" required maxlength="120">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">WhatsApp</label>
                        <input class="form-control" name="phone" inputmode="tel" autocomplete="tel" placeholder="(00) 00000-0000" required maxlength="30">
                    </div>

                    <div class="mb-4">
                        <label class="form-label">E-mail <span class="text-secondary">(opcional)</span></label>
                        <input class="form-control" type="email" name="email" autocomplete="email" maxlength="190">
                    </div>

                    <button class="btn btn-primary btn-lg w-100" data-submit disabled>
                        Reservar e pagar com Pix
                    </button>
                    <p class="small text-secondary text-center mt-3 mb-0">
                        <i class="bi bi-lock me-1"></i>Seus dados são usados apenas para identificar sua participação.
                    </p>
                </div>
            </div>
        </form>
    </div>
</section>

<section class="py-5 bg-white border-top" id="doar">
    <div class="container">
        <div class="row g-4 g-lg-5 align-items-center">
            <div class="col-lg-6">
                <span class="section-kicker">Doação direta</span>
                <h2 class="display-6 fw-bold mt-2 mb-3">Quer ajudar sem comprar um número?</h2>
                <p class="lead-copy mb-3">
                    Você também pode fazer uma contribuição de qualquer valor diretamente para a campanha.
                </p>
                <p class="text-secondary mb-0">
                    Escolha o valor, gere o pagamento e use o QR Code ou Pix Copia e Cola. Sua ajuda vai para o mesmo objetivo da campanha.
                </p>
            </div>

            <div class="col-lg-6">
                <form method="post" action="<?= e(url('/gerar-doacao.php')) ?>" class="soft-card p-4 p-md-5" data-donation-form>
                    <?= csrf_field() ?>
                    <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">

                    <label class="form-label fw-semibold">Quanto você quer doar?</label>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <?php foreach ([10, 20, 50, 100] as $preset): ?>
                            <button class="btn btn-light border rounded-pill" type="button" data-donation-preset="<?= $preset ?>">
                                <?= money($preset) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="input-group input-group-lg mb-3">
                        <span class="input-group-text">R$</span>
                        <input
                            class="form-control"
                            type="number"
                            name="amount"
                            min="1"
                            max="100000"
                            step="0.01"
                            inputmode="decimal"
                            placeholder="Outro valor"
                            required
                            data-donation-amount
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Seu nome</label>
                        <input class="form-control" name="name" maxlength="120" autocomplete="name" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">WhatsApp <span class="text-secondary">(opcional)</span></label>
                        <input class="form-control" name="phone" maxlength="30" inputmode="tel" autocomplete="tel" placeholder="(00) 00000-0000">
                    </div>

                    <button class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-qr-code me-2"></i>Gerar pagamento Pix
                    </button>

                    <p class="small text-secondary text-center mt-3 mb-0">
                        Você receberá um link de pagamento que pode abrir novamente ou compartilhar.
                    </p>
                </form>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container text-center" style="max-width:800px">
        <div class="soft-card p-4 p-md-5">
            <i class="bi bi-share-fill fs-2 text-primary"></i>
            <h2 class="h3 fw-bold mt-3">Não consegue participar agora?</h2>
            <p class="text-secondary">
                Compartilhar essa campanha também é uma enorme ajuda. Quanto mais pessoas conhecerem a história do Pedro, mais perto chegamos do objetivo.
            </p>
            <button class="btn btn-outline-primary rounded-pill px-4" onclick="navigator.share ? navigator.share({title:document.title,url:location.href}) : navigator.clipboard.writeText(location.href).then(()=>alert('Link copiado!'))">
                <i class="bi bi-share me-2"></i>Compartilhar campanha
            </button>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>