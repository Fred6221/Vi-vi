<?php
// Database Configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'vivi';

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to ensure proper encoding
$conn->set_charset("utf8mb4");

/**
 * Execute a query and return the result
 * 
 * @param string $sql SQL query to execute
 * @param array $params Parameters to bind to the query
 * @param string $types Types of the parameters (i: integer, s: string, d: double, b: blob)
 * @return mysqli_result|bool Result of the query
 */
function executeQuery($sql, $params = [], $types = null) {
    global $conn;
    
    if (empty($params)) {
        return $conn->query($sql);
    }
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }
    
    if ($types === null) {
        $types = str_repeat('s', count($params));
    }
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $result ? $result : $stmt->affected_rows > 0;
}

/**
 * Get a single row from the database
 * 
 * @param string $sql SQL query to execute
 * @param array $params Parameters to bind to the query
 * @param string $types Types of the parameters
 * @return array|null The row as an associative array or null if not found
 */
function fetchRow($sql, $params = [], $types = null) {
    $result = executeQuery($sql, $params, $types);
    
    if ($result instanceof mysqli_result) {
        $row = $result->fetch_assoc();
        $result->free();
        return $row;
    }
    
    return null;
}

/**
 * Get multiple rows from the database
 * 
 * @param string $sql SQL query to execute
 * @param array $params Parameters to bind to the query
 * @param string $types Types of the parameters
 * @return array Array of rows as associative arrays
 */
function fetchAll($sql, $params = [], $types = null) {
    $result = executeQuery($sql, $params, $types);
    
    if ($result instanceof mysqli_result) {
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
        return $rows;
    }
    
    return [];
}

/**
 * Insert a row into the database
 * 
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @return int|bool The inserted ID or false on failure
 */
function insert($table, $data) {
    global $conn;
    
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    $result = executeQuery($sql, array_values($data));
    
    return $result ? $conn->insert_id : false;
}

/**
 * Update rows in the database
 * 
 * @param string $table Table name
 * @param array $data Associative array of column => value to update
 * @param string $where WHERE clause
 * @param array $whereParams Parameters for the WHERE clause
 * @return bool True on success, false on failure
 */
function update($table, $data, $where, $whereParams = []) {
    $set = [];
    foreach (array_keys($data) as $column) {
        $set[] = "$column = ?";
    }
    
    $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE $where";
    $params = array_merge(array_values($data), $whereParams);
    
    return executeQuery($sql, $params) ? true : false;
}

/**
 * Delete rows from the database
 * 
 * @param string $table Table name
 * @param string $where WHERE clause
 * @param array $params Parameters for the WHERE clause
 * @return bool True on success, false on failure
 */
function delete($table, $where, $params = []) {
    $sql = "DELETE FROM $table WHERE $where";
    return executeQuery($sql, $params) ? true : false;
}