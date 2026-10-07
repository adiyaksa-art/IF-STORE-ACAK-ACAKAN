<?php
// File: public/cek_login.php
session_start();
require_once __DIR__ . '/../app/core/Database.php';

$username = $_POST['username'];
$password = $_POST['password'];

try {
    $db = Database::getConnection();

    // 1. Cari user berdasarkan Username
    $stmt = $db->prepare("SELECT * FROM users WHERE username = :u");
    $stmt->bindParam(':u', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Jika User ketemu, Cek Passwordnya
    if ($user) {
        if (password_verify($password, $user['password'])) {
            // LOGIN BERHASIL
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['level'] = $user['level']; // 'admin' atau 'user'

            // Arahkan sesuai Level/Role
            if ($user['level'] == 'admin') {
                header("Location: halaman_admin.php");
            } else {
                header("Location: halaman_pembeli.php");
            }
            exit;
        } else {
            // Password Salah
            header("Location: login.php?pesan=gagal_pass");
            exit;
        }
    } else {
        // Username Tidak Ditemukan
        header("Location: login.php?pesan=gagal_user");
        exit;
    }

} catch (Exception $e) {
    echo "Error Database: " . $e->getMessage();
}
?>