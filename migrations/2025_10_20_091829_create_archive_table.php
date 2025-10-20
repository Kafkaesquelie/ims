<?php
/**
 * Migration: Create archive table
 * Auto-generated on: 2025-10-20 09:18:29
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_archive_table extends Migration {
    
    public function up() {
        // Create archive table
        $columns = [
            'id int(11) NOT NULL',
            'record_id int(11) NOT NULL',
            'classification varchar(50) NOT NULL',
            'data longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`))',
            'archived_at timestamp NOT NULL DEFAULT current_timestamp()',
            'archived_by int(11) DEFAULT NULL'
        ];
        
        $this->createTable('archive', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {

        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('archive');
    }
    
    public function getDescription() {
        return 'Create archive table with sample data';
    }
}