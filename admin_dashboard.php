<?php
/**
 * Admin Dashboard (Legacy Standalone)
 */
session_start();
require_once 'config.php';
require_once 'db_connect_pdo.php';

// Redirect if not admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Fetch Stats
try {
    // Total Patients
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'patient'");
    $stmt->execute();
    $total_patients = $stmt->fetchColumn();

    // Total Hospitals
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'hospital' AND status = 'approved'");
    $stmt->execute();
    $total_hospitals = $stmt->fetchColumn();

    // Pending Hospitals
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'hospital' AND status = 'pending' ORDER BY created_at ASC");
    $stmt->execute();
    $pending_hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $pending_hospitals_count = count($pending_hospitals);

    // Appointment Stats
    $stmt = $pdo->query("SELECT COUNT(*) FROM appointments WHERE result = 'positive'");
    $pos = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) FROM appointments WHERE result = 'negative'");
    $neg = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) FROM appointments WHERE result = 'vaccinated'");
    $vac = $stmt->fetchColumn();
    $appt_stats = ['positive' => $pos, 'negative' => $neg, 'vaccinated' => $vac];

    // Real Recent Activity Fetch
    $activities = [];
    
    // New Patients
    $stmt = $pdo->query("SELECT name, created_at FROM users WHERE role = 'patient' ORDER BY created_at DESC LIMIT 3");
    while($row = $stmt->fetch()) {
        $activities[] = ['title' => 'New Patient', 'desc' => $row['name'] . ' registered', 'time' => $row['created_at'], 'icon' => 'fa-user-plus', 'color' => '#8b5cf6'];
    }
    
    // New Hospitals
    $stmt = $pdo->query("SELECT name, created_at FROM users WHERE role = 'hospital' AND status = 'approved' ORDER BY created_at DESC LIMIT 3");
    while($row = $stmt->fetch()) {
        $activities[] = ['title' => 'Hospital Live', 'desc' => $row['name'] . ' joined', 'time' => $row['created_at'], 'icon' => 'fa-hospital', 'color' => '#3b82f6'];
    }

    // New Appointments
    $stmt = $pdo->query("SELECT a.created_at, u.name as patient, h.name as hospital FROM appointments a JOIN users u ON a.patient_id = u.id JOIN users h ON a.hospital_id = h.id ORDER BY a.created_at DESC LIMIT 3");
    while($row = $stmt->fetch()) {
        $activities[] = ['title' => 'New Booking', 'desc' => $row['patient'] . ' &rarr; ' . $row['hospital'], 'time' => $row['created_at'], 'icon' => 'fa-calendar-check', 'color' => '#10b981'];
    }

    usort($activities, function($a, $b) { return strtotime($b['time']) - strtotime($a['time']); });
    $activities = array_slice($activities, 0, 6);

} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | MediCore</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .glass-card { 
            background: rgba(255, 255, 255, 0.03); 
            backdrop-filter: blur(15px); 
            border: 1px solid rgba(255, 255, 255, 0.08); 
            border-radius: 24px; 
            padding: 24px; 
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }
        .main-wrapper { padding-top: 40px; }

        /* Advanced Stat Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-bottom: 50px; }
        
        .stat-card {
            position: relative;
            padding: 30px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 28px;
            overflow: hidden;
            transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at var(--mouse-x, 50%) var(--mouse-y, 50%), rgba(255, 255, 255, 0.08) 0%, transparent 80%);
            opacity: 0;
            transition: opacity 0.5s;
        }

        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .stat-card:hover::before { opacity: 1; }

        .stat-icon-bg {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            position: relative;
            z-index: 2;
            transition: all 0.5s;
        }

        .stat-card:hover .stat-icon-bg { transform: rotate(-10deg) scale(1.1); }

        .stat-content { position: relative; z-index: 2; }
        
        .stat-label { 
            display: block;
            font-size: 0.85rem; 
            font-weight: 600; 
            text-transform: uppercase; 
            letter-spacing: 1.5px; 
            color: var(--text-muted);
            margin-bottom: 5px;
        }

        .stat-value { 
            font-size: 2.8rem; 
            font-weight: 800; 
            line-height: 1; 
            margin: 0;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-card small { color: var(--text-muted); font-size: 0.8rem; margin-top: 8px; display: block; opacity: 0.7; }

        /* Specific Glow Colors */
        .card-violet .stat-icon-bg { background: rgba(139, 92, 246, 0.1); color: #a78bfa; box-shadow: 0 0 20px rgba(139, 92, 246, 0.2); }
        .card-violet:hover { box-shadow: 0 20px 40px -10px rgba(139, 92, 246, 0.3); }

        .card-blue .stat-icon-bg { background: rgba(59, 130, 246, 0.1); color: #60a5fa; box-shadow: 0 0 20px rgba(59, 130, 246, 0.2); }
        .card-blue:hover { box-shadow: 0 20px 40px -10px rgba(59, 130, 246, 0.3); }

        .card-orange .stat-icon-bg { background: rgba(249, 115, 22, 0.1); color: #fb923c; box-shadow: 0 0 20px rgba(249, 115, 22, 0.2); }
        .card-orange:hover { box-shadow: 0 20px 40px -10px rgba(249, 115, 22, 0.3); }

        .card-green .stat-icon-bg { background: rgba(16, 185, 129, 0.1); color: #34d399; box-shadow: 0 0 20px rgba(16, 185, 129, 0.2); }
        .card-green:hover { box-shadow: 0 20px 40px -10px rgba(16, 185, 129, 0.3); }

        .stat-card i { position: absolute; right: -15px; bottom: -15px; font-size: 8rem; opacity: 0.03; transform: rotate(-15deg); transition: all 0.5s; }
        .stat-card:hover i { opacity: 0.1; transform: rotate(0deg) scale(1.1); }
        
        .modern-table td, .modern-table th { padding: 15px 20px; }
        .badge { padding: 6px 12px; font-size: 0.75rem; border-radius: 8px; font-weight: 600; text-transform: uppercase; }
        .badge-success { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
        /* Super Advanced Card System */
        .super-glass-card {
            background: rgba(255, 255, 255, 0.015);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 35px;
            padding: 35px;
            position: relative;
            overflow: hidden;
            transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .super-glass-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at center, rgba(139, 92, 246, 0.03) 0%, transparent 50%);
            transition: all 0.5s;
            pointer-events: none;
        }

        .super-glass-card:hover {
            transform: translateY(-10px) scale(1.01);
            background: rgba(255, 255, 255, 0.025);
            border-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 40px 100px -20px rgba(0,0,0,0.6), inset 0 0 20px rgba(255,255,255,0.02);
        }

        .card-header-adv {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            position: relative;
            z-index: 2;
        }

        .card-title-group {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .card-title-group .icon-box {
            width: 45px;
            height: 45px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.04);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--primary);
            border: 1px solid rgba(255, 255, 255, 0.06);
            transition: all 0.4s;
        }

        .super-glass-card:hover .icon-box {
            background: var(--primary);
            color: #000;
            transform: rotate(-5deg) scale(1.1);
        }

        .card-title-group h3 {
            font-size: 1.15rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.5px;
            margin: 0;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Hospital List Refinement */
        .hospital-list-adv {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .hospital-item-adv {
            background: rgba(255, 255, 255, 0.02);
            border-radius: 20px;
            padding: 15px 20px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.03);
            transition: all 0.3s;
        }

        .hospital-item-adv:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateX(5px);
            border-color: rgba(96, 165, 250, 0.2);
        }

        .hospital-info-adv { display: flex; align-items: center; gap: 12px; }
        .hospital-avatar-adv { width: 38px; height: 38px; border-radius: 10px; background: #2563eb; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; }

        /* Status Pills */
        .status-pill {
            padding: 5px 12px;
            border-radius: 100px;
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-pending { background: rgba(251, 191, 36, 0.1); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.2); }

        .glass-nav { position: sticky; top: 0; z-index: 1000; background: #000000; backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255,255,255,0.05); }

        /* Activity Timeline Refinement */
        .activity-feed-adv { position: relative; }
        .activity-item-adv {
            display: flex;
            gap: 18px;
            margin-bottom: 22px;
            position: relative;
        }
        .activity-item-adv::after {
            content: '';
            position: absolute;
            left: 17px;
            top: 35px;
            bottom: -15px;
            width: 1px;
            background: linear-gradient(to bottom, rgba(255,255,255,0.08) 0%, transparent 100%);
        }
        .activity-item-adv:last-child::after { display: none; }

        .activity-dot-adv {
            width: 35px;
            height: 35px;
            border-radius: 10px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            z-index: 2;
        }
        .btn-action-sm { 
            padding: 8px 18px; 
            border-radius: 12px; 
            font-size: 0.75rem; 
            font-weight: 800; 
            text-decoration: none; 
            transition: all 0.3s; 
            color: #ffffff !important; 
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-approve-sm { background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.3); }
        .btn-approve-sm:hover { background: #10b981; box-shadow: 0 0 20px rgba(16, 185, 129, 0.4); transform: translateY(-2px); }
        .btn-reject-sm { background: rgba(244, 63, 94, 0.2); border: 1px solid rgba(244, 63, 94, 0.3); }
        .btn-reject-sm:hover { background: #f43f5e; box-shadow: 0 0 20px rgba(244, 63, 94, 0.4); transform: translateY(-2px); }
    </style>
    <script>
        document.addEventListener('mousemove', e => {
            document.querySelectorAll('.stat-card').forEach(card => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                card.style.setProperty('--mouse-x', `${x}px`);
                card.style.setProperty('--mouse-y', `${y}px`);
            });
        });
    </script>
</head>
<body style="background: var(--background); color: var(--text-light);">

    <nav class="glass-nav" style="padding: 0 40px; display: flex; justify-content: space-between; align-items: center; height: 80px;">
        <div class="nav-left">
            <a href="admin_dashboard.php" class="nav-brand" style="text-decoration: none; color: var(--primary); font-weight: 700; font-size: 1.5rem;">
                Admin Dashboard
            </a>
        </div>
        <div class="nav-links" style="display: flex; gap: 10px; align-items: center;">
            <a href="admin_dashboard.php" class="btn btn-outline active" style="border: none;">Dashboard</a>
            <a href="manage_vaccines.php" class="btn btn-outline" style="border: none;">Vaccines</a>
            <a href="logout.php" class="btn btn-primary" style="background: #ef4444; border: none; color: white;">Logout</a>
        </div>
    </nav>

    <div class="container main-wrapper animate-fade">
        <div class="page-header-simple" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h1>Overview</h1>
                <p style="color: #fff; opacity: 0.8; font-weight: 500; font-size: 0.95rem;">System status and statistics</p>
            </div>
            <div id="clock" style="font-size: 1.5rem; font-weight: 600; color: var(--primary);">00:00:00</div>
        </div>

        <div class="stats-grid">
            <a href="admin_patients.php" style="text-decoration: none; color: inherit;">
                <div class="stat-card card-violet">
                    <div class="stat-icon-bg"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-content">
                        <span class="stat-label">Patients</span>
                        <h2 class="stat-value"><?php echo number_format($total_patients); ?></h2>
                        <small>Total Users</small>
                    </div>
                    <i class="fa-solid fa-users"></i>
                </div>
            </a>

            <a href="admin_hospitals.php" style="text-decoration: none; color: inherit;">
                <div class="stat-card card-blue">
                    <div class="stat-icon-bg"><i class="fa-solid fa-hospital"></i></div>
                    <div class="stat-content">
                        <span class="stat-label">Hospitals</span>
                        <h2 class="stat-value"><?php echo number_format($total_hospitals); ?></h2>
                        <small>Active Hospitals</small>
                    </div>
                    <i class="fa-solid fa-hospital"></i>
                </div>
            </a>

            <a href="admin_requests.php" style="text-decoration: none; color: inherit;">
                <div class="stat-card card-orange">
                    <div class="stat-icon-bg"><i class="fa-solid fa-bell"></i></div>
                    <div class="stat-content">
                        <span class="stat-label">Requests</span>
                        <h2 class="stat-value"><?php echo number_format($pending_hospitals_count); ?></h2>
                        <small>New Requests</small>
                    </div>
                    <i class="fa-solid fa-bell"></i>
                </div>
            </a>

            <a href="admin_system.php" style="text-decoration: none; color: inherit;">
                <div class="stat-card card-green">
                    <div class="stat-icon-bg"><i class="fa-solid fa-server"></i></div>
                    <div class="stat-content">
                        <span class="stat-label">System</span>
                        <h2 class="stat-value">Online</h2>
                        <small>System Online</small>
                    </div>
                    <i class="fa-solid fa-server"></i>
                </div>
            </a>
        </div>

        <div class="dashboard-split" style="display: grid; grid-template-columns: 2.2fr 1.2fr; gap: 30px;">
            <div class="left-col">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 30px;">
                    <!-- Card 1: Covid Stats -->
                    <div class="super-glass-card animate-fade">
                        <div class="card-header-adv">
                            <div class="card-title-group">
                                <div class="icon-box" style="color: #f43f5e;"><i class="fa-solid fa-chart-pie"></i></div>
                                <h3>Overview Stats</h3>
                            </div>
                            <span class="status-pill" style="background: rgba(244, 63, 94, 0.1); color: #f43f5e;">REAL-TIME</span>
                        </div>
                        <div style="height: 240px; position: relative;">
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none;">
                                <div style="font-size: 1.8rem; font-weight: 800; color: #fff;"><?php echo array_sum($appt_stats); ?></div>
                                <div style="font-size: 0.6rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">TOTAL</div>
                            </div>
                            <canvas id="covidChart"></canvas>
                        </div>
                    </div>

                    <!-- Card 2: Trends -->
                    <div class="super-glass-card animate-fade" style="animation-delay: 0.1s;">
                        <div class="card-header-adv">
                            <div class="card-title-group">
                                <div class="icon-box" style="color: #8b5cf6;"><i class="fa-solid fa-bolt-lightning"></i></div>
                                <h3>System Activity</h3>
                            </div>
                            <span class="status-pill" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">OPTIMIZED</span>
                        </div>
                        <div style="height: 240px;">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Hospital Apps -->
                <div class="super-glass-card animate-fade" style="animation-delay: 0.2s;">
                    <div class="card-header-adv">
                        <div class="card-title-group">
                            <div class="icon-box" style="color: #3b82f6;"><i class="fa-solid fa-building-circle-check"></i></div>
                            <h3>Hospital Requests</h3>
                        </div>
                        <div class="status-pill badge-pending"><?php echo $pending_hospitals_count; ?> PENDING</div>
                    </div>
                    
                    <div class="hospital-list-adv">
                        <?php foreach($pending_hospitals as $h): ?>
                        <div class="hospital-item-adv">
                            <div class="hospital-info-adv">
                                <div class="hospital-avatar-adv"><?php echo strtoupper(substr($h['name'], 0, 1)); ?></div>
                                <div>
                                    <div style="font-weight: 700; color: #fff; font-size: 0.95rem;"><?php echo htmlspecialchars($h['name']); ?></div>
                                    <div style="font-size: 0.75rem; color: #94a3b8;"><?php echo htmlspecialchars($h['location']); ?> • <span style="opacity:0.6;"><?php echo htmlspecialchars($h['email']); ?></span></div>
                                </div>
                            </div>
                            <div style="display:flex; gap:10px;">
                                <a href="handle_request.php?action=approve&id=<?php echo $h['id']; ?>" class="btn-action-sm btn-approve-sm" style="padding: 8px 18px;">ACCEPT</a>
                                <a href="handle_request.php?action=reject&id=<?php echo $h['id']; ?>" class="btn-action-sm btn-reject-sm" style="padding: 8px 18px;">REJECT</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if(empty($pending_hospitals)): ?>
                            <div style="text-align:center; padding: 50px; color: #fff;">
                                <i class="fa-solid fa-check-double" style="font-size: 2.5rem; margin-bottom: 15px; opacity: 0.6;"></i>
                                <p style="font-size: 0.9rem; opacity: 0.8;">No pending requests found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Card 4: System Intelligence -->
            <div class="right-col">
                 <div class="super-glass-card animate-fade" style="animation-delay: 0.3s;">
                    <div class="card-header-adv">
                        <div class="card-title-group">
                            <div class="icon-box" style="color: #fbbf24;"><i class="fa-solid fa-shield-heart"></i></div>
                            <h3>Recent Activity</h3>
                        </div>
                        <div class="status-pill" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">SECURE</div>
                    </div>

                    <div class="activity-feed-adv">
                        <?php foreach($activities as $act): ?>
                        <div class="activity-item-adv">
                            <div class="activity-dot-adv" style="color: <?php echo $act['color']; ?>; border-color: <?php echo $act['color']; ?>22;">
                                <i class="fa-solid <?php echo $act['icon']; ?>"></i>
                            </div>
                            <div style="flex: 1;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 4px;">
                                    <span style="font-weight:700; color:#fff; font-size:0.9rem;"><?php echo $act['title']; ?></span>
                                    <span style="font-size:0.7rem; font-weight:700; color:var(--primary); background:rgba(139, 92, 246, 0.1); padding: 2px 8px; border-radius: 6px;">
                                        <?php 
                                            $ago = time() - strtotime($act['time']);
                                            if($ago < 60) echo "NOW";
                                            elseif($ago < 3600) echo round($ago/60) . "M";
                                            elseif($ago < 86400) echo round($ago/3600) . "H";
                                            else echo date('M d', strtotime($act['time']));
                                        ?>
                                    </span>
                                </div>
                                <div style="font-size:0.8rem; color:#94a3b8; line-height:1.4;"><?php echo $act['desc']; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if(empty($activities)): ?>
                            <div style="text-align:center; padding-top: 40px; opacity:0.2;">
                                <i class="fa-solid fa-radar fa-spin-slow" style="font-size: 2rem; margin-bottom: 10px;"></i>
                                <p>No activity yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>

    <script>
        function updateClock() {
            document.getElementById('clock').innerText = new Date().toLocaleTimeString();
        }
        setInterval(updateClock, 1000); updateClock();

        new Chart(document.getElementById('covidChart'), {
            type: 'doughnut',
            data: {
                labels: ['Positive', 'Negative', 'Vaccinated'],
                datasets: [{
                    data: [<?php echo $appt_stats['positive']; ?>, <?php echo $appt_stats['negative']; ?>, <?php echo $appt_stats['vaccinated']; ?>],
                    backgroundColor: ['#f43f5e', '#10b981', '#3b82f6'],
                    hoverOffset: 10,
                    borderWidth: 0
                }]
            },
            options: { 
                maintainAspectRatio: false, 
                cutout: '75%', 
                plugins: { 
                    legend: { 
                        position: 'bottom', 
                        labels: { 
                            color: '#94a3b8', 
                            usePointStyle: true, 
                            padding: 20,
                            font: { size: 11, weight: '600' }
                        } 
                    } 
                } 
            }
        });

        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Traffic',
                    data: [12, 19, 15, 25, 22, 30, 35],
                    borderColor: '#8b5cf6',
                    borderWidth: 3,
                    pointBackgroundColor: '#8b5cf6',
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    tension: 0.4,
                    fill: true,
                    backgroundColor: ctx => {
                        const gradient = ctx.chart.ctx.createLinearGradient(0, 0, 0, 300);
                        gradient.addColorStop(0, 'rgba(139, 92, 246, 0.2)');
                        gradient.addColorStop(1, 'rgba(139, 92, 246, 0)');
                        return gradient;
                    }
                }]
            },
            options: { 
                maintainAspectRatio: false, 
                scales: { 
                    y: { 
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.03)', drawBorder: false },
                        ticks: { color: '#64748b', font: { size: 10 } }
                    }, 
                    x: { 
                        grid: { display: false },
                        ticks: { color: '#64748b', font: { size: 10 } }
                    } 
                }, 
                plugins: { legend: { display: false } },
                interaction: { intersect: false, mode: 'index' }
            }
        });
    </script>
</body>
</html>
