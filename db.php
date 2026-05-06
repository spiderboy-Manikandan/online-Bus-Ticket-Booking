<?php
$conn = new mysqli("localhost", "root", "", "greatbus");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}
$conn->set_charset("utf8");
?>