<?php
// File: app/models/Product.php
require_once __DIR__ . '/../core/Database.php';

class Product {
    protected $id;
    protected $name;
    protected $price;
    protected $description;
    protected $stock;
    protected $image;
    protected $created_at;

    // Constructor untuk mengisi data
    public function __construct(array $data = []) {
        if (!empty($data)) {
            $this->id = $data['id'] ?? null;
            $this->name = $data['name'] ?? null;
            $this->price = $data['price'] ?? 0;
            $this->description = $data['description'] ?? null;
            $this->stock = $data['stock'] ?? 0;
            $this->image = $data['image'] ?? 'default.jpg';
        }
    }

    // --- Setter dengan Validasi Sederhana ---
    public function setName($name) {
        if (empty($name)) throw new Exception("Nama produk tidak boleh kosong.");
        $this->name = $name;
    }

    public function setPrice($price) {
        if ($price < 0) throw new Exception("Harga tidak boleh negatif.");
        $this->price = $price;
    }

    // --- Method Penting: SAVE (Insert/Update Otomatis) ---
    public function save() {
        $db = Database::getConnection();

        try {
            if ($this->id === null) {
                // INSERT (Produk Baru)
                $sql = "INSERT INTO products (name, price, description, stock, image) VALUES (?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([$this->name, $this->price, $this->description, $this->stock, $this->image]);
                $this->id = $db->lastInsertId();
            } else {
                // UPDATE (Produk Lama)
                $sql = "UPDATE products SET name=?, price=?, description=?, stock=?, image=? WHERE id=?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$this->name, $this->price, $this->description, $this->stock, $this->image, $this->id]);
            }
            return true;
        } catch (PDOException $e) {
            throw new Exception("Database Error: " . $e->getMessage());
        }
    }

    // --- Method Static untuk Mengambil Data ---
    public static function getAll() {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM products ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Mengembalikan Array
    }
}
?>