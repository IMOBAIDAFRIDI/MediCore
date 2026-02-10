<?php
session_start();
include 'db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: vaccines.php");
    exit();
}

$id = $_GET['id'];
$sql = "SELECT * FROM articles WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "Article not found.";
    exit();
}

$article = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $article['title']; ?> | MediCore</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .article-hero {
            height: 400px;
            background-image: linear-gradient(to bottom, rgba(3, 7, 18, 0.4), var(--dark)), url('<?php echo $article['image']; ?>');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: flex-end;
            padding-bottom: 60px;
        }

        .article-content-container {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .article-body {
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--light);
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            padding: 40px;
            border-radius: 16px;
            margin-top: -50px;
        }
        
        .article-info {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            text-decoration: none;
            margin-bottom: 20px;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="logo">
                    <i class="fa-solid fa-virus-covid"></i> MediCore
                </div>
                <div class="nav-links">
                    <a href="index.php" class="btn btn-outline" style="border:none;">Home</a>
                    <a href="vaccines.php" class="btn btn-outline" style="border:none;">Vaccines</a>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="<?php echo $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : ($_SESSION['role'] === 'hospital' ? 'hospital_dashboard.php' : 'patient_dashboard.php'); ?>" class="btn btn-primary">Dashboard</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary">Login</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <div class="article-hero">
        <div class="container">
            <h1 class="animate-fade" style="font-size: 3rem; max-width: 900px; padding-bottom: 20px; text-shadow: 0 4px 10px rgba(0,0,0,0.5);"><?php echo $article['title']; ?></h1>
        </div>
    </div>

    <div class="container article-content-container">
        <a href="vaccines.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Vaccines</a>
        
        <div class="article-body animate-fade">
            <div class="article-info">
                <span><i class="fa-regular fa-calendar"></i> <?php echo date('F d, Y', strtotime($article['published_date'])); ?></span>
                <span><i class="fa-regular fa-user"></i> <?php echo $article['author']; ?></span>
            </div>
            
            <p style="font-size: 1.3rem; color: var(--primary); margin-bottom: 30px; line-height: 1.6; border-left: 3px solid var(--primary); padding-left: 20px;">
                <?php echo $article['summary']; ?>
            </p>

            <div style="white-space: pre-wrap; font-family: 'Inter', sans-serif;">
<?php echo $article['content']; ?>
            </div>
            
            <div style="margin-top: 50px; padding-top: 20px; border-top: 1px solid var(--glass-border); text-align: center;">
                <p class="text-muted">Share this article</p>
                <div style="display: flex; gap: 15px; justify-content: center; margin-top: 15px;">
                    <button class="btn btn-outline" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 50%;"><i class="fa-brands fa-twitter"></i></button>
                    <button class="btn btn-outline" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 50%;"><i class="fa-brands fa-facebook-f"></i></button>
                    <button class="btn btn-outline" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 50%;"><i class="fa-brands fa-linkedin-in"></i></button>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container" style="text-align: center; padding: 40px; color: var(--text-muted); border-top: 1px solid var(--glass-border); margin-top: 50px;">
            <p>&copy; 2026 MediCore Systems. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
