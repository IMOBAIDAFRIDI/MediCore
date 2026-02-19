<?php
/**
 * Admin Hospitals Management - Advanced
 */
session_start();
require_once 'config.php';
require_once 'db_connect_pdo.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'hospital'");
        $stmt->execute([$id]);
        header("Location: admin_hospitals.php?msg=Hospital deleted successfully");
        exit();
    } catch (PDOException $e) {
        $error = "Error deleting hospital: " . $e->getMessage();
    }
}

// Fetch all approved hospitals
$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'hospital' AND status = 'approved' ORDER BY created_at DESC");
$stmt->execute();
$hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Hospitals | Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .glass-nav { position: sticky; top: 0; z-index: 1000; background: #000000; backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255,255,255,0.05); }
        
        .advanced-card { 
            background: rgba(255, 255, 255, 0.02); 
            backdrop-filter: blur(15px); 
            border: 1px solid rgba(255, 255, 255, 0.05); 
            border-radius: 32px; 
            padding: 40px; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }

        .advanced-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at top right, rgba(139, 92, 246, 0.03), transparent);
            pointer-events: none;
        }

        .modern-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .modern-table tr { background: rgba(255, 255, 255, 0.01); transition: all 0.3s ease; }
        .modern-table tr:hover { background: rgba(255, 255, 255, 0.03); transform: scale(1.005); }
        .modern-table th { padding: 20px; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; color: var(--text-muted); border-bottom: 1px solid rgba(255,255,255,0.05); }
        .modern-table td { padding: 20px; vertical-align: middle; border-bottom: 1px solid rgba(255,255,255,0.03); }
        
        .hospital-identity { display: flex; align-items: center; gap: 15px; }
        .hospital-img { width: 45px; height: 45px; border-radius: 14px; object-fit: cover; border: 2px solid rgba(255,255,255,0.1); }
        .hospital-initial { width: 45px; height: 45px; border-radius: 14px; background: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #000; }

        .action-btns { display: flex; gap: 8px; }
        .btn-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; border: 1px solid rgba(255,255,255,0.05); cursor: pointer; color: #fff; }
        .btn-edit { background: rgba(139, 92, 246, 0.1); color: #a78bfa; }
        .btn-edit:hover { background: var(--primary); color: #000; }
        .btn-delete { background: rgba(244, 63, 94, 0.1); color: #fb7185; }
        .btn-delete:hover { background: #f43f5e; color: #fff; }

        .badge { padding: 6px 12px; font-size: 0.7rem; border-radius: 8px; font-weight: 700; }
        .badge-success { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        
        .msg-box { padding: 15px 25px; border-radius: 15px; margin-bottom: 25px; font-size: 0.9rem; }
        .msg-success { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
    </style>
</head>
<body style="background: var(--background); color: var(--text-light);">



    <style>
        .hospital-grid-adv {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-top: 20px;
        }

        @media (max-width: 900px) {
            .hospital-grid-adv { grid-template-columns: 1fr; }
        }

        .h-card-adv {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 30px;
            padding: 30px;
            position: relative;
            overflow: hidden;
            transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
            display: flex;
            flex-direction: column;
        }

        .h-card-adv:hover {
            transform: translateY(-8px);
            border-color: rgba(139, 92, 246, 0.3);
            background: rgba(255, 255, 255, 0.03);
            box-shadow: 0 40px 80px rgba(0,0,0,0.6);
        }

        .h-card-adv::after {
            content: '';
            position: absolute;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.05) 0%, transparent 60%);
            opacity: 0;
            transition: opacity 0.5s;
            pointer-events: none;
        }

        .h-card-adv:hover::after { opacity: 1; }

        .h-top-adv {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .h-img-adv {
            width: 75px;
            height: 75px;
            border-radius: 20px;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.05);
            background: rgba(0,0,0,0.2);
        }

        .h-initial-adv {
            width: 75px;
            height: 75px;
            border-radius: 20px;
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 800;
            color: #000;
            box-shadow: 0 10px 20px rgba(124, 58, 237, 0.2);
        }

        .h-meta-adv h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.5px;
        }

        .h-meta-adv .h-loc {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .h-details-adv {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
            padding: 20px;
            background: rgba(0,0,0,0.2);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.03);
        }

        .detail-node-adv {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .node-label {
            font-size: 0.65rem;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .node-value {
            font-size: 0.85rem;
            font-weight: 600;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .h-actions-adv {
            display: flex;
            gap: 12px;
            margin-top: auto;
        }

        .btn-h-action {
            flex: 1;
            padding: 12px;
            border-radius: 14px;
            font-size: 0.75rem;
            font-weight: 800;
            text-decoration: none;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-h-edit {
            background: rgba(139, 92, 246, 0.1);
            color: #a78bfa;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }

        .btn-h-edit:hover {
            background: var(--primary);
            color: #000;
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.3);
        }

        .btn-h-del {
            background: rgba(244, 63, 94, 0.1);
            color: #fb7185;
            border: 1px solid rgba(244, 63, 94, 0.2);
        }

        .btn-h-del:hover {
            background: #f43f5e;
            color: #fff;
            box-shadow: 0 0 20px rgba(244, 63, 94, 0.3);
        }
    </style>

    <div class="container" style="padding-top: 60px; max-width: 1200px;">
        <div class="page-header" style="margin-bottom: 50px; text-align: center;">
            <h1 style="font-size: 2.5rem; letter-spacing: -1px; margin: 0; font-weight: 800;">
                HOSPITAL <span style="color: var(--primary);">NETWORK</span>
            </h1>
            <p style="color: #94a3b8; font-weight: 500; margin-top: 5px;">Manage network partners and secure facility records.</p>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="msg-box msg-success animate-fade">
                <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>

        <div class="hospital-grid-adv">
            <?php foreach($hospitals as $h): ?>
                <div class="h-card-adv animate-fade">
                    <div class="h-top-adv">
                        <?php if(!empty($h['image'])): ?>
                            <img src="<?php echo htmlspecialchars($h['image']); ?>" class="h-img-adv">
                        <?php else: ?>
                            <div class="h-initial-adv"><?php echo strtoupper(substr($h['name'], 0, 1)); ?></div>
                        <?php endif; ?>
                        <div class="h-meta-adv">
                            <h3><?php echo htmlspecialchars($h['name']); ?></h3>
                            <div class="h-loc">
                                <i class="fa-solid fa-location-dot" style="color: var(--primary);"></i>
                                <?php echo htmlspecialchars($h['location'] ?? 'Global Node'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="h-details-adv">
                        <div class="detail-node-adv">
                            <span class="node-label">Contact Email</span>
                            <span class="node-value" title="<?php echo htmlspecialchars($h['email']); ?>">
                                <?php echo htmlspecialchars($h['email']); ?>
                            </span>
                        </div>
                        <div class="detail-node-adv">
                            <span class="node-label">Phone Line</span>
                            <span class="node-value"><?php echo htmlspecialchars($h['phone'] ?? '+00 000 000'); ?></span>
                        </div>
                        <div class="detail-node-adv" style="margin-top: 10px;">
                            <span class="node-label">Role</span>
                            <span class="node-value" style="color: #10b981;">HOSPITAL</span>
                        </div>
                        <div class="detail-node-adv" style="margin-top: 10px;">
                            <span class="node-label">Joined</span>
                            <span class="node-value"><?php echo date('M j, Y', strtotime($h['created_at'])); ?></span>
                        </div>
                    </div>

                    <div class="h-actions-adv">
                        <a href="edit_hospital.php?id=<?php echo $h['id']; ?>" class="btn-h-action btn-h-edit">
                            <i class="fa-solid fa-pen"></i> EDIT
                        </a>
                        <a href="admin_hospitals.php?delete_id=<?php echo $h['id']; ?>" class="btn-h-action btn-h-del" onclick="return confirm('Delete this facility?')">
                            <i class="fa-solid fa-trash"></i> DELETE
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if(empty($hospitals)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 100px; background: rgba(255,255,255,0.01); border-radius: 40px; border: 1px dashed rgba(255,255,255,0.05);">
                    <i class="fa-solid fa-hospital-user" style="font-size: 3rem; color: #334155; margin-bottom: 20px;"></i>
                    <h3 style="color: #64748b;">No Active Hospital Nodes Found</h3>
                </div>
            <?php endif; ?>
        </div>

        <div style="margin-top: 30px; text-align: center;">
            <a href="admin_dashboard.php" style="color: #94a3b8; text-decoration: none; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; transition: all 0.3s; display: inline-flex; align-items: center; gap: 10px; justify-content: center;" onmouseover="this.style.color='#fff'; this.style.transform='translateY(-3px)'" onmouseout="this.style.color='#94a3b8'; this.style.transform='translateY(0)'">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
