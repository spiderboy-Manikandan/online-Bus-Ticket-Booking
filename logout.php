<?php
session_start();

// Store user name for goodbye message if exists
$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';

// Destroy all session data
session_destroy();

// Optional: Clear remember me cookies if any
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - Great Bus</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Animated Background */
        .animated-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .floating-bus {
            position: absolute;
            font-size: 3rem;
            color: rgba(255, 255, 255, 0.1);
            animation: floatBus 20s infinite linear;
        }

        @keyframes floatBus {
            0% {
                transform: translateX(-10%) translateY(0vh);
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

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: floatParticle 15s infinite linear;
        }

        @keyframes floatParticle {
            0% {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }
            10% { opacity: 0.5; }
            90% { opacity: 0.5; }
            100% {
                transform: translateY(-10vh) translateX(50px);
                opacity: 0;
            }
        }

        /* Logout Card */
        .logout-card {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 48px;
            padding: 3rem;
            text-align: center;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.6s ease;
            border: 1px solid rgba(255, 107, 53, 0.3);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logout-icon {
            font-size: 5rem;
            color: #FF6B35;
            margin-bottom: 1rem;
            animation: bounce 0.8s ease;
        }

        @keyframes bounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }

        h2 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        p {
            color: #666;
            margin-bottom: 2rem;
        }

        .redirect-message {
            background: #f0f2ff;
            padding: 0.8rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
            color: #FF6B35;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .loader {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255, 107, 53, 0.3);
            border-top: 3px solid #FF6B35;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1.5rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #FF6B35, #F7931E);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 107, 53, 0.3);
        }

        .countdown {
            font-size: 1.2rem;
            font-weight: 700;
            color: #FF6B35;
        }

        @media (max-width: 550px) {
            .logout-card { padding: 1.8rem; }
            h2 { font-size: 1.5rem; }
            .logout-icon { font-size: 3.5rem; }
        }
    </style>
</head>
<body>

<!-- Animated Background -->
<div class="animated-bg">
    <div class="floating-bus"><i class="fas fa-bus"></i></div>
    <div class="floating-bus"><i class="fas fa-bus-simple"></i></div>
    <div class="floating-bus"><i class="fas fa-bus"></i></div>
</div>

<script>
    // Generate floating particles
    (function() {
        const bg = document.querySelector('.animated-bg');
        for (let i = 0; i < 30; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.width = Math.random() * 6 + 2 + 'px';
            particle.style.height = particle.style.width;
            particle.style.animationDuration = Math.random() * 20 + 10 + 's';
            particle.style.animationDelay = Math.random() * 10 + 's';
            bg.appendChild(particle);
        }
    })();
</script>

<div class="logout-card">
    <div class="logout-icon">
        <i class="fas fa-sign-out-alt"></i>
    </div>
    <h2>Goodbye!</h2>
    <p>You have been successfully logged out.</p>
    
    <div class="redirect-message">
        <i class="fas fa-hourglass-half"></i>
        <span>Redirecting to home page in <span id="countdown" class="countdown">3</span> seconds</span>
    </div>
    
    <div class="loader"></div>
    
    <a href="index.php" class="btn">
        <i class="fas fa-arrow-left"></i> Click here if not redirected
    </a>
</div>

<script>
    // Countdown timer
    let seconds = 3;
    const countdownElement = document.getElementById('countdown');
    
    const interval = setInterval(() => {
        seconds--;
        if (countdownElement) {
            countdownElement.textContent = seconds;
        }
        
        if (seconds <= 0) {
            clearInterval(interval);
            window.location.href = 'index.php';
        }
    }, 1000);
    
    // Console log
    console.log("%c🚌 You have been logged out. Redirecting to home page...", "color: #FF6B35; font-size: 14px; font-weight: bold;");
</script>

</body>
</html>