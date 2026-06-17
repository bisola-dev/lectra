<?php
// Enable CORS and handle OPTIONS
require_once __DIR__ . '/../header.php';

// Include database connection
require_once __DIR__ . '/../../conn.php';

// Include auth middleware (returns $authUser)
$authUser = require_once __DIR__ . '/../middleware/auth.php';

// Check that the user is a student
if ($authUser->role !== 'student') {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "Only students can record attendance"
    ]);
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);
$timetableId = $data['timetable_id'] ?? null;

// Validate timetable_id
if ($timetableId === null || !is_numeric($timetableId)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "timetable_id is required and must be a number"
    ]);
    exit;
}

$timetableId = (int)$timetableId;

// Optional: check if timetable exists
try {
    $stmt = $conn->prepare("SELECT id FROM timetable WHERE id = ?");
    $stmt->execute([$timetableId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Timetable entry not found"
        ]);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
    exit;
}

// Insert attendance record
try {
    $stmt = $conn->prepare("INSERT INTO attendance (student_id, timetable_id, confirmed_at) VALUES (?, ?, NOW())");
    $stmt->execute([$authUser->id, $timetableId]);
    
    echo json_encode([
        "success" => true,
        "data" => []
    ]);
} catch (Exception $e) {
    // Check if it's a duplicate entry error (unique constraint)
    if ($e->getCode() == 23000) {
        http_response_code(409); // Conflict
        echo json_encode([
            "success" => false,
            "message" => "Attendance already recorded for this timetable"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Database error: " . $e->getMessage()
        ]);
    }
}