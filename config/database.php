<?php
require_once __DIR__ . '/config.php';

// Global PDO connection variable
$pdo = null;

/**
 * Get PDO database connection
 * @return PDO
 */
function getConnection() {
    global $pdo;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            if (DEBUG_MODE) {
                error_log("Database connection established successfully");
            }
        } catch (PDOException $e) {
            $error_message = "Database connection failed: " . $e->getMessage();
            
            if (LOG_ERRORS) {
                error_log($error_message);
            }
            
            if (DEBUG_MODE) {
                die($error_message);
            } else {
                die("Database connection failed. Please try again later.");
            }
        }
    }
    
    return $pdo;
}

/**
 * Execute a query with parameters
 * @param string $sql SQL query
 * @param array $params Parameters for the query
 * @return PDOStatement
 */
function executeQuery($sql, $params = []) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if (DEBUG_MODE) {
            error_log("Query executed: " . $sql . " | Params: " . json_encode($params));
        }
        
        return $stmt;
    } catch (PDOException $e) {
        $error_message = "Query execution failed: " . $e->getMessage() . " | SQL: " . $sql;
        
        if (LOG_ERRORS) {
            error_log($error_message);
        }
        
        if (DEBUG_MODE) {
            throw new Exception($error_message);
        } else {
            throw new Exception("Database operation failed. Please try again.");
        }
    }
}

/**
 * Fetch a single row from the database
 * @param string $sql SQL query
 * @param array $params Parameters for the query
 * @return array|false
 */
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Fetch all rows from the database
 * @param string $sql SQL query
 * @param array $params Parameters for the query
 * @return array
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Get the last inserted ID
 * @return string
 */
function getLastInsertId() {
    $pdo = getConnection();
    return $pdo->lastInsertId();
}

/**
 * Begin a database transaction
 * @return bool
 */
function beginTransaction() {
    $pdo = getConnection();
    return $pdo->beginTransaction();
}

/**
 * Commit a database transaction
 * @return bool
 */
function commitTransaction() {
    $pdo = getConnection();
    return $pdo->commit();
}

/**
 * Rollback a database transaction
 * @return bool
 */
function rollbackTransaction() {
    $pdo = getConnection();
    return $pdo->rollBack();
}

/**
 * Check if a record exists
 * @param string $table Table name
 * @param string $column Column name
 * @param mixed $value Value to check
 * @return bool
 */
function recordExists($table, $column, $value) {
    $sql = "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = ?";
    $stmt = executeQuery($sql, [$value]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Insert a record into the database
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @return string Last insert ID
 */
function insertRecord($table, $data) {
    $columns = array_keys($data);
    $placeholders = array_fill(0, count($columns), '?');
    
    $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
    executeQuery($sql, array_values($data));
    
    return getLastInsertId();
}

/**
 * Update a record in the database
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @param string $whereColumn Where column name
 * @param mixed $whereValue Where column value
 * @return int Number of affected rows
 */
function updateRecord($table, $data, $whereColumn, $whereValue) {
    $setParts = [];
    $values = [];
    
    foreach ($data as $column => $value) {
        $setParts[] = "`{$column}` = ?";
        $values[] = $value;
    }
    
    $values[] = $whereValue;
    
    $sql = "UPDATE `{$table}` SET " . implode(', ', $setParts) . " WHERE `{$whereColumn}` = ?";
    $stmt = executeQuery($sql, $values);
    
    return $stmt->rowCount();
}

/**
 * Delete a record from the database
 * @param string $table Table name
 * @param string $whereColumn Where column name
 * @param mixed $whereValue Where column value
 * @return int Number of affected rows
 */
function deleteRecord($table, $whereColumn, $whereValue) {
    $sql = "DELETE FROM `{$table}` WHERE `{$whereColumn}` = ?";
    $stmt = executeQuery($sql, [$whereValue]);
    
    return $stmt->rowCount();
}
?>