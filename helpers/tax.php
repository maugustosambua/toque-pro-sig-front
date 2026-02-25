<?php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Retorna taxa de IVA das settings
function tps_get_iva_rate() {

    $settings = get_option( 'tps_settings', array() );

    if ( empty( $settings['tax_rate'] ) ) {
        return 0;
    }

    $rate = (float) $settings['tax_rate'];

    // Normaliza se vier em percentagem
    if ( $rate > 1 ) {
        $rate = $rate / 100;
    }

    return $rate;
}

// Calcula valor do IVA
function tps_calculate_iva( $amount ) {
    return $amount * tps_get_iva_rate();
}
