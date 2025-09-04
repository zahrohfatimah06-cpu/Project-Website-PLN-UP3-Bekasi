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

// Ambil semua data dari tabel log penghapusan
$sql = "SELECT * FROM saifi_delete_log ORDER BY deleted_at DESC";
$result = $conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Penghapusan Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-info">Riwayat Penghapusan Data</h2>
        <a href="index.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left me-2"></i>Kembali ke Dashboard</a>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID Log</th>
                        <th>ID Data Asli</th>
                        <th>Waktu Dihapus</th>
                        <th>Isi Data (JSON)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['log_id']) ?></td>
                        <td><?= htmlspecialchars($row['data_id']) ?></td>
                        <td><?= htmlspecialchars($row['deleted_at']) ?></td>
                        <td>
                            <pre class="bg-dark text-white p-2 rounded"><?= htmlspecialchars($row['data_content']) ?></pre>
                        </td>
                        <td>
                            <a href="kembalikan_data.php?log_id=<?= htmlspecialchars($row['log_id']) ?>" class="btn btn-sm btn-success">
                                <i class="fa-solid fa-undo me-1"></i>Kembalikan
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info" role="alert">
            Tidak ada riwayat penghapusan yang ditemukan.
        </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
