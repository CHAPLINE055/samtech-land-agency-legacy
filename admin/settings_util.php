<?php
// Lightweight settings helper for admin panel
if (!function_exists('get_setting')) {
    function get_setting($conn, $key, $default = null) {
        if (!$conn) return $default;
        $keyEsc = $conn->real_escape_string($key);
        $sql = "SELECT meta_value FROM settings WHERE meta_key='$keyEsc' LIMIT 1";
        $res = $conn->query($sql);
        if ($res && ($row = $res->fetch_assoc())) {
            return $row['meta_value'];
        }
        return $default;
    }
}

if (!function_exists('get_settings')) {
    function get_settings($conn, $keys_with_defaults) {
        $out = [];
        foreach ($keys_with_defaults as $k => $def) {
            $out[$k] = get_setting($conn, $k, $def);
        }
        return $out;
    }
}