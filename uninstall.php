<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Remove as tabelas do plugin.
$tables = array(
    $wpdb->prefix . 'tps_customers',
    $wpdb->prefix . 'tps_documents',
    $wpdb->prefix . 'tps_document_lines',
);

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

// Remove as opções do plugin.
delete_option( 'tps_settings' );
