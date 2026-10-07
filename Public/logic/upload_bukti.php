<?php
// File: public/logic/upload_bukti.php
session_start();
require_once __DIR__ . '/../../app/core/Database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Cek apakah ada file yang dikirim
    if (!isset($_FILES['bukti_bayar']) || $_FILES['bukti_bayar']['error'] != UPLOAD_ERR_OK) {
        echo "<script>alert('Gagal: Pilih file bukti pembayaran terlebih dahulu!'); window.history.back();</script>";
        exit;
    }

    $order_id = $_POST['order_id'];
    
    // --- SETUP FOLDER ---
    // Arahkan ke folder public/uploads/bukti
    $target_dir = __DIR__ . '/../uploads/bukti/';
    
    // Cek folder, jika belum ada BUAT OTOMATIS
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // --- PROSES UPLOAD ---
    $file_extension = pathinfo($_FILES['bukti_bayar']['name'], PATHINFO_EXTENSION);
    // Nama file unik: bukti_ID_TIMESTAMP.jpg
    $new_filename = "bukti_" . $order_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($_FILES["bukti_bayar"]["tmp_name"], $target_file)) {
        try {
            $db = Database::getConnection();
            // Update gambar DAN kembalikan status ke 'pending' agar admin ngeh ada update
            $stmt = $db->prepare("UPDATE orders SET proof_image = :img, status = 'pending' WHERE id = :id");
            $stmt->execute(['img' => $new_filename, 'id' => $order_id]);

            echo "<script>
                alert('Berhasil upload bukti pembayaran! Admin akan segera mengecek.');
                window.location.href='../halaman_user.php';
            </script>";
        } catch (PDOException $e) {
            echo "Error Database: " . $e->getMessage();
        }
    } else {
        echo "<script>alert('Gagal: Gagal memindahkan file ke folder uploads/bukti. Cek permission folder.'); window.history.back();</script>";
    }
}
?>