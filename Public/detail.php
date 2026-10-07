<?php
// File: public/detail.php
session_start();

require_once __DIR__ . '/../app/core/Database.php';

// 1. Cek Login (Security)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_produk = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id_produk) {
    echo "Produk tidak valid.";
    exit;
}

// 2. Ambil Data Produk
try {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute(['id' => $id_produk]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ambil nama user untuk auto-fill form (opsional, biar user ga capek ngetik)
    $stmtUser = $db->prepare("SELECT username FROM users WHERE id = :uid");
    $stmtUser->execute(['uid' => $_SESSION['user_id']]);
    $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo "Produk tidak ditemukan.";
        exit;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> | IF STORE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;500;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #2563eb;
            --dark: #0f172a;
            --light-bg: #f8fafc;
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--dark);
            padding-top: 100px;
        }

        /* --- NAVBAR --- */
        .nav-island {
            position: fixed; top: 25px; left: 50%; transform: translateX(-50%);
            width: auto; min-width: 320px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid white;
            padding: 8px 12px;
            border-radius: 100px;
            box-shadow: 0 10px 40px -10px rgba(37, 99, 235, 0.15);
            z-index: 1000;
            display: flex; align-items: center; gap: 8px;
        }
        .btn-icon-nav {
            width: 40px; height: 40px; border-radius: 50%;
            background: #f1f5f9; display: flex; align-items: center; justify-content: center;
            color: var(--dark); text-decoration: none; transition: 0.2s;
        }
        .btn-icon-nav:hover { background: #e2e8f0; }

        /* --- PRODUCT DETAIL STYLES --- */
        .img-container {
            background: white; border-radius: 24px; padding: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.05); text-align: center;
            position: sticky; top: 120px; /* Biar gambar ngikut pas scroll */
        }
        .product-img {
            max-width: 100%; height: auto; max-height: 500px;
            object-fit: contain; border-radius: 12px;
        }

        .detail-card { background: transparent; padding: 10px; }
        .badge-stock {
            font-family: 'Space Mono'; font-size: 12px; padding: 5px 10px;
            background: #dbeafe; color: var(--primary); border-radius: 6px; font-weight: bold;
        }
        .price-tag {
            font-size: 2.5rem; font-weight: 800; color: var(--dark); letter-spacing: -1px;
        }

        /* --- CUSTOM FORM STYLES (NEW) --- */
        .form-section-title {
            font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;
            color: #64748b; font-weight: 700; margin-bottom: 15px; display: block;
            border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; margin-top: 30px;
        }
        .form-label-custom {
            font-size: 0.85rem; font-weight: 700; margin-bottom: 5px; display: block;
        }
        .form-input-custom {
            background: white; border: 2px solid #e2e8f0; border-radius: 12px;
            padding: 12px; width: 100%; font-family: 'Plus Jakarta Sans', sans-serif;
            transition: all 0.2s; color: var(--dark);
        }
        .form-input-custom:focus {
            border-color: var(--primary); outline: none;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        .helper-text {
            font-size: 0.75rem; color: #ef4444; margin-top: 5px; display: block; font-weight: 600;
        }

        /* QUANTITY INPUT */
        .qty-wrapper {
            display: inline-flex; align-items: center; background: white;
            border: 2px solid #e2e8f0; border-radius: 12px; padding: 5px;
        }
        .btn-qty {
            width: 35px; height: 35px; border: none; background: #f1f5f9;
            border-radius: 8px; color: var(--dark); font-weight: bold; transition: 0.2s;
        }
        .btn-qty:hover { background: #e2e8f0; }
        .input-qty {
            width: 50px; text-align: center; border: none; font-weight: bold;
            font-family: 'Space Mono'; outline: none; background: transparent;
        }
        input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

        /* TOTAL BAR */
        .total-bar {
            background: var(--dark); color: white; padding: 20px;
            border-radius: 20px; margin-top: 30px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.3);
        }
        .btn-checkout {
            background: white; color: var(--dark); font-weight: bold;
            padding: 12px 30px; border-radius: 50px; border: none;
            transition: 0.3s;
        }
        .btn-checkout:hover {
            background: var(--primary); color: white; transform: scale(1.05);
        }
        .btn-checkout:disabled {
            background: #94a3b8; color: #cbd5e1; cursor: not-allowed; transform: none;
        }

    </style>
</head>
<body>

    <nav class="nav-island">
        <a href="Index.php" class="btn-icon-nav" title="Kembali">
            <i class="fas fa-arrow-left"></i>
        </a>
        <span class="fw-bold px-3 font-monospace">DETAIL PRODUK</span>
    </nav>

    <div class="container pb-5">
        <div class="row g-5">
            
            <div class="col-lg-6">
                <div class="img-container">
                    <img src="uploads/<?= htmlspecialchars($product['image']) ?>" alt="Produk" class="product-img" onerror="this.src='https://via.placeholder.com/500?text=No+Image'">
                </div>
            </div>

            <div class="col-lg-6">
                <div class="detail-card">
                    
                    <div class="mb-3">
                        <span class="badge-stock">STOK: <?= $product['stock'] ?> PCS</span>
                    </div>

                    <h1 class="fw-bold mb-2 display-6"><?= htmlspecialchars($product['name']) ?></h1>
                    <div class="price-tag mb-4">
                        Rp <span id="displayPrice"><?= number_format($product['price'], 0, ',', '.') ?></span>
                    </div>

                    <p class="text-muted mb-4" style="line-height: 1.8;">
                        <?= nl2br(htmlspecialchars($product['description'] ?? 'Deskripsi produk belum tersedia.')) ?>
                    </p>

                    <form action="logic/pesan_logic.php" method="POST">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="price_per_item" id="rawPrice" value="<?= $product['price'] ?>">
                        
                        <span class="form-section-title">Lengkapi Data Pesanan</span>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label-custom">Nama Lengkap</label>
                                <input type="text" name="customer_name" class="form-input-custom" 
                                       value="<?= htmlspecialchars($currentUser['username'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">No. WhatsApp</label>
                                <input type="number" name="whatsapp" class="form-input-custom" placeholder="08..." required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom">Lokasi Pengambilan</label>
                            <input type="text" name="pickup_location" class="form-input-custom" placeholder="Nama Gedung / Tempat" required>
                            <span class="helper-text"><i class="fas fa-info-circle"></i> Lokasi COD wajib disekitar area Kampus UMMI.</span>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-custom">Metode Pembayaran</label>
                            <select name="payment_method" class="form-input-custom" required>
                                <option value="" disabled selected>Pilih Metode...</option>
                                <option value="cash">💵 Cash (Bayar Ditempat)</option>
                                <option value="transfer">💳 Transfer (Bank/QRIS)</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-custom">Jumlah Pesanan</label>
                            <div class="d-flex align-items-center">
                                <div class="qty-wrapper me-3">
                                    <button type="button" class="btn-qty" onclick="updateQty(-1)">-</button>
                                    <input type="number" name="quantity" id="qtyInput" class="input-qty" value="1" min="1" max="<?= $product['stock'] ?>" readonly>
                                    <button type="button" class="btn-qty" onclick="updateQty(1)">+</button>
                                </div>
                                <?php if($product['stock'] < 5 && $product['stock'] > 0): ?>
                                    <span class="text-danger small fw-bold"><i class="fas fa-fire"></i> Stok menipis!</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="total-bar">
                            <div>
                                <small class="d-block text-white-50 font-monospace" style="font-size: 10px;">TOTAL PEMBAYARAN</small>
                                <span class="fs-4 fw-bold">Rp <span id="totalPrice"><?= number_format($product['price'], 0, ',', '.') ?></span></span>
                            </div>
                            
                            <?php if ($product['stock'] > 0): ?>
                                <button type="submit" name="submit_order" class="btn-checkout">
                                    Pesan Sekarang <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            <?php else: ?>
                                <button type="button" disabled class="btn-checkout">
                                    Stok Habis
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                    </div>
            </div>

        </div>
    </div>

    <script>
        const qtyInput = document.getElementById('qtyInput');
        const pricePerItem = parseInt(document.getElementById('rawPrice').value);
        const totalPriceLabel = document.getElementById('totalPrice');
        const maxStock = <?= $product['stock'] ?>;

        function updateQty(change) {
            let currentQty = parseInt(qtyInput.value);
            let newQty = currentQty + change;

            if (newQty < 1) newQty = 1;
            if (newQty > maxStock) {
                alert('Maksimal pembelian sesuai stok tersedia: ' + maxStock);
                newQty = maxStock;
            }

            qtyInput.value = newQty;
            updateTotal(newQty);
        }

        function updateTotal(qty) {
            let total = qty * pricePerItem;
            totalPriceLabel.innerText = new Intl.NumberFormat('id-ID').format(total);
        }
    </script>

</body>
</html>