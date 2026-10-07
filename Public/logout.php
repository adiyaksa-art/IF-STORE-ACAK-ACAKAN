<?php
session_start();
session_destroy(); // Hapus semua data sesi
header("Location: index.php"); // Kembalikan ke halaman utama
exit;