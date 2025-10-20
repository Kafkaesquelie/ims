<?php
/**
 * Migration: Create employees table
 * Auto-generated on: 2025-10-20 09:18:33
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_employees_table extends Migration {
    
    public function up() {
        // Create employees table
        $columns = [
            'id int(10) UNSIGNED NOT NULL',
            'first_name varchar(50) NOT NULL',
            'last_name varchar(50) NOT NULL',
            'middle_name varchar(50) DEFAULT NULL',
            'position varchar(100) NOT NULL',
            'image varchar(255) DEFAULT NULL',
            'division varchar(25) NOT NULL',
            'office varchar(100) DEFAULT NULL',
            'designation varchar(25) NOT NULL',
            'status enum(\'Active\',\'Inactive\',\'On Leave\') DEFAULT \'Active\'',
            'created_at timestamp NOT NULL DEFAULT current_timestamp()',
            'updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()',
            'user_id int(11) DEFAULT NULL'
        ];
        
        $this->createTable('employees', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {
        $sampleData = [
            ['id' => '2', 'first_name' => 'Jane', 'last_name' => 'Smith', 'middle_name' => 'B.', 'position' => 'HR Manager', 'image' => '1758712274_pfp1.jpg', 'division' => '3', 'office' => '5', 'designation' => '0', 'status' => 'Active', 'created_at' => '2025-09-20 11:21:57', 'updated_at' => '2025-10-14 15:10:32', 'user_id' => null],
            ['id' => '5', 'first_name' => 'Dave', 'last_name' => 'Nabaysan', 'middle_name' => 'A.', 'position' => 'Taga Kain', 'image' => '1760155369_Cats.jpg', 'division' => '4', 'office' => '8', 'designation' => '0', 'status' => 'Active', 'created_at' => '2025-10-11 04:02:38', 'updated_at' => '2025-10-14 15:11:03', 'user_id' => '12'],
            ['id' => '6', 'first_name' => 'Jhoy', 'last_name' => 'Bangleg', 'middle_name' => 'L.', 'position' => 'Taga Kain', 'image' => '1760155448_Cats.jpg', 'division' => '1', 'office' => '3', 'designation' => '0', 'status' => 'Active', 'created_at' => '2025-10-11 04:04:08', 'updated_at' => '2025-10-14 15:11:16', 'user_id' => '11']
        ];
        $this->insertData('employees', $sampleData);
        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('employees');
    }
    
    public function getDescription() {
        return 'Create employees table with sample data';
    }
}