<?php
/**
 * Migration Generator Script
 * Usage: php create_migration.php "migration_name" 
 * Or visit: yoursite.com/create_migration.php?name=migration_name
 */

function createMigration($migrationName) {
    // Clean the migration name
    $cleanName = preg_replace('/[^a-zA-Z0-9_]/', '_', $migrationName);
    $cleanName = strtolower($cleanName);
    $cleanName = trim($cleanName, '_');
    
    // Create timestamp
    $timestamp = date('Y_m_d_His');
    
    // Create filename
    $filename = "{$timestamp}_{$cleanName}.php";
    $className = "Migration_" . $cleanName;
    
    // Create migration template
    $template = "<?php
/**
 * Migration: {$migrationName}
 * Generated on: " . date('Y-m-d H:i:s') . "
 */

require_once __DIR__ . '/../includes/Migration.php';

class {$className} extends Migration {
    
    public function up() {
        // Add your migration logic here
        // Examples:
        
        // Create a table:
        // \$columns = [
        //     'id INT AUTO_INCREMENT PRIMARY KEY',
        //     'name VARCHAR(255) NOT NULL',
        //     'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
        // ];
        // \$this->createTable('table_name', \$columns);
        
        // Add a column:
        // \$this->addColumn('table_name', 'new_column', 'VARCHAR(100) DEFAULT NULL');
        
        // Add an index:
        // \$this->addIndex('table_name', 'idx_name', 'column_name');
        
        // Insert data:
        // \$data = [
        //     ['column1' => 'value1', 'column2' => 'value2'],
        //     ['column1' => 'value3', 'column2' => 'value4']
        // ];
        // \$this->insertData('table_name', \$data);
    }
    
    public function down() {
        // Add rollback logic here
        // This should undo everything done in the up() method
        
        // Examples:
        // \$this->dropTable('table_name');
        // \$this->dropColumn('table_name', 'column_name');
        // \$this->dropIndex('index_name', 'table_name');
    }
    
    public function getDescription() {
        return '{$migrationName}';
    }
}";

    // Create migrations directory if it doesn't exist
    $migrationsDir = __DIR__ . '/migrations';
    if (!is_dir($migrationsDir)) {
        mkdir($migrationsDir, 0755, true);
    }
    
    // Write the file
    $filepath = $migrationsDir . '/' . $filename;
    file_put_contents($filepath, $template);
    
    return [
        'success' => true,
        'filename' => $filename,
        'filepath' => $filepath,
        'className' => $className
    ];
}

// Handle web request
if (isset($_GET['name']) || isset($_POST['name'])) {
    $migrationName = $_GET['name'] ?? $_POST['name'];
    $result = createMigration($migrationName);
    
    if (isset($_POST['name'])) {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
}

// Handle CLI request
if (php_sapi_name() === 'cli') {
    if ($argc < 2) {
        echo "Usage: php create_migration.php \"migration_name\"\n";
        exit(1);
    }
    
    $migrationName = $argv[1];
    $result = createMigration($migrationName);
    
    if ($result['success']) {
        echo "Migration created successfully!\n";
        echo "File: {$result['filename']}\n";
        echo "Class: {$result['className']}\n";
        echo "Path: {$result['filepath']}\n";
    }
    exit;
}

// Web interface
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Migration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-plus-circle"></i> Create New Migration</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($result)): ?>
                            <div class="alert alert-success">
                                <h6><i class="fas fa-check-circle"></i> Migration Created Successfully!</h6>
                                <p><strong>Filename:</strong> <?php echo htmlspecialchars($result['filename']); ?></p>
                                <p><strong>Class:</strong> <?php echo htmlspecialchars($result['className']); ?></p>
                                <p><strong>Path:</strong> <?php echo htmlspecialchars($result['filepath']); ?></p>
                                <a href="migrate.php" class="btn btn-primary">
                                    <i class="fas fa-arrow-right"></i> Go to Migration Runner
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <form method="GET">
                            <div class="mb-3">
                                <label for="name" class="form-label">Migration Name</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="name" 
                                    name="name" 
                                    placeholder="e.g., add_user_settings_table"
                                    required
                                    value="<?php echo htmlspecialchars($_GET['name'] ?? ''); ?>"
                                >
                                <div class="form-text">
                                    Use descriptive names like "add_column_to_users", "create_products_table", etc.
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-plus"></i> Create Migration
                            </button>
                            <a href="migrate.php" class="btn btn-secondary">
                                <i class="fas fa-list"></i> View Migrations
                            </a>
                        </form>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-info-circle"></i> Naming Convention</h6>
                                <p class="small text-muted">
                                    Migration files follow the pattern:<br>
                                    <code>YYYY_MM_DD_HHMMSS_description.php</code>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-lightbulb"></i> Examples</h6>
                                <ul class="small text-muted">
                                    <li>add_email_column_to_users</li>
                                    <li>create_products_table</li>
                                    <li>update_user_roles</li>
                                    <li>add_indexes_for_performance</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>