<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

if (current_user()) {
    redirect('/admin/');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = (string) ($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (attempt_login($email, $password)) {
        redirect('/admin/');
    }
    $error = 'E-mail ou senha inválidos.';
}

$pageTitle = 'Entrar • Painel';
$campaignForHeader = null;
require __DIR__ . '/../partials/header.php';
?>
<main class="container py-5" style="max-width:520px">
    <div class="soft-card p-4 p-md-5">
        <div class="brand-heart mb-4"><i class="bi bi-shield-lock"></i></div>
        <h1 class="h3 fw-bold">Painel da campanha</h1>
        <p class="text-secondary">Acesso reservado à equipe responsável.</p>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="vstack gap-3">
            <?= csrf_field() ?>
            <div>
                <label class="form-label">E-mail</label>
                <input class="form-control form-control-lg" type="email" name="email" autocomplete="username" required>
            </div>
            <div>
                <label class="form-label">Senha</label>
                <input class="form-control form-control-lg" type="password" name="password" autocomplete="current-password" required>
            </div>
            <button class="btn btn-primary btn-lg">Entrar</button>
        </form>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>