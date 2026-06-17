<?php
// Enable CORS and handle OPTIONS
require_once __DIR__ . '/../header.php';

// Include database connection
require_once('../../conn.php');

// Include auth middleware (returns $authUser)
$authUser = require_once __DIR__ . '/../middleware/auth.php';

// We'll fetch active announcements (is_emergency = 1)
try {
    $stmt = $conn->prepare("
        SELECT id, title, body, is_emergency, created_at
        FROM announcements
        WHERE is_emergency = 1 
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "success" => true,
        "data" => $announcements
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}