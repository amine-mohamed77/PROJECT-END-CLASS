<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($data->action)) {
        switch ($data->action) {
            case 'add':
                addFavorite($db, $data);
                break;
            case 'remove':
                removeFavorite($db, $data);
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
                listFavorites($db);
                break;
            case 'check':
                checkFavorite($db, $_GET);
                break;
            default:
                echo json_encode(array("message" => "Invalid action"));
                break;
        }
    }
}

function addFavorite($db, $data) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "User not authenticated"));
        return;
    }

    // Check if already favorited
    $query = "SELECT id FROM favorites WHERE user_id = ? AND (offer_id = ? OR demand_id = ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([
        $_SESSION['user_id'],
        $data->offer_id ?? null,
        $data->demand_id ?? null
    ]);
    
    if ($stmt->fetch()) {
        echo json_encode(array("success" => false, "message" => "Already in favorites"));
        return;
    }

    $query = "INSERT INTO favorites (user_id, offer_id, demand_id) VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([
        $_SESSION['user_id'],
        $data->offer_id ?? null,
        $data->demand_id ?? null
    ])) {
        echo json_encode(array(
            "success" => true,
            "message" => "Added to favorites",
            "favorite_id" => $db->lastInsertId()
        ));
    } else {
        echo json_encode(array("success" => false, "message" => "Failed to add to favorites"));
    }
}

function removeFavorite($db, $data) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "User not authenticated"));
        return;
    }

    $query = "DELETE FROM favorites 
              WHERE user_id = ? AND (offer_id = ? OR demand_id = ?)";
    
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([
        $_SESSION['user_id'],
        $data->offer_id ?? null,
        $data->demand_id ?? null
    ])) {
        echo json_encode(array("success" => true, "message" => "Removed from favorites"));
    } else {
        echo json_encode(array("success" => false, "message" => "Failed to remove from favorites"));
    }
}

function listFavorites($db) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "User not authenticated"));
        return;
    }

    $query = "SELECT f.*, 
              o.title as offer_title, o.type as offer_type, o.price as offer_price,
              o.location as offer_location, o.university as offer_university,
              o.bedrooms as offer_bedrooms, o.bathrooms as offer_bathrooms,
              o.area as offer_area, o.status as offer_status,
              d.title as demand_title, d.type as demand_type, d.max_price as demand_price,
              d.location as demand_location, d.university as demand_university,
              d.bedrooms as demand_bedrooms, d.move_in_date as demand_move_in_date,
              d.duration as demand_duration, d.status as demand_status,
              (
                  SELECT image_path 
                  FROM offer_images 
                  WHERE offer_id = o.id 
                  LIMIT 1
              ) as offer_image
              FROM favorites f
              LEFT JOIN offers o ON f.offer_id = o.id
              LEFT JOIN demands d ON f.demand_id = d.id
              WHERE f.user_id = ?
              ORDER BY f.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    
    $favorites = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['offer_id']) {
            $row['type'] = 'offer';
            $row['title'] = $row['offer_title'];
            $row['price'] = $row['offer_price'];
            $row['location'] = $row['offer_location'];
            $row['university'] = $row['offer_university'];
            $row['bedrooms'] = $row['offer_bedrooms'];
            $row['status'] = $row['offer_status'];
            $row['image'] = $row['offer_image'];
        } else {
            $row['type'] = 'demand';
            $row['title'] = $row['demand_title'];
            $row['price'] = $row['demand_price'];
            $row['location'] = $row['demand_location'];
            $row['university'] = $row['demand_university'];
            $row['bedrooms'] = $row['demand_bedrooms'];
            $row['status'] = $row['demand_status'];
            $row['move_in_date'] = $row['demand_move_in_date'];
            $row['duration'] = $row['demand_duration'];
        }
        
        // Remove redundant fields
        unset($row['offer_title'], $row['offer_type'], $row['offer_price'],
              $row['offer_location'], $row['offer_university'], $row['offer_bedrooms'],
              $row['offer_bathrooms'], $row['offer_area'], $row['offer_status'],
              $row['demand_title'], $row['demand_type'], $row['demand_price'],
              $row['demand_location'], $row['demand_university'], $row['demand_bedrooms'],
              $row['demand_move_in_date'], $row['demand_duration'], $row['demand_status'],
              $row['offer_image']);
        
        $favorites[] = $row;
    }
    
    echo json_encode(array("success" => true, "favorites" => $favorites));
}

function checkFavorite($db, $params) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "User not authenticated"));
        return;
    }

    $query = "SELECT id FROM favorites 
              WHERE user_id = ? AND (offer_id = ? OR demand_id = ?)";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        $_SESSION['user_id'],
        $params['offer_id'] ?? null,
        $params['demand_id'] ?? null
    ]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(array(
        "success" => true,
        "is_favorite" => $result !== false
    ));
}
?> 