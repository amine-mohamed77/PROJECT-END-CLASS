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
                createOffer($db, $data);
                break;
            case 'update':
                updateOffer($db, $data);
                break;
            case 'delete':
                deleteOffer($db, $data);
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
                listOffers($db, $_GET);
                break;
            case 'get':
                getOffer($db, $_GET['id']);
                break;
            default:
                echo json_encode(array("message" => "Invalid action"));
                break;
        }
    }
}

function createOffer($db, $data) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "User not authenticated"));
        return;
    }

    $query = "INSERT INTO offers (owner_id, title, type, price, location, university, bedrooms, 
              bathrooms, area, description, amenities, contact) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($query);
    
    $amenities = isset($data->amenities) ? json_encode($data->amenities) : '[]';
    
    if ($stmt->execute([
        $_SESSION['user_id'],
        $data->title,
        $data->type,
        $data->price,
        $data->location,
        $data->university,
        $data->bedrooms,
        $data->bathrooms,
        $data->area,
        $data->description,
        $amenities,
        $data->contact
    ])) {
        $offer_id = $db->lastInsertId();
        
        // Handle image uploads
        if (isset($_FILES['images'])) {
            $upload_dir = "../uploads/offers/" . $offer_id . "/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                $file_name = $_FILES['images']['name'][$key];
                $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
                $new_file_name = uniqid() . "." . $file_ext;
                
                if (move_uploaded_file($tmp_name, $upload_dir . $new_file_name)) {
                    $query = "INSERT INTO offer_images (offer_id, image_path) VALUES (?, ?)";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$offer_id, "uploads/offers/" . $offer_id . "/" . $new_file_name]);
                }
            }
        }
        
        echo json_encode(array(
            "success" => true,
            "message" => "Property listing created successfully",
            "offer_id" => $offer_id
        ));
    } else {
        echo json_encode(array("success" => false, "message" => "Failed to create property listing"));
    }
}

function updateOffer($db, $data) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "User not authenticated"));
        return;
    }

    // Verify ownership
    $query = "SELECT owner_id FROM offers WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$data->id]);
    $offer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($offer['owner_id'] !== $_SESSION['user_id']) {
        echo json_encode(array("success" => false, "message" => "Unauthorized"));
        return;
    }

    $query = "UPDATE offers SET 
              title = ?, type = ?, price = ?, location = ?, university = ?,
              bedrooms = ?, bathrooms = ?, area = ?, description = ?,
              amenities = ?, contact = ?
              WHERE id = ?";
    
    $stmt = $db->prepare($query);
    
    $amenities = isset($data->amenities) ? json_encode($data->amenities) : '[]';
    
    if ($stmt->execute([
        $data->title,
        $data->type,
        $data->price,
        $data->location,
        $data->university,
        $data->bedrooms,
        $data->bathrooms,
        $data->area,
        $data->description,
        $amenities,
        $data->contact,
        $data->id
    ])) {
        echo json_encode(array("success" => true, "message" => "Property listing updated successfully"));
    } else {
        echo json_encode(array("success" => false, "message" => "Failed to update property listing"));
    }
}

function deleteOffer($db, $data) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(array("success" => false, "message" => "User not authenticated"));
        return;
    }

    // Verify ownership
    $query = "SELECT owner_id FROM offers WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$data->id]);
    $offer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($offer['owner_id'] !== $_SESSION['user_id']) {
        echo json_encode(array("success" => false, "message" => "Unauthorized"));
        return;
    }

    // Delete images
    $query = "DELETE FROM offer_images WHERE offer_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$data->id]);

    // Delete offer
    $query = "DELETE FROM offers WHERE id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$data->id])) {
        echo json_encode(array("success" => true, "message" => "Property listing deleted successfully"));
    } else {
        echo json_encode(array("success" => false, "message" => "Failed to delete property listing"));
    }
}

function listOffers($db, $params) {
    $query = "SELECT o.*, u.name as owner_name, 
              (SELECT image_path FROM offer_images WHERE offer_id = o.id LIMIT 1) as main_image
              FROM offers o
              JOIN users u ON o.owner_id = u.id
              WHERE 1=1";
    
    $values = array();
    
    if (isset($params['type'])) {
        $query .= " AND o.type = ?";
        $values[] = $params['type'];
    }
    
    if (isset($params['min_price'])) {
        $query .= " AND o.price >= ?";
        $values[] = $params['min_price'];
    }
    
    if (isset($params['max_price'])) {
        $query .= " AND o.price <= ?";
        $values[] = $params['max_price'];
    }
    
    if (isset($params['location'])) {
        $query .= " AND o.location LIKE ?";
        $values[] = "%" . $params['location'] . "%";
    }
    
    if (isset($params['university'])) {
        $query .= " AND o.university LIKE ?";
        $values[] = "%" . $params['university'] . "%";
    }
    
    $query .= " ORDER BY o.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($values);
    
    $offers = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['amenities'] = json_decode($row['amenities']);
        $offers[] = $row;
    }
    
    echo json_encode(array("success" => true, "offers" => $offers));
}

function getOffer($db, $id) {
    $query = "SELECT o.*, u.name as owner_name, u.email as owner_email
              FROM offers o
              JOIN users u ON o.owner_id = u.id
              WHERE o.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    
    if ($offer = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $offer['amenities'] = json_decode($offer['amenities']);
        
        // Get images
        $query = "SELECT image_path FROM offer_images WHERE offer_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        
        $images = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $images[] = $row['image_path'];
        }
        
        $offer['images'] = $images;
        
        echo json_encode(array("success" => true, "offer" => $offer));
    } else {
        echo json_encode(array("success" => false, "message" => "Property not found"));
    }
}
?> 