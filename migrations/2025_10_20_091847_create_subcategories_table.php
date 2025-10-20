<?php
/**
 * Migration: Create subcategories table
 * Auto-generated on: 2025-10-20 09:18:47
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_subcategories_table extends Migration {
    
    public function up() {
        // Create subcategories table
        $columns = [
            'id int(11) NOT NULL',
            'account_title_id int(11) NOT NULL',
            'subcategory_name varchar(255) NOT NULL',
            'uacs_code varchar(50) NOT NULL'
        ];
        
        $this->createTable('subcategories', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {
        $sampleData = [
            ['id' => '5', 'account_title_id' => '4', 'subcategory_name' => 'Medical Equipment', 'uacs_code' => '10605111000'],
            ['id' => '6', 'account_title_id' => '5', 'subcategory_name' => 'Motor Vehicles', 'uacs_code' => '1060601000'],
            ['id' => '7', 'account_title_id' => '5', 'subcategory_name' => 'Trains', 'uacs_code' => '1060602000'],
            ['id' => '8', 'account_title_id' => '5', 'subcategory_name' => 'Watercrafts', 'uacs_code' => '1060604000'],
            ['id' => '9', 'account_title_id' => '5', 'subcategory_name' => 'Aircrafts and Aircrafts Ground Equipment', 'uacs_code' => '1060603000']
        ];
        $this->insertData('subcategories', $sampleData);
        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('subcategories');
    }
    
    public function getDescription() {
        return 'Create subcategories table with sample data';
    }
}