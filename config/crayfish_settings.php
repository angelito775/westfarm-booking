<?php
/**
 * Read crayfish product settings from config file.
 * Returns array with keys: price_per_kg, min_order_kg, max_order_kg, product_name, is_active
 */
function getCrayfishSettings() {
    $settings_file = __DIR__ . '/crayfish_settings.json';
    $defaults = [
        'price_per_kg'  => 120,
        'min_order_kg'  => 0.5,
        'max_order_kg'  => 100,
        'product_name'  => 'Fresh Live Crayfish',
        'is_active'     => true,
    ];

    if (file_exists($settings_file)) {
        $json = file_get_contents($settings_file);
        $data = json_decode($json, true);
        if (is_array($data)) {
            return array_merge($defaults, $data);
        }
    }
    return $defaults;
}
