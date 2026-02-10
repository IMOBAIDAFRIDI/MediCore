<?php
session_start();
include 'db_connect.php';

// Fetch articles for the bottom section
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
    <title>Secure & Safe | MediCore</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .page-header {
            /* background-image removed for carousel */
            background-size: cover;
            background-position: center;
            height: 450px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            margin-bottom: 50px;
            position: relative; /* Added for carousel positioning */
            overflow: hidden; /* Added for carousel */
        }

        .hero-carousel {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0; /* Ensure carousel is behind content */
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
            transition: opacity 1.5s ease-in-out;
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
            z-index: 1; /* Ensure overlay is above carousel slides */
        }

        .page-header .container {
            position: relative;
            z-index: 2; /* Ensure content is above overlay */
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 80px;
        }

        .feature-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 40px 30px;
            text-align: center;
            transition: transform 0.3s ease, border-color 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            border-color: var(--success);
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.15);
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--success);
            margin-bottom: 25px;
            display: inline-block;
            background: rgba(16, 185, 129, 0.1); 
            padding: 20px;
            border-radius: 50%;
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: auto;
            margin-right: auto;
        }

        .feature-title {
            color: var(--light);
            font-size: 1.5rem;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .feature-desc {
            color: var(--text-muted);
            line-height: 1.6;
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
            <h1 class="animate-fade">Secure & <span style="color: var(--success);">Safe</span></h1>
            <p class="animate-fade" style="font-size: 1.2rem; max-width: 700px; margin: 20px auto 0; color: var(--text-muted);">
                Your health data is sensitive. We treat it with the highest level of security, encryption, and privacy compliance.
            </p>
        </div>
    </div>

    <div class="container">
        <div class="features-grid">
            <div class="feature-card animate-fade">
                <div class="feature-icon">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <h3 class="feature-title">AES-256 Encryption</h3>
                <p class="feature-desc">
                    All sensitive data is encrypted with military-grade AES-256 encryption before it is stored in our databases.
                </p>
            </div>

            <div class="feature-card animate-fade">
                <div class="feature-icon">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <h3 class="feature-title">Privacy First</h3>
                <p class="feature-desc">
                    We strictly adhere to data privacy laws. Your information is never sold or shared with advertisers or third parties without consent.
                </p>
            </div>

            <div class="feature-card animate-fade">
                <div class="feature-icon">
                    <i class="fa-solid fa-file-shield"></i>
                </div>
                <h3 class="feature-title">HIPAA & GDPR Compliant</h3>
                <p class="feature-desc">
                    MediCore is built to meet international healthcare regulations, ensuring your medical records are handled lawfully and ethically.
                </p>
            </div>

             <div class="feature-card animate-fade">
                <div class="feature-icon">
                    <i class="fa-solid fa-key"></i>
                </div>
                <h3 class="feature-title">Multi-Factor Auth</h3>
                <p class="feature-desc">
                    Protect your account with an extra layer of security. We support 2FA to prevent unauthorized access to your personal dashboard.
                </p>
            </div>
        </div>
    </div>

    <section class="articles-section">
        <div class="container">
            <h2 class="animate-fade" style="margin-bottom: 30px; border-left: 4px solid var(--success); padding-left: 15px;">Latest <span style="color: var(--success);">Health Insights</span></h2>
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
                                <a href="read_article.php?id='.$row['id'].'" class="read-more" style="color: var(--success);">Read Full Article <i class="fa-solid fa-arrow-right"></i></a>
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
