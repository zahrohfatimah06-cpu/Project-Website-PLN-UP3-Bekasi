<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Ambil ID dari URL dan pastikan integer
$id = $_GET['id'] ?? '';
$id = intval($id);
if ($id <= 0) {
    die("ID tidak valid");
}

// Koneksi ke database
$conn = new mysqli("localhost", "root", "", "pln_dashboard");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Hapus data KPI berdasarkan ID
$stmt = $conn->prepare("DELETE FROM kpi_data WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    // Redirect kembali ke halaman history KPI & PI
    header("Location: history_update_kpi_pi.php?msg=deleted");
    exit();
} else {
    $stmt->close();
    $conn->close();
    die("Gagal menghapus data.");
}
?>
