<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$campaign = active_campaign();

if ($campaign) {
    redirect(campaign_path($campaign));
}

$pageTitle = 'Ajude o Pedro';
$campaignForHeader = null;
require __DIR__ . '/partials/header.php';
?>
<main class="container py-5">
    <div class="soft-card p-5 text-center mx-auto" style="max-width:720px">
        <div class="brand-heart mx-auto mb-3"><i class="bi bi-heart-fill"></i></div>
        <h1 class="h2 fw-bold">Nenhuma rifa ativa no momento.</h1>
        <p class="text-secondary mb-0">Assim que uma campanha for ativada no painel, esta página redirecionará automaticamente para ela.</p>
    </div>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>