<?php
// File: app/models/UserAccount.php
require_once __DIR__ . '/../core/Database.php'; // Pastikan path ke file Database.php kamu benar

class UserAccount {
    private $db;

    public function __construct() {
        // PERUBAHAN DISINI:
        // Memanggil method static getConnection() dari class Database kamu
        $this->db = Database::getConnection();
    }

    public function cekAkun($username, $password) {
        try {
            // Kita gunakan prepared statement agar aman dari SQL Injection
            $query = "SELECT * FROM users WHERE username = :username LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":username", $username);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                // Cek password (sesuaikan dengan logic password kamu, misal pakai password_verify)
                if ($password == $user['password']) {
                    return $user; // Kembalikan data user jika sukses
                }
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>