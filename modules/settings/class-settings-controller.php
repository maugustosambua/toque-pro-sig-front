<?php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPS_Settings_Controller {

    // Inicializa o módulo Configurações
    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
        add_action( 'admin_init', array( __CLASS__, 'suppress_default_settings_notices' ), 20 );
        add_action( 'admin_init', array( __CLASS__, 'bridge_settings_notice_to_tps_notice' ), 30 );
    }

    // Remove avisos padrao do Settings API na pagina do plugin.
    public static function suppress_default_settings_notices() {
        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( 'tps-settings' !== $page ) {
            return;
        }

        remove_action( 'admin_notices', 'settings_errors' );
        remove_action( 'network_admin_notices', 'settings_errors' );
        remove_action( 'user_admin_notices', 'settings_errors' );
    }

    // Converte feedback da Settings API para o pipeline unificado tps_notice.
    public static function bridge_settings_notice_to_tps_notice() {
        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( 'tps-settings' !== $page ) {
            return;
        }

        if ( isset( $_GET['tps_notice'] ) ) {
            return;
        }

        $tab          = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'ui';
        $allowed_tabs = array( 'ui', 'company', 'tax', 'numbering' );
        if ( ! in_array( $tab, $allowed_tabs, true ) ) {
            $tab = 'ui';
        }

        $settings_messages = get_settings_errors( 'tps_settings' );
        if ( empty( $settings_messages ) && isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) {
            $settings_messages[] = array(
                'type'    => 'updated',
                'message' => 'Configurações guardadas com sucesso.',
            );
        }

        if ( empty( $settings_messages ) ) {
            return;
        }

        $first_type  = isset( $settings_messages[0]['type'] ) ? (string) $settings_messages[0]['type'] : 'info';
        $notice_type = ( 'updated' === $first_type || 'success' === $first_type ) ? 'success' : $first_type;
        if ( ! in_array( $notice_type, array( 'success', 'error', 'warning', 'info' ), true ) ) {
            $notice_type = 'info';
        }

        $messages = array();
        foreach ( $settings_messages as $item ) {
            if ( ! isset( $item['message'] ) || '' === (string) $item['message'] ) {
                continue;
            }
            $messages[] = sanitize_text_field( (string) $item['message'] );
        }

        $message_text = implode( ' ', $messages );
        if ( '' === $message_text ) {
            return;
        }

        $url = tps_notice_url(
            admin_url( 'admin.php?page=tps-settings&tab=' . rawurlencode( $tab ) ),
            'settings_feedback',
            $notice_type,
            array(
                'tps_notice_message' => $message_text,
            )
        );

        wp_safe_redirect( esc_url_raw( $url ) );
        exit;
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
            'Configurações da Interface',
            '__return_false',
            'tps-settings-ui'
        );

        // Campo: Linhas por página
        add_settings_field(
            'per_page',
            'Linhas por página',
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
            'Informações da Empresa',
            '__return_false',
            'tps-settings-company'
        );

        // Campo: Nome da empresa
        add_settings_field(
            'company_name',
            'Nome da Empresa',
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
            'Configurações de Impostos',
            '__return_false',
            'tps-settings-tax'
        );

        // Campo: IVA
        add_settings_field(
            'tax_rate',
            'IVA (%)',
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
            'Numeração de Documentos',
            '__return_false',
            'tps-settings-numbering'
        );

        // Campo: Próximo número Invoice
        add_settings_field(
            'invoice_next_number',
            'Próximo Número de Fatura',
            array( __CLASS__, 'number_field' ),
            'tps-settings-numbering',
            'tps_numbering',
            array( 'key' => 'invoice_next_number' )
        );

        // Campo: Próximo número VD
        add_settings_field(
            'vd_next_number',
            'Próximo Número de VD',
            array( __CLASS__, 'number_field' ),
            'tps-settings-numbering',
            'tps_numbering',
            array( 'key' => 'vd_next_number' )
        );

        // Campo: Próximo número Quotation
        add_settings_field(
            'quotation_next_number',
            'Próximo Número de Cotação',
            array( __CLASS__, 'number_field' ),
            'tps-settings-numbering',
            'tps_numbering',
            array( 'key' => 'quotation_next_number' )
        );
    }

    // Sanitiza e funde dados para não perder tabs
    public static function sanitize( $input ) {

        $stored    = get_option( 'tps_settings', array() );
        $has_error = false;

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

        $numeric_rules = array(
            'per_page'              => array(
                'min'     => 5,
                'max'     => 200,
                'message' => 'Linhas por página deve estar entre 5 e 200.',
            ),
            'tax_rate'              => array(
                'min'     => 0,
                'max'     => 100,
                'message' => 'IVA (%) deve estar entre 0 e 100.',
            ),
            'invoice_next_number'   => array(
                'min'     => 1,
                'max'     => 99999999,
                'message' => 'Próximo Número de Fatura deve ser maior ou igual a 1.',
            ),
            'vd_next_number'        => array(
                'min'     => 1,
                'max'     => 99999999,
                'message' => 'Próximo Número de VD deve ser maior ou igual a 1.',
            ),
            'quotation_next_number' => array(
                'min'     => 1,
                'max'     => 99999999,
                'message' => 'Próximo Número de Cotação deve ser maior ou igual a 1.',
            ),
        );

        foreach ( $numeric_rules as $key => $rule ) {
            if ( ! array_key_exists( $key, $input ) || '' === $input[ $key ] ) {
                continue;
            }

            if ( ! is_numeric( $input[ $key ] ) ) {
                $has_error   = true;
                $input[ $key ] = $stored[ $key ] ?? '';
                add_settings_error( 'tps_settings', 'tps_settings_' . $key . '_nan', $rule['message'], 'error' );
                continue;
            }

            $value = (float) $input[ $key ];
            if ( $value < $rule['min'] || $value > $rule['max'] ) {
                $has_error   = true;
                $input[ $key ] = $stored[ $key ] ?? '';
                add_settings_error( 'tps_settings', 'tps_settings_' . $key . '_range', $rule['message'], 'error' );
                continue;
            }

            $input[ $key ] = (string) (int) round( $value );
        }

        if ( ! $has_error ) {
            add_settings_error( 'tps_settings', 'tps_settings_saved', 'Configurações guardadas com sucesso.', 'updated' );
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

