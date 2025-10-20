<?php
/**
 * Automatic Migration Generator Class
 * This class analyzes database schema and generates all necessary migrations
 */

class AutoMigrationGenerator {
    private $db;
    private $pdo;
    private $migrationsPath;
    private $sqlFile;
    
    public function __construct() {
        global $db;
        $this->db = $db;
        $this->migrationsPath = __DIR__ . '/../migrations/';
        $this->sqlFile = __DIR__ . '/../inv_system (4).sql';
        
        // Create PDO connection
        try {
            $this->pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
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
        
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }
    }
    
    /**
     * Parse SQL file and extract table structures
     */
    public function parseSqlFile() {
        if (!file_exists($this->sqlFile)) {
            throw new Exception("SQL file not found: {$this->sqlFile}");
        }
        
        $sql = file_get_contents($this->sqlFile);
        $tables = [];
        
        // Extract CREATE TABLE statements
        preg_match_all('/CREATE TABLE `(\w+)` \((.*?)\) ENGINE=/s', $sql, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $tableName = $match[1];
            $tableDefinition = $match[2];
            
            $tables[$tableName] = [
                'name' => $tableName,
                'columns' => $this->parseTableColumns($tableDefinition),
                'indexes' => $this->parseTableIndexes($tableName, $sql),
                'data' => $this->parseTableData($tableName, $sql)
            ];
        }
        
        return $tables;
    }
    
    /**
     * Parse table columns from CREATE TABLE statement
     */
    private function parseTableColumns($definition) {
        $columns = [];
        $lines = explode("\n", $definition);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, 'PRIMARY KEY') !== false || 
                strpos($line, 'UNIQUE KEY') !== false || strpos($line, 'KEY') !== false ||
                strpos($line, 'FOREIGN KEY') !== false || strpos($line, 'CONSTRAINT') !== false) {
                continue;
            }
            
            // Remove trailing comma and parse column definition
            $line = rtrim($line, ',');
            if (preg_match('/`(\w+)` (.+)/', $line, $matches)) {
                $columnName = $matches[1];
                $columnDef = $matches[2];
                $columns[$columnName] = $columnDef;
            }
        }
        
        return $columns;
    }
    
    /**
     * Parse table indexes
     */
    private function parseTableIndexes($tableName, $sql) {
        $indexes = [];
        
        // Find ALTER TABLE statements for this table
        $pattern = "/ALTER TABLE `{$tableName}`\s+ADD\s+(PRIMARY\s+KEY|UNIQUE\s+KEY|KEY)\s+(?:`?(\w+)`?\s*)?\(([^)]+)\)/i";
        if (preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $type = trim($match[1]);
                $indexName = $match[2] ?? '';
                $columns = str_replace('`', '', $match[3]);
                
                $indexes[] = [
                    'type' => $type,
                    'name' => $indexName,
                    'columns' => $columns
                ];
            }
        }
        
        return $indexes;
    }
    
    /**
     * Parse table data (INSERT statements)
     */
    private function parseTableData($tableName, $sql) {
        $data = [];
        
        // Find INSERT statements for this table
        $pattern = "/INSERT INTO `{$tableName}` \(([^)]+)\) VALUES\s*(.*?);/s";
        if (preg_match($pattern, $sql, $matches)) {
            $columns = array_map('trim', explode(',', str_replace('`', '', $matches[1])));
            $valuesSection = $matches[2];
            
            // Parse VALUES tuples
            preg_match_all('/\(([^)]+)\)/', $valuesSection, $valueMatches);
            
            foreach ($valueMatches[1] as $valueSet) {
                $values = $this->parseValues($valueSet);
                if (count($values) === count($columns)) {
                    $row = array_combine($columns, $values);
                    $data[] = $row;
                }
            }
        }
        
        return array_slice($data, 0, 10); // Limit to first 10 rows for sample data
    }
    
    /**
     * Parse VALUES clause
     */
    private function parseValues($valueString) {
        $values = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = null;
        
        for ($i = 0; $i < strlen($valueString); $i++) {
            $char = $valueString[$i];
            
            if (($char === "'" || $char === '"') && !$inQuotes) {
                $inQuotes = true;
                $quoteChar = $char;
            } elseif ($char === $quoteChar && $inQuotes) {
                // Check for escaped quote
                if ($i + 1 < strlen($valueString) && $valueString[$i + 1] === $quoteChar) {
                    $current .= $char . $char;
                    $i++; // Skip next char
                } else {
                    $inQuotes = false;
                    $quoteChar = null;
                }
            } elseif ($char === ',' && !$inQuotes) {
                $values[] = $this->cleanValue(trim($current));
                $current = '';
            } else {
                $current .= $char;
            }
        }
        
        if (!empty(trim($current))) {
            $values[] = $this->cleanValue(trim($current));
        }
        
        return $values;
    }
    
    /**
     * Clean and format value
     */
    private function cleanValue($value) {
        if ($value === 'NULL') {
            return null;
        }
        
        // Remove quotes
        if ((str_starts_with($value, "'") && str_ends_with($value, "'")) ||
            (str_starts_with($value, '"') && str_ends_with($value, '"'))) {
            $value = substr($value, 1, -1);
        }
        
        return $value;
    }
    
    /**
     * Generate all migrations automatically
     */
    public function generateAllMigrations() {
        $tables = $this->parseSqlFile();
        $migrations = [];
        $timestamp = date('Y_m_d_His');
        
        foreach ($tables as $tableName => $tableInfo) {
            $migrationName = "create_{$tableName}_table";
            $className = "Migration_create_{$tableName}_table";
            $filename = "{$timestamp}_{$migrationName}.php";
            
            $content = $this->generateMigrationContent($className, $tableInfo);
            
            $filepath = $this->migrationsPath . $filename;
            file_put_contents($filepath, $content);
            
            $migrations[] = [
                'table' => $tableName,
                'filename' => $filename,
                'class' => $className,
                'path' => $filepath
            ];
            
            // Increment timestamp to maintain order
            sleep(1);
            $timestamp = date('Y_m_d_His');
        }
        
        return $migrations;
    }
    
    /**
     * Generate migration file content
     */
    private function generateMigrationContent($className, $tableInfo) {
        $tableName = $tableInfo['name'];
        $columns = $tableInfo['columns'];
        $indexes = $tableInfo['indexes'];
        $data = $tableInfo['data'];
        
        // Build column definitions
        $columnDefs = [];
        foreach ($columns as $colName => $colDef) {
            // Escape single quotes in column definitions
            $escapedColDef = str_replace("'", "\\'", $colDef);
            $columnDefs[] = "            '{$colName} {$escapedColDef}'";
        }
        $columnsStr = implode(",\n", $columnDefs);
        
        // Build data array
        $dataStr = '';
        if (!empty($data)) {
            $dataRows = [];
            foreach (array_slice($data, 0, 5) as $row) { // Limit to 5 sample rows
                $rowData = [];
                foreach ($row as $key => $value) {
                    if ($value === null) {
                        $rowData[] = "'{$key}' => null";
                    } else {
                        $escaped = addslashes($value);
                        $rowData[] = "'{$key}' => '{$escaped}'";
                    }
                }
                $dataRows[] = "            [" . implode(', ', $rowData) . "]";
            }
            $dataStr = "        \$sampleData = [\n" . implode(",\n", $dataRows) . "\n        ];\n        \$this->insertData('{$tableName}', \$sampleData);";
        }
        
        // Build indexes
        $indexStr = '';
        foreach ($indexes as $index) {
            if ($index['type'] !== 'PRIMARY KEY' && !empty($index['name'])) {
                $indexStr .= "        \$this->addIndex('{$tableName}', '{$index['name']}', '{$index['columns']}');\n";
            }
        }
        
        $template = "<?php
/**
 * Migration: Create {$tableName} table
 * Auto-generated on: " . date('Y-m-d H:i:s') . "
 */

require_once __DIR__ . '/../includes/Migration.php';

class {$className} extends Migration {
    
    public function up() {
        // Create {$tableName} table
        \$columns = [
{$columnsStr}
        ];
        
        \$this->createTable('{$tableName}', \$columns);
        
        // Add indexes
{$indexStr}
        
        // Insert sample data
        try {
{$dataStr}
        } catch (Exception \$e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        \$this->dropTable('{$tableName}');
    }
    
    public function getDescription() {
        return 'Create {$tableName} table with sample data';
    }
}";
        
        return $template;
    }
    
    /**
     * Check if migrations already exist
     */
    public function checkExistingMigrations() {
        $files = glob($this->migrationsPath . '*.php');
        $existing = [];
        
        foreach ($files as $file) {
            $existing[] = basename($file);
        }
        
        return $existing;
    }
    
    /**
     * Clean up old migrations
     */
    public function cleanupOldMigrations() {
        $files = glob($this->migrationsPath . '*.php');
        $deleted = [];
        
        foreach ($files as $file) {
            unlink($file);
            $deleted[] = basename($file);
        }
        
        return $deleted;
    }
}