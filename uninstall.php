<?php

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Remove as tabelas do plugin.
$tables = array(
    $wpdb->prefix . 'tps_customers',
    $wpdb->prefix . 'tps_documents',
    $wpdb->prefix . 'tps_document_lines',
    $wpdb->prefix . 'tps_products_services',
    $wpdb->prefix . 'tps_stock_movements',
    $wpdb->prefix . 'tps_payments',
    $wpdb->prefix . 'tps_payment_allocations',
);

foreach ($tables as $table) {
    if (! is_string($table) || ! preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        continue;
    }

    $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
}

// Remove as opções do plugin.
delete_option('tps_settings');
