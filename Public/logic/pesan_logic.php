<?php
// File: public/logic/pesan_logic.php

// --- BAGIAN 1: DEBUG MODE ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Cek Jalur Database
$dbPath = __DIR__ . '/../../app/core/Database.php';
if (!file_exists($dbPath)) {
    die("FATAL ERROR: File Database.php tidak ditemukan!");
}
require_once $dbPath;

// Cek Login
if (!isset($_SESSION['user_id'])) {
    die("ERROR: Kamu belum login. <a href='../login.php'>Login dulu</a>");
}

// Cek Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("ERROR: Akses ditolak.");
}

// 1. Ambil & Validasi Data Input
$user_id = $_SESSION['user_id'];
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

// Data Tambahan dari Form
$customer_name  = htmlspecialchars($_POST['customer_name'] ?? '');
$whatsapp       = htmlspecialchars($_POST['whatsapp'] ?? '');
$pickup_loc     = htmlspecialchars($_POST['pickup_location'] ?? '');
$payment_method = $_POST['payment_method'] ?? '';

// Validasi Kelengkapan Data
if (!$product_id || !$quantity || $quantity < 1) {
    die("ERROR: Data produk atau jumlah tidak valid.");
}

if (empty($customer_name) || empty($whatsapp) || empty($pickup_loc) || empty($payment_method)) {
    die("<script>alert('Mohon lengkapi semua data (Nama, WA, Lokasi, Metode Bayar)!'); window.history.back();</script>");
}

try {
    $db = Database::getConnection();
    
    // Mulai Transaksi (Penting agar data masuk ke orders DAN order_details secara bersamaan)
    $db->beginTransaction();

    // 2. Cek Produk & Ambil Harga Asli dari DB (Jangan percaya harga dari input user)
    $stmt = $db->prepare("SELECT price, stock FROM products WHERE id = :id FOR UPDATE");
    $stmt->execute(['id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception("Produk tidak ditemukan.");
    }

    if ($product['stock'] < $quantity) {
        throw new Exception("Stok Habis! Tersisa: " . $product['stock']);
    }

    // Hitung Total Harga
    $total_price = $product['price'] * $quantity;

    // 3. INSERT KE TABEL ORDERS (HEADER)
    // PERBAIKAN DI SINI: Status diubah jadi 'pending'
    $sqlOrder = "INSERT INTO orders (
                        user_id, customer_name, whatsapp, pickup_location, 
                        payment_method, total_price, status, created_at
                     ) VALUES (
                        :uid, :name, :wa, :loc, 
                        :pay, :total, 'pending', NOW()
                     )";

    $stmtOrder = $db->prepare($sqlOrder);
    $stmtOrder->execute([
        'uid'   => $user_id,
        'name'  => $customer_name,
        'wa'    => $whatsapp,
        'loc'   => $pickup_loc,
        'pay'   => $payment_method,
        'total' => $total_price
    ]);

    // Ambil ID Order yang baru saja dibuat
    $order_id = $db->lastInsertId();

    // 4. INSERT KE TABEL ORDER_DETAILS (ITEM)
    $sqlDetail = "INSERT INTO order_details (order_id, product_id, quantity, price) 
                  VALUES (:oid, :pid, :qty, :prc)";
    
    $stmtDetail = $db->prepare($sqlDetail);
    $stmtDetail->execute([
        'oid' => $order_id,
        'pid' => $product_id,
        'qty' => $quantity,
        'prc' => $product['price']
    ]);

    // 5. Kurangi Stok Produk
    $stmtUpdate = $db->prepare("UPDATE products SET stock = stock - :qty WHERE id = :id");
    $stmtUpdate->execute([
        'qty' => $quantity,
        'id'  => $product_id
    ]);

    // Commit Transaksi
    $db->commit();

    // Redirect Berhasil (Diarahkan ke halaman_user.php)
    echo "<script>
        alert('PESANAN BERHASIL DIBUAT! Status: Menunggu Konfirmasi.');
        document.location.href = '../halaman_user.php'; 
    </script>";

} catch (PDOException $e) {
    if (isset($db)) { $db->rollBack(); }
    
    // Cek jika errornya karena tabel order_details belum ada
    if (strpos($e->getMessage(), "Table 'if_store.order_details' doesn't exist") !== false) {
        die("<div style='background:#fee2e2; color:red; padding:20px;'>
                <h3>Error Database</h3>
                <p>Tabel <b>order_details</b> belum dibuat. Silakan jalankan SQL di bawah ini di phpMyAdmin.</p>
             </div>");
    }

    die("Error Database: " . $e->getMessage());

} catch (Exception $e) {
    if (isset($db)) { $db->rollBack(); }
    die("Gagal: " . $e->getMessage());
}
?>