<?php
/**
 * Admin Hospital Requests - Nexus Queue
 */
session_start();
require_once 'config.php';
require_once 'db_connect_pdo.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Fetch pending hospitals
$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'hospital' AND status = 'pending' ORDER BY created_at ASC");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NEXUS APPLICATIONS | MediCore</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --neon-blue: #3b82f6;
            --neon-green: #10b981;
            --neon-red: #f43f5e;
            --neon-orange: #fb923c;
        }

        body {
            background: #050505;
            color: #fff;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        .main-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 60px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .page-branding {
            text-align: center;
            margin-bottom: 50px;
        }

        .page-branding h1 {
            font-size: 2.2rem;
            font-weight: 900;
            letter-spacing: 4px;
            text-transform: uppercase;
            margin: 0;
            background: linear-gradient(to right, #fff, #64748b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-branding p {
            color: var(--neon-orange);
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-top: 5px;
        }

        .applications-card {
            width: 100%;
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 32px;
            padding: 35px;
            box-shadow: 0 40px 100px rgba(0,0,0,0.6);
            position: relative;
            overflow: hidden;
        }

        .applications-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: radial-gradient(circle at top right, rgba(251, 146, 60, 0.05), transparent 50%);
            pointer-events: none;
        }

        .app-item {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        .app-item:hover {
            transform: scale(1.02);
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .app-info {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .app-avatar {
            width: 55px;
            height: 55px;
            background: rgba(251, 146, 60, 0.1);
            border: 1px solid rgba(251, 146, 60, 0.2);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: var(--neon-orange);
            font-weight: 800;
        }

        .app-details h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
        }

        .app-details p {
            margin: 4px 0 0;
            font-size: 0.8rem;
            color: #94a3b8;
        }

        .app-actions {
            display: flex;
            gap: 12px;
        }

        .btn-action {
            padding: 10px 22px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-approve {
            background: rgba(16, 185, 129, 0.15);
            color: var(--neon-green);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .btn-approve:hover {
            background: var(--neon-green);
            color: #000;
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.4);
        }

        .btn-reject {
            background: rgba(244, 63, 94, 0.15);
            color: var(--neon-red);
            border: 1px solid rgba(244, 63, 94, 0.2);
        }

        .btn-reject:hover {
            background: var(--neon-red);
            color: #fff;
            box-shadow: 0 0 20px rgba(244, 63, 94, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 60px 0;
            opacity: 0.6;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--neon-orange);
            margin-bottom: 20px;
        }

        .footer-nav {
            margin-top: 40px;
        }

        .footer-nav a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-nav a:hover {
            color: #fff;
            transform: translateX(-5px);
        }
    </style>
</head>
<body>

    <div class="main-container">
        <div class="page-branding animate-fade">
            <h1 style="letter-spacing: 1px;">PENDING REQUESTS</h1>
            <p style="color: #94a3b8; font-weight: 500; letter-spacing: 0.5px;">Review hospital partnership applications.</p>
        </div>

        <div class="applications-card animate-fade">
            <?php if(empty($requests)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-check-double"></i>
                    <h3>Queue Fully Synchronized</h3>
                    <p>No pending hospital applications found in the registry.</p>
                </div>
            <?php else: ?>
                <?php foreach($requests as $r): ?>
                <div class="app-item animate-fade">
                    <div class="app-info">
                        <div class="app-avatar">
                            <?php echo strtoupper(substr($r['name'], 0, 1)); ?>
                        </div>
                        <div class="app-details">
                            <h3><?php echo htmlspecialchars($r['name']); ?></h3>
                            <p><?php echo htmlspecialchars($r['location'] ?? 'Location Unspecified'); ?> • <span style="opacity:0.7;"><?php echo htmlspecialchars($r['email']); ?></span></p>
                            <p style="font-size: 0.65rem; color: var(--neon-orange); font-weight: 700; margin-top: 5px;">
                                <i class="fa-regular fa-clock"></i> HANDED OVER: <?php echo date('M j, Y | H:i', strtotime($r['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                    <div class="app-actions">
                        <a href="handle_request.php?action=approve&id=<?php echo $r['id']; ?>" class="btn-action btn-approve">ACCEPT</a>
                        <a href="handle_request.php?action=reject&id=<?php echo $r['id']; ?>" class="btn-action btn-reject">REJECT</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="footer-nav animate-fade" style="animation-delay: 0.5s;">
            <a href="admin_dashboard.php">
                <i class="fa-solid fa-circle-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

</body>
</html>
