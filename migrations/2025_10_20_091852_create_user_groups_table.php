<?php
/**
 * Migration: Create user_groups table
 * Auto-generated on: 2025-10-20 09:18:52
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_user_groups_table extends Migration {
    
    public function up() {
        // Create user_groups table
        $columns = [
            'id int(11) NOT NULL',
            'group_name varchar(150) NOT NULL',
            'group_level int(11) NOT NULL',
            'group_status int(1) NOT NULL'
        ];
        
        $this->createTable('user_groups', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {
        $sampleData = [
            ['id' => '1', 'group_name' => 'Admin', 'group_level' => '1', 'group_status' => '1'],
            ['id' => '2', 'group_name' => 'IT', 'group_level' => '2', 'group_status' => '1'],
            ['id' => '3', 'group_name' => 'User', 'group_level' => '3', 'group_status' => '1']
        ];
        $this->insertData('user_groups', $sampleData);
        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('user_groups');
    }
    
    public function getDescription() {
        return 'Create user_groups table with sample data';
    }
}