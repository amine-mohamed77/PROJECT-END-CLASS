<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($data->action)) {
        switch ($data->action) {
            case 'create':
                createDemand($db, $data);
                break;
            case 'update':
                updateDemand($db, $data);
                break;
            case 'delete':
                deleteDemand($db, $data);
                break;
            default:
                echo json_encode(array("message" => "Invalid action"));
                break;
        }
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'list':
                listDemands($db, $_GET);
                break;
            case 'get':
                getDemand($db, $_GET['id']);
                break;
            default:
                echo json_encode(array("message" => "Invalid action"));
                break;
        }
    }
}

function createDemand($db, $data) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "User not authenticated"));
        return;
    }

    $query = "INSERT INTO demands (student_id, title, type, max_price, location, university, 
              bedrooms, move_in_date, duration, description, preferences, contact) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($query);
    
    $preferences = isset($data->preferences) ? json_encode($data->preferences) : '[]';
    
    if ($stmt->execute([
        $_SESSION['user_id'],
        $data->title,
        $data->type,
        $data->max_price,
        $data->location,
        $data->university,
        $data->bedrooms,
        $data->move_in_date,
        $data->duration,
        $data->description,
        $preferences,
        $data->contact
    ])) {
        $demand_id = $db->lastInsertId();
        
        echo json_encode(array(
            "success" => true,
            "message" => "Housing demand created successfully",
            "demand_id" => $demand_id
        ));
    } else {
        echo json_encode(array("success" => false, "message" => "Failed to create housing demand"));
    }
}

function updateDemand($db, $data) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "User not authenticated"));
        return;
    }

    // Verify ownership
    $query = "SELECT student_id FROM demands WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$data->id]);
    $demand = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($demand['student_id'] !== $_SESSION['user_id']) {
        echo json_encode(array("success" => false, "message" => "Unauthorized"));
        return;
    }

    $query = "UPDATE demands SET 
              title = ?, type = ?, max_price = ?, location = ?, university = ?,
              bedrooms = ?, move_in_date = ?, duration = ?, description = ?,
              preferences = ?, contact = ?
              WHERE id = ?";
    
    $stmt = $db->prepare($query);
    
    $preferences = isset($data->preferences) ? json_encode($data->preferences) : '[]';
    
    if ($stmt->execute([
        $data->title,
        $data->type,
        $data->max_price,
        $data->location,
        $data->university,
        $data->bedrooms,
        $data->move_in_date,
        $data->duration,
        $data->description,
        $preferences,
        $data->contact,
        $data->id
    ])) {
        echo json_encode(array("success" => true, "message" => "Housing demand updated successfully"));
    } else {
        echo json_encode(array("success" => false, "message" => "Failed to update housing demand"));
    }
}

function deleteDemand($db, $data) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "User not authenticated"));
        return;
    }

    // Verify ownership
    $query = "SELECT student_id FROM demands WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$data->id]);
    $demand = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($demand['student_id'] !== $_SESSION['user_id']) {
        echo json_encode(array("success" => false, "message" => "Unauthorized"));
        return;
    }

    $query = "DELETE FROM demands WHERE id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$data->id])) {
        echo json_encode(array("success" => true, "message" => "Housing demand deleted successfully"));
    } else {
        echo json_encode(array("success" => false, "message" => "Failed to delete housing demand"));
    }
}

function listDemands($db, $params) {
    $query = "SELECT d.*, u.name as student_name, u.email as student_email
              FROM demands d
              JOIN users u ON d.student_id = u.id
              WHERE 1=1";
    
    $values = array();
    
    if (isset($params['type'])) {
        $query .= " AND d.type = ?";
        $values[] = $params['type'];
    }
    
    if (isset($params['max_price'])) {
        $query .= " AND d.max_price <= ?";
        $values[] = $params['max_price'];
    }
    
    if (isset($params['location'])) {
        $query .= " AND d.location LIKE ?";
        $values[] = "%" . $params['location'] . "%";
    }
    
    if (isset($params['university'])) {
        $query .= " AND d.university LIKE ?";
        $values[] = "%" . $params['university'] . "%";
    }
    
    if (isset($params['move_in_date'])) {
        $query .= " AND d.move_in_date >= ?";
        $values[] = $params['move_in_date'];
    }
    
    $query .= " ORDER BY d.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($values);
    
    $demands = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['preferences'] = json_decode($row['preferences']);
        $demands[] = $row;
    }
    
    echo json_encode(array("success" => true, "demands" => $demands));
}

function getDemand($db, $id) {
    $query = "SELECT d.*, u.name as student_name, u.email as student_email
              FROM demands d
              JOIN users u ON d.student_id = u.id
              WHERE d.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    
    if ($demand = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $demand['preferences'] = json_decode($demand['preferences']);
        echo json_encode(array("success" => true, "demand" => $demand));
    } else {
        echo json_encode(array("success" => false, "message" => "Housing demand not found"));
    }
}
?> 