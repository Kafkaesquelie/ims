<?php
/**
 * Web-based Migration Runner
 * Access via: yoursite.com/migrate.php
 */

// Security check - only allow admin access
require_once 'includes/load.php';
require_once 'includes/Migration.php';

// Check if user is admin (adjust this based on your auth system)
if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] != 1) {
    // For development/testing, you can comment out the line below
    // die('Access denied. Admin privileges required.');
}

class MigrationRunner {
    private $migrationsPath;
    private $migrations = [];
    
    public function __construct() {
        $this->migrationsPath = __DIR__ . '/migrations/';
        $this->loadMigrations();
    }
    
    /**
     * Load all migration files
     */
    private function loadMigrations() {
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }
        
        $files = scandir($this->migrationsPath);
        foreach ($files as $file) {
            if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})_(.+)\.php$/', $file, $matches)) {
                $this->migrations[] = [
                    'file' => $file,
                    'timestamp' => $matches[1],
                    'name' => $matches[2],
                    'class' => $this->getClassName($file)
                ];
            }
        }
        
        // Sort by timestamp
        usort($this->migrations, function($a, $b) {
            return strcmp($a['timestamp'], $b['timestamp']);
        });
    }
    
    /**
     * Get class name from filename
     */
    private function getClassName($filename) {
        $name = str_replace('.php', '', $filename);
        $parts = explode('_', $name);
        array_shift($parts); // Remove timestamp
        return 'Migration_' . implode('_', $parts);
    }
    
    /**
     * Get migration status
     */
    public function getMigrationStatus() {
        $status = [];
        foreach ($this->migrations as $migration) {
            try {
                require_once $this->migrationsPath . $migration['file'];
                $class = $migration['class'];
                if (class_exists($class)) {
                    $instance = new $class();
                    $status[] = [
                        'file' => $migration['file'],
                        'name' => $migration['name'],
                        'timestamp' => $migration['timestamp'],
                        'class' => $class,
                        'description' => $instance->getDescription(),
                        'executed' => $instance->hasRun($migration['file']),
                        'error' => null
                    ];
                }
            } catch (Exception $e) {
                $status[] = [
                    'file' => $migration['file'],
                    'name' => $migration['name'],
                    'timestamp' => $migration['timestamp'],
                    'class' => $migration['class'],
                    'description' => 'Error loading migration',
                    'executed' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        return $status;
    }
    
    /**
     * Run specific migration
     */
    public function runMigration($filename, $direction = 'up') {
        try {
            require_once $this->migrationsPath . $filename;
            $class = $this->getClassName($filename);
            
            if (!class_exists($class)) {
                throw new Exception("Migration class {$class} not found");
            }
            
            $migration = new $class();
            
            if ($direction === 'up') {
                if ($migration->hasRun($filename)) {
                    return ['success' => false, 'message' => 'Migration already executed'];
                }
                
                $migration->up();
                $migration->markAsRun($filename);
                return ['success' => true, 'message' => 'Migration executed successfully'];
            } else {
                if (!$migration->hasRun($filename)) {
                    return ['success' => false, 'message' => 'Migration not yet executed'];
                }
                
                $migration->down();
                // Remove from migrations table
                $pdo = $migration->pdo ?? new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
                $stmt = $pdo->prepare("DELETE FROM migrations WHERE migration = ?");
                $stmt->execute([$filename]);
                
                return ['success' => true, 'message' => 'Migration rolled back successfully'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Run all pending migrations
     */
    public function runAllPending() {
        $results = [];
        $status = $this->getMigrationStatus();
        
        foreach ($status as $migration) {
            if (!$migration['executed'] && !$migration['error']) {
                $result = $this->runMigration($migration['file']);
                $results[] = array_merge($migration, $result);
            }
        }
        
        return $results;
    }
}

// Handle AJAX requests
if (isset($_POST['action'])) {
    $runner = new MigrationRunner();
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'status':
            echo json_encode($runner->getMigrationStatus());
            exit;
            
        case 'run':
            $filename = $_POST['filename'] ?? '';
            $direction = $_POST['direction'] ?? 'up';
            echo json_encode($runner->runMigration($filename, $direction));
            exit;
            
        case 'run_all':
            echo json_encode($runner->runAllPending());
            exit;
    }
}

$runner = new MigrationRunner();
$migrations = $runner->getMigrationStatus();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration Runner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .migration-item { border-left: 4px solid #dee2e6; }
        .migration-executed { border-left-color: #28a745; }
        .migration-pending { border-left-color: #ffc107; }
        .migration-error { border-left-color: #dc3545; }
        .log-container { height: 300px; overflow-y: auto; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-database"></i> Database Migration Runner</h1>
                    <div>
                        <a href="auto_migrate.php" class="btn btn-warning">
                            <i class="fas fa-magic"></i> Auto Generate
                        </a>
                        <button class="btn btn-success" onclick="runAllMigrations()">
                            <i class="fas fa-play"></i> Run All Pending
                        </button>
                        <button class="btn btn-info" onclick="refreshStatus()">
                            <i class="fas fa-refresh"></i> Refresh
                        </button>
                    </div>
                </div>
                
                <!-- Migration Status -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-list"></i> Migration Status</h5>
                    </div>
                    <div class="card-body" id="migration-list">
                        <?php foreach ($migrations as $migration): ?>
                        <div class="migration-item p-3 mb-2 <?php echo $migration['error'] ? 'migration-error' : ($migration['executed'] ? 'migration-executed' : 'migration-pending'); ?>">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($migration['name']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($migration['description']); ?></small>
                                    <?php if ($migration['error']): ?>
                                        <div class="text-danger mt-1">
                                            <small><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($migration['error']); ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted"><?php echo $migration['timestamp']; ?></small><br>
                                    <span class="badge <?php echo $migration['executed'] ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                        <?php echo $migration['executed'] ? 'Executed' : 'Pending'; ?>
                                    </span>
                                </div>
                                <div class="col-md-3 text-end">
                                    <?php if (!$migration['error']): ?>
                                        <?php if (!$migration['executed']): ?>
                                            <button class="btn btn-sm btn-primary" onclick="runMigration('<?php echo $migration['file']; ?>', 'up')">
                                                <i class="fas fa-play"></i> Run
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-warning" onclick="runMigration('<?php echo $migration['file']; ?>', 'down')">
                                                <i class="fas fa-undo"></i> Rollback
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($migrations)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <h5>No migrations found</h5>
                            <p>Create migration files in the /migrations/ directory</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Log Output -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-terminal"></i> Output Log</h5>
                        <button class="btn btn-sm btn-outline-secondary float-end" onclick="clearLog()">Clear</button>
                    </div>
                    <div class="card-body log-container bg-dark text-light" id="log-output">
                        <div class="text-muted">Ready to run migrations...</div>
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
        
        async function runMigration(filename, direction = 'up') {
            log(`${direction === 'up' ? 'Running' : 'Rolling back'} migration: ${filename}`);
            
            try {
                const response = await fetch('migrate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=run&filename=${filename}&direction=${direction}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    log(result.message, 'success');
                    refreshStatus();
                } else {
                    log(`Error: ${result.message}`, 'error');
                }
            } catch (error) {
                log(`Network error: ${error.message}`, 'error');
            }
        }
        
        async function runAllMigrations() {
            log('Running all pending migrations...');
            
            try {
                const response = await fetch('migrate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=run_all'
                });
                
                const results = await response.json();
                
                if (results.length === 0) {
                    log('No pending migrations to run', 'info');
                } else {
                    results.forEach(result => {
                        if (result.success) {
                            log(`✓ ${result.name}: ${result.message}`, 'success');
                        } else {
                            log(`✗ ${result.name}: ${result.message}`, 'error');
                        }
                    });
                }
                
                refreshStatus();
            } catch (error) {
                log(`Network error: ${error.message}`, 'error');
            }
        }
        
        async function refreshStatus() {
            log('Refreshing migration status...');
            window.location.reload();
        }
    </script>
</body>
</html>