<?php
session_start();
include 'db_connect.php';

// Fetch vaccines
try {
    // Fetch vaccines with availability status based on hospital inventory
    $sql = "SELECT v.*, 
            (SELECT COUNT(*) FROM hospital_vaccines hv WHERE hv.vaccine_id = v.id AND hv.status = 'available') as available_count 
            FROM vaccines v";
    $result = $conn->query($sql);

    // Fetch articles
    $sql_articles = "SELECT * FROM articles ORDER BY published_date DESC LIMIT 3";
    $result_articles = $conn->query($sql_articles);
} catch (Exception $e) {
    // Graceful fallback if tables don't exist
    $result = false;
    $result_articles = false;
    // Log error or display friendly message in admin area
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaccines | MediCore</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .page-header {
            /* background-image removed for carousel */
            background-size: cover;
            background-position: center;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            margin-bottom: 50px;
        }
        
        .vaccine-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            padding: 20px 0;
            align-items: stretch;
        }

        @media (max-width: 991px) {
            .vaccine-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .vaccine-grid {
                grid-template-columns: 1fr;
            }
        }

        .vaccine-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .vaccine-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 225, 255, 0.15);
            border-color: var(--primary);
        }

        .vaccine-img-container {
            height: 200px;
            overflow: hidden;
            position: relative;
        }

        .vaccine-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .vaccine-card:hover .vaccine-img {
            transform: scale(1.1);
        }

        .vaccine-content {
            padding: 25px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .vaccine-title {
            color: var(--light);
            font-size: 1.4rem;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .vaccine-desc {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 20px;
            flex-grow: 1;
        }

        .vaccine-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            border-top: 1px solid var(--glass-border);
            padding-top: 15px;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-available {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-unavailable {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .btn-book {
            background: var(--primary);
            color: #000;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-book:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        /* Article Section Styles */
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

        .read-more:hover {
            gap: 8px;
            color: var(--primary-hover);
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
        <!-- Carousel -->
        <div class="hero-carousel">
            <div class="carousel-slide active" style="background-image: url('https://images.unsplash.com/photo-1588776814546-1ffcf101e938?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div> <!-- Vaccine Vials -->
            <div class="carousel-slide" style="background-image: url('https://images.unsplash.com/photo-1632053000632-411a7f053229?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div> <!-- Lab -->
            <div class="carousel-slide" style="background-image: url('https://images.unsplash.com/photo-1576091160550-2187580018f7?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div> <!-- Doctor -->
            <div class="carousel-overlay"></div>
        </div>
        
        <div class="container">
            <h1 class="animate-fade">Available <span style="color: var(--secondary);">Vaccines</span></h1>
            <p class="animate-fade" style="font-size: 1.2rem; max-width: 600px; margin: 20px auto 30px; color: var(--text-muted);">
                Browse our list of approved COVID-19 vaccines. Check availability and book your appointment today.
            </p>
        </div>
    </div>

    <div class="container" style="padding-bottom: 80px;">
        <div class="vaccine-grid">
            <?php
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $isAvailable = $row['available_count'] > 0;
                    $statusClass = $isAvailable ? 'status-available' : 'status-unavailable';
                    // ... rest of loop code remains same, just ensuring $result check
                    $statusText = $isAvailable ? 'Available' : 'Unavailable';
                    $image = !empty($row['image']) ? $row['image'] : 'https://images.unsplash.com/photo-1584036561566-b93a50208ccf?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80';
                    
                    echo '
                    <div class="vaccine-card animate-fade">
                        <div class="vaccine-img-container">
                            <img src="'.$image.'" alt="'.$row['name'].'" class="vaccine-img">
                        </div>
                        <div class="vaccine-content">
                            <h3 class="vaccine-title">'.$row['name'].'</h3>
                            <p class="vaccine-desc">'.$row['description'].'</p>
                            <div class="vaccine-meta">
                                <span class="status-badge '.$statusClass.'">
                                    <i class="fa-solid fa-circle" style="font-size: 6px; vertical-align: middle; margin-right: 4px;"></i>
                                    '.$statusText.'
                                </span>
                                ' . ($isAvailable ? '<a href="book_appointment.php?vaccine_id='.$row['id'].'&type=vaccination" class="btn-book">Book Now</a>' : '<button class="btn-book" style="opacity:0.5; cursor:not-allowed;" disabled>No Stock</button>') . '
                            </div>
                        </div>
                    </div>
                    ';
                }
            } else {
                echo '<div class="glass-card" style="grid-column: 1/-1; text-align: center; padding: 40px;">
                        <i class="fa-solid fa-box-open" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 20px;"></i>
                        <h3>No Vaccines Found</h3>
                        <p class="text-muted">Currently there are no vaccines listed in our database or database connection failed.</p>
                      </div>';
            }
            ?>
        </div>
    </div>

    <section class="articles-section">
        <div class="container">
            <h2 class="animate-fade" style="margin-bottom: 30px; border-left: 4px solid var(--primary); padding-left: 15px;">Latest <span style="color: var(--primary);">Health Insights</span></h2>
            <div class="vaccine-grid">
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
                    echo '<p class="text-muted">No articles available at the moment. Warning: Articles table might be missing.</p>';
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

