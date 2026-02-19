<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' | ' : ''; ?>MediCore</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if(isset($extra_styles)) echo $extra_styles; ?>
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="logo">
                    <i class="fa-solid fa-virus-covid"></i> MediCore
                </div>
                <button class="hamburger" id="hamburger">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="nav-links" id="navLinks">
                    <a href="index.php" class="btn btn-outline" style="border:none;">Home</a>
                    <?php if(basename($_SERVER['PHP_SELF']) == 'index.php'): ?>
                        <a href="#features" class="btn btn-outline" style="border:none;">Features</a>
                    <?php endif; ?>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="<?php echo $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : ($_SESSION['role'] === 'hospital' ? 'hospital_dashboard.php' : 'patient_dashboard.php'); ?>" class="btn btn-primary">Dashboard</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary">Portal Login</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>
