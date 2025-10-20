<?php
/**
 * Migration: Create signatories table
 * Auto-generated on: 2025-10-20 09:18:45
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_signatories_table extends Migration {
    
    public function up() {
        // Create signatories table
        $columns = [
            'id int(11) NOT NULL',
            'name varchar(150) NOT NULL',
            'position varchar(150) NOT NULL',
            'agency varchar(150) NOT NULL',
            'created_at timestamp NOT NULL DEFAULT current_timestamp()',
            'updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()'
        ];
        
        $this->createTable('signatories', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {
        $sampleData = [
            ['id' => '1', 'name' => 'FREDALYN JOY V. FINMARA', 'position' => 'Accounting Staff', 'agency' => 'ACCOUNTING', 'created_at' => '2025-10-01 06:51:08', 'updated_at' => '2025-10-01 06:51:08'],
            ['id' => '2', 'name' => 'BRIGIDA A. BENSOSAN', 'position' => 'AO I / Supply Officer', 'agency' => 'SPMO', 'created_at' => '2025-10-01 06:53:12', 'updated_at' => '2025-10-01 06:53:12'],
            ['id' => '3', 'name' => 'JHOY L. BANGLEG', 'position' => 'COA REPRESENTATIVE', 'agency' => 'COA', 'created_at' => '2025-10-01 06:54:58', 'updated_at' => '2025-10-01 06:54:58'],
            ['id' => '4', 'name' => 'DAVE A. NABAYSAN', 'position' => 'AUDIT TEAM LEADER', 'agency' => 'COA', 'created_at' => '2025-10-01 06:55:46', 'updated_at' => '2025-10-01 06:55:46'],
            ['id' => '5', 'name' => 'KENNETH A. LARUAN', 'position' => 'UNIVERSITY PRESIDENT', 'agency' => 'ADMIN', 'created_at' => '2025-10-01 06:56:18', 'updated_at' => '2025-10-01 06:56:18']
        ];
        $this->insertData('signatories', $sampleData);
        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('signatories');
    }
    
    public function getDescription() {
        return 'Create signatories table with sample data';
    }
}