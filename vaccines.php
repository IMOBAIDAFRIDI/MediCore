<?php
session_start();
require_once 'config.php';
require_once 'db_connect_pdo.php';
try {
    $stmt = $pdo->query("SELECT * FROM articles ORDER BY created_at DESC LIMIT 3");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->query("SELECT * FROM vaccines ORDER BY name ASC");
    $vaccines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $articles = []; $vaccines = []; }
$page_title = "Vaccines";
include 'header.php';
?>
<style>
    .page-header { background-size:cover; background-position:center; padding:90px 20px; min-height:calc(85vh + 40px); display:flex; align-items:center; justify-content:center; text-align:center; margin-bottom:60px; position:relative; overflow:hidden; }

    /* Stats */
    .stats-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:20px; margin-bottom:80px; }
    .stat-box { background:rgba(15,23,42,0.6); border:1px solid rgba(255,255,255,0.07); border-radius:18px; padding:30px 20px; text-align:center; backdrop-filter:blur(16px); transition:transform 0.3s ease, border-color 0.3s ease; }
    .stat-box:hover { transform:translateY(-6px); border-color:rgba(139,92,246,0.4); }
    .stat-num { font-size:2.4rem; font-weight:800; background:linear-gradient(135deg,#8b5cf6,#a78bfa); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; line-height:1; margin-bottom:8px; }
    .stat-label { color:#64748b; font-size:0.82rem; font-weight:500; }

    /* How It Works */
    .info-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:24px; margin-bottom:90px; }
    .info-card {
        position:relative; background:rgba(15,23,42,0.6); border:1px solid rgba(255,255,255,0.08);
        border-radius:22px; padding:38px 26px; text-align:center; overflow:hidden;
        transition:transform 0.4s cubic-bezier(.23,1,.32,1), border-color 0.4s ease, box-shadow 0.4s ease;
        backdrop-filter:blur(20px); cursor:default;
    }
    .info-card::before { content:''; position:absolute; inset:0; border-radius:22px; background:radial-gradient(500px circle at var(--mx,50%) var(--my,50%), rgba(139,92,246,0.12) 0%, transparent 60%); opacity:0; transition:opacity 0.4s ease; pointer-events:none; }
    .info-card:hover::before { opacity:1; }
    .info-card:hover { transform:translateY(-10px) scale(1.02); border-color:rgba(139,92,246,0.45); box-shadow:0 20px 50px rgba(139,92,246,0.18); }
    .info-card::after { content:''; position:absolute; top:0; left:10%; right:10%; height:2px; background:linear-gradient(90deg,transparent,rgba(139,92,246,0.8),transparent); opacity:0; transition:opacity 0.4s ease; }
    .info-card:hover::after { opacity:1; }
    .info-icon-wrap { position:relative; width:90px; height:90px; margin:0 auto 24px; display:flex; align-items:center; justify-content:center; }
    .icon-ring { position:absolute; inset:0; border-radius:50%; border:1.5px solid rgba(139,92,246,0.3); animation:spin-ring 8s linear infinite; }
    .icon-ring-2 { position:absolute; inset:8px; border-radius:50%; border:1px dashed rgba(139,92,246,0.2); animation:spin-ring 12s linear infinite reverse; }
    @keyframes spin-ring { to { transform:rotate(360deg); } }
    .info-icon-bg { width:72px; height:72px; border-radius:50%; background:linear-gradient(135deg,rgba(139,92,246,0.15),rgba(139,92,246,0.05)); display:flex; align-items:center; justify-content:center; font-size:1.9rem; color:#a78bfa; position:relative; z-index:1; transition:transform 0.4s ease; }
    .info-card:hover .info-icon-bg { transform:scale(1.12) rotate(-5deg); }
    .info-card h4 { color:#f1f5f9; font-size:1.1rem; font-weight:700; margin-bottom:10px; }
    .info-card p { color:#94a3b8; font-size:0.88rem; line-height:1.6; }

    /* Vaccine Cards */
    .vaccines-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:28px; margin-bottom:90px; }
    @media(max-width:992px){ .vaccines-grid { grid-template-columns:repeat(2,1fr); } }
    @media(max-width:600px){ .vaccines-grid { grid-template-columns:1fr; } }

    .vaccine-card {
        position:relative; background:rgba(15,23,42,0.65); border:1px solid rgba(255,255,255,0.08);
        border-radius:22px; overflow:hidden; cursor:default;
        transition:transform 0.4s cubic-bezier(.23,1,.32,1), border-color 0.4s ease, box-shadow 0.4s ease;
        backdrop-filter:blur(20px);
    }
    .vaccine-card::before { content:''; position:absolute; inset:0; border-radius:22px; background:radial-gradient(600px circle at var(--mx,50%) var(--my,50%), rgba(139,92,246,0.12) 0%, transparent 60%); opacity:0; transition:opacity 0.4s ease; pointer-events:none; z-index:1; }
    .vaccine-card:hover::before { opacity:1; }
    .vaccine-card:hover { transform:translateY(-12px) scale(1.02); border-color:rgba(139,92,246,0.45); box-shadow:0 24px 60px rgba(139,92,246,0.2); }
    /* Top shimmer */
    .vaccine-card::after { content:''; position:absolute; top:0; left:10%; right:10%; height:2px; background:linear-gradient(90deg,transparent,rgba(139,92,246,0.8),transparent); opacity:0; transition:opacity 0.4s ease; z-index:2; }
    .vaccine-card:hover::after { opacity:1; }

    .vaccine-img-wrap { position:relative; overflow:hidden; background:rgba(255,255,255,0.02); border-bottom:1px solid rgba(255,255,255,0.06); }
    .vaccine-card-img { width:100%; height:200px; object-fit:contain; padding:20px; transition:transform 0.5s ease; }
    .vaccine-card:hover .vaccine-card-img { transform:scale(1.08); }

    /* Floating WHO badge on image */
    .who-float { position:absolute; top:12px; right:12px; background:rgba(139,92,246,0.9); color:#fff; font-size:0.65rem; font-weight:800; letter-spacing:0.8px; padding:4px 10px; border-radius:20px; text-transform:uppercase; z-index:3; backdrop-filter:blur(8px); }

    .vaccine-card-body { padding:24px; position:relative; z-index:1; }
    .vaccine-card-body h3 { color:#f1f5f9; font-size:1.15rem; font-weight:700; margin-bottom:10px; }
    .vaccine-card-body p { color:#94a3b8; line-height:1.65; font-size:0.88rem; margin-bottom:18px; }
    .vaccine-footer { display:flex; align-items:center; justify-content:space-between; }
    .vaccine-badge { display:inline-flex; align-items:center; gap:6px; padding:5px 12px; border-radius:20px; font-size:0.7rem; font-weight:700; letter-spacing:0.5px; text-transform:uppercase; background:rgba(139,92,246,0.12); color:#a78bfa; border:1px solid rgba(139,92,246,0.25); }
    .book-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; border-radius:20px; font-size:0.78rem; font-weight:700; background:linear-gradient(135deg,#7c3aed,#8b5cf6); color:#fff; text-decoration:none; transition:transform 0.2s ease, box-shadow 0.2s ease; }
    .book-btn:hover { transform:scale(1.05); box-shadow:0 6px 20px rgba(139,92,246,0.4); }

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
        color: #a78bfa; text-decoration: none; font-weight: 700; font-size: 0.85rem;
        display: inline-flex; align-items: center; gap: 8px; transition: gap 0.3s ease;
    }
    .read-more:hover { gap: 12px; }
</style>

<div class="page-header">
    <div class="hero-carousel">
        <div class="carousel-slide active" style="background-image:url('https://images.unsplash.com/photo-1615631648086-325025c9e51e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80');"></div>
        <div class="carousel-overlay"></div>
    </div>
    <div class="container">
        <h1 class="animate-fade">Available <span style="color:#a78bfa;">Vaccines</span></h1>
        <p class="animate-fade" style="font-size:1.2rem;max-width:700px;margin:20px auto 30px;color:#94a3b8;">
            Comprehensive vaccination programs with WHO-approved vaccines. Stay protected with our trusted healthcare network.
        </p>
        <a href="register_patient.php" class="btn btn-primary animate-fade"><i class="fa-solid fa-calendar-check"></i> Book Vaccination</a>
    </div>
</div>

<div class="container">
    <!-- Stats -->
    <div class="stats-row animate-fade">
        <div class="stat-box"><div class="stat-num" data-target="<?= count($vaccines) ?>">0</div><div class="stat-label">Vaccines Available</div></div>
        <div class="stat-box"><div class="stat-num" data-target="100">0</div><div class="stat-label">% WHO Approved</div></div>
        <div class="stat-box"><div class="stat-num" data-target="50000">0</div><div class="stat-label">+ Doses Administered</div></div>
        <div class="stat-box"><div class="stat-num" data-target="99">0</div><div class="stat-label">% Efficacy Rate</div></div>
    </div>

    <!-- How It Works -->
    <h2 class="animate-fade" style="text-align:center;margin-bottom:12px;">How It <span style="color:#a78bfa;">Works</span></h2>
    <p class="animate-fade" style="text-align:center;color:#64748b;max-width:600px;margin:0 auto 44px;">Simple steps to get vaccinated through our platform.</p>
    <div class="info-grid animate-fade">
        <div class="info-card">
            <div class="info-icon-wrap"><div class="icon-ring"></div><div class="icon-ring-2"></div><div class="info-icon-bg"><i class="fa-solid fa-user-plus"></i></div></div>
            <h4>1. Register</h4>
            <p>Create your patient account on MediCore with your basic information and medical history.</p>
        </div>
        <div class="info-card">
            <div class="info-icon-wrap"><div class="icon-ring"></div><div class="icon-ring-2"></div><div class="info-icon-bg"><i class="fa-solid fa-hospital"></i></div></div>
            <h4>2. Choose Hospital</h4>
            <p>Select from our network of verified hospitals offering vaccination services near you.</p>
        </div>
        <div class="info-card">
            <div class="info-icon-wrap"><div class="icon-ring"></div><div class="icon-ring-2"></div><div class="info-icon-bg"><i class="fa-solid fa-syringe"></i></div></div>
            <h4>3. Select Vaccine</h4>
            <p>Choose your preferred vaccine from the available options at your selected hospital.</p>
        </div>
        <div class="info-card">
            <div class="info-icon-wrap"><div class="icon-ring"></div><div class="icon-ring-2"></div><div class="info-icon-bg"><i class="fa-solid fa-file-medical"></i></div></div>
            <h4>4. Get Report</h4>
            <p>Receive your digital vaccination certificate and medical report instantly after the procedure.</p>
        </div>
    </div>

    <!-- Vaccine Cards -->
    <h2 class="animate-fade" style="text-align:center;margin-bottom:12px;">Our <span style="color:#a78bfa;">Vaccine Portfolio</span></h2>
    <p class="animate-fade" style="text-align:center;color:#64748b;max-width:600px;margin:0 auto 44px;">All vaccines available across our partner hospitals.</p>

    <div class="vaccines-grid animate-fade">
        <?php if (!empty($vaccines)): foreach($vaccines as $v):
            $img  = !empty($v['image']) ? $v['image'] : 'https://cdn-icons-png.flaticon.com/512/2966/2966327.png';
            $name = htmlspecialchars($v['name']);
            $desc = htmlspecialchars($v['description']);
        ?>
        <div class="vaccine-card">
            <div class="vaccine-img-wrap">
                <img src="<?= $img ?>" alt="<?= $name ?>" class="vaccine-card-img">
                <span class="who-float"><i class="fa-solid fa-check"></i> WHO</span>
            </div>
            <div class="vaccine-card-body">
                <h3><?= $name ?></h3>
                <p><?= $desc ?></p>
                <div class="vaccine-footer">
                    <span class="vaccine-badge"><i class="fa-solid fa-shield-halved" style="font-size:9px;"></i> Approved</span>
                    <a href="register_patient.php" class="book-btn"><i class="fa-solid fa-calendar-plus"></i> Book</a>
                </div>
            </div>
        </div>
        <?php endforeach; else: ?>
        <div style="grid-column:1/-1;text-align:center;padding:60px 20px;">
            <i class="fa-solid fa-syringe" style="font-size:3rem;color:#334155;margin-bottom:16px;display:block;"></i>
            <p style="color:#64748b;">No vaccines available at the moment. Please check back later.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Articles -->
<section class="articles-section">
    <div class="container">
        <h2 class="animate-fade" style="margin-bottom:30px;border-left:4px solid #a78bfa;padding-left:15px;">Latest <span style="color:#a78bfa;">Updates</span></h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:30px;">
            <?php if (!empty($articles)): foreach($articles as $row):
                $image = !empty($row['image']) ? $row['image'] : 'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?auto=format&fit=crop&w=400&q=80';
                $date = date('M d, Y', strtotime($row['created_at']));
                $summary = strlen($row['content']) > 120 ? substr($row['content'],0,120).'...' : $row['content'];
                $url = !empty($row['url']) ? $row['url'] : '#';
            ?>
            <div class="article-card animate-fade">
                <div class="article-img-wrap">
                    <span class="article-badge">Health</span>
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
// Spotlight for vaccine + info + article cards
document.querySelectorAll('.vaccine-card, .info-card, .article-card').forEach(card => {
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
