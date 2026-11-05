<?php
require_once __DIR__ . '/inc/koneksi.php';
require_once __DIR__ . '/inc/helpers.php';

if (!empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "SELECT u.iduser, u.username, u.password, u.idrole, r.nama_role
                 FROM `user` u
                 JOIN `role` r ON r.idrole = u.idrole
                 WHERE u.username = ?
                 LIMIT 1"
            );
            $stmt->execute([$username]);
            $data = $stmt->fetch();

            if (!$data || !hash_equals((string) $data['password'], $password)) {
                $error = 'Username atau password salah.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'iduser'    => (int) $data['iduser'],
                    'username'  => $data['username'],
                    'idrole'    => (int) $data['idrole'],
                    'nama_role' => $data['nama_role'],
                ];
                $_SESSION['flash'][] = [
                    'tipe'  => 'sukses',
                    'pesan' => 'Berhasil masuk. Selamat datang, ' . $data['username']
                        . ' (' . $data['nama_role'] . ').',
                ];
                header('Location: index.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = pesan_db($e);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPIDEYVERSE</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php render_flash(); ?>
<div class="login-wrap">
    <div class="login-kotak">
        <div class="brand">
            <div class="logo">SV</div>
            <div>
                <b>SPIDEYVERSE</b>
                <small class="brand-sub">Sistem Persediaan Barang</small>
            </div>
        </div>

        <h1 class="judul-timbul">Masuk</h1>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <div class="form-grid" style="grid-template-columns:1fr">
                <div>
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" autocomplete="username"
                           value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
                </div>
                <div>
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                </div>
            </div>
            <div class="form-aksi">
                <button type="submit" class="btn btn-glow" style="width:100%">Masuk</button>
            </div>
        </form>
    </div>
</div>
<script>
document.querySelector('.login-kotak form').addEventListener('submit', function () {
    var tombol = this.querySelector('button[type="submit"]');
    if (!tombol) {
        return;
    }
    window.setTimeout(function () {
        tombol.classList.add('btn-loading');
        tombol.disabled = true;
        tombol.textContent = 'Memproses...';
    }, 20);
});
</script>
</body>
</html>
