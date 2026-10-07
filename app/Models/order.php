<?php
// File: app/Models/Order.php

// Pastikan arah ke Database.php benar. 
// Jika foldermu "Core" (huruf besar), sesuaikan jadi '/../Core/Database.php'
require_once __DIR__ . '/../Core/Database.php'; 

class Order {
    private $db;

    public function __construct() {
        // Menggunakan koneksi database Singleton kamu
        $this->db = Database::getConnection();
    }

    // Mengambil semua data pesanan + Nama Pembeli + Nama Barang
    public function getAllOrders() {
        try {
            $sql = "SELECT orders.id, orders.status, orders.created_at, 
                           users.username AS nama_pembeli, 
                           products.name AS nama_barang,
                           products.image AS gambar_barang
                    FROM orders
                    JOIN users ON orders.user_id = users.id
                    JOIN products ON orders.product_id = products.id
                    ORDER BY orders.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Update status pesanan (Terima/Tolak)
    public function updateStatus($id, $status) {
        try {
            $sql = "UPDATE orders SET status = :status WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>