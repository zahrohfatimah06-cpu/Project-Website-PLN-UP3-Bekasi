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

// --- Ambil data dari formulir POST untuk pembaruan ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $tahun = $_POST['tahun_data']; 
    $unit = $_POST['unit'];
    $bulan = $_POST['bulan'];
    
    $kolom_realisasi_ini = 'realisasi_' . $tahun;
    $kolom_target_ini    = 'target_' . $tahun;

    $realisasi = $_POST[$kolom_realisasi_ini] ?? 0;
    $target    = $_POST[$kolom_target_ini] ?? 0;
    
    // Hitung pencapaian
    $pencapaian = ($target != 0) ? ($realisasi / $target) * 100 : 0;
    
    // Cek apakah kolom ada
    $kolom_ada = false;
    $result_check = $conn->query("SHOW COLUMNS FROM saifi_data LIKE '{$kolom_realisasi_ini}'");
    if ($result_check && $result_check->num_rows > 0) {
        $kolom_ada = true;
    }

    if ($kolom_ada) {
        $sql = "UPDATE saifi_data 
                SET unit = ?, bulan = ?, {$kolom_realisasi_ini} = ?, {$kolom_target_ini} = ?, pencapaian = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdddi", $unit, $bulan, $realisasi, $target, $pencapaian, $id);

        if ($stmt->execute()) {
            $status_message = "Data berhasil diperbarui!";
            $status_class = "alert-success";
            header("refresh:2;url=index.php?tahun={$tahun}");
        } else {
            $status_message = "Error: " . $stmt->error;
            $status_class = "alert-danger";
        }
        $stmt->close();
    } else {
        $status_message = "Gagal memperbarui: Kolom untuk tahun '{$tahun}' tidak ditemukan.";
        $status_class = "alert-danger";
    }

} else {
    // --- Ambil ID dan Tahun dari URL ---
    $id = $_GET['id'] ?? null;
    $tahun_data = $_GET['tahun'] ?? '2026';

    if (!$id) die("ID tidak ditemukan.");
    
    $kolom_realisasi_sebelum = 'realisasi_' . ($tahun_data - 1);
    $kolom_target_ini        = 'target_' . $tahun_data;
    $kolom_realisasi_ini     = 'realisasi_' . $tahun_data;

    $sql_select = "SELECT * FROM saifi_data WHERE id = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();

    if ($result->num_rows > 0) {
        $data_to_edit = $result->fetch_assoc();
    } else {
        die("Data tidak ditemukan.");
    }

    $stmt_select->close();

    $units_result  = $conn->query("SELECT DISTINCT unit FROM saifi_data ORDER BY unit");
    $months_result = $conn->query("SELECT DISTINCT bulan FROM saifi_data 
                                   ORDER BY FIELD(bulan,'JANUARI','FEBRUARI','MARET','APRIL','MEI',
                                                        'JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER',
                                                        'NOVEMBER','DESEMBER')");
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data SAIFI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">

    <h2 class="mb-4 text-primary">Edit Data</h2>

    <!-- Pesan status -->
    <?php if ($status_message): ?>
        <div class="alert <?= $status_class ?> alert-dismissible fade show" role="alert">
            <?= $status_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form id="editForm" method="post" action="edit_data.php">
      <div class="mb-3">
         <label for="id" class="form-label">ID Data</label>
           <input type="number" id="id" name="id" 
             class="form-control" 
              value="<?= htmlspecialchars($data_to_edit['id']) ?>" required>
    </div>
                <input type="hidden" id="id" name="id" value="<?= htmlspecialchars($data_to_edit['id']) ?>">
                <input type="hidden" id="tahun_data" name="tahun_data" value="<?= htmlspecialchars($tahun_data) ?>">

                <div class="mb-3">
                    <label for="unit" class="form-label">Unit</label>
                    <select name="unit" id="unit" class="form-select" required>
                        <?php while($u = $units_result->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($u['unit']) ?>" 
                                <?= $u['unit'] == $data_to_edit['unit'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['unit']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="bulan" class="form-label">Bulan</label>
                    <select name="bulan" id="bulan" class="form-select" required>
                        <?php while($m = $months_result->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($m['bulan']) ?>" 
                                <?= $m['bulan'] == $data_to_edit['bulan'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['bulan']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="<?= $kolom_realisasi_ini ?>" class="form-label">Realisasi <?= $tahun_data ?></label>
                    <input type="number" step="0.01" 
                           name="<?= $kolom_realisasi_ini ?>" 
                           id="<?= $kolom_realisasi_ini ?>" 
                           class="form-control" 
                           value="<?= htmlspecialchars($data_to_edit[$kolom_realisasi_ini] ?? 0) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="<?= $kolom_target_ini ?>" class="form-label">Target <?= $tahun_data ?></label>
                    <input type="number" step="0.01" 
                           name="<?= $kolom_target_ini ?>" 
                           id="<?= $kolom_target_ini ?>" 
                           class="form-control" 
                           value="<?= htmlspecialchars($data_to_edit[$kolom_target_ini] ?? 0) ?>" required>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="index.php?tahun=<?= htmlspecialchars($tahun_data) ?>" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>

            </form>
        </div>
     </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
