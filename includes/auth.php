<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

class Auth {
    private static $instance = null;

    private function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function login($username, $password) {
        try {
            $sql = "SELECT id, username, password, email, full_name, role
                    FROM admin_users
                    WHERE username = ?
                    LIMIT 1";

            $user = db()->fetchOne($sql, [$username]);

            if (!$user) {
                return ['success' => false, 'message' => 'Username atau password salah'];
            }

            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Username atau password salah'];
            }

            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();

            return [
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role']
                ]
            ];

        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem'];
        }
    }

    public function logout() {
        session_unset();
        session_destroy();
        return ['success' => true, 'message' => 'Logout berhasil'];
    }

    public function isLoggedIn() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }

        // Check session timeout
        if (isset($_SESSION['login_time'])) {
            $elapsed = time() - $_SESSION['login_time'];
            if ($elapsed > SESSION_LIFETIME) {
                $this->logout();
                return false;
            }
        }

        return true;
    }

    public function requireAuth($redirectToLogin = false) {
        if (!$this->isLoggedIn()) {
            if ($redirectToLogin) {
                // Redirect to login page for HTML pages
                header('Location: ' . BASE_URL . '/admin/login.php');
                exit;
            } else {
                // JSON response for API endpoints
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized', 'message' => 'Silakan login terlebih dahulu']);
                exit;
            }
        }
    }

    public function getUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'full_name' => $_SESSION['full_name'] ?? null,
            'role' => $_SESSION['role'] ?? null
        ];
    }

    public function updateSessionTime() {
        if ($this->isLoggedIn()) {
            $_SESSION['login_time'] = time();
        }
    }
}

// Helper function to get auth instance
function auth() {
    return Auth::getInstance();
}
?>
