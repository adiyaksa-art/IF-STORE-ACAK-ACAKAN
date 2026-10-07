<?php
// File: public/riwayat_pesanan.php
session_start();
require_once __DIR__ . '/../app/core/Database.php';

// 1. Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $db = Database::getConnection();

    // 2. LOGIC: KONFIRMASI TERIMA BARANG (Hanya jika status 'diproses')
    if (isset($_POST['action']) && $_POST['action'] === 'selesai' && isset($_POST['order_id'])) {
        $orderId = $_POST['order_id'];
        $stmtUpdate = $db->prepare("UPDATE orders SET status = 'selesai' WHERE id = :id AND user_id = :uid AND status = 'diproses'");
        $stmtUpdate->execute(['id' => $orderId, 'uid' => $user_id]);
        
        // Refresh halaman
        header("Location: riwayat_pesanan.php");
        exit;
    }

    // 3. QUERY DATA ORDERS
    $query = "SELECT * FROM orders WHERE user_id = :uid ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute(['uid' => $user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan | IF STORE</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        body { background-color: #f8fafc; font-family: 'Plus Jakarta Sans', sans-serif; color: #0f172a; }
        .navbar { background: white; border-bottom: 1px solid #e2e8f0; padding: 15px 0; }
        .navbar-brand { font-family: 'Space Mono', monospace; font-weight: 700; color: #2563eb !important; }
        
        /* Style Kartu Pesanan (Sama dengan Halaman User) */
        .card-order {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.2s;
            margin-bottom: 20px;
        }
        .card-order:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }
        
        /* STATUS BADGES */
        .status-badge { font-size: 0.75rem; padding: 6px 12px; border-radius: 50px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; font-family: 'Space Mono', monospace; }
        
        .st-pending { background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; } /* Kuning */
        .st-waiting { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; } /* Biru */
        .st-process { background: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd; } /* Biru Muda */
        .st-done { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }    /* Hijau */
        .st-cancel { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }  /* Merah */
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-cube me-2"></i>IF_STORE
            </a>
            <div class="d-flex align-items-center gap-3">
                <a href="index.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold">
                    <i class="fas fa-shopping-cart me-2"></i> Belanja
                </a>
                <a href="halaman_user.php" class="btn btn-light btn-sm rounded-pill px-3 fw-bold">
                    <i class="fas fa-user me-2"></i> Akun
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <div class="d-flex align-items-center mb-4">
                    <h4 class="fw-bold mb-0 me-auto"><i class="fas fa-history me-2 text-primary"></i>Riwayat Pesanan</h4>
                </div>

                <?php if (empty($orders)): ?>
                    <div class="text-center py-5">
                        <img src="https://cdn-icons-png.flaticon.com/512/2038/2038854.png" width="80" class="mb-3 opacity-25">
                        <h6 class="text-muted">Belum ada pesanan nih.</h6>
                        <a href="index.php" class="btn btn-primary rounded-pill mt-3">Mulai Belanja</a>
                    </div>
                <?php else: ?>
                    
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($orders as $order): ?>
                            <?php
                                // Ambil Detail Item
                                $stmtItem = $db->prepare("SELECT od.*, p.name, p.image, p.price as current_price FROM order_details od JOIN products p ON od.product_id = p.id WHERE od.order_id = ?");
                                $stmtItem->execute([$order['id']]);
                                $items = $stmtItem->fetchAll(PDO::FETCH_ASSOC);

                                // LOGIKA STATUS & TAMPILAN
                                $st = strtolower($order['status']);
                                $badge = "st-pending"; 
                                $statusLabel = "Pending";

                                if($st == 'pending') { 
                                    $badge = "st-pending"; 
                                    $statusLabel = "⏳ Menunggu Konfirmasi"; 
                                }
                                elseif($st == 'menunggu_pembayaran') { 
                                    $badge = "st-waiting"; 
                                    $statusLabel = "💳 Menunggu Pembayaran"; 
                                }
                                elseif($st == 'diproses') { 
                                    $badge = "st-process"; 
                                    $statusLabel = "📦 Diproses / Dikirim"; 
                                }
                                elseif($st == 'selesai') { 
                                    $badge = "st-done"; 
                                    $statusLabel = "✅ Selesai"; 
                                }
                                elseif($st == 'dibatalkan') { 
                                    $badge = "st-cancel"; 
                                    $statusLabel = "❌ Dibatalkan"; 
                                }
                            ?>

                            <div class="card-order">
                                <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-bold font-monospace me-2">#ORD-<?= $order['id'] ?></span>
                                        <small class="text-muted"><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></small>
                                    </div>
                                    <span class="status-badge <?= $badge ?>"><?= $statusLabel ?></span>
                                </div>

                                <div class="p-3">
                                    <?php foreach ($items as $item): ?>
                                        <div class="d-flex align-items-center gap-3 mb-2">
                                            <div class="bg-light rounded border" style="width: 50px; height: 50px; flex-shrink: 0;">
                                                <img src="uploads/<?= $item['image'] ?>" class="w-100 h-100 object-fit-cover rounded">
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold small"><?= $item['name'] ?></div>
                                                <div class="small text-muted"><?= $item['quantity'] ?> x Rp <?= number_format($item['price'],0,',','.') ?></div>
                                            </div>
                                            <div class="fw-bold small">
                                                Rp <?= number_format($item['quantity'] * $item['price'],0,',','.') ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <hr class="my-3 opacity-10">

                                    <div class="d-flex justify-content-between align-items-end">
                                        <div>
                                            <small class="text-secondary fw-bold" style="font-size: 0.7rem;">TOTAL BAYAR</small>
                                            <div class="fs-5 fw-bold text-primary">Rp <?= number_format($order['total_price'],0,',','.') ?></div>
                                        </div>

                                        <div>
                                            <?php if ($st == 'menunggu_pembayaran'): ?>
                                                <a href="https://wa.me/6281234567890?text=Halo%20Admin,%20saya%20mau%20konfirmasi%20pembayaran%20untuk%20Order%20%23<?= $order['id'] ?>" target="_blank" class="btn btn-primary btn-sm rounded-pill fw-bold px-3">
                                                    <i class="fab fa-whatsapp me-1"></i> Bayar Sekarang
                                                </a>

                                            <?php elseif ($st == 'diproses'): ?>
                                                <form method="POST" onsubmit="return confirm('Yakin barang sudah diterima?');">
                                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                    <input type="hidden" name="action" value="selesai">
                                                    <button type="submit" class="btn btn-success btn-sm rounded-pill fw-bold px-3">
                                                        <i class="fas fa-check-circle me-1"></i> Pesanan Diterima
                                                    </button>
                                                </form>

                                            <?php elseif ($st == 'selesai'): ?>
                                                <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Beli Lagi</a>
                                            
                                            <?php elseif ($st == 'pending'): ?>
                                                <button class="btn btn-light btn-sm text-muted rounded-pill px-3" disabled>Menunggu Admin...</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($st == 'menunggu_pembayaran'): ?>
                                        <div class="alert alert-info mt-3 mb-0 py-2 px-3 small border-0 bg-opacity-10 text-primary rounded-3">
                                            <i class="fas fa-info-circle me-1"></i> Admin sudah setuju. Silakan transfer agar diproses.
                                        </div>
                                    <?php endif; ?>

                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>