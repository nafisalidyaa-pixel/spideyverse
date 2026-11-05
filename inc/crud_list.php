<?php
/**
 * Halaman daftar (read) untuk master data.
 * Dipakai oleh: barang.php, satuan.php, vendor.php, role.php, user.php, margin.php
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/crud.php';

$cfg = master_config($master);
cek_role($cfg['roles']);
$boleh_edit = in_array(id_role(), $cfg['edit_roles'], true);

$where = [];
$params = [];

$cari = trim($_GET['cari'] ?? '');
if ($cari !== '' && !empty($cfg['cari'])) {
    $bagian = [];
    foreach ($cfg['cari'] as $kolom) {
        $bagian[] = $kolom . ' LIKE ?';
        $params[] = '%' . $cari . '%';
    }
    $where[] = '(' . implode(' OR ', $bagian) . ')';
}

$jenis = trim($_GET['jenis'] ?? '');
if ($jenis !== '' && !empty($cfg['filter_jenis'])) {
    $where[] = 'b.jenis = ?';
    $params[] = $jenis;
}

$sql = $cfg['list_sql'];
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= $cfg['order_sql'];

$data = db_all($sql, $params);

$JUDUL = $cfg['judul'];
$AKTIF = $cfg['halaman'];
$dasar = basename($cfg['halaman'], '.php');
require __DIR__ . '/header.php';
?>

<div class="judul-halaman">
    <div>
        <div class="sub">Menampilkan <b><?= count($data) ?></b> data <?= e($cfg['judul']) ?>.</div>
    </div>
    <?php if ($boleh_edit): ?>
        <a class="btn" href="<?= e($dasar) ?>_tambah.php">+ Tambah Data</a>
    <?php else: ?>
        <span class="badge badge-biru">Hanya Lihat</span>
    <?php endif; ?>
</div>

<?php if (!empty($cfg['cari']) || !empty($cfg['filter_jenis'])): ?>
    <div class="panel">
        <form method="get" action="<?= e($cfg['halaman']) ?>">
            <div class="form-grid">
                <?php if (!empty($cfg['cari'])): ?>
                    <div>
                        <label>Cari</label>
                        <input type="text" name="cari" value="<?= e($cari) ?>" placeholder="Kata kunci...">
                    </div>
                <?php endif; ?>
                <?php if (!empty($cfg['filter_jenis'])): ?>
                    <div>
                        <label>Filter Jenis</label>
                        <select name="jenis">
                            <option value="">Semua jenis</option>
                            <?php foreach (jenis_barang() as $kode => $label): ?>
                                <option value="<?= e($kode) ?>" <?= $jenis === $kode ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
            <div class="form-aksi">
                <button type="submit" class="btn">Terapkan</button>
                <a class="btn btn-outline" href="<?= e($cfg['halaman']) ?>">Reset</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<div class="panel">
    <div class="tabel-wrap">
        <table>
            <thead>
                <tr>
                    <?php foreach ($cfg['kolom'] as $kolom): ?>
                        <th class="<?= e($kolom['class'] ?? '') ?>"><?= e($kolom['label']) ?></th>
                    <?php endforeach; ?>
                    <?php if ($boleh_edit): ?>
                        <th>Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!$data): ?>
                    <tr>
                        <td class="kosong" colspan="<?= count($cfg['kolom']) + ($boleh_edit ? 1 : 0) ?>">
                            Belum ada data.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($data as $row): ?>
                    <tr>
                        <?php foreach ($cfg['kolom'] as $kolom): ?>
                            <td class="<?= e($kolom['class'] ?? '') ?>">
                                <?= tampil_nilai($kolom['format'] ?? 'text', $row[$kolom['key']] ?? null) ?>
                            </td>
                        <?php endforeach; ?>
                        <?php if ($boleh_edit): ?>
                            <td>
                                <div class="aksi">
                                    <a class="btn btn-kecil btn-outline"
                                       href="<?= e($dasar) ?>_edit.php?id=<?= (int) $row[$cfg['pk']] ?>">Edit</a>
                                    <form method="post" action="<?= e($dasar) ?>_hapus.php"
                                          onsubmit="return confirm('Hapus data ini?')">
                                        <input type="hidden" name="id" value="<?= (int) $row[$cfg['pk']] ?>">
                                        <button type="submit" class="btn btn-kecil btn-merah">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
