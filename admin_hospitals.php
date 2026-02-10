<?php
session_start();
include 'db_connect.php';

// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    
    // First delete associated appointments to satisfy foreign key constraint
    $conn->query("DELETE FROM appointments WHERE hospital_id=$id");
    
    // Then delete the hospital
    $conn->query("DELETE FROM hospitals WHERE id=$id");
    header("Location: admin_hospitals.php");
    exit();
}

// Search Logic
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where_sql = "WHERE status = 'approved'";
if (!empty($search)) {
    $where_sql .= " AND (name LIKE '%$search%' OR location LIKE '%$search%')";
}

$sql = "SELECT * FROM hospitals $where_sql ORDER BY reg_date DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hospitals List | MediCore Admin</title>
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
                <h1>Registered Hospitals</h1>
                <p class="text-muted">List of all approved hospitals in the system</p>
            </div>
            
            <div class="header-actions" style="gap: 15px;">
                 <form method="GET" class="search-container">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search hospitals..." class="glass-input">
                </form>
            </div>
        </div>

        <div class="glass-card animate-fade">
            <div class="card-header">
                <h3><i class="fa-solid fa-hospital"></i> Hospitals</h3>
            </div>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Logo</th>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Email</th>
                            <th>Joined Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>
                                    <td>#{$row['id']}</td>
                                    <td>
                                        <img src='".(!empty($row['image']) ? $row['image'] : 'https://cdn-icons-png.flaticon.com/512/33/33777.png')."' alt='Logo' style='width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid var(--glass-border);'>
                                    </td>
                                    <td>
                                        <div class='fw-bold'>{$row['name']}</div>
                                    </td>
                                    <td><i class='fa-solid fa-location-dot text-accent'></i> {$row['location']}</td>
                                    <td>{$row['email']}</td>
                                    <td>".date('M d, Y', strtotime($row['reg_date']))."</td>
                                    <td>
                                        <div class='d-flex gap-2'>
                                            <a href='edit_hospital.php?id={$row['id']}' class='icon-btn-sm text-primary' title='Edit Hospital'><i class='fa-solid fa-pen'></i></a>
                                            <a href='?delete_id={$row['id']}' class='icon-btn-sm text-danger' title='Delete Hospital' onclick='return confirm(\"Are you sure you want to delete this hospital? This action cannot be undone.\")'><i class='fa-solid fa-trash'></i></a>
                                        </div>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center py-5 text-muted'>No hospitals found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
