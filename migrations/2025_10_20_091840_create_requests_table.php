<?php
/**
 * Migration: Create requests table
 * Auto-generated on: 2025-10-20 09:18:40
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_requests_table extends Migration {
    
    public function up() {
        // Create requests table
        $columns = [
            'id int(11) UNSIGNED NOT NULL',
            'ris_no varchar(25) NOT NULL',
            'requested_by int(11) NOT NULL',
            'date timestamp NOT NULL DEFAULT current_timestamp()',
            'status enum(\'Pending\',\'Approved\',\'Completed\',\'Archived\',\'Issued\') DEFAULT \'Pending\'',
            'date_issued datetime DEFAULT NULL',
            'date_completed datetime DEFAULT NULL',
            'remarks varchar(255) NOT NULL',
            'created_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()'
        ];
        
        $this->createTable('requests', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {
        $sampleData = [
            ['id' => '97', 'ris_no' => '2025-10-0004', 'requested_by' => '10', 'date' => '2025-10-18 01:28:03', 'status' => 'Completed', 'date_issued' => null, 'date_completed' => '2025-10-20 02:40:39', 'remarks' => '', 'created_at' => '2025-10-19 18:40:39'],
            ['id' => '99', 'ris_no' => '2025-10-0005', 'requested_by' => '5', 'date' => '2025-10-19 17:24:33', 'status' => 'Issued', 'date_issued' => '2025-10-20 02:40:48', 'date_completed' => '2025-10-20 01:24:51', 'remarks' => '', 'created_at' => '2025-10-19 18:40:48'],
            ['id' => '102', 'ris_no' => '2025-10-0074', 'requested_by' => '10', 'date' => '2025-10-19 18:16:50', 'status' => 'Completed', 'date_issued' => null, 'date_completed' => '2025-10-20 02:52:53', 'remarks' => '', 'created_at' => '2025-10-19 18:52:53'],
            ['id' => '103', 'ris_no' => '2025-10-0235', 'requested_by' => '2', 'date' => '2025-10-19 18:50:10', 'status' => 'Issued', 'date_issued' => '2025-10-20 02:52:19', 'date_completed' => '2025-10-20 02:52:13', 'remarks' => '', 'created_at' => '2025-10-19 18:52:19'],
            ['id' => '104', 'ris_no' => '2025-10-0009', 'requested_by' => '11', 'date' => '2025-10-19 19:07:31', 'status' => 'Completed', 'date_issued' => '2025-10-20 03:09:01', 'date_completed' => '2025-10-20 03:47:07', 'remarks' => '', 'created_at' => '2025-10-19 19:47:07']
        ];
        $this->insertData('requests', $sampleData);
        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('requests');
    }
    
    public function getDescription() {
        return 'Create requests table with sample data';
    }
}