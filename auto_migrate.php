<?php
/**
 * Automatic Migration Generator
 * This script analyzes your database schema and generates all necessary migrations
 */

require_once 'includes/load.php';
require_once 'includes/Migration.php';

class AutoMigrationGenerator {
    private $db;
    private $pdo;
    private $migrationsPath;
    private $sqlFile;
    
    public function __construct() {
        global $db;
        $this->db = $db;
        $this->migrationsPath = __DIR__ . '/migrations/';
        $this->sqlFile = __DIR__ . '/inv_system (4).sql';
        
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

// Handle requests
if (isset($_POST['action'])) {
    $generator = new AutoMigrationGenerator();
    
    try {
        switch ($_POST['action']) {
            case 'generate':
                $cleanFirst = isset($_POST['clean_first']) && $_POST['clean_first'] === 'true';
                
                if ($cleanFirst) {
                    $deleted = $generator->cleanupOldMigrations();
                }
                
                $migrations = $generator->generateAllMigrations();
                
                echo json_encode([
                    'success' => true,
                    'migrations' => $migrations,
                    'deleted' => $deleted ?? [],
                    'message' => count($migrations) . ' migrations generated successfully'
                ]);
                break;
                
            case 'check':
                $existing = $generator->checkExistingMigrations();
                $tables = array_keys($generator->parseSqlFile());
                
                echo json_encode([
                    'success' => true,
                    'existing_migrations' => $existing,
                    'tables_found' => $tables,
                    'count' => count($tables)
                ]);
                break;
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

$generator = new AutoMigrationGenerator();
$existingMigrations = $generator->checkExistingMigrations();
$tablesFound = 0;

try {
    $tables = $generator->parseSqlFile();
    $tablesFound = count($tables);
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automatic Migration Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .log-container { height: 400px; overflow-y: auto; }
        .schema-preview { max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-magic"></i> Automatic Migration Generator</h1>
                    <div>
                        <a href="migrate.php" class="btn btn-info">
                            <i class="fas fa-list"></i> View Migrations
                        </a>
                        <a href="create_migration.php" class="btn btn-secondary">
                            <i class="fas fa-plus"></i> Create Custom
                        </a>
                    </div>
                </div>
                
                <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Error: <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <!-- Overview Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle"></i> Database Schema Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center">
                                    <h3 class="text-primary"><?php echo $tablesFound; ?></h3>
                                    <p class="text-muted">Tables Found</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <h3 class="text-warning"><?php echo count($existingMigrations); ?></h3>
                                    <p class="text-muted">Existing Migrations</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <h3 class="text-success" id="pending-count"><?php echo max(0, $tablesFound - count($existingMigrations)); ?></h3>
                                    <p class="text-muted">Migrations Needed</p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($existingMigrations)): ?>
                        <div class="mt-3">
                            <h6>Existing Migrations:</h6>
                            <div class="schema-preview bg-light p-2 rounded">
                                <?php foreach ($existingMigrations as $migration): ?>
                                <span class="badge bg-secondary me-1"><?php echo htmlspecialchars($migration); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Actions Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-cogs"></i> Generate Migrations</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="cleanFirst" <?php echo empty($existingMigrations) ? 'disabled' : ''; ?>>
                                <label class="form-check-label" for="cleanFirst">
                                    Clean existing migrations first
                                    <small class="text-muted d-block">This will delete all existing migration files before generating new ones</small>
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex">
                            <button class="btn btn-primary btn-lg" onclick="generateMigrations()">
                                <i class="fas fa-magic"></i> Generate All Migrations
                            </button>
                            <button class="btn btn-outline-secondary" onclick="checkSchema()">
                                <i class="fas fa-search"></i> Analyze Schema
                            </button>
                        </div>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                This will analyze your <code>inv_system (4).sql</code> file and automatically create migrations for all tables, 
                                including their structure, indexes, and sample data.
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Results Log -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-terminal"></i> Generation Log</h5>
                        <button class="btn btn-sm btn-outline-secondary" onclick="clearLog()">Clear</button>
                    </div>
                    <div class="card-body log-container bg-dark text-light" id="log-output">
                        <div class="text-muted">Ready to generate migrations...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function log(message, type = 'info') {
            const logOutput = document.getElementById('log-output');
            const timestamp = new Date().toLocaleTimeString();
            const colorClass = type === 'error' ? 'text-danger' : type === 'success' ? 'text-success' : 'text-info';
            
            logOutput.innerHTML += `<div class="${colorClass}">[${timestamp}] ${message}</div>`;
            logOutput.scrollTop = logOutput.scrollHeight;
        }
        
        function clearLog() {
            document.getElementById('log-output').innerHTML = '<div class="text-muted">Log cleared...</div>';
        }
        
        async function checkSchema() {
            log('Analyzing database schema...');
            
            try {
                const response = await fetch('auto_migrate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=check'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    log(`Found ${result.count} tables in database schema`, 'success');
                    result.tables_found.forEach(table => {
                        log(`  - ${table}`, 'info');
                    });
                    
                    if (result.existing_migrations.length > 0) {
                        log(`Found ${result.existing_migrations.length} existing migrations:`, 'info');
                        result.existing_migrations.forEach(migration => {
                            log(`  - ${migration}`, 'info');
                        });
                    }
                } else {
                    log(`Error: ${result.error}`, 'error');
                }
            } catch (error) {
                log(`Network error: ${error.message}`, 'error');
            }
        }
        
        async function generateMigrations() {
            const cleanFirst = document.getElementById('cleanFirst').checked;
            
            log('Starting automatic migration generation...');
            if (cleanFirst) {
                log('Will clean existing migrations first', 'info');
            }
            
            try {
                const response = await fetch('auto_migrate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=generate&clean_first=${cleanFirst}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (result.deleted && result.deleted.length > 0) {
                        log(`Cleaned ${result.deleted.length} existing migrations`, 'info');
                    }
                    
                    log(result.message, 'success');
                    
                    result.migrations.forEach(migration => {
                        log(`✓ Generated: ${migration.filename} for table '${migration.table}'`, 'success');
                    });
                    
                    log('All migrations generated successfully!', 'success');
                    log('You can now run them at: migrate.php', 'info');
                    
                    // Update counters
                    document.getElementById('pending-count').textContent = result.migrations.length;
                    
                    // Refresh page after 2 seconds
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    log(`Error: ${result.error}`, 'error');
                }
            } catch (error) {
                log(`Network error: ${error.message}`, 'error');
            }
        }
    </script>
</body>
</html>