<?php
session_start();
include "../db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php?type=admin");
    exit();
}

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $bus_id = mysqli_real_escape_string($conn, $_GET['delete']);
    
    $check = $conn->query("SELECT id FROM buses WHERE id = $bus_id");
    if ($check->num_rows > 0) {
        $conn->query("DELETE FROM buses WHERE id = $bus_id");
        $_SESSION['message'] = "Bus deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Bus not found!";
        $_SESSION['message_type'] = "error";
    }
    header("Location: manage_buses.php");
    exit();
}

// Handle status toggle
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $bus_id = mysqli_real_escape_string($conn, $_GET['toggle_status']);
    $current_status = $conn->query("SELECT status FROM buses WHERE id = $bus_id")->fetch_assoc();
    $new_status = ($current_status['status'] == 'active') ? 'inactive' : 'active';
    $conn->query("UPDATE buses SET status = '$new_status' WHERE id = $bus_id");
    $_SESSION['message'] = "Bus status updated to " . ucfirst($new_status) . "!";
    $_SESSION['message_type'] = "success";
    header("Location: manage_buses.php");
    exit();
}

// Search and filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

$query = "SELECT * FROM buses WHERE 1=1";
if ($search) {
    $query .= " AND (bus_name LIKE '%$search%' OR bus_number LIKE '%$search%' OR from_city LIKE '%$search%' OR to_city LIKE '%$search%')";
}
if ($status_filter) {
    $query .= " AND status = '$status_filter'";
}
$query .= " ORDER BY id DESC";
$buses = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Buses - Admin | Great Bus</title>
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
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            transition: background 0.3s ease;
        }
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
            color: rgba(255, 255, 255, 0.05);
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
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 100;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 50px;
            padding: 8px 18px;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .navbar {
            position: relative;
            z-index: 10;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        body.dark .navbar { background: rgba(30,30,46,0.95); }
        .logo { font-size: 1.5rem; font-weight: 800; background: linear-gradient(135deg, #1e3c72, #2a5298); background-clip: text; -webkit-background-clip: text; color: transparent; }
        .logo i { color: #2a5298; margin-right: 10px; }
        .logout-btn { background: #ff4757; color: white; padding: 0.5rem 1.2rem; border-radius: 25px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .container { position: relative; z-index: 10; max-width: 1400px; margin: 2rem auto; padding: 0 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
        .header h2 { color: white; font-size: 1.6rem; display: flex; align-items: center; gap: 10px; }
        .add-btn { background: #10b981; color: white; padding: 0.8rem 1.5rem; border-radius: 30px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; }
        .filters { background: var(--card-bg); border-radius: 20px; padding: 1rem; margin-bottom: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; }
        .search-box { flex: 1; display: flex; gap: 0.5rem; }
        .search-box input { flex: 1; padding: 0.7rem 1rem; border: 2px solid var(--border-color); border-radius: 12px; background: var(--card-bg); color: var(--text-primary); }
        .search-box button { padding: 0.7rem 1.2rem; background: #2a5298; color: white; border: none; border-radius: 12px; cursor: pointer; }
        .status-filter select { padding: 0.7rem 1rem; border: 2px solid var(--border-color); border-radius: 12px; background: var(--card-bg); color: var(--text-primary); }
        .table-container { background: var(--card-bg); border-radius: 25px; padding: 1rem; overflow-x: auto; box-shadow: var(--shadow); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color); color: var(--text-primary); }
        th { background: rgba(0,0,0,0.03); font-weight: 600; }
        .action-buttons { display: flex; gap: 0.8rem; flex-wrap: wrap; }
        .edit-btn, .view-btn, .toggle-btn, .delete-btn { text-decoration: none; padding: 0.3rem 0.6rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 5px; font-size: 0.8rem; }
        .edit-btn { background: rgba(102,126,234,0.1); color: #667eea; }
        .view-btn { background: rgba(59,130,246,0.1); color: #3b82f6; }
        .toggle-btn { background: rgba(245,158,11,0.1); color: #f59e0b; }
        .delete-btn { background: rgba(239,68,68,0.1); color: #ef4444; }
        .status-badge { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.7rem; font-weight: 600; }
        .status-active { background: rgba(16,185,129,0.2); color: #10b981; }
        .status-inactive { background: rgba(239,68,68,0.2); color: #ef4444; }
        .alert { padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: rgba(16,185,129,0.1); color: #10b981; border-left: 4px solid #10b981; }
        .alert-error { background: rgba(239,68,68,0.1); color: #ef4444; border-left: 4px solid #ef4444; }
        .empty-state { text-align: center; padding: 3rem; color: var(--text-secondary); }
        .back-link { display: inline-flex; align-items: center; gap: 8px; margin-top: 1.5rem; color: white; text-decoration: none; }
        @media (max-width: 768px) { .container { padding: 0 1rem; } .navbar { flex-direction: column; } .filters { flex-direction: column; } .search-box { width: 100%; } }
    </style>
</head>
<body>
<div class="animated-bg"><div class="floating-bus"><i class="fas fa-bus"></i></div><div class="floating-bus"><i class="fas fa-bus-simple"></i></div><div class="floating-bus"><i class="fas fa-bus"></i></div></div>
<button class="theme-toggle" id="themeToggle"><i class="fas fa-moon"></i> <span>Dark Mode</span></button>
<nav class="navbar"><div class="logo"><i class="fas fa-bus"></i> Great Bus Admin</div><a href="dashboard.php" style="background:rgba(42,82,152,0.1); color:#2a5298; padding:0.5rem 1.2rem; border-radius:25px; text-decoration:none;"><i class="fas fa-chart-line"></i> Dashboard</a><a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></nav>
<div class="container">
    <div class="header"><h2><i class="fas fa-list"></i> Manage Buses</h2><a href="add_bus.php" class="add-btn"><i class="fas fa-plus-circle"></i> Add New Bus</a></div>
    <?php if(isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?>"><i class="fas <?= $_SESSION['message_type']=='success'?'fa-check-circle':'fa-exclamation-circle' ?>"></i> <?= $_SESSION['message'] ?></div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>
    <div class="filters">
        <form method="GET" class="search-box"><input type="text" name="search" placeholder="Search by bus name, number, or route..." value="<?= htmlspecialchars($search) ?>"><button type="submit"><i class="fas fa-search"></i> Search</button><?php if($search || $status_filter): ?><a href="manage_buses.php" style="padding:0.7rem 1.2rem; background:#6c757d; color:white; border-radius:12px; text-decoration:none;">Clear</a><?php endif; ?></form>
        <div class="status-filter"><form method="GET" style="display:flex; gap:0.5rem;"><?php if($search): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?><select name="status" onchange="this.form.submit()"><option value="">All Status</option><option value="active" <?= $status_filter=='active'?'selected':'' ?>>Active</option><option value="inactive" <?= $status_filter=='inactive'?'selected':'' ?>>Inactive</option></select></form></div>
    </div>
    <div class="table-container">
        <?php if($buses && $buses->num_rows>0): ?>
        <table><thead><tr><th>ID</th><th>Bus Name</th><th>Number</th><th>Route</th><th>Departure</th><th>Arrival</th><th>Seats</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody><?php while($bus=$buses->fetch_assoc()): ?><tr><td>#<?= $bus['id'] ?></td><td><i class="fas fa-bus"></i> <strong><?= htmlspecialchars($bus['bus_name']) ?></strong></td><td><i class="fas fa-id-card"></i> <?= htmlspecialchars($bus['bus_number']) ?></td><td><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($bus['from_city']) ?> → <?= htmlspecialchars($bus['to_city']) ?></td><td><i class="fas fa-clock"></i> <?= date('h:i A', strtotime($bus['departure_time'])) ?></td><td><i class="fas fa-clock"></i> <?= date('h:i A', strtotime($bus['arrival_time'])) ?></td><td><i class="fas fa-chair"></i> <?= $bus['total_seats'] ?></td><td><i class="fas fa-tag"></i> ₹<?= number_format($bus['price_per_seat'],0) ?></td><td><span class="status-badge status-<?= $bus['status'] ?>"><i class="fas <?= $bus['status']=='active'?'fa-check-circle':'fa-times-circle' ?>"></i> <?= ucfirst($bus['status']) ?></span></td><td class="action-buttons"><a href="view_bus.php?id=<?= $bus['id'] ?>" class="view-btn"><i class="fas fa-eye"></i> View</a><a href="edit_bus.php?id=<?= $bus['id'] ?>" class="edit-btn"><i class="fas fa-edit"></i> Edit</a><a href="?toggle_status=<?= $bus['id'] ?>" class="toggle-btn" onclick="return confirm('Change bus status?')"><i class="fas <?= $bus['status']=='active'?'fa-pause-circle':'fa-play-circle' ?>"></i> <?= $bus['status']=='active'?'Deactivate':'Activate' ?></a><a href="?delete=<?= $bus['id'] ?>" class="delete-btn" onclick="return confirm('Delete this bus?')"><i class="fas fa-trash-alt"></i> Delete</a></td></tr><?php endwhile; ?></tbody></table>
        <?php else: ?><div class="empty-state"><i class="fas fa-bus" style="font-size:4rem; margin-bottom:1rem;"></i><h3>No Buses Found</h3><p>Click "Add New Bus" to add your first bus.</p></div><?php endif; ?>
    </div>
    <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>
<script>
    const themeToggle=document.getElementById('themeToggle');const body=document.body;const icon=themeToggle.querySelector('i');const text=themeToggle.querySelector('span');
    const savedTheme=localStorage.getItem('greatbus-theme');if(savedTheme==='dark'){body.classList.add('dark');icon.className='fas fa-sun';text.textContent='Light Mode';}
    themeToggle.addEventListener('click',()=>{body.classList.toggle('dark');if(body.classList.contains('dark')){localStorage.setItem('greatbus-theme','dark');icon.className='fas fa-sun';text.textContent='Light Mode';}else{localStorage.setItem('greatbus-theme','light');icon.className='fas fa-moon';text.textContent='Dark Mode';}});
    setTimeout(()=>{const alert=document.querySelector('.alert');if(alert){alert.style.opacity='0';setTimeout(()=>alert.remove(),300);}},3000);
</script>
</body>
</html>