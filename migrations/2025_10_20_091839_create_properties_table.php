<?php
/**
 * Migration: Create properties table
 * Auto-generated on: 2025-10-20 09:18:39
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_properties_table extends Migration {
    
    public function up() {
        // Create properties table
        $columns = [
            'id int(11) NOT NULL',
            'fund_cluster varchar(50) NOT NULL',
            'property_no varchar(100) NOT NULL',
            'subcategory_id int(11) NOT NULL',
            'article varchar(150) NOT NULL',
            'description text NOT NULL',
            'unit_cost decimal(12,2) NOT NULL DEFAULT 0.00',
            'unit varchar(50) NOT NULL',
            'qty int(11) NOT NULL DEFAULT 0',
            'qty_left int(11) NOT NULL',
            'date_acquired date NOT NULL',
            'remarks text DEFAULT NULL',
            'date_added timestamp NOT NULL DEFAULT current_timestamp()',
            'date_updated timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()',
            '`condition` enum(\'damaged\',\'functional\') NOT NULL'
        ];
        
        $this->createTable('properties', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {
        $sampleData = [
            ['id' => '1', 'fund_cluster' => 'IGI', 'property_no' => 'HY-465451', 'subcategory_id' => '9', 'article' => 'Helicopter', 'description' => 'wiiiiiiiiiiiiiiiiiiiiwwwwwwwwwwwwwwweeeeeeeeeeeeeee', 'unit_cost' => '500000.00', 'unit' => 'Unit', 'qty' => '11', 'qty_left' => '9', 'date_acquired' => '2025-10-16', 'remarks' => 'WITWEW', 'date_added' => '2025-10-04 06:59:07', 'date_updated' => '2025-10-19 13:34:05', 'condition' => 'damaged'],
            ['id' => '2', 'fund_cluster' => 'GAA', 'property_no' => 'PR-uyr123', 'subcategory_id' => '5', 'article' => 'Wheel Chair', 'description' => 'For mentals', 'unit_cost' => '89000.00', 'unit' => 'Unit', 'qty' => '22', 'qty_left' => '22', 'date_acquired' => '2025-10-01', 'remarks' => '', 'date_added' => '2025-10-05 02:07:43', 'date_updated' => '2025-10-11 05:55:33', 'condition' => 'damaged'],
            ['id' => '3', 'fund_cluster' => 'GAA', 'property_no' => 'PR-7865345', 'subcategory_id' => '12', 'article' => 'RBC', 'description' => 'Research Building', 'unit_cost' => '2000000.00', 'unit' => 'Lot', 'qty' => '1', 'qty_left' => '1', 'date_acquired' => '2025-10-16', 'remarks' => 'oooooff', 'date_added' => '2025-10-07 08:59:05', 'date_updated' => '2025-10-11 05:55:33', 'condition' => 'damaged']
        ];
        $this->insertData('properties', $sampleData);
        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('properties');
    }
    
    public function getDescription() {
        return 'Create properties table with sample data';
    }
}