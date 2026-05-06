<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Great Bus - Book Bus Tickets Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* CSS Variables for Light/Dark Mode - ORANGE THEME */
        :root {
            --bg-gradient-start: #FF6B35;
            --bg-gradient-end: #F7931E;
            --card-bg: rgba(255, 255, 255, 0.95);
            --text-primary: #333;
            --text-secondary: #666;
            --btn-primary: linear-gradient(135deg, #FF6B35, #F7931E);
            --btn-secondary: linear-gradient(135deg, #FF8C42, #FF5722);
            --feature-icon: #FF6B35;
            --footer-bg: rgba(0, 0, 0, 0.5);
            --footer-text: white;
            --shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            --border-glow: rgba(255, 107, 53, 0.3);
            --particle-color: rgba(255, 107, 53, 0.3);
        }

        body.dark {
            --bg-gradient-start: #0a0a1a;
            --bg-gradient-end: #1a1a2e;
            --card-bg: rgba(30, 30, 46, 0.95);
            --text-primary: #f0f0f0;
            --text-secondary: #a0a0a0;
            --btn-primary: linear-gradient(135deg, #FF6B35, #F7931E);
            --btn-secondary: linear-gradient(135deg, #FF8C42, #FF5722);
            --feature-icon: #FF8C42;
            --footer-bg: rgba(0, 0, 0, 0.7);
            --footer-text: #ccc;
            --shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            --border-glow: rgba(255, 107, 53, 0.5);
            --particle-color: rgba(255, 107, 53, 0.2);
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            transition: background 0.3s ease, color 0.3s ease;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background Container */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
            background: linear-gradient(135deg, var(--bg-gradient-start), var(--bg-gradient-end));
            transition: background 0.3s ease;
        }

        /* Moving Gradient Animation */
        .moving-gradient {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, 
                rgba(255,107,53,0.3), 
                rgba(247,147,30,0.3),
                rgba(255,107,53,0.1));
            animation: moveGradient 10s ease infinite;
        }

        @keyframes moveGradient {
            0% { transform: translateX(-10%) translateY(-10%) scale(1); opacity: 0.5; }
            50% { transform: translateX(10%) translateY(10%) scale(1.2); opacity: 0.3; }
            100% { transform: translateX(-10%) translateY(-10%) scale(1); opacity: 0.5; }
        }

        /* Floating Buses Animation */
        .floating-bus {
            position: absolute;
            font-size: 3rem;
            color: rgba(255, 255, 255, 0.15);
            animation: floatBus 20s infinite linear;
            pointer-events: none;
        }

        @keyframes floatBus {
            0% {
                transform: translateX(-10%) translateY(0vh) rotate(0deg);
                opacity: 0;
            }
            10% { opacity: 0.15; }
            90% { opacity: 0.15; }
            100% {
                transform: translateX(110vw) translateY(-10vh) rotate(5deg);
                opacity: 0;
            }
        }

        .floating-bus:nth-child(1) { top: 10%; left: -5%; animation-duration: 18s; font-size: 2.5rem; }
        .floating-bus:nth-child(2) { top: 30%; left: -8%; animation-duration: 25s; animation-delay: 2s; font-size: 3rem; }
        .floating-bus:nth-child(3) { top: 50%; left: -3%; animation-duration: 22s; animation-delay: 5s; font-size: 2rem; }
        .floating-bus:nth-child(4) { top: 70%; left: -10%; animation-duration: 28s; animation-delay: 1s; font-size: 3.5rem; }
        .floating-bus:nth-child(5) { top: 85%; left: -6%; animation-duration: 20s; animation-delay: 8s; font-size: 2.2rem; }
        .floating-bus:nth-child(6) { top: 20%; left: -12%; animation-duration: 32s; animation-delay: 3s; font-size: 2.8rem; }

        /* Floating Particles */
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            animation: floatParticle 15s infinite linear;
            pointer-events: none;
        }

        @keyframes floatParticle {
            0% {
                transform: translateY(100vh) translateX(0) rotate(0deg);
                opacity: 0;
            }
            10% { opacity: 0.5; }
            90% { opacity: 0.5; }
            100% {
                transform: translateY(-10vh) translateX(100px) rotate(360deg);
                opacity: 0;
            }
        }

        /* Circles Animation */
        .circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            animation: pulse 8s infinite ease-in-out;
            pointer-events: none;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.1;
            }
            50% {
                transform: scale(1.5);
                opacity: 0.05;
            }
            100% {
                transform: scale(1);
                opacity: 0.1;
            }
        }

        .circle:nth-child(1) { width: 300px; height: 300px; top: -100px; left: -100px; animation-duration: 12s; }
        .circle:nth-child(2) { width: 500px; height: 500px; bottom: -200px; right: -150px; animation-duration: 15s; }
        .circle:nth-child(3) { width: 200px; height: 200px; top: 50%; right: 10%; animation-duration: 10s; }
        .circle:nth-child(4) { width: 400px; height: 400px; bottom: 20%; left: -150px; animation-duration: 18s; }
        .circle:nth-child(5) { width: 150px; height: 150px; top: 20%; left: 30%; animation-duration: 9s; }

        /* Theme Toggle Button */
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1001;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 50px;
            padding: 10px 20px;
            color: white;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .theme-toggle:hover {
            transform: scale(1.05);
            background: rgba(0, 0, 0, 0.8);
        }

        /* Splash Screen - IMPROVED LOGO ANIMATION */
        .splash-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #FF6B35, #F7931E);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.5s ease, visibility 0.5s ease;
            cursor: pointer;
        }

        body.dark .splash-screen {
            background: linear-gradient(135deg, #1a1a2e, #0f0f1a);
        }

        .splash-content {
            text-align: center;
            animation: zoomIn 0.8s ease;
        }

        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.7); }
            to { opacity: 1; transform: scale(1); }
        }

        /* IMPROVED ANIMATED LOGO */
        .splash-logo {
            position: relative;
            width: 140px;
            height: 140px;
            margin: 0 auto;
            animation: floatLogo 2s ease-in-out infinite, glowPulse 1.5s ease-in-out infinite;
        }

        @keyframes floatLogo {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(5deg); }
        }

        @keyframes glowPulse {
            0%, 100% { filter: drop-shadow(0 0 5px rgba(255,215,0,0.5)); }
            50% { filter: drop-shadow(0 0 20px rgba(255,215,0,0.8)); }
        }

        /* Rotating ring around logo */
        .splash-logo::before {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            border-radius: 50%;
            background: conic-gradient(from 0deg, #FFD700, #FF6B35, #F7931E, #FFD700);
            animation: rotateRing 3s linear infinite;
            z-index: -1;
        }

        @keyframes rotateRing {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Inner glow */
        .splash-logo::after {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,215,0,0.3), transparent);
            animation: innerGlow 2s ease-in-out infinite;
        }

        @keyframes innerGlow {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.05); }
        }

        .splash-logo svg {
            width: 100%;
            height: 100%;
            filter: drop-shadow(0 8px 20px rgba(0,0,0,0.3));
            animation: logoSpin 4s linear infinite;
        }

        @keyframes logoSpin {
            0% { transform: rotate(0deg); }
            25% { transform: rotate(5deg); }
            75% { transform: rotate(-5deg); }
            100% { transform: rotate(0deg); }
        }

        /* Animated title */
        .splash-title {
            font-size: 2.5rem;
            color: white;
            margin-top: 1rem;
            font-weight: 800;
            letter-spacing: 2px;
            background: linear-gradient(135deg, #fff, #FFD700);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            animation: titleGlow 1.5s ease-in-out infinite, slideUp 0.5s ease;
        }

        @keyframes titleGlow {
            0%, 100% { text-shadow: 0 0 0px rgba(255,215,0,0); }
            50% { text-shadow: 0 0 20px rgba(255,215,0,0.5); }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Animated subtitle */
        .splash-sub {
            color: rgba(255,255,255,0.9);
            margin-top: 0.5rem;
            font-weight: 500;
            animation: fadeInOut 2s ease-in-out infinite;
        }

        @keyframes fadeInOut {
            0%, 100% { opacity: 0.7; }
            50% { opacity: 1; }
        }

        /* Modern loader */
        .loader {
            width: 50px;
            height: 50px;
            margin: 2rem auto 0;
            position: relative;
        }

        .loader::before,
        .loader::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            animation: ripple 1.8s ease-out infinite;
        }

        .loader::before {
            width: 100%;
            height: 100%;
            background: rgba(255,215,0,0.4);
            animation-delay: 0s;
        }

        .loader::after {
            width: 100%;
            height: 100%;
            background: rgba(255,215,0,0.2);
            animation-delay: 0.9s;
        }

        @keyframes ripple {
            0% {
                transform: scale(0.5);
                opacity: 0.8;
            }
            100% {
                transform: scale(2);
                opacity: 0;
            }
        }

        /* Click instruction */
        .click-instruction {
            margin-top: 1rem;
            font-size: 0.7rem;
            color: rgba(255,255,255,0.8);
            animation: bounceInstruction 2s ease-in-out infinite;
        }

        @keyframes bounceInstruction {
            0%, 100% { transform: translateY(0); opacity: 0.6; }
            50% { transform: translateY(5px); opacity: 1; }
        }

        /* Main Container */
        .container {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            opacity: 0;
            animation: fadeIn 0.8s ease forwards;
            animation-delay: 1.5s;
        }

        @keyframes fadeIn {
            to { opacity: 1; }
        }

        /* Welcome Card */
        .welcome-card {
            background: var(--card-bg);
            border-radius: 48px;
            padding: 3rem;
            text-align: center;
            max-width: 550px;
            width: 100%;
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 107, 53, 0.3);
        }

        .welcome-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 70px rgba(0,0,0,0.4);
            border-color: #FF6B35;
        }

        /* MAIN LOGO - IMPROVED DESIGN */
        .main-logo {
            width: 110px;
            height: 110px;
            margin: 0 auto 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            animation: mainLogoFloat 3s ease-in-out infinite;
        }

        @keyframes mainLogoFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .main-logo:hover {
            transform: scale(1.1) rotate(5deg);
        }

        .main-logo svg {
            width: 100%;
            height: 100%;
            filter: drop-shadow(0 4px 10px rgba(0,0,0,0.2));
            transition: all 0.3s ease;
        }

        .main-logo:hover svg {
            filter: drop-shadow(0 8px 20px rgba(255,107,53,0.5));
        }

        /* Pulsing ring around main logo */
        .main-logo::before {
            content: '';
            position: absolute;
            top: -15px;
            left: -15px;
            right: -15px;
            bottom: -15px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,107,53,0.2), transparent);
            animation: pulseRing 2s ease-out infinite;
            z-index: -1;
        }

        @keyframes pulseRing {
            0% {
                transform: scale(0.8);
                opacity: 0.5;
            }
            100% {
                transform: scale(1.5);
                opacity: 0;
            }
        }

        h1 {
            color: var(--text-primary);
            font-size: 2rem;
            margin-bottom: 0.5rem;
            font-weight: 800;
            animation: titleFadeIn 0.5s ease;
        }

        @keyframes titleFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .subtitle {
            color: var(--text-secondary);
            margin-bottom: 2rem;
            font-weight: 500;
        }

        /* Button Group */
        .btn-group {
            display: flex;
            gap: 1rem;
            flex-direction: column;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 60px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            border: none;
            animation: buttonSlideUp 0.5s ease forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        .btn:nth-child(1) { animation-delay: 0.1s; }
        .btn:nth-child(2) { animation-delay: 0.2s; }

        @keyframes buttonSlideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary {
            background: var(--btn-primary);
            color: white;
        }

        .btn-secondary {
            background: var(--btn-secondary);
            color: white;
        }

        .btn:hover {
            transform: translateY(-3px);
            filter: brightness(1.05);
        }

        .btn-primary:hover {
            box-shadow: 0 10px 25px rgba(255, 107, 53, 0.4);
        }

        .btn-secondary:hover {
            box-shadow: 0 10px 25px rgba(255, 140, 66, 0.4);
        }

        .btn:active {
            transform: translateY(2px);
        }

        /* Features */
        .features {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .feature {
            text-align: center;
            font-size: 0.85rem;
            color: var(--text-secondary);
            transition: transform 0.3s ease;
            animation: featureFadeIn 0.5s ease forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        .feature:nth-child(1) { animation-delay: 0.3s; }
        .feature:nth-child(2) { animation-delay: 0.4s; }
        .feature:nth-child(3) { animation-delay: 0.5s; }
        .feature:nth-child(4) { animation-delay: 0.6s; }

        @keyframes featureFadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .feature:hover {
            transform: translateY(-5px);
        }

        .feature i {
            font-size: 1.8rem;
            color: var(--feature-icon);
            margin-bottom: 0.5rem;
            display: block;
            transition: all 0.3s ease;
        }

        .feature:hover i {
            transform: scale(1.1);
        }

        /* Footer */
        footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--footer-bg);
            backdrop-filter: blur(10px);
            text-align: center;
            padding: 0.8rem;
            color: var(--footer-text);
            font-size: 0.8rem;
            font-weight: 500;
            z-index: 10;
        }

        /* Responsive for Mobile */
        @media (max-width: 550px) {
            .welcome-card { padding: 1.8rem; margin: 1rem; }
            h1 { font-size: 1.5rem; }
            .btn { padding: 0.8rem 1.5rem; font-size: 1rem; }
            .features { gap: 1rem; }
            .feature i { font-size: 1.4rem; }
            .theme-toggle { padding: 6px 12px; font-size: 0.8rem; top: 15px; right: 15px; }
            
            /* Mobile Logo Adjustments */
            .splash-logo { width: 100px; height: 100px; }
            .splash-title { font-size: 1.8rem; }
            .main-logo { width: 80px; height: 80px; }
            
            /* Mobile Animations - Optimized */
            @keyframes floatLogo {
                0%, 100% { transform: translateY(0px); }
                50% { transform: translateY(-8px); }
            }
            
            .splash-logo::before {
                top: -8px;
                left: -8px;
                right: -8px;
                bottom: -8px;
            }
        }

        /* Tablet Responsive */
        @media (min-width: 551px) and (max-width: 768px) {
            .splash-logo { width: 120px; height: 120px; }
            .main-logo { width: 95px; height: 95px; }
        }
    </style>
</head>
<body>

<!-- Animated Background -->
<div class="animated-bg">
    <div class="moving-gradient"></div>
    
    <!-- Floating Circles -->
    <div class="circle"></div>
    <div class="circle"></div>
    <div class="circle"></div>
    <div class="circle"></div>
    <div class="circle"></div>
    
    <!-- Floating Buses -->
    <div class="floating-bus"><i class="fas fa-bus"></i></div>
    <div class="floating-bus"><i class="fas fa-bus-simple"></i></div>
    <div class="floating-bus"><i class="fas fa-bus"></i></div>
    <div class="floating-bus"><i class="fas fa-van-shuttle"></i></div>
    <div class="floating-bus"><i class="fas fa-bus"></i></div>
    <div class="floating-bus"><i class="fas fa-bus-simple"></i></div>
</div>

<script>
    // Generate floating particles dynamically
    (function createParticles() {
        const bg = document.querySelector('.animated-bg');
        for (let i = 0; i < 50; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.width = Math.random() * 6 + 2 + 'px';
            particle.style.height = particle.style.width;
            particle.style.animationDuration = Math.random() * 20 + 10 + 's';
            particle.style.animationDelay = Math.random() * 10 + 's';
            particle.style.background = `rgba(255, ${Math.random() * 100 + 100}, ${Math.random() * 50}, ${Math.random() * 0.3 + 0.2})`;
            bg.appendChild(particle);
        }
    })();
</script>

<!-- Theme Toggle Button -->
<button class="theme-toggle" id="themeToggle">
    <i class="fas fa-moon"></i>
    <span>Dark Mode</span>
</button>

<!-- Splash Screen - IMPROVED ANIMATION -->
<div class="splash-screen" id="splash">
    <div class="splash-content">
        <div class="splash-logo">
            <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="48" stroke="#FFD700" stroke-width="3" fill="#1a1a2e"/>
                <rect x="25" y="35" width="50" height="35" rx="5" fill="#FF6B35"/>
                <rect x="20" y="30" width="60" height="8" rx="3" fill="#F7931E"/>
                <circle cx="35" cy="70" r="8" fill="#333" stroke="#FFD700" stroke-width="2"/>
                <circle cx="65" cy="70" r="8" fill="#333" stroke="#FFD700" stroke-width="2"/>
                <rect x="45" y="42" width="10" height="20" rx="2" fill="#333"/>
                <text x="50" y="62" text-anchor="middle" fill="#FFD700" font-size="12" font-weight="bold">GB</text>
            </svg>
        </div>
        <div class="splash-title">Great Bus</div>
        <div class="splash-sub">Your Journey, Our Priority</div>
        <div class="loader"></div>
        <div class="click-instruction">✨ Tap anywhere to continue ✨</div>
    </div>
</div>

<!-- Main Container -->
<div class="container">
    <div class="welcome-card">
        <div class="main-logo">
            <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="48" stroke="url(#grad)" stroke-width="2" fill="none"/>
                <defs>
                    <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#FF6B35;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#F7931E;stop-opacity:1" />
                    </linearGradient>
                </defs>
                <rect x="25" y="35" width="50" height="35" rx="5" fill="#FF6B35"/>
                <rect x="20" y="30" width="60" height="8" rx="3" fill="#F7931E"/>
                <circle cx="35" cy="70" r="8" fill="#fff" stroke="#FF6B35" stroke-width="2"/>
                <circle cx="65" cy="70" r="8" fill="#fff" stroke="#FF6B35" stroke-width="2"/>
                <rect x="45" y="42" width="10" height="20" rx="2" fill="#fff"/>
                <text x="50" y="62" text-anchor="middle" fill="#1a1a2e" font-size="12" font-weight="bold">GB</text>
            </svg>
        </div>
        <h1>Welcome to Great Bus</h1>
        <p class="subtitle">Book your bus tickets easily & travel comfortably</p>
        
        <div class="btn-group">
            <a href="login.php?type=user" class="btn btn-primary">
                <i class="fas fa-user"></i> User Login
            </a>
            <a href="signup.php" class="btn btn-secondary">
                <i class="fas fa-user-plus"></i> Sign Up
            </a>
        </div>
        
        <div class="features">
            <div class="feature">
                <i class="fas fa-ticket-alt"></i>
                <span>Easy Booking</span>
            </div>
            <div class="feature">
                <i class="fas fa-wifi"></i>
                <span>Free WiFi</span>
            </div>
            <div class="feature">
                <i class="fas fa-clock"></i>
                <span>24/7 Support</span>
            </div>
            <div class="feature">
                <i class="fas fa-shield-alt"></i>
                <span>Secure Payment</span>
            </div>
        </div>
    </div>
</div>

<footer>
    <i class="fas fa-bus"></i> Great Bus Booking System | <i class="fas fa-map-marker-alt"></i> India's Most Trusted Travel Partner | <i class="fas fa-headset"></i> 24/7 Customer Support
</footer>

<script>
    // SPLASH SCREEN - Click anywhere to continue
    const splash = document.getElementById('splash');
    
    function hideSplash() {
        if (splash) {
            splash.style.opacity = '0';
            splash.style.visibility = 'hidden';
            setTimeout(() => {
                if (splash && splash.parentNode) {
                    splash.remove();
                }
            }, 500);
        }
    }
    
    // Hide splash on click/tap (mobile friendly)
    if (splash) {
        splash.addEventListener('click', hideSplash);
        splash.addEventListener('touchstart', hideSplash);
    }
    
    // Auto hide after 3 seconds if not clicked
    setTimeout(() => {
        if (splash && splash.style.opacity !== '0') {
            hideSplash();
        }
    }, 3500);

    // DARK/LIGHT MODE TOGGLE
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;
    const icon = themeToggle.querySelector('i');
    const text = themeToggle.querySelector('span');

    const savedTheme = localStorage.getItem('greatbus-theme');
    if (savedTheme === 'dark') {
        body.classList.add('dark');
        icon.className = 'fas fa-sun';
        text.textContent = 'Light Mode';
    } else {
        body.classList.remove('dark');
        icon.className = 'fas fa-moon';
        text.textContent = 'Dark Mode';
    }

    themeToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        body.classList.toggle('dark');
        
        if (body.classList.contains('dark')) {
            localStorage.setItem('greatbus-theme', 'dark');
            icon.className = 'fas fa-sun';
            text.textContent = 'Light Mode';
        } else {
            localStorage.setItem('greatbus-theme', 'light');
            icon.className = 'fas fa-moon';
            text.textContent = 'Dark Mode';
        }
    });

    // Ripple effect for buttons (touch friendly)
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            let ripple = document.createElement('span');
            ripple.style.position = 'absolute';
            ripple.style.borderRadius = '50%';
            ripple.style.backgroundColor = 'rgba(255,255,255,0.5)';
            ripple.style.width = '20px';
            ripple.style.height = '20px';
            ripple.style.transform = 'scale(1)';
            ripple.style.opacity = '1';
            ripple.style.transition = 'transform 0.4s, opacity 0.4s';
            ripple.style.pointerEvents = 'none';
            
            const rect = this.getBoundingClientRect();
            ripple.style.left = `${e.clientX - rect.left - 10}px`;
            ripple.style.top = `${e.clientY - rect.top - 10}px`;
            ripple.style.position = 'absolute';
            
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.style.transform = 'scale(30)';
                ripple.style.opacity = '0';
                setTimeout(() => ripple.remove(), 400);
            }, 10);
        });
    });

    console.log("%c🚌 Great Bus | Logo Design Enhanced!", "color: #FF6B35; font-size: 16px; font-weight: bold;");
    console.log("%c✓ Floating Logo Animation | Rotating Ring | Glow Effects", "color: #F7931E; font-size: 12px;");
    console.log("%c✓ Mobile Optimized | Touch Events Added", "color: #FFD700; font-size: 12px;");
</script>
<!-- Floating Chatbot Button -->
<style>
    .chatbot-float {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 1000;
    }
    
    .chatbot-btn {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #FF6B35, #F7931E);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        transition: all 0.3s ease;
        animation: float 2s ease-in-out infinite;
        text-decoration: none;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
    
    .chatbot-btn:hover {
        transform: scale(1.1);
    }
    
    .chatbot-btn i {
        font-size: 1.8rem;
        color: white;
    }
    
    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ff4757;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: pulse 1s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.2); }
    }
    
    @media (max-width: 550px) {
        .chatbot-btn {
            width: 50px;
            height: 50px;
        }
        .chatbot-btn i {
            font-size: 1.5rem;
        }
        .chatbot-float {
            bottom: 20px;
            right: 20px;
        }
    }
</style>

<!-- Floating Chatbot Button -->
<div class="chatbot-float">
    <a href="chatbot.php" class="chatbot-btn">
        <i class="fas fa-robot"></i>
        <div class="notification-badge">
            <i class="fas fa-comment"></i>
        </div>
    </a>
</div>

</body>
</html>