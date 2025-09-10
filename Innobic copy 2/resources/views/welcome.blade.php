<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset(path: 'assets/img/innobic.png') }}">
    <link rel="icon" type="image/png" href="{{ asset(path: 'assets/img/innobic.png') }}">
    <title>Innobic - Procurement Management System</title>
    
    <!-- Fonts and icons -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- AOS Animation Library -->
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #0f6db7;
            --primary-light: #4c94d6;
            --primary-dark: #094a80;
            --accent-color: #ff7b00;
            --background-light: #f8fafc;
            --text-dark: #333;
            --text-light: #fff;
        }
        
        body {
            font-family: 'Sarabun', sans-serif;
            color: var(--text-dark);
            overflow-x: hidden;
            background-color: var(--background-light);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover, .btn-primary:focus {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(15, 109, 183, 0.2);
        }
        
        .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(15, 109, 183, 0.2);
        }
        
        .btn-accent {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-accent:hover {
            background-color: #e66e00;
            border-color: #e66e00;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 123, 0, 0.2);
        }
        
        .hero-section {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
            color: var(--text-light);
            position: relative;
            overflow: hidden;
        }
        
        .hero-content {
            z-index: 10;
            position: relative;
        }
        
        .hero-img {
            max-width: 90%;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            transition: all 0.5s ease;
        }
        
        .hero-img:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.2);
        }
        
        .floating-element {
            position: absolute;
            opacity: 0.1;
            border-radius: 50%;
            background-color: white;
            filter: blur(10px);
            z-index: 1;
        }
        
        .floating-1 {
            width: 300px;
            height: 300px;
            top: -50px;
            left: -100px;
            animation: float 15s ease-in-out infinite;
        }
        
        .floating-2 {
            width: 200px;
            height: 200px;
            bottom: 100px;
            right: -50px;
            animation: float 18s ease-in-out infinite 2s;
        }
        
        .floating-3 {
            width: 150px;
            height: 150px;
            top: 50%;
            left: 15%;
            animation: float 12s ease-in-out infinite 1s;
        }
        
        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(15px, 15px) rotate(5deg); }
            50% { transform: translate(0, 25px) rotate(0deg); }
            75% { transform: translate(-15px, 10px) rotate(-5deg); }
            100% { transform: translate(0, 0) rotate(0deg); }
        }
        
        .nav-link {
            color: var(--text-light);
            position: relative;
            margin: 0 15px;
            font-weight: 500;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 0;
            background-color: var(--text-light);
            transition: width 0.3s ease;
        }
        
        .nav-link:hover::after {
            width: 100%;
        }
        
        .feature-card {
            border-radius: 12px;
            background: white;
            overflow: hidden;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.05);
            transition: all 0.4s ease;
            height: 100%;
            position: relative;
            z-index: 1;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 16px 32px rgba(15, 109, 183, 0.1);
        }
        
        .feature-icon {
            font-size: 48px;
            color: var(--primary-color);
            margin-bottom: 20px;
            transition: all 0.4s ease;
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.1);
            color: var(--accent-color);
        }
        
        .section-title {
            position: relative;
            display: inline-block;
            margin-bottom: 50px;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            width: 60px;
            height: 3px;
            background-color: var(--primary-color);
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
        }
        
        .highlight {
            color: var(--primary-color);
            font-weight: 700;
        }
        
        .cta-section {
            background: linear-gradient(45deg, var(--primary-dark) 0%, var(--primary-color) 100%);
            color: var(--text-light);
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }
        
        .progress-loader {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            width: 0%;
            background-color: var(--accent-color);
            z-index: 1000;
            transition: width 0.3s ease-out;
        }
        
        .features-grid {
            position: relative;
        }
        
        .features-grid::before {
            content: '';
            position: absolute;
            top: 0;
            left: 20%;
            width: 1px;
            height: 100%;
            background: linear-gradient(to bottom, transparent, var(--primary-light) 20%, var(--primary-light) 80%, transparent);
            opacity: 0.2;
        }
        
        .features-grid::after {
            content: '';
            position: absolute;
            top: 0;
            left: 80%;
            width: 1px;
            height: 100%;
            background: linear-gradient(to bottom, transparent, var(--primary-light) 20%, var(--primary-light) 80%, transparent);
            opacity: 0.2;
        }
        
        .typewriter h1 {
            overflow: hidden;
            border-right: 0.15em solid var(--accent-color);
            white-space: nowrap;
            margin: 0 auto;
            animation: 
                typing 3.5s steps(40, end),
                blink-caret 0.75s step-end infinite;
        }
        
        @keyframes typing {
            from { width: 0 }
            to { width: 100% }
        }
        
        @keyframes blink-caret {
            from, to { border-color: transparent }
            50% { border-color: var(--accent-color) }
        }
        
        .mouse-scroll {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 50px;
            border: 2px solid white;
            border-radius: 20px;
        }
        
        .mouse-scroll::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            width: 6px;
            height: 6px;
            background-color: white;
            border-radius: 50%;
            animation: scroll 2s infinite;
        }
        
        @keyframes scroll {
            0% { opacity: 1; top: 10px; }
            70% { opacity: 1; top: 30px; }
            100% { opacity: 0; top: 30px; }
        }
        
        .navbar-toggler {
            border-color: rgba(255, 255, 255, 0.5);
            color: white;
            padding: 0.5rem;
        }
        
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255, 255, 255, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background-color: rgba(9, 74, 128, 0.95);
                border-radius: 0.5rem;
                padding: 1rem;
                margin-top: 1rem;
                box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            }
            
            .navbar-nav {
                align-items: center;
            }
            
            .navbar-nav .nav-item {
                width: 100%;
                margin-bottom: 0.5rem;
                text-align: center;
            }
            
            .navbar-nav .nav-link {
                display: block;
                padding: 0.5rem 1rem;
                margin: 0.25rem 0;
            }
            
            .navbar-nav .btn {
                display: block;
                width: 100%;
                margin: 0.5rem 0;
            }
        }
            </style>
    </head>
<body>
    <!-- Progress Loader -->
    <div class="progress-loader" id="progressLoader"></div>
    
    <!-- Hero Section -->
    <section class="hero-section d-flex align-items-center">
        <!-- Floating Elements -->
        <div class="floating-element floating-1"></div>
        <div class="floating-element floating-2"></div>
        <div class="floating-element floating-3"></div>
        
        <div class="container">
            <nav class="navbar navbar-expand-lg py-4">
                <div class="container-fluid">
                    <a class="navbar-brand" href="#">
                        <img src="{{ asset(path: 'assets/img/innobic.png') }}" alt="Innobic Logo" height="40">
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item">
                                <a class="nav-link" href="#">Home</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#">Features</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#">About</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#">Contact</a>
                            </li>
            @if (Route::has('login'))
                    @auth
                                    <li class="nav-item">
                                        <a href="{{ url('/dashboard') }}" class="btn btn-outline-light ms-lg-3">Dashboard</a>
                                    </li>
                    @else
                                    <li class="nav-item">
                                        <a href="{{ route('login') }}" class="btn btn-outline-light ms-lg-3">Log in</a>
                                    </li>
                        @if (Route::has('register'))
                                        <li class="nav-item">
                                            <a href="{{ route('register') }}" class="btn btn-accent ms-lg-3">Register</a>
                                        </li>
                        @endif
                    @endauth
                            @endif
                        </ul>
                    </div>
                </div>
                </nav>
            
            <div class="row align-items-center py-5 hero-content">
                <div class="col-lg-6 mb-5 mb-lg-0" data-aos="fade-right" data-aos-duration="1000">
                    <div class="typewriter mb-4">
                        <h1 class="display-4 fw-bold mb-0">Procurement Management System</h1>
                    </div>
                    <p class="lead mb-4">A comprehensive platform designed to streamline your procurement processes, manage suppliers, and optimize your organization's purchasing workflow.</p>
                    <div class="d-flex gap-3 mt-5">
                        <a href="#features" class="btn btn-accent btn-lg px-4 me-md-2">Get Started</a>
                        <a href="#" class="btn btn-outline-light btn-lg px-4">Learn More</a>
                    </div>
                </div>
                <div class="col-lg-6 text-center" data-aos="fade-left" data-aos-duration="1000" data-aos-delay="200">
                    <img src="{{ asset(path: 'assets/img/innobic.png') }}" class="hero-img" alt="Dashboard Preview">
                </div>
            </div>
        </div>
        
        <!-- Scroll Indicator -->
        <div class="mouse-scroll"></div>
    </section>
    
    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container py-5">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="section-title display-5 fw-bold">Why Choose <span class="highlight">Innobic</span> for Procurement?</h2>
                <p class="lead text-muted">Discover how our system transforms your procurement operations</p>
            </div>
            
            <div class="row g-4 features-grid">
                <!-- Feature 1 -->
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="100">
                    <div class="feature-card p-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h3>Lightning Fast</h3>
                        <p class="text-muted">Process purchase requisitions and approvals with unprecedented speed.</p>
                    </div>
                </div>
                
                <!-- Feature 2 -->
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="200">
                    <div class="feature-card p-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Secure & Reliable</h3>
                        <p class="text-muted">Enterprise-grade security to keep your procurement data and vendor information protected.</p>
                    </div>
                </div>
                
                <!-- Feature 3 -->
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="300">
                    <div class="feature-card p-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Advanced Analytics</h3>
                        <p class="text-muted">Gain valuable insights into spending patterns and supplier performance metrics.</p>
                    </div>
                </div>
                
                <!-- Feature 4 -->
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="400">
                    <div class="feature-card p-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3>Mobile Access</h3>
                        <p class="text-muted">Approve purchase orders and track deliveries from anywhere on any device.</p>
                    </div>
                </div>
                
                <!-- Feature 5 -->
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="500">
                    <div class="feature-card p-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Vendor Management</h3>
                        <p class="text-muted">Centralize vendor information and streamline supplier onboarding and evaluation.</p>
                    </div>
                </div>
                
                <!-- Feature 6 -->
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="600">
                    <div class="feature-card p-4 text-center">
                        <div class="feature-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h3>Customizable Workflows</h3>
                        <p class="text-muted">Tailor approval processes and procurement policies to your organization's needs.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container text-center" data-aos="fade-up">
            <h2 class="display-5 fw-bold mb-4">Ready to Transform Your Procurement?</h2>
            <p class="lead mb-5">Join organizations that have optimized their purchasing processes and achieved significant cost savings with Innobic.</p>
            <a href="{{ route('register') }}" class="btn btn-accent btn-lg px-5">Sign Up Now</a>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <img src="{{ asset(path: 'assets/img/innobic.png') }}" alt="Innobic Logo" height="40" class="mb-4">
                    <p>Empowering organizations with innovative procurement solutions for enhanced efficiency and cost control.</p>
                </div>
                <div class="col-lg-2">
                    <h5>Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white-50">Home</a></li>
                        <li><a href="#" class="text-white-50">Features</a></li>
                        <li><a href="#" class="text-white-50">About</a></li>
                        <li><a href="#" class="text-white-50">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h5>Contact</h5>
                    <ul class="list-unstyled text-white-50">
                        <li><i class="fas fa-map-marker-alt me-2"></i> 123 Innovation Drive, Tech City</li>
                        <li><i class="fas fa-phone me-2"></i> (123) 456-7890</li>
                        <li><i class="fas fa-envelope me-2"></i> info@innobic.com</li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h5>Follow Us</h5>
                    <div class="d-flex gap-3 mt-3">
                        <a href="#" class="text-white"><i class="fab fa-facebook-f fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin-in fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram fa-lg"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="small text-white-50">&copy; {{ date('Y') }} Innobic. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="small text-white-50">Terms | Privacy Policy | Cookies</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    
    <script>
        // Initialize AOS animation library
        AOS.init({
            once: true
        });
        
        // Progress bar loader
        window.addEventListener('scroll', function() {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = (winScroll / height) * 100;
            document.getElementById("progressLoader").style.width = scrolled + "%";
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
    </body>
</html>
