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
    <title>Reliability | MediCore</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .page-header {
            /* background-image removed for carousel */
            background-size: cover;
            background-position: center;
            height: 400px; /* Increased height for impact */
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
            <h1 class="animate-fade">System <span style="color: var(--success);">Reliability</span></h1>
            <p class="animate-fade" style="font-size: 1.2rem; max-width: 700px; margin: 20px auto 0; color: var(--text-muted);">
                Engineered for resilience. Our platform guarantees 99.9% uptime, ensuring critical healthcare services are always standard-compliant and available.
            </p>
        </div>
    </div>

    <div class="container">
        <div class="features-grid">
            <div class="feature-card animate-fade">
                <div class="feature-icon">
                    <i class="fa-solid fa-server"></i>
                </div>
                <h3 class="feature-title">99.9% Uptime SLA</h3>
                <p class="feature-desc">
                    Our distributed cloud architecture ensures high availability. Redundant servers and automated failover mechanisms keep the system live during spikes.
                </p>
            </div>

            <div class="feature-card animate-fade">
                <div class="feature-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3 class="feature-title">End-to-End Encryption</h3>
                <p class="feature-desc">
                    Patient data is secured with AES-256 encryption at rest and TLS 1.3 in transit. We prioritize privacy and compliance with global health standards.
                </p>
            </div>

            <div class="feature-card animate-fade">
                <div class="feature-icon">
                    <i class="fa-solid fa-headset"></i>
                </div>
                <h3 class="feature-title">24/7 Expert Support</h3>
                <p class="feature-desc">
                    Our dedicated technical team monitors system health around the clock. Rapid incident response ensures minimal disruption to hospital operations.
                </p>
            </div>

            <div class="feature-card animate-fade">
                <div class="feature-icon">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                <h3 class="feature-title">Lightning Fast</h3>
                <p class="feature-desc">
                    Optimized database queries and CDN integration deliver sub-second response times, ensuring medical professionals can access records instantly.
                </p>
            </div>
             <div class="feature-card animate-fade">
                <div class="feature-icon">
                    <i class="fa-solid fa-database"></i>
                </div>
                <h3 class="feature-title">Automated Backups</h3>
                <p class="feature-desc">
                    Real-time data replication and daily automated backups ensure data integrity and zero data loss in any scenario.
                </p>
            </div>
             <div class="feature-card animate-fade">
                <div class="feature-icon">
                    <i class="fa-solid fa-globe"></i>
                </div>
                <h3 class="feature-title">Global Scalability</h3>
                <p class="feature-desc">
                    Built to scale horizontally, our infrastructure supports thousands of concurrent users across multiple regions without performance degradation.
                </p>
            </div>
        </div>
    </div>

    <section class="articles-section">
        <div class="container">
            <h2 class="animate-fade" style="margin-bottom: 30px; border-left: 4px solid var(--success); padding-left: 15px;">Latest <span style="color: var(--success);">Updates</span></h2>
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
