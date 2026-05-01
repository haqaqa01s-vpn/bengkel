<?php
require 'koneksi.php';

// Kalau sudah login, redirect ke dashboard
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = md5($_POST['password']);

    $user = cari("SELECT * FROM users WHERE username = ? AND password = ?", [$username, $password]);

    if ($user) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'nama_lengkap' => $user['nama_lengkap'],
            'role' => $user['role']
        ];
        header('Location: index.php');
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bengkel TEFA TSM</title>
    <link rel="stylesheet" href="/bengkel.tsm/assets/css/style.css">
    <style>
        body.login-page {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .login-container {
            background: #fff;
            border-radius: 16px;
            padding: 36px 32px;
            width: 400px;
            max-width: 95vw;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        .login-header {
            text-align: center;
            margin-bottom: 28px;
        }
        .login-header .logo-icon {
            width: 64px;
            height: 64px;
            background: #2563eb;
            color: #fff;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 700;
            margin: 0 auto 14px;
        }
        .login-header h2 {
            font-size: 20px;
            color: #1e3a5f;
            margin: 0;
        }
        .login-header p {
            font-size: 13px;
            color: #6b7280;
            margin: 4px 0 0;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-login:hover {
            background: #1d4ed8;
        }
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
            border: 1px solid #fecaca;
        }
        .login-footer {
            text-align: center;
            margin-top: 16px;
            font-size: 12px;
            color: #9ca3af;
        }
        .login-footer span {
            display: block;
        }
    </style>
</head>
<body class="login-page">

<div class="login-container">
    <div class="login-header">
        <div class="logo-icon">🏍️</div>
        <h2>Bengkel TEFA TSM</h2>
        <p>Teknik Sepeda Motor • Teaching Factory</p>
    </div>

    <?php if ($error): ?>
    <div class="alert-error">
        ⚠️ <?= $error ?>
    </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="Masukkan username..." required autocomplete="off">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Masukkan password..." required>
        </div>
        <button type="submit" class="btn-login">
            🔐 Masuk ke Sistem
        </button>
    </form>

    <div class="login-footer">
        <span>🔑 Admin: admin / admin123</span>
        <span>💳 Kasir: kasir1 / kasir123</span>
    </div>
</div>

</body>
</html>