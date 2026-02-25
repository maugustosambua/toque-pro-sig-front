<?php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Retorna chave da numeração por tipo
function tps_numbering_key_by_type( $type ) {

    $map = array(
        'invoice'   => 'invoice_next_number',
        'vd'        => 'vd_next_number',
        'quotation' => 'quotation_next_number',
    );

    return $map[ $type ] ?? '';
}

// Retorna próximo número e incrementa nas settings
function tps_get_and_increment_document_number( $type ) {

    $key = tps_numbering_key_by_type( $type );
    if ( ! $key ) {
        return 0;
    }

    $settings = get_option( 'tps_settings', array() );

    $current = isset( $settings[ $key ] ) ? (int) $settings[ $key ] : 1;

    $settings[ $key ] = $current + 1;
    update_option( 'tps_settings', $settings );

    return $current;
}
