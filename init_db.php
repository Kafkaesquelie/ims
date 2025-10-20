<?php
/**
 * Database Initialization Script
 * This script automatically generates and runs all migrations for first-time setup
 */

require_once 'includes/load.php';
require_once 'includes/Migration.php';
require_once 'includes/DatabaseInitializer.php';

// Handle web requests
if (isset($_POST['action'])) {
    $initializer = new DatabaseInitializer();
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'init':
                $force = isset($_POST['force']) && $_POST['force'] === 'true';
                $result = $initializer->initializeDatabase($force);
                echo json_encode($result);
                break;
                
            case 'check':
                $checks = $initializer->checkRequirements();
                echo json_encode(['success' => true, 'checks' => $checks]);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

$initializer = new DatabaseInitializer();
$requirements = $initializer->checkRequirements();
$allGood = true;
foreach ($requirements as $check) {
    if ($check['status'] === 'error') {
        $allGood = false;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Initializer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .log-container { height: 400px; overflow-y: auto; }
        .status-ok { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-error { color: #dc3545; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="text-center mb-4">
                    <h1><i class="fas fa-database"></i> Database Initializer</h1>
                    <p class="lead">One-click database setup for your Inventory Management System</p>
                </div>
                
                <!-- Requirements Check -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-check-circle"></i> System Requirements</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($requirements as $name => $check): ?>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-<?php echo $check['status'] === 'ok' ? 'check-circle status-ok' : ($check['status'] === 'warning' ? 'exclamation-triangle status-warning' : 'times-circle status-error'); ?> me-3"></i>
                            <div>
                                <strong><?php echo ucwords(str_replace('_', ' ', $name)); ?>:</strong>
                                <span class="status-<?php echo $check['status']; ?>"><?php echo htmlspecialchars($check['message']); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if ($allGood): ?>
                        <div class="alert alert-success mt-3">
                            <i class="fas fa-thumbs-up"></i> All requirements met! Ready to initialize database.
                        </div>
                        <?php else: ?>
                        <div class="alert alert-danger mt-3">
                            <i class="fas fa-exclamation-triangle"></i> Please resolve the errors above before proceeding.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Initialization Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-rocket"></i> Initialize Database</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>What this will do:</h6>
                                <ul class="text-muted">
                                    <li>Analyze your database schema</li>
                                    <li>Generate migration files for all tables</li>
                                    <li>Execute migrations to create database structure</li>
                                    <li>Insert sample data for testing</li>
                                    <li>Set up proper indexes and relationships</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="forceInit">
                                    <label class="form-check-label" for="forceInit">
                                        Force reinitialize
                                        <small class="text-muted d-block">Delete existing migrations and start fresh</small>
                                    </label>
                                </div>
                                
                                <div class="d-grid">
                                    <button class="btn btn-success btn-lg" onclick="initializeDatabase()" <?php echo !$allGood ? 'disabled' : ''; ?>>
                                        <i class="fas fa-rocket"></i> Initialize Database
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                After initialization, you can manage migrations at 
                                <a href="migrate.php" class="text-decoration-none">migrate.php</a>
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Progress Log -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <h5><i class="fas fa-terminal"></i> Initialization Log</h5>
                        <button class="btn btn-sm btn-outline-secondary" onclick="clearLog()">Clear</button>
                    </div>
                    <div class="card-body log-container bg-dark text-light" id="log-output">
                        <div class="text-muted">Ready to initialize database...</div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="text-center mt-4">
                    <a href="migrate.php" class="btn btn-outline-primary">
                        <i class="fas fa-list"></i> View Migrations
                    </a>
                    <a href="auto_migrate.php" class="btn btn-outline-secondary">
                        <i class="fas fa-magic"></i> Auto Generate Only
                    </a>
                    <a href="index.php" class="btn btn-outline-info">
                        <i class="fas fa-home"></i> Back to App
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function log(message, type = 'info') {
            const logOutput = document.getElementById('log-output');
            const timestamp = new Date().toLocaleTimeString();
            const colorClass = type === 'error' ? 'text-danger' : type === 'success' ? 'text-success' : type === 'warning' ? 'text-warning' : 'text-info';
            
            logOutput.innerHTML += `<div class="${colorClass}">[${timestamp}] ${message}</div>`;
            logOutput.scrollTop = logOutput.scrollHeight;
        }
        
        function clearLog() {
            document.getElementById('log-output').innerHTML = '<div class="text-muted">Log cleared...</div>';
        }
        
        async function initializeDatabase() {
            const force = document.getElementById('forceInit').checked;
            const button = event.target;
            
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Initializing...';
            
            log('Starting database initialization...', 'info');
            
            try {
                const response = await fetch('init_db.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=init&force=${force}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    log(result.message, 'success');
                    
                    if (result.details.deleted_migrations) {
                        log(`Cleaned ${result.details.deleted_migrations.length} existing migrations`, 'warning');
                    }
                    
                    if (result.details.generated_migrations) {
                        log(`Generated ${result.details.generated_migrations.length} migration files:`, 'info');
                        result.details.generated_migrations.forEach(migration => {
                            log(`  ✓ ${migration.filename} (${migration.table})`, 'success');
                        });
                    }
                    
                    if (result.details.executed_migrations) {
                        const executed = result.details.executed_migrations;
                        log(`Executed ${executed.successful.length} migrations successfully`, 'success');
                        
                        executed.successful.forEach(migration => {
                            log(`  ✓ ${migration.filename}: ${migration.description}`, 'success');
                        });
                        
                        if (executed.failed.length > 0) {
                            log(`${executed.failed.length} migrations failed:`, 'error');
                            executed.failed.forEach(migration => {
                                log(`  ✗ ${migration.filename}: ${migration.error}`, 'error');
                            });
                        }
                    }
                    
                    log('Database initialization completed!', 'success');
                    log('You can now access your application or manage migrations.', 'info');
                    
                    // Show success actions
                    setTimeout(() => {
                        if (confirm('Database initialized successfully! Would you like to view the migrations page?')) {
                            window.location.href = 'migrate.php';
                        }
                    }, 2000);
                    
                } else {
                    log(`Initialization failed: ${result.error}`, 'error');
                    if (result.message) {
                        log(result.message, 'warning');
                    }
                }
                
            } catch (error) {
                log(`Network error: ${error.message}`, 'error');
            } finally {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-rocket"></i> Initialize Database';
            }
        }
    </script>
</body>
</html>