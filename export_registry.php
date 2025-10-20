<?php
require_once 'vendor/autoload.php';
use PhpOffice\PhpWord\TemplateProcessor;

// Only proceed on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get submitted data
    $fund_cluster = $_POST['fund_cluster'] ?? '';
    $tableData = json_decode($_POST['tableData'], true);

    // --- Debugging Start ---
    echo "<pre>";
    echo "Fund Cluster: "; var_dump($fund_cluster);
    echo "\nOriginal Table Data: "; var_dump($tableData);
    echo "\nTotal rows in data: " . count($tableData);
    echo "</pre>";

    // Map numeric keys to associative keys for PhpWord
    $tableDataAssoc = array_map(function($row) {
        $issued = (float) ($row[5] ?? 0);
        $returned = (float) ($row[7] ?? 0);
        return [
            'date' => $row[0] ?? '',
            'ics_no' => $row[1] ?? '',
            'inv_item_no' => $row[2] ?? '',
            'description' => $row[3] ?? '',
            'useful_life' => $row[4] ?? '',
            'issued_qty' => $row[5] ?? '',
            'issued_officer' => $row[6] ?? '',
            'returned_qty' => $row[7] ?? '',
            'returned_officer' => $row[8] ?? '',
            'reissued_qty' => $row[9] ?? '',
            'reissued_officer' => $row[10] ?? '',
            'disposed' => '', 
            'balance' => $issued - $returned,
            'amount' => $row[13] ?? '',
            'remarks' => $row[14] ?? ''
        ];
    }, $tableData);

    echo "<pre>Mapped Table Data: "; var_dump($tableDataAssoc); echo "</pre>";

    
    // Load Word template
    $templatePath = __DIR__ . '/templates/Registry_Template.docx';
    if (!file_exists($templatePath)) die("Template file not found at $templatePath");

    $template = new TemplateProcessor($templatePath);

    // Replace fund cluster
    $template->setValue('fund_cluster', htmlspecialchars($fund_cluster ?: '__________'));

    $totalRows = 25;
    $dataCount = count($tableDataAssoc);

    echo "<p>Cloning rows: $dataCount</p>";

    // Clone row using 'date' placeholder
    try {
        $template->cloneRow('date', max($dataCount, 1));
    } catch (\PhpOffice\PhpWord\Exception\Exception $e) {
        die("Error cloning row: " . $e->getMessage());
    }

    // Fill table data
    foreach ($tableDataAssoc as $i => $row) {
        $index = $i + 1;
        $template->setValue("date#$index", $row['date']);
        $template->setValue("ics_no#$index", $row['ics_no']);
        $template->setValue("inv_item_no#$index", $row['inv_item_no']);
        $template->setValue("description#$index", $row['description']);
        $template->setValue("useful_life#$index", $row['useful_life']);
        $template->setValue("issued_qty#$index", $row['issued_qty']);
        $template->setValue("issued_officer#$index", $row['issued_officer']);
        $template->setValue("returned_qty#$index", $row['returned_qty']);
        $template->setValue("returned_officer#$index", ($row['returned_qty'] ?? 0) > 0 ? $row['returned_officer'] : '');
        $template->setValue("reissued_qty#$index", $row['reissued_qty']);
        $template->setValue("reissued_officer#$index", ($row['reissued_qty'] ?? 0) > 0 ? $row['reissued_officer'] : '');
        $template->setValue("disposed#$index", $row['disposed']);
        $template->setValue("balance#$index", $row['balance']);
        $template->setValue("amount#$index", $row['amount']);
        $template->setValue("remarks#$index", $row['remarks']);
    }

    // Fill remaining empty rows up to 25
    for ($i = $dataCount; $i < $totalRows; $i++) {
        $index = $i + 1;
        $fields = [
            'date','ics_no','inv_item_no','description','useful_life',
            'issued_qty','issued_officer','returned_qty','returned_officer',
            'reissued_qty','reissued_officer','disposed','balance','amount','remarks'
        ];
        foreach ($fields as $field) {
            $template->setValue("$field#$index", '');
        }
    }

    // Ensure exports folder exists
    $exportDir = __DIR__ . '/exports';
    if (!is_dir($exportDir)) mkdir($exportDir, 0777, true);

    // Save output
    $outputFile = $exportDir . '/Registry_Filled_' . date('Ymd_His') . '.docx';
    $template->saveAs($outputFile);

    // Send file to browser
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . basename($outputFile) . '"');
    header('Content-Length: ' . filesize($outputFile));
    flush();
    readfile($outputFile);
    exit;
}
?> 