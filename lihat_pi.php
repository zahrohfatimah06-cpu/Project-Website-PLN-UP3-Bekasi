<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<title>Performance Indicators - PLN</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>
    :root {
        --primary: #0A4C95;
        --primary-light: #3a6da7;
        --primary-dark: #073b76;
        --secondary: #FFA500;
        --accent: #FFD700;
        --success: #28a745;
        --danger: #dc3545;
        --light: #f8f9fa;
        --dark: #2c3e50;
        --gray: #6c757d;
        --light-gray: #e9ecef;
        --bg-color: #f5f9ff;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--bg-color);
        color: var(--dark);
        line-height: 1.6;
    }

    /* Navbar */
    .navbar {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        padding: 1rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .navbar-brand {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .navbar-brand i {
        font-size: 1.8rem;
        color: var(--accent);
    }

    .navbar-brand h2 {
        margin: 0;
        font-weight: 700;
        font-size: 1.5rem;
    }

    .user-actions {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .user-actions a {
        color: white;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 0.5rem 1rem;
        border-radius: 8px;
    }

    .user-actions a:hover {
        color: var(--accent);
        background-color: rgba(255, 255, 255, 0.1);
    }

    /* Main Content */
    .container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1.5rem;
    }

    .page-header {
        text-align: center;
        margin-bottom: 2.5rem;
    }

    .page-title {
        font-size: 2rem;
        color: var(--primary);
        margin-bottom: 0.5rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }

    .page-subtitle {
        color: var(--gray);
        font-size: 1rem;
        max-width: 700px;
        margin: 0 auto;
    }

    /* PI Sections */
    .pi-section {
        margin-bottom: 2.5rem;
        background: white;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
        padding: 1.5rem;
        transition: transform 0.3s ease;
    }

    .pi-section:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .pi-section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid var(--light-gray);
    }

    .pi-section-title {
        font-size: 1.4rem;
        font-weight: 600;
        color: var(--primary);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .pi-section-title i {
        color: var(--secondary);
    }

    .cards-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.25rem;
    }

    /* PI Cards */
    .pi-card {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border-left: 4px solid var(--primary);
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .pi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        border-left-color: var(--secondary);
    }

    .pi-card-icon {
        font-size: 2rem;
        color: var(--primary);
        margin-bottom: 1rem;
    }

    .pi-card-title {
        font-weight: 600;
        margin-bottom: 1rem;
        color: var(--dark);
        font-size: 1.1rem;
        flex-grow: 1;
    }

    .pi-card-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background-color: var(--primary);
        color: white;
        padding: 0.6rem 1rem;
        border-radius: 8px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.3s ease;
        margin-top: auto;
    }

    .pi-card-btn:hover {
        background-color: var(--primary-dark);
        transform: translateY(-2px);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .navbar {
            flex-direction: column;
            padding: 1rem;
            gap: 1rem;
        }
        
        .user-actions {
            width: 100%;
            justify-content: space-between;
        }
        
        .container {
            padding: 0 1rem;
        }
        
        .cards-container {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 480px) {
        .user-actions {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .pi-section-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .page-title {
            font-size: 1.5rem;
        }
    }
</style>
</head>
<body>

<div class="navbar">
    <div class="navbar-brand">
        <i class="fas fa-chart-line"></i>
        <h2>PLN Performance Indicators</h2>
    </div>
    <div class="user-actions">
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="container">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-tachometer-alt"></i> Performance Indicators</h1>
        <p class="page-subtitle">Monitor dan kelola indikator kinerja utama PLN secara real-time</p>
    </div>

    <!-- Keandalan Jaringan Distribusi -->
    <section class="pi-section">
        <div class="pi-section-header">
            <h3 class="pi-section-title"><i class="fas fa-network-wired"></i> Keandalan Jaringan Distribusi</h3>
        </div>
        <div class="cards-container">
            <div class="pi-card">
                <i class="pi-card-icon fas fa-exclamation-triangle"></i>
                <h4 class="pi-card-title">Gangguan TM > 5 Menit</h4>
                <a href="data_gangguan_tm_lebih_5.php" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
            <div class="pi-card">
                <i class="pi-card-icon fas fa-history"></i>
                <h4 class="pi-card-title">Gangguan TM ≤ 5 Menit</h4>
                <a href="data_gangguan_tm_kurang_5.php" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
            <div class="pi-card">
                <i class="pi-card-icon fas fa-tools"></i>
                <h4 class="pi-card-title">Kerusakan Peralatan Distribusi (PHB TM dan Trafo) Sesuai Kewenangan</h4>
                <a href="data_kerusakan_peralatan.php" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
        </div>
    </section>

    <!-- Peningkatan Layanan PLN Mobile -->
    <section class="pi-section">
        <div class="pi-section-header">
            <h3 class="pi-section-title"><i class="fas fa-mobile-alt"></i> Peningkatan Layanan PLN Mobile</h3>
        </div>
        <div class="cards-container">
            <div class="pi-card">
                <i class="pi-card-icon fas fa-thumbs-down"></i>
                <h4 class="pi-card-title">Feedback Rating Negatif pada PLN Mobile - Gangguan</h4>
                <a href="feedback_rating_negatif.php" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
            <div class="pi-card">
                <i class="pi-card-icon fas fa-redo-alt"></i>
                <h4 class="pi-card-title">Pengaduan Gangguan Berulang pada PLN Mobile</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
            <div class="pi-card">
                <i class="pi-card-icon fas fa-star-half-alt"></i>
                <h4 class="pi-card-title">Rating PLN Mobile</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
            <div class="pi-card">
                <i class="pi-card-icon fas fa-credit-card"></i>
                <h4 class="pi-card-title">Jumlah Kali Transaksi Keuangan melalui PLN Mobile</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
        </div>
    </section>

    <!-- Dispatch Respon Time -->
    <section class="pi-section">
        <div class="pi-section-header">
            <h3 class="pi-section-title"><i class="fas fa-stopwatch"></i> Dispatch Response Time</h3>
        </div>
        <div class="cards-container">
            <div class="pi-card">
                <i class="pi-card-icon fas fa-stopwatch"></i>
                <h4 class="pi-card-title">Response Time atas Gangguan (diluar clear temper)</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
        </div>
    </section>

    <!-- Sistem Recovery -->
    <section class="pi-section">
        <div class="pi-section-header">
            <h3 class="pi-section-title"><i class="fas fa-history"></i> Sistem Recovery</h3>
        </div>
        <div class="cards-container">
            <div class="pi-card">
                <i class="pi-card-icon fas fa-history"></i>
                <h4 class="pi-card-title">Recovery Time Gangguan Meluas TM (MV Outage Duration)</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
            <div class="pi-card">
                <i class="pi-card-icon fas fa-sync-alt"></i>
                <h4 class="pi-card-title">Penormalan Siaga 1 Gangguan TM</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
        </div>
    </section>

    <!-- Digitalisasi Aplikasi Korporat -->
    <section class="pi-section">
        <div class="pi-section-header">
            <h3 class="pi-section-title"><i class="fas fa-laptop-code"></i> Digitalisasi Aplikasi Korporat</h3>
        </div>
        <div class="cards-container">
            <div class="pi-card">
                <i class="pi-card-icon fas fa-laptop-code"></i>
                <h4 class="pi-card-title">Pengembangan EAM Distribusi</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
            <div class="pi-card">
                <i class="pi-card-icon fas fa-lightbulb"></i>
                <h4 class="pi-card-title">Value Creation AMI</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
        </div>
    </section>

    <!-- Percepatan Penyambungan Pelanggan -->
    <section class="pi-section">
        <div class="pi-section-header">
            <h3 class="pi-section-title"><i class="fas fa-plug"></i> Percepatan Penyambungan Pelanggan</h3>
        </div>
        <div class="cards-container">
            <div class="pi-card">
                <i class="pi-card-icon fas fa-plug"></i>
                <h4 class="pi-card-title">Penambahan Daya Tersambung</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
            <div class="pi-card">
                <i class="pi-card-icon fas fa-user-plus"></i>
                <h4 class="pi-card-title">Penambahan Jumlah Pelanggan</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
            <div class="pi-card">
                <i class="pi-card-icon fas fa-users"></i>
                <h4 class="pi-card-title">Jumlah Penambahan Pelanggan Produk Tematik</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
            <div class="pi-card">
                <i class="pi-card-icon fas fa-briefcase"></i>
                <h4 class="pi-card-title">Implementasi Kesesuaian Sektor Bisnis sesuai KBLI 2020</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
            <div class="pi-card">
                <i class="pi-card-icon fas fa-home"></i>
                <h4 class="pi-card-title">Penambahan Jumlah Pelanggan Rumah Tangga Lisdes</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
            <div class="pi-card">
                <i class="pi-card-icon fas fa-chart-line"></i>
                <h4 class="pi-card-title">Peningkatan kWh Penjualan dari Pelanggan Rumah Tangga Lisdes</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
        </div>
    </section>

    <!-- Pengelolaan Pembacaan Meter -->
    <section class="pi-section">
        <div class="pi-section-header">
            <h3 class="pi-section-title"><i class="fas fa-tachometer-alt"></i> Pengelolaan Pembacaan Meter</h3>
        </div>
        <div class="cards-container">
            <div class="pi-card">
                <i class="pi-card-icon fas fa-mobile-alt"></i>
                <h4 class="pi-card-title">Jumlah Pengguna Swacam</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
            <div class="pi-card">
                <i class="pi-card-icon fas fa-file-alt"></i>
                <h4 class="pi-card-title">Tindaklanjut LBKB (Laporan Bulanan Kelainan Baca Meter)</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
        </div>
    </section>

    <!-- Percepatan Cash In -->
    <section class="pi-section">
        <div class="pi-section-header">
            <h3 class="pi-section-title"><i class="fas fa-money-bill-wave"></i> Percepatan Cash In</h3>
        </div>
        <div class="cards-container">
            <div class="pi-card">
                <i class="pi-card-icon fas fa-coins"></i>
                <h4 class="pi-card-title">Pencapaian Rata-Rata Saldo Tanggal 20 diluar Konsumen Instansi (Kementerian & Lembaga)</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
            <div class="pi-card">
                <i class="pi-card-icon fas fa-wallet"></i>
                <h4 class="pi-card-title">Pencapaian Saldo Rata-Rata Akhir Bulan diluar Konsumen Instansi (Kementerian & Lembaga)</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
            <div class="pi-card">
                <i class="pi-card-icon fas fa-check-circle"></i>
                <h4 class="pi-card-title">Pencapaian Pelunasan PRR</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
        </div>
    </section>

    <!-- Pengendalian Anggaran -->
    <section class="pi-section">
        <div class="pi-section-header">
            <h3 class="pi-section-title"><i class="fas fa-chart-pie"></i> Pengendalian Anggaran</h3>
        </div>
        <div class="cards-container">
            <div class="pi-card">
                <i class="pi-card-icon fas fa-chart-pie"></i>
                <h4 class="pi-card-title">Pengendalian Penggunaan Anggaran Investasi sesuai RKAP</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
            <div class="pi-card">
                <i class="pi-card-icon fas fa-trash-alt"></i>
                <h4 class="pi-card-title">Usulan Penghapusan ATTB</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
            <div class="pi-card">
                <i class="pi-card-icon fas fa-trash"></i>
                <h4 class="pi-card-title">Usulan Penghapusan PRR</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
        </div>
    </section>

    <!-- Manajemen SDM, Umum, Komunikasi dan TJSL -->
    <section class="pi-section">
        <div class="pi-section-header">
            <h3 class="pi-section-title"><i class="fas fa-users-cog"></i> Manajemen SDM, Umum, Komunikasi dan TJSL</h3>
        </div>
        <div class="cards-container">
            <div class="pi-card">
                <i class="pi-card-icon fas fa-users-cog"></i>
                <h4 class="pi-card-title">HCR, OCR dan Produktivitas Pegawai</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
            <div class="pi-card">
                <i class="pi-card-icon fas fa-industry"></i>
                <h4 class="pi-card-title">Produktivitas Unit</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
            <div class="pi-card">
                <i class="pi-card-icon fas fa-comments"></i>
                <h4 class="pi-card-title">Pengelolaan Komunikasi dan TJSL</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
            <div class="pi-card">
                <i class="pi-card-icon fas fa-hard-hat"></i>
                <h4 class="pi-card-title">Peningkatan Layanan dan Budaya K3</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
        </div>
    </section>

    <!-- Perbaikan Proses Bisnis dan Keberlanjutan -->
    <section class="pi-section">
        <div class="pi-section-header">
            <h3 class="pi-section-title"><i class="fas fa-project-diagram"></i> Perbaikan Proses Bisnis dan Keberlanjutan</h3>
        </div>
        <div class="cards-container">
            <div class="pi-card">
                <i class="pi-card-icon fas fa-building"></i>
                <h4 class="pi-card-title">Implementasi PLN Bisnis Ekselen</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
            <div class="pi-card">
                <i class="pi-card-icon fas fa-seedling"></i>
                <h4 class="pi-card-title">Maturity Level Sustainability</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
            <div class="pi-card">
                <i class="pi-card-icon fas fa-warehouse"></i>
                <h4 class="pi-card-title">Maturity Level Gudang</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
            <div class="pi-card">
                <i class="pi-card-icon fas fa-project-diagram"></i>
                <h4 class="pi-card-title">Implementasi Roadmap Perbaikan Penerapan Manajemen Risiko dan Pencapaian Perlakuan Risiko</h4>
                <a href="#" class="pi-card-btn"><i class="fas fa-eye"></i> Lihat Data</a>
            </div>
        </div>
    </section>

</div>

</body>
</html>
