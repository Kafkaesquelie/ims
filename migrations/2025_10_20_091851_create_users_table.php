<?php
/**
 * Migration: Create users table
 * Auto-generated on: 2025-10-20 09:18:51
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_users_table extends Migration {
    
    public function up() {
        // Create users table
        $columns = [
            'id int(11) UNSIGNED NOT NULL',
            'name varchar(60) NOT NULL',
            'username varchar(50) NOT NULL',
            'password varchar(255) NOT NULL',
            'office varchar(25) NOT NULL',
            'position varchar(255) NOT NULL',
            'division varchar(25) NOT NULL',
            'user_level int(11) NOT NULL',
            'image varchar(255) DEFAULT \'no_image.jpg\'',
            'status int(1) NOT NULL',
            'last_login datetime DEFAULT NULL',
            'last_edited datetime DEFAULT NULL ON UPDATE current_timestamp()',
            'created_at datetime DEFAULT current_timestamp()'
        ];
        
        $this->createTable('users', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {
        $sampleData = [
            ['id' => '6', 'name' => 'Meow Cat', 'username' => 'user1', 'password' => '12dea96fec20593566ab75692c9949596833adc9', 'office' => '11', 'position' => 'Taga kain', 'division' => '3', 'user_level' => '3', 'image' => 'j4ub93go6.jpg', 'status' => '1', 'last_login' => '2025-10-19 21:56:45', 'last_edited' => '2025-10-20 03:56:45', 'created_at' => '2025-10-05 22:59:16'],
            ['id' => '10', 'name' => 'Administrator', 'username' => 'Admin', 'password' => 'd033e22ae348aeb5660fc2140aec35850c4da997', 'office' => '12', 'position' => 'Admin', 'division' => '1', 'user_level' => '1', 'image' => 'xokc7qp110.jpg', 'status' => '1', 'last_login' => '2025-10-20 06:27:00', 'last_edited' => '2025-10-20 12:27:00', 'created_at' => '2025-10-05 22:59:16'],
            ['id' => '11', 'name' => 'Jhoy Bangleg', 'username' => 'User2', 'password' => '12dea96fec20593566ab75692c9949596833adc9', 'office' => '8', 'position' => 'Taga Kain', 'division' => '4', 'user_level' => '3', 'image' => '9djigqw11.jpg', 'status' => '1', 'last_login' => '2025-10-20 04:30:15', 'last_edited' => '2025-10-20 10:30:15', 'created_at' => '2025-10-05 22:59:16'],
            ['id' => '12', 'name' => 'Dave Nabaysan', 'username' => 'user3', 'password' => '12dea96fec20593566ab75692c9949596833adc9', 'office' => '10', 'position' => 'HR Manager', 'division' => '3', 'user_level' => '3', 'image' => '3bct9r7912.jpg', 'status' => '1', 'last_login' => '2025-10-19 22:02:24', 'last_edited' => '2025-10-20 04:02:24', 'created_at' => '2025-10-05 22:59:16'],
            ['id' => '28', 'name' => 'kafka', 'username' => 'IT', 'password' => '12dea96fec20593566ab75692c9949596833adc9', 'office' => '12', 'position' => 'Taga Kain', 'division' => '1', 'user_level' => '2', 'image' => 'b94yw6ny28.jpg', 'status' => '1', 'last_login' => '2025-10-19 18:52:59', 'last_edited' => '2025-10-20 03:24:55', 'created_at' => '2025-10-05 22:59:16']
        ];
        $this->insertData('users', $sampleData);
        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('users');
    }
    
    public function getDescription() {
        return 'Create users table with sample data';
    }
}