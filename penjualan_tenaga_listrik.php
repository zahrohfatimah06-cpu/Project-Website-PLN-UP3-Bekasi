<?php
// ==================== SESSION & KONEKSI ====================
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$db   = "pln_dashboard";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// ==================== LOGIN & ROLE ====================
$is_logged_in = isset($_SESSION['user_id']);
$username     = $_SESSION['username'] ?? 'Guest';
$role         = $_SESSION['role'] ?? 'guest';
$is_admin     = ($role === 'admin');

// ==================== VARIABEL GLOBAL ====================
$bulan_arr = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI',
              'JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];

$bulan_filter    = $_GET['bulan'] ?? '';
$unit_filter     = $_GET['unit'] ?? '';
$tahun_sekarang  = date('Y');
$tahun_filter    = $_GET['tahun'] ?? $tahun_sekarang;

// ==================== CEK KOLUMN TAHUN DI DATABASE ====================
$kolom_valid = [];
$res = $conn->query("SHOW COLUMNS FROM penjualan_tenaga_listrik");
while($row = $res->fetch_assoc()){
    $kolom_valid[] = $row['Field'];
}
$res->free();

$tahun_tersedia = [];
foreach ($kolom_valid as $col) {
    if (preg_match('/(target|realisasi)_(\d{4})/', $col, $m)) {
        $tahun_tersedia[] = (int)$m[2];
    }
}
$tahun_tersedia[] = (int)date('Y'); 
$tahun_tersedia = array_unique($tahun_tersedia);
sort($tahun_tersedia);

if (!in_array((int)$tahun_filter, $tahun_tersedia)) {
    $tahun_filter = max($tahun_tersedia);
}

// ==================== QUERY DATA ====================
$realisasi_prev_col = 'realisasi_'.($tahun_filter - 1);
$target_col         = 'target_'.$tahun_filter;
$realisasi_col      = 'realisasi_'.$tahun_filter;

$where  = ["1=1"];
$params = [];
$types  = '';

if ($bulan_filter) {
    $index_bulan = array_search($bulan_filter, $bulan_arr);
    if ($index_bulan !== false) {
        // Ambil semua bulan dari Januari sampai bulan terpilih
        $bulan_sampai = array_slice($bulan_arr, 0, $index_bulan+1);

        $bulan_in = "'".implode("','", $bulan_sampai)."'";
        $where[] = "bulan IN ($bulan_in)";
    }
}
if ($unit_filter) {
    $where[]="unit=?";
    $params[]=$unit_filter;
    $types.='s';
}

$sql = "SELECT unit, bulan, 
               IFNULL(`$realisasi_prev_col`,0) AS realisasi_prev, 
               IFNULL(`$target_col`,0) AS target, 
               IFNULL(`$realisasi_col`,0) AS realisasi, 
               IFNULL(persen_pencapaian,0) AS persen_pencapaian, 
               id 
        FROM penjualan_tenaga_listrik 
        WHERE ".implode(" AND ",$where)."
        ORDER BY FIELD(bulan,'".implode("','",$bulan_arr)."'), unit";

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$data_all = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ==================== HITUNG DATA UNTUK CARD ====================
$total_realisasi_prev = array_sum(array_column($data_all,'realisasi_prev'));
$total_target         = array_sum(array_column($data_all,'target'));
$total_realisasi      = array_sum(array_column($data_all,'realisasi'));
$avg_pencapaian       = $data_all ? round(array_sum(array_column($data_all,'persen_pencapaian'))/count($data_all),2) : 0;

// Fungsi warna berdasarkan pencapaian
function warna_pencapaian($p){
    if($p>=100) return 'green';
    if($p>=80) return 'orange';
    return 'red';
}

$labels = array_column($data_all,'bulan');
$target_data = array_column($data_all,'target');
$realisasi_data = array_column($data_all,'realisasi');
$pencapaian_data = array_column($data_all,'persen_pencapaian');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Penjualan Tenaga Listrik <?= htmlspecialchars($tahun_filter); ?></title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root {
    --bg-color: #f5f5f5;
    --text-color: #222;
    --card-bg: #fff;
    --primary: #007bff;
    --hover-color: #e0e0e0;
}
body.dark {
    --bg-color: #121212;
    --text-color: #f5f5f5;
    --card-bg: #1e1e1e;
    --primary: #4dabf7;
    --hover-color: #2a2a2a;
}
body {
    margin:20px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: var(--bg-color); color: var(--text-color); transition: all 0.3s;
}
h2 { margin-bottom: 20px; }
.filter { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px; align-items:center;
         background: var(--card-bg); padding:15px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.1);}
.filter label { font-weight:bold; margin-right:5px; }
.filter select { padding:5px 10px; border-radius:5px; border:1px solid #ccc; }

.cards { display:flex; flex-wrap:wrap; gap:15px; margin-bottom:20px; }
.card { flex:1 1 200px; background:var(--card-bg); padding:20px; border-radius:10px;
       box-shadow:0 2px 10px rgba(0,0,0,0.1); text-align:center; transition:0.3s; }
.card h3 { margin:0; font-size:1.1rem; margin-bottom:8px; }
.card p { font-size:1.5rem; font-weight:bold; margin:0; }

table { width:100%; border-collapse:collapse; border-radius:10px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,0.1);}
th,td { padding:12px; text-align:center; }
th { background: var(--primary); color:#fff; }
tbody tr:hover { background: var(--hover-color); transition:0.2s; }

.chart-container { width:100%; height:400px; margin-top:40px; }
.back-btn, .toggle-mode { display:inline-block; padding:8px 15px; margin-top:10px; border-radius:5px; border:none; cursor:pointer; font-weight:bold; transition:0.3s; }
.back-btn { background: var(--primary); color:#fff; text-decoration:none; }
.back-btn:hover, .toggle-mode:hover { opacity:0.8; }
.toggle-mode { float:right; background: var(--primary); color:#fff; }
</style>
</head>
<body>
<button class="toggle-mode" onclick="toggleMode()">🌙/☀️ Mode</button>
<h2>📊 Penjualan Tenaga Listrik Tahun <?= htmlspecialchars($tahun_filter); ?></h2>
<!-- Tombol Kembali -->
<a href="lihat_kpi.php" class="back-btn" style="margin-bottom:15px; display:inline-block;">⬅️ Kembali ke KPI</a>

<!-- Filter -->
<form method="get" class="filter">
    <label>Tahun:</label>
    <select name="tahun" onchange="this.form.submit()">
        <?php foreach($tahun_tersedia as $th): ?>
            <option value="<?= $th ?>" <?= $th==$tahun_filter?'selected':'' ?>><?= $th ?></option>
        <?php endforeach; ?>
    </select>
    <label>Bulan:</label>
    <select name="bulan" onchange="this.form.submit()">
        <option value="">-- Semua --</option>
        <?php foreach($bulan_arr as $b): ?>
            <option value="<?= $b ?>" <?= $b==$bulan_filter?'selected':'' ?>><?= $b ?></option>
        <?php endforeach; ?>
    </select>
    <label>Unit:</label>
    <select name="unit" onchange="this.form.submit()">
        <option value="">-- Semua --</option>
        <?php 
        $unit_q = $conn->query("SELECT DISTINCT unit FROM penjualan_tenaga_listrik ORDER BY unit");
        while($u=$unit_q->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($u['unit']) ?>" <?= $u['unit']==$unit_filter?'selected':'' ?>><?= htmlspecialchars($u['unit']) ?></option>
        <?php endwhile; ?>
    </select>
</form>

<!-- Cards -->
<div class="cards">
    <div class="card">
        <h3>Total Realisasi <?= $tahun_filter-1; ?></h3>
        <p><?= number_format($total_realisasi_prev,2,',','.') ?></p>
    </div>
    <div class="card">
        <h3>Total Target <?= $tahun_filter; ?></h3>
        <p><?= number_format($total_target,2,',','.') ?></p>
    </div>
    <div class="card">
        <h3>Total Realisasi <?= $tahun_filter; ?></h3>
        <p><?= number_format($total_realisasi,2,',','.') ?></p>
    </div>
    <div class="card">
        <h3>Rata-rata Pencapaian</h3>
        <p style="color:<?= warna_pencapaian($avg_pencapaian) ?>"><?= $avg_pencapaian ?>%</p>
    </div>
</div>

<!-- Tabel -->
<table>
    <thead>
        <tr>
            <th>Unit</th>
            <th>Bulan</th>
            <th>Realisasi <?= $tahun_filter-1; ?></th>
            <th>Target <?= $tahun_filter; ?></th>
            <th>Realisasi <?= $tahun_filter; ?></th>
            <th>% Pencapaian</th>
        </tr>
    </thead>
    <tbody>
        <?php if($data_all): foreach($data_all as $d): ?>
            <tr>
                <td><?= htmlspecialchars($d['unit']) ?></td>
                <td><?= htmlspecialchars($d['bulan']) ?></td>
                <td><?= number_format($d['realisasi_prev'],2,',','.') ?></td>
                <td><?= number_format($d['target'],2,',','.') ?></td>
                <td><?= number_format($d['realisasi'],2,',','.') ?></td>
                <td style="color:<?= warna_pencapaian($d['persen_pencapaian']) ?>"><?= number_format($d['persen_pencapaian'],2,',','.') ?>%</td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="6">Tidak ada data</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Grafik -->
<div class="chart-container">
    <canvas id="chartPenjualan"></canvas>
</div>

<a href="lihat_kpi.php" class="back-btn">⬅️ Kembali ke KPI</a>

<script>
const ctx = document.getElementById('chartPenjualan').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [
            {
                label: 'Target <?= $tahun_filter ?>',
                data: <?= json_encode($target_data) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderWidth:1
            },
            {
                label: 'Realisasi <?= $tahun_filter ?>',
                data: <?= json_encode($realisasi_data) ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                borderWidth:1
            },
            {
                label: '% Pencapaian',
                data: <?= json_encode($pencapaian_data) ?>,
                type: 'line',
                borderColor: 'rgba(255,99,132,1)',
                borderWidth:2,
                fill:false,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive:true,
        interaction:{ mode:'index', intersect:false },
        scales:{
            y:{ beginAtZero:true, title:{display:true,text:'Nilai'} },
            y1:{ beginAtZero:true, position:'right', grid:{ drawOnChartArea:false }, title:{display:true,text:'% Pencapaian'} }
        }
    }
});

function toggleMode(){
    document.body.classList.toggle("dark");
    localStorage.setItem("theme", document.body.classList.contains("dark") ? "dark" : "light");
}
if(localStorage.getItem("theme")==="dark"){ document.body.classList.add("dark"); }
</script>
</body>
</html>
