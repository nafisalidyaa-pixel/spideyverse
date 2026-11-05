<?php
/**
 * Form tambah / edit master data. Halaman terpisah untuk tiap proses.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/crud.php';

$cfg = master_config($master);
cek_role($cfg['edit_roles']);
$dasar = basename($cfg['halaman'], '.php');

$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
$edit = $id > 0;

$data = [];
foreach ($cfg['fields'] as $field) {
    $data[$field['name']] = $field['default'] ?? '';
}

if ($edit) {
    $row = db_one(
        "SELECT * FROM `{$cfg['tabel']}` WHERE `{$cfg['pk']}` = ?",
        [$id]
    );
    if (!$row) {
        flash('error', 'Data tidak ditemukan.');
        header('Location: ' . $cfg['halaman']);
        exit;
    }
    foreach ($cfg['fields'] as $field) {
        if (array_key_exists($field['name'], $row)) {
            $data[$field['name']] = $row[$field['name']];
        }
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($cfg['fields'] as $field) {
        $data[$field['name']] = trim((string) ($_POST[$field['name']] ?? ''));
    }

    foreach ($cfg['fields'] as $field) {
        $name = $field['name'];
        $nilai = $data[$name];

        if (!empty($field['required']) && $nilai === '') {
            $errors[] = $field['label'] . ' wajib diisi.';
            continue;
        }

        if (!empty($field['max']) && panjang_teks($nilai) > $field['max']) {
            $errors[] = $field['label'] . ' maksimal ' . $field['max'] . ' karakter.';
        }

        if (($field['type'] ?? '') === 'number' && $nilai !== '' && !is_numeric($nilai)) {
            $errors[] = $field['label'] . ' harus berupa angka.';
        }

        if (($field['type'] ?? '') === 'number' && isset($field['min']) && $nilai !== '' && (float) $nilai < (float) $field['min']) {
            $errors[] = $field['label'] . ' minimal ' . $field['min'] . '.';
        }
    }

    if (!$errors && isset($cfg['validasi'])) {
        $pesan = $cfg['validasi']($data, $id);
        if ($pesan !== null) {
            $errors[] = $pesan;
        }
    }

    if (!$errors) {
        try {
            if ($edit) {
                $set = [];
                $params = [];
                foreach ($cfg['fields'] as $field) {
                    $set[] = '`' . $field['name'] . '` = ?';
                    $params[] = $data[$field['name']];
                }
                $params[] = $id;
                db_exec(
                    "UPDATE `{$cfg['tabel']}` SET " . implode(', ', $set) . " WHERE `{$cfg['pk']}` = ?",
                    $params
                );
                flash('sukses', $cfg['judul'] . ' berhasil diperbarui.');
            } else {
                $kolom = [];
                $placeholder = [];
                $params = [];
                foreach ($cfg['fields'] as $field) {
                    $kolom[] = '`' . $field['name'] . '`';
                    $placeholder[] = '?';
                    $params[] = $data[$field['name']];
                }
                db_exec(
                    "INSERT INTO `{$cfg['tabel']}` (" . implode(', ', $kolom) . ")
                     VALUES (" . implode(', ', $placeholder) . ")",
                    $params
                );
                flash('sukses', $cfg['judul'] . ' berhasil ditambahkan.');
            }

            redirect_dengan_log($cfg['halaman']);
        } catch (PDOException $e) {
            $errors[] = pesan_db($e);
        }
    }
}

$JUDUL = ($edit ? 'Edit ' : 'Tambah ') . $cfg['judul'];
$AKTIF = $cfg['halaman'];
require __DIR__ . '/header.php';
?>

<div class="panel">
    <h2><?= e($JUDUL) ?></h2>

    <?php foreach ($errors as $pesan): ?>
        <div class="alert alert-error"><?= e($pesan) ?></div>
    <?php endforeach; ?>

    <form method="post" action="<?= e($dasar) ?><?= $edit ? '_edit.php' : '_tambah.php' ?>">
        <?php if ($edit): ?>
            <input type="hidden" name="id" value="<?= (int) $id ?>">
        <?php endif; ?>

        <div class="form-grid">
            <?php foreach ($cfg['fields'] as $field): ?>
                <?php $name = $field['name']; ?>
                <div>
                    <label for="f_<?= e($name) ?>"><?= e($field['label']) ?></label>

                    <?php if (($field['type'] ?? 'text') === 'select'): ?>
                        <select id="f_<?= e($name) ?>" name="<?= e($name) ?>" <?= !empty($field['required']) ? 'required' : '' ?>>
                            <?php if (empty($field['required'])): ?>
                                <option value="">- pilih -</option>
                            <?php endif; ?>
                            <?php foreach (opsi_field($field) as $nilai => $label): ?>
                                <option value="<?= e($nilai) ?>"
                                    <?= (string) $data[$name] === (string) $nilai ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input
                            type="<?= e($field['type'] ?? 'text') ?>"
                            id="f_<?= e($name) ?>"
                            name="<?= e($name) ?>"
                            value="<?= e($data[$name]) ?>"
                            <?= isset($field['max']) ? 'maxlength="' . (int) $field['max'] . '"' : '' ?>
                            <?= isset($field['min']) ? 'min="' . e($field['min']) . '"' : '' ?>
                            <?= isset($field['step']) ? 'step="' . e($field['step']) . '"' : '' ?>
                            <?= !empty($field['required']) ? 'required' : '' ?>>
                    <?php endif; ?>

                    <?php if (!empty($field['help'])): ?>
                        <small><?= e($field['help']) ?></small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="form-aksi">
            <button type="submit" class="btn">Simpan</button>
            <a class="btn btn-outline" href="<?= e($cfg['halaman']) ?>">Batal</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/footer.php'; ?>
