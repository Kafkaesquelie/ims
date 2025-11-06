<?php
function cache_get($key) {
    $file = "cache/{$key}.cache";
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if ($data['expires'] >= time()) {
            return $data['value'];
        }
    }
    return false;
}

function cache_set($key, $value, $ttl = 300) {
    if (!is_dir('cache')) {
        mkdir('cache', 0777, true);
    }
    $data = [
        'expires' => time() + $ttl,
        'value' => $value
    ];
    file_put_contents("cache/{$key}.cache", json_encode($data));
}
?>
