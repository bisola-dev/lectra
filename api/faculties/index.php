<?php

require_once __DIR__ . '/../header.php';
require_once "../../conn.php";
require_once "../../jwt.php";

$method = $_SERVER['REQUEST_METHOD'];
$id     = null;

// Get ID from query parameter (e.g., /api/faculties/index.php?id=123)
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
                // GET /api/faculties/:id - Get specific faculty
                $stmt = $conn->prepare("SELECT id, name, created_at FROM faculties WHERE id = ?");
                $stmt->execute([$id]);
                $faculty = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$faculty) {
                    http_response_code(404);
                    echo json_encode([
                        "status" => "error",
                        "message" => "Faculty not found"
                    ]);
                    break;
                }
                
                echo json_encode([
                    "status" => "success",
                    "faculty" => $faculty
                ]);
            } else {
                // GET /api/faculties - List all faculties
                $stmt = $conn->prepare("SELECT id, name, created_at FROM faculties ORDER BY name");
                $stmt->execute();
                $faculties = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    "status" => "success",
                    "data" => $faculties,
                    "count" => count($faculties)
                ]);
            }
            break;

        case 'POST':
            // POST /api/faculties - Create new faculty
            // Handle JSON data
            $data = json_decode(file_get_contents("php://input"), true);
            
            $name = trim($data['name'] ?? '');

            // Validation
            if ($name === '') {
                echo json_encode([
                    "status" => "error",
                    "message" => "Faculty name is required"
                ]);
                break;
            }

            // Check if faculty already exists
            $chk = $conn->prepare("SELECT id FROM faculties WHERE name = ?");
            $chk->execute([$name]);
            if ($chk->fetch()) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Faculty already exists"
                ]);
                break;
            }

            // Create faculty
            $stmt = $conn->prepare("INSERT INTO faculties (name) VALUES (?)");
            $stmt->execute([$name]);
            
            $facultyId = $conn->lastInsertId();
            
            // Get created faculty
            $stmt = $conn->prepare("SELECT id, name, created_at FROM faculties WHERE id = ?");
            $stmt->execute([$facultyId]);
            $newFaculty = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "status" => "success",
                "message" => "Faculty created successfully",
                "faculty" => $newFaculty
            ]);
            break;

        case 'PUT':
            // PUT /api/faculties/:id - Update faculty (Simplified for beginners)
            $data = json_decode(file_get_contents("php://input"), true);

            if (!$id) {
                echo json_encode([
                    "status" => "error",
                    "message" => "ID required"
                ]);
                break;
            }

            // Find existing faculty FIRST
            $stmt = $conn->prepare("SELECT * FROM faculties WHERE id = ?");
            $stmt->execute([$id]);
            $faculty = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$faculty) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Faculty not found"
                ]);
                break;
            }

            // Read fields — fall back to existing data when a field is left blank
            $name = trim($data['name'] ?? $faculty['name']);

            // Validation
            if ($name === '') {
                echo json_encode([
                    "status" => "error",
                    "message" => "Faculty name is required"
                ]);
                break;
            }

            // Check if name is being updated and if it already exists (for another faculty)
            if ($name !== $faculty['name']) {
                $chk = $conn->prepare("SELECT id FROM faculties WHERE name = ? AND id != ?");
                $chk->execute([$name, $id]);
                if ($chk->fetch()) {
                    echo json_encode([
                        "status" => "error",
                        "message" => "Faculty already exists"
                    ]);
                    break;
                }
            }

            // Update faculty
            $stmt = $conn->prepare("UPDATE faculties SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);

            echo json_encode([
                "status" => "success",
                "message" => "Faculty updated successfully"
            ]);
            break;

        case 'DELETE':
            // DELETE /api/faculties/:id - Delete faculty
            if ($id === null) {
                echo json_encode([
                    "status" => "error",
                    "message" => "ID required"
                ]);
                break;
            }

            // Check if faculty exists
            $stmt = $conn->prepare("SELECT id FROM faculties WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Faculty not found"
                ]);
                break;
            }

            // Check if faculty has departments (prevent deletion if in use)
            $deptCheck = $conn->prepare("SELECT COUNT(*) FROM departments WHERE name LIKE ?");
            // Since departments don't have faculty_id in the schema, we need to check differently
            // Looking at the database schema, departments don't reference faculties directly
            // So we can allow deletion of faculties even if departments exist
            // But let's check if there's any relationship - actually looking at schema, there isn't
            
            // Delete faculty
            $del = $conn->prepare("DELETE FROM faculties WHERE id = ?");
            $del->execute([$id]);
            
            echo json_encode([
                "status" => "success",
                "message" => "Faculty deleted successfully"
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