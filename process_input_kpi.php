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

// Fungsi konversi nama bulan ke angka
function monthNameToNumber($monthName) {
    $months = [
        'Januari' => '01',
        'Februari' => '02',
        'Maret' => '03',
        'April' => '04',
        'Mei' => '05',
        'Juni' => '06',
        'Juli' => '07',
        'Agustus' => '08',
        'September' => '09',
        'Oktober' => '10',
        'November' => '11',
        'Desember' => '12',
    ];
    return $months[$monthName] ?? null;
}

$msg = "";
$msg_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bulanNama = trim($_POST['bulan'] ?? '');
    $indikator = trim($_POST['indikator'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $bobot = $_POST['bobot'] ?? null;
    $skor = $_POST['skor'] ?? null;

    if (empty($bulanNama) || empty($indikator) || empty($unit) || $bobot === null || $skor === null) {
        $msg = "Semua field harus diisi.";
        $msg_type = "error";
    } else {
        $bulanNum = monthNameToNumber($bulanNama);
        if (!$bulanNum) {
            $msg = "Bulan tidak valid.";
            $msg_type = "error";
        } else {
            $tahun = date('Y');

            if (!is_numeric($bobot) || $bobot < 0 || $bobot > 100) {
                $msg = "Bobot harus berupa angka antara 0 dan 100.";
                $msg_type = "error";
            } elseif (!is_numeric($skor) || $skor < 0) {
                $msg = "Skor harus berupa angka 0 atau lebih.";
                $msg_type = "error";
            } else {
                // Cek data sudah ada
                $stmtCheck = $conn->prepare("SELECT id FROM kpi_data WHERE tahun = ? AND bulan = ? AND indikator = ? AND unit = ?");
                $stmtCheck->bind_param("ssss", $tahun, $bulanNum, $indikator, $unit);
                $stmtCheck->execute();
                $stmtCheck->store_result();

                if ($stmtCheck->num_rows > 0) {
                    $stmtCheck->bind_result($existingId);
                    $stmtCheck->fetch();
                    $stmtCheck->close();

                    $stmtUpdate = $conn->prepare("UPDATE kpi_data SET bobot = ?, skor = ? WHERE id = ?");
                    $stmtUpdate->bind_param("ddi", $bobot, $skor, $existingId);
                    if ($stmtUpdate->execute()) {
                        $msg = "Data KPI berhasil diperbarui.";
                        $msg_type = "success";
                    } else {
                        $msg = "Gagal memperbarui data KPI.";
                        $msg_type = "error";
                    }
                    $stmtUpdate->close();
                } else {
                    $stmtCheck->close();

                    $stmtInsert = $conn->prepare("INSERT INTO kpi_data (tahun, bulan, indikator, unit, bobot, skor) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmtInsert->bind_param("ssssdd", $tahun, $bulanNum, $indikator, $unit, $bobot, $skor);

                    if ($stmtInsert->execute()) {
                        $msg = "Data KPI berhasil disimpan.";
                        $msg_type = "success";
                    } else {
                        $msg = "Gagal menyimpan data KPI.";
                        $msg_type = "error";
                    }
                    $stmtInsert->close();
                }
            }
        }
    }
} else {
    $msg = "Metode request tidak valid.";
    $msg_type = "error";
}

$conn->close();

$_SESSION['msg'] = $msg;
$_SESSION['msg_type'] = $msg_type;
header("Location: input_kpi.php");
exit();
