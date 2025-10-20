<?php
/**
 * Database Initialization Class
 * This class automatically generates and runs all migrations for first-time setup
 */

require_once 'AutoMigrationGenerator.php';

class DatabaseInitializer {
    private $generator;
    
    public function __construct() {
        $this->generator = new AutoMigrationGenerator();
    }
    
    /**
     * Initialize database completely
     */
    public function initializeDatabase($force = false) {
        $results = [];
        
        try {
            // Check if migrations already exist
            $existingMigrations = $this->generator->checkExistingMigrations();
            
            if (!empty($existingMigrations) && !$force) {
                return [
                    'success' => false,
                    'message' => 'Database already initialized. Use force=true to reinitialize.',
                    'existing_migrations' => $existingMigrations
                ];
            }
            
            // Clean existing migrations if force mode
            if ($force && !empty($existingMigrations)) {
                $deleted = $this->generator->cleanupOldMigrations();
                $results['deleted_migrations'] = $deleted;
            }
            
            // Generate all migrations
            $generatedMigrations = $this->generator->generateAllMigrations();
            $results['generated_migrations'] = $generatedMigrations;
            
            // Run all migrations
            $executedMigrations = $this->runAllMigrations();
            $results['executed_migrations'] = $executedMigrations;
            
            return [
                'success' => true,
                'message' => 'Database initialized successfully!',
                'details' => $results,
                'summary' => [
                    'tables_created' => count($generatedMigrations),
                    'migrations_executed' => count($executedMigrations['successful']),
                    'failures' => count($executedMigrations['failed'])
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'details' => $results
            ];
        }
    }
    
    /**
     * Run all pending migrations
     */
    private function runAllMigrations() {
        $results = ['successful' => [], 'failed' => []];
        
        // Load all migration files
        $files = glob(__DIR__ . '/../migrations/*.php');
        
        // Sort files by timestamp (filename)
        usort($files, function($a, $b) {
            return strcmp(basename($a), basename($b));
        });
        
        foreach ($files as $file) {
            $filename = basename($file);
            
            try {
                require_once $file;
                
                // Extract class name from filename
                $name = str_replace('.php', '', $filename);
                $parts = explode('_', $name);
                array_shift($parts); // Remove timestamp
                $className = 'Migration_' . implode('_', $parts);
                
                if (class_exists($className)) {
                    $migration = new $className();
                    
                    // Check if already executed
                    if (!$migration->hasRun($filename)) {
                        $migration->up();
                        $migration->markAsRun($filename);
                        
                        $results['successful'][] = [
                            'filename' => $filename,
                            'class' => $className,
                            'description' => $migration->getDescription()
                        ];
                    }
                }
            } catch (Exception $e) {
                $results['failed'][] = [
                    'filename' => $filename,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Check database connection and requirements
     */
    public function checkRequirements() {
        $checks = [];
        
        // Check database connection
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $checks['database_connection'] = ['status' => 'ok', 'message' => 'MySQL connection successful'];
        } catch (PDOException $e) {
            try {
                $pdo = new PDO(
                    "pgsql:host=" . DB_HOST . ";port=" . (defined('DB_PORT') ? DB_PORT : 5432) . ";dbname=" . DB_NAME,
                    DB_USER,
                    DB_PASS,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                $checks['database_connection'] = ['status' => 'ok', 'message' => 'PostgreSQL connection successful'];
            } catch (PDOException $e2) {
                $checks['database_connection'] = ['status' => 'error', 'message' => $e2->getMessage()];
            }
        }
        
        // Check SQL file exists
        $sqlFile = __DIR__ . '/../inv_system (4).sql';
        if (file_exists($sqlFile)) {
            $checks['sql_file'] = ['status' => 'ok', 'message' => 'SQL schema file found'];
        } else {
            $checks['sql_file'] = ['status' => 'error', 'message' => 'SQL schema file not found'];
        }
        
        // Check migrations directory
        $migrationsDir = __DIR__ . '/../migrations/';
        if (is_dir($migrationsDir) && is_writable($migrationsDir)) {
            $checks['migrations_directory'] = ['status' => 'ok', 'message' => 'Migrations directory ready'];
        } else {
            $checks['migrations_directory'] = ['status' => 'warning', 'message' => 'Migrations directory will be created'];
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
            $checks['php_version'] = ['status' => 'ok', 'message' => 'PHP version: ' . PHP_VERSION];
        } else {
            $checks['php_version'] = ['status' => 'warning', 'message' => 'PHP version ' . PHP_VERSION . ' may have compatibility issues'];
        }
        
        return $checks;
    }
}