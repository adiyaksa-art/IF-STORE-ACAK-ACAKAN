<?php
// File: app/Controllers/PesananController.php
require_once __DIR__ . '/../core/Database.php';

class PesananController {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // [ADMIN] Ambil Semua Pesanan (+ HARGA untuk Laporan)
    public function ambilDataPesanan() {
        // PERHATIKAN: Saya menambahkan 'products.price' di sini
        $query = "SELECT orders.id, orders.status, orders.created_at,
                         users.username AS nama_pembeli, 
                         products.name AS nama_barang, 
                         products.image AS gambar_barang,
                         products.price AS harga_barang 
                  FROM orders 
                  JOIN users ON orders.user_id = users.id 
                  JOIN products ON orders.product_id = products.id 
                  ORDER BY orders.created_at DESC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // [USER] Ambil Pesanan Saya
    public function ambilPesananSaya($id_user) {
        $query = "SELECT orders.id, orders.status, products.name AS nama_barang, products.image AS gambar_barang 
                  FROM orders 
                  JOIN products ON orders.product_id = products.id 
                  WHERE orders.user_id = :uid 
                  ORDER BY orders.created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':uid' => $id_user]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // [ADMIN] Update Status
    public function prosesPesanan($data) {
        $id = $data['order_id'];
        $aksi = $data['aksi']; 
        $statusBaru = ($aksi == 'terima') ? 'SELESAI' : 'DIBATALKAN';

        $query = "UPDATE orders SET status = :status WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':status' => $statusBaru, ':id' => $id]);
    }
}
?>