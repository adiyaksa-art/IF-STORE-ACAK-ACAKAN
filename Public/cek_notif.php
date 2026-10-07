<?php
// Masukkan koneksi database kamu di sini
include '../config/database.php'; // Sesuaikan path koneksi kamu
session_start();

header('Content-Type: application/json');

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'empty']);
    exit;
}

$my_id = $_SESSION['user_id'];

// Cari notifikasi yang BELUM DIBACA (is_read = 0) untuk user ini
$query = "SELECT * FROM notifications WHERE user_id = '$my_id' AND is_read = 0 LIMIT 1";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    
    // Tandai sudah dibaca supaya tidak muncul berulang-ulang
    $update = "UPDATE notifications SET is_read = 1 WHERE id = " . $row['id'];
    mysqli_query($conn, $update);

    // Kirim data ke Javascript
    echo json_encode([
        'status' => 'success',
        'message' => $row['message']
    ]);
} else {
    echo json_encode(['status' => 'empty']);
}
?>