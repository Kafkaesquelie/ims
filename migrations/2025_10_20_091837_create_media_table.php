<?php
/**
 * Migration: Create media table
 * Auto-generated on: 2025-10-20 09:18:37
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_media_table extends Migration {
    
    public function up() {
        // Create media table
        $columns = [
            'id int(11) UNSIGNED NOT NULL',
            'file_name varchar(255) NOT NULL',
            'file_type varchar(100) NOT NULL'
        ];
        
        $this->createTable('media', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {
        $sampleData = [
            ['id' => '1', 'file_name' => 'bsulogo.png', 'file_type' => 'image/png'],
            ['id' => '2', 'file_name' => 'muriatic.jpg', 'file_type' => 'image/jpeg'],
            ['id' => '3', 'file_name' => 'puncher.jpg', 'file_type' => 'image/jpeg'],
            ['id' => '4', 'file_name' => 'wire.jpg', 'file_type' => 'image/jpeg'],
            ['id' => '5', 'file_name' => 'teflon.jpg', 'file_type' => 'image/jpeg']
        ];
        $this->insertData('media', $sampleData);
        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('media');
    }
    
    public function getDescription() {
        return 'Create media table with sample data';
    }
}