<?php
// File: public/admin_pages/buka_chat.php (sesuaikan lokasi foldernya)

session_start();

// 1. KONEKSI DATABASE (Sesuaikan path ini dengan struktur foldermu)
require_once __DIR__ . '/../../app/core/Database.php'; 
$db = Database::getConnection();

// 2. AMBIL ID
$user_id = $_GET['user_id'] ?? null;
$admin_id = $_SESSION['user_id'] ?? 1; // Pastikan ini ID admin yang sedang login

if (!$user_id) {
    die("Error: User ID tidak ditemukan.");
}

try {
    // 3. CEK: APAKAH SUDAH ADA ROOM CHAT ANTARA ADMIN & USER INI?
    // Asumsi nama tabelmu 'chat_rooms' atau 'chats'. Sesuaikan nama tabel di sini!
    $stmt = $db->prepare("SELECT id FROM chat_rooms WHERE user_id = :uid AND admin_id = :aid LIMIT 1");
    $stmt->execute(['uid' => $user_id, 'aid' => $admin_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($room) {
        // KASUS A: SUDAH ADA ROOM -> Ambil ID-nya
        $room_id = $room['id'];
    } else {
        // KASUS B: BELUM ADA ROOM -> Buat Room Baru
        $insert = $db->prepare("INSERT INTO chat_rooms (user_id, admin_id, created_at) VALUES (:uid, :aid, NOW())");
        $insert->execute(['uid' => $user_id, 'aid' => $admin_id]);
        $room_id = $db->lastInsertId();
    }

    // 4. REDIRECT KE HALAMAN CHAT
    // Ganti '?page=chat' sesuai link halaman chat di webmu
    header("Location: ../index.php?page=chat&room_id=" . $room_id);
    exit;

} catch (Exception $e) {
    echo "Terjadi kesalahan: " . $e->getMessage();
}
?>