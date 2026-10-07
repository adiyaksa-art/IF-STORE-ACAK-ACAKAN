<?php
// File: public/admin_pages/produk.php

// Pastikan koneksi DB tersedia (dari halaman induk)
if (!isset($db)) {
    die("Akses langsung dilarang.");
}

// ==========================================
// 1. LOGIKA HAPUS PRODUK
// ==========================================
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];

    // Ambil nama file gambar dulu sebelum hapus data
    $stmt = $db->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Hapus Data dari DB
        $del = $db->prepare("DELETE FROM products WHERE id = ?");
        if ($del->execute([$id])) {
            // Hapus File Gambar Fisik (Jika bukan default)
            if ($row['image'] != 'default.jpg' && file_exists("uploads/" . $row['image'])) {
                unlink("uploads/" . $row['image']);
            }
            echo "<script>alert('Produk berhasil dihapus!'); window.location='?page=produk';</script>";
        }
    }
}

// ==========================================
// 2. LOGIKA PERSIAPAN EDIT (AMBIL DATA)
// ==========================================
$isEdit = false;
$editData = [
    'name' => '',
    'price' => '',
    'stock' => '',
    'description' => '',
    'image' => ''
];

if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $isEdit = true;
        $editData = $result;
    }
}

// ==========================================
// 3. LOGIKA SIMPAN (TAMBAH / UPDATE)
// ==========================================
if (isset($_POST['simpan'])) {
    $nama  = htmlspecialchars($_POST['nama']);
    $harga = $_POST['harga'];
    $stok  = $_POST['stok'];
    $desc  = htmlspecialchars($_POST['desc']);
    
    // Default gambar (jika tambah baru) atau gambar lama (jika edit)
    $namaGambar = ($isEdit) ? $editData['image'] : 'default.jpg';

    // Cek apakah ada file baru diupload
    if (!empty($_FILES['foto']['name'])) {
        $targetDir = "uploads/";
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $uniqueName = uniqid() . '.' . $ext; // Nama file acak biar gak bentrok
        
        // Proses Upload
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetDir . $uniqueName)) {
            // Jika berhasil upload baru, hapus gambar lama (kalau mode edit & bukan default)
            if ($isEdit && $editData['image'] != 'default.jpg' && file_exists($targetDir . $editData['image'])) {
                unlink($targetDir . $editData['image']);
            }
            $namaGambar = $uniqueName;
        }
    }

    if ($isEdit) {
        // --- QUERY UPDATE ---
        $sql = "UPDATE products SET name=?, price=?, stock=?, description=?, image=? WHERE id=?";
        $q = $db->prepare($sql);
        $q->execute([$nama, $harga, $stok, $desc, $namaGambar, $_GET['edit']]);
        echo "<script>alert('Data berhasil diupdate!'); window.location='?page=produk';</script>";
    } else {
        // --- QUERY INSERT ---
        $sql = "INSERT INTO products (name, price, stock, description, image) VALUES (?, ?, ?, ?, ?)";
        $q = $db->prepare($sql);
        $q->execute([$nama, $harga, $stok, $desc, $namaGambar]);
        echo "<script>alert('Produk baru ditambahkan!'); window.location='?page=produk';</script>";
    }
}

// Ambil semua data untuk tabel
$products = $db->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    
    <div class="col-lg-4 mb-4">
        <div class="content-card shadow-sm border-top border-4 <?= $isEdit ? 'border-warning' : 'border-primary' ?>">
            <h5 class="fw-bold mb-3">
                <?= $isEdit ? '<i class="fas fa-edit text-warning"></i> Edit Produk' : '<i class="fas fa-plus-circle text-primary"></i> Tambah Produk' ?>
            </h5>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Nama Produk</label>
                    <input type="text" name="nama" class="form-control form-control-custom" required value="<?= $editData['name'] ?>" placeholder="Contoh: Jaket Himpunan">
                </div>
                
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-bold text-muted">Harga (Rp)</label>
                        <input type="number" name="harga" class="form-control form-control-custom" required value="<?= $editData['price'] ?>">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-bold text-muted">Stok</label>
                        <input type="number" name="stok" class="form-control form-control-custom" required value="<?= $editData['stock'] ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Deskripsi</label>
                    <textarea name="desc" class="form-control form-control-custom" rows="3"><?= $editData['description'] ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted">Foto Produk</label>
                    <input type="file" name="foto" class="form-control form-control-custom" accept="image/*">
                    <?php if($isEdit): ?>
                        <div class="mt-2 p-2 bg-light rounded d-flex align-items-center gap-2">
                            <img src="uploads/<?= $editData['image'] ?>" width="40" height="40" class="rounded object-fit-cover">
                            <span class="small text-muted">Foto saat ini (Biarkan kosong jika tidak diganti)</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" name="simpan" class="btn <?= $isEdit ? 'btn-warning text-dark' : 'btn-primary' ?> fw-bold py-2">
                        <?= $isEdit ? 'UPDATE DATA' : 'SIMPAN PRODUK' ?>
                    </button>
                    
                    <?php if($isEdit): ?>
                        <a href="?page=produk" class="btn btn-light border text-muted">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="content-card shadow-sm">
            <h5 class="fw-bold mb-4">Daftar Katalog</h5>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Produk</th>
                            <th scope="col">Harga</th>
                            <th scope="col" class="text-center">Stok</th>
                            <th scope="col" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($products)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="fas fa-box-open fa-2x mb-2"></i><br>Belum ada data produk.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($products as $p): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="uploads/<?= $p['image'] ?>" class="rounded me-3 object-fit-cover" width="50" height="50" alt="img">
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($p['name']) ?></div>
                                            <small class="text-muted d-block text-truncate" style="max-width: 150px;">
                                                <?= htmlspecialchars($p['description']) ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-bold text-primary">Rp <?= number_format($p['price'], 0, ',', '.') ?></td>
                                <td class="text-center">
                                    <?php if($p['stock'] < 5): ?>
                                        <span class="badge bg-danger">Sisa <?= $p['stock'] ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><?= $p['stock'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="?page=produk&edit=<?= $p['id'] ?>" class="btn btn-sm btn-light border text-warning" title="Edit">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <a href="?page=produk&hapus=<?= $p['id'] ?>" class="btn btn-sm btn-light border text-danger" 
                                       onclick="return confirm('Yakin hapus produk ini permanen?')" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>