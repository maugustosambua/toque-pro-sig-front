<?php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPS_Settings_Controller {

    // Inicializa o módulo Configurações
    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
    }

    // Regista opção, secções e campos
    public static function register_settings() {

        // Regista opção com merge entre tabs
        register_setting(
            'tps_settings',
            'tps_settings',
            array( __CLASS__, 'sanitize' )
        );

        /* =========================
         * UI
         * ========================= */

        // Secção UI
        add_settings_section(
            'tps_ui',
            'UI Settings',
            '__return_false',
            'tps-settings-ui'
        );

        // Campo: Linhas por página
        add_settings_field(
            'per_page',
            'Rows per page',
            array( __CLASS__, 'number_field' ),
            'tps-settings-ui',
            'tps_ui',
            array( 'key' => 'per_page' )
        );

        /* =========================
         * COMPANY
         * ========================= */

        // Secção Company
        add_settings_section(
            'tps_company',
            'Company Information',
            '__return_false',
            'tps-settings-company'
        );

        // Campo: Nome da empresa
        add_settings_field(
            'company_name',
            'Company Name',
            array( __CLASS__, 'text_field' ),
            'tps-settings-company',
            'tps_company',
            array( 'key' => 'company_name' )
        );

        // Campo: NUIT
        add_settings_field(
            'company_nuit',
            'NUIT',
            array( __CLASS__, 'text_field' ),
            'tps-settings-company',
            'tps_company',
            array( 'key' => 'company_nuit' )
        );

        /* =========================
         * TAX
         * ========================= */

        // Secção Tax
        add_settings_section(
            'tps_tax',
            'Tax Settings',
            '__return_false',
            'tps-settings-tax'
        );

        // Campo: IVA
        add_settings_field(
            'tax_rate',
            'VAT (%)',
            array( __CLASS__, 'number_field' ),
            'tps-settings-tax',
            'tps_tax',
            array( 'key' => 'tax_rate' )
        );

        /* =========================
         * NUMBERING
         * ========================= */

        // Secção Numbering
        add_settings_section(
            'tps_numbering',
            'Document Numbering',
            '__return_false',
            'tps-settings-numbering'
        );

        // Campo: Próximo número Invoice
        add_settings_field(
            'invoice_next_number',
            'Next Invoice Number',
            array( __CLASS__, 'number_field' ),
            'tps-settings-numbering',
            'tps_numbering',
            array( 'key' => 'invoice_next_number' )
        );

        // Campo: Próximo número VD
        add_settings_field(
            'vd_next_number',
            'Next VD Number',
            array( __CLASS__, 'number_field' ),
            'tps-settings-numbering',
            'tps_numbering',
            array( 'key' => 'vd_next_number' )
        );

        // Campo: Próximo número Quotation
        add_settings_field(
            'quotation_next_number',
            'Next Quotation Number',
            array( __CLASS__, 'number_field' ),
            'tps-settings-numbering',
            'tps_numbering',
            array( 'key' => 'quotation_next_number' )
        );
    }

    // Sanitiza e funde dados para não perder tabs
    public static function sanitize( $input ) {

        $stored = get_option( 'tps_settings', array() );

        if ( ! is_array( $stored ) ) {
            $stored = array();
        }

        if ( ! is_array( $input ) ) {
            $input = array();
        }

        foreach ( $input as $key => $value ) {
            if ( is_string( $value ) ) {
                $input[ $key ] = sanitize_text_field( $value );
            }
        }

        return array_merge( $stored, $input );
    }

    // Campo de texto genérico
    public static function text_field( $args ) {
        $options = get_option( 'tps_settings', array() );
        $value   = $options[ $args['key'] ] ?? '';
        echo '<input type="text" class="regular-text" name="tps_settings[' . esc_attr( $args['key'] ) . ']" value="' . esc_attr( $value ) . '">';
    }

    // Campo numérico genérico
    public static function number_field( $args ) {
        $options = get_option( 'tps_settings', array() );
        $value   = $options[ $args['key'] ] ?? '';
        echo '<input type="number" class="small-text" name="tps_settings[' . esc_attr( $args['key'] ) . ']" value="' . esc_attr( $value ) . '">';
    }
}
