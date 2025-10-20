<?php
/**
 * Migration: Create stock_history table
 * Auto-generated on: 2025-10-20 09:18:46
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_stock_history_table extends Migration {
    
    public function up() {
        // Create stock_history table
        $columns = [
            'id int(11) NOT NULL',
            'item_id int(10) UNSIGNED NOT NULL',
            'previous_qty int(11) NOT NULL',
            'new_qty int(11) NOT NULL',
            'change_type enum(\'stock_in\',\'adjustment\',\'correction\',\'initial\') DEFAULT \'adjustment\'',
            'changed_by varchar(100) DEFAULT NULL',
            'remarks text DEFAULT NULL',
            'date_changed datetime DEFAULT current_timestamp()'
        ];
        
        $this->createTable('stock_history', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {
        $sampleData = [
            ['id' => '2', 'item_id' => '64', 'previous_qty' => '23', 'new_qty' => '22', 'change_type' => 'adjustment', 'changed_by' => 'Administrator', 'remarks' => 'Quantity changed from 23 to 22.', 'date_changed' => '2025-10-14 21:49:19'],
            ['id' => '3', 'item_id' => '66', 'previous_qty' => '5', 'new_qty' => '11', 'change_type' => 'stock_in', 'changed_by' => 'Administrator', 'remarks' => 'Quantity changed from 5 to 11.', 'date_changed' => '2025-10-14 21:49:39'],
            ['id' => '4', 'item_id' => '66', 'previous_qty' => '6', 'new_qty' => '6', 'change_type' => '', 'changed_by' => 'System', 'remarks' => 'Request #86 issued', 'date_changed' => '2025-10-15 00:44:35'],
            ['id' => '5', 'item_id' => '66', 'previous_qty' => '6', 'new_qty' => '6', 'change_type' => '', 'changed_by' => 'System', 'remarks' => 'Request #86 issued', 'date_changed' => '2025-10-15 00:45:14'],
            ['id' => '6', 'item_id' => '66', 'previous_qty' => '6', 'new_qty' => '6', 'change_type' => '', 'changed_by' => 'System', 'remarks' => 'Request #86 issued', 'date_changed' => '2025-10-15 00:45:28']
        ];
        $this->insertData('stock_history', $sampleData);
        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('stock_history');
    }
    
    public function getDescription() {
        return 'Create stock_history table with sample data';
    }
}