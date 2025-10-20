<?php
/**
 * Migration: Create semicategories table
 * Auto-generated on: 2025-10-20 09:18:43
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_semicategories_table extends Migration {
    
    public function up() {
        // Create semicategories table
        $columns = [
            'id int(11) NOT NULL',
            'semicategory_name varchar(255) NOT NULL',
            'uacs int(25) NOT NULL',
            'date_added datetime DEFAULT current_timestamp()',
            'date_updated datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()'
        ];
        
        $this->createTable('semicategories', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {
        $sampleData = [
            ['id' => '1', 'semicategory_name' => 'Machinery', 'uacs' => '1040501000', 'date_added' => '2025-10-02 23:53:59', 'date_updated' => '2025-10-05 10:21:03'],
            ['id' => '2', 'semicategory_name' => 'Office Equipment', 'uacs' => '1040502000', 'date_added' => '2025-10-02 23:54:34', 'date_updated' => '2025-10-05 10:21:17'],
            ['id' => '3', 'semicategory_name' => 'Medical Equipment', 'uacs' => '1040510000', 'date_added' => '2025-10-02 23:55:04', 'date_updated' => '2025-10-05 10:21:29'],
            ['id' => '4', 'semicategory_name' => 'Sports Equipment', 'uacs' => '1040512000', 'date_added' => '2025-10-02 23:55:35', 'date_updated' => '2025-10-05 10:21:47'],
            ['id' => '5', 'semicategory_name' => 'Printing Equipment', 'uacs' => '1040511000', 'date_added' => '2025-10-02 23:56:01', 'date_updated' => '2025-10-05 10:59:16']
        ];
        $this->insertData('semicategories', $sampleData);
        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('semicategories');
    }
    
    public function getDescription() {
        return 'Create semicategories table with sample data';
    }
}