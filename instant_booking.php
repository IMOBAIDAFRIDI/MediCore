<?php
session_start();
require_once 'config.php';
require_once 'db_connect_pdo.php';
try {
    $stmt = $pdo->query("SELECT * FROM articles ORDER BY created_at DESC LIMIT 3");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $articles = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instant Booking | MediCore</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .page-header { background-size:cover; background-position:center; padding:90px 20px; min-height:calc(85vh + 40px); display:flex; align-items:center; justify-content:center; text-align:center; margin-bottom:60px; position:relative; overflow:hidden; }

        .features-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:28px; margin-bottom:90px; }

        .adv-card {
            position:relative;
            background:rgba(15,23,42,0.6);
            border:1px solid rgba(255,255,255,0.08);
            border-radius:24px;
            padding:44px 32px 36px;
            text-align:center;
            overflow:hidden;
            cursor:default;
            transition:transform 0.4s cubic-bezier(.23,1,.32,1), border-color 0.4s ease, box-shadow 0.4s ease;
            backdrop-filter:blur(20px);
        }
        .adv-card::before {
            content:''; position:absolute; inset:0; border-radius:24px;
            background:radial-gradient(600px circle at var(--mx,50%) var(--my,50%), rgba(59,130,246,0.12) 0%, transparent 60%);
            opacity:0; transition:opacity 0.4s ease; pointer-events:none;
        }
        .adv-card:hover::before { opacity:1; }
        .adv-card:hover { transform:translateY(-12px) scale(1.02); border-color:rgba(59,130,246,0.45); box-shadow:0 24px 60px rgba(59,130,246,0.18), 0 0 0 1px rgba(59,130,246,0.15); }
        .adv-card::after { content:''; position:absolute; top:0; left:10%; right:10%; height:2px; background:linear-gradient(90deg,transparent,rgba(59,130,246,0.8),transparent); border-radius:2px; opacity:0; transition:opacity 0.4s ease; }
        .adv-card:hover::after { opacity:1; }

        .adv-icon-wrap { position:relative; width:90px; height:90px; margin:0 auto 28px; display:flex; align-items:center; justify-content:center; }
        .icon-ring { position:absolute; inset:0; border-radius:50%; border:1.5px solid rgba(59,130,246,0.3); animation:spin-ring 8s linear infinite; }
        .icon-ring-2 { position:absolute; inset:8px; border-radius:50%; border:1px dashed rgba(59,130,246,0.2); animation:spin-ring 12s linear infinite reverse; }
        @keyframes spin-ring { to { transform:rotate(360deg); } }
        .adv-icon-bg { width:72px; height:72px; border-radius:50%; background:linear-gradient(135deg,rgba(59,130,246,0.15),rgba(59,130,246,0.05)); display:flex; align-items:center; justify-content:center; font-size:2rem; color:#3b82f6; position:relative; z-index:1; transition:transform 0.4s ease, background 0.4s ease; }
        .adv-card:hover .adv-icon-bg { transform:scale(1.12) rotate(-5deg); background:linear-gradient(135deg,rgba(59,130,246,0.3),rgba(59,130,246,0.1)); }
        .adv-card h3 { color:#f1f5f9; font-size:1.35rem; font-weight:700; margin-bottom:14px; }
        .adv-card p { color:#94a3b8; line-height:1.7; font-size:0.93rem; }
        .adv-tag { display:inline-flex; align-items:center; gap:6px; margin-top:22px; padding:6px 14px; border-radius:20px; font-size:0.72rem; font-weight:700; letter-spacing:0.8px; text-transform:uppercase; background:rgba(59,130,246,0.12); color:#3b82f6; border:1px solid rgba(59,130,246,0.25); transition:background 0.3s ease; }
        .adv-card:hover .adv-tag { background:rgba(59,130,246,0.22); }

        /* Steps Timeline */
        .steps-section { margin-bottom:90px; }
        .steps-title { text-align:center; margin-bottom:50px; }
        .steps-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:0; position:relative; }
        .steps-grid::before { content:''; position:absolute; top:40px; left:10%; right:10%; height:2px; background:linear-gradient(90deg,rgba(59,130,246,0.3),rgba(59,130,246,0.6),rgba(59,130,246,0.3)); z-index:0; }
        .step-item { text-align:center; padding:20px 16px; position:relative; z-index:1; }
        .step-num { width:80px; height:80px; border-radius:50%; background:linear-gradient(135deg,#1e3a8a,#3b82f6); display:flex; align-items:center; justify-content:center; margin:0 auto 20px; font-size:1.6rem; font-weight:800; color:#fff; box-shadow:0 8px 24px rgba(59,130,246,0.35); transition:transform 0.3s ease; }
        .step-item:hover .step-num { transform:scale(1.1); }
        .step-item h4 { color:#f1f5f9; font-size:1rem; font-weight:700; margin-bottom:8px; }
        .step-item p { color:#64748b; font-size:0.85rem; line-height:1.5; }

        /* Stats */
        .stats-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:20px; margin-bottom:80px; }
        .stat-box { background:rgba(15,23,42,0.6); border:1px solid rgba(255,255,255,0.07); border-radius:18px; padding:30px 20px; text-align:center; backdrop-filter:blur(16px); transition:transform 0.3s ease, border-color 0.3s ease; }
        .stat-box:hover { transform:translateY(-6px); border-color:rgba(59,130,246,0.4); }
        .stat-num { font-size:2.4rem; font-weight:800; background:linear-gradient(135deg,#3b82f6,#60a5fa); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; line-height:1; margin-bottom:8px; }
        .stat-label { color:#64748b; font-size:0.82rem; font-weight:500; }

        /* Advanced Article cards */
        .articles-section { padding: 80px 0; background: rgba(3,7,18,0.5); }
        .article-card {
            position: relative; background: rgba(15,23,42,0.65); border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px; overflow: hidden; display: flex; flex-direction: column; height: 100%;
            transition: transform 0.4s cubic-bezier(.23,1,.32,1), border-color 0.4s ease, box-shadow 0.4s ease;
            backdrop-filter: blur(20px);
        }
        .article-card::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(400px circle at var(--amx,50%) var(--amy,50%), rgba(59,130,246,0.12) 0%, transparent 70%);
            opacity: 0; transition: opacity 0.4s ease; pointer-events: none; z-index: 2;
        }
        .article-card:hover::before { opacity: 1; }
        .article-card:hover { transform: translateY(-8px); border-color: rgba(59,130,246,0.4); box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
        .article-img-wrap { position: relative; height: 200px; overflow: hidden; }
        .article-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease; }
        .article-card:hover .article-img { transform: scale(1.1); }
        
        .article-badge {
            position: absolute; top: 15px; left: 15px; background: rgba(59,130,246,0.9);
            color: #fff; font-size: 0.65rem; font-weight: 800; padding: 4px 10px; border-radius: 8px;
            text-transform: uppercase; letter-spacing: 1px; z-index: 3; backdrop-filter: blur(4px);
        }

        .article-content { padding: 24px; display: flex; flex-direction: column; flex-grow: 1; position: relative; z-index: 3; }
        .article-meta { font-size: 0.8rem; color: #64748b; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .article-meta i { color: #3b82f6; }
        .article-title { font-size: 1.15rem; color: #f1f5f9; margin-bottom: 12px; font-weight: 700; line-height: 1.4; }
        .article-summary { font-size: 0.9rem; color: #94a3b8; margin-bottom: 20px; flex-grow: 1; line-height: 1.6; }
        .read-more {
            color: #3b82f6; text-decoration: none; font-weight: 700; font-size: 0.85rem;
            display: inline-flex; align-items: center; gap: 8px; transition: gap 0.3s ease;
        }
        .read-more:hover { gap: 12px; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="page-header">
        <div class="hero-carousel">
            <div class="carousel-slide active" style="background-image:url('https://media.istockphoto.com/id/1256620949/photo/travel-booking-web-page-concept-booking-on-the-internet.jpg?s=612x612&w=0&k=20&c=f44108uXhCrf0eQn3OEftyGLIj7CXr_SDdqXsiZ19IU=');"></div>
            <div class="carousel-overlay"></div>
        </div>
        <div class="container">
            <h1 class="animate-fade">Instant <span style="color:#3b82f6;">Booking</span></h1>
            <p class="animate-fade" style="font-size:1.2rem;max-width:700px;margin:20px auto 30px;color:#94a3b8;">
                Skip the waiting lines. Our AI-powered system connects you with the nearest available vaccination center in seconds.
            </p>
            <a href="login.php" class="btn btn-primary animate-fade" style="padding:15px 40px;font-size:1.1rem;">
                <i class="fa-regular fa-calendar-check" style="margin-right:10px;"></i> Book Appointment Now
            </a>
        </div>
    </div>

    <div class="container">
        <!-- Stats -->
        <div class="stats-row animate-fade">
            <div class="stat-box"><div class="stat-num" data-target="500">0</div><div class="stat-label">+ Partner Hospitals</div></div>
            <div class="stat-box"><div class="stat-num" data-target="30">0</div><div class="stat-label">sec Slot Updates</div></div>
            <div class="stat-box"><div class="stat-num" data-target="24">0</div><div class="stat-label">/7 Booking Open</div></div>
            <div class="stat-box"><div class="stat-num" data-target="2">0</div><div class="stat-label">min Avg. Booking Time</div></div>
        </div>

        <!-- Feature Cards -->
        <div class="features-grid animate-fade">
            <div class="adv-card">
                <div class="adv-icon-wrap"><div class="icon-ring"></div><div class="icon-ring-2"></div><div class="adv-icon-bg"><i class="fa-solid fa-bolt"></i></div></div>
                <h3>Real-time Availability</h3>
                <p>View live appointment slots from over 500+ hospitals. Our system updates every 30 seconds to ensure you see accurate availability.</p>
                <span class="adv-tag"><i class="fa-solid fa-circle" style="font-size:6px;"></i> Live Updates</span>
            </div>
            <div class="adv-card">
                <div class="adv-icon-wrap"><div class="icon-ring"></div><div class="icon-ring-2"></div><div class="adv-icon-bg"><i class="fa-solid fa-location-dot"></i></div></div>
                <h3>Smart Location</h3>
                <p>Automatically detects your location to suggest the nearest vaccination centers, saving you travel time and hassle.</p>
                <span class="adv-tag"><i class="fa-solid fa-location-dot" style="font-size:9px;"></i> GPS Powered</span>
            </div>
            <div class="adv-card">
                <div class="adv-icon-wrap"><div class="icon-ring"></div><div class="icon-ring-2"></div><div class="adv-icon-bg"><i class="fa-regular fa-clock"></i></div></div>
                <h3>24/7 Booking</h3>
                <p>Book your slot anytime, anywhere. Our platform is always online, allowing you to manage your health schedule on your own terms.</p>
                <span class="adv-tag"><i class="fa-solid fa-clock" style="font-size:9px;"></i> Always Open</span>
            </div>
        </div>

        <!-- How It Works Steps -->
        <div class="steps-section animate-fade">
            <div class="steps-title">
                <h2>How It <span style="color:#3b82f6;">Works</span></h2>
                <p style="color:#64748b;margin-top:10px;">Book your appointment in 4 simple steps</p>
            </div>
            <div class="steps-grid">
                <div class="step-item"><div class="step-num">1</div><h4>Register</h4><p>Create your free patient account in under 2 minutes.</p></div>
                <div class="step-item"><div class="step-num">2</div><h4>Find Hospital</h4><p>Browse verified hospitals near your location.</p></div>
                <div class="step-item"><div class="step-num">3</div><h4>Pick a Slot</h4><p>Choose your preferred date and time instantly.</p></div>
                <div class="step-item"><div class="step-num">4</div><h4>Get Confirmed</h4><p>Receive instant email confirmation with details.</p></div>
            </div>
        </div>
    </div>

    <section class="articles-section">
        <div class="container">
            <h2 class="animate-fade" style="margin-bottom:30px;border-left:4px solid #3b82f6;padding-left:15px;">Latest <span style="color:#3b82f6;">Health Insights</span></h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:30px;">
                <?php if (!empty($articles)): foreach($articles as $row):
                    $image = !empty($row['image']) ? $row['image'] : 'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?auto=format&fit=crop&w=400&q=80';
                    $date = date('M d, Y', strtotime($row['created_at']));
                    $summary = strlen($row['content']) > 120 ? substr($row['content'],0,120).'...' : $row['content'];
                    $url = !empty($row['url']) ? $row['url'] : '#';
                ?>
                <div class="article-card animate-fade">
                    <div class="article-img-wrap">
                        <span class="article-badge">Booking</span>
                        <a href="<?= $url ?>" target="_blank"><img src="<?= $image ?>" alt="<?= htmlspecialchars($row['title']) ?>" class="article-img"></a>
                    </div>
                    <div class="article-content">
                        <div class="article-meta"><i class="fa-regular fa-calendar"></i> <?= $date ?></div>
                        <h3 class="article-title"><?= htmlspecialchars($row['title']) ?></h3>
                        <p class="article-summary"><?= htmlspecialchars($summary) ?></p>
                        <a href="<?= $url ?>" target="_blank" class="read-more">Learn More <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>
    <script>
    document.querySelectorAll('.adv-card, .article-card').forEach(card => {
        card.addEventListener('mousemove', e => {
            const r = card.getBoundingClientRect();
            const x = ((e.clientX - r.left) / r.width * 100) + '%';
            const y = ((e.clientY - r.top) / r.height * 100) + '%';
            
            if(card.classList.contains('article-card')) {
                card.style.setProperty('--amx', x);
                card.style.setProperty('--amy', y);
            } else {
                card.style.setProperty('--mx', x);
                card.style.setProperty('--my', y);
            }
        });
    });

    const counters = document.querySelectorAll('.stat-num[data-target]');
    const obs = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const el = entry.target, target = parseFloat(el.dataset.target);
            let start = 0; const inc = target / (1800/16);
            const t = setInterval(() => { start = Math.min(start+inc,target); el.textContent = Math.floor(start); if(start>=target) clearInterval(t); }, 16);
            obs.unobserve(el);
        });
    }, {threshold:0.5});
    counters.forEach(c => obs.observe(c));
    </script>
</body>
</html>
