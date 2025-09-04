  <?php
    // Data dari tabel yang diberikan
    $rawData = array(
        array("unit" => "UP3 BEKASI", "bulan" => "JANUARI", "target" => 100.00, "realisasi" => 99.83, "pencapaian" => 99.83),
        array("unit" => "ULP BEKASI KOTA", "bulan" => "JANUARI", "target" => 100.00, "realisasi" => 100.00, "pencapaian" => 100.00),
        array("unit" => "ULP MEDAN SATRIA", "bulan" => "JANUARI", "target" => 100.00, "realisasi" => 100.00, "pencapaian" => 100.00),
        array("unit" => "ULP BABELAN", "bulan" => "JANUARI", "target" => 100.00, "realisasi" => 99.36, "pencapaian" => 99.36),
        array("unit" => "ULP BANTARGEBANG", "bulan" => "JANUARI", "target" => 100.00, "realisasi" => 100.00, "pencapaian" => 100.00),
        array("unit" => "ULP MUSTIKAJAYA", "bulan" => "JANUARI", "target" => 100.00, "realisasi" => 100.00, "pencapaian" => 100.00),
        array("unit" => "ULP PRIMA BEKASI", "bulan" => "JANUARI", "target" => 100.00, "realisasi" => 100.00, "pencapaian" => 100.00),
        array("unit" => "UP3 BEKASI", "bulan" => "FEBRUARI", "target" => 100.00, "realisasi" => 99.66, "pencapaian" => 99.66),
        array("unit" => "ULP BEKASI KOTA", "bulan" => "FEBRUARI", "target" => 100.00, "realisasi" => 100.00, "pencapaian" => 100.00),
        array("unit" => "ULP MEDAN SATRIA", "bulan" => "FEBRUARI", "target" => 100.00, "realisasi" => 100.00, "pencapaian" => 100.00),
        array("unit" => "ULP BABELAN", "bulan" => "FEBRUARI", "target" => 100.00, "realisasi" => 98.78, "pencapaian" => 98.78),
        array("unit" => "ULP BANTARGEBANG", "bulan" => "FEBRUARI", "target" => 100.00, "realisasi" => 100.00, "pencapaian" => 100.00),
        array("unit" => "ULP MUSTIKAJAYA", "bulan" => "FEBRUARI", "target" => 100.00, "realisasi" => 100.00, "pencapaian" => 100.00),
        array("unit" => "ULP PRIMA BEKASI", "bulan" => "FEBRUARI", "target" => 100.00, "realisasi" => 100.00, "pencapaian" => 100.00),
        array("unit" => "UP3 BEKASI", "bulan" => "MARET", "target" => 100.00, "realisasi" => 99.52, "pencapaian" => 99.52),
        array("unit" => "ULP BEKASI KOTA", "bulan" => "MARET", "target" => 100.00, "realisasi" => 100.00, "pencapaian" => 100.00),
        array("unit" => "ULP MEDAN SATRIA", "bulan" => "MARET", "target" => 100.00, "realisasi" => 99.79, "pencapaian" => 99.79),
        array("unit" => "ULP BABELAN", "bulan" => "MARET", "target" => 100.00, "realisasi" => 99.03, "pencapaian" => 99.03),
        array("unit" => "ULP BANTARGEBANG", "bulan" => "MARET", "target" => 100.00, "realisasi" => 99.10, "pencapaian" => 99.10),
        array("unit" => "ULP MUSTIKAJAYA", "bulan" => "MARET", "target" => 100.00, "realisasi" => 100.00, "pencapaian" => 100.00),
        array("unit" => "ULP PRIMA BEKASI", "bulan" => "MARET", "target" => 100.00, "realisasi" => 100.00, "pencapaian" => 100.00),
        array("unit" => "UP3 BEKASI", "bulan" => "APRIL", "target" => 100.00, "realisasi" => 99.53, "pencapaian" => 99.53),
        array("unit" => "ULP BEKASI KOTA", "bulan" => "APRIL", "target" => 100.00, "realisasi" => 100.00, "pencapaian" => 100.00),
        array("unit" => "ULP MEDAN SATRIA", "bulan" => "APRIL", "target" => 100.00, "realisasi" => 99.83, "pencapaian" => 99.83),
        array("unit" => "ULP BABELAN", "bulan" => "APRIL", "target" => 100.00, "realisasi" => 99.22, "pencapaian" => 99.22),
        array("unit" => "ULP BANTARGEBANG", "bulan" => "APRIL", "target" => 100.00, "realisasi" => 98.88, "pencapaian" => 98.88),
        array("unit" => "ULP MUSTIKAJAYA", "bulan" => "APRIL", "target" => 100.00, "realisasi" => 99.98, "pencapaian" => 99.98),
        array("unit" => "ULP PRIMA BEKASI", "bulan" => "APRIL", "target" => 100.00, "realisasi" => 100.00, "pencapaian" => 100.00),
        array("unit" => "UP3 BEKASI", "bulan" => "MEI", "target" => 100.00, "realisasi" => 99.16, "pencapaian" => 99.16),
        array("unit" => "ULP BEKASI KOTA", "bulan" => "MEI", "target" => 100.00, "realisasi" => 99.97, "pencapaian" => 99.97),
        array("unit" => "ULP MEDAN SATRIA", "bulan" => "MEI", "target" => 100.00, "realisasi" => 99.75, "pencapaian" => 99.75),
        array("unit" => "ULP BABELAN", "bulan" => "MEI", "target" => 100.00, "realisasi" => 98.61, "pencapaian" => 98.61),
        array("unit" => "ULP BANTARGEBANG", "bulan" => "MEI", "target" => 100.00, "realisasi" => 98.76, "pencapaian" => 98.76),
        array("unit" => "ULP MUSTIKAJAYA", "bulan" => "MEI", "target" => 100.00, "realisasi" => 99.22, "pencapaian" => 99.22),
        array("unit" => "ULP PRIMA BEKASI", "bulan" => "MEI", "target" => 100.00, "realisasi" => 100.00, "pencapaian" => 100.00),
        array("unit" => "UP3 BEKASI", "bulan" => "JUNI", "target" => 100.00, "realisasi" => 100.00, "pencapaian" => 100.00),
        array("unit" => "ULP BEKASI KOTA", "bulan" => "JUNI", "target" => 100.00, "realisasi" => 100.00, "pencapaian" => 100.00),
        array("unit" => "ULP MEDAN SATRIA", "bulan" => "JUNI", "target" => 100.00, "realisasi" => 100.00, "pencapaian" => 100.00),
        array("unit" => "ULP BABELAN", "bulan" => "JUNI", "target" => 100.00, "realisasi" => 100.00, "pencapaian" => 100.00),
        array("unit" => "ULP BANTARGEBANG", "bulan" => "JUNI", "target" => 100.00, "realisasi" => 100.00, "pencapaian" => 100.00),
        array("unit" => "ULP MUSTIKAJAYA", "bulan" => "JUNI", "target" => 100.00, "realisasi" => 100.00, "pencapaian" => 100.00),
        array("unit" => "ULP PRIMA BEKASI", "bulan" => "JUNI", "target" => 100.00, "realisasi" => 100.00, "pencapaian" => 100.00),
        array("unit" => "UP3 BEKASI", "bulan" => "JULI", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP BEKASI KOTA", "bulan" => "JULI", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP MEDAN SATRIA", "bulan" => "JULI", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP BABELAN", "bulan" => "JULI", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP BANTARGEBANG", "bulan" => "JULI", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP MUSTIKAJAYA", "bulan" => "JULI", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP PRIMA BEKASI", "bulan" => "JULI", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "UP3 BEKASI", "bulan" => "AGUSTUS", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP BEKASI KOTA", "bulan" => "AGUSTUS", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP MEDAN SATRIA", "bulan" => "AGUSTUS", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP BABELAN", "bulan" => "AGUSTUS", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP BANTARGEBANG", "bulan" => "AGUSTUS", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP MUSTIKAJAYA", "bulan" => "AGUSTUS", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP PRIMA BEKASI", "bulan" => "AGUSTUS", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "UP3 BEKASI", "bulan" => "SEPTEMBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP BEKASI KOTA", "bulan" => "SEPTEMBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP MEDAN SATRIA", "bulan" => "SEPTEMBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP BABELAN", "bulan" => "SEPTEMBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP BANTARGEBANG", "bulan" => "SEPTEMBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP MUSTIKAJAYA", "bulan" => "SEPTEMBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP PRIMA BEKASI", "bulan" => "SEPTEMBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "UP3 BEKASI", "bulan" => "OKTOBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP BEKASI KOTA", "bulan" => "OKTOBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP MEDAN SATRIA", "bulan" => "OKTOBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP BABELAN", "bulan" => "OKTOBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP BANTARGEBANG", "bulan" => "OKTOBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP MUSTIKAJAYA", "bulan" => "OKTOBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP PRIMA BEKASI", "bulan" => "OKTOBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "UP3 BEKASI", "bulan" => "NOVEMBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP BEKASI KOTA", "bulan" => "NOVEMBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP MEDAN SATRIA", "bulan" => "NOVEMBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP BABELAN", "bulan" => "NOVEMBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP BANTARGEBANG", "bulan" => "NOVEMBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP MUSTIKAJAYA", "bulan" => "NOVEMBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP PRIMA BEKASI", "bulan" => "NOVEMBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "UP3 BEKASI", "bulan" => "DESEMBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP BEKASI KOTA", "bulan" => "DESEMBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP MEDAN SATRIA", "bulan" => "DESEMBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP BABELAN", "bulan" => "DESEMBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP BANTARGEBANG", "bulan" => "DESEMBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP MUSTIKAJAYA", "bulan" => "DESEMBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00),
        array("unit" => "ULP PRIMA BEKASI", "bulan" => "DESEMBER", "target" => 100.00, "realisasi" => 0.00, "pencapaian" => 0.00)
    );

    // Konversi data ke format JSON untuk digunakan di JavaScript
    $rawDataJson = json_encode($rawData);
    ?>
    <!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Realisasi Target 2025</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #1a73e8, #0d47a1);
            color: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
        }
        
        .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .filters {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            min-width: 250px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1a73e8;
        }
        
        .filter-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f8f9fa;
            font-size: 1rem;
            cursor: pointer;
        }
        
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(600px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 20px;
            height: 400px;
            display: flex;
            flex-direction: column;
        }
        
        .chart-title {
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: #1a73e8;
            text-align: center;
            font-weight: 600;
        }
        
        .chart-wrapper {
            flex: 1;
            position: relative;
        }
        
        .data-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 20px;
            text-align: center;
        }
        
        .summary-title {
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: #5f6368;
        }
        
        .summary-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1a73e8;
        }
        
        .summary-subtitle {
            margin-top: 5px;
            color: #70757a;
            font-size: 0.9rem;
        }
        
        footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            color: #5f6368;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
            
            .chart-card {
                height: 350px;
            }
            
            .filters {
                flex-direction: column;
                align-items: center;
            }
            
            .filter-group {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>LAPORAN REALISASI TARGET 2025</h1>
            <div class="subtitle">Dashboard Monitoring Kinerja Unit Pelayanan</div>
        </header>
        
        <div class="filters">
            <div class="filter-group">
                <label for="unitFilter">Pilih Unit:</label>
                <select id="unitFilter">
                    <option value="all">Semua Unit</option>
                    <option value="UP3 BEKASI">UP3 BEKASI</option>
                    <option value="ULP BEKASI KOTA">ULP BEKASI KOTA</option>
                    <option value="ULP MEDAN SATRIA">ULP MEDAN SATRIA</option>
                    <option value="ULP BABELAN">ULP BABELAN</option>
                    <option value="ULP BANTARGEBANG">ULP BANTARGEBANG</option>
                    <option value="ULP MUSTIKAJAYA">ULP MUSTIKAJAYA</option>
                    <option value="ULP PRIMA BEKASI">ULP PRIMA BEKASI</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="monthFilter">Pilih Bulan:</label>
                <select id="monthFilter">
                    <option value="all">Semua Bulan</option>
                    <option value="JANUARI">Januari</option>
                    <option value="FEBRUARI">Februari</option>
                    <option value="MARET">Maret</option>
                    <option value="APRIL">April</option>
                    <option value="MEI">Mei</option>
                    <option value="JUNI">Juni</option>
                    <option value="JULI">Juli</option>
                    <option value="AGUSTUS">Agustus</option>
                    <option value="SEPTEMBER">September</option>
                    <option value="OKTOBER">Oktober</option>
                    <option value="NOVEMBER">November</option>
                    <option value="DESEMBER">Desember</option>
                </select>
            </div>
        </div>
        
        <div class="data-summary">
            <div class="summary-card">
                <div class="summary-title">Rata-rata Pencapaian</div>
                <div class="summary-value" id="avgAchievement">92.45%</div>
                <div class="summary-subtitle">Seluruh Unit & Bulan</div>
            </div>
            
            <div class="summary-card">
                <div class="summary-title">Unit Terbaik</div>
                <div class="summary-value" id="bestUnit">ULP BEKASI KOTA</div>
                <div class="summary-subtitle">Pencapaian 100%</div>
            </div>
            
            <div class="summary-card">
                <div class="summary-title">Bulan Terbaik</div>
                <div class="summary-value" id="bestMonth">Juni</div>
                <div class="summary-subtitle">Pencapaian 100%</div>
            </div>
        </div>
        
        <div class="charts-container">
            <div class="chart-card">
                <div class="chart-title">Perbandingan Target vs Realisasi</div>
                <div class="chart-wrapper">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-title">Tren Pencapaian Tahunan</div>
                <div class="chart-wrapper">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-title">Distribusi Pencapaian per Unit</div>
                <div class="chart-wrapper">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
        </div>
        
        <footer>
            <p>© 2025 - Sistem Pelaporan Kinerja | Data diperbarui terakhir: <?php echo date('d F Y'); ?></p>
        </footer>
    </div>

    <script>
        // Data dari PHP
        const rawData = <?php echo $rawDataJson; ?>;
        
        // Inisialisasi chart
        let barChart, lineChart, pieChart;

        // Fungsi untuk memfilter data berdasarkan unit dan bulan
        function filterData() {
            const unitFilter = document.getElementById('unitFilter').value;
            const monthFilter = document.getElementById('monthFilter').value;
            
            return rawData.filter(item => {
                const unitMatch = unitFilter === 'all' || item.unit === unitFilter;
                const monthMatch = monthFilter === 'all' || item.bulan === monthFilter;
                return unitMatch && monthMatch;
            });
        }

        // Fungsi untuk mengupdate chart
        function updateCharts() {
            const filteredData = filterData();
            
            // Update bar chart
            const barCtx = document.getElementById('barChart').getContext('2d');
            if (barChart) barChart.destroy();
            
            const units = [...new Set(filteredData.map(item => item.unit))];
            const months = [...new Set(filteredData.map(item => item.bulan))];
            
            // Siapkan data untuk bar chart
            let labels, targetData, realisasiData;
            
            if (document.getElementById('unitFilter').value !== 'all') {
                // Jika unit dipilih, tampilkan per bulan
                labels = months;
                targetData = months.map(month => {
                    const data = filteredData.find(d => d.bulan === month);
                    return data ? data.target : 0;
                });
                realisasiData = months.map(month => {
                    const data = filteredData.find(d => d.bulan === month);
                    return data ? data.realisasi : 0;
                });
            } else {
                // Jika semua unit, tampilkan per unit
                labels = units;
                targetData = units.map(unit => {
                    const data = filteredData.find(d => d.unit === unit);
                    return data ? data.target : 0;
                });
                realisasiData = units.map(unit => {
                    const data = filteredData.find(d => d.unit === unit);
                    return data ? data.realisasi : 0;
                });
            }
            
            barChart = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Target',
                            data: targetData,
                            backgroundColor: 'rgba(54, 162, 235, 0.7)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Realisasi',
                            data: realisasiData,
                            backgroundColor: 'rgba(75, 192, 192, 0.7)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Persentase (%)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: false
                        }
                    }
                }
            });
            
            // Update line chart
            const lineCtx = document.getElementById('lineChart').getContext('2d');
            if (lineChart) lineChart.destroy();
            
            // Urutan bulan yang benar
            const monthOrder = [
                'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI',
                'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'
            ];
            
            // Data tren tahunan
            const pencapaianPerBulan = monthOrder.map(month => {
                const monthData = filteredData.filter(d => d.bulan === month);
                if (monthData.length === 0) return 0;
                const total = monthData.reduce((sum, item) => sum + item.pencapaian, 0);
                return total / monthData.length;
            });
            
            lineChart = new Chart(lineCtx, {
                type: 'line',
                data: {
                    labels: monthOrder.map(month => month.charAt(0) + month.slice(1).toLowerCase()),
                    datasets: [{
                        label: 'Rata-rata Pencapaian',
                        data: pencapaianPerBulan,
                        fill: false,
                        borderColor: 'rgb(255, 99, 132)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Persentase (%)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });
            
            // Update pie chart
            const pieCtx = document.getElementById('pieChart').getContext('2d');
            if (pieChart) pieChart.destroy();
            
            // Data untuk pie chart (distribusi per unit)
            const pencapaianPerUnit = units.map(unit => {
                const unitData = filteredData.filter(d => d.unit === unit);
                if (unitData.length === 0) return 0;
                const total = unitData.reduce((sum, item) => sum + item.pencapaian, 0);
                return total / unitData.length;
            });
            
            // Warna untuk pie chart
            const backgroundColors = [
                'rgba(255, 99, 132, 0.7)',
                'rgba(54, 162, 235, 0.7)',
                'rgba(255, 206, 86, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(153, 102, 255, 0.7)',
                'rgba(255, 159, 64, 0.7)',
                'rgba(199, 199, 199, 0.7)'
            ];
            
            pieChart = new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: units,
                    datasets: [{
                        data: pencapaianPerUnit,
                        backgroundColor: backgroundColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.label}: ${context.raw.toFixed(2)}%`;
                                }
                            }
                        }
                    }
                }
            });
            
            // Update summary cards
            const avgAchievement = filteredData.reduce((sum, item) => sum + item.pencapaian, 0) / filteredData.length;
            document.getElementById('avgAchievement').textContent = avgAchievement.toFixed(2) + '%';
            
            // Cari unit dengan pencapaian tertinggi
            let bestUnit = { unit: '', pencapaian: 0 };
            units.forEach(unit => {
                const unitData = filteredData.filter(d => d.unit === unit);
                const avg = unitData.reduce((sum, item) => sum + item.pencapaian, 0) / unitData.length;
                if (avg > bestUnit.pencapaian) {
                    bestUnit = { unit, pencapaian: avg };
                }
            });
            document.getElementById('bestUnit').textContent = bestUnit.unit || '-';
            
            // Cari bulan dengan pencapaian tertinggi
            let bestMonth = { month: '', pencapaian: 0 };
            monthOrder.forEach(month => {
                const monthData = filteredData.filter(d => d.bulan === month);
                if (monthData.length === 0) return;
                const avg = monthData.reduce((sum, item) => sum + item.pencapaian, 0) / monthData.length;
                if (avg > bestMonth.pencapaian) {
                    bestMonth = { month, pencapaian: avg };
                }
            });
            document.getElementById('bestMonth').textContent = bestMonth.month ? 
                bestMonth.month.charAt(0) + bestMonth.month.slice(1).toLowerCase() : '-';
        }

        // Inisialisasi saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            updateCharts();
            
            // Tambahkan event listener untuk dropdown
            document.getElementById('unitFilter').addEventListener('change', updateCharts);
            document.getElementById('monthFilter').addEventListener('change', updateCharts);
        });
    </script>
</body>
</html>