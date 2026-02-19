<?php
session_start();
include 'db_connect_pdo.php';

// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// System Stats
$db_status_bool = false;
try {
    $db_status_bool = (bool)$pdo->query("SELECT 1");
} catch(Exception $e) {
    $db_status_bool = false;
}

$db_status = $db_status_bool ? "Online" : "Offline";
$db_color = $db_status_bool ? "var(--success)" : "var(--danger)";
$server_soft = $_SERVER['SERVER_SOFTWARE'];
$php_ver = phpversion();
$memory_usage = round(memory_get_usage() / 1024 / 1024, 2) . " MB";
$server_time = date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Health | MediCore Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Special Tech Theme Overrides */
        :root {
            --primary: #0ea5e9; /* Cyan 500 */
            --primary-dark: #0284c7; /* Cyan 600 */
        }
        
        .page-header {
            background-image: linear-gradient(rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.95)), url('https://images.unsplash.com/photo-1558494949-ef526b0042a0?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            margin-bottom: 50px;
            border-bottom: 1px solid rgba(14, 165, 233, 0.3);
        }

        .health-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }

        .metric-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(14, 165, 233, 0.2);
            border-radius: 16px;
            padding: 30px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .metric-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: 0 10px 40px rgba(14, 165, 233, 0.15);
        }

        .metric-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: rgba(14, 165, 233, 0.1);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-right: 20px;
        }

        .metric-content h3 {
            color: #94a3b8;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .metric-value {
            color: #f8fafc;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="logo">
                    <i class="fa-solid fa-virus-covid" style="color: var(--primary);"></i> MediCore<span style="color: var(--primary);">Admin</span>
                </div>
                <div class="nav-links">
                    <a href="index.php" class="btn btn-outline" style="border:none;">Home</a>
                    <a href="admin_dashboard.php" class="btn btn-primary">Dashboard</a>
                    <a href="logout.php" class="btn btn-outline" style="border-color: var(--danger); color: var(--danger);">Logout</a>
                </div>
            </nav>
        </div>
    </header>

    <div class="page-header">
        <div class="container">
            <h1 class="animate-fade" style="font-size: 2.5rem;">System <span style="color: var(--primary);">Health</span></h1>
            <p class="animate-fade" style="font-size: 1.1rem; max-width: 600px; margin: 15px auto 0; color: #94a3b8;">
                Real-time performance metrics and server status monitoring.
            </p>
        </div>
    </div>

    <div class="container">
        <div class="health-grid">
            <!-- Database Status -->
            <div class="metric-card animate-fade">
                <div class="metric-icon" style="color: <?php echo $db_color; ?>; background: rgba(<?php echo $db_status_bool ? '16, 185, 129' : '239, 68, 68'; ?>, 0.1);">
                    <i class="fa-solid fa-database"></i>
                </div>
                <div class="metric-content">
                    <h3>Database Status</h3>
                    <div class="metric-value" style="color: <?php echo $db_color; ?>;"><?php echo $db_status; ?></div>
                </div>
            </div>

            <!-- PHP Version -->
            <div class="metric-card animate-fade">
                <div class="metric-icon">
                    <i class="fa-brands fa-php"></i>
                </div>
                <div class="metric-content">
                    <h3>PHP Version</h3>
                    <div class="metric-value"><?php echo $php_ver; ?></div>
                </div>
            </div>

            <!-- Memory Usage -->
            <div class="metric-card animate-fade">
                <div class="metric-icon">
                    <i class="fa-solid fa-memory"></i>
                </div>
                <div class="metric-content">
                    <h3>Memory Usage</h3>
                    <div class="metric-value"><?php echo $memory_usage; ?></div>
                </div>
            </div>

            <!-- Server Time -->
            <div class="metric-card animate-fade">
                <div class="metric-icon">
                    <i class="fa-regular fa-clock"></i>
                </div>
                <div class="metric-content">
                    <h3>Server Time</h3>
                    <div class="metric-value" style="font-size: 1.4rem;"><?php echo $server_time; ?></div>
                </div>
            </div>

             <!-- Server Software -->
             <div class="metric-card animate-fade" style="grid-column: 1 / -1;">
                <div class="metric-icon">
                    <i class="fa-solid fa-server"></i>
                </div>
                <div class="metric-content">
                    <h3>Server Software</h3>
                    <div class="metric-value" style="font-size: 1.2rem;"><?php echo $server_soft; ?></div>
                </div>
            </div>
        </div>

        <!-- System Logs / Info -->
        <div class="glass-card animate-fade" style="padding: 30px; border-left: 4px solid var(--primary);">
            <div style="display: flex; align-items: flex-start; gap: 20px;">
                <i class="fa-solid fa-circle-info" style="font-size: 2rem; color: var(--primary); margin-top: 5px;"></i>
                <div>
                    <h3 style="color: var(--light); margin-bottom: 10px;">Security Check</h3>
                    <p style="color: var(--text-muted); line-height: 1.6;">
                        <strong>User Agent:</strong> <?php echo $_SERVER['HTTP_USER_AGENT']; ?><br>
                        <strong>Remote Address:</strong> <?php echo $_SERVER['REMOTE_ADDR']; ?><br>
                        <strong>Session ID:</strong> <?php echo session_id(); ?>
                    </p>
                </div>
            </div>
        </div>
        
    </div>

    <!-- Footer -->
    <footer style="margin-top: 80px;">
        <div class="container" style="text-align: center; padding: 40px; color: var(--text-muted); border-top: 1px solid var(--glass-border);">
            <p>&copy; 2026 MediCore Systems. Admin Console.</p>
        </div>
    </footer>
</body>
</html>
