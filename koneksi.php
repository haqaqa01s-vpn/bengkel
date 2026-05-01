<?php
session_start();

$host     = 'localhost';
$dbname   = 'bengkel_tsm';
$username = 'root';
$password = '';  // XAMPP default: kosong

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

// Fungsi cek login
function cekLogin() {
    if (!isset($_SESSION['user'])) {
        header('Location: /bengkel.tsm/login.php');
        exit;
    }
}

// Fungsi cek role
function cekRole($role) {
    if ($_SESSION['user']['role'] !== $role) {
        header('Location: /bengkel.tsm/index.php');
        exit;
    }
}

// Fungsi query praktis
function query($sql) {
    global $pdo;
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function cari($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>