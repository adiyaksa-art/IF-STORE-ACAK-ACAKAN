<?php
// File: public/halaman_user.php
session_start();
require_once __DIR__ . '/../app/core/Database.php';

// 1. Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Mahasiswa';

try {
    $db = Database::getConnection();

    // 2. LOGIC: KONFIRMASI TERIMA BARANG
    if (isset($_POST['action']) && $_POST['action'] === 'selesai' && isset($_POST['order_id'])) {
        $orderId = $_POST['order_id'];
        $stmtUpdate = $db->prepare("UPDATE orders SET status = 'selesai' WHERE id = :id AND user_id = :uid");
        $stmtUpdate->execute(['id' => $orderId, 'uid' => $user_id]);
        header("Location: halaman_user.php");
        exit;
    }

    // 3. QUERY UTAMA
    $query = "SELECT * FROM orders WHERE user_id = :uid ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute(['uid' => $user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_orders = count($orders);

} catch (Exception $e) {
    die("Error Database: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard User | IF STORE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;500;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { background-color: #f8fafc; font-family: 'Plus Jakarta Sans', sans-serif; }
        
        .profile-card {
            background: white; border-radius: 20px; padding: 40px 20px;
            text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            height: 100%;
        }
        .avatar-circle {
            width: 100px; height: 100px; background: #2563eb; color: white;
            font-size: 40px; font-weight: bold; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px; box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
        }
        .stats-box {
            background: #f1f5f9; border-radius: 12px; padding: 15px;
            margin-top: 20px; text-align: left;
        }
        .order-item {
            background: white; border-radius: 16px; padding: 25px;
            margin-bottom: 20px; border: 1px solid #e2e8f0;
            transition: 0.3s;
        }
        /* Animasi hover dihapus sedikit efek transformnya agar tidak bentrok dengan modal di beberapa browser lama, 
           tapi karena modal sudah dipindah ke bawah, ini aman. */
        .order-item:hover {
            box-shadow: 0 15px 30px rgba(0,0,0,0.05);
            border-color: #2563eb;
        }
        .status-badge {
            padding: 6px 14px; border-radius: 8px; font-size: 11px; 
            font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;
            display: inline-flex; align-items: center; gap: 5px;
        }
        
        /* Warna Status */
        .bg-pending { background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; }
        .bg-bayar   { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; }
        .bg-proses  { background: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd; }
        .bg-sukses  { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }
        .bg-batal   { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }
        
        .info-label { font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase; }
        .info-value { font-weight: 600; color: #0f172a; }
    </style>
</head>
<body class="py-5">

<div class="container">
    <div class="row g-4">
        
        <div class="col-lg-4">
            <div class="profile-card sticky-top" style="top: 20px; z-index: 1;">
                <div class="avatar-circle">
                    <?= strtoupper(substr($username, 0, 1)) ?>
                </div>
                <h4 class="fw-bold mb-1"><?= htmlspecialchars($username) ?></h4>
                <p class="text-muted small mb-4">Pengguna Terdaftar</p>
                
                <div class="row g-2">
                    <div class="col-6">
                        <div class="stats-box">
                            <div class="small text-muted fw-bold">ORDERS</div>
                            <div class="fs-4 fw-bold text-dark"><?= $total_orders ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stats-box">
                            <div class="small text-muted fw-bold">STATUS</div>
                            <div class="fs-4 fw-bold text-primary">Active</div>
                        </div>
                    </div>
                </div>

                <a href="logout.php" class="btn btn-danger w-100 rounded-pill mt-4 fw-bold py-2">
                    <i class="fas fa-sign-out-alt me-2"></i> Log Out
                </a>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">Riwayat Belanja</h4>
                <a href="Index.php" class="btn btn-outline-primary rounded-pill px-4 fw-bold">
                    <i class="fas fa-arrow-left me-2"></i> Kembali Belanja
                </a>
            </div>

            <?php if (empty($orders)): ?>
                <div class="text-center py-5">
                    <img src="https://illustrations.popsy.co/gray/surr-searching.svg" width="150" style="opacity:0.5" class="mb-3">
                    <h5 class="text-muted">Belum ada pesanan.</h5>
                </div>
            <?php else: ?>
                
                <?php foreach ($orders as $order): ?>
                    <?php 
                        // Ambil item
                        $stmtItems = $db->prepare("SELECT od.*, p.name, p.image, p.price FROM order_details od JOIN products p ON od.product_id = p.id WHERE od.order_id = :oid");
                        $stmtItems->execute(['oid' => $order['id']]);
                        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
                        
                        $firstItem = $items[0] ?? [];
                        $countItems = count($items);
                        $productName = $firstItem['name'] ?? 'Produk tidak tersedia';
                        $productImage = $firstItem['image'] ?? 'default.jpg';
                        
                        $st = strtolower($order['status']);
                        
                        // Mapping Status
                        if ($st == 'pending') { $badgeClass = 'bg-pending'; $labelStatus = '<i class="fas fa-clock"></i> Menunggu Konfirmasi'; } 
                        elseif ($st == 'menunggu_pembayaran') { $badgeClass = 'bg-bayar'; $labelStatus = '<i class="fas fa-wallet"></i> Menunggu Pembayaran'; }
                        elseif ($st == 'diproses' || $st == 'di acc') { $badgeClass = 'bg-proses'; $labelStatus = '<i class="fas fa-box"></i> Sedang Diproses'; }
                        elseif ($st == 'dikirim') { $badgeClass = 'bg-proses'; $labelStatus = '<i class="fas fa-truck"></i> Sedang Dikirim'; }
                        elseif ($st == 'selesai') { $badgeClass = 'bg-sukses'; $labelStatus = '<i class="fas fa-check-circle"></i> Selesai'; }
                        else { $badgeClass = 'bg-batal'; $labelStatus = '<i class="fas fa-times-circle"></i> Dibatalkan'; }
                    ?>

                    <div class="order-item">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex gap-2 text-muted small">
                                <i class="far fa-calendar-alt"></i> <?= date('d M Y, H:i', strtotime($order['created_at'])) ?>
                                <span class="mx-1 text-secondary">|</span>
                                <span class="fw-bold text-dark">ID #<?= $order['id'] ?></span>
                            </div>
                            <span class="status-badge <?= $badgeClass ?>"><?= $labelStatus ?></span>
                        </div>

                        <div class="d-flex align-items-center gap-4">
                            <img src="uploads/<?= htmlspecialchars($productImage) ?>" class="rounded-3" style="width: 80px; height: 80px; object-fit: cover; background:#eee;">
                            <div class="flex-grow-1">
                                <h5 class="fw-bold mb-1"><?= htmlspecialchars($productName) ?></h5>
                                <div class="text-muted small">
                                    <?php if($countItems > 1): ?>
                                        <span class="text-primary fw-bold">+<?= $countItems - 1 ?> produk lainnya</span> • 
                                    <?php endif; ?>
                                    Total Tagihan:
                                </div>
                                <div class="fs-5 fw-bold text-primary mt-1">Rp <?= number_format($order['total_price'], 0, ',', '.') ?></div>
                            </div>
                        </div>

                        <hr class="my-3 opacity-25">

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="flex-grow-1">
                                <?php if ($st == 'pending'): ?>
                                    <small class="text-muted"><i class="fas fa-info-circle"></i> Mohon tunggu admin konfirmasi stok.</small>

                                <?php elseif ($st == 'menunggu_pembayaran'): ?>
                                    <?php if (empty($order['proof_image'])): ?>
                                        <button class="btn btn-primary rounded-pill px-4 btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalUpload<?= $order['id'] ?>">
                                            <i class="fas fa-upload me-1"></i> Upload Bukti
                                        </button>
                                        <div class="mt-1"><small class="text-danger fw-bold" style="font-size: 0.7rem;">*Silakan transfer & upload bukti.</small></div>
                                    <?php else: ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-info text-dark"><i class="fas fa-check-circle"></i> Bukti Terkirim</span>
                                            <a href="uploads/bukti/<?= htmlspecialchars($order['proof_image']) ?>" target="_blank" class="btn btn-sm btn-outline-info" style="font-size: 0.75rem;">Lihat Foto</a>
                                        </div>
                                        <small class="text-muted" style="font-size: 0.7rem;">Menunggu verifikasi admin...</small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                            <div class="d-flex gap-2">
                                <button class="btn btn-dark rounded-pill px-4 btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $order['id'] ?>">Detail</button>
                                
                                <?php if ($st == 'dikirim'): ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Konfirmasi bahwa pesanan sudah Anda terima?');">
                                        <input type="hidden" name="action" value="selesai">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <button class="btn btn-success rounded-pill px-4 btn-sm fw-bold"><i class="fas fa-check me-1"></i> Pesanan Selesai</button>
                                    </form>
                                <?php endif; ?>

                                <a href="https://wa.me/6281234567890?text=Halo admin, saya mau tanya pesanan ID #<?= $order['id'] ?>" class="btn btn-outline-success rounded-pill px-3 btn-sm fw-bold" target="_blank"><i class="fab fa-whatsapp"></i> Chat</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($orders)): ?>
    <?php foreach ($orders as $order): ?>
        <?php 
            // Ambil ulang item untuk modal detail
            $stmtItems = $db->prepare("SELECT od.*, p.name FROM order_details od JOIN products p ON od.product_id = p.id WHERE od.order_id = :oid");
            $stmtItems->execute(['oid' => $order['id']]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <div class="modal fade" id="modalUpload<?= $order['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Upload Bukti Order #<?= $order['id'] ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="logic/upload_bukti.php" method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">File Bukti Transfer</label>
                                <input type="file" name="bukti_bayar" class="form-control" required accept="image/*">
                                <div class="form-text text-muted">Format: JPG, PNG, JPEG. Maks 2MB.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary rounded-pill px-4">Kirim Bukti</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalDetail<?= $order['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 p-3">
                    <div class="modal-header border-bottom-0">
                        <h5 class="modal-title fw-bold">Detail Pesanan #<?= $order['id'] ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="bg-light p-3 rounded-3 mb-3">
                            <div class="info-label mb-1">Lokasi Pengambilan</div>
                            <div class="info-value mb-3"><i class="fas fa-map-marker-alt text-danger me-2"></i><?= htmlspecialchars($order['pickup_location'] ?? '-') ?></div>
                            <div class="info-label mb-1">Metode Pembayaran</div>
                            <div class="info-value"><?= strtoupper($order['payment_method'] ?? 'Transfer') ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="info-label mb-2">Item Belanja</div>
                            <ul class="list-group list-group-flush">
                                <?php foreach($items as $item): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <div class="small">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($item['name']) ?></div>
                                            <div class="text-muted"><?= $item['quantity'] ?> x Rp <?= number_format($item['price'], 0, ',', '.') ?></div>
                                        </div>
                                        <div class="fw-bold">Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <span class="fw-bold text-muted">Total Pembayaran</span>
                            <span class="fs-5 fw-bold text-primary">Rp <?= number_format($order['total_price'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php endforeach; ?>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>