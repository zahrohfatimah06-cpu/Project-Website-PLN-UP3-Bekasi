<?php
// koneksi database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "pln_dashboard";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// array bulan berurutan
$bulan_arr = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI',
              'JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];

// Ambil filter bulan & unit
$bulan_filter = isset($_GET['bulan']) ? $_GET['bulan'] : '';
$unit_filter  = isset($_GET['unit']) ? $_GET['unit'] : '';

// Ambil daftar unit unik dari tabel
$units = [];
$unit_sql = "SELECT DISTINCT unit FROM data_ens ORDER BY unit ASC";
$unit_result = $conn->query($unit_sql);
if ($unit_result && $unit_result->num_rows > 0) {
    while ($u = $unit_result->fetch_assoc()) {
        $units[] = $u['unit'];
    }
}

// ==== REVISI: cari bulan dari Januari sampai bulan terpilih + bulan berikutnya ====
$bulan_chart = [];
if ($bulan_filter) {
    $index = array_search($bulan_filter, $bulan_arr);
    if ($index !== false) {
        // dari Januari sampai bulan terpilih
        for ($i = 0; $i <= $index; $i++) {
            $bulan_chart[] = $bulan_arr[$i];
        }
        // tambah 1 bulan berikutnya kalau ada
        if (isset($bulan_arr[$index+1])) {
            $bulan_chart[] = $bulan_arr[$index+1];
        }
    }
}

// ==== SIMPAN CATATAN ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_catatan'])) {
    $catatan1 = $conn->real_escape_string($_POST['catatan1']);
    $catatan2 = $conn->real_escape_string($_POST['catatan2']);

    if ($unit_filter && $bulan_filter) {
        $cek = $conn->query("SELECT id FROM catatan_ens WHERE unit='$unit_filter' AND bulan='$bulan_filter'");
        if ($cek && $cek->num_rows > 0) {
            $conn->query("UPDATE catatan_ens 
                          SET catatan1='$catatan1', catatan2='$catatan2' 
                          WHERE unit='$unit_filter' AND bulan='$bulan_filter'");
        } else {
            $conn->query("INSERT INTO catatan_ens (unit, bulan, catatan1, catatan2) 
                          VALUES ('$unit_filter','$bulan_filter','$catatan1','$catatan2')");
        }
    }
}

// ==== AMBIL CATATAN UNTUK FILTER SEKARANG ====
$catatan1 = $catatan2 = '';
if ($unit_filter && $bulan_filter) {
    $ct = $conn->query("SELECT * FROM catatan_ens WHERE unit='$unit_filter' AND bulan='$bulan_filter' LIMIT 1");
    if ($ct && $ct->num_rows > 0) {
        $row_ct = $ct->fetch_assoc();
        $catatan1 = $row_ct['catatan1'];
        $catatan2 = $row_ct['catatan2'];
    }
}

// ==== Query data untuk tabel ====
$sql = "SELECT * FROM data_ens WHERE 1=1";
if ($bulan_filter) {
    $sql .= " AND bulan = '" . $conn->real_escape_string($bulan_filter) . "'";
}
if ($unit_filter) {
    $sql .= " AND unit = '" . $conn->real_escape_string($unit_filter) . "'";
}
$sql .= " ORDER BY FIELD(bulan,'JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI',
                        'JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'), unit";
$result = $conn->query($sql);

// ==== Query data untuk grafik ====
$chartData = [];
if (!empty($bulan_chart) && $unit_filter) {
    $bulan_in = "'" . implode("','", $bulan_chart) . "'";
    $chart_sql = "SELECT bulan, target_2025, realisasi_2025 
                  FROM data_ens 
                  WHERE unit='" . $conn->real_escape_string($unit_filter) . "' 
                  AND bulan IN ($bulan_in)
                  ORDER BY FIELD(bulan,'JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI',
                        'JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER')";
    $chart_result = $conn->query($chart_sql);
    if ($chart_result && $chart_result->num_rows > 0) {
        while ($c = $chart_result->fetch_assoc()) {
            $chartData[] = $c;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data ENS PLN</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: none;
        }
        
        .card-header {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
        }
        
        .table th {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            color: white;
        }
        
        .filter-section {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .notes-section {
            background: linear-gradient(to bottom, #e6f7ff, #ffffff);
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #0d6efd;
        }
        
        .notes-section h6 {
            color: #0a58ca;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .notes-section h6 i {
            margin-right: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #0a58ca, #084298);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #198754, #0f5132);
            border: none;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #0f5132, #0a3622);
        }
        
        .positive {
            color: #198754;
            font-weight: 600;
        }
        
        .negative {
            color: #dc3545;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
            }
            
            .notes-column {
                margin-top: 20px;
            }
        }
    </style>
</head>
<body class="container py-4">

    <h2 class="mb-4 text-primary"><i class="fas fa-bolt"></i> Data ENS PLN</h2>

    <!-- Filter + Catatan -->
    <div class="filter-section">
        <div class="row filter-row">
            <div class="col-md-8">
                <form method="get" class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Pilih Bulan</label>
                        <select name="bulan" class="form-select">
                            <option value="">-- Semua Bulan --</option>
                            <?php
                            foreach ($bulan_arr as $b) {
                                $sel = ($bulan_filter == $b) ? 'selected' : '';
                                echo "<option value='$b' $sel>$b</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Pilih Unit</label>
                        <select name="unit" class="form-select">
                            <option value="">-- Semua Unit --</option>
                            <?php
                            foreach ($units as $u) {
                                $sel = ($unit_filter == $u) ? 'selected' : '';
                                echo "<option value='$u' $sel>$u</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Terapkan Filter</button>
                    </div>
                </form>
            </div>
            
            <!-- Catatan Section - Berderet Atas Bawah -->
            <div class="col-md-4 notes-column">
                <?php if ($unit_filter && $bulan_filter): ?>
                <div class="notes-section">
                    <h6><i class="fas fa-sticky-note"></i> Catatan untuk <?= $unit_filter ?> - <?= $bulan_filter ?></h6>
                    <form method="post">
                        <input type="hidden" name="simpan_catatan" value="1">
                        <div class="mb-3">
                            <label class="form-label"><strong>Catatan 1</strong></label>
                            <textarea name="catatan1" class="form-control" rows="2" placeholder="Tulis catatan di sini..."><?= htmlspecialchars($catatan1) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><strong>Catatan 2</strong></label>
                            <textarea name="catatan2" class="form-control" rows="2" placeholder="Tulis catatan di sini..."><?= htmlspecialchars($catatan2) ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-save"></i> Simpan Catatan
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Grafik -->
    <?php if (!empty($chartData)) : ?>
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-chart-bar"></i> Grafik Perbandingan Target & Realisasi 2025 (<?= $unit_filter ?> - <?= $bulan_filter ?>)
        </div>
        <div class="card-body">
            <canvas id="ensChart" height="100"></canvas>
        </div>
    </div>
    <script>
        const ctx = document.getElementById('ensChart').getContext('2d');
        const chartData = <?= json_encode($chartData) ?>;
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.map(d => d.bulan),
                datasets: [
                    {
                        label: 'Target 2025',
                        data: chartData.map(d => d.target_2025),
                        backgroundColor: 'rgba(13, 110, 253, 0.7)',
                        borderColor: 'rgba(13, 110, 253, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Realisasi 2025',
                        data: chartData.map(d => d.realisasi_2025),
                        backgroundColor: 'rgba(25, 135, 84, 0.7)',
                        borderColor: 'rgba(25, 135, 84, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Perbandingan Target dan Realisasi ENS 2025'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nilai ENS'
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>

    <!-- Tabel Data -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-table"></i> Tabel Data ENS
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Unit</th>
                            <th>Bulan</th>
                            <th class="text-end">Realisasi 2024</th>
                            <th class="text-end">Target 2025</th>
                            <th class="text-end">Realisasi 2025</th>
                            <th class="text-center">Pencapaian (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result && $result->num_rows > 0) {
                            $no = 1;
                            while ($row = $result->fetch_assoc()) {
                                $pencapaian = $row['pencapaian'];
                                $pencapaian_class = ($pencapaian >= 100) ? 'positive' : 'negative';
                                
                                echo "<tr>
                                    <td>{$no}</td>
                                    <td>{$row['unit']}</td>
                                    <td>{$row['bulan']}</td>
                                    <td class='text-end'>" . number_format($row['realisasi_2024'], 2, ",", ".") . "</td>
                                    <td class='text-end'>" . number_format($row['target_2025'], 2, ",", ".") . "</td>
                                    <td class='text-end'>" . number_format($row['realisasi_2025'], 2, ",", ".") . "</td>
                                    <td class='text-center {$pencapaian_class}'>" . number_format($pencapaian, 2, ",", ".") . "%</td>
                                </tr>";
                                $no++;
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center'>Data tidak ditemukan</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tampilkan Catatan di Bawah Tabel jika ada -->
    <?php if (($catatan1 || $catatan2) && $unit_filter && $bulan_filter): ?>
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <i class="fas fa-sticky-note"></i> Catatan Tersimpan untuk <?= $unit_filter ?> - <?= $bulan_filter ?>
        </div>
        <div class="card-body">
            <?php if ($catatan1): ?>
            <div class="mb-3">
                <h6><i class="fas fa-pencil-alt"></i> Catatan 1:</h6>
                <p class="mb-1"><?= nl2br(htmlspecialchars($catatan1)) ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($catatan2): ?>
            <div>
                <h6><i class="fas fa-pencil-alt"></i> Catatan 2:</h6>
                <p class="mb-0"><?= nl2br(htmlspecialchars($catatan2)) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>