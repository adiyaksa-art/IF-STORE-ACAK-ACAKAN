<?php
// File: app/Controllers/ProdukController.php
require_once __DIR__ . '/../core/Database.php';

class ProdukController {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function tambahProduk($data, $file) {
        $nama = $data['nama_produk'];
        $harga = $data['harga_produk'];
        $stok = $data['stok'];
        $deskripsi = $data['deskripsi'];

        // 1. Cek & Upload Gambar
        $namaFile = $file['foto_produk']['name'];
        $tmpName = $file['foto_produk']['tmp_name'];
        $ekstensiValid = ['jpg', 'jpeg', 'png'];
        $ekstensiFile = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));

        // Validasi Ekstensi
        if (!in_array($ekstensiFile, $ekstensiValid)) {
            return "Format file harus JPG/PNG!";
        }

        // Generate nama baru (biar gak bentrok)
        $namaFileBaru = uniqid() . '.' . $ekstensiFile;
        // Pindahkan file ke folder uploads di Public
        move_uploaded_file($tmpName, __DIR__ . '/../../public/uploads/' . $namaFileBaru);

        // 2. Masukkan ke Database
        try {
            $query = "INSERT INTO products (name, price, description, stock, image) VALUES (:nama, :harga, :desc, :stok, :img)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':nama' => $nama,
                ':harga' => $harga,
                ':desc' => $deskripsi,
                ':stok' => $stok,
                ':img' => $namaFileBaru
            ]);
            return true;
        } catch (PDOException $e) {
            return "Gagal upload: " . $e->getMessage();
        }
    }
}