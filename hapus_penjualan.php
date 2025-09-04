<?php
session_start();
$conn = new mysqli("localhost", "root", "", "pln_dashboard");
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    // ambil data lama
    $data = $conn->query("SELECT * FROM penjualan_tenaga_listrik WHERE id=$id")->fetch_assoc();
    if ($data) {
        // simpan ke riwayat
        $stmt = $conn->prepare("INSERT INTO riwayat_penjualan 
            (id_asli, unit, bulan, realisasi_2024, target_2025, realisasi_2025, persen_pencapaian) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdddd", 
            $data['id'], $data['unit'], $data['bulan'],
            $data['realisasi_2024'], $data['target_2025'],
            $data['realisasi_2025'], $data['persen_pencapaian']
        );
        $stmt->execute();

        // hapus dari tabel utama
        $conn->query("DELETE FROM penjualan_tenaga_listrik WHERE id=$id");

        header("Location: penjualan_tenaga_listrik.php?deleted=1");
        exit();
    }
}
echo "Data tidak ditemukan!";
