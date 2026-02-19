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
    <title>Digital Reports | MediCore</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .page-header { background-size:cover; background-position:center; padding:90px 20px; min-height:calc(85vh + 40px); display:flex; align-items:center; justify-content:center; text-align:center; margin-bottom:60px; position:relative; overflow:hidden; }
        .features-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:28px; margin-bottom:90px; }
        .adv-card { position:relative; background:rgba(15,23,42,0.6); border:1px solid rgba(255,255,255,0.08); border-radius:24px; padding:44px 32px 36px; text-align:center; overflow:hidden; cursor:default; transition:transform 0.4s cubic-bezier(.23,1,.32,1), border-color 0.4s ease, box-shadow 0.4s ease; backdrop-filter:blur(20px); }
        .adv-card::before { content:''; position:absolute; inset:0; border-radius:24px; background:radial-gradient(600px circle at var(--mx,50%) var(--my,50%), rgba(139,92,246,0.12) 0%, transparent 60%); opacity:0; transition:opacity 0.4s ease; pointer-events:none; }
        .adv-card:hover::before { opacity:1; }
        .adv-card:hover { transform:translateY(-12px) scale(1.02); border-color:rgba(139,92,246,0.45); box-shadow:0 24px 60px rgba(139,92,246,0.18); }
        .adv-card::after { content:''; position:absolute; top:0; left:10%; right:10%; height:2px; background:linear-gradient(90deg,transparent,rgba(139,92,246,0.8),transparent); opacity:0; transition:opacity 0.4s ease; }
        .adv-card:hover::after { opacity:1; }
        .adv-icon-wrap { position:relative; width:90px; height:90px; margin:0 auto 28px; display:flex; align-items:center; justify-content:center; }
        .icon-ring { position:absolute; inset:0; border-radius:50%; border:1.5px solid rgba(139,92,246,0.3); animation:spin-ring 8s linear infinite; }
        .icon-ring-2 { position:absolute; inset:8px; border-radius:50%; border:1px dashed rgba(139,92,246,0.2); animation:spin-ring 12s linear infinite reverse; }
        @keyframes spin-ring { to { transform:rotate(360deg); } }
        .adv-icon-bg { width:72px; height:72px; border-radius:50%; background:linear-gradient(135deg,rgba(139,92,246,0.15),rgba(139,92,246,0.05)); display:flex; align-items:center; justify-content:center; font-size:2rem; color:#8b5cf6; position:relative; z-index:1; transition:transform 0.4s ease; }
        .adv-card:hover .adv-icon-bg { transform:scale(1.12) rotate(-5deg); }
        .adv-card h3 { color:#f1f5f9; font-size:1.35rem; font-weight:700; margin-bottom:14px; }
        .adv-card p { color:#94a3b8; line-height:1.7; font-size:0.93rem; }
        .adv-tag { display:inline-flex; align-items:center; gap:6px; margin-top:22px; padding:6px 14px; border-radius:20px; font-size:0.72rem; font-weight:700; letter-spacing:0.8px; text-transform:uppercase; background:rgba(139,92,246,0.12); color:#8b5cf6; border:1px solid rgba(139,92,246,0.25); }

        /* PDF Mockup */
        .pdf-showcase { display:grid; grid-template-columns:1fr 1fr; gap:50px; align-items:center; margin-bottom:90px; }
        @media(max-width:768px){ .pdf-showcase { grid-template-columns:1fr; } }
        .pdf-mockup { background:linear-gradient(145deg,rgba(20,30,55,0.9),rgba(10,15,30,0.95)); border:1px solid rgba(139,92,246,0.2); border-radius:20px; padding:28px; position:relative; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,0.5); }
        .pdf-mockup::before { content:''; position:absolute; top:0; left:0; right:0; height:4px; background:linear-gradient(90deg,#8b5cf6,#a78bfa,#8b5cf6); }
        .pdf-header { display:flex; align-items:center; gap:12px; margin-bottom:20px; padding-bottom:16px; border-bottom:1px solid rgba(255,255,255,0.06); }
        .pdf-logo { width:36px; height:36px; background:linear-gradient(135deg,#8b5cf6,#6d28d9); border-radius:8px; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:14px; }
        .pdf-title-block h4 { color:#f1f5f9; font-size:0.9rem; font-weight:700; margin:0; }
        .pdf-title-block span { color:#64748b; font-size:0.72rem; }
        .pdf-row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.04); }
        .pdf-row .lbl { color:#64748b; font-size:0.8rem; }
        .pdf-row .val { color:#e2e8f0; font-size:0.8rem; font-weight:600; }
        .pdf-result { margin-top:16px; padding:12px 16px; background:rgba(139,92,246,0.1); border:1px solid rgba(139,92,246,0.25); border-radius:10px; }
        .pdf-result .res-label { color:#94a3b8; font-size:0.7rem; letter-spacing:1px; text-transform:uppercase; }
        .pdf-result .res-value { color:#a78bfa; font-size:1rem; font-weight:700; margin-top:4px; }
        .pdf-stamp { margin-top:16px; text-align:center; }
        .pdf-stamp span { display:inline-block; padding:6px 24px; border:2px solid rgba(139,92,246,0.5); border-radius:4px; color:#8b5cf6; font-size:0.8rem; font-weight:800; letter-spacing:2px; }
        .showcase-text h2 { font-size:2rem; font-weight:800; margin-bottom:16px; }
        .showcase-text p { color:#94a3b8; line-height:1.7; margin-bottom:20px; }
        .feature-pill { display:inline-flex; align-items:center; gap:8px; padding:8px 16px; background:rgba(139,92,246,0.1); border:1px solid rgba(139,92,246,0.2); border-radius:20px; color:#a78bfa; font-size:0.82rem; font-weight:600; margin:4px; }

        .stats-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:20px; margin-bottom:80px; }
        .stat-box { background:rgba(15,23,42,0.6); border:1px solid rgba(255,255,255,0.07); border-radius:18px; padding:30px 20px; text-align:center; backdrop-filter:blur(16px); transition:transform 0.3s ease, border-color 0.3s ease; }
        .stat-box:hover { transform:translateY(-6px); border-color:rgba(139,92,246,0.4); }
        .stat-num { font-size:2.4rem; font-weight:800; background:linear-gradient(135deg,#8b5cf6,#a78bfa); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; line-height:1; margin-bottom:8px; }
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
            background: radial-gradient(400px circle at var(--amx,50%) var(--amy,50%), rgba(139,92,246,0.12) 0%, transparent 70%);
            opacity: 0; transition: opacity 0.4s ease; pointer-events: none; z-index: 2;
        }
        .article-card:hover::before { opacity: 1; }
        .article-card:hover { transform: translateY(-8px); border-color: rgba(139,92,246,0.4); box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
        .article-img-wrap { position: relative; height: 200px; overflow: hidden; }
        .article-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease; }
        .article-card:hover .article-img { transform: scale(1.1); }
        
        .article-badge {
            position: absolute; top: 15px; left: 15px; background: rgba(139,92,246,0.9);
            color: #fff; font-size: 0.65rem; font-weight: 800; padding: 4px 10px; border-radius: 8px;
            text-transform: uppercase; letter-spacing: 1px; z-index: 3; backdrop-filter: blur(4px);
        }

        .article-content { padding: 24px; display: flex; flex-direction: column; flex-grow: 1; position: relative; z-index: 3; }
        .article-meta { font-size: 0.8rem; color: #64748b; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .article-meta i { color: #8b5cf6; }
        .article-title { font-size: 1.15rem; color: #f1f5f9; margin-bottom: 12px; font-weight: 700; line-height: 1.4; }
        .article-summary { font-size: 0.9rem; color: #94a3b8; margin-bottom: 20px; flex-grow: 1; line-height: 1.6; }
        .read-more {
            color: #8b5cf6; text-decoration: none; font-weight: 700; font-size: 0.85rem;
            display: inline-flex; align-items: center; gap: 8px; transition: gap 0.3s ease;
        }
        .read-more:hover { gap: 12px; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="page-header">
        <div class="hero-carousel">
            <div class="carousel-slide active" style="background-image:url('https://png.pngtree.com/thumb_back/fh260/background/20240418/pngtree-payroll-overtime-binder-data-finance-report-business-with-graph-analysis-in-image_15659967.jpg');"></div>
            <div class="carousel-overlay"></div>
        </div>
        <div class="container">
            <h1 class="animate-fade">Digital <span style="color:#8b5cf6;">Reports</span></h1>
            <p class="animate-fade" style="font-size:1.2rem;max-width:700px;margin:20px auto 0;color:#94a3b8;">Access your medical history anywhere. QR-coded digital certificates for every vaccination and test result, instantly verifiable.</p>
        </div>
    </div>
    <div class="container">
        <div class="stats-row animate-fade">
            <div class="stat-box"><div class="stat-num" data-target="100">0</div><div class="stat-label">% Digital Records</div></div>
            <div class="stat-box"><div class="stat-num" data-target="0">0</div><div class="stat-label">Paper Waste</div></div>
            <div class="stat-box"><div class="stat-num" data-target="5">0</div><div class="stat-label">sec Download Time</div></div>
            <div class="stat-box"><div class="stat-num" data-target="256">0</div><div class="stat-label">-bit Encrypted</div></div>
        </div>
        <div class="pdf-showcase animate-fade">
            <div class="pdf-mockup">
                <div class="pdf-header">
                    <div class="pdf-logo">M</div>
                    <div class="pdf-title-block"><h4>Vaccination Certificate</h4><span>MediCore Health System</span></div>
                </div>
                <div class="pdf-row"><span class="lbl">Patient Name</span><span class="val">Ali Hassan</span></div>
                <div class="pdf-row"><span class="lbl">Vaccine</span><span class="val">Pfizer-BioNTech</span></div>
                <div class="pdf-row"><span class="lbl">Date</span><span class="val">18 Feb 2026</span></div>
                <div class="pdf-row"><span class="lbl">Hospital</span><span class="val">City General Hospital</span></div>
                <div class="pdf-row"><span class="lbl">Report ID</span><span class="val">MC-A3F9D2C1</span></div>
                <div class="pdf-result"><div class="res-label">Outcome</div><div class="res-value">Vaccination Successful ✓</div></div>
                <div class="pdf-stamp"><span>COMPLETED</span></div>
            </div>
            <div class="showcase-text">
                <h2>Your Records, <span style="color:#8b5cf6;">Always Ready</span></h2>
                <p>Every appointment generates a professionally formatted PDF report with a unique Report ID. Download, share, or verify instantly — no paperwork needed.</p>
                <div>
                    <span class="feature-pill"><i class="fa-solid fa-qrcode"></i> QR Verified</span>
                    <span class="feature-pill"><i class="fa-solid fa-cloud-arrow-down"></i> Instant Download</span>
                    <span class="feature-pill"><i class="fa-solid fa-envelope"></i> Email Delivery</span>
                    <span class="feature-pill"><i class="fa-solid fa-leaf"></i> Paperless</span>
                </div>
            </div>
        </div>
        <div class="features-grid animate-fade">
            <div class="adv-card">
                <div class="adv-icon-wrap"><div class="icon-ring"></div><div class="icon-ring-2"></div><div class="adv-icon-bg"><i class="fa-solid fa-qrcode"></i></div></div>
                <h3>QR Code Verification</h3>
                <p>Every report comes with a unique, encrypted QR code. Authorities can scan it to instantly verify authenticity.</p>
                <span class="adv-tag"><i class="fa-solid fa-check" style="font-size:9px;"></i> Tamper-Proof</span>
            </div>
            <div class="adv-card">
                <div class="adv-icon-wrap"><div class="icon-ring"></div><div class="icon-ring-2"></div><div class="adv-icon-bg"><i class="fa-solid fa-cloud-arrow-down"></i></div></div>
                <h3>Secure Cloud Storage</h3>
                <p>Your records are stored safely in the cloud. Access your history, download PDFs, or share reports directly from your dashboard.</p>
                <span class="adv-tag"><i class="fa-solid fa-cloud" style="font-size:9px;"></i> Cloud Backed</span>
            </div>
            <div class="adv-card">
                <div class="adv-icon-wrap"><div class="icon-ring"></div><div class="icon-ring-2"></div><div class="adv-icon-bg"><i class="fa-solid fa-share-nodes"></i></div></div>
                <h3>Easy Sharing</h3>
                <p>Generate temporary secure links or send direct emails with one click to share records with doctors or authorities.</p>
                <span class="adv-tag"><i class="fa-solid fa-share" style="font-size:9px;"></i> One-Click Share</span>
            </div>
            <div class="adv-card">
                <div class="adv-icon-wrap"><div class="icon-ring"></div><div class="icon-ring-2"></div><div class="adv-icon-bg"><i class="fa-solid fa-leaf"></i></div></div>
                <h3>Eco-Friendly</h3>
                <p>Go paperless. Reduce waste and contribute to a greener planet by managing healthcare documentation entirely digitally.</p>
                <span class="adv-tag"><i class="fa-solid fa-leaf" style="font-size:9px;"></i> Zero Paper</span>
            </div>
        </div>
    </div>
    <section class="articles-section">
        <div class="container">
            <h2 class="animate-fade" style="margin-bottom:30px;border-left:4px solid #8b5cf6;padding-left:15px;">Latest <span style="color:#8b5cf6;">Health Insights</span></h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:30px;">
                <?php if (!empty($articles)): foreach($articles as $row):
                    $image = !empty($row['image']) ? $row['image'] : 'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?auto=format&fit=crop&w=400&q=80';
                    $date = date('M d, Y', strtotime($row['created_at']));
                    $summary = strlen($row['content']) > 120 ? substr($row['content'],0,120).'...' : $row['content'];
                    $url = !empty($row['url']) ? $row['url'] : '#';
                ?>
                <div class="article-card animate-fade">
                    <div class="article-img-wrap">
                        <span class="article-badge">Report</span>
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
