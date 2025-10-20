<?php
require_once 'includes/load.php';
require_once 'includes/Migration.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Run Failed Migrations</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container py-5'>
    <h1>Run Failed Migrations</h1>";

// Only run the 3 failed migrations
$failedMigrations = [
    '2025_10_20_091839_create_properties_table.php',
    '2025_10_20_091844_create_semi_exp_prop_table.php', 
    '2025_10_20_091848_create_transactions_table.php'
];

$migrationsPath = __DIR__ . '/migrations/';

if (isset($_POST['run_failed'])) {
    echo "<div class='card'><div class='card-body'>";
    echo "<h5>Running failed migrations...</h5>";
    
    foreach ($failedMigrations as $filename) {
        $migrationFile = $migrationsPath . $filename;
        echo "<div>Processing: $filename ... ";
        
        try {
            require_once $migrationFile;
            
            // Get class name from filename
            $name = str_replace('.php', '', $filename);
            $parts = explode('_', $name);
            $description = array_slice($parts, 4);
            $className = 'Migration_' . implode('_', $description);
            
            if (class_exists($className)) {
                $migration = new $className();
                
                // Force drop the table first if it exists (to handle partial creation)
                try {
                    $migration->down();
                } catch (Exception $e) {
                    // Ignore if table doesn't exist
                }
                
                // Now run the migration
                $migration->up();
                $migration->markAsRun($filename);
                echo "<span class='text-success'>✓ SUCCESS</span>";
            } else {
                echo "<span class='text-danger'>✗ Class $className not found</span>";
            }
        } catch (Exception $e) {
            echo "<span class='text-danger'>✗ ERROR: " . htmlspecialchars($e->getMessage()) . "</span>";
        }
        
        echo "</div>";
    }
    
    echo "</div></div>";
    echo "<div class='alert alert-success mt-3'>Failed migrations retry completed!</div>";
    echo "<a href='run_migrations.php' class='btn btn-primary'>Back to All Migrations</a> ";
    echo "<a href='index.php' class='btn btn-success'>Go to Application</a>";
} else {
    echo "<div class='alert alert-info'>Ready to retry the 3 failed migrations with fixes applied.</div>";
    echo "<div class='card'>
        <div class='card-body'>
            <h5>Failed Migrations to Retry:</h5>
            <ul>";
    
    foreach ($failedMigrations as $filename) {
        echo "<li>$filename</li>";
    }
    
    echo "</ul>
            <form method='POST'>
                <button type='submit' name='run_failed' class='btn btn-warning btn-lg'>
                    <i class='fas fa-redo'></i> Retry Failed Migrations
                </button>
            </form>
        </div>
    </div>";
}

echo "</div></body></html>";
?>