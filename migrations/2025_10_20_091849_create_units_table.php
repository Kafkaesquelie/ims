<?php
/**
 * Migration: Create units table
 * Auto-generated on: 2025-10-20 09:18:49
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_units_table extends Migration {
    
    public function up() {
        // Create units table
        $columns = [
            'id int(11) NOT NULL',
            'name varchar(50) NOT NULL',
            'symbol varchar(10) DEFAULT NULL'
        ];
        
        $this->createTable('units', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {
        $sampleData = [
            ['id' => '1', 'name' => 'Piece', 'symbol' => 'pc'],
            ['id' => '2', 'name' => 'Dozen', 'symbol' => 'dz'],
            ['id' => '3', 'name' => 'Box', 'symbol' => 'box'],
            ['id' => '4', 'name' => 'Pack', 'symbol' => 'pk'],
            ['id' => '5', 'name' => 'Gram', 'symbol' => 'g']
        ];
        $this->insertData('units', $sampleData);
        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('units');
    }
    
    public function getDescription() {
        return 'Create units table with sample data';
    }
}