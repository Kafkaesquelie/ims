<?php
  require_once('includes/load.php');
/*--------------------------------------------------------------*/
/* Function for find all database table rows by table name
/*--------------------------------------------------------------*/
function find_all($table) {
   global $db;
   if(tableExists($table))
   {
     return find_by_sql("SELECT * FROM ".$db->escape($table));
   }
}
/*--------------------------------------------------------------*/
/* Function for Perform queries
/*--------------------------------------------------------------*/
function find_by_sql($sql)
{
  global $db;
  $result = $db->query($sql);
  $result_set = $db->while_loop($result);
 return $result_set;
}
/*--------------------------------------------------------------*/
/*  Function for Find data from table by id
/*--------------------------------------------------------------*/
function find_by_id($table,$id)
{
  global $db;
  $id = (int)$id;
    if(tableExists($table)){
          $sql = $db->query("SELECT * FROM {$db->escape($table)} WHERE id='{$db->escape($id)}' LIMIT 1");
          if($result = $db->fetch_assoc($sql))
            return $result;
          else
            return null;
     }
}
/*--------------------------------------------------------------*/
/* Function for Delete data from table by id
/*--------------------------------------------------------------*/
function delete_by_id($table,$id)
{
  global $db;
  if(tableExists($table))
   {
    $sql = "DELETE FROM ".$db->escape($table);
    $sql .= " WHERE id=". $db->escape($id);
    $sql .= " LIMIT 1";
    $db->query($sql);
    return ($db->affected_rows() === 1) ? true : false;
   }
}
// *********************************

function archive($table, $id, $classification) {
    global $db;

    // Ensure session is started
    if(session_status() === PHP_SESSION_NONE) session_start();

    // Default values if no session
    $user_id = 0;
    $user_role = 'Unknown';

    // Get logged-in user info from session
    if(isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
        $user_id = (int)$_SESSION['user_id'];
    }

    // Fetch record to archive
    $sql = "SELECT * FROM {$table} WHERE id = '{$id}' LIMIT 1";
    $result = $db->query($sql);

    if ($db->num_rows($result) > 0) {
        $record = $db->fetch_assoc($result);

        // Store archived_by as JSON
        $archived_by = json_encode([ 
            'user_id' => $user_id,
            
        ]);

        // Insert into archive
        $archive_sql = "INSERT INTO archive (record_id, data, classification, archived_at, archived_by) 
                        VALUES (
                            '{$record['id']}',
                            '" . $db->escape(json_encode($record)) . "',
                            '{$classification}',
                            NOW(),
                            '" . $db->escape($archived_by) . "'
                        )";

        if ($db->query($archive_sql)) {
            // Delete original record
            $delete_sql = "DELETE FROM {$table} WHERE id = '{$id}' LIMIT 1";
            return $db->query($delete_sql);
        }
    }

    return false;
}


function archive_request($id) {
    global $db;

    $id = (int)$id;

    $sql = "UPDATE requests SET status='Archived' WHERE id='{$id}' LIMIT 1";

    return $db->query($sql);
}



/*--------------------------------------------------------------*/
/* Function for Restore from archive
/*--------------------------------------------------------------*/


function restore_from_archive($archive_id) {
    global $db;

    // Get archive record
    $sql = "SELECT * FROM archive WHERE id = '{$archive_id}' LIMIT 1";
    $result = $db->query($sql);

    if ($db->num_rows($result) > 0) {
        $archive = $db->fetch_assoc($result);
        $data = json_decode($archive['data'], true);
        $classification = $archive['classification'];

        // Restore into correct table
        $table = $classification;
        $columns = array_keys($data);
        $values = array_map([$db, 'escape'], array_values($data));

        $restore_sql = "INSERT INTO {$table} (" . implode(',', $columns) . ")
                        VALUES ('" . implode("','", $values) . "')";
        
        if ($db->query($restore_sql)) {
            // Remove from archive after restoring
            $delete_sql = "DELETE FROM archive WHERE id = '{$archive_id}' LIMIT 1";
            return $db->query($delete_sql);
        }
    }
    return false;
}



/*--------------------------------------------------------------*/
/* Delete archives older than 30 days
/*--------------------------------------------------------------*/
function purge() {
    global $db;
    $sql = "DELETE FROM archive WHERE archived_at < NOW() - INTERVAL 30 DAY";
    return $db->query($sql);
}

/*--------------------------------------------------------------*/
/* Function for Request Approval and Notification
/*--------------------------------------------------------------*/
function approve_request($request_id) {
    global $db;

    $sql = "UPDATE requests SET status = 'approved' WHERE id = '{$request_id}' LIMIT 1";
    
    if ($db->query($sql)) {
        return true;
    }
    return false;
}





/*--------------------------------------------------------------*/
/* Function for Count id  By table name
/*--------------------------------------------------------------*/

function count_by_id($table){
  global $db;
  if(tableExists($table))
  {
    $sql    = "SELECT COUNT(id) AS total FROM ".$db->escape($table);
    $result = $db->query($sql);
     return($db->fetch_assoc($result));
  }
}
/*--------------------------------------------------------------*/
/* Determine if database table exists
/*--------------------------------------------------------------*/
function tableExists($table){
  global $db;
  $table_exit = $db->query('SHOW TABLES FROM '.DB_NAME.' LIKE "'.$db->escape($table).'"');
      if($table_exit) {
        if($db->num_rows($table_exit) > 0)
              return true;
         else
              return false;
      }
  }
 /*--------------------------------------------------------------*/
 /* Login with the data provided in $_POST,
 /* coming from the login form.
/*--------------------------------------------------------------*/
  function authenticate($username='', $password='') {
    global $db;
    $username = $db->escape($username);
    $password = $db->escape($password);
    $sql  = sprintf("SELECT id,username,password,user_level FROM users WHERE username ='%s' LIMIT 1", $username);
    $result = $db->query($sql);
    if($db->num_rows($result)){
      $user = $db->fetch_assoc($result);
      $password_request = sha1($password);
      if($password_request === $user['password'] ){
        return $user['id'];
      }
    }
   return false;
  }
  /*--------------------------------------------------------------*/
  /* Login with the data provided in $_POST,
  /* coming from the login_v2.php form.
  /* If you used this method then remove authenticate function.
 /*--------------------------------------------------------------*/
   function authenticate_v2($username='', $password='') {
     global $db;
     $username = $db->escape($username);
     $password = $db->escape($password);
     $sql  = sprintf("SELECT id,username,password,user_level FROM users WHERE username ='%s' LIMIT 1", $username);
     $result = $db->query($sql);
     if($db->num_rows($result)){
       $user = $db->fetch_assoc($result);
       $password_request = sha1($password);
       if($password_request === $user['password'] ){
         return $user;
       }
     }
    return false;
   }


  /*--------------------------------------------------------------*/
  /* Find current log in user by session id
  /*--------------------------------------------------------------*/
  function current_user(){
      static $current_user;
      global $db;
      if(!$current_user){
         if(isset($_SESSION['id'])):
             $user_id = intval($_SESSION['id']);
             $current_user = find_by_id('users',$user_id);
        endif;
      }
    return $current_user;
  }
  /*--------------------------------------------------------------*/
  /* Find all user by
  /* Joining users table and user groups table
  /*--------------------------------------------------------------*/
function find_all_user(){
    global $db;
    $sql  = "SELECT u.id, u.name, u.username, u.image, u.user_level, u.status, u.last_login, u.position,u.last_edited, ";
    $sql .= "g.group_name, d.dpt AS dep_name ";
    $sql .= "FROM users u ";
    $sql .= "LEFT JOIN user_groups g ON u.user_level = g.id ";
    $sql .= "LEFT JOIN departments d ON u.department = d.id ";
    $sql .= "ORDER BY u.name ASC";
    return find_by_sql($sql);
}
  /*--------------------------------------------------------------*/
  /* Function to update the last log in of a user
  /*--------------------------------------------------------------*/

 function updateLastLogIn($user_id)
	{
		global $db;
    $date = make_date();
    $sql = "UPDATE users SET last_login='{$date}' WHERE id ='{$user_id}' LIMIT 1";
    $result = $db->query($sql);
    return ($result && $db->affected_rows() === 1 ? true : false);
	}

  /*--------------------------------------------------------------*/
  /* Find all Group name
  /*--------------------------------------------------------------*/
  function find_by_groupName($val)
  {
    global $db;
    $sql = "SELECT group_name FROM user_groups WHERE group_name = '{$db->escape($val)}' LIMIT 1 ";
    $result = $db->query($sql);
    return($db->num_rows($result) === 0 ? true : false);
  }
  /*--------------------------------------------------------------*/
  /* Find group level
  /*--------------------------------------------------------------*/
  function find_by_groupLevel($level)
  {
    global $db;
    $sql = "SELECT group_level FROM user_groups WHERE group_level = '{$db->escape($level)}' LIMIT 1 ";
    $result = $db->query($sql);
    return($db->num_rows($result) === 0 ? true : false);
  }
  /*--------------------------------------------------------------*/
  /* Function for cheaking which user level has access to page
  /*--------------------------------------------------------------*/
   function page_require_level($require_level){
     global $session;
     $current_user = current_user();
     $login_level = find_by_groupLevel($current_user['user_level']);
     //if user not login
     if (!$session->isUserLoggedIn(true)):
            $session->msg('d','Please login to continue.');
            redirect('index.php', false);
    //   //if Group status Deactive
    //  elseif($login_level['group_status'] === '0'):
    //        $session->msg('d','This level user has been banned!');
    //        redirect('home.php',false);
    //   //cheackin log in User level and Require level is Less than or equal to
     elseif($current_user['user_level'] <= (int)$require_level):
              return true;
      else:
            $session->msg("d", "Sorry! you dont have permission to view the page.");
            redirect('home.php', false);
        endif;

     }
   /*--------------------------------------------------------------*/
   /* Function for Finding all product name
   /* JOIN with categorie  and media database table
   /*--------------------------------------------------------------*/
  function join_item_table(){
     global $db;
     $sql  =" SELECT p.id,p.stock_card,p.name,p.quantity,p.unit_cost,p.media_id,p.date_added,c.name";
    $sql  .=" AS categorie,m.file_name AS image";
    $sql  .=" FROM items p";
    $sql  .=" LEFT JOIN categories c ON c.id = p.categorie_id";
    $sql  .=" LEFT JOIN media m ON m.id = p.media_id";
    $sql  .=" ORDER BY p.id ASC";
    return find_by_sql($sql);

   }
  /*--------------------------------------------------------------*/
  /* Function for Finding all product name
  /* Request coming from ajax.php for auto suggest
  /*--------------------------------------------------------------*/

   function find_item_by_title($product_name){
     global $db;
     $p_name = remove_junk($db->escape($product_name));
     $sql = "SELECT name FROM items WHERE name like '%$p_name%' LIMIT 5";
     $result = find_by_sql($sql);
     return $result;
   }

  /*--------------------------------------------------------------*/
  /* Function for Finding all product info by product title
  /* Request coming from ajax.php
  /*--------------------------------------------------------------*/
  function find_all_item_info_by_title($title){
    global $db;
    $sql  = "SELECT * FROM items ";
    $sql .= " WHERE name ='{$title}'";
    $sql .=" LIMIT 1";
    return find_by_sql($sql);
  }

  /*--------------------------------------------------------------*/
  /* Function for Update product quantity
  /*--------------------------------------------------------------*/
  function update_item_qty($qty,$p_id){
    global $db;
    $qty = (int) $qty;
    $id  = (int)$p_id;
    $sql = "UPDATE items SET quantity=quantity -'{$qty}' WHERE id = '{$id}'";
    $result = $db->query($sql);
    return($db->affected_rows() === 1 ? true : false);

  }
  /*--------------------------------------------------------------*/
  /* Function for Display Recent item Added
  /*--------------------------------------------------------------*/
 function find_recent_item_added($limit){
   global $db;
   $sql   = " SELECT p.id,p.name,p.media_id,c.name AS categorie,p.quantity,p.date_added,";
   $sql  .= "m.file_name AS image FROM items p";
   $sql  .= " LEFT JOIN categories c ON c.id = p.categorie_id";
   $sql  .= " LEFT JOIN media m ON m.id = p.media_id";
   $sql  .= " ORDER BY p.id DESC LIMIT ".$db->escape((int)$limit);
   return find_by_sql($sql);
 }
 /*--------------------------------------------------------------*/
 /* Function for Find Need Restocking item
 /*--------------------------------------------------------------*/
function find_lacking_items($threshold = 10){
    global $db;

    $sql  = "SELECT i.id, 
                    i.name, 
                    i.quantity, 
                    i.description, 
                    i.stock_card, 
                    IFNULL(SUM(ri.qty), 0) AS total_req
             FROM items i
             LEFT JOIN request_items ri ON ri.item_id = i.id
             LEFT JOIN requests r ON ri.req_id = r.id AND r.status = 'Approved'
             WHERE i.quantity < ".$db->escape((int)$threshold)."
             GROUP BY i.id, i.name, i.quantity, i.stock_card
             ORDER BY i.quantity ASC";

    return $db->query($sql);
}



 /*--------------------------------------------------------------*/
 /* Function for find all requests
 /*--------------------------------------------------------------*/
function find_all_req() {
    global $db;
    $sql  = "SELECT 
                r.id,r.ris_no,
                r.requested_by, 
                r.remarks,
                COALESCE(u.image, e.image) AS image,
                COALESCE(
                    u.name,
                    CONCAT(e.first_name, ' ', 
                           IFNULL(CONCAT(e.middle_name, ' '), ''), 
                           e.last_name)
                ) AS req_by,
                COALESCE(u.position, e.position) AS position,
                r.date, 
                COALESCE(d.dpt, e.office) AS dep_name,
                r.status,
                GROUP_CONCAT(i.name SEPARATOR ', ') AS item_name,
                GROUP_CONCAT(c.name SEPARATOR ', ') AS cat_name,
                GROUP_CONCAT(ri.qty SEPARATOR ', ') AS qty,
                GROUP_CONCAT(ri.price SEPARATOR ', ') AS price
             FROM requests r
             LEFT JOIN users u ON r.requested_by = u.id
             LEFT JOIN employees e ON r.requested_by = e.id
             LEFT JOIN departments d ON u.department = d.id
             LEFT JOIN request_items ri ON ri.req_id = r.id
             LEFT JOIN items i ON ri.item_id = i.id
             LEFT JOIN categories c ON i.categorie_id = c.id  
             WHERE r.status != 'Completed'
             GROUP BY r.id
             ORDER BY r.id DESC";
    
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}







function count_requests() {
    global $db;
    $sql  = "SELECT COUNT(DISTINCT id) AS total     
    FROM requests r
    WHERE r.status = 'pending'";
    $result = $db->query($sql);
    $row = $result->fetch_assoc();
    return $row['total'];
}


function get_request_item_remarks($req_id) {
    global $db;
    $sql = "SELECT remarks FROM request_items WHERE req_id = '{$req_id}'";
    $result = $db->query($sql);
    $remarks_arr = [];
    while ($row = $result->fetch_assoc()) {
        if(!empty($row['remarks'])) {
            $remarks_arr[] = $row['remarks'];
        }
    }
    return implode(", ", $remarks_arr);
}


function find_request_items($req_id) {
    global $db;
    $req_id = (int)$req_id;
    $sql = "SELECT ri.*, i.name AS item_name, stock_card, price, i.unit_id
            FROM request_items ri
            LEFT JOIN items i ON ri.item_id = i.id
            WHERE ri.req_id = '{$req_id}'";
    $result = $db->query($sql);
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    return $items;
}

  // Helper function: get concatenated item names
  function get_request_items_list($req_id) {
      global $db;
      $sql = "SELECT i.name, ri.qty, ri.price 
              FROM request_items ri
              LEFT JOIN items i ON ri.item_id = i.id
              WHERE ri.req_id = '{$req_id}'";
      $result = $db->query($sql);
      $items_arr = [];
      while($row = $result->fetch_assoc()) {
          $items_arr[] = "{$row['name']} (Qty: {$row['qty']})";
      }
      return implode(", ", $items_arr);
  }

 function find_all_req_logs() {
    global $db;
    $sql = "
        SELECT r.id, r.date, r.status,r.ris_no,
               -- Unified fields for requestor
               COALESCE(u.id, e.id) AS requestor_id,
               COALESCE(u.name, CONCAT(e.first_name, ' ', e.last_name)) AS req_name,
               COALESCE(u.image, e.image, 'default.png') AS prof_pic,
               COALESCE(u.position, e.position) AS req_position,
               COALESCE(d.dpt, e.office) AS req_department
        FROM requests r
        LEFT JOIN users u ON r.requested_by = u.id
        LEFT JOIN departments d ON u.department = d.id
        LEFT JOIN employees e ON r.requested_by = e.id
        WHERE r.status IN ('Completed','Archived')
        ORDER BY r.date DESC
    ";
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}



function find_all_user_req_logs() {
    global $db;
    $sql  = "SELECT r.id AS req_id, r.date, r.status, r.requested_by,r.date,ri.qty,";
    $sql .= "GROUP_CONCAT(i.name SEPARATOR ', ') AS name, ";
    $sql .= "SUM(ri.qty * i.unit_cost) AS total_cost ";
    $sql .= "FROM requests r ";
    $sql .= "JOIN request_items ri ON r.id = ri.req_id ";
    $sql .= "JOIN items i ON ri.item_id = i.id ";
    $sql .= "WHERE r.status IN ('Completed','Archived') ";
    $sql .= "GROUP BY r.id ";
    $sql .= "ORDER BY r.date DESC";
    return find_by_sql($sql);
}

 /*--------------------------------------------------------------*/
 /* Function for Display Highest Request item
 /*--------------------------------------------------------------*/
function find_highest_requested_items($limit = 10){
    global $db;
    $sql  = "SELECT i.id, 
                    i.name, 
                    i.stock_card, 
                    SUM(ri.qty) AS totalQty, 
                    COUNT(DISTINCT r.id) AS totalRequests
             FROM request_items ri
             LEFT JOIN items i ON ri.item_id = i.id
             LEFT JOIN requests r ON ri.req_id = r.id
             WHERE r.status = 'Approved'
             GROUP BY i.id, i.name, i.stock_card
             ORDER BY totalQty DESC
             LIMIT ".$db->escape((int)$limit);
    return $db->query($sql);
}

/******************************/


function get_items_paginated($limit = 10, $page = 1, $category = 'all') {
    global $db;
    $start = ($page - 1) * $limit;
    
    $sql = "SELECT i.id, i.fund_cluster,i.name, i.stock_card, i.unit_id,  u.name AS unit_name, i.quantity, i.unit_cost, i.date_added, i.last_edited,
                   c.name AS category, m.file_name AS image
            FROM items i
            JOIN categories c ON i.categorie_id = c.id
             LEFT JOIN units u ON i.unit_id = u.id
            LEFT JOIN media m ON i.media_id = m.id";
    
    if($category !== 'all') {
        $sql .= " WHERE c.name = '".$db->escape($category)."'";
    }
    
    $sql .= " ORDER BY c.name, i.name
              LIMIT ".$db->escape((int)$limit)." 
              OFFSET ".$db->escape((int)$start);
    
    return find_by_sql($sql);
}

/***************************************************/
// Count total requests by month and optionally 
/***************************************************/

function count_requests_by_month_dept($month, $department_id = 0) {
    global $db;

    // Escape input
    $month = $db->escape($month);
    $department_id = (int)$department_id;

    $where_dept = $department_id > 0 ? "AND d.id = {$department_id}" : "";

    $sql = "
        SELECT COUNT(r.id) AS total_requests
        FROM requests r
        JOIN users u ON r.requested_by = u.id
        JOIN departments d ON u.department = d.id
        WHERE DATE_FORMAT(r.date, '%Y-%m') = '{$month}' {$where_dept}
    ";

    $result = $db->query($sql);
    if ($result && $db->num_rows($result) > 0) {
        $row = $db->fetch_assoc($result);
        return (int)$row['total_requests'];
    }
    return 0;
}


/***********************************************/
// Count total 
/***********************************************/

function count_users() {
    global $db;
    $sql = "SELECT COUNT(*) AS total FROM users";
    $result = $db->query($sql);
    $row = $result->fetch_assoc();
    return (int)$row['total'];
}

// Get stock distribution by category (optionally filtered by month and department)
function get_stock_distribution($month = null, $department_id = 0) {
    global $db;
    $where = [];

    // Filter by month
    if ($month) {
        $where[] = "DATE_FORMAT(r.date, '%Y-%m') = '{$month}'";
    }

    // Filter by specific department ID
    if ($department_id > 0) {
        $where[] = "d.id = {$department_id}";
    }

    // Exclude SPMO department
    $where[] = "d.department <> 'SPMO'";

    // Combine conditions
    $where_sql = count($where) > 0 ? "WHERE " . implode(' AND ', $where) : "";

    $sql = "
        SELECT c.name AS category, SUM(i.quantity) AS total_quantity
        FROM request_items ri
        JOIN requests r ON ri.req_id = r.id
        JOIN items i ON ri.item_id = i.id
        JOIN users u ON r.requested_by = u.id
        JOIN departments d ON u.department = d.id
        JOIN categories c ON i.categorie_id = c.id
        {$where_sql}
        GROUP BY c.id
    ";

    $result = $db->query($sql);
    $distribution = [];
    while ($row = $result->fetch_assoc()) {
        $distribution[$row['category']] = (int)$row['total_quantity'];
    }

    return $distribution;
}

/**********************************************/
//Funtion to get employees
/**********************************************/

function get_employees() {
    global $db;
    $sql = "SELECT id, first_name, middle_name, last_name, position, 'employee' as source , user_id
            FROM employees 
            ORDER BY last_name ASC, first_name ASC";
    $result = $db->query($sql);
    $employees = [];
    while($row = $result->fetch_assoc()){
        $row['full_name'] = trim($row['first_name'] . ' ' . ($row['middle_name'] ?? '') . ' ' . $row['last_name']);
        $employees[] = $row;
    }
    return $employees;
}


// ==========================
// USER REQUEST STATISTICS
// ==========================

function count_user_requests($user_id) {
    global $db;
    $sql = "SELECT COUNT(*) AS total FROM requests WHERE requested_by = '{$db->escape($user_id)}'";
    $result = $db->query($sql);
    $data = $db->fetch_assoc($result);
    return $data ? (int)$data['total'] : 0;
}

function count_user_requests_by_status($user_id, $status) {
    global $db;
    $sql = "SELECT COUNT(*) AS total 
            FROM requests 
            WHERE requested_by = '{$db->escape($user_id)}' 
              AND status = '{$db->escape($status)}'";
    $result = $db->query($sql);
    $data = $db->fetch_assoc($result);
    return $data ? (int)$data['total'] : 0;
}


// ==========================
// COUNT ITEMS IN A REQUEST
// ==========================
function count_request_items($request_id) {
    global $db;
    $sql = "SELECT COUNT(*) AS total 
            FROM request_items 
            WHERE req_id = '{$db->escape($request_id)}'";
    $result = $db->query($sql);
    $data = $db->fetch_assoc($result);
    return $data ? (int)$data['total'] : 0;
}


/************************************************/
//Fetch all transactions
/************************************************/

function find_all_par_transactions() {
    global $db;
    $sql = "
        SELECT 
            t.id,
            t.par_no,
            t.item_id,
            p.property_no,
            p.article AS item_name,
            p.description,
            p.fund_cluster,
            p.unit,
            t.quantity,
            t.transaction_date,
            t.status,
            t.remarks,
            CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name,
            e.position AS position,
            e.office AS department,
            e.image
        FROM transactions t
        LEFT JOIN properties p ON t.item_id = p.id
        LEFT JOIN employees e ON t.employee_id = e.id
        WHERE t.par_no IS NOT NULL
        ORDER BY t.transaction_date DESC
    ";
    return find_by_sql($sql);
}

function find_all_ics_transactions() {
    global $db;
    $sql = "
        SELECT 
            t.id,
            t.ics_no,
            t.item_id,
            s.property_no,
            s.item AS item_name,
            s.item_description,
            s.fund_cluster,
            s.unit,
            t.quantity,
            t.transaction_date,
            t.status,
            t.remarks,
            CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name,
            e.position AS position,
            e.office AS department,
            e.image
        FROM transactions t
        LEFT JOIN semi_exp_prop s ON t.item_id = s.id
        LEFT JOIN employees e ON t.employee_id = e.id
        WHERE t.ics_no IS NOT NULL 
          AND t.ics_no != ''
        ORDER BY t.transaction_date DESC
    ";
    return find_by_sql($sql);
}



?> 
