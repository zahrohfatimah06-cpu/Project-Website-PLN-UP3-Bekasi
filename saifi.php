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

// --- Ambil Filter ---
$filter_unit  = $_GET['unit']  ?? '';
$filter_bulan = $_GET['bulan'] ?? '';
$filter_tahun = $_GET['tahun'] ?? '2026'; 

// Tentukan nama kolom realisasi dan target secara dinamis berdasarkan tahun
$tahun_sebelumnya = $filter_tahun - 1;
$kolom_realisasi_sebelum = 'realisasi_' . $tahun_sebelumnya;
$kolom_target_ini = 'target_' . $filter_tahun;
$kolom_realisasi_ini = 'realisasi_' . $filter_tahun;

// --- Ambil List Dropdown ---
$units_result = $conn->query("SELECT DISTINCT unit FROM saifi_data ORDER BY unit");
$months_in_order = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
$months_result = $conn->query("SELECT DISTINCT bulan FROM saifi_data ORDER BY FIELD(bulan,'".implode("','", $months_in_order)."')");

// --- Query Data untuk Grafik dan Ringkasan (diurutkan berdasarkan bulan) ---
$sql_chart = "SELECT * FROM saifi_data WHERE is_deleted = 0";
if ($filter_unit) {
    $sql_chart .= " AND unit='$filter_unit'";
}
if ($filter_bulan) {
    $bulan_index = array_search($filter_bulan, $months_in_order);
    if ($bulan_index !== false) {
        $selected_months = array_slice($months_in_order, 0, $bulan_index + 1);
        $month_list = "'" . implode("','", $selected_months) . "'";
        $sql_chart .= " AND bulan IN ($month_list)";
    }
}
$sql_chart .= " ORDER BY FIELD(bulan,'".implode("','", $months_in_order)."')";

$result_chart = $conn->query($sql_chart);

// --- Hitung Ringkasan dan Siapkan Data untuk Grafik ---
$total_rows = 0;
$sum_pencapaian = 0; 
$sum_saifi = 0;
$data_rows_chart = [];
$labels_chart = [];
$data_pencapaian_chart = [];
$data_saifi_chart = [];

if ($result_chart && $result_chart->num_rows > 0) {
    while ($r = $result_chart->fetch_assoc()) {
        $data_rows_chart[] = $r;
        
        // Siapkan data untuk chart
        $labels_chart[] = $r['bulan'];
        $pencapaian_row = 0;
        if (isset($r[$kolom_realisasi_ini]) && isset($r[$kolom_target_ini]) && $r[$kolom_target_ini] != 0) {
            $pencapaian_row = ($r[$kolom_realisasi_ini] / $r[$kolom_target_ini]) * 100;
        }
        $data_pencapaian_chart[] = $pencapaian_row;
        $data_saifi_chart[] = isset($r['saifi']) ? $r['saifi'] : 0;
    }
    
    $total_rows = count($data_rows_chart);
    foreach ($data_rows_chart as $r) {
        if (isset($r[$kolom_realisasi_ini]) && isset($r[$kolom_target_ini]) && $r[$kolom_target_ini] != 0) {
            $pencapaian_row = ($r[$kolom_realisasi_ini] / $r[$kolom_target_ini]) * 100;
            $sum_pencapaian += $pencapaian_row;
        }
        if (isset($r['saifi'])) {
            $sum_saifi += $r['saifi'];
        }
    }

    $avg_pencapaian = ($total_rows > 0) ? $sum_pencapaian / $total_rows : 0;
    $avg_saifi = ($total_rows > 0) ? $sum_saifi / $total_rows : 0;
} else {
    $avg_pencapaian = 0;
    $avg_saifi = 0;
    $data_rows_chart = [];
}

// --- Query Data untuk Tabel (diurutkan berdasarkan ID) ---
$sql_table = "SELECT * FROM saifi_data WHERE is_deleted = 0";
if ($filter_unit) {
    $sql_table .= " AND unit='$filter_unit'";
}
if ($filter_bulan) {
    $bulan_index = array_search($filter_bulan, $months_in_order);
    if ($bulan_index !== false) {
        $selected_months = array_slice($months_in_order, 0, $bulan_index + 1);
        $month_list = "'" . implode("','", $selected_months) . "'";
        $sql_table .= " AND bulan IN ($month_list)";
    }
}
$sql_table .= " ORDER BY id ASC";

$result_table = $conn->query($sql_table);
$data_rows_table = [];
if ($result_table && $result_table->num_rows > 0) {
    while ($r = $result_table->fetch_assoc()) {
        $data_rows_table[] = $r;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Halaman Data SAIFI PLN</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary">Halaman Data SAIFI Tahun <?= $filter_tahun ?></h2>
        <div class="d-flex gap-2">
            <a href="tambah_data.php" class="btn btn-success"><i class="fa-solid fa-plus me-2"></i>Tambah Data</a>
            <a href="riwayat_penghapusan.php" class="btn btn-info"><i class="fa-solid fa-history me-2"></i>Riwayat Penghapusan</a>
        </div>
    </div>

    <!-- Filter -->
    <form method="get" class="row g-3 mb-4">
        <div class="col-md-3">
            <label class="form-label">Unit</label>
            <select name="unit" class="form-select">
                <option value="">-- Semua Unit --</option>
                <?php if ($units_result): mysqli_data_seek($units_result, 0); while($u=$units_result->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($u['unit']) ?>" <?= $u['unit']==$filter_unit?'selected':'' ?>>
                        <?= htmlspecialchars($u['unit']) ?>
                    </option>
                <?php endwhile; endif; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Bulan</label>
            <select name="bulan" class="form-select">
                <option value="">-- Semua Bulan --</option>
                <?php if ($months_result): mysqli_data_seek($months_result, 0); while($m=$months_result->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($m['bulan']) ?>" <?= $m['bulan']==$filter_bulan?'selected':'' ?>>
                        <?= htmlspecialchars($m['bulan']) ?>
                    </option>
                <?php endwhile; endif; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tahun</label>
            <select name="tahun" class="form-select">
                <?php for($t=2024; $t<=2030; $t++): ?>
                    <option value="<?= $t ?>" <?= $t==$filter_tahun?'selected':'' ?>>
                        <?= $t ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-1 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Terapkan</button>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <a href="?" class="btn btn-secondary w-100">Reset</a>
        </div>
    </form>

    <!-- Ringkasan -->
    <div class="row mb-4 text-center">
        <div class="col-md-3"><div class="p-3 bg-white rounded shadow-sm">
            <h6>Unit Dipilih</h6><h5><?= htmlspecialchars($filter_unit) ?: 'Semua' ?></h5>
        </div></div>
        <div class="col-md-3"><div class="p-3 bg-white rounded shadow-sm">
            <h6>Bulan Dipilih</h6><h5><?= htmlspecialchars($filter_bulan) ?: 'Semua' ?></h5>
        </div></div>
        <div class="col-md-3"><div class="p-3 bg-white rounded shadow-sm">
            <h6>Rata-rata Pencapaian</h6><h5><?= number_format($avg_pencapaian,2) ?>%</h5>
        </div></div>
        <div class="col-md-3"><div class="p-3 bg-white rounded shadow-sm">
            <h6>Rata-rata SAIFI</h6><h5><?= number_format($avg_saifi,2) ?></h5>
        </div></div>
    </div>

    <!-- Grafik -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-success text-white">Grafik Pencapaian dan SAIFI</div>
        <div class="card-body"><canvas id="saifiChart"></canvas></div>
    </div>

    <!-- Grafik Bar -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-warning text-dark">Grafik Perbandingan Realisasi & Target <?= $filter_tahun ?></div>
        <div class="card-body"><canvas id="barChart"></canvas></div>
    </div>

    <!-- Tabel -->
    <div class="card">
        <div class="card-header bg-primary text-white">Data SAIFI (<?= count($data_rows_table) ?> baris)</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Unit</th>
                        <th>Bulan</th>
                        <th>Realisasi <?= $tahun_sebelumnya ?></th>
                        <th>Target <?= $filter_tahun ?></th>
                        <th>Realisasi <?= $filter_tahun ?></th>
                        <th>% Pencapaian</th>
                        <th>SAIFI</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($data_rows_table)): ?>
                    <?php foreach ($data_rows_table as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <td><?= htmlspecialchars($row['unit']) ?></td>
                            <td><?= htmlspecialchars($row['bulan']) ?></td>
                            <td><?= isset($row[$kolom_realisasi_sebelum]) ? number_format($row[$kolom_realisasi_sebelum], 2) : 'N/A' ?></td>
                            <td><?= isset($row[$kolom_target_ini]) ? number_format($row[$kolom_target_ini], 2) : 'N/A' ?></td>
                            <td><?= isset($row[$kolom_realisasi_ini]) ? number_format($row[$kolom_realisasi_ini], 2) : 'N/A' ?></td>
                            <td>
                                <?php
                                    $pencapaian_row = 0;
                                    if (isset($row[$kolom_realisasi_ini]) && isset($row[$kolom_target_ini]) && $row[$kolom_target_ini] != 0) {
                                        $pencapaian_row = ($row[$kolom_realisasi_ini] / $row[$kolom_target_ini]) * 100;
                                    }
                                    echo number_format($pencapaian_row, 2) . '%';
                                ?>
                            </td>
                            <td><?= isset($row['saifi']) ? number_format($row['saifi'], 2) : 'N/A' ?></td>
                            <td>
                               <a href="edit.php?tabel=data_saifi&id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="hapus_data.php?id=<?= $row['id'] ?>&tahun=<?= htmlspecialchars($filter_tahun) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center">Tidak ada data</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Data Line Chart
    const labels = <?= json_encode($labels_chart) ?>;
    const dataPencapaian = <?= json_encode($data_pencapaian_chart) ?>;
    const dataSaifi = <?= json_encode($data_saifi_chart) ?>;

    const ctx = document.getElementById('saifiChart').getContext('2d');
    const saifiChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {label:'Pencapaian (%)',data:dataPencapaian,borderColor:'rgb(75,192,192)',backgroundColor:'rgba(75,192,192,0.2)',tension:0.2,fill:false},
                {label:'SAIFI',data:dataSaifi,borderColor:'rgb(255,99,132)',backgroundColor:'rgba(255,99,132,0.2)',tension:0.2,fill:false}
            ]
        },
        options: {responsive:true,plugins:{title:{display:true,text:'Grafik Tren Pencapaian dan SAIFI'}},scales:{y:{beginAtZero:true}}}
    });

    // Data Bar Chart
    const dataRealisasiSebelum = <?= json_encode(array_map(fn($r)=>isset($r[$kolom_realisasi_sebelum])?(float)$r[$kolom_realisasi_sebelum]:0,$data_rows_chart)) ?>;
    const dataTargetIni = <?= json_encode(array_map(fn($r)=>isset($r[$kolom_target_ini])?(float)$r[$kolom_target_ini]:0,$data_rows_chart)) ?>;
    const dataRealisasiIni = <?= json_encode(array_map(fn($r)=>isset($r[$kolom_realisasi_ini])?(float)$r[$kolom_realisasi_ini]:0,$data_rows_chart)) ?>;

    const ctxBar = document.getElementById('barChart').getContext('2d');
    const barChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {label:'Realisasi <?= $tahun_sebelumnya ?>',data:dataRealisasiSebelum,backgroundColor:'rgba(54,162,235,0.7)'},
                {label:'Target <?= $filter_tahun ?>',data:dataTargetIni,backgroundColor:'rgba(255,206,86,0.7)'},
                {label:'Realisasi <?= $filter_tahun ?>',data:dataRealisasiIni,backgroundColor:'rgba(75,192,192,0.7)'}
            ]
        },
        options: {
            responsive:true,
            plugins:{title:{display:true,text:'Perbandingan Realisasi & Target per Bulan'}},
            scales:{x:{stacked:true},y:{beginAtZero:true,stacked:true}}
        }
    });
});
</script>

</body>
</html>
