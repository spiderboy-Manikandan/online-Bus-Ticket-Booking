<?php
session_start();
include "../db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php?type=admin");
    exit();
}

// Search and filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

$query = "SELECT b.*, u.fullname, u.email, u.phone, bs.bus_name, bs.bus_number, bs.from_city, bs.to_city 
          FROM bookings b 
          JOIN users u ON b.user_id = u.id 
          JOIN buses bs ON b.bus_id = bs.id 
          WHERE 1=1";
if ($search) {
    $query .= " AND (b.booking_number LIKE '%$search%' OR u.fullname LIKE '%$search%' OR u.email LIKE '%$search%')";
}
if ($status_filter) {
    $query .= " AND b.status = '$status_filter'";
}
$query .= " ORDER BY b.id DESC";
$bookings = $conn->query($query);

// Get totals
$total_amount = $conn->query("SELECT SUM(total_amount) as total FROM bookings")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Bookings - Admin | Great Bus</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --bg-gradient: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            --card-bg: #ffffff;
            --text-primary: #333;
            --text-secondary: #666;
            --border-color: #e0e0e0;
            --shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        body.dark {
            --bg-gradient: linear-gradient(135deg, #0a0a1a 0%, #0f0f1a 100%);
            --card-bg: #1e1e2e;
            --text-primary: #f0f0f0;
            --text-secondary: #a0a0a0;
            --border-color: #3a3a4a;
            --shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        body { font-family: 'Poppins', sans-serif; background: var(--bg-gradient); min-height: 100vh; transition: background 0.3s ease; }
        .animated-bg { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; overflow: hidden; pointer-events: none; }
        .floating-bus { position: absolute; font-size: 2rem; color: rgba(255,255,255,0.05); animation: floatBus 20s infinite linear; }
        @keyframes floatBus { 0% { transform: translateX(-10%) translateY(10vh); opacity: 0; } 10% { opacity: 0.1; } 90% { opacity: 0.1; } 100% { transform: translateX(110vw) translateY(-10vh); opacity: 0; } }
        .floating-bus:nth-child(1) { top: 20%; left: -5%; animation-duration: 18s; }
        .floating-bus:nth-child(2) { top: 50%; left: -10%; animation-duration: 25s; animation-delay: 3s; }
        .floating-bus:nth-child(3) { top: 70%; left: -3%; animation-duration: 22s; animation-delay: 6s; }
        .theme-toggle { position: fixed; top: 20px; right: 20px; z-index: 100; background: rgba(0,0,0,0.6); backdrop-filter: blur(10px); border: none; border-radius: 50px; padding: 8px 18px; color: white; cursor: pointer; display: flex; align-items: center; gap: 8px; }
        .navbar { position: relative; z-index: 10; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 20px rgba(0,0,0,0.1); }
        body.dark .navbar { background: rgba(30,30,46,0.95); }
        .logo { font-size: 1.5rem; font-weight: 800; background: linear-gradient(135deg, #1e3c72, #2a5298); background-clip: text; -webkit-background-clip: text; color: transparent; }
        .logo i { color: #2a5298; margin-right: 10px; }
        .logout-btn { background: #ff4757; color: white; padding: 0.5rem 1.2rem; border-radius: 25px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .container { position: relative; z-index: 10; max-width: 1400px; margin: 2rem auto; padding: 0 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
        .header h2 { color: white; font-size: 1.6rem; display: flex; align-items: center; gap: 10px; }
        .stats-grid { display: flex; gap: 1rem; flex-wrap: wrap; }
        .stat-card { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 0.5rem 1rem; border-radius: 20px; color: white; }
        .filters { background: var(--card-bg); border-radius: 20px; padding: 1rem; margin-bottom: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; }
        .search-box { flex: 1; display: flex; gap: 0.5rem; }
        .search-box input { flex: 1; padding: 0.7rem 1rem; border: 2px solid var(--border-color); border-radius: 12px; background: var(--card-bg); color: var(--text-primary); }
        .search-box button { padding: 0.7rem 1.2rem; background: #2a5298; color: white; border: none; border-radius: 12px; cursor: pointer; }
        .status-filter select { padding: 0.7rem 1rem; border: 2px solid var(--border-color); border-radius: 12px; background: var(--card-bg); color: var(--text-primary); }
        .table-container { background: var(--card-bg); border-radius: 25px; padding: 1rem; overflow-x: auto; box-shadow: var(--shadow); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color); color: var(--text-primary); }
        th { background: rgba(0,0,0,0.03); font-weight: 600; }
        .status-badge { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.7rem; font-weight: 600; }
        .status-confirmed { background: rgba(16,185,129,0.2); color: #10b981; }
        .status-cancelled { background: rgba(239,68,68,0.2); color: #ef4444; }
        .status-pending { background: rgba(245,158,11,0.2); color: #f59e0b; }
        .view-details { background: rgba(42,82,152,0.1); color: #2a5298; padding: 0.3rem 0.6rem; border-radius: 15px; text-decoration: none; font-size: 0.8rem; }
        .empty-state { text-align: center; padding: 3rem; color: var(--text-secondary); }
        .back-link { display: inline-flex; align-items: center; gap: 8px; margin-top: 1.5rem; color: white; text-decoration: none; }
        @media (max-width: 768px) { .container { padding: 0 1rem; } .navbar { flex-direction: column; } .filters { flex-direction: column; } .search-box { width: 100%; } }
    </style>
</head>
<body>
<div class="animated-bg"><div class="floating-bus"><i class="fas fa-bus"></i></div><div class="floating-bus"><i class="fas fa-bus-simple"></i></div><div class="floating-bus"><i class="fas fa-bus"></i></div></div>
<button class="theme-toggle" id="themeToggle"><i class="fas fa-moon"></i> <span>Dark Mode</span></button>
<nav class="navbar"><div class="logo"><i class="fas fa-bus"></i> Great Bus Admin</div><a href="dashboard.php" style="background:rgba(42,82,152,0.1); padding:0.5rem 1.2rem; border-radius:25px; text-decoration:none; color:#2a5298;"><i class="fas fa-chart-line"></i> Dashboard</a><a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></nav>
<div class="container">
    <div class="header">
        <h2><i class="fas fa-ticket-alt"></i> All Bookings</h2>
        <div class="stats-grid"><div class="stat-card"><i class="fas fa-chart-line"></i> Total: <?= $bookings->num_rows ?></div><div class="stat-card"><i class="fas fa-rupee-sign"></i> Revenue: ₹<?= number_format($total_amount['total']??0,0) ?></div></div>
    </div>
    <div class="filters">
        <form method="GET" class="search-box"><input type="text" name="search" placeholder="Search by Booking ID or User..." value="<?= htmlspecialchars($search) ?>"><button type="submit"><i class="fas fa-search"></i> Search</button><?php if($search || $status_filter): ?><a href="view_bookings.php" style="padding:0.7rem 1.2rem; background:#6c757d; color:white; border-radius:12px; text-decoration:none;">Clear</a><?php endif; ?></form>
        <div class="status-filter"><form method="GET" style="display:flex; gap:0.5rem;"><?php if($search): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?><select name="status" onchange="this.form.submit()"><option value="">All Status</option><option value="confirmed" <?= $status_filter=='confirmed'?'selected':'' ?>>Confirmed</option><option value="cancelled" <?= $status_filter=='cancelled'?'selected':'' ?>>Cancelled</option><option value="pending" <?= $status_filter=='pending'?'selected':'' ?>>Pending</option></select></form></div>
    </div>
    <div class="table-container">
        <?php if($bookings && $bookings->num_rows>0): ?>
        <table><thead><tr><th>Booking ID</th><th>User</th><th>Bus</th><th>Route</th><th>Journey Date</th><th>Seats</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
        <tbody><?php while($booking=$bookings->fetch_assoc()): ?><tr><td><strong>#<?= htmlspecialchars($booking['booking_number']) ?></strong></td><td><?= htmlspecialchars($booking['fullname']) ?><br><small><?= htmlspecialchars($booking['email']) ?></small></td><td><i class="fas fa-bus"></i> <?= htmlspecialchars($booking['bus_name']) ?></td><td><?= htmlspecialchars($booking['from_city']) ?> → <?= htmlspecialchars($booking['to_city']) ?></td><td><i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($booking['journey_date'])) ?></td><td><?= $booking['seats'] ?></td><td><strong>₹<?= number_format($booking['total_amount'],0) ?></strong></td><td><span class="status-badge status-<?= $booking['status'] ?>"><i class="fas <?= $booking['status']=='confirmed'?'fa-check-circle':'fa-times-circle' ?>"></i> <?= ucfirst($booking['status']) ?></span></td><td><a href="booking_details.php?id=<?= $booking['id'] ?>" class="view-details"><i class="fas fa-eye"></i> View</a></td></tr><?php endwhile; ?></tbody></table>
        <?php else: ?><div class="empty-state"><i class="fas fa-ticket-alt" style="font-size:4rem; margin-bottom:1rem;"></i><h3>No Bookings Found</h3><p>No bookings have been made yet.</p></div><?php endif; ?>
    </div>
    <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>
<script>
    const themeToggle=document.getElementById('themeToggle');const body=document.body;const icon=themeToggle.querySelector('i');const text=themeToggle.querySelector('span');
    const savedTheme=localStorage.getItem('greatbus-theme');if(savedTheme==='dark'){body.classList.add('dark');icon.className='fas fa-sun';text.textContent='Light Mode';}
    themeToggle.addEventListener('click',()=>{body.classList.toggle('dark');if(body.classList.contains('dark')){localStorage.setItem('greatbus-theme','dark');icon.className='fas fa-sun';text.textContent='Light Mode';}else{localStorage.setItem('greatbus-theme','light');icon.className='fas fa-moon';text.textContent='Dark Mode';}});
</script>
</body>
</html>