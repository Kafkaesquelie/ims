<?php
/**
 * Migration: Create base_units table
 * Auto-generated on: 2025-10-20 09:18:30
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_base_units_table extends Migration {
    
    public function up() {
        // Create base_units table
        $columns = [
            'id int(11) NOT NULL',
            'name varchar(50) NOT NULL',
            'symbol varchar(20) DEFAULT NULL'
        ];
        
        $this->createTable('base_units', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {
        $sampleData = [
            ['id' => '1', 'name' => 'Not Applicable', 'symbol' => 'N/A'],
            ['id' => '2', 'name' => 'Box', 'symbol' => 'box'],
            ['id' => '3', 'name' => 'Pack', 'symbol' => 'pk'],
            ['id' => '4', 'name' => 'Ream', 'symbol' => 'ream'],
            ['id' => '5', 'name' => 'Meter', 'symbol' => 'm']
        ];
        $this->insertData('base_units', $sampleData);
        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('base_units');
    }
    
    public function getDescription() {
        return 'Create base_units table with sample data';
    }
}