<?php
// File: public/admin_pages/overview.php

// --- 1. LOGIC DATA (DATABASE) ---

// A. Total User
$totalUser = $db->query("SELECT COUNT(*) FROM users WHERE level = 'user'")->fetchColumn(); 

// B. Total Pendapatan (Status 'selesai')
$totalUang = $db->query("SELECT SUM(total_price) FROM orders WHERE status = 'selesai'")->fetchColumn();
$totalUang = $totalUang ?: 0;

// C. Pesanan Pending/Diproses
$totalPending = $db->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'diproses')")->fetchColumn();

// D. Stok Menipis (< 5)
$stmtStok = $db->query("SELECT name, stock, image FROM products WHERE stock < 5 ORDER BY stock ASC");
$stokMenipis = $stmtStok->fetchAll(PDO::FETCH_ASSOC);

// E. 5 Transaksi Terakhir
$queryRecent = "SELECT o.*, u.username 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                ORDER BY o.created_at DESC LIMIT 5";
$recentOrders = $db->query($queryRecent)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex align-items-end justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1">Dashboard Overview</h3>
        <p class="text-secondary small mb-0">Pantau performa harian IF STORE.</p>
    </div>
    <div class="d-none d-md-block">
        <div class="bg-white px-4 py-2 rounded-pill border shadow-sm small font-mono fw-bold text-secondary">
            <i class="far fa-calendar-alt me-2 text-primary"></i> <?= date('d F Y') ?>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="main-card p-4 d-flex flex-column justify-content-between h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-secondary fw-bold small" style="font-size: 0.75rem; letter-spacing: 1px;">PENDAPATAN BERSIH</span>
                <div class="bg-success bg-opacity-10 text-success p-2 rounded-3">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>
            <div>
                <h2 class="fw-bold m-0 font-mono text-dark">Rp <?= number_format($totalUang, 0, ',', '.') ?></h2>
                <span class="small text-success fw-bold"><i class="fas fa-arrow-up"></i> Updated just now</span>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="main-card p-4 d-flex flex-column justify-content-between h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-secondary fw-bold small" style="font-size: 0.75rem; letter-spacing: 1px;">PERLU DIPROSES</span>
                <div class="bg-warning bg-opacity-10 text-warning p-2 rounded-3">
                    <i class="fas fa-box-open"></i>
                </div>
            </div>
            <div>
                <h2 class="fw-bold m-0 font-mono text-dark"><?= $totalPending ?> <span class="fs-6 text-muted">Pesanan</span></h2>
                <span class="small text-warning fw-bold">Segera proses!</span>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="main-card p-4 d-flex flex-column justify-content-between h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-secondary fw-bold small" style="font-size: 0.75rem; letter-spacing: 1px;">TOTAL PELANGGAN</span>
                <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div>
                <h2 class="fw-bold m-0 font-mono text-dark"><?= $totalUser ?> <span class="fs-6 text-muted">Akun</span></h2>
                <span class="small text-primary fw-bold">Active users</span>
            </div>
        </div>
    </div>
</div>

<?php if(!empty($stokMenipis)): ?>
<div class="alert bg-white border-danger border-start border-4 shadow-sm rounded-3 p-4 mb-4">
    <div class="d-flex align-items-center gap-3 mb-3">
        <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
            <i class="fas fa-bell small"></i>
        </div>
        <h6 class="fw-bold m-0 text-danger">Peringatan: Stok Produk Menipis!</h6>
    </div>
    <div class="d-flex gap-3 flex-wrap">
        <?php foreach($stokMenipis as $sm): ?>
            <div class="d-flex align-items-center bg-light border rounded-pill pe-3 ps-1 py-1">
                <img src="uploads/<?= $sm['image'] ?>" class="rounded-circle me-2" style="width: 30px; height: 30px; object-fit: cover;" onerror="this.src='https://placehold.co/50'">
                <span class="small fw-bold text-dark me-2"><?= htmlspecialchars($sm['name']) ?></span>
                <span class="badge bg-danger text-white font-mono">Sisa: <?= $sm['stock'] ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="main-card">
    <div class="d-flex justify-content-between align-items-center p-4 border-bottom">
        <h5 class="fw-bold m-0">Transaksi Terakhir</h5>
        <a href="?page=pesanan" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold small">
            Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Waktu</th>
                    <th>Pelanggan</th>
                    <th>Item</th>
                    <th class="text-end pe-4">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($recentOrders)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-5">Belum ada data transaksi.</td></tr>
                <?php else: ?>
                    <?php foreach($recentOrders as $ro): ?>
                        <?php 
                            // Logic Badge Status (Sesuai halaman_admin.php)
                            $st = strtolower($ro['status']);
                            $cls = 'bg-status-pending'; 
                            if($st == 'dikonfirmasi' || $st == 'diproses') $cls = 'bg-status-acc';
                            if($st == 'selesai') $cls = 'bg-status-done';
                            if($st == 'dibatalkan') $cls = 'bg-status-cancel';

                            // Logic Ambil Nama Produk (Query simpel di dalam loop untuk display)
                            $stmtItem = $db->prepare("SELECT p.name FROM order_details od JOIN products p ON od.product_id = p.id WHERE od.order_id = ?");
                            $stmtItem->execute([$ro['id']]);
                            $items = $stmtItem->fetchAll(PDO::FETCH_COLUMN);
                            $barangStr = implode(', ', $items);
                            if (strlen($barangStr) > 40) $barangStr = substr($barangStr, 0, 40) . '...';
                        ?>
                        <tr>
                            <td class="ps-4 text-secondary small font-mono">
                                <?= date('d/m H:i', strtotime($ro['created_at'])) ?>
                            </td>
                            <td>
                                <span class="fw-bold text-dark d-block">
                                    <?= htmlspecialchars($ro['customer_name'] ?: ($ro['username'] ?? 'Guest')) ?>
                                </span>
                            </td>
                            <td class="small text-muted">
                                <i class="fas fa-shopping-bag me-1 text-secondary opacity-50"></i> <?= htmlspecialchars($barangStr) ?>
                            </td>
                            <td class="text-end pe-4">
                                <span class="badge-status <?= $cls ?>"><?= strtoupper($st) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>