<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Koneksi database
$conn = new mysqli("localhost", "root", "", "pln_dashboard");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil data history
$sql = "SELECT * FROM history_update_kpi_pi ORDER BY tanggal_update DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Riwayat Update KPI & PI - PLN Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        /* Variabel warna PLN */
        :root {
            --pln-blue: #004aad;
            --pln-blue-dark: #003a80;
            --pln-yellow: #ffcb05;
            --pln-gray-light: #f5f8fa;
            --pln-gray-dark: #495057;
            --pln-error: #dc3545;
            --pln-success: #28a745;
            --pln-shadow: rgba(0, 74, 173, 0.15);
        }

        /* Reset dan dasar */
        * {
            box-sizing: border-box;
        }

        body {
            background: var(--pln-gray-light);
            font-family: 'Poppins', Arial, sans-serif;
            margin: 0;
            padding: 20px 15px;
            color: var(--pln-gray-dark);
            min-height: 100vh;
        }

        h2 {
            text-align: center;
            color: var(--pln-blue);
            margin-bottom: 25px;
            font-weight: 700;
            font-size: 1.9rem;
            letter-spacing: 1px;
        }

        /* Tombol kembali */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            background: var(--pln-blue);
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.3s ease;
            box-shadow: 0 4px 6px var(--pln-shadow);
            margin-bottom: 20px;
        }
        .back-btn i {
            font-size: 1.1rem;
        }
        .back-btn:hover {
            background: var(--pln-blue-dark);
        }

        /* Tabel */
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 6px 18px var(--pln-shadow);
        }

        thead {
            background-color: var(--pln-blue);
            color: white;
            font-weight: 600;
        }

        th, td {
            text-align: center;
            padding: 14px 12px;
            border-bottom: 1px solid #dee2e6;
            font-size: 0.95rem;
            vertical-align: middle;
        }

        th:first-child, td:first-child {
            width: 50px;
        }
        th:nth-child(2), td:nth-child(2),
        th:nth-child(3), td:nth-child(3) {
            width: 80px;
        }
        th:nth-child(8), td:nth-child(8) {
            width: 140px;
        }
        th:nth-child(9), td:nth-child(9) {
            width: 160px;
        }

        tbody tr:hover {
            background-color: var(--pln-yellow);
            color: var(--pln-blue-dark);
            font-weight: 600;
            cursor: default;
            transition: background-color 0.3s ease;
        }

        tbody tr:nth-child(even) {
            background-color: #f9fbff;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 15px 10px;
            }
            h2 {
                font-size: 1.5rem;
                margin-bottom: 18px;
            }
            table {
                font-size: 0.85rem;
            }
            th, td {
                padding: 10px 8px;
            }
        }

        @media (max-width: 480px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            tr {
                border: 1px solid #ccc;
                margin-bottom: 15px;
                border-radius: 10px;
                background: white;
                box-shadow: 0 3px 8px var(--pln-shadow);
            }
            td {
                border: none;
                border-bottom: 1px solid #eee;
                position: relative;
                padding-left: 50%;
                text-align: left;
                font-size: 0.9rem;
            }
            td:before {
                position: absolute;
                top: 12px;
                left: 15px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                font-weight: 700;
                color: var(--pln-blue);
                content: attr(data-label);
            }
            td:last-child {
                border-bottom: 0;
            }
        }
    </style>
</head>
<body>

<a href="dashboard.php" class="back-btn" title="Kembali ke Dashboard">
    <i class="fa fa-arrow-left"></i> Kembali ke Dashboard
</a>

<h2>Riwayat Update KPI & PI</h2>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Tahun</th>
            <th>Bulan</th>
            <th>Indikator</th>
            <th>Unit</th>
            <th>Bobot</th>
            <th>Skor</th>
            <th>Diupdate Oleh</th>
            <th>Tanggal Update</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td data-label="ID"><?= htmlspecialchars($row['id']) ?></td>
                    <td data-label="Tahun"><?= htmlspecialchars($row['tahun']) ?></td>
                    <td data-label="Bulan"><?= htmlspecialchars($row['bulan']) ?></td>
                    <td data-label="Indikator"><?= htmlspecialchars($row['indikator']) ?></td>
                    <td data-label="Unit"><?= htmlspecialchars($row['unit']) ?></td>
                    <td data-label="Bobot"><?= htmlspecialchars($row['bobot']) ?></td>
                    <td data-label="Skor"><?= htmlspecialchars($row['skor']) ?></td>
                    <td data-label="Diupdate Oleh"><?= htmlspecialchars($row['diupdate_oleh']) ?></td>
                    <td data-label="Tanggal Update"><?= htmlspecialchars($row['tanggal_update']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="9" style="font-style: italic; color: #666;">Belum ada data history.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
<?php
$conn->close();
?>
