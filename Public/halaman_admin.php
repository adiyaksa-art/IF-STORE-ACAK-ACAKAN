<?php
// File: public/halaman_admin.php
session_start();

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Cek Login Admin
if(!isset($_SESSION['user_id']) || $_SESSION['level'] !== 'admin') { 
    header("Location: login.php"); exit; 
}

// 2. Koneksi Database
require_once __DIR__ . '/../app/core/Database.php';
$db = Database::getConnection();

// 3. Init Halaman
$page = isset($_GET['page']) ? $_GET['page'] : 'overview';
$selectedUserId = isset($_GET['uid']) ? $_GET['uid'] : null;

// =======================
// A. LOGIC UMUM & INIT
// =======================
$chatUsers = []; $chatHistory = []; $orders = []; $products = []; $usersList = [];
$lowStock = []; $recentSales = []; 
$isEdit = false;
$editData = ['name' => '', 'price' => '', 'stock' => '', 'description' => '', 'image' => ''];

// INIT STATS
$globalPendingCount = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

$stats = [
    'pending' => $globalPendingCount,
    'total'   => 0,
    'users'   => 0,
    'income'  => 0
];

// =======================
// B. LOGIC UPDATE STATUS (PESANAN)
// =======================
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    try {
        $stmt = $db->prepare("UPDATE orders SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $new_status, 'id' => $order_id]);
        
        // Opsional: Kirim notifikasi ke user saat status berubah
        // (Kode notifikasi bisa ditambahkan di sini jika tabel notifikasi tersedia)
        
        header("Location: ?page=pesanan"); exit;
    } catch (Exception $e) {
        die("Error DB: " . $e->getMessage());
    }
}

// =======================
// C. LOGIC CHAT (REPLY)
// =======================
if ($page == 'chat' && $selectedUserId && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_msg'])) {
    $msg = trim($_POST['reply_msg']);
    if (!empty($msg)) {
        $stmtSend = $db->prepare("INSERT INTO chats (sender_id, receiver_id, message, sender) VALUES (:admin_id, :uid, :msg, 'admin')");
        $stmtSend->execute(['admin_id' => $_SESSION['user_id'], 'uid' => $selectedUserId, 'msg' => $msg]);
        header("Location: ?page=chat&uid=$selectedUserId"); exit;
    }
}

// =======================
// D. LOGIC PRODUK (CRUD LENGKAP)
// =======================
if ($page == 'produk') {
    if (isset($_GET['hapus'])) {
        $id = $_GET['hapus'];
        $stmt = $db->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $del = $db->prepare("DELETE FROM products WHERE id = ?");
            $del->execute([$id]);
             if ($row['image'] != 'default.jpg' && file_exists("uploads/" . $row['image'])) {
                unlink("uploads/" . $row['image']);
            }
            echo "<script>alert('Produk berhasil dihapus!'); window.location='?page=produk';</script>"; exit;
        }
    }

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

    if (isset($_POST['simpan_produk'])) {
        $nama    = htmlspecialchars($_POST['nama']);
        $harga   = $_POST['harga'];
        $stok    = $_POST['stok'];
        $desc    = htmlspecialchars($_POST['desc']);
        
        $namaGambar = ($isEdit) ? $editData['image'] : 'default.jpg';

        if (!empty($_FILES['foto']['name'])) {
            $targetDir = "uploads/";
            if (!file_exists($targetDir)) { mkdir($targetDir, 0777, true); }
            
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $uniqueName = uniqid() . '.' . $ext;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetDir . $uniqueName)) {
                if ($isEdit && $editData['image'] != 'default.jpg' && file_exists($targetDir . $editData['image'])) {
                    unlink($targetDir . $editData['image']);
                }
                $namaGambar = $uniqueName;
            }
        }

        if ($isEdit) {
            $sql = "UPDATE products SET name=?, price=?, stock=?, description=?, image=? WHERE id=?";
            $q = $db->prepare($sql);
            $q->execute([$nama, $harga, $stok, $desc, $namaGambar, $_GET['edit']]);
            echo "<script>alert('Data berhasil diupdate!'); window.location='?page=produk';</script>"; exit;
        } else {
            $sql = "INSERT INTO products (name, price, stock, description, image) VALUES (?, ?, ?, ?, ?)";
            $q = $db->prepare($sql);
            $q->execute([$nama, $harga, $stok, $desc, $namaGambar]);
            echo "<script>alert('Produk baru ditambahkan!'); window.location='?page=produk';</script>"; exit;
        }
    }
}

// =======================
// E. AMBIL DATA VIEW
// =======================

// Data Statistik Overview
if ($page == 'overview') {
    $stats['total'] = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['users'] = $db->query("SELECT COUNT(*) FROM users WHERE level = 'user'")->fetchColumn();
    // Income hanya dihitung dari yang selesai
    $stats['income'] = $db->query("SELECT SUM(total_price) FROM orders WHERE status = 'selesai'")->fetchColumn();

    $lowStock = $db->query("SELECT * FROM products WHERE stock < 10 ORDER BY stock ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    // Menggunakan created_at agar tidak error
    $recentSales = $db->query("SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.status = 'selesai' ORDER BY o.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} 

// Data Chat
if ($page == 'chat') {
    $stmtUsers = $db->query("
        SELECT DISTINCT u.id, u.username, 
        (SELECT message FROM chats WHERE (sender_id = u.id OR receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_msg,
        (SELECT created_at FROM chats WHERE (sender_id = u.id OR receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_time
        FROM users u
        JOIN chats c ON u.id = c.sender_id OR u.id = c.receiver_id
        WHERE u.level = 'user'
        ORDER BY last_time DESC
    ");
    $chatUsers = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

    if ($selectedUserId) {
        $stmtChat = $db->prepare("SELECT * FROM chats WHERE (sender_id = :uid OR receiver_id = :uid) ORDER BY created_at ASC");
        $stmtChat->execute(['uid' => $selectedUserId]);
        $chatHistory = $stmtChat->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Data Pesanan
if ($page == 'pesanan') {
    $stmtOrders = $db->query("SELECT o.*, u.id as user_id FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");
    $orders = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);
}

// Data Produk
if ($page == 'produk') {
    $stmtProd = $db->query("SELECT * FROM products ORDER BY id DESC");
    $products = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
}

// Data Users
if ($page == 'users') {
    $stmtUsersList = $db->query("SELECT * FROM users WHERE level = 'user' ORDER BY created_at DESC");
    $usersList = $stmtUsersList->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | IF STORE</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #2563eb;
            --primary-soft: #eff6ff;
            --primary-glow: rgba(37, 99, 235, 0.3);
            --dark: #0f172a;
            --secondary: #64748b;
            --light-bg: #f8fafc;
            --card-bg: #ffffff;
            --radius-xl: 24px;
            --sidebar-width: 280px;
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--dark);
            overflow-x: hidden;
        }

        /* SIDEBAR STYLE */
        .sidebar {
            background: var(--card-bg);
            border-radius: var(--radius-xl);
            padding: 30px 20px;
            height: calc(100vh - 40px);
            position: sticky; top: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            display: flex; flex-direction: column;
            border: 1px solid #e2e8f0;
        }

        .brand-logo { 
            font-family: 'Space Mono', monospace; 
            font-weight: 700; 
            font-size: 1.5rem; 
            color: var(--dark);
            margin-bottom: 40px;
            padding-left: 10px;
            display: flex; align-items: center; gap: 10px;
        }
        
        .logo-img {
            height: 40px;
            width: auto;
            object-fit: contain;
        }

        .nav-link {
            color: var(--secondary);
            font-weight: 600;
            padding: 14px 20px;
            border-radius: 14px;
            margin-bottom: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex; align-items: center; gap: 14px;
            font-size: 0.95rem;
        }
        .nav-link:hover { background: #f1f5f9; color: var(--primary); transform: translateX(5px); }
        .nav-link.active { 
            background: var(--primary); 
            color: white; 
            box-shadow: 0 10px 15px -3px var(--primary-glow);
        }
        .nav-link i { width: 20px; text-align: center; font-size: 1.1rem; }

        /* CONTENT CARDS */
        .main-card {
            background: var(--card-bg);
            border-radius: var(--radius-xl);
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
            overflow: hidden;
            height: 100%;
            transition: transform 0.2s;
        }
        .main-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); }
        
        .font-mono { font-family: 'Space Mono', monospace; }
        
        /* OVERVIEW SPECIFIC */
        .stat-card { position: relative; overflow: hidden; }
        .stat-icon-bg { position: absolute; right: -10px; bottom: -10px; font-size: 8rem; opacity: 0.05; transform: rotate(-15deg); }

        /* TABLE STYLE */
        .table { --bs-table-bg: transparent; }
        .table thead th {
            background: #f8fafc; color: var(--secondary);
            font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;
            font-weight: 700; padding: 20px; border-bottom: 1px solid #e2e8f0;
        }
        .table tbody td { padding: 20px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        
        /* STATUS BADGES */
        .badge-status { padding: 8px 12px; border-radius: 8px; font-family: 'Space Mono', monospace; font-size: 11px; font-weight: 700; }
        .bg-status-pending { background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; }
        .bg-status-waiting { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; } /* Biru */
        .bg-status-process { background: #f0f9ff; color: #0284c7; border: 1px solid #e0f2fe; } /* Biru Muda */
        .bg-status-done { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }
        .bg-status-cancel { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }

        /* CHAT */
        .chat-user-list { height: 600px; overflow-y: auto; }
        .chat-item { padding: 15px; margin-bottom: 8px; border-radius: 16px; transition: 0.2s; cursor: pointer; border: 1px solid transparent; }
        .chat-item:hover { background: #f8fafc; border-color: #e2e8f0; }
        .chat-item.active { background: var(--primary-soft); border-color: #dbeafe; }
        .chat-window { background: #f8fafc; height: 500px; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 15px; }
        .bubble { padding: 12px 18px; border-radius: 18px; max-width: 75%; font-size: 14px; position: relative; }
        .bubble.admin { background: var(--primary); color: white; align-self: flex-end; border-bottom-right-radius: 4px; }
        .bubble.user { background: white; color: var(--dark); align-self: flex-start; border-bottom-left-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .bubble-time { font-size: 10px; opacity: 0.7; display: block; text-align: right; margin-top: 5px; font-family: 'Space Mono'; }

    </style>
</head>
<body class="p-3">

<div class="container-fluid">
    <div class="row g-4">
        
        <div class="col-lg-2 d-none d-lg-block" style="width: var(--sidebar-width);">
            <aside class="sidebar">
                <div class="brand-logo">
                    <img src="uploads/logo_hima.png" alt="Logo Hima" class="logo-img me-2">
                    IF_ADMIN
                </div>
                
                <nav class="nav flex-column flex-grow-1">
                    <span class="text-uppercase text-secondary fw-bold small px-3 mb-2" style="font-size: 0.7rem; letter-spacing: 1px;">Menu Utama</span>
                    <a href="?page=overview" class="nav-link <?= $page=='overview'?'active':'' ?>">
                        <i class="fas fa-chart-pie"></i> Overview
                    </a>
                    <a href="?page=pesanan" class="nav-link <?= $page=='pesanan'?'active':'' ?>">
                        <i class="fas fa-receipt"></i> Pesanan
                        <?php if($globalPendingCount > 0): ?>
                        <span class="badge bg-danger rounded-pill ms-auto font-mono"><?= $globalPendingCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="?page=produk" class="nav-link <?= $page=='produk'?'active':'' ?>">
                        <i class="fas fa-box"></i> Produk
                    </a>
                    <a href="?page=chat" class="nav-link <?= $page=='chat'?'active':'' ?>">
                        <i class="fas fa-comments"></i> Pesan
                    </a>
                </nav>

                <div class="mt-auto pt-4 border-top">
                    <a href="Index.php" class="btn btn-light w-100 mb-2 text-start small fw-bold text-secondary rounded-3">
                        <i class="fas fa-external-link-alt me-2"></i> Lihat Web
                    </a>
                    <a href="logout.php" class="btn btn-danger w-100 text-start small fw-bold rounded-3">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </aside>
        </div>

        <div class="col-lg d-flex flex-column" style="min-height: 100vh;">
            
            <?php if ($page == 'overview'): ?>
                <div class="mb-5 bg-white p-4 rounded-4 border shadow-sm d-flex justify-content-between align-items-center position-relative overflow-hidden">
                    <div class="position-relative z-2">
                        <h2 class="fw-bold mb-1">Selamat Datang, Admin! 👋</h2>
                        <p class="text-secondary mb-0">Berikut adalah ringkasan aktivitas toko hari ini.</p>
                    </div>
                    <div class="d-none d-md-block position-relative z-2">
                        <span class="font-mono text-secondary bg-light px-3 py-2 rounded-3 border">
                            <i class="far fa-calendar-alt me-2"></i> <?= date('d F Y') ?>
                        </span>
                    </div>
                    <i class="fas fa-chart-line position-absolute text-light z-1" style="font-size: 10rem; right: -20px; bottom: -40px; transform: rotate(-10deg);"></i>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="main-card p-4 d-flex flex-column justify-content-between stat-card border-warning border-opacity-25" style="background: linear-gradient(145deg, #fff7ed 0%, #ffffff 100%);">
                            <div>
                                <div class="text-warning fw-bold small mb-2 text-uppercase">Pesanan Pending</div>
                                <h2 class="fw-bold m-0 font-mono text-dark"><?= $stats['pending'] ?? 0 ?></h2>
                            </div>
                            <div class="mt-3">
                                <a href="?page=pesanan" class="text-warning small fw-bold text-decoration-none">Proses Sekarang <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                            <i class="fas fa-clock stat-icon-bg text-warning"></i>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="main-card p-4 d-flex flex-column justify-content-between stat-card border-primary border-opacity-25" style="background: linear-gradient(145deg, #eff6ff 0%, #ffffff 100%);">
                            <div>
                                <div class="text-primary fw-bold small mb-2 text-uppercase">Total Transaksi</div>
                                <h2 class="fw-bold m-0 font-mono text-dark"><?= $stats['total'] ?? 0 ?></h2>
                            </div>
                            <i class="fas fa-shopping-bag stat-icon-bg text-primary"></i>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="main-card p-4 d-flex flex-column justify-content-between stat-card border-info border-opacity-25" style="background: linear-gradient(145deg, #ecfeff 0%, #ffffff 100%);">
                            <div>
                                <div class="text-info fw-bold small mb-2 text-uppercase">Total Pelanggan</div>
                                <h2 class="fw-bold m-0 font-mono text-dark"><?= $stats['users'] ?? 0 ?></h2>
                            </div>
                            <i class="fas fa-users stat-icon-bg text-info"></i>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="main-card p-4 d-flex flex-column justify-content-between stat-card border-success border-opacity-25" style="background: linear-gradient(145deg, #f0fdf4 0%, #ffffff 100%);">
                            <div>
                                <div class="text-success fw-bold small mb-2 text-uppercase">Estimasi Income</div>
                                <h4 class="fw-bold m-0 font-mono text-dark">Rp <?= number_format(($stats['income'] ?? 0), 0, ',', '.') ?></h4>
                            </div>
                            <i class="fas fa-wallet stat-icon-bg text-success"></i>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="main-card p-4 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold m-0"><i class="fas fa-history text-primary me-2"></i>Penjualan Terakhir</h5>
                                <small class="text-muted">5 Transaksi selesai terbaru</small>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="py-2">Order ID</th>
                                            <th class="py-2">Pelanggan</th>
                                            <th class="py-2">Tanggal</th>
                                            <th class="py-2 text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($recentSales)): ?>
                                            <tr><td colspan="4" class="text-center py-3 text-muted">Belum ada data penjualan selesai.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($recentSales as $rs): ?>
                                            <tr>
                                                <td class="font-mono fw-bold">#<?= $rs['id'] ?></td>
                                                <td><?= htmlspecialchars($rs['customer_name'] ?: $rs['username']) ?></td>
                                                <td class="small text-muted"><?= date('d M Y', strtotime($rs['created_at'])) ?></td>
                                                <td class="text-end fw-bold text-success">Rp <?= number_format($rs['total_price'],0,',','.') ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="main-card p-4 h-100 border-danger border-opacity-25 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold m-0 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Stok Menipis</h5>
                                <a href="?page=produk" class="btn btn-sm btn-outline-danger rounded-pill px-3">Manage</a>
                            </div>
                            <div class="d-flex flex-column gap-3">
                                <?php if(empty($lowStock)): ?>
                                    <div class="text-center py-4 text-muted small">
                                        <i class="fas fa-check-circle text-success mb-2 fs-4"></i><br>
                                        Semua stok produk aman.
                                    </div>
                                <?php else: ?>
                                    <?php foreach($lowStock as $ls): ?>
                                    <div class="d-flex align-items-center bg-light p-2 rounded-3 border">
                                        <div class="bg-white rounded p-1 border me-3" style="width: 50px; height: 50px;">
                                            <img src="uploads/<?= $ls['image'] ?>" alt="img" class="w-100 h-100 object-fit-cover rounded">
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold small text-truncate" style="max-width: 150px;"><?= $ls['name'] ?></div>
                                            <div class="small text-muted">Rp <?= number_format($ls['price'],0,',','.') ?></div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-danger font-mono">Sisa: <?= $ls['stock'] ?></span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($page == 'pesanan'): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold mb-1">Manajemen Order</h3>
                        <p class="text-secondary small">Kelola pesanan masuk.</p>
                    </div>
                    <a href="cetak_laporan.php" target="_blank" class="btn btn-dark rounded-pill px-4 fw-bold font-mono text-sm">
                        <i class="fas fa-print me-2"></i> Laporan
                    </a>
                </div>

                <div class="main-card">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Order ID</th>
                                    <th>Pelanggan</th>
                                    <th>Chat</th> <th>Bukti Bayar</th> <th>Metode</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <?php
                                        // Load Items
                                        $stmtItems = $db->prepare("SELECT od.*, p.name FROM order_details od JOIN products p ON od.product_id = p.id WHERE od.order_id = :oid");
                                        $stmtItems->execute(['oid' => $order['id']]);
                                        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

                                        $st = strtolower($order['status']);
                                        $cls = 'bg-secondary'; 
                                        $labelStatus = ucwords(str_replace('_', ' ', $st));

                                        if($st == 'pending')             { $cls = 'bg-warning text-dark'; $labelStatus = '⏳ Pending'; }
                                        if($st == 'menunggu_pembayaran') { $cls = 'bg-status-waiting'; $labelStatus = '💰 Menunggu Pembayaran'; }
                                        if($st == 'diproses')            { $cls = 'bg-status-process'; $labelStatus = '📦 Diproses'; }
                                        if($st == 'selesai')             { $cls = 'bg-status-done'; $labelStatus = '✅ Selesai'; }
                                        if($st == 'dibatalkan')          { $cls = 'bg-status-cancel'; $labelStatus = '❌ Dibatalkan'; }
                                    ?>
                                    
                                    <tr style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#ord<?= $order['id'] ?>">
                                        <td class="ps-4 font-mono fw-bold">#ORD-<?= $order['id'] ?></td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?></div>
                                            <div class="small text-muted font-mono"><?= date('d M, H:i', strtotime($order['created_at'])) ?></div>
                                        </td>
                                        <td class="position-relative">
                                            <?php if(!empty($order['user_id'])): ?>
                                                <a href="javascript:void(0);" 
                                                   onclick="event.stopPropagation(); window.location.href='?page=chat&uid=<?= $order['user_id'] ?>';"
                                                   class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold position-relative" 
                                                   style="font-size: 0.75rem; z-index: 50;">
                                                    <i class="fas fa-comment-dots me-1"></i> Chat
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-light text-secondary border">Guest</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                        <?php if (!empty($order['proof_image'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalBukti<?= $order['id'] ?>">
                                                <i class="fas fa-image me-1"></i> Foto
                                            </button>
                                        <?php else: ?>
                                            <span class="badge bg-light text-secondary border">Belum Upload</span>
                                        <?php endif; ?>
                                    </td>
                                        <td class="font-mono small"><?= strtoupper($order['payment_method']) ?></td>
                                        <td class="fw-bold">Rp <?= number_format($order['total_price'], 0, ',', '.') ?></td>
                                        <td><span class="badge rounded-pill <?= $cls ?>"><?= $labelStatus ?></span></td>
                                        <td class="text-end pe-4"><i class="fas fa-chevron-down text-secondary"></i></td>
                                    </tr>

                                    <tr class="collapse bg-light" id="ord<?= $order['id'] ?>">
                                        <td colspan="7" class="p-4"> <div class="row g-4">
                                                <div class="col-md-5">
                                                    <div class="bg-white p-3 rounded-3 border h-100">
                                                        <h6 class="fw-bold mb-3 small text-secondary">DATA PENGIRIMAN</h6>
                                                        <ul class="list-unstyled small mb-0">
                                                            <li class="mb-2"><i class="fab fa-whatsapp me-2 text-success"></i> <?= $order['whatsapp'] ?></li>
                                                            <li class="mb-2"><i class="fas fa-map-marker-alt me-2 text-danger"></i> <?= $order['pickup_location'] ?></li>
                                                            <li class="text-muted fst-italic mt-3">Catatan: <?= htmlspecialchars($order['notes'] ?? '-') ?></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-7">
                                                    <div class="bg-white p-3 rounded-3 border">
                                                        <h6 class="fw-bold mb-3 small text-secondary">ITEM DIBELI</h6>
                                                        <?php foreach($items as $itm): ?>
                                                            <div class="d-flex justify-content-between mb-2 small border-bottom pb-2">
                                                                <span><?= $itm['name'] ?> <span class="fw-bold">x<?= $itm['quantity'] ?></span></span>
                                                                <span class="font-mono">Rp <?= number_format($itm['price']*$itm['quantity'],0,',','.') ?></span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                        
                                                        <div class="mt-4 p-3 bg-primary-soft rounded-3">
                                                            <div class="fw-bold mb-2 small text-secondary">UPDATE STATUS:</div>
                                                            <form method="POST" class="d-flex gap-2 flex-wrap">
                                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                                
                                                                <?php if($st == 'pending'): ?>
                                                                    <button type="submit" name="update_status" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold" onclick="this.form.status_input.value='menunggu_pembayaran'">
                                                                        <i class="fas fa-check me-1"></i> Accept
                                                                    </button>
                                                                    <button type="submit" name="update_status" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold" onclick="this.form.status_input.value='dibatalkan'">
                                                                        <i class="fas fa-times me-1"></i> Decline
                                                                    </button>
                                                                    <input type="hidden" name="status" id="status_input">

                                                                <?php elseif($st == 'menunggu_pembayaran'): ?>
                                                                    <div class="small text-muted me-2 pt-1"><i class="fas fa-info-circle"></i> Tunggu user bayar.</div>
                                                                    <input type="hidden" name="status" value="diproses">
                                                                    <button type="submit" name="update_status" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold">
                                                                        <i class="fas fa-money-bill-wave me-1"></i> Konfirmasi Bayar
                                                                    </button>

                                                                <?php elseif($st == 'diproses'): ?>
                                                                    <div class="small text-muted me-2 pt-1"><i class="fas fa-spinner fa-spin"></i> Sedang diproses.</div>
                                                                    <input type="hidden" name="status" value="selesai">
                                                                    <button type="submit" name="update_status" class="btn btn-sm btn-success rounded-pill px-3 fw-bold">
                                                                        <i class="fas fa-check-circle me-1"></i> Selesaikan Order
                                                                    </button>

                                                                <?php else: ?>
                                                                    <select name="status" class="form-select form-select-sm w-auto d-inline-block">
                                                                        <option value="dibatalkan">Batalkan Paksa</option>
                                                                        <option value="pending">Reset Pending</option>
                                                                    </select>
                                                                    <button type="submit" name="update_status" class="btn btn-sm btn-dark">OK</button>
                                                                <?php endif; ?>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($page == 'chat'): ?>
                <div class="row g-4 h-100">
                    <div class="col-md-4 col-lg-3">
                        <div class="bg-white rounded-4 border p-3 h-100 d-flex flex-column">
                            <h5 class="fw-bold mb-3 px-2">Pesan Masuk</h5>
                            <div class="chat-user-list pe-2" style="overflow-y: auto;">
                                <?php if(empty($chatUsers)): ?>
                                    <div class="text-center text-muted small mt-5">Belum ada pesan.</div>
                                <?php else: ?>
                                    <?php foreach($chatUsers as $cu): ?>
                                        <div onclick="window.location='?page=chat&uid=<?= $cu['id'] ?>'" 
                                             class="chat-item d-flex align-items-center gap-3 p-3 mb-2 rounded-3 border-bottom <?= ($selectedUserId == $cu['id']) ? 'bg-primary-soft border-primary' : 'bg-white' ?>" 
                                             style="cursor: pointer; transition: 0.2s;">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 45px; height: 45px; flex-shrink: 0;">
                                                <?= strtoupper(substr($cu['username'], 0, 2)) ?>
                                            </div>
                                            <div class="overflow-hidden w-100">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="fw-bold text-dark text-truncate"><?= $cu['username'] ?></div>
                                                    <span style="font-size: 0.65rem; color: #94a3b8;"><?= date('H:i', strtotime($cu['last_time'])) ?></span>
                                                </div>
                                                <div class="small text-muted text-truncate"><?= htmlspecialchars($cu['last_msg']) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8 col-lg-9">
                        <div class="bg-white rounded-4 border h-100 d-flex flex-column overflow-hidden shadow-sm">
                            <?php if($selectedUserId): ?>
                                <div class="p-3 border-bottom bg-light d-flex align-items-center gap-3">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold m-0">Chat Room</h6>
                                        <small class="text-muted">User ID: #<?= $selectedUserId ?></small>
                                    </div>
                                </div>

                                <div class="chat-window flex-grow-1 p-4" id="chatContainer" style="overflow-y: auto; background: #f8fafc;">
                                    <?php if(empty($chatHistory)): ?>
                                        <div class="text-center mt-5 text-muted small">Mulai percakapan...</div>
                                    <?php else: ?>
                                        <?php foreach($chatHistory as $chat): ?>
                                            <div class="bubble mb-3 p-3 shadow-sm <?= $chat['sender'] == 'admin' ? 'admin ms-auto bg-primary text-white' : 'user bg-white text-dark border' ?>" 
                                                 style="max-width: 75%; width: fit-content; border-radius: 15px; <?= $chat['sender'] == 'admin' ? 'border-bottom-right-radius: 2px;' : 'border-bottom-left-radius: 2px;' ?>">
                                                <div class="message-content"><?= htmlspecialchars($chat['message']) ?></div>
                                                <div class="text-end mt-1" style="font-size: 0.65rem; opacity: 0.7;">
                                                    <?= date('H:i', strtotime($chat['created_at'])) ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="p-3 border-top bg-white">
                                    <form method="POST" class="d-flex gap-2">
                                        <input type="text" name="reply_msg" class="form-control rounded-pill border-light bg-light px-4 py-2" placeholder="Ketik balasan..." required autocomplete="off">
                                        <button type="submit" class="btn btn-primary rounded-circle shadow-sm" style="width: 45px; height: 45px;"><i class="fas fa-paper-plane"></i></button>
                                    </form>
                                </div>
                                <script>var chatDiv = document.getElementById("chatContainer"); if(chatDiv) { chatDiv.scrollTop = chatDiv.scrollHeight; }</script>
                            <?php else: ?>
                                <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                                    <div class="bg-light rounded-circle p-4 mb-3"><i class="fas fa-comments fs-1 text-secondary opacity-50"></i></div>
                                    <h5 class="fw-bold">Menu Pesan</h5>
                                    <p>Pilih pelanggan untuk mulai chat.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            <?php elseif ($page == 'produk'): ?>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="main-card p-4 h-100">
                            <h5 class="fw-bold mb-4"><?= $isEdit ? '✏️ Edit Produk' : '➕ Tambah Produk' ?></h5>
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Nama Produk</label>
                                    <input type="text" name="nama" class="form-control" required value="<?= $editData['name'] ?>">
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Harga</label>
                                        <input type="number" name="harga" class="form-control" required value="<?= $editData['price'] ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Stok</label>
                                        <input type="number" name="stok" class="form-control" required value="<?= $editData['stock'] ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Deskripsi</label>
                                    <textarea name="desc" class="form-control" rows="3" required><?= $editData['description'] ?></textarea>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold">Foto</label>
                                    <input type="file" name="foto" class="form-control">
                                    <?php if($isEdit && $editData['image']): ?>
                                        <div class="mt-2 small text-muted">File saat ini: <?= $editData['image'] ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" name="simpan_produk" class="btn btn-primary fw-bold rounded-pill">Simpan Data</button>
                                    <?php if($isEdit): ?>
                                        <a href="?page=produk" class="btn btn-light fw-bold rounded-pill text-secondary">Batal</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="main-card p-0 overflow-hidden">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr><th class="ps-4">Produk</th><th>Harga</th><th>Stok</th><th class="text-end pe-4">Aksi</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($products as $p): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="uploads/<?= $p['image'] ?>" class="rounded border" style="width: 45px; height: 45px; object-fit: cover;">
                                                    <div class="fw-bold small"><?= $p['name'] ?></div>
                                                </div>
                                            </td>
                                            <td class="font-mono text-success small">Rp <?= number_format($p['price'],0,',','.') ?></td>
                                            <td><span class="badge <?= $p['stock']<5 ? 'bg-danger' : 'bg-light text-dark border' ?> rounded-pill font-mono"><?= $p['stock'] ?></span></td>
                                            <td class="text-end pe-4">
                                                <a href="?page=produk&edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-warning rounded-circle me-1"><i class="fas fa-pencil-alt"></i></a>
                                                <a href="?page=produk&hapus=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger rounded-circle" onclick="return confirm('Hapus?')"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($page == 'users'): ?>
                <h3 class="fw-bold mb-4">Data Pelanggan</h3>
                <div class="main-card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr><th class="ps-4">ID</th><th>Username</th><th>Email</th><th>No. HP</th><th>Terdaftar</th><th class="text-end pe-4">Aksi</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($usersList as $u): ?>
                                <tr>
                                    <td class="ps-4 font-mono fw-bold">#<?= $u['id'] ?></td>
                                    <td><div class="d-flex align-items-center gap-2"><div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;"><i class="fas fa-user small"></i></div><span class="fw-bold small"><?= $u['username'] ?></span></div></td>
                                    <td class="small"><?= $u['email'] ?></td>
                                    <td class="small font-mono"><?= $u['phone'] ?? '-' ?></td>
                                    <td class="small text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                                    <td class="text-end pe-4">
                                        <a href="?page=chat&uid=<?= $u['id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3 py-1" style="font-size: 0.75rem;">Chat <i class="fas fa-arrow-right ms-1"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mt-auto py-4 text-center text-secondary small">
                &copy; 2026 IF Store Admin Panel. All rights reserved.
            </div>
            <?php if (!empty($orders)): ?>
    <?php foreach($orders as $order): ?>
        <?php if (!empty($order['proof_image'])): ?>
            <div class="modal fade" id="modalBukti<?= $order['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title fw-bold">Bukti Pembayaran #<?= $order['id'] ?></h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center bg-light">
                            <img src="uploads/bukti/<?= htmlspecialchars($order['proof_image']) ?>" 
                                 class="img-fluid rounded border shadow-sm" 
                                 alt="Bukti Transfer">
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>