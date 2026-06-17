<?php

require_once __DIR__ . "/../header.php";
require_once "../../conn.php";
require_once "../../jwt.php";

$method = $_SERVER['REQUEST_METHOD'];
$id     = null;

// Get ID from query parameter (e.g., /api/timetable/index.php?id=123)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
}

/* =========================
   LOAD MIDDLEWARE (AUTH CHECK)
   ========================= */
try {
    $authUser = require_once __DIR__ . "/../middleware/auth.php";
    
     // =========================
     // ADMIN CHECK
     // =========================
     if ($authUser->role !== 'admin') {
         http_response_code(403);
         echo json_encode([
             "status" => "error",
             "message" => "Forbidden - Admin access required"
         ]);
         exit;
     }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "Unauthorized - Invalid or missing token"
    ]);
    exit;
}

try {
    switch ($method) {
        case 'GET':
            if ($id) {
                // GET /api/timetable/:id - Get specific timetable entry
                $stmt = $conn->prepare("
                    SELECT t.id, t.course_id, t.day, t.start_time, t.end_time, t.venue, t.week, t.created_at,
                           c.code AS course_code, c.title AS course_title,
                           d.name AS department_name,
                           l.name AS lecturer_name
                    FROM timetable t
                    JOIN courses c ON t.course_id = c.id
                    JOIN departments d ON c.department_id = d.id
                    LEFT JOIN users l ON c.lecturer_id = l.id
                    WHERE t.id = ?
                ");
                $stmt->execute([$id]);
                $timetableEntry = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$timetableEntry) {
                    http_response_code(404);
                    echo json_encode([
                        "status" => "error",
                        "message" => "Timetable entry not found"
                    ]);
                    break;
                }
                
                echo json_encode([
                    "status" => "success",
                    "timetable_entry" => $timetableEntry
                ]);
            } else {
                // GET /api/timetable - List timetable entries with filtering
                // Supported filters: department_id, day, week
                
                $departmentId = $_GET['department_id'] ?? null;
                $day = $_GET['day'] ?? null;
                $week = $_GET['week'] ?? null;
                
                // Build query with optional filters
                $sql = "
                    SELECT t.id, t.course_id, t.day, t.start_time, t.end_time, t.venue, t.week, t.created_at,
                           c.code AS course_code, c.title AS course_title,
                           d.name AS department_name,
                           l.name AS lecturer_name
                    FROM timetable t
                    JOIN courses c ON t.course_id = c.id
                    JOIN departments d ON c.department_id = d.id
                    LEFT JOIN users l ON c.lecturer_id = l.id
                    WHERE 1=1
                ";
                
                $params = [];
                $paramTypes = '';
                
                if ($departmentId !== null) {
                    $sql .= " AND c.department_id = ?";
                    $params[] = $departmentId;
                    $paramTypes .= 'i';
                }
                
                // Day filter (with validation)
                if ($day !== null) {
                    $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    if (!in_array($day, $validDays)) {
                        echo json_encode([
                            "status" => "error",
                            "message" => "Invalid day. Must be one of: Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Sunday"
                        ]);
                        break;
                    }
                    $sql .= " AND t.day = ?";
                    $params[] = $day;
                    $paramTypes .= 's';
                }
                
                // Week filter (integer)
                if ($week !== null) {
                    $sql .= " AND t.week = ?";
                    $params[] = (int)$week;
                    $paramTypes .= 'i';
                }
                
                $sql .= " ORDER BY t.day, t.start_time";
                
                $stmt = $conn->prepare($sql);
                if ($params) {
                    $stmt->execute($params);
                } else {
                    $stmt->execute();
                }
                $timetableEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    "status" => "success",
                    "data" => $timetableEntries,
                    "count" => count($timetableEntries),
                    "filters_applied" => [
                        "department_id" => $departmentId,
                        "day" => $day,
                        "week" => $week
                    ]
                ]);
            }
            break;

        case 'POST':
            // POST /api/timetable - Create new timetable entry
            // Handle JSON data
            $data = json_decode(file_get_contents("php://input"), true);
            
            $course_id = $data['course_id'] ?? null;
            $day = $data['day'] ?? '';
            $start_time = $data['start_time'] ?? '';
            $end_time = $data['end_time'] ?? '';
            $venue = trim($data['venue'] ?? '');
            $week = $data['week'] ?? 1;

            // Validation
            if ($course_id === null) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Course ID is required"
                ]);
                break;
            }

            $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            if ($day === '' || !in_array($day, $validDays)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Valid day is required (Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Sunday)"
                ]);
                break;
            }

            if ($start_time === '' || !preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/', $start_time)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Valid start time is required (HH:MM:SS format)"
                ]);
                break;
            }

            if ($end_time === '' || !preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/', $end_time)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Valid end time is required (HH:MM:SS format)"
                ]);
                break;
            }

            if ($venue === '') {
                echo json_encode([
                    "status" => "error",
                    "message" => "Venue is required"
                ]);
                break;
            }

            $week = (int)$week;
            if ($week < 1) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Week must be a positive integer"
                ]);
                break;
            }

            // Validate course exists
            $courseCheck = $conn->prepare("SELECT id FROM courses WHERE id = ?");
            $courseCheck->execute([$course_id]);
            if (!$courseCheck->fetch()) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid course ID"
                ]);
                break;
            }

            // Check for time conflicts (same venue, day, week - overlapping times not allowed)
            $conflictCheck = $conn->prepare("
                SELECT id FROM timetable 
                WHERE venue = ? AND day = ? AND week = ? 
                AND start_time < ? AND end_time > ?
            ");
            $conflictCheck->execute([$venue, $day, $week, $end_time, $start_time]);
             
            if ($conflictCheck->fetch()) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Time conflict detected - another timetable entry already exists for this venue at the overlapping time"
                ]);
                break;
            }

            // Create timetable entry
            $stmt = $conn->prepare("INSERT INTO timetable (course_id, day, start_time, end_time, venue, week) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$course_id, $day, $start_time, $end_time, $venue, $week]);
            
            $timetableId = $conn->lastInsertId();
            
            // Get created timetable entry with course details
            $stmt = $conn->prepare("
                SELECT t.id, t.course_id, t.day, t.start_time, t.end_time, t.venue, t.week, t.created_at,
                       c.code AS course_code, c.title AS course_title,
                       d.name AS department_name,
                       l.name AS lecturer_name
                FROM timetable t
                JOIN courses c ON t.course_id = c.id
                JOIN departments d ON c.department_id = d.id
                LEFT JOIN users l ON c.lecturer_id = l.id
                WHERE t.id = ?
            ");
            $stmt->execute([$timetableId]);
            $newTimetableEntry = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "status" => "success",
                "message" => "Timetable entry created successfully",
                "timetable_entry" => $newTimetableEntry
            ]);
            break;

        case 'PUT':
            // PUT /api/timetable/:id - Update timetable entry (Simplified for beginners)
            $data = json_decode(file_get_contents("php://input"), true);

            if ($id === null) {
                echo json_encode([
                    "status" => "error",
                    "message" => "ID required"
                ]);
                break;
            }

            // Find existing timetable entry FIRST
            $stmt = $conn->prepare("SELECT * FROM timetable WHERE id = ?");
            $stmt->execute([$id]);
            $timetableEntry = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$timetableEntry) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Timetable entry not found"
                ]);
                break;
            }

            // Read fields — fall back to existing data when a field is left blank
            $course_id = $data['course_id'] ?? $timetableEntry['course_id'];
            $day = $data['day'] ?? $timetableEntry['day'];
            $start_time = $data['start_time'] ?? $timetableEntry['start_time'];
            $end_time = $data['end_time'] ?? $timetableEntry['end_time'];
            $venue = trim($data['venue'] ?? $timetableEntry['venue']);
            $week = $data['week'] ?? $timetableEntry['week'];

            // Validation
            if ($course_id === null) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Course ID is required"
                ]);
                break;
            }

            $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            if ($day === '' || !in_array($day, $validDays)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Valid day is required (Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Sunday)"
                ]);
                break;
            }

            if ($start_time === '' || !preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/', $start_time)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Valid start time is required (HH:MM:SS format)"
                ]);
                break;
            }

            if ($end_time === '' || !preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/', $end_time)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Valid end time is required (HH:MM:SS format)"
                ]);
                break;
            }

            if ($venue === '') {
                echo json_encode([
                    "status" => "error",
                    "message" => "Venue is required"
                ]);
                break;
            }

            $week = (int)$week;
            if ($week < 1) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Week must be a positive integer"
                ]);
                break;
            }

            // Validate course exists
            $courseCheck = $conn->prepare("SELECT id FROM courses WHERE id = ?");
            $courseCheck->execute([$course_id]);
            if (!$courseCheck->fetch()) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid course ID"
                ]);
                break;
            }

            // Check for time conflicts (same venue, day, week - overlapping times not allowed, excluding current entry)
            $conflictCheck = $conn->prepare("
                SELECT id FROM timetable 
                WHERE venue = ? AND day = ? AND week = ? 
                AND start_time < ? AND end_time > ? AND id != ?
            ");
            $conflictCheck->execute([$venue, $day, $week, $end_time, $start_time, $id]);
             
            if ($conflictCheck->fetch()) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Time conflict detected - another timetable entry already exists for this venue at the overlapping time"
                ]);
                break;
            }

            // Update timetable entry
            $stmt = $conn->prepare("UPDATE timetable SET course_id = ?, day = ?, start_time = ?, end_time = ?, venue = ?, week = ? WHERE id = ?");
            $stmt->execute([$course_id, $day, $start_time, $end_time, $venue, $week, $id]);
            
            // Get updated timetable entry with course details
            $stmt = $conn->prepare("
                SELECT t.id, t.course_id, t.day, t.start_time, t.end_time, t.venue, t.week, t.created_at,
                       c.code AS course_code, c.title AS course_title,
                       d.name AS department_name,
                       l.name AS lecturer_name
                FROM timetable t
                JOIN courses c ON t.course_id = c.id
                JOIN departments d ON c.department_id = d.id
                LEFT JOIN users l ON c.lecturer_id = l.id
                WHERE t.id = ?
            ");
            $stmt->execute([$id]);
            $updatedTimetableEntry = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "status" => "success",
                "message" => "Timetable entry updated successfully",
                "timetable_entry" => $updatedTimetableEntry
            ]);
            break;

    
        
        case 'DELETE':
            // DELETE /api/timetable/:id - Delete timetable entry
            if ($id === null) {
                echo json_encode([
                    "status" => "error",
                    "message" => "ID required"
                ]);
                break;
            }

            // Check if timetable entry exists
            $stmt = $conn->prepare("SELECT id FROM timetable WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Timetable entry not found"
                ]);
                break;
            }

            // Delete timetable entry
            $del = $conn->prepare("DELETE FROM timetable WHERE id = ?");
            $del->execute([$id]);
            
            echo json_encode([
                "status" => "success",
                "message" => "Timetable entry deleted successfully"
            ]);
            break;

        default:
            echo json_encode([
                "status" => "error",
                "message" => "Method not allowed. Use GET, POST, PUT, or DELETE."
            ]);
            http_response_code(405);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}