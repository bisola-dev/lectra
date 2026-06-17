<?php
// Test script to verify the notifications query logic
require_once __DIR__ . '/../../conn.php';

$role = 'student';
$lastChecked = '2026-05-26 16:01:57';

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
    
    echo "Found " . count($notifications) . " notifications:\n";
    foreach ($notifications as $notification) {
        echo "- ID: {$notification['id']}, Title: {$notification['title']}, Created: {$notification['created_at']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>