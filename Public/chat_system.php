<?php
// File: public/chat_system.php
session_start();
require_once __DIR__ . '/../app/core/Database.php';

header('Content-Type: application/json');

// 1. Cek Login (User WAJIB Login untuk chat)
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Kamu belum login!']);
    exit;
}

$db = Database::getConnection();
$userId = $_SESSION['user_id'];
// Asumsi: ID Admin adalah 1. Kalau ID adminmu beda, ganti angka 1 ini.
$adminId = 1; 

// 2. JIKA KIRIM PESAN (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = trim($_POST['message'] ?? '');
    
    if(!empty($msg)) {
        try {
            // Masukkan pesan ke database
            $stmt = $db->prepare("INSERT INTO chats (sender_id, receiver_id, message, sender) VALUES (:uid, :aid, :msg, 'user')");
            $stmt->execute([
                'uid' => $userId,
                'aid' => $adminId,
                'msg' => $msg
            ]);
            echo json_encode(['status' => 'success']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Pesan kosong']);
    }
    exit;
}

// 3. JIKA AMBIL PESAN (GET) - Biar chat muncul otomatis
try {
    $stmt = $db->prepare("
        SELECT * FROM chats 
        WHERE (sender_id = :uid OR receiver_id = :uid) 
        ORDER BY created_at ASC
    ");
    $stmt->execute(['uid' => $userId]);
    $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $chats]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal memuat chat']);
}
?>