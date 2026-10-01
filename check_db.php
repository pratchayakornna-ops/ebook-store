<?php
require_once 'c:/xampp/htdocs/ebook-store/config.php';

echo "Connected successfully.\n";

$res = $conn->query("SHOW TABLES");
echo "Tables:\n";
while ($row = $res->fetch_row()) {
    echo "- " . $row[0] . "\n";
}

echo "\nUsers:\n";
$users = $conn->query("SELECT user_id, name, email, role FROM users");
while ($u = $users->fetch_assoc()) {
    echo "- ID: {$u['user_id']}, Name: {$u['name']}, Email: {$u['email']}, Role: {$u['role']}\n";
}

echo "\nOrders:\n";
$orders = $conn->query("SELECT order_id, user_id, total_amount, status, order_date FROM orders");
while ($o = $orders->fetch_assoc()) {
    echo "- Order #{$o['order_id']}, User ID: {$o['user_id']}, Status: {$o['status']}, Total: {$o['total_amount']}\n";
}

echo "\nOrder items:\n";
$items = $conn->query("SELECT * FROM order_items");
if ($items) {
    while ($it = $items->fetch_assoc()) {
        echo "- Item: " . json_encode($it) . "\n";
    }
} else {
    echo "No order_items table or error\n";
}
