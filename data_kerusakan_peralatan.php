<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Koneksi database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "pln_dashboard";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: ".$conn->connect_error);

// Ambil daftar unit dan bulan unik
$result_units = $conn->query("SELECT DISTINCT unit FROM data_kerusakan_peralatan ORDER BY unit ASC");
$result_months = $conn->query("SELECT DISTINCT bulan FROM data_kerusakan_peralatan ORDER BY FIELD(bulan, 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember')");

// Filter
$filter_unit = $_GET['unit'] ?? '';
$filter_bulan = $_GET['bulan'] ?? '';

$sql = "SELECT * FROM data_kerusakan_peralatan";
$conditions = [];
if($filter_unit) $conditions[] = "unit='".$conn->real_escape_string($filter_unit)."'";
if($filter_bulan) $conditions[] = "bulan='".$conn->real_escape_string($filter_bulan)."'";
if($conditions) $sql .= " WHERE ".implode(' AND ', $conditions);
$sql .= " ORDER BY id ASC";
$result = $conn->query($sql);

// Data kartu
$sql_cards = "SELECT SUM(target_2025) as total_target, SUM(realisasi_2025) as total_realisasi FROM data_kerusakan_peralatan";
if($conditions) $sql_cards .= " WHERE ".implode(' AND ', $conditions);
$card_result = $conn->query($sql_cards);
$card_data = $card_result->fetch_assoc();
$total_target = $card_data['total_target'] ?? 0;
$total_realisasi = $card_data['total_realisasi'] ?? 0;
$pencapaian_persen = $total_target > 0 ? round(($total_realisasi/$total_target)*100,2) : 0;
$status_pencapaian = $pencapaian_persen >= 100 ? 'Tercapai' : 'Belum Tercapai';

// Data chart
$sql_charts = "SELECT unit, SUM(target_2025) as total_target, SUM(realisasi_2025) as total_realisasi FROM data_kerusakan_peralatan";
if($conditions) $sql_charts .= " WHERE ".implode(' AND ', $conditions);
$sql_charts .= " GROUP BY unit";
$chart_result = $conn->query($sql_charts);
$chart_labels = [];
$chart_targets = [];
$chart_realisasi = [];
if($chart_result && $chart_result->num_rows>0){
    while($row = $chart_result->fetch_assoc()){
        $chart_labels[] = $row['unit'];
        $chart_targets[] = $row['total_target'];
        $chart_realisasi[] = $row['total_realisasi'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Kerusakan Peralatan PLN</title>
<style>
body { font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#e9ecef; margin:0; color:#333;}
.container { max-width:1200px; margin:20px auto; padding:20px; background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.1);}
h1 { text-align:center; padding:25px; background:#004a9f; color:white; margin:-20px -20px 20px -20px; border-radius:12px 12px 0 0; font-weight:600;}
.controls { display:flex; justify-content:space-between; flex-wrap:wrap; gap:15px; margin-bottom:25px;}
.controls select, .controls button { padding:10px 15px; border-radius:6px; border:1px solid #ccc; font-size:15px; cursor:pointer;}
.apply-btn { background:#007bff; color:white; border:none; font-weight:bold;}
.apply-btn:hover { background:#0056b3;}
.export-btn { background:#28a745; color:white; border:none; font-weight:bold;}
.export-btn:hover { background:#218838;}
.back-button { padding:10px 15px; background:#6c757d; color:white; text-decoration:none; border-radius:6px; font-weight:bold;}
.back-button:hover { background:#5a6268;}
.card-container { display:flex; gap:20px; flex-wrap:wrap; margin-bottom:30px; }
.card { flex:1 1 200px; background:#f8f9fa; border:1px solid #e2e6ea; border-radius:10px; padding:20px; text-align:center; box-shadow:0 2px 5px rgba(0,0,0,0.05); transition:0.3s;}
.card:hover { transform:translateY(-5px); box-shadow:0 6px 15px rgba(0,0,0,0.1);}
.card h3 { margin:0 0 10px; font-size:1.1em; color:#666;}
.card p { font-size:2em; font-weight:bold; margin:0; color:#007bff;}
.card .status { font-size:1.2em; font-weight:bold; margin-top:10px;}
.status.tercapai { color:#28a745;}
.status.belum-tercapai { color:#dc3545;}
.chart-container { display:flex; flex-wrap:wrap; gap:25px; justify-content:center; margin-bottom:30px;}
.chart-box { flex:1 1 400px; padding:20px; border:1px solid #ddd; background:#fafafa; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.08);}
#pieChart { max-width:350px !important; max-height:350px !important; margin:0 auto;}
table { width:100%; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; }
th, td { padding:12px 15px; border:1px solid #e9ecef; text-align:center; }
th { background:#007bff; color:white; font-weight:600; }
tr:nth-child(even) { background:#f8f9fa;}
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>
<body>
<div class="container">
<h1>📊 Dashboard Kerusakan Peralatan</h1>

<div style="text-align:right;margin-bottom:20px;"><a href="lihat_pi.php" class="back-button">⬅️ Kembali</a></div>

<form method="GET" action="">
<div class="controls">
<div>
<select name="unit">
<option value="">Semua Unit</option>
<?php while($row_unit = $result_units->fetch_assoc()): ?>
<option value="<?= htmlspecialchars($row_unit['unit']) ?>" <?= $filter_unit==$row_unit['unit']?'selected':'' ?>><?= htmlspecialchars($row_unit['unit']) ?></option>
<?php endwhile; ?>
</select>

<select name="bulan">
<option value="">Semua Bulan</option>
<?php while($row_month = $result_months->fetch_assoc()): ?>
<option value="<?= htmlspecialchars($row_month['bulan']) ?>" <?= $filter_bulan==$row_month['bulan']?'selected':'' ?>><?= htmlspecialchars($row_month['bulan']) ?></option>
<?php endwhile; ?>
</select>

<button type="submit" class="apply-btn">Terapkan Filter</button>
</div>

<div>
<button type="button" class="export-btn" onclick="exportToXLSX()">Export XLSX</button>
<button type="button" class="export-btn" onclick="exportToPDF()">Cetak PDF</button>
<button type="button" class="export-btn" onclick="exportToJPG()">Download JPG</button>
</div>
</div>
</form>

<div class="card-container">
<div class="card"><h3>Total Target</h3><p><?= number_format($total_target) ?></p></div>
<div class="card"><h3>Total Realisasi</h3><p><?= number_format($total_realisasi) ?></p></div>
<div class="card"><h3>Persentase Pencapaian</h3><p><?= $pencapaian_persen ?>%</p></div>
<div class="card"><h3>Status</h3><p class="status <?= $status_pencapaian==='Tercapai'?'tercapai':'belum-tercapai' ?>"><?= $status_pencapaian ?></p></div>
</div>

<div class="chart-container">
<div class="chart-box"><canvas id="barChart"></canvas></div>
<div class="chart-box"><canvas id="lineChart"></canvas></div>
<div class="chart-box"><canvas id="pieChart"></canvas></div>
</div>

<div id="data-table">
<h2>Data Kerusakan Peralatan</h2>
<table>
<thead>
<tr>
<th>No</th><th>Unit</th><th>Bulan</th><th>Target 2025</th><th>Realisasi 2025</th><th>Persentase Pencapaian (%)</th>
</tr>
</thead>
<tbody>
<?php
if($result && $result->num_rows>0){
$no=1;
while($row = $result->fetch_assoc()){
echo "<tr>
<td>$no</td>
<td>".htmlspecialchars($row['unit'])."</td>
<td>".htmlspecialchars($row['bulan'])."</td>
<td>".htmlspecialchars($row['target_2025'])."</td>
<td>".htmlspecialchars($row['realisasi_2025'])."</td>
<td>".htmlspecialchars($row['persentase_pencapaian'])."</td>
</tr>";
$no++;
}
} else {
echo "<tr><td colspan='6'>Tidak ada data.</td></tr>";
}
?>
</tbody>
</table>
</div>
</div>

<script>
const labels = <?= json_encode($chart_labels) ?>;
const targets = <?= json_encode($chart_targets) ?>;
const realisasi = <?= json_encode($chart_realisasi) ?>;

// Bar Chart
new Chart(document.getElementById('barChart'),{
type:'bar',
data:{ labels:labels, datasets:[{label:'Target 2025', data:targets, backgroundColor:'rgba(54,162,235,0.7)', borderColor:'rgba(54,162,235,1)', borderWidth:1},{label:'Realisasi 2025', data:realisasi, backgroundColor:'rgba(255,99,132,0.7)', borderColor:'rgba(255,99,132,1)', borderWidth:1}]},
options:{responsive:true, scales:{x:{grid:{display:false}}, y:{beginAtZero:true, grid:{color:'rgba(0,0,0,0.1)'}}}, plugins:{title:{display:true,text:'Grafik Target vs Realisasi',font:{size:16,weight:'bold'}},legend:{position:'bottom'}}}
});

// Line Chart
new Chart(document.getElementById('lineChart'),{
type:'line',
data:{ labels:labels, datasets:[{label:'Target 2025', data:targets, borderColor:'#007bff', backgroundColor:'rgba(0,123,255,0.1)', fill:true, tension:0.4},{label:'Realisasi 2025', data:realisasi, borderColor:'#28a745', backgroundColor:'rgba(40,167,69,0.1)', fill:true, tension:0.4}]},
options:{responsive:true, scales:{x:{grid:{display:false}},y:{beginAtZero:true, grid:{color:'rgba(0,0,0,0.1)'}}}, plugins:{title:{display:true,text:'Tren Target vs Realisasi',font:{size:16,weight:'bold'}},legend:{position:'bottom'}}}
});

// Pie Chart
new Chart(document.getElementById('pieChart'),{
type:'pie',
data:{labels:labels,datasets:[{label:'Realisasi 2025',data:realisasi,backgroundColor:['#007bff','#28a745','#ffc107','#dc3545','#17a2b8','#6c757d','#fd7e14'],borderColor:'#fff',borderWidth:2}]},
options:{responsive:true, maintainAspectRatio:false, plugins:{title:{display:true,text:'Distribusi Realisasi 2025',font:{size:16,weight:'bold'}},legend:{position:'bottom',labels:{padding:15,font:{size:12}}}}}
});

// Export XLSX
function exportToXLSX(){const table=document.querySelector('table');const ws=XLSX.utils.table_to_sheet(table);const wb=XLSX.utils.book_new();XLSX.utils.book_append_sheet(wb,ws,"Data Kerusakan");XLSX.writeFile(wb,"Data_Kerusakan_Peralatan.xlsx");}
// Export PDF
function exportToPDF(){const { jsPDF }=window.jspdf;const doc=new jsPDF('p','pt','a4');const container=document.querySelector('.container');html2canvas(container,{scale:2}).then(canvas=>{const imgData=canvas.toDataURL('image/png');const imgWidth=595.28;const pageHeight=841.89;const imgHeight=canvas.height*imgWidth/canvas.width;let heightLeft=imgHeight;let position=0;doc.addImage(imgData,'PNG',0,position,imgWidth,imgHeight);heightLeft-=pageHeight;while(heightLeft>=0){position=heightLeft-imgHeight;doc.addPage();doc.addImage(imgData,'PNG',0,position,imgWidth,imgHeight);heightLeft-=pageHeight;}doc.save('Dashboard_PLN_Kerusakan.pdf');});}
// Export JPG
function exportToJPG(){const container=document.querySelector('.container');html2canvas(container,{scale:2}).then(canvas=>{const link=document.createElement('a');link.download='Dashboard_PLN_Kerusakan.jpg';link.href=canvas.toDataURL('image/jpeg',0.9);link.click();});}
</script>
</body>
</html>
<?php $conn->close(); ?>
