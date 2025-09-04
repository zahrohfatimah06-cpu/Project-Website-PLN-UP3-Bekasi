<?php
// Koneksi ke database
$host = "localhost"; // sesuaikan
$user = "root";      // sesuaikan
$pass = "";          // sesuaikan
$db   = "namadatabase"; // ganti dengan nama DB kamu

$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil data dari form
$catatan1 = isset($_POST['catatan1']) ? $_POST['catatan1'] : null;
$catatan2 = isset($_POST['catatan2']) ? $_POST['catatan2'] : null;

// Cek apakah sudah ada data sebelumnya
$sql_check = "SELECT id FROM notes_admin LIMIT 1";
$result = $conn->query($sql_check);

if ($result->num_rows > 0) {
    // Update data yang sudah ada
    $row = $result->fetch_assoc();
    $id  = $row['id'];

    if ($catatan1 !== null) {
        $stmt = $conn->prepare("UPDATE notes_admin SET catatan1=? WHERE id=?");
        $stmt->bind_param("si", $catatan1, $id);
        $stmt->execute();
    }

    if ($catatan2 !== null) {
        $stmt = $conn->prepare("UPDATE notes_admin SET catatan2=? WHERE id=?");
        $stmt->bind_param("si", $catatan2, $id);
        $stmt->execute();
    }
} else {
    // Insert baru jika belum ada
    $stmt = $conn->prepare("INSERT INTO notes_admin (catatan1, catatan2) VALUES (?, ?)");
    $stmt->bind_param("ss", $catatan1, $catatan2);
    $stmt->execute();
}

// Tutup koneksi
$conn->close();

// Redirect balik ke dashboard
header("Location: dashboard.php"); // ganti sesuai halaman utama kamu
exit;
?>
