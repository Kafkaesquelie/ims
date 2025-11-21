<?php
class Session {

    public $msg = [];
    private $user_is_logged_in = false;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->flash_msg();
        $this->setupUserLogin();
    }

    /** ---------------------------
     *   LOGIN STATUS CHECK
     *  --------------------------- */
    public function isUserLoggedIn() {
        return $this->user_is_logged_in;
    }

    public function requireLogin() {
        if (!$this->isUserLoggedIn()) {
            header("Location: index.php");
            exit();
        }
    }

    /** ---------------------------
     *   LOGIN & LOGOUT
     *  --------------------------- */
    public function login($user_id) {
        if (!empty($user_id)) {
            $_SESSION['id'] = $user_id;
            $this->user_is_logged_in = true;
        }
    }

    private function setupUserLogin() {
        $this->user_is_logged_in = !empty($_SESSION['id']);
    }

    public function logout() {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
        session_write_close();

        $this->user_is_logged_in = false;
    }

    /** ---------------------------
     *   FLASH MESSAGE HANDLER
     *  --------------------------- */
    public function msg($type = '', $msg = '') {
        if (!empty($msg)) {
            // Convert short type (d,w,i,s) → Bootstrap types
            if (strlen(trim($type)) == 1) {
                $type = str_replace(
                    ['d', 'i', 'w', 's'],
                    ['danger', 'info', 'warning', 'success'],
                    $type
                );
            }
            $_SESSION['msg'][$type] = $msg;
        } else {
            // Return flash messages stored earlier
            return $this->msg;
        }
    }

    private function flash_msg() {
        if (!empty($_SESSION['msg'])) {
            $this->msg = $_SESSION['msg'];
            unset($_SESSION['msg']);
        } else {
            $this->msg = [];
        }
    }
}

$session = new Session();
$msg = $session->msg();
?>
