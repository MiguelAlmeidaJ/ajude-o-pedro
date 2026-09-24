<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$pdo = null;
$dbError = null;
$hasUsers = false;

try {
    $pdo = db();
    try {
        $hasUsers = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() > 0;
    } catch (Throwable) {
        $hasUsers = false;
    }
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$dbError && !$hasUsers) {
    verify_csrf();

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        flash('danger', 'Preencha nome, e-mail válido e uma senha com pelo menos 8 caracteres.');
        redirect('/setup.php');
    }

    try {
        $sql = (string) file_get_contents(__DIR__ . '/database/schema.sql');
        foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [] as $statement) {
            $statement = trim($statement);
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }

        $q = $pdo->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,'dev')");
        $q->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);

        flash('success', 'Configuração concluída. Entre no painel e crie a campanha.');
        redirect('/admin/login.php');
    } catch (Throwable $e) {
        flash('danger', 'Falha na configuração: ' . $e->getMessage());
        redirect('/setup.php');
    }
}

$pageTitle = 'Configuração inicial • Ajude o Pedro';
$campaignForHeader = null;
require __DIR__ . '/partials/header.php';
?>
<main class="container py-5" style="max-width:720px">
    <div class="soft-card p-4 p-md-5">
        <div class="brand-heart mb-4"><i class="bi bi-gear-fill"></i></div>
        <h1 class="h2 fw-bold">Configuração inicial</h1>
        <p class="text-secondary">Edite primeiro <code>app/config.php</code> com os dados MySQL da hospedagem. Depois crie o primeiro usuário DEV.</p>

        <?php if ($dbError): ?>
            <div class="alert alert-danger">
                <strong>Não foi possível conectar ao MySQL.</strong><br>
                <span class="small"><?= e($dbError) ?></span>
            </div>
        <?php elseif ($hasUsers): ?>
            <div class="alert alert-success">O sistema já possui usuário cadastrado.</div>
            <a class="btn btn-primary" href="<?= e(url('/admin/login.php')) ?>">Ir para o painel</a>
        <?php else: ?>
            <form method="post" class="vstack gap-3">
                <?= csrf_field() ?>
                <div><label class="form-label">Nome do DEV</label><input class="form-control form-control-lg" name="name" required></div>
                <div><label class="form-label">E-mail</label><input class="form-control form-control-lg" type="email" name="email" required></div>
                <div><label class="form-label">Senha</label><input class="form-control form-control-lg" type="password" name="password" minlength="8" required></div>
                <button class="btn btn-primary btn-lg">Configurar sistema</button>
            </form>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>