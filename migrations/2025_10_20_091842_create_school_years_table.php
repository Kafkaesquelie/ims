<?php
/**
 * Migration: Create school_years table
 * Auto-generated on: 2025-10-20 09:18:42
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_school_years_table extends Migration {
    
    public function up() {
        // Create school_years table
        $columns = [
            'id int(11) UNSIGNED NOT NULL',
            'school_year varchar(9) NOT NULL',
            'semester enum(\'1st\',\'2nd\',\'summer\') NOT NULL',
            'start_date date NOT NULL',
            'end_date date NOT NULL',
            'is_current tinyint(1) DEFAULT 0',
            'created_at timestamp NOT NULL DEFAULT current_timestamp()',
            'updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()'
        ];
        
        $this->createTable('school_years', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {
        $sampleData = [
            ['id' => '1', 'school_year' => '2024-2025', 'semester' => '1st', 'start_date' => '2025-07-31', 'end_date' => '2025-12-19', 'is_current' => '1', 'created_at' => '2025-10-13 05:04:54', 'updated_at' => '2025-10-13 05:04:54']
        ];
        $this->insertData('school_years', $sampleData);
        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('school_years');
    }
    
    public function getDescription() {
        return 'Create school_years table with sample data';
    }
}