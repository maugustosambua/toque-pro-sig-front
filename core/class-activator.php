<?php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Classe executada na activacao do plugin
class TPS_Activator {

    // Metodo chamado ao activar o plugin.
    public static function activate() {
        require_once TPS_PLUGIN_PATH . 'database/class-schema.php';
        require_once TPS_PLUGIN_PATH . 'helpers/capabilities.php';
        TPS_Schema::install();

        $defaults = array(
            'per_page'              => '20',
            'company_name'          => get_bloginfo( 'name' ),
            'company_nuit'          => '',
            'tax_rate'              => '16',
            'invoice_next_number'   => '1',
            'vd_next_number'        => '1',
            'quotation_next_number' => '1',
        );

        $stored_settings = get_option( 'tps_settings', array() );
        if ( ! is_array( $stored_settings ) ) {
            $stored_settings = array();
        }

        update_option( 'tps_settings', array_merge( $defaults, $stored_settings ) );

        tps_sync_role_capabilities();
        update_option( 'tps_capabilities_matrix_version', '2026-03-15-2' );
    }
}
