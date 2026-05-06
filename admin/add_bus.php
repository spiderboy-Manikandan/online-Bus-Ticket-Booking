<?php
session_start();
include "../db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$message = "";
$error = "";

if (isset($_POST['add'])) {
    $bus_name = mysqli_real_escape_string($conn, $_POST['bus_name']);
    $bus_number = mysqli_real_escape_string($conn, $_POST['bus_number']);
    $from_city = mysqli_real_escape_string($conn, $_POST['from_city']);
    $to_city = mysqli_real_escape_string($conn, $_POST['to_city']);
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $travel_date = $_POST['travel_date'];
    $total_seats = (int)$_POST['total_seats'];
    $price_per_seat = (float)$_POST['price_per_seat'];
    $amenities = mysqli_real_escape_string($conn, $_POST['amenities']);
    
    // Validate
    if (empty($bus_name) || empty($bus_number) || empty($from_city) || empty($to_city)) {
        $error = "Please fill all required fields!";
    } else {
        $conn->query("INSERT INTO buses (bus_name, bus_number, from_city, to_city, departure_time, arrival_time, travel_date, total_seats, price_per_seat, amenities) 
                      VALUES ('$bus_name', '$bus_number', '$from_city', '$to_city', '$departure_time', '$arrival_time', '$travel_date', '$total_seats', '$price_per_seat', '$amenities')");
        $message = '<div class="success"><i class="fas fa-check-circle"></i> Bus added successfully!</div>';
        
        // Clear form after success (optional)
        echo '<script>setTimeout(function(){ window.location.href = "manage_buses.php"; }, 1500);</script>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Bus - Admin | Great Bus</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* CSS Variables for Dark Blue Theme */
        :root {
            --bg-gradient: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            --card-bg: #ffffff;
            --text-primary: #333;
            --text-secondary: #666;
            --input-border: #e0e0e0;
            --input-focus: #2a5298;
            --btn-primary: linear-gradient(135deg, #1e3c72, #2a5298);
            --success-bg: #d4edda;
            --success-color: #155724;
            --error-bg: #fee2e2;
            --error-color: #dc2626;
            --shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        body.dark {
            --bg-gradient: linear-gradient(135deg, #0a0a1a 0%, #0f0f1a 100%);
            --card-bg: #1e1e2e;
            --text-primary: #f0f0f0;
            --text-secondary: #a0a0a0;
            --input-border: #3a3a4a;
            --input-focus: #4a6fa5;
            --btn-primary: linear-gradient(135deg, #2a5298, #1e3c72);
            --success-bg: rgba(40, 167, 69, 0.2);
            --success-color: #2ecc71;
            --error-bg: rgba(220, 38, 38, 0.2);
            --error-color: #f87171;
            --shadow: 0 20px 60px rgba(0,0,0,0.5);
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
            color: rgba(255, 255, 255, 0.08);
            animation: floatBus 20s infinite linear;
        }

        @keyframes floatBus {
            0% { transform: translateX(-10%) translateY(10vh); opacity: 0; }
            10% { opacity: 0.1; }
            90% { opacity: 0.1; }
            100% { transform: translateX(110vw) translateY(-10vh); opacity: 0; }
        }

        .floating-bus:nth-child(1) { top: 20%; left: -5%; animation-duration: 18s; }
        .floating-bus:nth-child(2) { top: 50%; left: -10%; animation-duration: 25s; animation-delay: 3s; }
        .floating-bus:nth-child(3) { top: 70%; left: -3%; animation-duration: 22s; animation-delay: 6s; }

        .particle {
            position: absolute;
            width: 3px;
            height: 3px;
            background: rgba(255, 255, 255, 0.4);
            border-radius: 50%;
            animation: floatParticle 15s infinite linear;
        }

        @keyframes floatParticle {
            0% { transform: translateY(100vh) translateX(0); opacity: 0; }
            10% { opacity: 0.5; }
            90% { opacity: 0.5; }
            100% { transform: translateY(-10vh) translateX(50px); opacity: 0; }
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

        .form-container {
            position: relative;
            z-index: 10;
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 48px;
            padding: 2rem;
            width: 100%;
            max-width: 650px;
            box-shadow: var(--shadow);
            animation: slideUp 0.6s ease;
            transition: all 0.3s ease;
            border: 1px solid rgba(30, 60, 114, 0.3);
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

        .form-container:hover {
            transform: translateY(-5px);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 1rem;
        }

        .logo-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto;
            animation: bounce 0.8s ease;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .logo-icon i {
            font-size: 3.5rem;
            color: #2a5298;
        }

        h2 {
            text-align: center;
            color: var(--text-primary);
            font-size: 1.6rem;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
        }

        .input-group {
            margin-bottom: 1.2rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.85rem;
        }

        label i {
            margin-right: 8px;
            color: #2a5298;
            width: 20px;
        }

        input, select, textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid var(--input-border);
            border-radius: 15px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            background: var(--card-bg);
            color: var(--text-primary);
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--input-focus);
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.2);
        }

        .double-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: var(--btn-primary);
            color: white;
            border: none;
            border-radius: 60px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 1rem;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            filter: brightness(1.05);
            box-shadow: 0 10px 25px rgba(30, 60, 114, 0.4);
        }

        .success {
            background: var(--success-bg);
            color: var(--success-color);
            padding: 0.8rem 1rem;
            border-radius: 60px;
            margin-bottom: 1rem;
            text-align: center;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            animation: fadeInOut 3s ease;
        }

        .error {
            background: var(--error-bg);
            color: var(--error-color);
            padding: 0.8rem 1rem;
            border-radius: 60px;
            margin-bottom: 1rem;
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

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 1.5rem;
            color: #2a5298;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            transform: translateX(-5px);
            color: #1e3c72;
        }

        body.dark .back-link {
            color: #4a6fa5;
        }

        @media (max-width: 550px) {
            .form-container { padding: 1.5rem; margin: 1rem; }
            h2 { font-size: 1.3rem; }
            .double-group { grid-template-columns: 1fr; }
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
</div>

<script>
    // Generate floating particles
    (function() {
        const bg = document.querySelector('.animated-bg');
        for (let i = 0; i < 40; i++) {
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

<div class="form-container">
    <div class="logo-section">
        <div class="logo-icon">
            <i class="fas fa-bus"></i>
        </div>
        <h2><i class="fas fa-plus-circle"></i> Add New Bus</h2>
        <p class="subtitle">Fill in the details to add a new bus to the fleet</p>
    </div>

    <?= $message ?>
    <?php if ($error): ?>
        <div class="error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="input-group">
            <label><i class="fas fa-bus"></i> Bus Name</label>
            <input type="text" name="bus_name" placeholder="e.g., Royal Travels" required>
        </div>

        <div class="input-group">
            <label><i class="fas fa-id-card"></i> Bus Number</label>
            <input type="text" name="bus_number" placeholder="e.g., TN-01-AB-1234" required>
        </div>

        <div class="double-group">
            <div class="input-group">
                <label><i class="fas fa-map-marker-alt"></i> From City</label>
                <input type="text" name="from_city" placeholder="e.g., Chennai" required>
            </div>
            <div class="input-group">
                <label><i class="fas fa-map-marker-alt"></i> To City</label>
                <input type="text" name="to_city" placeholder="e.g., Madurai" required>
            </div>
        </div>

        <div class="double-group">
            <div class="input-group">
                <label><i class="fas fa-clock"></i> Departure Time</label>
                <input type="time" name="departure_time" required>
            </div>
            <div class="input-group">
                <label><i class="fas fa-clock"></i> Arrival Time</label>
                <input type="time" name="arrival_time" required>
            </div>
        </div>

        <div class="input-group">
            <label><i class="fas fa-calendar"></i> Travel Date</label>
            <input type="date" name="travel_date" required>
        </div>

        <div class="double-group">
            <div class="input-group">
                <label><i class="fas fa-chair"></i> Total Seats</label>
                <input type="number" name="total_seats" placeholder="e.g., 40" min="1" max="60" required>
            </div>
            <div class="input-group">
                <label><i class="fas fa-tag"></i> Price Per Seat (₹)</label>
                <input type="number" step="0.01" name="price_per_seat" placeholder="e.g., 850" min="0" required>
            </div>
        </div>

        <div class="input-group">
            <label><i class="fas fa-wifi"></i> Amenities (comma separated)</label>
            <textarea name="amenities" rows="3" placeholder="e.g., AC, WiFi, Food, Charging Point, Water Bottle"></textarea>
        </div>

        <button type="submit" name="add" class="btn-submit">
            <i class="fas fa-plus-circle"></i> Add Bus
        </button>
    </form>

    <a href="manage_buses.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Manage Buses</a>
</div>

<script>
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

    // Set default travel date to tomorrow
    const travelDateInput = document.querySelector('input[name="travel_date"]');
    if (travelDateInput && !travelDateInput.value) {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        travelDateInput.value = tomorrow.toISOString().split('T')[0];
    }

    console.log("%c🚌 Great Bus | Admin - Add Bus", "color: #2a5298; font-size: 14px; font-weight: bold;");
</script>

</body>
</html>