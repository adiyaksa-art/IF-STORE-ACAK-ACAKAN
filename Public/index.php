<?php
// File: public/Index.php
session_start();

// 1. KONEKSI DATABASE
require_once __DIR__ . '/../app/core/Database.php';

$products = []; 
$searchKeyword = isset($_GET['q']) ? trim($_GET['q']) : null;
$isLoggedIn = isset($_SESSION['user_id']); 

try { 
    $db = Database::getConnection(); 
    
    // Logic Pencarian Produk
    if ($searchKeyword) {
        $stmt = $db->prepare("SELECT * FROM products WHERE name LIKE :keyword ORDER BY id DESC");
        $stmt->execute(['keyword' => "%$searchKeyword%"]);
    } else {
        $stmt = $db->query("SELECT * FROM products ORDER BY id DESC");
    }
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Silent error
}

// Cek Admin & User ID
$isAdmin = (isset($_SESSION['level']) && $_SESSION['level'] == 'admin');
$userId = $_SESSION['user_id'] ?? null;

// --- LOGIKA NOTIFIKASI MANUAL (BADGE MERAH) ---
$notifCount = 0;
$notifLink = $isAdmin ? 'halaman_admin.php?page=pesanan' : 'halaman_user.php'; 

if ($isLoggedIn && isset($db)) {
    if ($isAdmin) {
        $stmtNotif = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
        $notifCount = $stmtNotif->fetchColumn();
    } else {
        $stmtNotif = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = :uid AND status != 'dibatalkan'");
        $stmtNotif->execute(['uid' => $userId]);
        $notifCount = $stmtNotif->fetchColumn();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IF STORE | HMIF Merch</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;500;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        :root {
            --primary: #2563eb;
            --primary-glow: rgba(37, 99, 235, 0.3);
            --dark: #0f172a;
            --light-bg: #f1f5f9;
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--dark);
            overflow-x: hidden;
        }

        /* --- BACKGROUND GLOW --- */
        .ambient-glow {
            position: absolute; width: 600px; height: 600px;
            background: radial-gradient(circle, var(--primary-glow) 0%, rgba(255,255,255,0) 70%);
            top: -200px; left: 50%; transform: translateX(-50%);
            z-index: -1; pointer-events: none;
        }

        /* --- NAVBAR ISLAND STYLE --- */
        .nav-island {
            position: fixed; top: 25px; left: 50%; transform: translateX(-50%);
            width: auto; min-width: 340px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid white;
            padding: 8px 12px;
            border-radius: 100px;
            box-shadow: 0 10px 40px -10px rgba(37, 99, 235, 0.15);
            z-index: 1000;
            display: flex; align-items: center; gap: 8px;
        }

        .nav-island .logo-area {
            background: var(--dark); color: white;
            padding: 8px 16px; border-radius: 100px;
            display: flex; align-items: center; gap: 8px;
            text-decoration: none;
        }

        .nav-island .menu-item {
            padding: 8px 16px; border-radius: 100px;
            color: #64748b; font-weight: 600; font-size: 14px;
            text-decoration: none; transition: 0.3s;
        }
        .nav-island .menu-item:hover { background: #e2e8f0; color: var(--dark); }
        .nav-island .menu-item.active { background: #eff6ff; color: var(--primary); }

        /* Search Pill */
        .search-pill {
            background: #f1f5f9; border-radius: 50px;
            padding: 2px 5px 2px 15px; display: flex; align-items: center;
            transition: 0.3s;
        }
        .search-pill:focus-within { background: #fff; box-shadow: 0 0 0 2px var(--primary-glow); }
        .search-input { border: none; background: transparent; font-size: 14px; width: 100px; outline: none; color: var(--dark); }
        
        /* Dropdown & Avatar */
        .user-avatar-sm {
            width: 35px; height: 35px; font-size: 14px;
            background: var(--primary); color: white;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-weight: bold; cursor: pointer;
        }

        /* --- HERO SECTION & LOGO ANIMATION --- */
        .hero-section { padding-top: 160px; padding-bottom: 60px; text-align: center; position: relative; }
        .hero-logo-container { margin-bottom: 20px; }
        .hero-logo {
            height: 180px; width: auto; object-fit: contain;
            filter: drop-shadow(0 10px 20px rgba(37, 99, 235, 0.25));
            transition: transform 0.3s ease;
        }
        .hero-logo:hover { transform: scale(1.05); }

        .hero-title {
            font-size: 4.5rem; font-weight: 800; line-height: 0.95; letter-spacing: -2px;
            margin-bottom: 20px; margin-top: 0;
            background: linear-gradient(180deg, #0f172a 0%, #334155 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .hero-subtitle { font-size: 1.2rem; color: #64748b; max-width: 500px; margin: 0 auto 40px; }
        
        .hero-img-container { margin-top: 20px; transform: perspective(1000px) rotateX(5deg); transition: transform 0.5s; }
        .hero-img-container:hover { transform: perspective(1000px) rotateX(0deg); }
        
        .visual-card {
            background: white; border: 1px solid #cbd5e1; border-radius: 24px; padding: 10px;
            box-shadow: 0 25px 60px -15px rgba(37, 99, 235, 0.2);
            display: inline-block; width: 100%; max-width: 500px;
        }
        .visual-inner {
            background: #f8fafc; border-radius: 18px; height: 400px; 
            display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden;
        }
        .hero-jacket-img { width: 100%; height: 100%; object-fit: contain; transition: transform 0.5s; padding: 20px; }
        .visual-card:hover .hero-jacket-img { transform: scale(1.05); }

        /* --- PRODUCT CARD --- */
        .product-card-modern {
            background: white; border-radius: 24px; padding: 0; border: none; overflow: hidden;
            transition: 0.3s cubic-bezier(0.2, 0.8, 0.2, 1); position: relative; height: 100%;
        }
        .product-card-modern:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.08); }
        
        .card-img-modern {
            height: 280px; background: #f1f5f9; display: flex; align-items: center; justify-content: center;
            position: relative; overflow: hidden;
        }
        .card-img-modern img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .product-card-modern:hover .card-img-modern img { transform: scale(1.1); }

        .hover-btn {
            position: absolute; bottom: 20px; right: 20px;
            background: var(--dark); color: white; height: 45px; padding: 0 20px;
            border-radius: 50px; display: flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); opacity: 0; transform: translateY(10px); 
            transition: 0.3s; text-decoration: none; font-family: 'Space Mono'; font-weight: bold; font-size: 12px;
        }
        .product-card-modern:hover .hover-btn { opacity: 1; transform: translateY(0); }
        .hover-btn:hover { background: var(--primary); }

        .price-badge {
            background: var(--dark); color: white; font-family: 'Space Mono'; font-size: 12px; 
            padding: 4px 10px; border-radius: 8px; float: right;
        }

        .admin-tools { position: absolute; top: 15px; right: 15px; z-index: 10; display: flex; gap: 8px; }
        .btn-tool {
            width: 35px; height: 35px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
            font-size: 14px; text-decoration: none; box-shadow: 0 4px 10px rgba(0,0,0,0.1); transition: 0.2s;
        }
        .btn-tool-edit { background: #f59e0b; color: white; }
        .btn-tool-del { background: #ef4444; color: white; }

        .card-add-product {
            border: 2px dashed #94a3b8; background: rgba(255,255,255,0.5);
            display: flex; align-items: center; justify-content: center; flex-direction: column;
            color: #64748b; cursor: pointer; height: 100%; min-height: 380px;
        }
        .card-add-product:hover { border-color: var(--primary); color: var(--primary); background: white; }

        /* Marquee */
        .marquee-container {
            background: var(--primary); color: white; padding: 12px 0; overflow: hidden; white-space: nowrap;
            font-family: 'Space Mono'; font-weight: 700; margin: 40px 0 80px; 
            border-top: 2px solid var(--dark); border-bottom: 2px solid var(--dark);
        }
        .marquee-content { display: inline-block; animation: marquee 20s linear infinite; }
        @keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }

        /* --- STYLE CHAT BOX --- */
        .chat-toggle-btn {
            position: fixed; bottom: 30px; right: 30px;
            width: 60px; height: 60px; background: var(--primary); color: white;
            border-radius: 50%; border: none; font-size: 24px;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4); z-index: 9999;
            transition: 0.3s; display: flex; align-items: center; justify-content: center; cursor: pointer;
        }
        .chat-toggle-btn:hover { transform: scale(1.1); background: #1d4ed8; }

        .chat-box-container {
            position: fixed; bottom: 100px; right: 30px; width: 350px; height: 450px;
            background: white; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            z-index: 9998; display: none; flex-direction: column; overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .chat-header { background: var(--primary); color: white; padding: 15px; font-weight: bold; display: flex; justify-content: space-between; align-items: center; }
        .chat-body { flex: 1; padding: 15px; overflow-y: auto; background: #f8fafc; display: flex; flex-direction: column; gap: 10px; }
        .chat-footer { padding: 10px; background: white; border-top: 1px solid #e2e8f0; display: flex; gap: 5px; }
        
        .chat-bubble { max-width: 80%; padding: 10px 15px; border-radius: 12px; font-size: 14px; position: relative; word-wrap: break-word; }
        .chat-user { align-self: flex-end; background: var(--primary); color: white; border-bottom-right-radius: 2px; }
        .chat-admin { align-self: flex-start; background: #e2e8f0; color: var(--dark); border-bottom-left-radius: 2px; }
        .chat-time { font-size: 10px; opacity: 0.7; margin-top: 4px; display: block; text-align: right; }

        @media (max-width: 768px) {
            .hero-title { font-size: 3rem; }
            .nav-island { bottom: 20px; top: auto; min-width: auto; width: 90%; justify-content: space-between; }
            .search-input { width: 80px; }
            .chat-box-container { width: 90%; right: 5%; bottom: 100px; }
        }
    </style>
</head>
<body>

    <div class="ambient-glow"></div>

    <nav class="nav-island">
        <a href="Index.php" class="logo-area">
            <i class="fas fa-cube"></i>
            <span class="fw-bold d-none d-sm-block font-monospace">IF_STORE</span>
        </a>
        
        <div class="d-flex align-items-center bg-light rounded-pill px-1 d-none d-md-flex">
            <a href="Index.php" class="menu-item active">Home</a>
            <a href="#katalog" class="menu-item">Katalog</a>
        </div>

        <div class="d-flex align-items-center gap-1 ms-2">
            <form action="Index.php#katalog" method="GET" class="search-pill me-1">
                <input type="text" name="q" class="search-input" placeholder="Cari..." value="<?= htmlspecialchars($searchKeyword ?? '') ?>">
                <button type="submit" class="btn border-0 p-0 text-secondary">
                    <i class="fas fa-search small"></i>
                </button>
            </form>

            <?php if ($isLoggedIn): ?>
                
                <a href="<?= $notifLink ?>" class="btn btn-light rounded-circle position-relative me-1" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-bell text-secondary"></i>
                    <?php if($notifCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light" style="font-size: 9px;">
                            <?= $notifCount ?>
                        </span>
                    <?php endif; ?>
                </a>

                <div class="dropdown">
                    <div class="user-avatar-sm" data-bs-toggle="dropdown" aria-expanded="false">
                        <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-3 rounded-4 p-2" style="min-width: 200px;">
                        <li class="px-3 py-2">
                            <span class="d-block small text-muted">Signed in as</span>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($_SESSION['username']) ?></span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        
                        <?php if($isAdmin): ?>
                            <li>
                                <a class="dropdown-item rounded-3 mb-1 fw-bold" href="halaman_admin.php">
                                    <i class="fas fa-tachometer-alt me-2 text-primary"></i> Dashboard Admin
                                </a>
                            </li>
                        <?php else: ?>
                            <li>
                                <a class="dropdown-item rounded-3 mb-1 fw-bold" href="halaman_user.php">
                                    <i class="fas fa-receipt me-2 text-primary"></i> Pesanan Saya
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <li><a class="dropdown-item rounded-3 text-danger fw-bold" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>

            <?php else: ?>
                <a href="login.php" class="btn btn-dark rounded-pill px-4 py-2 fw-bold text-decoration-none ms-2 shadow-sm" style="font-size: 14px;">Log In</a>
            <?php endif; ?>
        </div>
    </nav>

    <section class="hero-section container">
        <div class="hero-logo-container">
             <img src="uploads/logo_hima.png" alt="Logo Hima" class="hero-logo">
        </div>

        <h1 class="hero-title"><span style="color: var(--primary);">IF STORE</span></h1>
        <p class="hero-subtitle">Merchandise Eksklusif Himpunan Mahasiswa Teknik Informatika.</p>

        <div class="hero-img-container">
            <div class="visual-card">
                <div class="visual-inner">
                    <div style="position: absolute; top: 15px; right: 15px; background: #fbbf24; color: #000; padding: 6px 16px; border-radius: 30px; font-weight: 800; font-family: 'Space Mono'; z-index: 5; font-size: 12px; box-shadow: 0 4px 15px rgba(251, 191, 36, 0.4);">
                        <i class="fas fa-star me-1"></i> BEST SELLER
                    </div>
                    <img src="uploads/jaket_himpunan.jpeg" alt="Jaket Himpunan" class="hero-jacket-img" onerror="this.src='https://placehold.co/400x400/png?text=Preview+Merch'">
                    <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 20px; background: linear-gradient(to top, rgba(0,0,0,0.8), transparent); color: white;">
                        <h4 class="fw-bold m-0">LIMITED EDITION</h4>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="marquee-container">
        <div class="marquee-content">
            OFFICIAL MERCHANDISE • HMIF • JAKET HIMPUNAN • GELANG IF • TUMBLER • STIKER PACK • LIMITED STOCK • 
            OFFICIAL MERCHANDISE • HMIF • JAKET HIMPUNAN • GELANG IF • TUMBLER • STIKER PACK • LIMITED STOCK • 
        </div>
    </div>

    <div class="container pb-5" id="katalog">
        <div class="d-flex justify-content-between align-items-end mb-5 px-2">
            <div>
                <h2 class="fw-bold mb-0" style="font-size: 2rem;">
                    <?= $searchKeyword ? 'HASIL PENCARIAN' : 'KATALOG' ?>
                </h2>
                <p class="text-muted mb-0 font-monospace">
                    <?= $searchKeyword ? 'Menampilkan hasil untuk "'.htmlspecialchars($searchKeyword).'"' : 'Pilih Produk yang Kamu Inginkan.' ?>
                </p>
                <?php if($searchKeyword): ?>
                    <a href="Index.php#katalog" class="text-decoration-none small text-danger fw-bold mt-2 d-inline-block"><i class="fas fa-times"></i> Reset</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4">
            
            <?php if ($isAdmin && !$searchKeyword): ?>
            <div class="col-md-6 col-lg-3">
                <a href="halaman_admin.php?page=produk&action=tambah" class="text-decoration-none">
                    <div class="product-card-modern card-add-product h-100 p-5">
                        <i class="fas fa-plus-circle fa-4x mb-3"></i>
                        <h5 class="fw-bold">Tambah Produk</h5>
                        <p class="small text-center m-0">Klik untuk upload baru</p>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <?php if (!empty($products)): ?>
                <?php foreach($products as $prod): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="product-card-modern">
                        
                        <?php if($isAdmin): ?>
                        <div class="admin-tools">
                            <a href="halaman_admin.php?page=produk&edit=<?= $prod['id'] ?>" class="btn-tool btn-tool-edit"><i class="fas fa-pen"></i></a>
                            <a href="halaman_admin.php?page=produk&hapus=<?= $prod['id'] ?>" class="btn-tool btn-tool-del" onclick="return confirm('Hapus produk ini?');"><i class="fas fa-trash"></i></a>
                        </div>
                        <?php endif; ?>

                        <div class="card-img-modern">
                            <img src="uploads/<?= htmlspecialchars($prod['image']) ?>" alt="<?= htmlspecialchars($prod['name']) ?>" onerror="this.src='https://placehold.co/300x300?text=No+Image'">
                            
                            <?php $targetLink = $isLoggedIn ? "detail.php?id={$prod['id']}" : "login.php"; ?>
                            <a href="<?= $targetLink ?>" class="hover-btn">
                                PRE ORDER <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                        
                        <div class="card-info p-4">
                            <span class="price-badge">Rp <?= number_format($prod['price'], 0, ',', '.') ?></span>
                            <h5 class="fw-bold mb-1 text-truncate"><?= htmlspecialchars($prod['name']) ?></h5>
                            <p class="small text-muted mb-0 font-monospace">Stok: <?= $prod['stock'] ?> pcs</p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="text-muted opacity-50 mb-3"><i class="fas fa-box-open fa-3x"></i></div>
                    <h5 class="fw-bold text-muted">Produk tidak ditemukan.</h5>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <div class="container text-center py-5 mt-5 border-top">
        <p class="font-monospace text-muted small">
            &copy; 2025 IF STORE SYSTEM<i class="text-warning"></i>
        </p>
    </div>

    <?php if($isLoggedIn && !$isAdmin): ?>
    
    <button class="chat-toggle-btn" onclick="toggleChat()">
        <i class="fas fa-comment-dots"></i>
    </button>

    <div class="chat-box-container" id="chatBox">
        <div class="chat-header">
            <span><i class="fas fa-headset me-2"></i>Admin IF STORE</span>
            <button class="btn btn-sm text-white p-0" onclick="toggleChat()"><i class="fas fa-times"></i></button>
        </div>
        <div class="chat-body" id="chatBody">
            <div class="text-center text-muted small mt-5">
                <i class="fas fa-circle-notch fa-spin"></i> Memuat chat...
            </div>
        </div>
        <div class="chat-footer">
            <input type="text" id="chatInput" class="form-control border-0" placeholder="Tulis pesan..." autocomplete="off">
            <button class="btn btn-primary btn-sm rounded-circle" style="width: 40px; height: 35px;" onclick="sendMessage()">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>

    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <?php if($isLoggedIn && !$isAdmin): ?>
    <script>
        const chatBox = document.getElementById('chatBox');
        const chatBody = document.getElementById('chatBody');
        const chatInput = document.getElementById('chatInput');
        let chatInterval;

        function toggleChat() {
            if (chatBox.style.display === 'none' || chatBox.style.display === '') {
                chatBox.style.display = 'flex';
                loadMessages(); 
                chatInterval = setInterval(loadMessages, 2000); 
            } else {
                chatBox.style.display = 'none';
                clearInterval(chatInterval); 
            }
        }

        function sendMessage() {
            const msg = chatInput.value.trim();
            if(!msg) return;

            const formData = new FormData();
            formData.append('message', msg);

            fetch('chat_system.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    chatInput.value = '';
                    loadMessages(); 
                }
            })
            .catch(err => console.error(err));
        }

        chatInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') sendMessage();
        });

        function loadMessages() {
            fetch('chat_system.php')
            .then(res => res.json())
            .then(result => {
                if(result.status === 'success') {
                    let html = '';
                    if(result.data.length === 0) {
                        html = '<div class="text-center text-muted small mt-4">Halo! Ada yang bisa kami bantu?</div>';
                    } else {
                        result.data.forEach(chat => {
                            const position = (chat.sender === 'user') ? 'chat-user' : 'chat-admin';
                            const time = new Date(chat.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                            
                            html += `
                                <div class="chat-bubble ${position}">
                                    ${escapeHtml(chat.message)}
                                    <span class="chat-time">${time}</span>
                                </div>
                            `;
                        });
                    }
                    chatBody.innerHTML = html;
                    chatBody.scrollTop = chatBody.scrollHeight;
                }
            })
            .catch(err => console.error(err));
        }

        function escapeHtml(text) {
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }
    </script>
    <?php endif; ?>


    <?php if($isLoggedIn): ?>
    <script>
    $(document).ready(function() {
        // Cek Notifikasi setiap 3000ms (3 detik)
        setInterval(function() {
            $.ajax({
                url: 'cek_notif.php', // Pastikan file ini ada!
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // Tampilkan Toast SweetAlert (Pojok Kanan Atas)
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'info',
                            title: 'Pemberitahuan Baru!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 5000,
                            timerProgressBar: true,
                            background: '#fff',
                            color: '#0f172a',
                            didOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer)
                                toast.addEventListener('mouseleave', Swal.resumeTimer)
                            }
                        });
                    }
                }
            });
        }, 3000);
    });
    </script>
    <?php endif; ?>

</body>
</html>