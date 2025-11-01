<?php
/**
 * Base Migration Class
 * Provides core functionality for database migrations
 */

require_once 'config.php';
require_once 'database.php';

abstract class Migration {
    protected $db;
    protected $pdo;
    
    public function __construct() {
        global $db;
        $this->db = $db;
        
        // Create PDO connection for advanced operations
        try {
            $this->pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            // Try PostgreSQL if MySQL fails (for Render deployment)
            try {
                $this->pdo = new PDO(
                    "pgsql:host=" . DB_HOST . ";port=" . (defined('DB_PORT') ? DB_PORT : 5432) . ";dbname=" . DB_NAME,
                    DB_USER,
                    DB_PASS,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
            } catch (PDOException $e2) {
                throw new Exception("Database connection failed: " . $e2->getMessage());
            }
        }
        
        $this->createMigrationsTable();
    }
    
    /**
     * Create migrations tracking table
     */
    private function createMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        // PostgreSQL version
        if ($this->isPostgreSQL()) {
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id SERIAL PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
        }
        
        $this->pdo->exec($sql);
    }
    
    /**
     * Check if using PostgreSQL
     */
    private function isPostgreSQL() {
        return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql';
    }
    
    /**
     * Check if migration has been executed
     */
    public function hasRun($migrationName) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM migrations WHERE migration = ?");
        $stmt->execute([$migrationName]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Mark migration as executed
     */
    public function markAsRun($migrationName) {
        $stmt = $this->pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
        return $stmt->execute([$migrationName]);
    }
    
    /**
     * Execute SQL with error handling
     */
    protected function executeSQL($sql) {
        try {
            return $this->pdo->exec($sql);
        } catch (PDOException $e) {
            throw new Exception("SQL Error: " . $e->getMessage() . "\nSQL: " . $sql);
        }
    }
    
    /**
     * Execute multiple SQL statements
     */
    protected function executeSQLBatch($sqlArray) {
        $this->pdo->beginTransaction();
        try {
            foreach ($sqlArray as $sql) {
                if (trim($sql)) {
                    $this->executeSQL($sql);
                }
            }
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw $e;
        }
    }
    
    /**
     * Add column to table
     */
    protected function addColumn($table, $column, $definition) {
        $sql = "ALTER TABLE {$table} ADD COLUMN {$column} {$definition}";
        return $this->executeSQL($sql);
    }
    
    /**
     * Drop column from table
     */
    protected function dropColumn($table, $column) {
        $sql = "ALTER TABLE {$table} DROP COLUMN {$column}";
        return $this->executeSQL($sql);
    }
    
    /**
     * Create table
     */
    protected function createTable($table, $columns) {
        $sql = "CREATE TABLE {$table} (\n    " . implode(",\n    ", $columns) . "\n)";
        return $this->executeSQL($sql);
    }
    
    /**
     * Drop table
     */
    protected function dropTable($table) {
        $sql = "DROP TABLE IF EXISTS {$table}";
        return $this->executeSQL($sql);
    }
    
    /**
     * Add index
     */
    protected function addIndex($table, $indexName, $columns) {
        $columnList = is_array($columns) ? implode(', ', $columns) : $columns;
        $sql = "CREATE INDEX {$indexName} ON {$table} ({$columnList})";
        return $this->executeSQL($sql);
    }
    
    /**
     * Drop index
     */
    protected function dropIndex($indexName, $table = null) {
        if ($this->isPostgreSQL()) {
            $sql = "DROP INDEX IF EXISTS {$indexName}";
        } else {
            $sql = "DROP INDEX {$indexName}" . ($table ? " ON {$table}" : "");
        }
        return $this->executeSQL($sql);
    }
    
    /**
     * Insert data
     */
    protected function insertData($table, $data) {
        if (empty($data)) return;
        
        $columns = array_keys($data[0]);
        $placeholders = ':' . implode(', :', $columns);
        $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES ({$placeholders})";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($data as $row) {
            $stmt->execute($row);
        }
    }
    
    // Abstract methods that must be implemented
    abstract public function up();
    abstract public function down();
    abstract public function getDescription();
}