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

// Catálogo de motivos fiscais para linhas isentas/não sujeitas.
function tps_get_fiscal_exemption_reasons() {
    $defaults = array(
        'MZ-IVA-ART09' => 'Operacao isenta de IVA (artigo 9).',
        'MZ-IVA-ART10' => 'Operacao isenta de IVA (artigo 10).',
        'MZ-IVA-ART11' => 'Operacao isenta de IVA (artigo 11).',
        'MZ-IVA-EXPO'  => 'Exportacao de bens/servicos (taxa 0).',
        'MZ-IVA-NS'    => 'Operacao fora do ambito de incidencia do IVA.',
    );

    $reasons = apply_filters( 'tps_fiscal_exemption_reasons', $defaults );

    return is_array( $reasons ) ? $reasons : $defaults;
}

// Retorna label de um código de motivo fiscal.
function tps_get_fiscal_exemption_label( $code ) {
    $reasons = tps_get_fiscal_exemption_reasons();
    $code    = strtoupper( trim( (string) $code ) );

    return isset( $reasons[ $code ] ) ? (string) $reasons[ $code ] : '';
}

// Verifica se código fiscal de isenção é válido.
function tps_is_valid_fiscal_exemption_code( $code ) {
    return '' !== tps_get_fiscal_exemption_label( $code );
}

// Permissão central para gerir classificação fiscal no detalhe do documento.
function tps_current_user_can_manage_fiscal_rules() {
    $can = function_exists( 'tps_current_user_can' )
        ? tps_current_user_can( 'fiscal' )
        : ( current_user_can( 'manage_options' ) || current_user_can( 'tps_manage_fiscal_rules' ) );

    return (bool) apply_filters( 'tps_current_user_can_manage_fiscal_rules', $can );
}
