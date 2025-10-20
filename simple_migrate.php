<?php
require_once 'includes/load.php';
require_once 'includes/Migration.php';

// Simple migration runner that works
$migrationsPath = __DIR__ . '/migrations/';
$migrations = [];

// Get all migration files
if (is_dir($migrationsPath)) {
    $files = scandir($migrationsPath);
    foreach ($files as $file) {
        if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})_(.+)\.php$/', $file)) {
            $migrations[] = $file;
        }
    }
    
    // Sort by filename (timestamp)
    sort($migrations);
}

// Handle running migrations
if (isset($_POST['run_migration'])) {
    $filename = $_POST['filename'];
    
    try {
        require_once $migrationsPath . $filename;
        
        // Get class name
        $name = str_replace('.php', '', $filename);
        $parts = explode('_', $name);
        array_shift($parts); // Remove timestamp
        $className = 'Migration_' . implode('_', $parts);
        
        if (class_exists($className)) {
            $migration = new $className();
            
            if (!$migration->hasRun($filename)) {
                $migration->up();
                $migration->markAsRun($filename);
                $success = "Migration {$filename} executed successfully!";
            } else {
                $warning = "Migration {$filename} has already been executed.";
            }
        }
    } catch (Exception $e) {
        $error = "Error running migration: " . $e->getMessage();
    }
}

// Handle running all migrations
if (isset($_POST['run_all'])) {
    $results = [];
    
    foreach ($migrations as $filename) {
        try {
            require_once $migrationsPath . $filename;
            
            // Get class name
            $name = str_replace('.php', '', $filename);
            $parts = explode('_', $name);
            array_shift($parts); // Remove timestamp
            $className = 'Migration_' . implode('_', $parts);
            
            if (class_exists($className)) {
                $migration = new $className();
                
                if (!$migration->hasRun($filename)) {
                    $migration->up();
                    $migration->markAsRun($filename);
                    $results[] = "✓ {$filename}";
                } else {
                    $results[] = "- {$filename} (already executed)";
                }
            }
        } catch (Exception $e) {
            $results[] = "✗ {$filename}: " . $e->getMessage();
        }
    }
}

// Check migration status
$migrationStatus = [];
foreach ($migrations as $filename) {
    $migrationStatus[$filename] = [
        'executed' => false,
        'description' => 'Pending migration'
    ];
    
    try {
        // Include the file but suppress errors
        @include_once $migrationsPath . $filename;
        
        // Get class name
        $name = str_replace('.php', '', $filename);
        $parts = explode('_', $name);
        array_shift($parts); // Remove timestamp
        $className = 'Migration_' . implode('_', $parts);
        
        if (class_exists($className)) {
            try {
                $migration = new $className();
                $migrationStatus[$filename] = [
                    'executed' => $migration->hasRun($filename),
                    'description' => $migration->getDescription()
                ];
            } catch (Exception $e) {
                $migrationStatus[$filename] = [
                    'executed' => false,
                    'description' => 'Error loading: ' . $e->getMessage()
                ];
            }
        } else {
            $migrationStatus[$filename] = [
                'executed' => false,
                'description' => "Class {$className} not found"
            ];
        }
    } catch (Exception $e) {
        $migrationStatus[$filename] = [
            'executed' => false,
            'description' => 'File error: ' . $e->getMessage()
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Migration Runner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-database"></i> Simple Migration Runner</h1>
                    <div>
                        <a href="init_db.php" class="btn btn-warning">
                            <i class="fas fa-rocket"></i> Auto Initialize
                        </a>
                        <a href="auto_migrate.php" class="btn btn-info">
                            <i class="fas fa-magic"></i> Auto Generate
                        </a>
                    </div>
                </div>
                
                <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($warning)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($warning); ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($results)): ?>
                <div class="alert alert-info">
                    <h6><i class="fas fa-list"></i> Batch Migration Results:</h6>
                    <?php foreach ($results as $result): ?>
                        <div><?php echo htmlspecialchars($result); ?></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Migration Status -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-list"></i> Migrations (<?php echo count($migrations); ?> found)</h5>
                        <?php if (!empty($migrations)): ?>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="run_all" class="btn btn-success btn-sm">
                                <i class="fas fa-play"></i> Run All Pending
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($migrations)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <h5>No migrations found</h5>
                            <p>Use the Auto Generate button to create migrations</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Migration File</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($migrations as $filename): ?>
                                    <tr>
                                        <td>
                                            <small class="font-monospace"><?php echo htmlspecialchars($filename); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($migrationStatus[$filename]['description'] ?? 'Unknown'); ?></td>
                                        <td>
                                            <?php if (isset($migrationStatus[$filename]) && $migrationStatus[$filename]['executed']): ?>
                                                <span class="badge bg-success">Executed</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!isset($migrationStatus[$filename]) || !$migrationStatus[$filename]['executed']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="filename" value="<?php echo htmlspecialchars($filename); ?>">
                                                <button type="submit" name="run_migration" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-play"></i> Run
                                                </button>
                                            </form>
                                            <?php else: ?>
                                            <span class="text-muted">Already executed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Summary -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-primary"><?php echo count($migrations); ?></h3>
                                <p class="text-muted">Total Migrations</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-success">
                                    <?php echo count(array_filter($migrationStatus, function($s) { return $s['executed']; })); ?>
                                </h3>
                                <p class="text-muted">Executed</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-warning">
                                    <?php echo count(array_filter($migrationStatus, function($s) { return !$s['executed']; })); ?>
                                </h3>
                                <p class="text-muted">Pending</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>