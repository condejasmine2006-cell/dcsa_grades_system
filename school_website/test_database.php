<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: PHP started.<br>";

require_once "database.php";

echo "Step 2: Database connected successfully.<br>";

$result = $conn->query("SELECT COUNT(*) AS total FROM users");

if (!$result) {
    die("SQL ERROR: " . $conn->error);
}

$row = $result->fetch_assoc();

echo "Step 3: Users table found.<br>";
echo "Total registered users: " . $row["total"];

?>