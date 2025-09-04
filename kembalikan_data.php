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

$status_message = '';
$status_class = '';

$log_id = $_GET['log_id'] ?? null;

if ($log_id) {
    // 1. Ambil data dari tabel log berdasarkan log_id
    $sql_select = "SELECT data_content, data_id FROM saifi_delete_log WHERE log_id = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $log_id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();

    if ($result->num_rows > 0) {
        $log_row = $result->fetch_assoc();
        $data_id = $log_row['data_id'];
        $data_content = json_decode($log_row['data_content'], true);
        
        // Periksa apakah data asli sudah ada
        $sql_check = "SELECT id FROM saifi_data WHERE id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("i", $data_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        $sql_restore = "";
        $status_message = "";
        
        if ($result_check->num_rows > 0) {
            // Data asli sudah ada, lakukan update status is_deleted menjadi 0
            $sql_restore = "UPDATE saifi_data SET is_deleted = 0 WHERE id = ?";
            $status_message = "Data dengan ID asli {$data_id} berhasil dipulihkan.";
            $stmt_restore = $conn->prepare($sql_restore);
            $stmt_restore->bind_param("i", $data_id);
        } else {
            // Data asli tidak ada, masukkan kembali sebagai entri baru
            // Buat placeholder untuk setiap kolom
            $columns = array_keys($data_content);
            $placeholders = str_repeat('?,', count($columns) - 1) . '?';
            
            // Buat string tipe data parameter
            $bind_types = "";
            $bind_params = array();
            foreach ($data_content as $value) {
                if (is_int($value)) {
                    $bind_types .= "i";
                } elseif (is_float($value)) {
                    $bind_types .= "d";
                } else {
                    $bind_types .= "s";
                }
                $bind_params[] = &$value;
            }

            $sql_restore = "INSERT INTO saifi_data (" . implode(',', $columns) . ") VALUES ({$placeholders})";
            $status_message = "Data berhasil ditambahkan kembali sebagai entri baru.";

            $stmt_restore = $conn->prepare($sql_restore);
            array_unshift($bind_params, $bind_types);
            call_user_func_array(array($stmt_restore, 'bind_param'), $bind_params);
        }
        
        $stmt_check->close();
        
        if ($stmt_restore->execute()) {
            $status_class = "alert-success";
            
            // Hapus entri dari log setelah berhasil dipulihkan
            $sql_delete_log = "DELETE FROM saifi_delete_log WHERE log_id = ?";
            $stmt_delete_log = $conn->prepare($sql_delete_log);
            $stmt_delete_log->bind_param("i", $log_id);
            $stmt_delete_log->execute();
            $stmt_delete_log->close();

        } else {
            $status_message = "Error saat mengembalikan data: " . $stmt_restore->error;
            $status_class = "alert-danger";
        }
        $stmt_restore->close();

    } else {
        $status_message = "Error: Data log tidak ditemukan.";
        $status_class = "alert-warning";
    }
    $stmt_select->close();

} else {
    $status_message = "Error: ID log tidak valid.";
    $status_class = "alert-warning";
}

$conn->close();

// Redirect kembali ke halaman riwayat setelah 2 detik
header("refresh:2;url=riwayat_penghapusan.php");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kembalikan Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5 text-center">
    <div class="alert <?= $status_class ?>" role="alert">
        <?= $status_message ?>
    </div>
    <p>Anda akan dialihkan kembali ke halaman riwayat...</p>
    <a href="riwayat_hapus.php" class="btn btn-secondary">Kembali Sekarang</a>
</div>

</body>
</html>
