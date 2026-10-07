<?php
// File: app/Controllers/LoginController.php

// Pastikan file model ini ada di folder Models
require_once __DIR__ . '/../Models/UserAccount.php';

class LoginController {
    private $userModel;

    public function __construct() {
        $this->userModel = new UserAccount();
    }

    public function validasiLogin($username, $password) {
        // Minta Model cek data di database
        $akun = $this->userModel->cekAkun($username, $password);

        if ($akun) {
            // Jika akun ditemukan & password benar
            // Simpan data penting ke Session
            $_SESSION['user_id'] = $akun['id'];
            $_SESSION['username'] = $akun['username'];
            $_SESSION['level'] = $akun['role']; // 'admin' atau 'user'
            return true;
        } else {
            return false;
        }
    }
}
?>