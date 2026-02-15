<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCore | Advanced Covid Solutions</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; padding: 40px 0; }
        .feature-item { text-align: center; padding: 0; overflow: hidden; }
        .feature-img { width: 100%; height: 200px; object-fit: cover; }
        .feature-content { padding: 30px; }
        .feature-icon { width: 60px; height: 60px; background: rgba(0, 242, 254, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: -60px auto 20px; position: relative; background: var(--secondary-bg); border: 1px solid var(--primary); color: var(--primary); font-size: 1.5rem; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="logo">
                    <i class="fa-solid fa-virus-covid"></i> MediCore
                </div>
                <button class="hamburger" id="hamburger">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="nav-links" id="navLinks">
                    <a href="index.php" class="btn btn-outline" style="border:none;">Overview</a>
                    <a href="#features" class="btn btn-outline" style="border:none;">Features</a>
                    <a href="login.php" class="btn btn-primary"><i class="fa-solid fa-right-to-bracket"></i> Portal Login</a>
                </div>
            </nav>
        </div>
    </header>

    <section class="hero">
        <!-- Hero Carousel -->
        <div class="hero-carousel">
            <div class="carousel-slide active" style="background-image: url('https://images.unsplash.com/photo-1584036561566-b452ae580dbc?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
            <div class="carousel-slide" style="background-image: url('https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
            <div class="carousel-slide" style="background-image: url('https://images.unsplash.com/photo-1516574187841-69301905a304?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
            <div class="carousel-overlay"></div>
        </div>

        <div class="container">
            <h1 class="animate-fade">Fighting the Pandemic<br><span style="background: linear-gradient(to right, var(--primary), var(--secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">With Technology</span></h1>
            <p class="animate-fade" style="animation-delay: 0.1s;">
                MediCore connects patients with world-class hospitals for rapid Covid-19 testing and vaccination scheduling. Real-time reports, digital tracking, and seamless integration.
            </p>
            <div class="animate-fade" style="animation-delay: 0.2s; display:flex; justify-content:center; gap:20px; flex-wrap: wrap;">
                <a href="register_patient.php" class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> Patient Registration</a>
                <a href="register_hospital.php" class="btn btn-outline"><i class="fa-solid fa-hospital"></i> Join as Hospital</a>
            </div>

            <div class="stats-grid" style="margin-top: 80px; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px;">
                <!-- Vaccines Administered -->
                <a href="vaccines.php" style="text-decoration: none; color: inherit; display: block;">
                    <div class="stat-card card-violet" style="display: block;">
                        <div class="stat-icon-bg"><i class="fa-solid fa-syringe"></i></div>
                        <div class="stat-content">
                            <div class="flex-between">
                                <span class="stat-label">Vaccines</span>
                                <span class="stat-trend positive"><i class="fa-solid fa-arrow-trend-up"></i> +45%</span>
                            </div>
                            <h2 class="stat-value">10M+</h2>
                            <div class="progress-info">
                                <div class="progress-bar-bg">
                                    <div class="progress-bar-fill" style="width: 85%; background: var(--primary);"></div>
                                </div>
                                <small class="text-muted">Doses Administered</small>
                            </div>
                        </div>
                        <div class="scurve-bg"></div>
                    </div>
                </a>

                <!-- System Uptime -->
                <a href="reliability.php" style="text-decoration: none; color: inherit; display: block;">
                    <div class="stat-card card-green" style="display: block;">
                        <div class="stat-icon-bg"><i class="fa-solid fa-server"></i></div>
                        <div class="stat-content">
                            <div class="flex-between">
                                <span class="stat-label">Reliability</span>
                                <span class="stat-trend positive">Stable</span>
                            </div>
                            <h2 class="stat-value">99.9%</h2>
                            <div class="progress-info">
                                <div class="progress-bar-bg">
                                    <div class="progress-bar-fill" style="width: 99%; background: var(--success);"></div>
                                </div>
                                <small class="text-muted">System Uptime</small>
                            </div>
                        </div>
                        <div class="scurve-bg"></div>
                    </div>
                </a>

                <!-- Partner Hospitals -->
                <a href="network.php" style="text-decoration: none; color: inherit; display: block;">
                    <div class="stat-card card-orange" style="display: block;">
                        <div class="stat-icon-bg"><i class="fa-solid fa-hospital"></i></div>
                        <div class="stat-content">
                            <div class="flex-between">
                                <span class="stat-label">Network</span>
                                <span class="stat-trend warning">Growing</span>
                            </div>
                            <h2 class="stat-value">500+</h2>
                            <div class="progress-info">
                                <div class="progress-bar-bg">
                                    <div class="progress-bar-fill" style="width: 60%; background: var(--warning);"></div>
                                </div>
                                <small class="text-muted">Partner Hospitals</small>
                            </div>
                        </div>
                        <div class="scurve-bg"></div>
                    </div>
                </a>
            </div>

            <h2 id="features" style="margin-top: 100px; margin-bottom: 40px; text-align: center;">Why Choose MediCore?</h2>
            <div class="feature-grid" style="gap: 40px;">
                
                <a href="instant_booking.php" style="text-decoration: none; color: inherit; display: block;">
                    <div class="feature-card card-accent">
                        <div class="feature-img-wrapper">
                            <img src="https://images.unsplash.com/photo-1638202993928-7267aad84c31?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Instant Booking" class="feature-img">
                        </div>
                        <div class="feature-content-box">
                            <div class="feature-icon-large" style="width: 60px; height: 60px; font-size: 1.8rem; margin-top: -60px; background: rgba(20,20,20,0.9);"><i class="fa-solid fa-bolt"></i></div>
                            <h3>Instant Booking</h3>
                            <p>Book appointment slots in real-time with no waiting. Our AI algorithms match you with the nearest available center instantly.</p>
                        </div>
                    </div>
                </a>

                <a href="digital_reports.php" style="text-decoration: none; color: inherit; display: block;">
                    <div class="feature-card card-violet">
                        <div class="feature-img-wrapper">
                            <img src="https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Digital Reports" class="feature-img">
                        </div>
                        <div class="feature-content-box">
                            <div class="feature-icon-large" style="width: 60px; height: 60px; font-size: 1.8rem; margin-top: -60px; background: rgba(20,20,20,0.9);"><i class="fa-solid fa-file-medical"></i></div>
                            <h3>Digital Reports</h3>
                            <p>Access, download, and share verifiable medical reports instantly via our secure cloud portal. No more physical paperwork.</p>
                        </div>
                    </div>
                </a>

                <a href="secure_safe.php" style="text-decoration: none; color: inherit; display: block;">
                    <div class="feature-card card-green">
                        <div class="feature-img-wrapper">
                            <img src="https://images.unsplash.com/photo-1555949963-ff9fe0c870eb?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Secure & Safe" class="feature-img">
                        </div>
                        <div class="feature-content-box">
                            <div class="feature-icon-large" style="width: 60px; height: 60px; font-size: 1.8rem; margin-top: -60px; background: rgba(20,20,20,0.9);"><i class="fa-solid fa-shield-virus"></i></div>
                            <h3>Secure & Safe</h3>
                            <p>Your medical data is encrypted with military-grade AES-256 security. Privacy is our top priority, ensuring your data stays yours.</p>
                        </div>
                    </div>
                </a>

            </div>
        </div>
    </section>

    <!-- Partners Section -->
    <section style="padding: 60px 0; background: rgba(0,0,0,0.2);">
        <div class="container" style="text-align: center;">
            <p style="color: var(--text-muted); margin-bottom: 30px; letter-spacing: 2px; text-transform: uppercase;">Trusted by Healthcare Leaders</p>
            <div style="display: flex; justify-content: center; gap: 40px; opacity: 0.5; flex-wrap: wrap;">
                <i class="fa-solid fa-hospital fa-3x"></i>
                <i class="fa-solid fa-user-doctor fa-3x"></i>
                <i class="fa-solid fa-dna fa-3x"></i>
                <i class="fa-solid fa-heart-pulse fa-3x"></i>
                <i class="fa-solid fa-leaf fa-3x"></i>
            </div>
        </div>
    </section>

    <footer>
        <div class="container" style="text-align: center; padding: 40px; color: var(--text-muted); border-top: 1px solid var(--glass-border);">
            <p>&copy; 2026 MediCore Systems. <i class="fa-solid fa-heart" style="color: var(--danger);"></i> For Humanity.</p>
        </div>
    </footer>
    <script src="script.js"></script>
</body>
</html>
