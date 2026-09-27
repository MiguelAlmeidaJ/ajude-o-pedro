<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require_role('dev');

$currentUser = current_user();
$editId = max(0, (int) ($_GET['edit'] ?? $_POST['user_id'] ?? 0));

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

            $exists = db()->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $exists->execute([$email]);
            if ((int) $exists->fetchColumn() > 0) {
                throw new RuntimeException('Já existe um usuário com este e-mail.');
            }

            $q = db()->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)");
            $q->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$role]);

            flash('success', 'Usuário criado.');
            redirect('/admin/usuarios.php');
        }

        if ($action === 'update') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            $role = (string) ($_POST['role'] ?? 'admin');

            $find = db()->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
            $find->execute([$userId]);
            $target = $find->fetch();

            if (!$target) {
                throw new RuntimeException('Usuário não encontrado.');
            }

            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Informe nome e e-mail válidos.');
            }

            if (!in_array($role, ['dev','admin'], true)) {
                throw new RuntimeException('Perfil inválido.');
            }

            if ($password !== '' && strlen($password) < 8) {
                throw new RuntimeException('A nova senha deve ter pelo menos 8 caracteres.');
            }

            if ($userId === (int) $currentUser['id']) {
                $role = (string) $target['role'];
            }

            $exists = db()->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?");
            $exists->execute([$email, $userId]);
            if ((int) $exists->fetchColumn() > 0) {
                throw new RuntimeException('Já existe outro usuário com este e-mail.');
            }

            if ($password !== '') {
                $q = db()->prepare(
                    "UPDATE users
                     SET name = ?, email = ?, role = ?, password_hash = ?
                     WHERE id = ?"
                );
                $q->execute([$name, $email, $role, password_hash($password, PASSWORD_DEFAULT), $userId]);
            } else {
                $q = db()->prepare(
                    "UPDATE users
                     SET name = ?, email = ?, role = ?
                     WHERE id = ?"
                );
                $q->execute([$name, $email, $role, $userId]);
            }

            if ($userId === (int) $currentUser['id']) {
                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['email'] = $email;
            }

            flash('success', 'Usuário atualizado.');
            redirect('/admin/usuarios.php');
        }

        if ($action === 'toggle') {
            $userId = (int) ($_POST['user_id'] ?? 0);

            if ($userId === (int) $currentUser['id']) {
                throw new RuntimeException('Você não pode desativar o próprio usuário.');
            }

            $q = db()->prepare("UPDATE users SET active = IF(active=1,0,1) WHERE id = ?");
            $q->execute([$userId]);

            flash('success', 'Acesso do usuário atualizado.');
            redirect('/admin/usuarios.php');
        }
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());

        if ($action === 'update' && $editId > 0) {
            redirect('/admin/usuarios.php?edit=' . $editId);
        }

        redirect('/admin/usuarios.php');
    }
}

$editingUser = null;
if ($editId > 0) {
    $edit = db()->prepare("SELECT id,name,email,role,active,created_at FROM users WHERE id = ? LIMIT 1");
    $edit->execute([$editId]);
    $editingUser = $edit->fetch() ?: null;

    if (!$editingUser) {
        flash('warning', 'Usuário não encontrado.');
        redirect('/admin/usuarios.php');
    }
}

$users = db()->query("SELECT id,name,email,role,active,created_at FROM users ORDER BY id")->fetchAll();

$pageTitle = 'Usuários • Painel';
require __DIR__ . '/partials/header.php';
?>
<div class="mb-4">
    <h1 class="h3 fw-bold mb-1">Usuários</h1>
    <p class="text-secondary mb-0">DEV pode criar e editar usuários. ADMIN gerencia campanhas existentes e participações.</p>
</div>

<div class="row g-4">
    <div class="col-xl-5">
        <div class="admin-card p-4">
            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h5 fw-bold mb-1"><?= $editingUser ? 'Editar usuário' : 'Novo usuário' ?></h2>
                    <?php if ($editingUser): ?>
                        <p class="small text-secondary mb-0">Atualize os dados e salve as alterações.</p>
                    <?php endif; ?>
                </div>
                <?php if ($editingUser): ?>
                    <a class="btn btn-sm btn-light border" href="<?= e(url('/admin/usuarios.php')) ?>">
                        <i class="bi bi-x-lg me-1"></i>Cancelar
                    </a>
                <?php endif; ?>
            </div>

            <form method="post" class="vstack gap-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="<?= $editingUser ? 'update' : 'create' ?>">
                <?php if ($editingUser): ?>
                    <input type="hidden" name="user_id" value="<?= (int) $editingUser['id'] ?>">
                <?php endif; ?>

                <div>
                    <label class="form-label">Nome</label>
                    <input
                        class="form-control"
                        name="name"
                        value="<?= e((string) ($editingUser['name'] ?? '')) ?>"
                        required
                    >
                </div>

                <div>
                    <label class="form-label">E-mail</label>
                    <input
                        class="form-control"
                        type="email"
                        name="email"
                        value="<?= e((string) ($editingUser['email'] ?? '')) ?>"
                        required
                    >
                </div>

                <div>
                    <label class="form-label"><?= $editingUser ? 'Nova senha' : 'Senha' ?></label>
                    <input
                        class="form-control"
                        type="password"
                        name="password"
                        minlength="8"
                        <?= $editingUser ? '' : 'required' ?>
                    >
                    <?php if ($editingUser): ?>
                        <div class="form-text">Deixe em branco para manter a senha atual.</div>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="form-label">Perfil</label>
                    <select
                        class="form-select"
                        name="role"
                        <?= $editingUser && (int) $editingUser['id'] === (int) $currentUser['id'] ? 'disabled' : '' ?>
                    >
                        <?php $selectedRole = (string) ($editingUser['role'] ?? 'admin'); ?>
                        <option value="admin" <?= $selectedRole === 'admin' ? 'selected' : '' ?>>ADMIN</option>
                        <option value="dev" <?= $selectedRole === 'dev' ? 'selected' : '' ?>>DEV</option>
                    </select>

                    <?php if ($editingUser && (int) $editingUser['id'] === (int) $currentUser['id']): ?>
                        <input type="hidden" name="role" value="<?= e((string) $editingUser['role']) ?>">
                        <div class="form-text">Seu próprio perfil não pode ser alterado nesta tela.</div>
                    <?php endif; ?>
                </div>

                <button class="btn btn-primary">
                    <i class="bi bi-<?= $editingUser ? 'check2-circle' : 'person-plus' ?> me-2"></i>
                    <?= $editingUser ? 'Salvar alterações' : 'Criar usuário' ?>
                </button>
            </form>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="admin-card overflow-hidden">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Usuário</th>
                            <th>Perfil</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $item): ?>
                    <tr class="<?= $editingUser && (int) $editingUser['id'] === (int) $item['id'] ? 'table-primary' : '' ?>">
                        <td>
                            <div class="fw-semibold">
                                <?= e($item['name']) ?>
                                <?php if ((int) $item['id'] === (int) $currentUser['id']): ?>
                                    <span class="badge text-bg-light border ms-1">Você</span>
                                <?php endif; ?>
                            </div>
                            <div class="small text-secondary"><?= e($item['email']) ?></div>
                        </td>
                        <td><span class="badge text-bg-dark"><?= e(strtoupper($item['role'])) ?></span></td>
                        <td>
                            <?= $item['active']
                                ? '<span class="badge text-bg-success">Ativo</span>'
                                : '<span class="badge text-bg-secondary">Inativo</span>' ?>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                <a
                                    class="btn btn-sm btn-outline-primary"
                                    href="<?= e(url('/admin/usuarios.php?edit=' . (int) $item['id'])) ?>"
                                >
                                    <i class="bi bi-pencil-square me-1"></i>Editar
                                </a>

                                <?php if ((int) $item['id'] !== (int) $currentUser['id']): ?>
                                    <form method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="user_id" value="<?= (int) $item['id'] ?>">
                                        <button
                                            class="btn btn-sm btn-outline-secondary"
                                            onclick="return confirm('<?= $item['active'] ? 'Desativar este usuário?' : 'Ativar este usuário?' ?>')"
                                        >
                                            <?= $item['active'] ? 'Desativar' : 'Ativar' ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
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
