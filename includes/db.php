<?php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $connection = null;
    private $queryCount = 0;

    private function __construct() {
        $this->connect();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false, // Changed to false for remote connection
                PDO::ATTR_TIMEOUT => 10, // 10 seconds timeout
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);

        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            error_log("DSN: mysql:host=" . DB_HOST . ":" . DB_PORT . ";dbname=" . DB_NAME);

            // More detailed error message
            $errorMsg = "Database connection failed: " . $e->getMessage() . " (Host: " . DB_HOST . ":" . DB_PORT . ", DB: " . DB_NAME . ")";
            throw new Exception($errorMsg);
        }
    }

    public function getConnection() {
        // Check if connection is still alive
        try {
            $this->connection->query('SELECT 1');
        } catch (PDOException $e) {
            // Reconnect if connection lost
            $this->connect();
        }
        return $this->connection;
    }

    public function query($sql, $params = [], $retries = 3) {
        $attempt = 0;

        while ($attempt < $retries) {
            try {
                $stmt = $this->connection->prepare($sql);
                $stmt->execute($params);
                $this->queryCount++;
                return $stmt;

            } catch (PDOException $e) {
                $attempt++;

                if ($attempt >= $retries) {
                    error_log("Query failed after {$retries} attempts: " . $e->getMessage());
                    error_log("SQL: {$sql}");
                    throw $e;
                }

                // Exponential backoff
                usleep(1000 * pow(2, $attempt));

                // Try to reconnect
                $this->connect();
            }
        }
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    public function commit() {
        return $this->connection->commit();
    }

    public function rollback() {
        return $this->connection->rollBack();
    }

    public function getQueryCount() {
        return $this->queryCount;
    }
}

// Helper function to get database instance
function db() {
    return Database::getInstance();
}
?>
