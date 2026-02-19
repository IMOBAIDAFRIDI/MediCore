<?php
session_start();
require_once 'config.php';
require_once 'db_connect_pdo.php';
try {
    $stmt = $pdo->query("SELECT * FROM articles ORDER BY created_at DESC LIMIT 3");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'hospital' AND status = 'approved' ORDER BY name ASC");
    $hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $articles = []; $hospitals = []; }
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
        .page-header { background-size:cover; background-position:center; padding:90px 20px; min-height:calc(85vh + 40px); display:flex; align-items:center; justify-content:center; text-align:center; margin-bottom:60px; position:relative; overflow:hidden; }

        /* Stats */
        .stats-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:20px; margin-bottom:80px; }
        .stat-box { background:rgba(15,23,42,0.6); border:1px solid rgba(255,255,255,0.07); border-radius:18px; padding:30px 20px; text-align:center; backdrop-filter:blur(16px); transition:transform 0.3s ease, border-color 0.3s ease; }
        .stat-box:hover { transform:translateY(-6px); border-color:rgba(245,158,11,0.4); }
        .stat-num { font-size:2.4rem; font-weight:800; background:linear-gradient(135deg,#f59e0b,#fbbf24); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; line-height:1; margin-bottom:8px; }
        .stat-label { color:#64748b; font-size:0.82rem; font-weight:500; }

        /* Hospital Cards */
        .network-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:24px; margin-bottom:90px; }

        .hospital-card {
            position:relative;
            background:rgba(15,23,42,0.65);
            border:1px solid rgba(255,255,255,0.08);
            border-radius:20px;
            padding:28px 24px;
            display:flex; align-items:center; gap:20px;
            overflow:hidden;
            cursor:default;
            transition:transform 0.4s cubic-bezier(.23,1,.32,1), border-color 0.4s ease, box-shadow 0.4s ease;
            backdrop-filter:blur(20px);
        }
        .hospital-card::before {
            content:''; position:absolute; inset:0; border-radius:20px;
            background:radial-gradient(500px circle at var(--mx,50%) var(--my,50%), rgba(245,158,11,0.10) 0%, transparent 60%);
            opacity:0; transition:opacity 0.4s ease; pointer-events:none;
        }
        .hospital-card:hover::before { opacity:1; }
        .hospital-card:hover {
            transform:translateY(-8px) scale(1.01);
            border-color:rgba(245,158,11,0.4);
            box-shadow:0 20px 50px rgba(245,158,11,0.15), 0 0 0 1px rgba(245,158,11,0.1);
        }
        /* Top shimmer line */
        .hospital-card::after {
            content:''; position:absolute; top:0; left:15%; right:15%; height:1.5px;
            background:linear-gradient(90deg,transparent,rgba(245,158,11,0.7),transparent);
            opacity:0; transition:opacity 0.4s ease;
        }
        .hospital-card:hover::after { opacity:1; }

        .hosp-avatar {
            flex-shrink:0;
            width:68px; height:68px; border-radius:16px;
            background:linear-gradient(135deg,rgba(245,158,11,0.2),rgba(245,158,11,0.05));
            border:1.5px solid rgba(245,158,11,0.25);
            display:flex; align-items:center; justify-content:center;
            font-size:1.8rem; color:#f59e0b;
            overflow:hidden;
            transition:transform 0.3s ease, border-color 0.3s ease;
        }
        .hosp-avatar img { width:100%; height:100%; object-fit:cover; border-radius:14px; }
        .hospital-card:hover .hosp-avatar { transform:scale(1.08); border-color:rgba(245,158,11,0.5); }

        .hosp-body { flex-grow:1; min-width:0; }
        .hosp-name { color:#f1f5f9; font-size:1.05rem; font-weight:700; margin-bottom:6px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .hosp-location { color:#94a3b8; font-size:0.83rem; margin-bottom:10px; display:flex; align-items:center; gap:6px; }
        .hosp-location i { color:#f59e0b; }
        .hosp-badge {
            display:inline-flex; align-items:center; gap:5px;
            padding:4px 10px; border-radius:20px;
            font-size:0.7rem; font-weight:700; letter-spacing:0.5px;
            background:rgba(245,158,11,0.1); color:#f59e0b;
            border:1px solid rgba(245,158,11,0.25);
        }
        .hosp-badge .dot { width:6px; height:6px; border-radius:50%; background:#f59e0b; animation:blink 1.5s ease-in-out infinite; }
        @keyframes blink { 0%,100%{ opacity:1; } 50%{ opacity:0.3; } }

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
            background: radial-gradient(400px circle at var(--amx,50%) var(--amy,50%), rgba(245,158,11,0.12) 0%, transparent 70%);
            opacity: 0; transition: opacity 0.4s ease; pointer-events: none; z-index: 2;
        }
        .article-card:hover::before { opacity: 1; }
        .article-card:hover { transform: translateY(-8px); border-color: rgba(245,158,11,0.4); box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
        .article-img-wrap { position: relative; height: 200px; overflow: hidden; }
        .article-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease; }
        .article-card:hover .article-img { transform: scale(1.1); }
        
        .article-badge {
            position: absolute; top: 15px; left: 15px; background: rgba(245,158,11,0.9);
            color: #fff; font-size: 0.65rem; font-weight: 800; padding: 4px 10px; border-radius: 8px;
            text-transform: uppercase; letter-spacing: 1px; z-index: 3; backdrop-filter: blur(4px);
        }

        .article-content { padding: 24px; display: flex; flex-direction: column; flex-grow: 1; position: relative; z-index: 3; }
        .article-meta { font-size: 0.8rem; color: #64748b; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .article-meta i { color: #f59e0b; }
        .article-title { font-size: 1.15rem; color: #f1f5f9; margin-bottom: 12px; font-weight: 700; line-height: 1.4; }
        .article-summary { font-size: 0.9rem; color: #94a3b8; margin-bottom: 20px; flex-grow: 1; line-height: 1.6; }
        .read-more {
            color: #f59e0b; text-decoration: none; font-weight: 700; font-size: 0.85rem;
            display: inline-flex; align-items: center; gap: 8px; transition: gap 0.3s ease;
        }
        .read-more:hover { gap: 12px; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="page-header">
        <div class="hero-carousel">
            <div class="carousel-slide active" style="background-image:url('https://media.istockphoto.com/id/914789708/photo/technology-innovation-and-medicine-concept-doctor-and-medical-network-connection-with-modern.jpg?s=612x612&w=0&k=20&c=SuL-hUCj7fEt_aoB_-ocW3mnCDkEqdVrWp4bpPVxlTQ=');"></div>
            <div class="carousel-overlay"></div>
        </div>
        <div class="container">
            <h1 class="animate-fade">Global <span style="color:#f59e0b;">Network</span></h1>
            <p class="animate-fade" style="font-size:1.2rem;max-width:700px;margin:20px auto 0;color:#94a3b8;">
                Connecting trusted healthcare providers worldwide. Find verified hospitals and specialized clinics in your region.
            </p>
        </div>
    </div>

    <div class="container">
        <!-- Stats -->
        <div class="stats-row animate-fade">
            <div class="stat-box"><div class="stat-num" data-target="<?= count($hospitals) ?>">0</div><div class="stat-label">Verified Partners</div></div>
            <div class="stat-box"><div class="stat-num" data-target="50">0</div><div class="stat-label">+ Cities Covered</div></div>
            <div class="stat-box"><div class="stat-num" data-target="99">0</div><div class="stat-label">% Satisfaction Rate</div></div>
            <div class="stat-box"><div class="stat-num" data-target="24">0</div><div class="stat-label">/7 Available</div></div>
        </div>

        <!-- Hospital Cards -->
        <h2 class="animate-fade" style="margin-bottom:12px;border-left:4px solid #f59e0b;padding-left:15px;">Our <span style="color:#f59e0b;">Partners</span></h2>
        <p class="animate-fade" style="color:#64748b;margin-bottom:40px;">All hospitals are verified and approved by the MediCore admin team.</p>

        <div class="network-grid animate-fade">
            <?php if (!empty($hospitals)): foreach($hospitals as $row): ?>
            <div class="hospital-card">
                <div class="hosp-avatar">
                    <?php if (!empty($row['image'])): ?>
                        <img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                    <?php else: ?>
                        <i class="fa-solid fa-hospital"></i>
                    <?php endif; ?>
                </div>
                <div class="hosp-body">
                    <div class="hosp-name"><?= htmlspecialchars($row['name']) ?></div>
                    <div class="hosp-location"><i class="fa-solid fa-location-dot"></i><?= htmlspecialchars($row['location'] ?? 'N/A') ?></div>
                    <span class="hosp-badge"><span class="dot"></span> Verified Partner</span>
                </div>
            </div>
            <?php endforeach; else: ?>
            <div style="grid-column:1/-1;text-align:center;padding:60px 20px;">
                <i class="fa-solid fa-hospital" style="font-size:3rem;color:#334155;margin-bottom:16px;display:block;"></i>
                <p style="color:#64748b;">We are currently updating our partner list. Check back soon.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <section class="articles-section">
        <div class="container">
            <h2 class="animate-fade" style="margin-bottom:30px;border-left:4px solid #f59e0b;padding-left:15px;">Latest <span style="color:#f59e0b;">Health Insights</span></h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:30px;">
                <?php if (!empty($articles)): foreach($articles as $row):
                    $image = !empty($row['image']) ? $row['image'] : 'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?auto=format&fit=crop&w=400&q=80';
                    $date = date('M d, Y', strtotime($row['created_at']));
                    $summary = strlen($row['content']) > 120 ? substr($row['content'],0,120).'...' : $row['content'];
                    $url = !empty($row['url']) ? $row['url'] : '#';
                ?>
                <div class="article-card animate-fade">
                    <div class="article-img-wrap">
                        <span class="article-badge">Network</span>
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
    // Spotlight for hospital + article cards
    document.querySelectorAll('.hospital-card, .article-card').forEach(card => {
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

    // Animated counters
    const obs = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const el = entry.target, target = parseFloat(el.dataset.target);
            let start = 0; const inc = target / (1800/16);
            const t = setInterval(() => { start = Math.min(start+inc,target); el.textContent = Math.floor(start); if(start>=target) clearInterval(t); }, 16);
            obs.unobserve(el);
        });
    }, {threshold:0.5});
    document.querySelectorAll('.stat-num[data-target]').forEach(c => obs.observe(c));
    </script>
</body>
</html>
