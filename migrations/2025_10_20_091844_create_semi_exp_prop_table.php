<?php
/**
 * Migration: Create semi_exp_prop table
 * Auto-generated on: 2025-10-20 09:18:44
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_semi_exp_prop_table extends Migration {
    
    public function up() {
        // Create semi_exp_prop table
        $columns = [
            'id int(10) UNSIGNED NOT NULL',
            'fund_cluster varchar(50) NOT NULL',
            'inv_item_no varchar(255) DEFAULT NULL',
            'item varchar(255) NOT NULL',
            'item_description varchar(255) NOT NULL',
            'semicategory_id int(25) NOT NULL',
            'unit varchar(50) NOT NULL',
            'unit_cost decimal(12,2) NOT NULL DEFAULT 0.00',
            'total_qty int(25) NOT NULL',
            'qty_left int(25) NOT NULL',
            'estimated_use varchar(50) DEFAULT NULL',
            'date_added datetime NOT NULL DEFAULT current_timestamp()',
            'last_edited datetime DEFAULT NULL ON UPDATE current_timestamp()',
            'status enum(\'available\',\'issued\',\'lost\',\'returned\',\'disposed\',\'archived\') DEFAULT \'available\'',
            '`condition` enum(\'damaged\',\'functional\') NOT NULL'
        ];
        
        $this->createTable('semi_exp_prop', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {
        $sampleData = [
            ['id' => '1', 'fund_cluster' => 'GAA', 'inv_item_no' => '908453-3487', 'item' => 'ACER LAPTOP', 'item_description' => 'Laptop ACER', 'semicategory_id' => '4', 'unit' => 'Unit', 'unit_cost' => '39000.00', 'total_qty' => '1', 'qty_left' => '0', 'estimated_use' => '', 'date_added' => '2025-09-20 18:29:42', 'last_edited' => '2025-10-20 11:06:50', 'status' => 'issued', 'condition' => 'damaged'],
            ['id' => '2', 'fund_cluster' => 'IGI', 'inv_item_no' => 'JHGFUIG8764', 'item' => 'FIRE EXTINGUISHER', 'item_description' => 'Fire Extinguisher', 'semicategory_id' => '1', 'unit' => 'Bottle', 'unit_cost' => '5000.00', 'total_qty' => '1', 'qty_left' => '1', 'estimated_use' => '5 Years', 'date_added' => '2025-09-21 23:12:30', 'last_edited' => '2025-10-07 12:26:23', 'status' => 'available', 'condition' => 'damaged'],
            ['id' => '11', 'fund_cluster' => 'GAA', 'inv_item_no' => 'GAA-908453-3487', 'item' => 'CHAIR', 'item_description' => 'Chair', 'semicategory_id' => '4', 'unit' => 'Unit', 'unit_cost' => '39000.00', 'total_qty' => '17', 'qty_left' => '16', 'estimated_use' => '', 'date_added' => '2025-09-22 11:40:04', 'last_edited' => '2025-10-18 14:06:21', 'status' => 'available', 'condition' => 'damaged'],
            ['id' => '12', 'fund_cluster' => 'GAA', 'inv_item_no' => '12345-678', 'item' => 'PENCIL', 'item_description' => 'Lapis', 'semicategory_id' => '2', 'unit' => 'Box', 'unit_cost' => '17.00', 'total_qty' => '20', 'qty_left' => '19', 'estimated_use' => '', 'date_added' => '2025-09-22 12:43:15', 'last_edited' => '2025-10-18 23:27:43', 'status' => 'available', 'condition' => 'damaged'],
            ['id' => '13', 'fund_cluster' => 'GAA', 'inv_item_no' => 'INV-984', 'item' => 'TISSUE', 'item_description' => 'Tissue huhu', 'semicategory_id' => '1', 'unit' => 'roll', 'unit_cost' => '75.00', 'total_qty' => '12', 'qty_left' => '12', 'estimated_use' => '', 'date_added' => '2025-09-28 18:41:46', 'last_edited' => '2025-10-15 10:49:40', 'status' => 'available', 'condition' => 'damaged']
        ];
        $this->insertData('semi_exp_prop', $sampleData);
        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('semi_exp_prop');
    }
    
    public function getDescription() {
        return 'Create semi_exp_prop table with sample data';
    }
}