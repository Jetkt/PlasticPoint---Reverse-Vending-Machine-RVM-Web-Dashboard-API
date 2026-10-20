<?php
// Konfigurasi koneksi database
$host = '127.0.0.1';
$dbname = 'rvm';
$username = 'root';
$password = ''; // default Laragon/XAMPP biasanya kosong

// API Key untuk ESP32 — ganti dengan string acak yang kamu buat sendiri
// Key ini harus sama persis dengan yang di-hardcode di kode ESP32 nanti
define('API_KEY', 'rvm-secret-2025');

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
