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
        'credit_note' => 'credit_note_next_number',
        'debit_note'  => 'debit_note_next_number',
    );

    return $map[ $type ] ?? '';
}

// Retorna próximo número e incrementa nas settings
function tps_get_and_increment_document_number( $type ) {

    $key = tps_numbering_key_by_type( $type );
    if ( ! $key ) {
        return 0;
    }

    global $wpdb;

    $lock_name = $wpdb->prefix . 'tps_numbering_' . sanitize_key( (string) $type );
    $got_lock  = (int) $wpdb->get_var(
        $wpdb->prepare(
            'SELECT GET_LOCK(%s, %d)',
            $lock_name,
            5
        )
    );

    if ( 1 !== $got_lock ) {
        return 0;
    }

    try {
        $settings = get_option( 'tps_settings', array() );

        $current = isset( $settings[ $key ] ) ? (int) $settings[ $key ] : 1;
        if ( $current < 1 ) {
            $current = 1;
        }

        $settings[ $key ] = (string) ( $current + 1 );

        $updated = update_option( 'tps_settings', $settings );
        if ( false === $updated ) {
            return 0;
        }

        return $current;
    } finally {
        $wpdb->get_var(
            $wpdb->prepare(
                'SELECT RELEASE_LOCK(%s)',
                $lock_name
            )
        );
    }
}
