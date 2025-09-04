<?php
// ==================== Koneksi Database ====================
$host = "localhost";
$user = "root";
$pass = "";
$db   = "pln_dashboard";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// ==================== Filter ====================
$tahun_filter = isset($_GET['tahun']) ? intval($_GET['tahun']) : date("Y");
$unit_filter  = isset($_GET['unit'])  ? $_GET['unit'] : '';
$bulan_filter = isset($_GET['bulan']) ? $_GET['bulan'] : '';

// ==================== Ambil Tahun Unik ====================
// ==================== Ambil Tahun Unik ====================
$sqlTahun = "SELECT DISTINCT tahun FROM data_ami ORDER BY tahun DESC";
$resTahun = $conn->query($sqlTahun);
$tahunDB = [];
while($row = $resTahun->fetch_assoc()) $tahunDB[] = $row['tahun'];

// Tambahkan tahun tambahan (2026, 2027, dst)
$nextYears = [2026, 2027, 2028, 2029, 2030];
foreach ($nextYears as $ny) {
    if (!in_array($ny, $tahunDB)) {
        $tahunDB[] = $ny;
    }
}

// Urutkan descending
rsort($tahunDB);

// ==================== Ambil Unit Unik ====================
$sqlUnit = "SELECT DISTINCT unit FROM data_ami ORDER BY unit ASC";
$resUnit = $conn->query($sqlUnit);
$unitDB = [];
while($row = $resUnit->fetch_assoc()) $unitDB[] = $row['unit'];

// ==================== Bulan ====================
$bulanList = [
    'JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI',
    'JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'
];

// ==================== Query Data ====================
$sql = "SELECT * FROM data_ami WHERE tahun = $tahun_filter";
if ($unit_filter)  $sql .= " AND unit = '".$conn->real_escape_string($unit_filter)."'";

// jika bulan dipilih → tampilkan dari Januari s/d bulan tsb
if ($bulan_filter) {
    $indexBulan = array_search($bulan_filter, $bulanList);
    if ($indexBulan !== false) {
        $bulanSampai = array_slice($bulanList, 0, $indexBulan+1);
        $sql .= " AND bulan IN ('".implode("','",$bulanSampai)."')";
    }
}

$sql .= " ORDER BY FIELD(bulan, '".implode("','",$bulanList)."')";
$result = $conn->query($sql);

// ==================== Hitung Ringkasan ====================
$total_target = $total_realisasi = 0;
$dataChart = ["bulan"=>[], "target"=>[], "realisasi"=>[], "pencapaian"=>[]];
$dataRows = [];

while($row = $result->fetch_assoc()) {
    $total_target   += $row['target'];
    $total_realisasi+= $row['realisasi'];

    $pencapaian = ($row['target'] > 0) ? ($row['realisasi']/$row['target']*100) : 0;

    $dataChart['bulan'][]      = $row['bulan'];
    $dataChart['target'][]     = $row['target'];
    $dataChart['realisasi'][]  = $row['realisasi'];
    $dataChart['pencapaian'][] = round($pencapaian,2);

    $dataRows[] = $row;
}
$rata2_pencapaian = ($total_target > 0) ? ($total_realisasi/$total_target*100) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Data AMI <?= $tahun_filter; ?></title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root {
    --bg-color: #f5f6fa;
    --text-color: #2f3640;
    --card-bg: #fff;
    --primary: #0984e3;
    --table-header: #0984e3;
    --accent: #00b894;
}
body.dark {
    --bg-color: #1e1e1e;
    --text-color: #dcdde1;
    --card-bg: #2f3640;
    --primary: #74b9ff;
    --table-header: #74b9ff;
    --accent: #55efc4;
}
body {
    font-family: "Segoe UI", Arial, sans-serif;
    margin: 0;
    padding: 0;
    background: var(--bg-color);
    color: var(--text-color);
    transition: all .3s ease-in-out;
}
.container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 20px;
}
h2 {
    margin: 0;
    font-size: 24px;
}
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
button, select, a {
    padding: 7px 12px;
    border-radius: 8px;
    border: 1px solid var(--primary);
    background: var(--primary);
    color: #fff;
    cursor: pointer;
    text-decoration: none;
    transition: 0.2s;
    font-size: 14px;
}
button:hover, select:hover, a:hover { opacity: .85; }
.theme-toggle { background: var(--accent); border-color: var(--accent); }
form select { background: var(--card-bg); color: var(--text-color); }
form { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }

/* ringkasan cards */
.summary {
    display: grid;
    grid-template-columns: repeat(auto-fit,minmax(200px,1fr));
    gap: 15px;
    margin-bottom: 20px;
}
.card {
    background: var(--card-bg);
    padding: 15px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
}
.card h3 { margin: 0; font-size: 18px; color: var(--primary); }
.card p { margin: 5px 0 0; font-size: 20px; font-weight: bold; }

/* table */
.table-box {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow-x: auto;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}
th, td {
    padding: 10px;
    text-align: center;
    border-bottom: 1px solid #ccc;
}
th { background: var(--table-header); color: #fff; }
tr:nth-child(even) { background: rgba(0,0,0,0.03); }
.actions a {
    margin: 0 5px;
    font-weight: bold;
}

/* chart */
.chart-box {
    margin-top: 20px;
    background: var(--card-bg);
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    height: 400px;
}
.chart-box canvas { width: 100%!important; height: 100%!important; }
</style>
</head>
<body>
<div class="container">
    <header>
    <h2>📊 Data AMI <?= $tahun_filter; ?></h2>
    <div>
        <a href="lihat_pi.php" class="btn-back">⬅ Kembali</a>
        <button class="theme-toggle" onclick="toggleTheme()">🌙 / ☀️</button>
    </div>
</header>

    <!-- Filter -->
    <form method="GET">
        <label>Tahun:</label>
        <select name="tahun">
            <?php foreach($tahunDB as $th): ?>
                <option value="<?= $th ?>" <?= ($th==$tahun_filter?'selected':'') ?>><?= $th ?></option>
            <?php endforeach; ?>
        </select>
        <label>Unit:</label>
        <select name="unit">
            <option value="">-- Semua --</option>
            <?php foreach($unitDB as $un): ?>
                <option value="<?= $un ?>" <?= ($un==$unit_filter?'selected':'') ?>><?= $un ?></option>
            <?php endforeach; ?>
        </select>
        <label>Bulan:</label>
        <select name="bulan">
            <option value="">-- Semua --</option>
            <?php foreach($bulanList as $bl): ?>
                <option value="<?= $bl ?>" <?= ($bl==$bulan_filter?'selected':'') ?>><?= $bl ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filter</button>
        <a href="tambah_data.php">➕ Tambah Data</a>
    </form>

    <!-- Ringkasan -->
    <div class="summary">
        <div class="card">
            <h3>Total Target</h3>
            <p><?= number_format($total_target,2,",",".") ?></p>
        </div>
        <div class="card">
            <h3>Total Realisasi</h3>
            <p><?= number_format($total_realisasi,2,",",".") ?></p>
        </div>
        <div class="card">
            <h3>Rata-rata Pencapaian</h3>
            <p><?= number_format($rata2_pencapaian,2,",",".") ?>%</p>
        </div>
    </div>

    <!-- Table -->
    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Unit</th>
                    <th>Bulan</th>
                    <th>Tahun</th>
                    <th>Target</th>
                    <th>Realisasi</th>
                    <th>% Pencapaian</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if(!empty($dataRows)): $no=1; foreach($dataRows as $row): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= $row['unit'] ?></td>
                    <td><?= $row['bulan'] ?></td>
                    <td><?= $row['tahun'] ?></td>
                    <td><?= number_format($row['target'],2,",",".") ?></td>
                    <td><?= number_format($row['realisasi'],2,",",".") ?></td>
                    <td><?= number_format(($row['target']>0?($row['realisasi']/$row['target']*100):0),2,",",".") ?>%</td>
                    <td class="actions">
                        <a href="edit_data.php?id=<?= $row['id'] ?>">✏</a>
                        <a href="hapus_data.php?id=<?= $row['id'] ?>" onclick="return confirm('Yakin hapus data ini?')">🗑</a>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="8">Data tidak ditemukan</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

      <!-- Chart Line -->
    <div class="chart-box">
        <canvas id="grafik"></canvas>
    </div>

    <!-- Chart Bar -->
    <div class="chart-box">
        <canvas id="grafikBar"></canvas>
    </div>

<script>
// Grafik Line (sudah ada)
new Chart(document.getElementById('grafik'), {
    type: 'line',
    data: {
        labels: <?= json_encode($dataChart['bulan']) ?>,
        datasets: [
            { label:'Target', data:<?= json_encode($dataChart['target']) ?>, borderColor:'blue', backgroundColor:'blue', fill:false, tension:.3 },
            { label:'Realisasi', data:<?= json_encode($dataChart['realisasi']) ?>, borderColor:'green', backgroundColor:'green', fill:false, tension:.3 },
            { label:'% Pencapaian', data:<?= json_encode($dataChart['pencapaian']) ?>, borderColor:'red', backgroundColor:'red', fill:false, tension:.3, yAxisID:'y1' }
        ]
    },
    options: {
        responsive:true,
        maintainAspectRatio:false,
        interaction:{ mode:'index', intersect:false },
        scales:{
            y:{ title:{display:true,text:'Target/Realisasi'} },
            y1:{ type:'linear', position:'right', title:{display:true,text:'% Pencapaian'}, grid:{drawOnChartArea:false}, min:0, max:200 }
        }
    }
});

// Grafik Bar
new Chart(document.getElementById('grafikBar'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($dataChart['bulan']) ?>,
        datasets: [
            { label:'Target', data:<?= json_encode($dataChart['target']) ?>, backgroundColor:'rgba(54,162,235,0.7)' },
            { label:'Realisasi', data:<?= json_encode($dataChart['realisasi']) ?>, backgroundColor:'rgba(75,192,192,0.7)' },
            { label:'% Pencapaian', data:<?= json_encode($dataChart['pencapaian']) ?>, backgroundColor:'rgba(255,99,132,0.7)', yAxisID:'y1' }
        ]
    },
    options: {
        responsive:true,
        maintainAspectRatio:false,
        interaction:{ mode:'index', intersect:false },
        scales:{
            y:{ beginAtZero:true, title:{display:true,text:'Target/Realisasi'} },
            y1:{ type:'linear', position:'right', title:{display:true,text:'% Pencapaian'}, grid:{drawOnChartArea:false}, min:0, max:200 }
        }
    }
});

// dark mode
function toggleTheme(){
    document.body.classList.toggle("dark");
    localStorage.setItem("theme", document.body.classList.contains("dark") ? "dark" : "light");
}
if(localStorage.getItem("theme")==="dark"){ document.body.classList.add("dark"); }
</script>
</body>
</html>
