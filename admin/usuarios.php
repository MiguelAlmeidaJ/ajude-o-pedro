<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require_role('dev');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'create') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            $role = (string) ($_POST['role'] ?? 'admin');

            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
                throw new RuntimeException('Informe nome, e-mail válido e senha com pelo menos 8 caracteres.');
            }
            if (!in_array($role, ['dev','admin'], true)) {
                throw new RuntimeException('Perfil inválido.');
            }

            $q = db()->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)");
            $q->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$role]);
            flash('success', 'Usuário criado.');
        }

        if ($action === 'toggle') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            if ($userId === (int) current_user()['id']) {
                throw new RuntimeException('Você não pode desativar o próprio usuário.');
            }
            $q = db()->prepare("UPDATE users SET active = IF(active=1,0,1) WHERE id = ?");
            $q->execute([$userId]);
            flash('success', 'Acesso do usuário atualizado.');
        }
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
    }

    redirect('/admin/usuarios.php');
}

$users = db()->query("SELECT id,name,email,role,active,created_at FROM users ORDER BY id")->fetchAll();

$pageTitle = 'Usuários • Painel';
require __DIR__ . '/partials/header.php';
?>
<div class="mb-4">
    <h1 class="h3 fw-bold mb-1">Usuários</h1>
    <p class="text-secondary mb-0">DEV pode criar campanhas e usuários. ADMIN gerencia campanhas existentes e participações.</p>
</div>

<div class="row g-4">
    <div class="col-xl-5">
        <div class="admin-card p-4">
            <h2 class="h5 fw-bold mb-3">Novo usuário</h2>
            <form method="post" class="vstack gap-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <div><label class="form-label">Nome</label><input class="form-control" name="name" required></div>
                <div><label class="form-label">E-mail</label><input class="form-control" type="email" name="email" required></div>
                <div><label class="form-label">Senha</label><input class="form-control" type="password" name="password" minlength="8" required></div>
                <div>
                    <label class="form-label">Perfil</label>
                    <select class="form-select" name="role">
                        <option value="admin">ADMIN</option>
                        <option value="dev">DEV</option>
                    </select>
                </div>
                <button class="btn btn-primary">Criar usuário</button>
            </form>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="admin-card overflow-hidden">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Usuário</th><th>Perfil</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $item): ?>
                    <tr>
                        <td><div class="fw-semibold"><?= e($item['name']) ?></div><div class="small text-secondary"><?= e($item['email']) ?></div></td>
                        <td><span class="badge text-bg-dark"><?= e(strtoupper($item['role'])) ?></span></td>
                        <td><?= $item['active'] ? '<span class="badge text-bg-success">Ativo</span>' : '<span class="badge text-bg-secondary">Inativo</span>' ?></td>
                        <td class="text-end">
                            <?php if ((int) $item['id'] !== (int) current_user()['id']): ?>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="user_id" value="<?= (int) $item['id'] ?>">
                                <button class="btn btn-sm btn-outline-secondary"><?= $item['active'] ? 'Desativar' : 'Ativar' ?></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>