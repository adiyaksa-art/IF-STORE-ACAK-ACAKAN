<?php
// File: public/login.php
session_start();
require_once __DIR__ . '/../app/controllers/LoginController.php';

// 1. Cek jika sudah login, langsung lempar (Auto-redirect)
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['level']) && $_SESSION['level'] == 'admin') {
        header("Location: halaman_admin.php");
    } else {
        header("Location: Index.php"); // User langsung ke Index
    }
    exit;
}

// 2. Logika Login OOP
if (isset($_POST['login'])) {
    $controller = new LoginController();
    $username = $_POST['username'];
    $password = $_POST['password'];

    // validasiLogin() diasumsikan melakukan query DB & set $_SESSION
    if ($controller->validasiLogin($username, $password)) {
        
        // Cek Level setelah login berhasil
        if ($_SESSION['level'] == 'admin') {
            header("Location: halaman_admin.php");
        } else {
            header("Location: Index.php"); // User masuk ke halaman belanja
        }
        exit;
    } else {
        $error = "Username atau Password Salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | IF STORE</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;500;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- STYLE KONSISTEN DENGAN INDEX/PROFIL --- */
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --primary-glow: rgba(37, 99, 235, 0.35);
            --dark: #0f172a;
            --light-bg: #f8fafc;
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--dark);
            height: 100vh;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Background Glow Effect */
        .ambient-glow {
            position: absolute; width: 600px; height: 600px;
            background: radial-gradient(circle, var(--primary-glow) 0%, rgba(255,255,255,0) 70%);
            top: 50%; left: 50%; transform: translate(-50%, -50%);
            z-index: -1; pointer-events: none;
        }

        /* Tombol Kembali */
        .back-link {
            position: absolute; top: 30px; left: 30px;
            text-decoration: none; color: var(--dark); font-weight: 800;
            font-family: 'Space Mono', monospace; font-size: 0.9rem;
            display: flex; align-items: center; gap: 10px;
            transition: 0.3s;
            z-index: 10;
        }
        .back-link:hover { color: var(--primary); transform: translateX(-5px); }

        /* Card Login Style */
        .login-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 24px;
            padding: 40px;
            width: 100%; max-width: 400px;
            box-shadow: 0 20px 50px -12px rgba(37, 99, 235, 0.15);
        }

        /* Form Controls */
        .form-label {
            font-size: 0.75rem; font-weight: 800; letter-spacing: 0.5px;
            color: #64748b; margin-bottom: 6px; display: block;
        }
        
        .form-control-custom {
            width: 100%;
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 12px 16px;
            font-family: 'Space Mono', monospace;
            font-size: 14px;
            color: var(--dark);
            transition: all 0.2s;
        }
        .form-control-custom:focus {
            background: #fff;
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        /* Button Style */
        .btn-primary-custom {
            background: var(--primary); color: white;
            border: none; padding: 14px; border-radius: 12px;
            font-weight: 700; width: 100%;
            transition: all 0.3s ease;
            font-size: 1rem;
            box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.3);
        }
        .btn-primary-custom:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 15px 30px -5px rgba(37, 99, 235, 0.4);
        }
    </style>
</head>
<body>

    <div class="ambient-glow"></div>
    
    <a href="Index.php" class="back-link">
        <i class="fas fa-arrow-left"></i> KEMBALI
    </a>

    <div class="login-card">
        <div class="text-center mb-4">
            <div class="mb-3">
                <img src="uploads/logo_hima.png" alt="Logo Himpunan" class="img-fluid" style="height: 80px; object-fit: contain;">
            </div>
            
            <h3 class="fw-bold mb-1">Selamat Datang</h3>
            <p class="text-muted small">Silakan login untuk mengakses layanan.</p>
        </div>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger py-2 small border-0 bg-danger-subtle text-danger rounded-3 text-center mb-4">
                <i class="fas fa-exclamation-circle me-1"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">USERNAME</label>
                <input type="text" name="username" class="form-control-custom" placeholder="Masukan username..." required autocomplete="off">
            </div>
            
            <div class="mb-4">
                <div class="d-flex justify-content-between">
                    <label class="form-label">PASSWORD</label>
                </div>
                <input type="password" name="password" class="form-control-custom" placeholder="••••••••" required>
            </div>

            <button type="submit" name="login" class="btn-primary-custom">
                MASUK SEKARANG <i class="fas fa-arrow-right ms-2"></i>
            </button>
        </form>

        <div class="text-center mt-4 pt-3 border-top border-light">
            <p class="small text-muted mb-0">
                Belum punya akun? <a href="register.php" class="text-primary fw-bold text-decoration-none">Daftar disini</a>
            </p>
        </div>
    </div>

</body>
</html>