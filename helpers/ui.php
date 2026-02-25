<?php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Retorna linhas por página (global)
function tps_get_per_page() {

    $settings = get_option( 'tps_settings', array() );

    $per_page = isset( $settings['per_page'] ) ? (int) $settings['per_page'] : 20;

    if ( $per_page < 5 ) {
        $per_page = 5;
    }

    if ( $per_page > 200 ) {
        $per_page = 200;
    }

    return $per_page;
}
