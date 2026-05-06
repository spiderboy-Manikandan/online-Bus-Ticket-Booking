<?php
session_start();
include "db.php";

$error = "";

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $pass = $_POST['password'];
    
    if (empty($email) || empty($pass)) {
        $error = "Please fill all fields!";
    } else {
        $res = $conn->query("SELECT * FROM users WHERE email='$email'");
        if ($res && $res->num_rows > 0) {
            $user = $res->fetch_assoc();
            if (password_verify($pass, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['fullname'];
                $_SESSION['phone'] = $user['phone'];
                header("Location: search_bus.php");
                exit();
            } else { 
                $error = "Invalid password!"; 
            }
        } else { 
            $error = "Email not found!"; 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login - Great Bus</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* CSS Variables for Orange Theme */
        :root {
            --bg-gradient: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            --card-bg: rgba(255, 255, 255, 0.98);
            --text-primary: #333;
            --text-secondary: #666;
            --input-border: #e0e0e0;
            --input-focus: #FF6B35;
            --btn-primary: linear-gradient(135deg, #FF6B35, #F7931E);
            --error-bg: #fee2e2;
            --error-color: #dc2626;
            --shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        body.dark {
            --bg-gradient: linear-gradient(135deg, #1a1a2e 0%, #0f0f1a 100%);
            --card-bg: rgba(30, 30, 46, 0.98);
            --text-primary: #f0f0f0;
            --text-secondary: #a0a0a0;
            --input-border: #3a3a4a;
            --input-focus: #FF8C42;
            --btn-primary: linear-gradient(135deg, #FF6B35, #F7931E);
            --error-bg: rgba(220, 38, 38, 0.2);
            --error-color: #f87171;
            --shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            transition: background 0.3s ease;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .floating-bus {
            position: absolute;
            font-size: 2rem;
            color: rgba(255, 255, 255, 0.1);
            animation: floatBus 20s infinite linear;
        }

        @keyframes floatBus {
            0% {
                transform: translateX(-10%) translateY(10vh);
                opacity: 0;
            }
            10% { opacity: 0.1; }
            90% { opacity: 0.1; }
            100% {
                transform: translateX(110vw) translateY(-10vh);
                opacity: 0;
            }
        }

        .floating-bus:nth-child(1) { top: 20%; left: -5%; animation-duration: 18s; }
        .floating-bus:nth-child(2) { top: 50%; left: -10%; animation-duration: 25s; animation-delay: 3s; }
        .floating-bus:nth-child(3) { top: 70%; left: -3%; animation-duration: 22s; animation-delay: 6s; }
        .floating-bus:nth-child(4) { top: 85%; left: -8%; animation-duration: 28s; animation-delay: 9s; }

        .particle {
            position: absolute;
            width: 3px;
            height: 3px;
            background: rgba(255, 255, 255, 0.4);
            border-radius: 50%;
            animation: floatParticle 15s infinite linear;
        }

        @keyframes floatParticle {
            0% {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }
            10% { opacity: 0.6; }
            90% { opacity: 0.6; }
            100% {
                transform: translateY(-10vh) translateX(50px);
                opacity: 0;
            }
        }

        /* Theme Toggle */
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 100;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 50px;
            padding: 8px 18px;
            color: white;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .theme-toggle:hover {
            transform: scale(1.05);
            background: rgba(0, 0, 0, 0.8);
        }

        /* Login Container */
        .login-container {
            position: relative;
            z-index: 10;
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 48px;
            padding: 2.5rem;
            width: 100%;
            max-width: 480px;
            box-shadow: var(--shadow);
            animation: slideUp 0.6s ease;
            border: 1px solid rgba(255, 107, 53, 0.3);
            transition: all 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-container:hover {
            transform: translateY(-5px);
        }

        /* Logo Section */
        .logo-section {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto;
            animation: bounce 0.8s ease;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .logo-icon svg {
            width: 100%;
            height: 100%;
        }

        h2 {
            text-align: center;
            color: var(--text-primary);
            font-size: 1.8rem;
            margin-top: 0.5rem;
            font-weight: 700;
        }

        .subtitle {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }

        /* Input Groups */
        .input-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #FF6B35;
            font-size: 1.1rem;
            z-index: 1;
        }

        input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid var(--input-border);
            border-radius: 60px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            background: var(--card-bg);
            color: var(--text-primary);
        }

        input:focus {
            outline: none;
            border-color: var(--input-focus);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.2);
        }

        input::placeholder {
            color: var(--text-secondary);
            opacity: 0.7;
        }

        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            font-size: 1.1rem;
            z-index: 2;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #FF6B35;
        }

        /* Button */
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: var(--btn-primary);
            color: white;
            border: none;
            border-radius: 60px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 0.5rem;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            filter: brightness(1.05);
            box-shadow: 0 10px 25px rgba(255, 107, 53, 0.4);
        }

        /* Error Message */
        .error {
            background: var(--error-bg);
            color: var(--error-color);
            padding: 0.8rem 1rem;
            border-radius: 60px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Links */
        .signup-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-secondary);
        }

        .signup-link a {
            color: #FF6B35;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        .back-home {
            text-align: center;
            margin-top: 1rem;
        }

        .back-home a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .back-home a:hover {
            color: #FF6B35;
        }

        /* Responsive */
        @media (max-width: 550px) {
            .login-container { padding: 1.8rem; margin: 1rem; }
            h2 { font-size: 1.5rem; }
            .logo-icon { width: 60px; height: 60px; }
            .theme-toggle { padding: 6px 12px; font-size: 0.8rem; top: 15px; right: 15px; }
        }
    </style>
</head>
<body>

<!-- Animated Background -->
<div class="animated-bg">
    <div class="floating-bus"><i class="fas fa-bus"></i></div>
    <div class="floating-bus"><i class="fas fa-bus-simple"></i></div>
    <div class="floating-bus"><i class="fas fa-bus"></i></div>
    <div class="floating-bus"><i class="fas fa-van-shuttle"></i></div>
</div>

<script>
    // Generate floating particles
    (function() {
        const bg = document.querySelector('.animated-bg');
        for (let i = 0; i < 60; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.width = Math.random() * 5 + 2 + 'px';
            particle.style.height = particle.style.width;
            particle.style.animationDuration = Math.random() * 20 + 10 + 's';
            particle.style.animationDelay = Math.random() * 10 + 's';
            bg.appendChild(particle);
        }
    })();
</script>

<!-- Theme Toggle -->
<button class="theme-toggle" id="themeToggle">
    <i class="fas fa-moon"></i> <span>Dark Mode</span>
</button>

<div class="login-container">
    <div class="logo-section">
        <div class="logo-icon">
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
        <h2>Welcome Back!</h2>
        <p class="subtitle">Login to continue your journey</p>
    </div>

    <?php if ($error): ?>
        <div class="error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="input-group">
            <i class="fas fa-envelope"></i>
            <input type="email" name="email" placeholder="Email Address" required autocomplete="email">
        </div>
        <div class="input-group" id="passwordGroup">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" id="password" placeholder="Password" required autocomplete="current-password">
            <i class="fas fa-eye-slash password-toggle" id="togglePassword"></i>
        </div>
        <button type="submit" name="login" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>
    </form>

    <div class="signup-link">
        Don't have an account? <a href="signup.php">Sign Up <i class="fas fa-arrow-right"></i></a>
    </div>
    
    <div class="back-home">
        <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
    </div>
</div>

<script>
    // Password Visibility Toggle
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');

    if (togglePassword && password) {
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    // Dark/Light Mode Toggle
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
        icon.className = 'fas fa-moon';
        text.textContent = 'Dark Mode';
    }

    themeToggle.addEventListener('click', () => {
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

    // Console greeting
    console.log("%c🚌 Great Bus | User Login Page", "color: #FF6B35; font-size: 14px; font-weight: bold;");
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