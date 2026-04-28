<?php
$host     = 'localhost';
$dbname   = 'bengkel_smk';
$username = 'root';
$password = '';  // XAMPP default: kosong

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}
?>