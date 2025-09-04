<?php
// Koneksi ke database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "pln_dashboard";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil opsi filter Unit dan Bulan
$units_result = $conn->query("SELECT DISTINCT unit FROM data_dispatch ORDER BY unit");
$months_result = $conn->query("SELECT DISTINCT bulan FROM data_dispatch ORDER BY id");

// Ambil data berdasarkan filter (jika ada)
$where = [];
if (isset($_GET['unit']) && $_GET['unit'] != '') {
    $unit = $conn->real_escape_string($_GET['unit']);
    $where[] = "unit = '$unit'";
}
if (isset($_GET['bulan']) && $_GET['bulan'] != '') {
    $bulan = $conn->real_escape_string($_GET['bulan']);
    $where[] = "bulan = '$bulan'";
}
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

$query = "SELECT * FROM data_dispatch $where_sql ORDER BY id ASC";
$result = $conn->query($query);

// Ambil data untuk grafik
$chart_query = "SELECT unit, bulan, target_2025, realisasi_2025, persen_pencapaian 
                FROM data_dispatch ORDER BY id ASC";
$chart_result = $conn->query($chart_query);

$labels = [];
$targets = [];
$realisasi = [];
$pencapaian = [];

while ($row = $chart_result->fetch_assoc()) {
    $labels[] = $row['unit'] . " - " . $row['bulan'];
    $targets[] = $row['target_2025'];
    $realisasi[] = $row['realisasi_2025'];
    $pencapaian[] = $row['persen_pencapaian'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>PLN Dashboard - Data Dispatch</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #eef2f7;}
        header {
            background: linear-gradient(90deg, #007BFF, #004080); 
            color: #fff; padding: 15px 30px; 
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.3);
        }
        header h1 {margin: 0; font-size: 22px;}
        header .logo {font-size: 26px; font-weight: bold;}
        .container {display: flex;}
        nav {
            width: 220px; background: #004080; color: #fff; min-height: 100vh;
            padding: 20px; box-sizing: border-box;
        }
        nav h3 {color: #ffde59; margin-top: 0;}
        nav ul {list-style: none; padding: 0;}
        nav ul li {margin: 15px 0;}
        nav ul li a {color: #fff; text-decoration: none; font-weight: 500;}
        nav ul li a:hover {color: #ffde59;}
        main {flex: 1; padding: 20px;}
        .dashboard {display: flex; gap: 20px;}
        .left {flex: 3; display: flex; flex-direction: column; gap: 20px;}
        .right {flex: 1; display: flex; flex-direction: column; gap: 20px;}
        .filter-form {
            margin-bottom: 10px; display: flex; gap: 10px; flex-wrap: wrap;
            background: #fff; padding: 15px; border-radius: 12px; box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }
        select, button {padding: 8px 14px; border-radius: 6px; border: 1px solid #ccc;}
        button {background: #007BFF; color: #fff; border: none; cursor: pointer;}
        button:hover {background: #0056b3;}
        table {width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 3px 8px rgba(0,0,0,0.1);}
        th, td {padding: 10px; text-align: center; border-bottom: 1px solid #eee;}
        th {background: #007BFF; color: #fff; font-weight: 600;}
        tr:nth-child(even) {background: #f9f9f9;}
        .card {
            background: #fff; padding: 15px; border-radius: 12px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }
        .card h3 {margin-top: 0; color: #007BFF;}
        .card-stats {display: flex; gap: 15px;}
        .stat {
            background: linear-gradient(135deg,#007BFF,#004080);
            padding: 15px; border-radius: 12px; text-align: center; flex: 1;
            color: #fff; font-weight: bold; box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        .stat h4 {margin: 5px 0; font-size: 14px; color: #ffde59;}
        .stat p {font-size: 20px;}
        canvas {
            background: #fff; padding: 15px; border-radius: 12px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header>
        <div class="logo"><i class="fas fa-bolt"></i> PLN Dashboard</div>
        <h1>Data Dispatch 2025</h1>
        <div><i class="fas fa-user-circle"></i> Admin</div>
    </header>

    <div class="container">
        <!-- SIDEBAR -->
        <nav>
            <h3>Menu</h3>
            <ul>
                <li><a href="#"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="#"><i class="fas fa-database"></i> Data Dispatch</a></li>
                <li><a href="#"><i class="fas fa-chart-line"></i> Laporan</a></li>
                <li><a href="#"><i class="fas fa-sticky-note"></i> Catatan</a></li>
                <li><a href="#"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- MAIN CONTENT -->
        <main>
            <div class="dashboard">
                <!-- LEFT CONTENT -->
                <div class="left">
                    <!-- FILTER -->
                    <form method="GET" class="filter-form">
                        <label>Unit:</label>
                        <select name="unit">
                            <option value="">-- Semua Unit --</option>
                            <?php 
                            $units_result->data_seek(0);
                            while ($row = $units_result->fetch_assoc()): ?>
                                <option value="<?= $row['unit']; ?>" <?= (isset($unit) && $unit == $row['unit']) ? 'selected' : ''; ?>>
                                    <?= $row['unit']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <label>Bulan:</label>
                        <select name="bulan">
                            <option value="">-- Semua Bulan --</option>
                            <?php 
                            $months_result->data_seek(0);
                            while ($row = $months_result->fetch_assoc()): ?>
                                <option value="<?= $row['bulan']; ?>" <?= (isset($bulan) && $bulan == $row['bulan']) ? 'selected' : ''; ?>>
                                    <?= $row['bulan']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <button type="submit"><i class="fas fa-filter"></i> Filter</button>
                    </form>

                    <!-- CARD INFO -->
                    <div class="card-stats">
                        <div class="stat">
                            <h4>Unit Terpilih</h4>
                            <p><?= isset($unit) ? $unit : "Semua Unit"; ?></p>
                        </div>
                        <div class="stat">
                            <h4>Bulan Terpilih</h4>
                            <p><?= isset($bulan) ? $bulan : "Semua Bulan"; ?></p>
                        </div>
                    </div>

                    <!-- TABLE -->
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Unit</th>
                                <th>Bulan</th>
                                <th>Target 2025</th>
                                <th>Realisasi 2025</th>
                                <th>% Pencapaian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $row['id']; ?></td>
                                        <td><?= $row['unit']; ?></td>
                                        <td><?= $row['bulan']; ?></td>
                                        <td><?= number_format($row['target_2025'], 2); ?></td>
                                        <td><?= number_format($row['realisasi_2025'], 2); ?></td>
                                        <td><?= $row['persen_pencapaian']; ?>%</td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6">Tidak ada data ditemukan</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- GRAFIK -->
                    <canvas id="barChart"></canvas>
                    <canvas id="lineChart"></canvas>
                    <canvas id="donutChart"></canvas>
                </div>

                <!-- RIGHT CONTENT: NOTES -->
                <div class="right">
                    <div class="card">
                        <h3><i class="fas fa-sticky-note"></i> Catatan 1</h3>
                        <textarea rows="6" placeholder="Tulis catatan penting di sini..." style="width:100%;"></textarea>
                        <button class="btn-save"><i class="fas fa-save"></i> Simpan</button>
                    </div>
                    <div class="card">
                        <h3><i class="fas fa-sticky-note"></i> Catatan 2</h3>
                        <textarea rows="6" placeholder="Tulis catatan tambahan di sini..." style="width:100%;"></textarea>
                        <button class="btn-save"><i class="fas fa-save"></i> Simpan</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const labels = <?= json_encode($labels); ?>;
        const targets = <?= json_encode($targets); ?>;
        const realisasi = <?= json_encode($realisasi); ?>;
        const pencapaian = <?= json_encode($pencapaian); ?>;

        // BAR CHART
        new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Target 2025',
                        data: targets,
                        backgroundColor: 'rgba(40, 167, 69, 0.7)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Realisasi 2025',
                        data: realisasi,
                        backgroundColor: 'rgba(0, 123, 255, 0.7)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Perbandingan Target & Realisasi' }
                },
                scales: { y: { beginAtZero: true } }
            }
        });

        // LINE CHART
        new Chart(document.getElementById('lineChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Target 2025',
                        data: targets,
                        borderColor: 'green',
                        backgroundColor: 'rgba(40, 167, 69, 0.3)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Realisasi 2025',
                        data: realisasi,
                        borderColor: 'blue',
                        backgroundColor: 'rgba(0, 123, 255, 0.3)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Tren Target dan Realisasi' }
                }
            }
        });

        // DONUT CHART (Persen Pencapaian)
        const colors = pencapaian.map(val => {
            if (val < 50) return 'rgba(220, 53, 69,0.7)'; // merah
            if (val < 70) return 'rgba(255,193,7,0.7)';  // kuning
            if (val < 90) return 'rgba(23,162,184,0.7)'; // biru
            return 'rgba(40,167,69,0.7)';                // hijau
        });

        new Chart(document.getElementById('donutChart'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: pencapaian,
                    backgroundColor: colors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'right' },
                    title: { display: true, text: '% Pencapaian per Unit-Bulan' }
                }
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
