<?php
/**
 * Migration: Create item_stocks_per_year table
 * Auto-generated on: 2025-10-20 09:18:36
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_item_stocks_per_year_table extends Migration {
    
    public function up() {
        // Create item_stocks_per_year table
        $columns = [
            'id int(10) UNSIGNED NOT NULL',
            'item_id int(10) UNSIGNED NOT NULL',
            'school_year_id int(10) UNSIGNED NOT NULL',
            'stock int(11) DEFAULT 0',
            'updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()'
        ];
        
        $this->createTable('item_stocks_per_year', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {
        $sampleData = [
            ['id' => '4', 'item_id' => '67', 'school_year_id' => '1', 'stock' => '1', 'updated_at' => '2025-10-19 19:16:02'],
            ['id' => '5', 'item_id' => '65', 'school_year_id' => '1', 'stock' => '8', 'updated_at' => '2025-10-20 03:04:49'],
            ['id' => '6', 'item_id' => '64', 'school_year_id' => '1', 'stock' => '0', 'updated_at' => '2025-10-19 20:11:05'],
            ['id' => '7', 'item_id' => '66', 'school_year_id' => '1', 'stock' => '15', 'updated_at' => '2025-10-20 05:11:59'],
            ['id' => '8', 'item_id' => '68', 'school_year_id' => '1', 'stock' => '0', 'updated_at' => '2025-10-19 18:52:19']
        ];
        $this->insertData('item_stocks_per_year', $sampleData);
        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('item_stocks_per_year');
    }
    
    public function getDescription() {
        return 'Create item_stocks_per_year table with sample data';
    }
}