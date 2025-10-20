<?php
/**
 * Migration: Create categories table
 * Auto-generated on: 2025-10-20 09:18:31
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_categories_table extends Migration {
    
    public function up() {
        // Create categories table
        $columns = [
            'id int(11) UNSIGNED NOT NULL',
            'name varchar(60) NOT NULL'
        ];
        
        $this->createTable('categories', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {
        $sampleData = [
            ['id' => '1', 'name' => 'Common Supplies'],
            ['id' => '2', 'name' => 'Electrical Supplies'],
            ['id' => '3', 'name' => 'GSO Supplies'],
            ['id' => '4', 'name' => 'Janitorial Supplies'],
            ['id' => '5', 'name' => 'Motorpool Supplies']
        ];
        $this->insertData('categories', $sampleData);
        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('categories');
    }
    
    public function getDescription() {
        return 'Create categories table with sample data';
    }
}