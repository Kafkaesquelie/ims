<?php
/**
 * Migration: Create unit_conversions table
 * Auto-generated on: 2025-10-20 09:18:50
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_unit_conversions_table extends Migration {
    
    public function up() {
        // Create unit_conversions table
        $columns = [
            'id int(11) NOT NULL',
            'item_id int(11) DEFAULT NULL',
            'from_unit_id int(11) NOT NULL',
            'to_unit_id int(11) NOT NULL',
            'conversion_rate decimal(12,6) NOT NULL'
        ];
        
        $this->createTable('unit_conversions', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {
        $sampleData = [
            ['id' => '1', 'item_id' => '65', 'from_unit_id' => '3', 'to_unit_id' => '21', 'conversion_rate' => '12.000000'],
            ['id' => '2', 'item_id' => '64', 'from_unit_id' => '3', 'to_unit_id' => '21', 'conversion_rate' => '12.000000'],
            ['id' => '3', 'item_id' => '66', 'from_unit_id' => '3', 'to_unit_id' => '16', 'conversion_rate' => '10.000000'],
            ['id' => '4', 'item_id' => '67', 'from_unit_id' => '3', 'to_unit_id' => '14', 'conversion_rate' => '7.000000'],
            ['id' => '5', 'item_id' => '68', 'from_unit_id' => '3', 'to_unit_id' => '8', 'conversion_rate' => '15.000000']
        ];
        $this->insertData('unit_conversions', $sampleData);
        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('unit_conversions');
    }
    
    public function getDescription() {
        return 'Create unit_conversions table with sample data';
    }
}