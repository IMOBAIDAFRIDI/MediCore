<?php
/**
 * Admin Patients Management - Advanced
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
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'patient'");
        $stmt->execute([$id]);
        header("Location: admin_patients.php?msg=Patient record removed successfully");
        exit();
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch all patients
$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'patient' ORDER BY created_at DESC");
$stmt->execute();
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Patients | Admin</title>
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
            background: radial-gradient(circle at top left, rgba(139, 92, 246, 0.03), transparent);
            pointer-events: none;
        }

        .modern-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        .modern-table tr { background: rgba(255, 255, 255, 0.01); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .modern-table tr:hover { background: rgba(255, 255, 255, 0.04); transform: translateX(5px); }
        .modern-table th { padding: 15px 20px; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1.5px; color: var(--text-muted); border-bottom: 1px solid rgba(255,255,255,0.05); text-align: left; }
        .modern-table td { padding: 18px 20px; vertical-align: middle; border-bottom: 1px solid rgba(255,255,255,0.02); }
        
        .patient-identity { display: flex; align-items: center; gap: 12px; }
        .patient-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), #ec4899); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #fff; font-size: 0.9rem; border: 2px solid rgba(255,255,255,0.1); }

        .action-btns { display: flex; gap: 10px; }
        .btn-icon { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; border: 1px solid rgba(255,255,255,0.05); cursor: pointer; }
        .btn-edit { background: rgba(139, 92, 246, 0.1); color: #a78bfa; }
        .btn-edit:hover { background: var(--primary); color: #000; }
        .btn-delete { background: rgba(244, 63, 94, 0.1); color: #fb7185; }
        .btn-delete:hover { background: #f43f5e; color: #fff; }

        .badge { padding: 5px 10px; font-size: 0.65rem; border-radius: 6px; font-weight: 700; letter-spacing: 0.5px; }
        .badge-patient { background: rgba(139, 92, 246, 0.1); color: #a78bfa; border: 1px solid rgba(139, 92, 246, 0.2); }
        
        .msg-box { padding: 15px 25px; border-radius: 15px; margin-bottom: 25px; font-size: 0.9rem; }
        .msg-success { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
    </style>
</head>
<body style="background: var(--background); color: var(--text-light);">

    <style>
        .patient-grid-adv {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        @media (max-width: 1100px) {
            .patient-grid-adv { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .patient-grid-adv { grid-template-columns: 1fr; }
        }

        .p-card-adv {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 25px;
            position: relative;
            overflow: hidden;
            transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
            display: flex;
            flex-direction: column;
            min-height: 380px;
        }

        .p-card-adv:hover {
            transform: translateY(-8px);
            border-color: rgba(236, 72, 153, 0.3);
            background: rgba(255, 255, 255, 0.03);
            box-shadow: 0 40px 80px rgba(0,0,0,0.6);
        }

        .p-card-adv::after {
            content: '';
            position: absolute;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(236, 72, 153, 0.05) 0%, transparent 60%);
            opacity: 0;
            transition: opacity 0.5s;
            pointer-events: none;
        }

        .p-card-adv:hover::after { opacity: 1; }

        .p-top-adv {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .p-avatar-adv {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ec4899, #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 800;
            color: #fff;
            box-shadow: 0 10px 25px rgba(236, 72, 153, 0.3);
            border: 3px solid rgba(255, 255, 255, 0.1);
        }

        .p-meta-adv h3 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.5px;
        }

        .p-meta-adv .p-loc {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .p-details-adv {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 25px;
            padding: 15px;
            background: rgba(0,0,0,0.2);
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.03);
        }

        .detail-node-adv {
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-width: 0;
        }

        .node-label {
            font-size: 0.6rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .node-value {
            font-size: 0.75rem;
            font-weight: 700;
            color: #e2e8f0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
        }

        .p-actions-adv {
            display: flex;
            gap: 10px;
            margin-top: auto;
        }

        .btn-p-action {
            flex: 1;
            padding: 10px;
            border-radius: 12px;
            font-size: 0.65rem;
            font-weight: 800;
            text-decoration: none;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-p-edit {
            background: rgba(139, 92, 246, 0.1);
            color: #a78bfa;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }

        .btn-p-edit:hover {
            background: #8b5cf6;
            color: #fff;
            box-shadow: 0 0 15px rgba(139, 92, 246, 0.3);
        }

        .btn-p-del {
            background: rgba(244, 63, 94, 0.1);
            color: #fb7185;
            border: 1px solid rgba(244, 63, 94, 0.2);
        }

        .btn-p-del:hover {
            background: #f43f5e;
            color: #fff;
            box-shadow: 0 0 15px rgba(244, 63, 94, 0.3);
        }
    </style>

    <div class="container" style="padding-top: 60px; max-width: 1200px;">
        <div class="page-header" style="margin-bottom: 50px; text-align: center;">
            <h1 style="font-size: 2.5rem; letter-spacing: 1px; margin: 0; font-weight: 800; text-transform: uppercase;">
                PATIENT <span style="color: #ec4899;">REGISTRY</span>
            </h1>
            <p style="color: #94a3b8; font-weight: 500; margin-top: 5px;">Manage citizen records and medical access.</p>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="msg-box msg-success animate-fade">
                <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>

        <div class="patient-grid-adv">
            <?php foreach($patients as $p): ?>
                <div class="p-card-adv animate-fade">
                    <div class="p-top-adv">
                        <div class="p-avatar-adv"><?php echo strtoupper(substr($p['name'], 0, 1)); ?></div>
                        <div class="p-meta-adv">
                            <h3><?php echo htmlspecialchars($p['name']); ?></h3>
                            <div class="p-loc">
                                <i class="fa-solid fa-location-dot" style="color: #ec4899;"></i>
                                <?php echo htmlspecialchars($p['location'] ?? 'Region Unspecified'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="p-details-adv">
                        <div class="detail-node-adv">
                            <span class="node-label">ID</span>
                            <span class="node-value">#<?php echo $p['id']; ?></span>
                        </div>
                        <div class="detail-node-adv">
                            <span class="node-label">Joined</span>
                            <span class="node-value"><?php echo date('M j, Y', strtotime($p['created_at'])); ?></span>
                        </div>
                        <div class="detail-node-adv" style="padding-top: 8px; border-top: 1px solid rgba(255,255,255,0.05);">
                            <span class="node-label">Email</span>
                            <span class="node-value" title="<?php echo htmlspecialchars($p['email']); ?>">
                                <?php echo htmlspecialchars($p['email']); ?>
                            </span>
                        </div>
                        <div class="detail-node-adv">
                            <span class="node-label">Phone</span>
                            <span class="node-value"><?php echo htmlspecialchars($p['phone'] ?? 'NOT LINKED'); ?></span>
                        </div>
                    </div>

                    <div class="p-actions-adv">
                        <a href="edit_patient.php?id=<?php echo $p['id']; ?>" class="btn-p-action btn-p-edit">
                            <i class="fa-solid fa-pen"></i> EDIT
                        </a>
                        <a href="admin_patients.php?delete_id=<?php echo $p['id']; ?>" class="btn-p-action btn-p-del" onclick="return confirm('Delete this record?')">
                            <i class="fa-solid fa-trash"></i> DELETE
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if(empty($patients)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 100px; background: rgba(255,255,255,0.01); border-radius: 40px; border: 1px dashed rgba(255,255,255,0.05);">
                    <i class="fa-solid fa-users-slash" style="font-size: 3rem; color: #334155; margin-bottom: 20px;"></i>
                    <h3 style="color: #64748b;">The Patient Registry is Currently Empty</h3>
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
