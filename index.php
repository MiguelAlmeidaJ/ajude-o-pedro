<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$pageTitle = 'Ajude o Pedro';
$campaignForHeader = null;
require __DIR__ . '/partials/header.php';
?>

<main class="home-simple">
    <section class="container py-5 py-lg-6">
        <div class="row g-5 align-items-center min-vh-75">
            <div class="col-lg-6 order-2 order-lg-1">
                <span class="section-kicker">Ajude o Pedro</span>
                <h1 class="display-title mt-3 mb-4">
                    Uma ajuda que pode trazer mais <span class="text-gradient">segurança para o Pedro.</span>
                </h1>

                <p class="lead-copy mb-4">
                    Pedro tem 3 anos e precisa do suporte de um respirador. Nossa família está realizando uma rifa solidária para ajudar na compra de um respirador portátil próprio.
                </p>

                <p class="lead-copy mb-4">
                    Cada número comprado e cada compartilhamento fazem diferença.
                </p>

                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-primary btn-lg rounded-pill px-4" href="<?= e(url('/rifa/')) ?>">
                        <i class="bi bi-ticket-perforated me-2"></i>Participar da rifa
                    </a>

                    <a class="btn btn-light btn-lg rounded-pill px-4 border" href="<?= e(url('/rifa/#historia')) ?>">
                        <i class="bi bi-heart me-2"></i>Conhecer a história
                    </a>
                </div>

                <div class="home-note mt-4">
                    <i class="bi bi-share me-2"></i>
                    Não pode participar agora? Compartilhar a campanha também ajuda muito.
                </div>
            </div>

            <div class="col-lg-6 order-1 order-lg-2">
                <div class="home-photo">
                    <img src="<?= e(url('/assets/img/pedro-familia.webp')) ?>" alt="Pedro com sua família">
                    <div class="home-photo-caption">
                        <span class="brand-heart"><i class="bi bi-heart-fill"></i></span>
                        <div>
                            <strong>Ajude o Pedro</strong>
                            <span>Rifa solidária</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>