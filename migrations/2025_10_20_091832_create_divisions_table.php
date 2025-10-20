<?php
/**
 * Migration: Create divisions table
 * Auto-generated on: 2025-10-20 09:18:32
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_divisions_table extends Migration {
    
    public function up() {
        // Create divisions table
        $columns = [
            'id int(11) NOT NULL',
            'division_name varchar(150) NOT NULL',
            'created_at timestamp NOT NULL DEFAULT current_timestamp()',
            'updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()'
        ];
        
        $this->createTable('divisions', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {
        $sampleData = [
            ['id' => '1', 'division_name' => 'ADMINISTRATIVE', 'created_at' => '2025-10-01 14:53:33', 'updated_at' => '2025-10-01 14:53:33'],
            ['id' => '3', 'division_name' => 'CTE', 'created_at' => '2025-10-01 14:59:13', 'updated_at' => '2025-10-01 14:59:13'],
            ['id' => '4', 'division_name' => 'OSS', 'created_at' => '2025-10-01 15:03:55', 'updated_at' => '2025-10-01 15:03:55']
        ];
        $this->insertData('divisions', $sampleData);
        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('divisions');
    }
    
    public function getDescription() {
        return 'Create divisions table with sample data';
    }
}