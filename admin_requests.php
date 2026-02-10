<?php
session_start();
include 'db_connect.php';

// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle Actions
if (isset($_GET['approve_id'])) {
    $id = $_GET['approve_id'];
    $conn->query("UPDATE hospitals SET status='approved' WHERE id=$id");
    header("Location: admin_requests.php?msg=approved");
    exit();
}

if (isset($_GET['reject_id'])) {
    $id = $_GET['reject_id'];
    $conn->query("UPDATE hospitals SET status='rejected' WHERE id=$id");
    header("Location: admin_requests.php?msg=rejected");
    exit();
}

// Fetch Pending
$sql = "SELECT * FROM hospitals WHERE status = 'pending' ORDER BY reg_date ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Requests | MediCore Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Glass Navbar -->
    <nav class="glass-nav">
        <a href="admin_dashboard.php" class="nav-brand">
            <i class="fa-solid fa-heart-pulse text-primary"></i>
            <span>MediCore</span>
        </a>

        <div class="nav-links-container">
            <a href="admin_dashboard.php" class="nav-link">
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
                <h1>Hospital Requests</h1>
                <p class="text-muted">Manage pending registration requests</p>
            </div>
        </div>

        <div class="glass-card animate-fade">
            <div class="card-header">
                <h3><i class="fa-solid fa-bell"></i> Pending Approvals</h3>
            </div>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Hospital Name</th>
                            <th>Location</th>
                            <th>Email</th>
                            <th>Request Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>
                                    <td>
                                        <div class='fw-bold'>{$row['name']}</div>
                                    </td>
                                    <td>{$row['location']}</td>
                                    <td>{$row['email']}</td>
                                    <td>".date('M d, Y h:i A', strtotime($row['reg_date']))."</td>
                                    <td>
                                        <div class='d-flex gap-2'>
                                            <a href='?approve_id={$row['id']}' class='btn btn-primary btn-sm' style='padding: 6px 12px; font-size: 0.8rem;'>Approve</a>
                                            <a href='?reject_id={$row['id']}' class='btn btn-outline btn-sm' style='padding: 6px 12px; font-size: 0.8rem; border-color: var(--danger); color: var(--danger);'>Reject</a>
                                        </div>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center py-5 text-muted'>No pending requests at the moment.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
