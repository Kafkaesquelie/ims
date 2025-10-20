<?php
/**
 * Migration: Create transactions table
 * Auto-generated on: 2025-10-20 09:18:48
 */

require_once __DIR__ . '/../includes/Migration.php';

class Migration_create_transactions_table extends Migration {
    
    public function up() {
        // Create transactions table
        $columns = [
            'id int(11) NOT NULL',
            'employee_id int(25) UNSIGNED DEFAULT NULL',
            'item_id int(10) UNSIGNED NOT NULL',
            'quantity int(11) NOT NULL',
            'qty_returned int(25) NOT NULL',
            'qty_re_issued int(25) NOT NULL',
            'PAR_No varchar(25) DEFAULT NULL',
            'ICS_No varchar(25) DEFAULT NULL',
            'RRSP_No varchar(25) NOT NULL',
            'transaction_type enum(\'issue\',\'return\',\'re-issue\',\'disposed\') NOT NULL',
            'transaction_date datetime DEFAULT current_timestamp()',
            'return_date datetime DEFAULT NULL',
            're_issue_date datetime DEFAULT NULL',
            'status enum(\'Issued\',\'Returned\',\'Damaged\',\'Re-issued\',\'Partially Returned\',\'Partially Re-Issued\') NOT NULL DEFAULT \'Issued\'',
            '`condition` varchar(25) NOT NULL',
            'remarks varchar(255) DEFAULT NULL'
        ];
        
        $this->createTable('transactions', $columns);
        
        // Add indexes

        
        // Insert sample data
        try {
        $sampleData = [
            ['id' => '53', 'employee_id' => '5', 'item_id' => '11', 'quantity' => '2', 'qty_returned' => '2', 'qty_re_issued' => '1', 'PAR_No' => null, 'ICS_No' => '2025-10-0094', 'RRSP_No' => '2025-10-0002', 'transaction_type' => 'return', 'transaction_date' => '2025-10-11 00:00:00', 'return_date' => '2025-10-11 00:00:00', 're_issue_date' => '2025-10-11 00:00:00', 'status' => 'Partially Re-Issued', 'condition' => '', 'remarks' => 'TYUIOP\\r\\n'],
            ['id' => '55', 'employee_id' => '6', 'item_id' => '1', 'quantity' => '1', 'qty_returned' => '0', 'qty_re_issued' => '0', 'PAR_No' => '2025-10-0095', 'ICS_No' => null, 'RRSP_No' => '', 'transaction_type' => 'issue', 'transaction_date' => '2025-10-11 00:00:00', 'return_date' => '2028-10-11 00:00:00', 're_issue_date' => null, 'status' => 'Issued', 'condition' => '', 'remarks' => 'jhgtfd'],
            ['id' => '56', 'employee_id' => '2', 'item_id' => '12', 'quantity' => '1', 'qty_returned' => '1', 'qty_re_issued' => '0', 'PAR_No' => null, 'ICS_No' => '2025-10-3002', 'RRSP_No' => '2025-10-0002', 'transaction_type' => 'return', 'transaction_date' => '2025-10-18 00:00:00', 'return_date' => '2025-10-18 00:00:00', 're_issue_date' => null, 'status' => 'Returned', 'condition' => '', 'remarks' => 'bulaga'],
            ['id' => '58', 'employee_id' => '6', 'item_id' => '1', 'quantity' => '1', 'qty_returned' => '1', 'qty_re_issued' => '0', 'PAR_No' => null, 'ICS_No' => '2025-10-0096', 'RRSP_No' => '2025-10-0004', 'transaction_type' => 'return', 'transaction_date' => '2025-10-19 00:00:00', 'return_date' => '2025-10-20 00:00:00', 're_issue_date' => null, 'status' => 'Returned', 'condition' => '', 'remarks' => 'wala na sira na'],
            ['id' => '59', 'employee_id' => '2', 'item_id' => '1', 'quantity' => '1', 'qty_returned' => '0', 'qty_re_issued' => '0', 'PAR_No' => '2025-10-0093', 'ICS_No' => null, 'RRSP_No' => '', 'transaction_type' => 'issue', 'transaction_date' => '2025-10-19 00:00:00', 'return_date' => '2028-10-19 00:00:00', 're_issue_date' => null, 'status' => 'Issued', 'condition' => '', 'remarks' => 'dftrfufytdeaewaghoiutr']
        ];
        $this->insertData('transactions', $sampleData);
        } catch (Exception $e) {
            // Ignore data insertion errors in case of foreign key constraints
        }
    }
    
    public function down() {
        $this->dropTable('transactions');
    }
    
    public function getDescription() {
        return 'Create transactions table with sample data';
    }
}