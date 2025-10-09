<?php
require_once('includes/load.php');
//    page_require_level(1);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id <= 0){
    $session->msg("d", "Invalid ID");
    redirect($_SERVER['HTTP_REFERER'], false);
    exit;
}

// Detect source page to know the table
$referer = basename(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH));

switch ($referer) {
    case 'items.php':
        $table = 'items';
        $classification = 'items';
        break;
    case 'users.php':
        $table = 'users';
        $classification = 'users';
        break;
   case 'requests.php':
        $table = 'requests';
        $classification = 'requests';
        break;
    case 'reports.php':
        $table = 'reports';
        $classification = 'reports';
        break;
      case 'cat.php':
        if (isset($_GET['type'])) {
            if ($_GET['type'] === 'subcategory') {
                $table = 'subcategories';
                $classification = 'subcategories';
            } elseif ($_GET['type'] === 'semi') {
                $table = 'semi_categories';
                $classification = 'semi_categories';
            } else {
                $table = 'categories';
                $classification = 'categories';
            }
        } else {
            $table = 'categories';
            $classification = 'categories';
        }
        break;
     case 'logs.php':
        $table = 'requests';  
        $classification = 'requests';
        break;
    case 'emps.php':
        $table = 'employees';
        $classification = 'employees';
        break;
   case 'refs.php':
    $type = $_GET['type'] ?? '';
    if ($type === 'offices') {
        $table = 'offices';
        $classification = 'offices';
    } elseif ($type === 'divisions') {
        $table = 'divisions';
        $classification = 'divisions';
    } elseif ($type === 'fund_clusters') {
        $table = 'fund_clusters';
        $classification = 'fund_clusters';
    } else {
        $session->msg("d", "Unknown reference type.");
        redirect($_SERVER['HTTP_REFERER'], false);
        exit;
    }
    break;





    default:
        $session->msg("d", "Unknown archive source.");
        redirect($_SERVER['HTTP_REFERER'], false);
        exit;
}

if (archive($table, $id, $classification)) {
    $session->msg("s", ucfirst($classification) . " archived successfully!");
} else {
    $session->msg("d", "Failed to archive " . $classification);
}
redirect($_SERVER['HTTP_REFERER'], false);
?>
