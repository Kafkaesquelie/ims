<?php
/**
 * Migration: Create offices table
 * Auto-generated on: 2025-10-20 09:18:38
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_offices_table extends Migration {
    
    public function up() {
        // Create offices table
        $columns = [
            'id int(11) NOT NULL',
            'division_id int(11) NOT NULL',
            'office_name varchar(150) NOT NULL',
            'created_at timestamp NOT NULL DEFAULT current_timestamp()',
            'updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()'
        ];
        
        $this->createTable('offices', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {
        $sampleData = [
            ['id' => '1', 'division_id' => '1', 'office_name' => 'GSO', 'created_at' => '2025-10-01 14:59:27', 'updated_at' => '2025-10-01 15:00:01'],
            ['id' => '3', 'division_id' => '1', 'office_name' => 'BUDGET', 'created_at' => '2025-10-01 14:59:27', 'updated_at' => '2025-10-01 14:59:51'],
            ['id' => '4', 'division_id' => '1', 'office_name' => 'MOTORPOOL', 'created_at' => '2025-10-01 15:00:16', 'updated_at' => '2025-10-01 15:00:16'],
            ['id' => '5', 'division_id' => '3', 'office_name' => 'CED', 'created_at' => '2025-10-01 15:00:37', 'updated_at' => '2025-10-01 15:00:37'],
            ['id' => '7', 'division_id' => '4', 'office_name' => 'LIBRARY', 'created_at' => '2025-10-01 15:19:52', 'updated_at' => '2025-10-01 15:19:52']
        ];
        $this->insertData('offices', $sampleData);
        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('offices');
    }
    
    public function getDescription() {
        return 'Create offices table with sample data';
    }
}