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

    // Get logged-in user ID safely
    $user_id = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;

    // Fetch original record
    $sql = "SELECT * FROM {$table} WHERE id = '{$id}' LIMIT 1";
    $result = $db->query($sql);

    if ($db->num_rows($result) > 0) {
        $record = $db->fetch_assoc($result);

        // JSON encode record properly for storage
        $json_data = $db->escape(json_encode($record, JSON_UNESCAPED_UNICODE));

        // Insert into archive table (archived_by is integer ✅)
        $archive_sql = "
            INSERT INTO archive (record_id, data, classification, archived_at, archived_by)
            VALUES (
                '{$record['id']}',
                '{$json_data}',
                '{$classification}',
                NOW(),
                {$user_id}
            )";

        if ($db->query($archive_sql)) {
            // ✅ Delete original after archive success
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
//   function authenticate($username='', $password='') {
//     global $db;
//     $username = $db->escape($username);
//     $password = $db->escape($password);
//     $sql  = sprintf("SELECT id,username,password,user_level FROM users WHERE username ='%s' LIMIT 1", $username);
//     $result = $db->query($sql);
//     if($db->num_rows($result)){
//       $user = $db->fetch_assoc($result);
//       $password_request = sha1($password);
//       if($password_request === $user['password'] ){
//         return $user['id'];
//       }
//     }
//    return false;
//   }
  /*--------------------------------------------------------------*/
  /* Login with the data provided in $_POST,
  /* coming from the login_v2.php form.
  /* If you used this method then remove authenticate function.
 /*--------------------------------------------------------------*/
   function authenticate_v2($username = '', $password = '') {
    global $db, $session;

    $username = $db->escape($username);
    $password = $db->escape($password);

    // Fetch user details including status
    $sql  = sprintf("SELECT id, username, password, user_level, status FROM users WHERE username = '%s' LIMIT 1", $username);
    $result = $db->query($sql);

    if ($db->num_rows($result)) {
        $user = $db->fetch_assoc($result);
        $password_request = sha1($password);

        // Check password
        if ($password_request === $user['password']) {

            // 🔒 Check account status (active/inactive)
            if ($user['status'] == '0') {
                $session->msg('d', 'Your account is inactive. Please contact the administrator.');
                redirect('login.php', false);
                exit(); // stop further execution
            }

            // ✅ Return user if active
            return $user;
        }
    }

    // ❌ Invalid username or password
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
function find_all_user() {
    global $db;
    $sql  = "SELECT 
                u.id, 
                u.name, 
                u.username, 
                u.image, 
                u.user_level, 
                u.status, 
                u.last_login, 
                u.position, 
                u.last_edited,
                g.group_name,
                o.office_name,
                d.division_name
            FROM users u
            LEFT JOIN user_groups g ON u.user_level = g.id
            LEFT JOIN offices o ON u.office = o.id
            LEFT JOIN divisions d ON o.division_id = d.id
            ORDER BY u.name ASC";
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
    if (!$session->isUserLoggedIn(true)) {
        $session->msg('d', 'Please login to continue.');
        redirect('login.php', false);
    }
   
    //   //cheackin log in User level and Require level is Less than or equal to
     if ($current_user['user_level'] <= (int)$require_level) {
        return true;
    } else {
        $session->msg("d", "Sorry! You don't have permission to view this page.");
        redirect('login.php', false);
    }
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
    $sql  = "SELECT 
                p.id,
                p.name,
                p.media_id,
                c.name AS categorie,
                p.quantity,
                p.date_added,
                m.file_name AS image
             FROM items p
             LEFT JOIN categories c ON c.id = p.categorie_id
             LEFT JOIN media m ON m.id = p.media_id
             WHERE p.archived = 0
             ORDER BY p.id DESC 
             LIMIT ".$db->escape((int)$limit);

    return find_by_sql($sql);
}

 /*--------------------------------------------------------------*/
 /* Function for Find Need Restocking item
 /*--------------------------------------------------------------*/
function find_lacking_items($threshold = 10){
    global $db;

    $sql  = "SELECT 
                i.id, 
                i.name, 
                i.quantity, 
                i.description, 
                i.stock_card, 
                IFNULL(SUM(ri.qty), 0) AS total_req
             FROM items i
             LEFT JOIN request_items ri ON ri.item_id = i.id
             LEFT JOIN requests r ON ri.req_id = r.id AND r.status = 'Approved'
             WHERE i.archived = 0 
               AND i.quantity < ".$db->escape((int)$threshold)."
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
                r.id, 
                r.ris_no,
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
                COALESCE(o.office_name, eo.office_name) AS office,
                COALESCE(dv.division_name, edv.division_name) AS division,
                r.date, 
                r.status,
                GROUP_CONCAT(i.name SEPARATOR ', ') AS item_name,
                GROUP_CONCAT(c.name SEPARATOR ', ') AS cat_name,
                GROUP_CONCAT(ri.qty SEPARATOR ', ') AS qty,
                GROUP_CONCAT(ri.price SEPARATOR ', ') AS price
             FROM requests r
             LEFT JOIN users u ON r.requested_by = u.id
             LEFT JOIN employees e ON r.requested_by = e.id

             -- Join for offices (users and employees)
             LEFT JOIN offices o ON u.office = o.id
             LEFT JOIN offices eo ON e.office = eo.id

             -- Join for divisions (users and employees)
             LEFT JOIN divisions dv ON u.division = dv.id
             LEFT JOIN divisions edv ON e.division = edv.id

             LEFT JOIN request_items ri ON ri.req_id = r.id
             LEFT JOIN items i ON ri.item_id = i.id
             LEFT JOIN categories c ON i.categorie_id = c.id  

             WHERE r.status != 'Completed'
               AND r.id IN (
                   SELECT DISTINCT ri2.req_id
                   FROM request_items ri2
                   JOIN items i2 ON ri2.item_id = i2.id
                   WHERE i2.archived = 0
               )
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


function find_req_items($req_id) {
    global $db;
    $req_id = (int)$req_id;

    $sql = "SELECT 
                ri.*, 
                i.name AS item_name, 
                i.stock_card, 
                ri.price, 
                i.fund_cluster,
                COALESCE(bu.name, u.name) AS unit_name
            FROM request_items ri
            LEFT JOIN items i ON ri.item_id = i.id
            LEFT JOIN units u ON i.unit_id = u.id
            LEFT JOIN base_units bu ON i.base_unit_id = bu.id
            WHERE ri.req_id = '{$req_id}'";

    $result = $db->query($sql);
    $items = [];

    while ($row = $result->fetch_assoc()) {
        // If the request_items table already has a stored text unit, use it
        $row['unit'] = !empty($row['unit']) ? $row['unit'] : $row['unit_name'];
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
        SELECT 
            r.id, 
            r.date, 
            r.status,
            r.ris_no,
            r.date_completed,
            COALESCE(ou.office_name, eo.office_name) AS office_name,
            COALESCE(u.id, e.id) AS requestor_id,
            COALESCE(u.name, CONCAT(e.first_name, ' ', e.last_name)) AS req_name,
            COALESCE(u.image, e.image, 'default.png') AS prof_pic,
            COALESCE(u.position, e.position) AS req_position
        FROM requests r
        LEFT JOIN users u ON r.requested_by = u.id
        LEFT JOIN employees e ON r.requested_by = e.id
        LEFT JOIN offices ou ON u.office = ou.id   -- user's office
        LEFT JOIN offices eo ON e.office = eo.id   -- employee's office
        WHERE r.status IN ('Completed','Archived','Issued','Canceled','Declined')
        ORDER BY r.date_completed DESC
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
    $sql .= "ORDER BY r.date_completed DESC";
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

// function get_items_paginated($limit = 10, $page = 1, $category = 'all') {
//     global $db;
//     $start = ($page - 1) * $limit;

//     $sql = "SELECT 
//                 i.id, 
//                 i.fund_cluster,
//                 i.name, 
//                 i.stock_card, 
//                 i.unit_id,  
//                 u.name AS unit_name, 
//                 COALESCE(s.stock, i.quantity) AS quantity, 
//                 i.unit_cost, 
//                 i.date_added, 
//                 i.last_edited,
//                 c.name AS category, 
//                 m.file_name AS image
//             FROM items i
//             JOIN categories c ON i.categorie_id = c.id
//             LEFT JOIN units u ON i.unit_id = u.id
//             LEFT JOIN media m ON i.media_id = m.id
//             LEFT JOIN item_stocks_per_year s 
//                 ON s.item_id = i.id 
//                 AND s.school_year_id = (SELECT id FROM school_years WHERE is_current = 1 LIMIT 1)
//             WHERE i.archived = 0"; // 👈 hide archived items

//     if ($category !== 'all') {
//         $sql .= " AND c.name = '".$db->escape($category)."'";
//     }

//     $sql .= " ORDER BY c.name, i.name
//               LIMIT ".$db->escape((int)$limit)." 
//               OFFSET ".$db->escape((int)$start);

//     return find_by_sql($sql);
// }




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
            e.position,
            o.office_name AS department,
            e.image
        FROM transactions t
        LEFT JOIN properties p ON t.item_id = p.id
        LEFT JOIN employees e ON t.employee_id = e.id
        LEFT JOIN offices o ON e.office = o.id
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
            s.inv_item_no,
            s.item AS item_name,
            s.item_description,
            s.fund_cluster,
            s.unit,
            t.quantity,
            t.transaction_date,
            t.status,
            t.remarks,
            CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name,
            e.position,
            o.office_name AS department,
            e.image
        FROM transactions t
        LEFT JOIN semi_exp_prop s ON t.item_id = s.id
        LEFT JOIN employees e ON t.employee_id = e.id
        LEFT JOIN offices o ON e.office = o.id
        WHERE t.ics_no IS NOT NULL 
          AND t.ics_no != ''
        ORDER BY t.transaction_date DESC
    ";
    return find_by_sql($sql);
}

function find_all_par_documents() {
    global $db;
    $sql = "
        SELECT 
            t.par_no,
            CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name,
            o.office_name AS department,
            e.image,
            MAX(t.transaction_date) AS transaction_date,
            COUNT(t.id) AS total_items,
            SUM(t.quantity) AS total_quantity,
            SUM(COALESCE(ri.qty,0)) AS total_returned_qty,
            CASE 
                WHEN SUM(COALESCE(ri.qty,0)) = 0 THEN 'Issued'
                WHEN SUM(COALESCE(ri.qty,0)) < SUM(t.quantity) THEN 'Partially Returned'
                ELSE 'Returned'
            END AS status
        FROM transactions t
        LEFT JOIN return_items ri ON t.id = ri.transaction_id
        LEFT JOIN employees e ON t.employee_id = e.id
        LEFT JOIN offices o ON e.office = o.id
        WHERE t.par_no IS NOT NULL
        GROUP BY t.par_no, e.first_name, e.middle_name, e.last_name, o.office_name, e.image
        ORDER BY transaction_date DESC
    ";
    return find_by_sql($sql);
}

function find_all_ics_documents() {
    global $db;
    $sql = "
        SELECT 
            t.ics_no,
            CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name,
            o.office_name AS department,
            e.image,
            MAX(t.transaction_date) AS transaction_date,
            COUNT(t.id) AS total_items,
            SUM(t.quantity) AS total_quantity,
            SUM(COALESCE(ri.qty,0)) AS total_returned_qty,
            CASE 
                WHEN SUM(COALESCE(ri.qty,0)) = 0 THEN 'Issued'
                WHEN SUM(COALESCE(ri.qty,0)) < SUM(t.quantity) THEN 'Partially Returned'
                ELSE 'Returned'
            END AS status
        FROM transactions t
        LEFT JOIN return_items ri ON t.id = ri.transaction_id
        LEFT JOIN employees e ON t.employee_id = e.id
        LEFT JOIN offices o ON e.office = o.id
        WHERE t.ics_no IS NOT NULL AND t.ics_no != ''
        GROUP BY t.ics_no, e.first_name, e.middle_name, e.last_name, o.office_name, e.image
        ORDER BY transaction_date DESC
    ";
    return find_by_sql($sql);
}

function count_pending_requests() {
    global $db;
    // Count only pending requests that include at least one active (non-archived) item
    $sql = "
        SELECT COUNT(DISTINCT r.id) AS total
        FROM requests r
        LEFT JOIN request_items ri ON r.id = ri.req_id
        LEFT JOIN items i ON ri.item_id = i.id
        WHERE r.status = 'Pending' 
          AND (i.archived = 0 OR i.archived IS NULL)
    ";
    $result = $db->query($sql);
    $data = $result->fetch_assoc();
    return $data['total'];
}

function count_low_stock_items() {
    global $db;
    // Count only non-archived items with quantity <= 10
    $sql = "
        SELECT COUNT(*) AS total 
        FROM items 
        WHERE quantity <= 10 
          AND archived = 0
    ";
    $result = $db->query($sql);
    $data = $result->fetch_assoc();
    return $data['total'];
}


// function calculate_total_inventory_value() {
//     global $db;
//     $sql = "SELECT SUM(quantity * unit_cost) as total_value FROM items";
//     $result = $db->query($sql);
//     $data = $result->fetch_assoc();
//     return $data['total_value'] ? $data['total_value'] : 0;
// }


function find_current_school_year_id() {
    global $db;
    $sql = "SELECT id FROM school_year WHERE is_current = 1 LIMIT 1";
    $result = $db->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id'];
    }
    return null;
}

function update_item_stock_per_year($item_id, $quantity) {
    global $db;

    // ✅ Get the current school year (you can adjust this based on your schema)
    $school_year = find_current_school_year(); 
    if (!$school_year) return;

    $school_year_id = (int)$school_year['id'];

    // ✅ Check if record already exists for this item + school year
    $check_sql = "SELECT id FROM item_stocks_per_year 
                  WHERE item_id = '{$item_id}' 
                  AND school_year_id = '{$school_year_id}' 
                  LIMIT 1";
    $check_result = $db->query($check_sql);

    if ($db->num_rows($check_result) > 0) {
        // ✅ Update existing record
        $db->query("UPDATE item_stocks_per_year 
                    SET stock = '{$quantity}', 
                        updated_at = NOW() 
                    WHERE item_id = '{$item_id}' 
                    AND school_year_id = '{$school_year_id}'");
    } else {
        // ✅ Insert new record
        $db->query("INSERT INTO item_stocks_per_year (item_id, school_year_id, stock)
                    VALUES ('{$item_id}', '{$school_year_id}', '{$quantity}')");
    }
}

// helper to get current school year
function find_current_school_year() {
    global $db;
    $sql = "SELECT * FROM school_years WHERE is_current = 1 LIMIT 1";
    return $db->fetch_assoc($db->query($sql));
}

function find_conversion_by_item($item_id) {
    global $db;
    $sql = "SELECT * FROM unit_conversion WHERE item_id = '{$item_id}' LIMIT 1";
    $result = $db->query($sql);
    return $result && $db->num_rows($result) > 0 ? $db->fetch_assoc($result) : false;
}


function find_unit_name($unit_id) {
    global $db;
    $sql = "SELECT name FROM units WHERE id = '{$db->escape($unit_id)}' LIMIT 1";
    $result = $db->fetch_assoc($db->query($sql));
    return $result ? $result['name'] : '';
}


?> 
