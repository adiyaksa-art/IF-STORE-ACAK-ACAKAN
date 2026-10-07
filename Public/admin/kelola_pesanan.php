<?php
// File: public/admin/kelola_pesanan.php
session_start();
require_once __DIR__ . '/../../app/core/Database.php';

// 1. Cek Admin
if (!isset($_SESSION['user_id']) || (isset($_SESSION['level']) && $_SESSION['level'] !== 'admin')) {
    header("Location: login.php");
    exit;
}

$db = Database::getConnection();

// 2. LOGIC UPDATE STATUS PESANAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];

    $stmt = $db->prepare("UPDATE orders SET status = :status WHERE id = :id");
    $stmt->execute(['status' => $new_status, 'id' => $order_id]);
    
    // Redirect agar refresh
    echo "<script>window.location.href='kelola_pesanan.php';</script>";
    exit;
}

// 3. AMBIL SEMUA DATA PESANAN
// Kita ambil proof_image juga dari tabel orders (o.* sudah mengambil semuanya)
$query = "SELECT o.*, u.username as customer_name, u.phone as whatsapp 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          ORDER BY o.created_at DESC";
$orders = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

function formatWA($nomor) {
    $nomor = preg_replace('/[^0-9]/', '', $nomor);
    if (substr($nomor, 0, 1) == '0') {
        $nomor = '62' . substr($nomor, 1);
    }
    return $nomor;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin - Kelola Pesanan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f1f5f9; font-family: sans-serif; }
        .card-order { border: none; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .table th { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; background: #f8fafc; }
        .status-select { font-size: 0.85rem; padding: 5px 10px; border-radius: 6px; cursor: pointer; }
        .btn-wa { background-color: #25D366; color: white; border: none; }
        .btn-wa:hover { background-color: #128C7E; color: white; }
    </style>
</head>
<body class="p-4">

    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-dark"><i class="fas fa-boxes me-2"></i>Kelola Pesanan Masuk</h3>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary btn-sm" onclick="location.reload()"><i class="fas fa-sync"></i> Refresh</button>
                <a href="../halaman_admin.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Dashboard</a>
            </div>
        </div>

        <div class="card card-order p-4 bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Tanggal</th>
                            <th>Customer</th>
                            <th>Bukti Bayar</th> <th>Total</th>
                            <th>Status Alur</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">Belum ada pesanan masuk.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($orders as $order): ?>
                            <?php 
                                $st = $order['status'];
                                // Warna border dropdown berdasarkan status
                                $borderClass = 'border-secondary';
                                if($st == 'pending') $borderClass = 'border-warning text-warning';
                                if($st == 'menunggu_pembayaran') $borderClass = 'border-info text-info';
                                if($st == 'diproses') $borderClass = 'border-primary text-primary';
                                if($st == 'selesai') $borderClass = 'border-success text-success';
                                if($st == 'dibatalkan') $borderClass = 'border-danger text-danger';
                            ?>
                            <tr>
                                <td class="fw-bold text-muted">#<?= $order['id'] ?></td>
                                <td>
                                    <span class="small text-muted"><?= date('d M Y', strtotime($order['created_at'])) ?></span><br>
                                    <span class="small fw-bold"><?= date('H:i', strtotime($order['created_at'])) ?></span>
                                </td>
                                
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?></div>
                                    <?php if (!empty($order['whatsapp'])): ?>
                                        <a href="https://wa.me/<?= formatWA($order['whatsapp']) ?>?text=Halo Kak <?= $order['customer_name'] ?>, mau konfirmasi pesanan #<?= $order['id'] ?>" 
                                           target="_blank" class="badge text-decoration-none btn-wa mt-1">
                                            <i class="fab fa-whatsapp me-1"></i> Hubungi
                                        </a>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($order['proof_image'])): ?>
                                        <a href="../uploads/bukti/<?= htmlspecialchars($order['proof_image']) ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3" style="font-size: 0.8rem;">
                                            <i class="fas fa-image me-1"></i> Lihat Foto
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-light text-secondary border">Belum Upload</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="fw-bold text-primary">Rp <?= number_format($order['total_price'], 0, ',', '.') ?></div>
                                    <div class="small text-muted text-uppercase fw-bold mt-1" style="font-size: 0.7rem;">
                                        <?= htmlspecialchars($order['payment_method'] ?? '-') ?>
                                    </div>
                                </td>

                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="update_status" value="1">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        
                                        <select name="status" class="form-select form-select-sm status-select fw-bold <?= $borderClass ?>" onchange="this.form.submit()">
                                            <option value="pending" <?= $st == 'pending' ? 'selected' : '' ?>>⏳ Pending (Baru)</option>
                                            <option value="menunggu_pembayaran" <?= $st == 'menunggu_pembayaran' ? 'selected' : '' ?>>💰 Tagih Bayar</option>
                                            <option value="diproses" <?= $st == 'diproses' ? 'selected' : '' ?>>📦 Diproses/Kirim</option>
                                            <option value="selesai" <?= $st == 'selesai' ? 'selected' : '' ?>>✅ Selesai</option>
                                            <option value="dibatalkan" <?= $st == 'dibatalkan' ? 'selected' : '' ?>>❌ Batalkan</option>
                                        </select>
                                    </form>
                                </td>

                                <td>
                                    <button class="btn btn-outline-dark btn-sm rounded-pill px-3" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalAdmin<?= $order['id'] ?>">
                                        Lihat
                                    </button>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalAdmin<?= $order['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header bg-light">
                                            <h5 class="modal-title fw-bold">Order Detail #<?= $order['id'] ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            
                                            <?php if (!empty($order['proof_image'])): ?>
                                                <div class="mb-3 p-2 border rounded bg-light text-center">
                                                    <label class="small text-muted fw-bold d-block mb-2">📸 BUKTI PEMBAYARAN</label>
                                                    <img src="../uploads/bukti/<?= htmlspecialchars($order['proof_image']) ?>" class="img-fluid rounded border" style="max-height: 250px;">
                                                </div>
                                            <?php endif; ?>

                                            <div class="mb-4">
                                                <label class="small text-muted fw-bold mb-2">ITEM PESANAN</label>
                                                <?php 
                                                    // Ambil detail barang untuk order ini
                                                    $stmtItems = $db->prepare("SELECT od.*, p.name FROM order_details od JOIN products p ON od.product_id = p.id WHERE od.order_id = ?");
                                                    $stmtItems->execute([$order['id']]);
                                                    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
                                                    foreach($items as $item):
                                                ?>
                                                <div class="d-flex justify-content-between border-bottom py-2">
                                                    <span><?= $item['name'] ?> (x<?= $item['quantity'] ?>)</span>
                                                    <span class="fw-bold text-dark">Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></span>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>

                                            <div class="row g-3">
                                                <div class="col-6">
                                                    <label class="small text-muted fw-bold d-block">METODE</label>
                                                    <span class="badge bg-primary"><?= strtoupper($order['payment_method']) ?></span>
                                                </div>
                                                <div class="col-6">
                                                    <label class="small text-muted fw-bold d-block">TOTAL TAGIHAN</label>
                                                    <span class="fw-bold text-primary">Rp <?= number_format($order['total_price'], 0, ',', '.') ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light border-0">
                                            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>