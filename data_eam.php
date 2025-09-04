<?php
// ==================== Koneksi Database ====================
$host = "localhost";
$user = "root";
$pass = "";
$db   = "pln_dashboard";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// ==================== Ambil Filter ====================
$tahun_filter = isset($_GET['tahun']) ? intval($_GET['tahun']) : date("Y");
$unit_filter  = $_GET['unit']  ?? '';
$bulan_filter = $_GET['bulan'] ?? '';

// ==================== Ambil Tahun Unik ====================
$sqlTahun = "SELECT DISTINCT tahun FROM data_eam ORDER BY tahun DESC";
$resultTahun = $conn->query($sqlTahun);
$tahunDB = [];
while($row = $resultTahun->fetch_assoc()) {
    $tahunDB[] = (int)$row['tahun'];
}

// Tambahkan range tahun hingga +5
$tahun_terakhir = max($tahunDB ?: [date("Y")]); 
for ($i = $tahun_terakhir + 1; $i <= date("Y") + 5; $i++) {
    $tahunDB[] = $i;
}
$tahunDB = array_unique($tahunDB);
rsort($tahunDB);

// ==================== Ambil Unit Unik ====================
$sqlUnit = "SELECT DISTINCT unit FROM data_eam ORDER BY unit ASC";
$resultUnit = $conn->query($sqlUnit);
$unitDB = [];
while($row = $resultUnit->fetch_assoc()) {
    $unitDB[] = $row['unit'];
}

// ==================== Ambil Bulan Unik ====================
$bulanList = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI',
              'JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];

// ==================== Query Data sesuai Filter ====================
$where = ["tahun = $tahun_filter"];
if ($unit_filter) $where[] = "unit = '".$conn->real_escape_string($unit_filter)."'";
if ($bulan_filter) $where[] = "bulan = '".$conn->real_escape_string($bulan_filter)."'";
$whereSQL = implode(" AND ", $where);

$sqlData = "
    SELECT id, unit, bulan, tahun, target, realisasi, pencapaian 
    FROM data_eam 
    WHERE $whereSQL 
    ORDER BY FIELD(bulan,'" . implode("','",$bulanList) . "')";
$resultData = $conn->query($sqlData);

// ==================== Hitung Ringkasan ====================
$sqlSummary = "SELECT 
        SUM(target) as total_target,
        SUM(realisasi) as total_realisasi,
        AVG(pencapaian) as rata_pencapaian
    FROM data_eam 
    WHERE $whereSQL";
$summary = $conn->query($sqlSummary)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data EAM - <?= $tahun_filter; ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fa; margin:0; padding:0; }
        h2 { color: #333; text-align:center; margin:20px 0; }
        .filters { text-align:center; margin: 15px; }
        select, button { padding: 6px 12px; font-size: 14px; margin:0 5px; }
        .btn { padding: 5px 8px; text-decoration: none; border-radius: 4px; font-size: 13px; }
        .btn-edit { background: #28a745; color: white; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-add { background: #007bff; color: white; margin-left: 20px; }
        table {
            border-collapse: collapse;
            width: 95%;
            margin: 20px auto;
            background: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px 12px;
            text-align: center;
        }
        th { background: #007bff; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .cards { display:flex; justify-content:center; gap:20px; margin:20px; }
        .card {
            background:#fff; padding:15px; border-radius:8px;
            box-shadow:0 2px 6px rgba(0,0,0,0.1);
            width:180px; text-align:center;
        }
        .card h3 { margin:0; font-size:16px; color:#555; }
        .card p { font-size:20px; margin-top:8px; font-weight:bold; }
        canvas { background:#fff; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1); padding:10px; }
    </style>
</head>
<body>

<h2>Data EAM Tahun <?= $tahun_filter; ?></h2>

<!-- ==================== FILTER ==================== -->
<div class="filters">
    <form method="GET" action="">
        <label for="tahun">Tahun:</label>
        <select name="tahun" id="tahun" onchange="this.form.submit()">
            <?php foreach($tahunDB as $th): ?>
                <option value="<?= $th; ?>" <?= ($tahun_filter == $th) ? 'selected' : ''; ?>><?= $th; ?></option>
            <?php endforeach; ?>
        </select>

        <label for="unit">Unit:</label>
        <select name="unit" id="unit" onchange="this.form.submit()">
            <option value="">-- Semua --</option>
            <?php foreach($unitDB as $u): ?>
                <option value="<?= $u; ?>" <?= ($unit_filter == $u) ? 'selected' : ''; ?>><?= $u; ?></option>
            <?php endforeach; ?>
        </select>

        <label for="bulan">Bulan:</label>
        <select name="bulan" id="bulan" onchange="this.form.submit()">
            <option value="">-- Semua --</option>
            <?php foreach($bulanList as $b): ?>
                <option value="<?= $b; ?>" <?= ($bulan_filter == $b) ? 'selected' : ''; ?>><?= $b; ?></option>
            <?php endforeach; ?>
        </select>

        <a href="tambah_data.php?tahun=<?= $tahun_filter; ?>" class="btn btn-add">+ Tambah Data</a>
    </form>
</div>

<!-- ==================== Cards Ringkasan ==================== -->
<div class="cards">
    <div class="card">
        <h3>Total Target</h3>
        <p><?= number_format($summary['total_target'] ?? 0, 2, ',', '.'); ?></p>
    </div>
    <div class="card">
        <h3>Total Realisasi</h3>
        <p><?= number_format($summary['total_realisasi'] ?? 0, 2, ',', '.'); ?></p>
    </div>
    <div class="card">
        <h3>Rata Pencapaian</h3>
        <p><?= number_format($summary['rata_pencapaian'] ?? 0, 2, ',', '.'); ?>%</p>
    </div>
</div>

<!-- ==================== Grafik ==================== -->
<div style="width:90%; margin:20px auto;">
    <canvas id="chartEAM"></canvas>
</div>
<script>
const ctx = document.getElementById('chartEAM').getContext('2d');
const chart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: [<?php 
            $labels=[]; $target=[]; $realisasi=[];
            $resultData->data_seek(0);
            while($row=$resultData->fetch_assoc()){
                $labels[] = $row['bulan'];
                $target[] = $row['target'];
                $realisasi[] = $row['realisasi'];
            }
            echo "'".implode("','",$labels)."'";
        ?>],
        datasets: [
            {
                label: 'Target',
                data: [<?= implode(",", $target); ?>],
                backgroundColor: 'rgba(54, 162, 235, 0.7)'
            },
            {
                label: 'Realisasi',
                data: [<?= implode(",", $realisasi); ?>],
                backgroundColor: 'rgba(75, 192, 192, 0.7)'
            }
        ]
    },
    options: { responsive:true, scales: { y: { beginAtZero:true } } }
});
</script>

<!-- ==================== Tabel Data ==================== -->
<table>
    <thead>
        <tr>
            <th>UNIT</th>
            <th>BULAN</th>
            <th>TARGET (<?= $tahun_filter; ?>)</th>
            <th>REALISASI (<?= $tahun_filter; ?>)</th>
            <th>% PENCAPAIAN</th>
            <th>AKSI</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $resultData->data_seek(0); // reset pointer
        if ($resultData->num_rows > 0): 
            while($row = $resultData->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['unit']); ?></td>
                    <td><?= htmlspecialchars($row['bulan']); ?></td>
                    <td><?= number_format($row['target'], 2, ',', '.'); ?></td>
                    <td><?= number_format($row['realisasi'], 2, ',', '.'); ?></td>
                    <td><?= number_format($row['pencapaian'], 2, ',', '.'); ?>%</td>
                    <td>
                        <a href="edit_data.php?id=<?= $row['id']; ?>" class="btn btn-edit">Edit</a>
                        <a href="hapus_data.php?id=<?= $row['id']; ?>" 
                           class="btn btn-delete" 
                           onclick="return confirm('Yakin ingin menghapus data ini?');">Hapus</a>
                    </td>
                </tr>
            <?php endwhile; 
        else: ?>
            <tr><td colspan="6">Data belum tersedia</td></tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
