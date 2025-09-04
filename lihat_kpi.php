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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat KPI - PLN Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #0A4C95;
            --primary-dark: #073b76;
            --primary-light: #e6f0ff;
            --accent: #FFA500;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --background: #f5f9ff;
            --card-bg: #ffffff;
            --shadow: rgba(0,0,0,0.1);
        }

        *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
        body{background:var(--background);color:var(--text-dark);display:flex;flex-direction:column;min-height:100vh;}

        /* Sidebar */
        .sidebar{width:250px;position:fixed;top:0;left:0;height:100%;background:linear-gradient(to bottom,var(--primary),var(--primary-dark));color:white;transition:all 0.3s;z-index:1000;box-shadow:2px 0 10px var(--shadow);overflow:hidden;}
        .sidebar.collapsed{width:70px;}
        .sidebar-header{padding:20px;display:flex;align-items:center;justify-content:space-between;}
        .logo{width:40px;height:40px;background:white;border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--primary);}
        .sidebar-title{font-weight:700;margin-left:10px;}
        .sidebar.collapsed .sidebar-title{display:none;}

        .sidebar-menu{margin-top:20px;}
        .sidebar-menu a{display:flex;align-items:center;padding:12px 20px;color:white;text-decoration:none;transition:0.3s;}
        .sidebar-menu a:hover,.sidebar-menu a.active{background:rgba(255,255,255,0.15);border-left:4px solid var(--accent);}
        .sidebar-menu i{font-size:18px;margin-right:12px;min-width:20px;text-align:center;}
        .sidebar-menu span{white-space:nowrap;}
        /* Collapse mode: hanya icon */
        .sidebar.collapsed .sidebar-menu span{display:none;}
        .sidebar.collapsed .sidebar-menu a{justify-content:center;}
        .sidebar.collapsed .sidebar-menu i{margin-right:0;}

        /* Header */
        .header{margin-left:250px;transition:margin-left 0.3s;background:var(--primary);color:white;padding:15px 25px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 10px var(--shadow);position:sticky;top:0;z-index:900;}
        .header.collapsed{margin-left:70px;}
        .header-title{font-weight:700;font-size:20px;}

        /* Container */
        .container{flex-grow:1;margin-left:250px;transition:0.3s;padding:30px;}
        .container.collapsed{margin-left:70px;}

        /* Dashboard UI */
        .dashboard-header{text-align:center;margin-bottom:30px;}
        .dashboard-title{font-size:28px;font-weight:800;color:var(--primary);}
        .dashboard-subtitle{color:var(--text-light);margin-top:8px;}
        .kpi-container{display:grid;grid-template-columns:repeat(auto-fit,minmax(350px,1fr));gap:25px;}
        .kpi-card{background:var(--card-bg);border-radius:16px;box-shadow:0 6px 20px var(--shadow);overflow:hidden;transition:0.3s;}
        .kpi-card:hover{transform:translateY(-5px);}
        .kpi-card-header{background:linear-gradient(90deg,var(--primary),var(--primary-dark));color:white;padding:18px;display:flex;align-items:center;gap:15px;}
        .kpi-icon{font-size:24px;background:rgba(255,255,255,0.2);padding:12px;border-radius:10px;}
        .kpi-card-title{font-weight:700;font-size:18px;}
        .kpi-card-subtitle{font-size:13px;opacity:0.9;}
        .kpi-card-body{padding:20px;}
        .kpi-item{background:var(--primary-light);padding:12px 15px;border-radius:10px;margin-bottom:15px;display:flex;justify-content:space-between;align-items:center;cursor:pointer;transition:0.3s;}
        .kpi-item:hover{background:#d9e7ff;}
        .lihat-button{background:var(--primary);color:white;padding:6px 14px;border-radius:6px;font-size:13px;text-decoration:none;transition:0.3s;}
        .lihat-button:hover{background:var(--primary-dark);}
        .kpi-stats{display:flex;justify-content:space-between;font-size:13px;color:var(--text-light);margin-top:10px;}
        .progress-container{background:#eaeaea;border-radius:8px;height:8px;overflow:hidden;margin-top:5px;}
        .progress-bar{height:100%;border-radius:8px;background:var(--primary);width:0;transition:width 1s;}
        .kpi-sub-items{max-height:0;overflow:hidden;transition:0.5s;}
        .kpi-sub-items.active{max-height:400px;}
        .sub-item{background:#f9faff;padding:10px 15px;border-radius:8px;display:flex;justify-content:space-between;align-items:center;margin-top:10px;}
        .sub-item-title{font-size:14px;font-weight:500;color:var(--text-dark);}

        /* Footer */
        .footer{margin-left:250px;transition:0.3s;background:var(--primary-dark);color:white;text-align:center;padding:15px;}
        .footer.collapsed{margin-left:70px;}

        @media(max-width:768px){
            .sidebar{transform:translateX(-100%);}
            .sidebar.active{transform:translateX(0);}
            .header,.container,.footer{margin-left:0!important;}
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div style="display:flex;align-items:center;">
                <div class="logo"><i class="fas fa-bolt"></i></div>
                <span class="sidebar-title">PLN</span>
            </div>
            <button id="toggleSidebar" style="background:none;border:none;color:white;font-size:18px;cursor:pointer;">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a>
            <a href="penjualan_tenaga_listrik.php"><i class="fas fa-bolt"></i><span>Penjualan</span></a>
            <a href="lihat_kpi.php" class="active"><i class="fas fa-tachometer-alt"></i><span>KPI</span></a>
            <a href="lihat_pi.php"><i class="fas fa-chart-pie"></i><span>PI</span></a>
            <a href="manage_users.php"><i class="fas fa-users-cog"></i><span>User</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Keluar</span></a>
        </div>
    </div>

    <!-- Header -->
    <div class="header" id="header">
        <div class="header-title">📊 PLN Performance Dashboard</div>
        <div class="user-info">
            <a href="logout.php" style="color:white;text-decoration:none;">
                <i class="fas fa-user-circle"></i> 
                <?php 
                echo is_array($_SESSION['user']) ? $_SESSION['user']['username'] : $_SESSION['user']; 
                ?>
            </a>
        </div>
    </div>

    <!-- Main -->
    <div class="container" id="mainContainer">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Key Performance Indicators</h1>
            <p class="dashboard-subtitle">Pantau capaian KPI utama secara real-time.</p>
        </div>

        <div class="kpi-container">
            <!-- KPI Card Example -->
            <div class="kpi-card">
                <div class="kpi-card-header">
                    <div class="kpi-icon"><i class="fas fa-chart-line"></i></div>
                    <div>
                        <div class="kpi-card-title">Penjualan Listrik</div>
                        <div class="kpi-card-subtitle">Target: 12.5 TWh | Capaian: 10.2 TWh</div>
                    </div>
                </div>
                <div class="kpi-card-body">
                    <div class="kpi-item">
                        <div>Data Penjualan</div>
                        <a href="penjualan_tenaga_listrik.php" class="lihat-button">Lihat</a>
                    </div>
                    <canvas id="salesChart" height="90"></canvas>
                    <div class="kpi-stats"><span>Progress: 82%</span><span>+8% YoY</span></div>
                    <div class="progress-container"><div class="progress-bar" style="width:82%"></div></div>
                </div>
            </div>

            <!-- KPI Kehandalan -->
            <div class="kpi-card">
                <div class="kpi-card-header">
                    <div class="kpi-icon"><i class="fas fa-network-wired"></i></div>
                    <div>
                        <div class="kpi-card-title">Kehandalan Jaringan</div>
                        <div class="kpi-card-subtitle">SAIDI: 2.8 jam</div>
                    </div>
                </div>
                <div class="kpi-card-body">
                    <div class="kpi-item" onclick="toggleSection('section2')">Metrik Kehandalan <span id="icon-section2">▼</span></div>
                    <div class="kpi-sub-items" id="section2">
                        <div class="sub-item"><span class="sub-item-title">SAIDI</span><a href="saidi.php" class="lihat-button">Lihat</a></div>
                        <div class="sub-item"><span class="sub-item-title">SAIFI</span><a href="saifi.php" class="lihat-button">Lihat</a></div>
                        <div class="sub-item"><span class="sub-item-title">ENS</span><a href="ens.php" class="lihat-button">Lihat</a></div>
                    </div>
                    <div class="kpi-stats"><span>Kehandalan: 89%</span><span>+3% QoQ</span></div>
                    <div class="progress-container"><div class="progress-bar" style="width:89%"></div></div>
                </div>
            </div>

            <!-- KPI Efisiensi -->
            <div class="kpi-card">
                <div class="kpi-card-header">
                    <div class="kpi-icon"><i class="fas fa-cogs"></i></div>
                    <div>
                        <div class="kpi-card-title">Efisiensi Distribusi</div>
                        <div class="kpi-card-subtitle">Susut: 8.2%</div>
                    </div>
                </div>
                <div class="kpi-card-body">
                    <div class="kpi-item" onclick="toggleSection('section3')">Metrik Efisiensi <span id="icon-section3">▼</span></div>
                    <div class="kpi-sub-items" id="section3">
                        <div class="sub-item"><span class="sub-item-title">Susut Distribusi</span><a href="susut_distribusi.php" class="lihat-button">Lihat</a></div>
                        <div class="sub-item"><span class="sub-item-title">Perolehan P2TL</span><a href="perolehan.php" class="lihat-button">Lihat</a></div>
                        <div class="sub-item"><span class="sub-item-title">Penyelesaian P2TL</span><a href="penyelesaian.php" class="lihat-button">Lihat</a></div>
                        <div class="sub-item"><span class="sub-item-title">Ganti Meter</span><a href="ganti_meter.php" class="lihat-button">Lihat</a></div>
                    </div>
                    <div class="kpi-stats"><span>Efisiensi: 94%</span><span>+1.8% MoM</span></div>
                    <div class="progress-container"><div class="progress-bar" style="width:94%"></div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer" id="footer">
        PLN © 2025 - Sistem KPI | Update: <?php echo date('d M Y H:i'); ?>
    </div>

    <script>
    // Sidebar toggle
    document.getElementById("toggleSidebar").addEventListener("click",()=>{
        document.getElementById("sidebar").classList.toggle("collapsed");
        document.getElementById("header").classList.toggle("collapsed");
        document.getElementById("mainContainer").classList.toggle("collapsed");
        document.getElementById("footer").classList.toggle("collapsed");
    });

    function toggleSection(id){
        let el=document.getElementById(id);
        let icon=document.getElementById("icon-"+id);
        el.classList.toggle("active");
        icon.textContent=el.classList.contains("active")?"▲":"▼";
    }

    new Chart(document.getElementById("salesChart"),{
        type:'line',
        data:{
            labels:['Jan','Feb','Mar','Apr','Mei','Jun','Jul'],
            datasets:[{
                label:'Penjualan (TWh)',
                data:[1.2,1.4,1.3,1.5,1.6,1.7,1.8],
                borderColor:'#0A4C95',backgroundColor:'rgba(10,76,149,0.15)',
                borderWidth:2,fill:true,tension:0.3
            }]
        },
        options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}
    });

    document.querySelectorAll(".progress-bar").forEach(bar=>{
        let width=bar.style.width;bar.style.width="0";setTimeout(()=>{bar.style.width=width},300);
    });
    </script>
</body>
</html>
