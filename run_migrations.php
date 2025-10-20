<?php
require_once 'includes/load.php';
require_once 'includes/Migration.php';

// Ultra-simple migration runner
echo "<!DOCTYPE html>
<html>
<head>
    <title>Run Migrations</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container py-5'>
    <h1><i class='fas fa-database'></i> Run Migrations</h1>";

$migrationsPath = __DIR__ . '/migrations/';
$migrations = glob($migrationsPath . '*.php');

if (empty($migrations)) {
    echo "<div class='alert alert-warning'>No migration files found in migrations/ directory.</div>";
} else {
    echo "<div class='alert alert-info'>Found " . count($migrations) . " migration files</div>";
    
    if (isset($_POST['run_all'])) {
        echo "<div class='card'><div class='card-body'>";
        echo "<h5>Running migrations...</h5>";
        
        foreach ($migrations as $migrationFile) {
            $filename = basename($migrationFile);
            echo "<div>Processing: $filename ... ";
            
            try {
                require_once $migrationFile;
                
                // Get class name from filename pattern: YYYY_MM_DD_HHMMSS_description.php
                $name = str_replace('.php', '', $filename);
                $parts = explode('_', $name);
                // Remove first 4 parts (YYYY, MM, DD, HHMMSS) to get description
                $description = array_slice($parts, 4);
                $className = 'Migration_' . implode('_', $description);
                
                if (class_exists($className)) {
                    $migration = new $className();
                    
                    if (!$migration->hasRun($filename)) {
                        $migration->up();
                        $migration->markAsRun($filename);
                        echo "<span class='text-success'>✓ SUCCESS</span>";
                    } else {
                        echo "<span class='text-warning'>- Already executed</span>";
                    }
                } else {
                    echo "<span class='text-danger'>✗ Class $className not found</span>";
                }
            } catch (Exception $e) {
                echo "<span class='text-danger'>✗ ERROR: " . htmlspecialchars($e->getMessage()) . "</span>";
            }
            
            echo "</div>";
        }
        
        echo "</div></div>";
        echo "<div class='alert alert-success mt-3'>Migration process completed!</div>";
        echo "<a href='index.php' class='btn btn-primary'>Go to Application</a>";
    } else {
        // Show migration list
        echo "<div class='card'>
            <div class='card-header'>
                <h5>Available Migrations</h5>
            </div>
            <div class='card-body'>
                <ul class='list-group mb-3'>";
        
        foreach ($migrations as $migrationFile) {
            $filename = basename($migrationFile);
            echo "<li class='list-group-item'>$filename</li>";
        }
        
        echo "</ul>
                <form method='POST'>
                    <button type='submit' name='run_all' class='btn btn-success btn-lg'>
                        <i class='fas fa-play'></i> Run All Migrations
                    </button>
                </form>
            </div>
        </div>";
    }
}

echo "</div></body></html>";
?>