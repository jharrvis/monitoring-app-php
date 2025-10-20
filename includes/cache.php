<?php
class Cache {
    private static $instance = null;
    private $cache = [];
    private $ttl = [];

    private function __construct() {}

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get($key) {
        if (!CACHE_ENABLED) {
            return null;
        }

        if (!isset($this->cache[$key])) {
            return null;
        }

        // Check if expired
        if (isset($this->ttl[$key]) && time() > $this->ttl[$key]) {
            unset($this->cache[$key]);
            unset($this->ttl[$key]);
            return null;
        }

        return $this->cache[$key];
    }

    public function set($key, $value, $ttl = null) {
        if (!CACHE_ENABLED) {
            return false;
        }

        $this->cache[$key] = $value;

        if ($ttl === null) {
            $ttl = CACHE_DURATION;
        }

        $this->ttl[$key] = time() + $ttl;
        return true;
    }

    public function has($key) {
        return $this->get($key) !== null;
    }

    public function delete($key) {
        unset($this->cache[$key]);
        unset($this->ttl[$key]);
        return true;
    }

    public function clear() {
        $this->cache = [];
        $this->ttl = [];
        return true;
    }

    public function cleanup() {
        $now = time();
        foreach ($this->ttl as $key => $expiry) {
            if ($now > $expiry) {
                unset($this->cache[$key]);
                unset($this->ttl[$key]);
            }
        }
    }

    public function getOrSet($key, $callback, $ttl = null) {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }
}

// Helper function to get cache instance
function cache() {
    return Cache::getInstance();
}
?>
