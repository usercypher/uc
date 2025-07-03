function env($key, $value = null, $default = null) {
    static $store = array();
    
    $args = func_num_args();
    if ($args === 1 || $args === 3) {
        return isset($store[$key]) ? $store[$key] : $default;
    } else {
        $store[$key] = $value;
        return $value;
    }
}
