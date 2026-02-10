<?php
session_start();
include 'db_connect.php';

// Fetch approved hospitals
try {
    $sql_hospitals = "SELECT * FROM hospitals WHERE status = 'approved'";
    $result_hospitals = $conn->query($sql_hospitals);
} catch (Exception $e) {
    $result_hospitals = false;
}

// Fetch articles
try {
    $sql_articles = "SELECT * FROM articles ORDER BY published_date DESC LIMIT 3";
    $result_articles = $conn->query($sql_articles);
} catch (Exception $e) {
    $result_articles = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Network | MediCore</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .page-header {
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            margin-bottom: 50px;
            position: relative;
            overflow: hidden;
        }

        .hero-carousel {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        .carousel-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }

        .carousel-slide.active {
            opacity: 1;
        }

        .carousel-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(3, 7, 18, 0.8), rgba(3, 7, 18, 0.9));
            z-index: 1;
        }

        .page-header .container {
            position: relative;
            z-index: 2;
        }
        
        .network-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 80px;
        }

        .hospital-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            transition: transform 0.3s ease, border-color 0.3s ease;
        }

        .hospital-card:hover {
            transform: translateY(-5px);
            border-color: var(--warning);
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.15);
        }

        .hospital-img {
            width: 120px;
            height: 100%;
            object-fit: cover;
            min-height: 150px;
        }

        .hospital-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .hospital-name {
            color: var(--light);
            font-size: 1.2rem;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .hospital-location {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .hospital-status {
            display: inline-block;
            font-size: 0.75rem;
            padding: 4px 10px;
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border-radius: 20px;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        /* Article Section Styles (Reused) */
        .articles-section {
            padding: 80px 0;
            background: rgba(3, 7, 18, 0.5); 
        }

        .article-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
            transition: transform 0.3s ease;
        }

        .article-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
        }

        .article-img {
            height: 180px;
            width: 100%;
            object-fit: cover;
        }

        .article-content {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .article-meta {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .article-title {
            font-size: 1.2rem;
            color: var(--light);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .article-summary {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 20px;
            flex-grow: 1;
            line-height: 1.5;
        }

        .read-more {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: gap 0.2s ease;
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
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="<?php echo $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : ($_SESSION['role'] === 'hospital' ? 'hospital_dashboard.php' : 'patient_dashboard.php'); ?>" class="btn btn-primary">Dashboard</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary">Login</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <div class="page-header">
        <div class="container">
            <h1 class="animate-fade">Global <span style="color: var(--warning);">Network</span></h1>
            <p class="animate-fade" style="font-size: 1.2rem; max-width: 700px; margin: 20px auto 0; color: var(--text-muted);">
                Connecting trusted healthcare providers worldwide. Find verified hospitals and specialized clinics in your region.
            </p>
        </div>
    </div>

    <div class="container">
        <h2 class="animate-fade" style="margin-bottom: 30px; border-left: 4px solid var(--warning); padding-left: 15px;">Our <span style="color: var(--warning);">Partners</span></h2>
        
        <div class="network-grid">
            <?php
            if ($result_hospitals && $result_hospitals->num_rows > 0) {
                while($row = $result_hospitals->fetch_assoc()) {
                    $image = !empty($row['image']) ? $row['image'] : 'https://images.unsplash.com/photo-1516574187841-69301905a304?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80';
                    
                    echo '
                    <div class="hospital-card animate-fade">
                        <img src="'.$image.'" alt="'.$row['name'].'" class="hospital-img">
                        <div class="hospital-content">
                            <h3 class="hospital-name">'.$row['name'].'</h3>
                            <div class="hospital-location"><i class="fa-solid fa-location-dot" style="margin-right:5px;"></i> '.$row['location'].'</div>
                            <span class="hospital-status"><i class="fa-solid fa-check-circle"></i> Verified Partner</span>
                        </div>
                    </div>
                    ';
                }
            } else {
                 echo '<div class="glass-card full-width" style="grid-column: 1/-1; text-align: center; padding: 40px;">
                        <i class="fa-solid fa-hospital" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 20px;"></i>
                        <h3>No Hospitals Found</h3>
                        <p class="text-muted">We are currently updating our partner list.</p>
                      </div>';
            }
            ?>
        </div>
    </div>

    <section class="articles-section">
        <div class="container">
            <h2 class="animate-fade" style="margin-bottom: 30px; border-left: 4px solid var(--primary); padding-left: 15px;">Latest <span style="color: var(--primary);">Health Insights</span></h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px;">
                <?php
                if ($result_articles && $result_articles->num_rows > 0) {
                    while($row = $result_articles->fetch_assoc()) {
                        $image = !empty($row['image']) ? $row['image'] : 'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80';
                        $date = date('M d, Y', strtotime($row['published_date']));
                        
                        echo '
                        <div class="article-card animate-fade">
                            <img src="'.$image.'" alt="'.$row['title'].'" class="article-img">
                            <div class="article-content">
                                <div class="article-meta">
                                    <span><i class="fa-regular fa-calendar"></i> '.$date.'</span>
                                    <span style="margin: 0 5px;">•</span>
                                    <span>'.$row['author'].'</span>
                                </div>
                                <h3 class="article-title">'.$row['title'].'</h3>
                                <p class="article-summary">'.$row['summary'].'</p>
                                <a href="read_article.php?id='.$row['id'].'" class="read-more">Read Full Article <i class="fa-solid fa-arrow-right"></i></a>
                            </div>
                        </div>
                        ';
                    }
                } else {
                    echo '<p class="text-muted">No articles available.</p>';
                }
                ?>
            </div>
        </div>
    </section>

    <footer>
        <div class="container" style="text-align: center; padding: 40px; color: var(--text-muted); border-top: 1px solid var(--glass-border);">
            <p>&copy; 2026 MediCore Systems. All rights reserved.</p>
        </div>
    </footer>
    <script src="script.js"></script>
</body>
</html>
