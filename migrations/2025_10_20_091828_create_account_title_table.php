<?php
/**
 * Migration: Create account_title table
 * Auto-generated on: 2025-10-20 09:18:28
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_account_title_table extends Migration {
    
    public function up() {
        // Create account_title table
        $columns = [
            'id int(11) NOT NULL',
            'category_name varchar(255) NOT NULL'
        ];
        
        $this->createTable('account_title', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {
        $sampleData = [
            ['id' => '4', 'category_name' => 'Machinery and Equipment'],
            ['id' => '5', 'category_name' => 'Transportation Equipment'],
            ['id' => '6', 'category_name' => 'Furniture, Fixtures and Books'],
            ['id' => '7', 'category_name' => 'Buildings and Other Structures'],
            ['id' => '8', 'category_name' => 'Infrastructure Assets']
        ];
        $this->insertData('account_title', $sampleData);
        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('account_title');
    }
    
    public function getDescription() {
        return 'Create account_title table with sample data';
    }
}