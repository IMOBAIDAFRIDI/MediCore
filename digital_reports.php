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
    <title>Digital Reports | MediCore</title>
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
            border-color: var(--primary);
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.15); /* Keep green tint for eco/digital feel or use primary */
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--primary);
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
            <h1 class="animate-fade">Digital <span style="color: var(--primary);">Reports</span></h1>
            <p class="animate-fade" style="font-size: 1.2rem; max-width: 700px; margin: 20px auto 0; color: var(--text-muted);">
                Access your medical history anywhere. Unique QR-coded digital certificates for every vaccination and test result, instantly verifiable.
            </p>
        </div>
    </div>

    <div class="container">
        <div class="features-grid">
            <div class="feature-card animate-fade">
                <div class="feature-icon">
                    <i class="fa-solid fa-qrcode"></i>
                </div>
                <h3 class="feature-title">QR Code Verification</h3>
                <p class="feature-desc">
                    Every report comes with a unique, encrypted QR code. Authorities and organizations can scan it to instantly verify authenticity.
                </p>
            </div>

            <div class="feature-card animate-fade">
                <div class="feature-icon">
                    <i class="fa-solid fa-cloud-arrow-down"></i>
                </div>
                <h3 class="feature-title">Secure Cloud Storage</h3>
                <p class="feature-desc">
                    Your records are stored safely in the cloud. Access your history, download PDFs, or share reports directly from your dashboard.
                </p>
            </div>

            <div class="feature-card animate-fade">
                <div class="feature-icon">
                    <i class="fa-solid fa-share-nodes"></i>
                </div>
                <h3 class="feature-title">Easy Sharing</h3>
                <p class="feature-desc">
                    Need to send records to a doctor or travel authority? Generate temporary secure links or send direct emails with one click.
                </p>
            </div>
            
             <div class="feature-card animate-fade">
                <div class="feature-icon">
                    <i class="fa-solid fa-leaf"></i>
                </div>
                <h3 class="feature-title">Eco-Friendly</h3>
                <p class="feature-desc">
                    Go paperless. Reduce waste and contribute to a greener planet by managing your healthcare documentation entirely digitally.
                </p>
            </div>
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
