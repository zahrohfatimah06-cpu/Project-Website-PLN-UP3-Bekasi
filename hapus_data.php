<?php
// --- Koneksi Database ---
$host = "localhost";
$user = "root"; 
$pass = ""; 
$db   = "pln_dashboard";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// --- Variabel pesan status ---
$status_message = '';
$status_class = '';

// Ambil ID dari URL
$id = $_GET['id'] ?? null;
$tahun = $_GET['tahun'] ?? '2026';

if ($id) {
    // 1. Ambil data yang akan dihapus untuk disimpan di log
    $sql_select = "SELECT * FROM saifi_data WHERE id = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();

    if ($result->num_rows > 0) {
        $data_to_log = $result->fetch_assoc();
        $data_json = json_encode($data_to_log);
        $stmt_select->close();

        // 2. Masukkan data ke tabel log penghapusan
        $sql_log = "INSERT INTO saifi_delete_log (data_id, data_content) VALUES (?, ?)";
        $stmt_log = $conn->prepare($sql_log);
        $stmt_log->bind_param("is", $id, $data_json);

        if ($stmt_log->execute()) {
            $stmt_log->close();

            // 3. Lakukan "soft delete" pada data asli
            $sql_update = "UPDATE saifi_data SET is_deleted = 1 WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("i", $id);

            if ($stmt_update->execute()) {
                $status_message = "Data dengan ID {$id} berhasil dihapus dan dicatat dalam log.";
                $status_class = "alert-success";
            } else {
                $status_message = "Error saat memperbarui data (soft delete): " . $stmt_update->error;
                $status_class = "alert-danger";
            }
            $stmt_update->close();

        } else {
            $status_message = "Error saat mencatat log: " . $stmt_log->error;
            $status_class = "alert-danger";
        }
    } else {
        $status_message = "Error: Data tidak ditemukan.";
        $status_class = "alert-warning";
    }
    
} else {
    $status_message = "Error: ID tidak valid.";
    $status_class = "alert-warning";
}

$conn->close();

// Redirect kembali ke halaman utama setelah 2 detik
header("refresh:2;url=index.php?tahun={$tahun}");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5 text-center">
    <div class="alert <?= $status_class ?>" role="alert">
        <?= $status_message ?>
    </div>
    <p>Anda akan dialihkan kembali ke halaman utama dalam 2 detik...</p>
    <div class="d-flex justify-content-center gap-2 mt-3">
        <a href="index.php?tahun=<?= htmlspecialchars($tahun) ?>" class="btn btn-secondary">Kembali Sekarang</a>
        <a href="riwayat_hapus.php" class="btn btn-info">Riwayat Penghapusan</a>
    </div>
</div>

</body>
</html>
