<?php
// Test script to verify the notifications query logic with detailed output
require_once __DIR__ . '/conn.php';

$role = 'student';
$lastChecked = '2026-05-26 16:01:57';

echo "Role: $role\n";
echo "Last checked: $lastChecked\n\n";

// First, let's see what's in the notifications table
$stmt = $conn->query("SELECT id, title, message, target_role, is_active, created_at FROM notifications");
$allNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "All notifications in table:\n";
foreach ($allNotifications as $notification) {
    echo "- ID: {$notification['id']}, Title: {$notification['title']}, Target Role: {$notification['target_role']}, Is Active: {$notification['is_active']}, Created: {$notification['created_at']}\n";
}
echo "\n";

// Now run our actual query
try {
    $stmt = $conn->prepare("
        SELECT id, title, message, type, target_role, is_active, created_at
        FROM notifications
        WHERE (target_role = ? OR target_role = 'all')
          AND is_active = 1
          AND created_at >= ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$role, $lastChecked]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Query returned " . count($notifications) . " notifications:\n";
    foreach ($notifications as $notification) {
        echo "- ID: {$notification['id']}, Title: {$notification['title']}, Created: {$notification['created_at']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>