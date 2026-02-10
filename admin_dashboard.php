<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['approve_hospital'])) {
    $h_id = $_GET['approve_hospital'];
    
    // Fetch hospital email 
    $h_res = $conn->query("SELECT email, name FROM hospitals WHERE id=$h_id");
    if ($h_res->num_rows > 0) {
        $h_data = $h_res->fetch_assoc();
        include_once 'mail_helper.php';
        sendMail($h_data['email'], "Hospital Registration Approved - MediCore", 
            "Dear <strong>{$h_data['name']}</strong>,<br><br>
            Your hospital registration has been approved by the administrator.<br>
            You can now log in to your dashboard using your registered credentials.<br><br>
            <a href='http://localhost/hospital/login.php'>Login Here</a>");
    }
    
    $conn->query("UPDATE hospitals SET status='approved' WHERE id=$h_id");
    header("Location: admin_dashboard.php");
}
if (isset($_GET['reject_hospital'])) {
    $h_id = $_GET['reject_hospital'];
    $conn->query("UPDATE hospitals SET status='rejected' WHERE id=$h_id");
    header("Location: admin_dashboard.php");
}

// Data Aggregation for Charts
$total_patients = $conn->query("SELECT COUNT(*) as c FROM patients")->fetch_assoc()['c'];
$total_hospitals = $conn->query("SELECT COUNT(*) as c FROM hospitals WHERE status='approved'")->fetch_assoc()['c'];
$pending_hospitals = $conn->query("SELECT COUNT(*) as c FROM hospitals WHERE status='pending'")->fetch_assoc()['c'];

// Example Chart Data (Mocking a bit for visual if tables empty)
$c_pos = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE result='positive'")->fetch_assoc()['c'];
$c_neg = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE result='negative'")->fetch_assoc()['c'];
$c_vac = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE type='vaccination' AND status='completed'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Command Center</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Glass Navbar -->
    <nav class="glass-nav">
        <a href="admin_dashboard.php" class="nav-brand">
            <i class="fa-solid fa-heart-pulse text-primary"></i>
            <span>MediCore</span>
        </a>

        <div class="nav-links-container">
            <a href="admin_dashboard.php" class="nav-link active">
                <i class="fa-solid fa-chart-grid"></i> Dashboard
            </a>
            <a href="manage_vaccines.php" class="nav-link">
                <i class="fa-solid fa-syringe"></i> Vaccines
            </a>
            <a href="logout.php" class="nav-link" style="color: #ef4444;">
                <i class="fa-solid fa-power-off"></i> Logout
            </a>
        </div>
    </nav>

    <div class="main-wrapper animate-fade">
        <div class="page-header-simple">
            <div>
                <h1>Dashboard Overview</h1>
                <p class="text-muted"><?php echo date('l, F j, Y'); ?></p>
            </div>
            <div class="time-display" id="clock">00:00:00</div>
        </div>

        <!-- content-wrapper no longer needed as main wrapper handles it, simplifying -->
        <div class="stats-grid">
            <!-- Total Patients (CLICKABLE) -->
                    <a href="admin_patients.php" class="stat-card card-violet" style="text-decoration: none; color: inherit; cursor: pointer; display: block;">
                        <div class="stat-icon-bg"><i class="fa-solid fa-users"></i></div>
                        <div class="stat-content">
                            <div class="flex-between">
                                <span class="stat-label">Patients</span>
                                <span class="stat-trend positive"><i class="fa-solid fa-arrow-trend-up"></i> +12%</span>
                            </div>
                            <h2 class="stat-value"><?php echo number_format($total_patients); ?></h2>
                            
                            <div class="progress-info">
                                <div class="progress-bar-bg">
                                    <div class="progress-bar-fill" style="width: 75%; background: var(--primary);"></div>
                                </div>
                                <small class="text-muted">View all patients details</small>
                            </div>
                        </div>
                        
                        <div class="scurve-bg"></div>
                    </a>

                    <!-- Total Hospitals -->
                    <a href="admin_hospitals.php" class="stat-card card-blue" style="text-decoration: none; color: inherit; cursor: pointer; display: block;">
                        <div class="stat-icon-bg"><i class="fa-solid fa-hospital"></i></div>
                        <div class="stat-content">
                             <div class="flex-between">
                                <span class="stat-label">Hospitals</span>
                                <span class="stat-trend neutral"><i class="fa-solid fa-minus"></i> 0%</span>
                            </div>
                            <h2 class="stat-value"><?php echo number_format($total_hospitals); ?></h2>
                            
                            <div class="progress-info">
                                <div class="progress-bar-bg">
                                    <div class="progress-bar-fill" style="width: 40%; background: #3b82f6;"></div>
                                </div>
                                <small class="text-muted">40% capacity used</small>
                            </div>
                        </div>
                        <div class="scurve-bg"></div>
                    </a>

                    <!-- Pending Requests -->
                    <a href="admin_requests.php" class="stat-card card-orange" style="text-decoration: none; color: inherit; cursor: pointer; display: block;">
                        <div class="stat-icon-bg"><i class="fa-solid fa-bell"></i></div>
                        <div class="stat-content">
                             <div class="flex-between">
                                <span class="stat-label">Requests</span>
                                <span class="stat-trend warning"><i class="fa-solid fa-circle-exclamation"></i> Action</span>
                            </div>
                            <h2 class="stat-value"><?php echo number_format($pending_hospitals); ?></h2>
                            
                            <div class="progress-info">
                                <div class="progress-bar-bg">
                                    <div class="progress-bar-fill" style="width: <?php echo min($pending_hospitals * 10, 100); ?>%; background: #f97316;"></div>
                                </div>
                                <small class="text-muted"><?php echo $pending_hospitals; ?> pending tasks</small>
                            </div>
                        </div>
                        <div class="scurve-bg"></div>
                    </a>

                    <!-- System Status -->
                    <a href="system_health.php" class="stat-card card-green" style="text-decoration: none; color: inherit; cursor: pointer; display: block;">
                        <div class="stat-icon-bg"><i class="fa-solid fa-server"></i></div>
                        <div class="stat-content">
                             <div class="flex-between">
                                <span class="stat-label">System</span>
                                <span class="stat-trend positive">Stable</span>
                            </div>
                            <h2 class="stat-value" style="font-size: 1.8rem;">Online</h2>
                            
                            <div class="progress-info">
                                <div class="progress-bar-bg">
                                    <div class="progress-bar-fill" style="width: 98%; background: #10b981;"></div>
                                </div>
                                <small class="text-muted">98.9% Uptime</small>
                            </div>
                        </div>
                        <div class="scurve-bg"></div>
                    </a>
                </div>

                <div class="dashboard-split animate-fade" style="animation-delay: 0.1s;">
                    <!-- Left Column: Charts -->
                    <div class="charts-section">
                        <div class="charts-grid-2">
                             <div class="glass-card big-card">
                                <div class="card-header">
                                    <h3><i class="fa-solid fa-chart-pie"></i> Covid Statistics</h3>
                                    <button class="icon-btn-sm"><i class="fa-solid fa-ellipsis"></i></button>
                                </div>
                                <div class="chart-container">
                                    <canvas id="covidChart"></canvas>
                                </div>
                            </div>
                            <div class="glass-card big-card">
                                <div class="card-header">
                                    <h3><i class="fa-solid fa-chart-line"></i> Activity Trends</h3>
                                    <button class="icon-btn-sm"><i class="fa-solid fa-ellipsis"></i></button>
                                </div>
                                <div class="chart-container">
                                    <canvas id="trendChart"></canvas>
                                </div>
                            </div>
                        </div>

                         <!-- Registered Hospitals Table -->
                        <div class="glass-card mt-4" style="margin-top: 40px !important;">
                            <div class="card-header">
                                <div>
                                    <h3><i class="fa-solid fa-hospital-user"></i> Hospital Network</h3>
                                    <p class="text-muted text-sm">Recent registered facilities</p>
                                </div>
                                <a href="admin_hospitals.php" class="btn-outline btn-sm">View All</a>
                            </div>
                            <div class="table-responsive">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Status</th>
                                            <th>Location</th>
                                            <th>Contact</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $result = $conn->query("SELECT * FROM hospitals WHERE status IN ('approved', 'rejected') ORDER BY id DESC LIMIT 2");
                                        while($row = $result->fetch_assoc()) {
                                            $statusClass = $row['status'] == 'approved' ? 'badge-success' : ($row['status'] == 'pending' ? 'badge-warning' : 'badge-danger');
                                            $logo = $row['image'] ? "<img src='{$row['image']}' class='table-avatar'>" : "<div class='table-avatar-placeholder'><i class='fa-solid fa-building'></i></div>";
                                            
                                            echo "<tr>
                                                <td>
                                                    <div class='d-flex align-items-center gap-3'>
                                                        $logo
                                                        <div>
                                                            <div class='fw-bold'>{$row['name']}</div>
                                                            <div class='text-xs text-muted'>ID: #{$row['id']}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class='badge $statusClass'>{$row['status']}</span></td>
                                                <td>{$row['location']}</td>
                                                <td>{$row['email']}</td>
                                            </tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Recent Activity & Pending -->
                    <div class="side-widgets">
                         <!-- Pending Approvals Widget -->
                        <?php if ($pending_hospitals > 0): ?>
                        <div class="glass-card warning-glow mb-4">
                            <div class="card-header">
                                <h3 class="text-warning"><i class="fa-solid fa-triangle-exclamation"></i> Pending</h3>
                                <span class="badge badge-warning"><?php echo $pending_hospitals; ?> New</span>
                            </div>
                            <div class="pending-list">
                                <?php
                                $p_res = $conn->query("SELECT * FROM hospitals WHERE status='pending' LIMIT 3");
                                while($p_row = $p_res->fetch_assoc()) {
                                    echo "<div class='pending-item'>
                                        <div class='d-flex align-items-center gap-3'>
                                            <div class='avatar-sm bg-warning-subtle text-warning'><i class='fa-solid fa-hospital'></i></div>
                                            <div>
                                                <div class='fw-bold'>{$p_row['name']}</div>
                                                <div class='text-xs text-muted'>{$p_row['location']}</div>
                                            </div>
                                        </div>
                                        <div class='action-row mt-2'>
                                             <a href='?approve_hospital={$p_row['id']}' class='btn-micro btn-approve'>Approve</a>
                                             <a href='?reject_hospital={$p_row['id']}' class='btn-micro btn-reject'>Reject</a>
                                        </div>
                                    </div>";
                                }
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Recent Activity Feed -->
                        <div class="glass-card">
                            <div class="card-header">
                                <h3><i class="fa-solid fa-clock-rotate-left"></i> Recent Activity</h3>
                            </div>
                            <div class="activity-feed">
                                <div class="feed-item">
                                    <div class="feed-icon bg-violet"><i class="fa-solid fa-user-plus"></i></div>
                                    <div class="feed-content">
                                        <div class="feed-title">New Patient Registered</div>
                                        <div class="feed-time">2 mins ago</div>
                                    </div>
                                </div>
                                <div class="feed-item">
                                    <div class="feed-icon bg-blue"><i class="fa-solid fa-vial"></i></div>
                                    <div class="feed-content">
                                        <div class="feed-title">Test Results Updated</div>
                                        <div class="feed-time">15 mins ago</div>
                                    </div>
                                </div>
                                <div class="feed-item">
                                    <div class="feed-icon bg-green"><i class="fa-solid fa-check-double"></i></div>
                                    <div class="feed-content">
                                        <div class="feed-title">System Backup Completed</div>
                                        <div class="feed-time">1 hour ago</div>
                                    </div>
                                </div>
                                 <div class="feed-item">
                                    <div class="feed-icon bg-orange"><i class="fa-solid fa-lock"></i></div>
                                    <div class="feed-content">
                                        <div class="feed-title">Security Check</div>
                                        <div class="feed-time">3 hours ago</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            document.getElementById('clock').innerText = now.toLocaleTimeString();
        }
        setInterval(updateClock, 1000);
        updateClock(); 

        // Charts Configuration
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: '#94a3b8', font: { family: "'Plus Jakarta Sans', sans-serif" } } }
            }
        };

        // Covid Stats Chart
        new Chart(document.getElementById('covidChart'), {
            type: 'doughnut',
            data: {
                labels: ['Positive', 'Negative', 'Vaccinated'],
                datasets: [{
                    data: [<?php echo $c_pos; ?>, <?php echo $c_neg; ?>, <?php echo $c_vac; ?>],
                    backgroundColor: ['#f43f5e', '#10b981', '#3b82f6'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                ...commonOptions,
                cutout: '75%'
            }
        });

        // Trend Chart
        const base = <?php echo ($total_patients + $c_vac); ?>;
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Activity',
                    data: [base+2, base+8, base+4, base+12, base+5, base+15, base+10],
                    borderColor: '#8b5cf6',
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    pointBackgroundColor: '#8b5cf6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
             options: {
                ...commonOptions,
                scales: {
                    y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' } },
                    x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                }
            }
        });
    </script>

    </div> <!-- End Main Wrapper -->
</body>
</html>
