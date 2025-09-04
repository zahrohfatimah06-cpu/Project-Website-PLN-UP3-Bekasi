<?php
// --- Koneksi ke database ---
$host = "localhost"; 
$user = "root"; 
$pass = ""; 
$db   = "pln_dashboard"; // ganti dengan nama database kamu

$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
