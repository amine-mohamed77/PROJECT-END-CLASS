<?php
// Database configuration
$host = 'localhost';
$dbname = 'student_housing';
$username = 'root';
$password = '123456';
$charset = 'utf8mb4';

// DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

// PDO options
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

// Create PDO instance
try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Log error (to a file in a real application)
    error_log('Connection Error: ' . $e->getMessage());
    
    // Display user-friendly message
    die('Sorry, there was a problem connecting to the database. Please try again later.');
}

// Function to execute queries
function executeQuery($sql, $params = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        // Log error
        error_log('Query Error: ' . $e->getMessage());
        
        // Return false to indicate failure
        return false;
    }
}

// Function to get a single row
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

// Function to get multiple rows
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetchAll() : false;
}

// Function to insert data and return the ID
function insert($table, $data) {
    global $pdo;
    
    // Build the query
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($data));
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log('Insert Error: ' . $e->getMessage());
        return false;
    }
}

// Function to update data
function update($table, $data, $where, $whereParams = []) {
    global $pdo;
    
    // Build the SET part of the query
    $setParts = [];
    $params = [];
    
    foreach ($data as $column => $value) {
        $setParts[] = "$column = ?";
        $params[] = $value;
    }
    
    $setClause = implode(', ', $setParts);
    
    // Add where parameters to the params array
    $params = array_merge($params, $whereParams);
    
    $sql = "UPDATE $table SET $setClause WHERE $where";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log('Update Error: ' . $e->getMessage());
        return false;
    }
}

// Function to delete data
function delete($table, $where, $params = []) {
    $sql = "DELETE FROM $table WHERE $where";
    
    try {
        $stmt = executeQuery($sql, $params);
        return $stmt ? $stmt->rowCount() : false;
    } catch (PDOException $e) {
        error_log('Delete Error: ' . $e->getMessage());
        return false;
    }
}
?>