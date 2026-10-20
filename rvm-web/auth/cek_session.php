<?php
// File ini di-include() di paling atas setiap halaman admin/ dan user/
// Fungsinya: mencegah orang yang belum login atau salah role mengakses halaman

session_start();

function cek_login_role($role_diizinkan) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        // Belum login sama sekali
        header('Location: /rvm-web/auth/login.php');
        exit;
    }

    if ($_SESSION['role'] !== $role_diizinkan) {
        // Login tapi role-nya salah (misal user coba akses halaman admin)
        header('Location: /rvm-web/auth/login.php');
        exit;
    }
}
