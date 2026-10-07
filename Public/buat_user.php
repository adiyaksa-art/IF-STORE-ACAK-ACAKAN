<?php
// File: public/buat_user.php
require_once __DIR__ . '/../app/core/Database.php';

try {
    $db = Database::getConnection();

    // --- DATA USER BARU ---
    $username = 'mahasiswa';       // Username
    $password_asli = 'mhs123';   // Password yang akan diketik saat login
    $level = 'user';          // <--- INI BEDANYA (Levelnya user/customer)

    // 1. Enkripsi Password (WAJIB)
    // Ini mengubah '123' menjadi kode acak panjang
    $password_hash = password_hash($password_asli, PASSWORD_DEFAULT);

    // 2. Cek dulu apakah tabelnya 'users' atau 'user_account'
    // Sesuai screenshot file model kamu, saya asumsikan tabel di database namanya 'users'
    // Kalau error, coba ganti 'users' jadi 'user_accounts'
    $query = "INSERT INTO users (username, password, level) VALUES (:u, :p, :l)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':u', $username);
    $stmt->bindParam(':p', $password_hash);
    $stmt->bindParam(':l', $level);
    
    $stmt->execute();

    echo "<h2 style='color:green'>BERHASIL DIBUAT!</h2>";
    echo "User: <b>$username</b><br>";
    echo "Pass: <b>$password_asli</b><br>";
    echo "Level: <b>$level</b><br>";
    echo "<hr>";
    echo "Silakan coba login sekarang: <a href='login.php'>Halaman Login</a>";

} catch (PDOException $e) {
    echo "<h2 style='color:red'>GAGAL :(</h2>";
    echo "Pesan Error: " . $e->getMessage();
    echo "<br><br><b>Tips:</b><br>";
    echo "1. Cek apakah username '$username' sudah pernah dibuat sebelumnya?<br>";
    echo "2. Cek di phpMyAdmin, apa nama tabelnya? 'users' atau 'user_account'?";
}
?>