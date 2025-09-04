<?php
// create_admin.php
$host = "localhost";
$user = "root";   // sesuaikan
$pass = "";       // sesuaikan
$db   = "pln_dashboard";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Data admin default
$username = "admin";
$password_plain = "admin123";
$role = "admin";
$unit = "PUSAT"; // bisa diganti sesuai kebutuhan

// Hash password
$password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

// Cek apakah user admin sudah ada
$stmt = $conn->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    echo "⚠️ User 'admin' sudah ada di database.";
} else {
    $stmt = $conn->prepare("INSERT INTO users (username, password, role, unit) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $password_hashed, $role, $unit);

    if ($stmt->execute()) {
        echo "✅ User admin berhasil dibuat.<br>";
        echo "➡️ Username: <b>$username</b><br>";
        echo "➡️ Password: <b>$password_plain</b><br>";
    } else {
        echo "❌ Gagal menambahkan user admin: " . $conn->error;
    }
}
$conn->close();
?>
