<?php
/**
 * Migration: Create items table
 * Auto-generated on: 2025-10-20 09:18:35
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_items_table extends Migration {
    
    public function up() {
        // Create items table
        $columns = [
            'id int(11) UNSIGNED NOT NULL',
            'fund_cluster varchar(255) NOT NULL',
            'stock_card varchar(11) NOT NULL',
            'name varchar(255) NOT NULL',
            'quantity varchar(50) DEFAULT NULL',
            'unit_cost decimal(25,2) DEFAULT NULL',
            'categorie_id int(11) UNSIGNED NOT NULL',
            'media_id int(11) DEFAULT 0',
            'description varchar(255) NOT NULL',
            'date_added datetime NOT NULL',
            'last_edited datetime DEFAULT NULL ON UPDATE current_timestamp()',
            'unit_id int(11) DEFAULT NULL',
            'base_unit_id int(11) DEFAULT NULL',
            'archived tinyint(1) NOT NULL DEFAULT 0'
        ];
        
        $this->createTable('items', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {

        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('items');
    }
    
    public function getDescription() {
        return 'Create items table with sample data';
    }
}