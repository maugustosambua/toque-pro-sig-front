<?php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPS_Settings_Controller {

    // Inicializa o modulo Configuracoes.
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_settings' ) );
        add_action( 'init', array( __CLASS__, 'bridge_settings_notice_to_tps_notice' ), 30 );
    }

    // Converte feedback da Settings API para o pipeline unificado tps_notice.
    public static function bridge_settings_notice_to_tps_notice() {
        if ( 'tps-settings' !== self::current_settings_page() ) {
            return;
        }

        if ( isset( $_GET['tps_notice'] ) ) {
            return;
        }

        $tab               = self::get_current_tab();
        $settings_messages = get_settings_errors( 'tps_settings' );

        $settings_updated = isset( $_GET['settings-updated'] ) ? sanitize_text_field( wp_unslash( $_GET['settings-updated'] ) ) : '';

        if ( empty( $settings_messages ) && 'true' === $settings_updated ) {
            $settings_messages[] = array(
                'type'    => 'updated',
                'message' => 'Configuracoes guardadas com sucesso.',
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
            self::get_tab_form_url( $tab ),
            'settings_feedback',
            $notice_type,
            array(
                'tps_notice_message' => $message_text,
            )
        );

        wp_safe_redirect( esc_url_raw( $url ) );
        exit;
    }

    // Regista a opcao principal das configuracoes.
    public static function register_settings() {
        register_setting(
            'tps_settings',
            'tps_settings',
            array( __CLASS__, 'sanitize' )
        );
    }

    // Sanitiza e funde dados para nao perder tabs.
    public static function sanitize( $input ) {
        $stored    = wp_parse_args( get_option( 'tps_settings', array() ), self::get_defaults() );
        $has_error = false;

        if ( ! is_array( $input ) ) {
            $input = array();
        }

        foreach ( $input as $key => $value ) {
            if ( is_string( $value ) ) {
                $input[ $key ] = sanitize_text_field( $value );
            }
        }

        if ( isset( $input['fiscal_layout_version'] ) ) {
            $input['fiscal_layout_version'] = strtoupper( preg_replace( '/[^A-Z0-9._-]/', '', (string) $input['fiscal_layout_version'] ) );
        }

        $numeric_rules = array(
            'per_page'              => array(
                'min'     => 5,
                'max'     => 200,
                'message' => 'Linhas por pagina deve estar entre 5 e 200.',
            ),
            'tax_rate'              => array(
                'min'     => 0,
                'max'     => 100,
                'message' => 'IVA (%) deve estar entre 0 e 100.',
            ),
            'invoice_next_number'   => array(
                'min'     => 1,
                'max'     => 99999999,
                'message' => 'Proximo Numero de Fatura deve ser maior ou igual a 1.',
            ),
            'vd_next_number'        => array(
                'min'     => 1,
                'max'     => 99999999,
                'message' => 'Proximo Numero de VD deve ser maior ou igual a 1.',
            ),
            'quotation_next_number' => array(
                'min'     => 1,
                'max'     => 99999999,
                'message' => 'Proximo Numero de Cotacao deve ser maior ou igual a 1.',
            ),
            'credit_note_next_number' => array(
                'min'     => 1,
                'max'     => 99999999,
                'message' => 'Proximo Numero de Nota de Credito deve ser maior ou igual a 1.',
            ),
            'debit_note_next_number' => array(
                'min'     => 1,
                'max'     => 99999999,
                'message' => 'Proximo Numero de Nota de Debito deve ser maior ou igual a 1.',
            ),
        );

        foreach ( $numeric_rules as $key => $rule ) {
            if ( ! array_key_exists( $key, $input ) || '' === $input[ $key ] ) {
                continue;
            }

            if ( ! is_numeric( $input[ $key ] ) ) {
                $has_error    = true;
                $input[ $key ] = $stored[ $key ] ?? '';
                add_settings_error( 'tps_settings', 'tps_settings_' . $key . '_nan', $rule['message'], 'error' );
                continue;
            }

            $value = (float) $input[ $key ];
            if ( $value < $rule['min'] || $value > $rule['max'] ) {
                $has_error    = true;
                $input[ $key ] = $stored[ $key ] ?? '';
                add_settings_error( 'tps_settings', 'tps_settings_' . $key . '_range', $rule['message'], 'error' );
                continue;
            }

            $input[ $key ] = (string) (int) round( $value );
        }

        if ( isset( $input['company_name'] ) && '' === trim( (string) $input['company_name'] ) ) {
            $has_error             = true;
            $input['company_name'] = $stored['company_name'];
            add_settings_error( 'tps_settings', 'tps_settings_company_name_required', 'Nome da Empresa e obrigatorio.', 'error' );
        }

        if ( isset( $input['company_nuit'] ) ) {
            $input['company_nuit'] = preg_replace( '/\s+/', '', (string) $input['company_nuit'] );
        }

        if ( ! $has_error ) {
            add_settings_error( 'tps_settings', 'tps_settings_saved', 'Configuracoes guardadas com sucesso.', 'updated' );
        }

        $merged = array_merge( self::get_defaults(), $stored, $input );

        if ( ! $has_error ) {
            $before = array();
            $after  = array();

            foreach ( $merged as $key => $value ) {
                $stored_value = isset( $stored[ $key ] ) ? (string) $stored[ $key ] : '';
                $new_value    = (string) $value;

                if ( $stored_value === $new_value ) {
                    continue;
                }

                $before[ $key ] = $stored_value;
                $after[ $key ]  = $new_value;
            }

            if ( ! empty( $after ) ) {
                tps_audit_log( 'settings_updated', 'settings', 0, $before, $after );
            }
        }

        return $merged;
    }

    // Retorna os valores padrao do sistema.
    public static function get_defaults() {
        return array(
            'per_page'              => '20',
            'company_name'          => get_bloginfo( 'name' ),
            'company_nuit'          => '',
            'tax_rate'              => '16',
            'fiscal_layout_version' => 'AT-MZ-MVP-1.0',
            'invoice_next_number'   => '1',
            'vd_next_number'        => '1',
            'quotation_next_number' => '1',
            'credit_note_next_number' => '1',
            'debit_note_next_number'  => '1',
        );
    }

    // Retorna tabs disponiveis no modulo.
    public static function get_tabs() {
        return array(
            'ui'        => array(
                'label'       => 'Interface',
                'icon'        => 'dashicons-screenoptions',
                'title'       => 'Configuracoes de Interface',
                'description' => 'Ajuste o comportamento geral das listagens e do ambiente de trabalho.',
                'eyebrow'     => 'Experiencia',
            ),
            'company'   => array(
                'label'       => 'Empresa',
                'icon'        => 'dashicons-building',
                'title'       => 'Dados da Empresa',
                'description' => 'Defina a identidade apresentada no frontend, documentos e cabecalhos.',
                'eyebrow'     => 'Identidade',
            ),
            'tax'       => array(
                'label'       => 'Impostos',
                'icon'        => 'dashicons-calculator',
                'title'       => 'Configuracoes Fiscais',
                'description' => 'Controle a taxa de IVA usada no calculo automatico dos documentos.',
                'eyebrow'     => 'Fiscal',
            ),
            'numbering' => array(
                'label'       => 'Numeracao',
                'icon'        => 'dashicons-editor-ol',
                'title'       => 'Sequencias de Documentos',
                'description' => 'Mantenha a continuidade da numeracao emitida por cada tipo de documento.',
                'eyebrow'     => 'Operacao',
            ),
        );
    }

    // Retorna configuracao completa dos campos por tab.
    public static function get_fields_by_tab() {
        return array(
            'ui'        => array(
                array(
                    'key'         => 'per_page',
                    'label'       => 'Linhas por pagina',
                    'type'        => 'number',
                    'description' => 'Quantidade padrao usada nas listagens de clientes, documentos, produtos e utilizadores.',
                    'min'         => 5,
                    'max'         => 200,
                    'step'        => 1,
                    'placeholder' => '20',
                ),
            ),
            'company'   => array(
                array(
                    'key'         => 'company_name',
                    'label'       => 'Nome da Empresa',
                    'type'        => 'text',
                    'description' => 'Este nome aparece no topo da aplicacao, nos documentos e nos recibos.',
                    'placeholder' => 'Ex.: Toque Pro SiG, Lda',
                ),
                array(
                    'key'         => 'company_nuit',
                    'label'       => 'NUIT',
                    'type'        => 'text',
                    'description' => 'Numero fiscal mostrado no cabecalho do app e nos documentos impressos.',
                    'placeholder' => 'Ex.: 400123456',
                ),
            ),
            'tax'       => array(
                array(
                    'key'         => 'tax_rate',
                    'label'       => 'IVA (%)',
                    'type'        => 'number',
                    'description' => 'Percentagem aplicada por defeito no calculo do imposto.',
                    'min'         => 0,
                    'max'         => 100,
                    'step'        => 1,
                    'placeholder' => '16',
                ),
                array(
                    'key'         => 'fiscal_layout_version',
                    'label'       => 'Versao do Layout Fiscal AT',
                    'type'        => 'text',
                    'description' => 'Versao do layout usada na exportacao fiscal para a AT (ex.: AT-MZ-MVP-1.0).',
                    'placeholder' => 'AT-MZ-MVP-1.0',
                ),
            ),
            'numbering' => array(
                array(
                    'key'         => 'invoice_next_number',
                    'label'       => 'Proximo Numero de Fatura',
                    'type'        => 'number',
                    'description' => 'Numero inicial usado na proxima fatura emitida.',
                    'min'         => 1,
                    'max'         => 99999999,
                    'step'        => 1,
                    'placeholder' => '1',
                ),
                array(
                    'key'         => 'vd_next_number',
                    'label'       => 'Proximo Numero de VD',
                    'type'        => 'number',
                    'description' => 'Sequencia reservada para vendas a dinheiro.',
                    'min'         => 1,
                    'max'         => 99999999,
                    'step'        => 1,
                    'placeholder' => '1',
                ),
                array(
                    'key'         => 'quotation_next_number',
                    'label'       => 'Proximo Numero de Cotacao',
                    'type'        => 'number',
                    'description' => 'Numero a usar na proxima cotacao gerada pelo sistema.',
                    'min'         => 1,
                    'max'         => 99999999,
                    'step'        => 1,
                    'placeholder' => '1',
                ),
                array(
                    'key'         => 'credit_note_next_number',
                    'label'       => 'Proximo Numero de Nota de Credito',
                    'type'        => 'number',
                    'description' => 'Numero a usar na proxima nota de credito emitida.',
                    'min'         => 1,
                    'max'         => 99999999,
                    'step'        => 1,
                    'placeholder' => '1',
                ),
                array(
                    'key'         => 'debit_note_next_number',
                    'label'       => 'Proximo Numero de Nota de Debito',
                    'type'        => 'number',
                    'description' => 'Numero a usar na proxima nota de debito emitida.',
                    'min'         => 1,
                    'max'         => 99999999,
                    'step'        => 1,
                    'placeholder' => '1',
                ),
            ),
        );
    }

    // Retorna a tab actual com whitelist.
    public static function get_current_tab() {
        $tabs = self::get_tabs();
        $tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'ui';

        if ( ! isset( $tabs[ $tab ] ) ) {
            $tab = 'ui';
        }

        return $tab;
    }

    // Resolve valor de um campo de configuracao.
    public static function get_setting_value( $key ) {
        $settings = wp_parse_args( get_option( 'tps_settings', array() ), self::get_defaults() );

        return isset( $settings[ $key ] ) ? (string) $settings[ $key ] : '';
    }

    // Resolve os campos de uma tab.
    public static function get_tab_fields( $tab ) {
        $fields_by_tab = self::get_fields_by_tab();

        return isset( $fields_by_tab[ $tab ] ) ? $fields_by_tab[ $tab ] : array();
    }

    // Monta URL de retorno limpa para o form de configuracoes.
    public static function get_tab_form_url( $tab ) {
        return remove_query_arg(
            array( 'settings-updated', 'tps_notice', 'tps_notice_type', 'tps_notice_message' ),
            tps_get_page_url( 'tps-settings', array( 'tab' => sanitize_key( (string) $tab ) ) )
        );
    }

    private static function current_settings_page() {
        if ( isset( $_GET['page'] ) ) {
            return sanitize_key( wp_unslash( $_GET['page'] ) );
        }

        if ( isset( $_GET['tps_view'] ) ) {
            return sanitize_key( wp_unslash( $_GET['tps_view'] ) );
        }

        return '';
    }
}
