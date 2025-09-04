<?php
session_start();

// Cek login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Ambil data user dari session
$user = $_SESSION['user'];

// Hanya admin / administrator yang boleh akses
$adminRoles = ['admin', 'administrator'];
if (!in_array(strtolower($user['role']), $adminRoles)) {
    header("Location: dashboard.php"); // kalau bukan admin diarahkan ke dashboard biasa
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            margin: 0;
            padding: 0;
        }
        header {
            background: #2d89ef;
            color: #fff;
            padding: 15px;
            text-align: center;
        }
        .container {
            padding: 20px;
        }
        .card {
            background: #fff;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        a {
            display: inline-block;
            margin-top: 10px;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 6px;
            background: #2d89ef;
            color: #fff;
        }
        a:hover {
            background: #1e5bb8;
        }
    </style>
</head>
<body>
    <header>
        <h1>Admin Dashboard</h1>
        <p>Selamat datang, <?= htmlspecialchars($user['username']); ?> (<?= htmlspecialchars($user['role']); ?>)</p>
    </header>

    <div class="container">
        <div class="card">
            <h2>Kelola User</h2>
            <p>Tambah, edit, atau hapus akun user di sistem.</p>
            <a href="manage_users.php">Kelola User</a>
        </div>

        <div class="card">
            <h2>Kelola Data</h2>
            <p>Akses penuh untuk mengelola data yang ada.</p>
            <a href="manage_data.php">Kelola Data</a>
        </div>

        <div class="card">
            <h2>Laporan</h2>
            <p>Lihat semua laporan yang tersedia.</p>
            <a href="laporan.php">Lihat Laporan</a>
        </div>

        <div class="card">
            <h2>Lihat Catatan</h2>
            <p>Lihat dan kelola catatan penting di sistem.</p>
            <a href="catatan.php">Lihat Catatan</a>
        </div>

        <div class="card">
            <h2>Logout</h2>
            <a href="logout.php">Keluar</a>
        </div>
    </div>
</body>
</html>
