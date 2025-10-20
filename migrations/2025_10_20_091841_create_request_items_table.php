<?php
/**
 * Migration: Create request_items table
 * Auto-generated on: 2025-10-20 09:18:41
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_request_items_table extends Migration {
    
    public function up() {
        // Create request_items table
        $columns = [
            'id int(11) UNSIGNED NOT NULL',
            'req_id int(11) UNSIGNED NOT NULL',
            'item_id int(11) UNSIGNED NOT NULL',
            'qty int(11) NOT NULL',
            'unit varchar(50) DEFAULT NULL',
            'price decimal(10,2) DEFAULT NULL',
            'remarks varchar(255) NOT NULL'
        ];
        
        $this->createTable('request_items', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {

        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('request_items');
    }
    
    public function getDescription() {
        return 'Create request_items table with sample data';
    }
}