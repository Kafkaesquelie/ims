<?php
/**
 * Migration: Create fund_clusters table
 * Auto-generated on: 2025-10-20 09:18:34
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_fund_clusters_table extends Migration {
    
    public function up() {
        // Create fund_clusters table
        $columns = [
            'id int(11) NOT NULL',
            'name varchar(100) NOT NULL',
            'description text DEFAULT NULL',
            'updated_at date NOT NULL DEFAULT current_timestamp()'
        ];
        
        $this->createTable('fund_clusters', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {
        $sampleData = [
            ['id' => '1', 'name' => 'GAA', 'description' => 'General', 'updated_at' => '2025-10-02'],
            ['id' => '2', 'name' => 'IGI', 'description' => 'Internal', 'updated_at' => '2025-10-02']
        ];
        $this->insertData('fund_clusters', $sampleData);
        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('fund_clusters');
    }
    
    public function getDescription() {
        return 'Create fund_clusters table with sample data';
    }
}