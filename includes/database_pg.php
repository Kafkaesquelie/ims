<?php
require_once(LIB_PATH_INC . DS . "config.php");

class Postgres_DB {

    private $con;
    public $query_id;

    function __construct() {
        $this->db_connect();
    }

    /*--------------------------------------------------------------*/
    /* Open database connection */
    /*--------------------------------------------------------------*/
    public function db_connect()
    {
        $host = DB_HOST;
        $port = DB_PORT;
        $db   = DB_NAME;
        $user = DB_USER;
        $pass = DB_PASS;

        try {
            $this->con = new PDO(
                "pgsql:host=$host;port=$port;dbname=$db",
                $user,
                $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    /*--------------------------------------------------------------*/
    /* Close database connection */
    /*--------------------------------------------------------------*/
    public function db_disconnect()
    {
        $this->con = null;
    }

    /*--------------------------------------------------------------*/
    /* Execute a query */
    /*--------------------------------------------------------------*/
    public function query($sql)
    {
        if (trim($sql) != "") {
            try {
                $this->query_id = $this->con->query($sql);
            } catch (PDOException $e) {
                die("Error on this Query:<pre>$sql</pre>\n" . $e->getMessage());
            }
        }
        return $this->query_id;
    }

    /*--------------------------------------------------------------*/
    /* Fetch helpers */
    /*--------------------------------------------------------------*/
    public function fetch_array($statement)
    {
        return $statement->fetch(PDO::FETCH_BOTH);
    }

    public function fetch_object($statement)
    {
        return $statement->fetch(PDO::FETCH_OBJ);
    }

    public function fetch_assoc($statement)
    {
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function num_rows($statement)
    {
        return $statement->rowCount();
    }

    public function insert_id()
    {
        return $this->con->lastInsertId();
    }

    public function affected_rows()
    {
        return $this->query_id ? $this->query_id->rowCount() : 0;
    }

    /*--------------------------------------------------------------*/
    /* Escape string for queries */
    /*--------------------------------------------------------------*/
    public function escape($str)
    {
        return htmlspecialchars($str, ENT_QUOTES);
    }

    /*--------------------------------------------------------------*/
    /* While loop helper */
    /*--------------------------------------------------------------*/
    public function while_loop($loop)
    {
        $results = [];
        while ($result = $this->fetch_assoc($loop)) {
            $results[] = $result;
        }
        return $results;
    }
}

// Instantiate PostgreSQL DB
$db_pg = new Postgres_DB();
?>
