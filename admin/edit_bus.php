<?php
session_start();
include "../db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$bus_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$bus = $conn->query("SELECT * FROM buses WHERE id = $bus_id")->fetch_assoc();

if (!$bus) {
    header("Location: manage_buses.php");
    exit();
}

if (isset($_POST['update'])) {
    $bus_name = $_POST['bus_name'];
    $bus_number = $_POST['bus_number'];
    $from_city = $_POST['from_city'];
    $to_city = $_POST['to_city'];
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $travel_date = $_POST['travel_date'];
    $total_seats = $_POST['total_seats'];
    $price_per_seat = $_POST['price_per_seat'];
    $amenities = $_POST['amenities'];
    
    $conn->query("UPDATE buses SET 
        bus_name='$bus_name', bus_number='$bus_number', from_city='$from_city', 
        to_city='$to_city', departure_time='$departure_time', arrival_time='$arrival_time',
        travel_date='$travel_date', total_seats='$total_seats', 
        price_per_seat='$price_per_seat', amenities='$amenities' 
        WHERE id=$bus_id");
    
    header("Location: manage_buses.php");
    exit();
}
?>
<!-- Add edit form HTML here similar to add_bus.php with values filled -->