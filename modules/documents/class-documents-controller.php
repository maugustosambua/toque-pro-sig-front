<?php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPS_Documents_Controller {

    // Inicializa hooks
    public static function init() {
        
        // Salva documentos
        add_action( 'admin_post_tps_save_document', array( __CLASS__, 'save' ) );

        // Guarda uma linha
        add_action( 'admin_post_tps_add_line', array( __CLASS__, 'add_line' ) );

        // Remove uma linha
        add_action( 'admin_post_tps_delete_line', array( __CLASS__, 'delete_line' ) );

        // Emite um documento
        add_action( 'admin_post_tps_issue_document', array( __CLASS__, 'issue' ) );

        // Atualiza retenção fiscal em rascunho.
        add_action( 'admin_post_tps_update_document_withholding', array( __CLASS__, 'update_withholding' ) );

        // Cancela documento
        add_action( 'admin_post_tps_cancel_document', array( __CLASS__, 'cancel' ) );

        // Download do pdf
        add_action( 'admin_post_tps_download_document_pdf', array( __CLASS__, 'download_pdf' ) );
        add_action( 'admin_post_tps_export_documents', array( __CLASS__, 'export_documents' ) );
        add_action( 'admin_post_tps_export_fiscal_at', array( __CLASS__, 'export_fiscal_at' ) );
        add_action( 'admin_post_tps_close_fiscal_month', array( __CLASS__, 'close_fiscal_month' ) );
        add_action( 'admin_post_tps_export_fiscal_month_close', array( __CLASS__, 'export_fiscal_month_close' ) );

        // Pesquisa de clientes (AJAX)
        add_action( 'wp_ajax_tps_search_customers', array( __CLASS__, 'search_customers_ajax' ) );
        add_action( 'wp_ajax_tps_ajax_documents_list', array( __CLASS__, 'list_ajax' ) );

    }

    // Pesquisa clientes para seleccao no documento
    public static function search_customers_ajax() {

        if ( ! tps_current_user_can( 'emitir' ) ) {
            wp_send_json_error( array( 'message' => 'Permissão negada.' ), 403 );
        }

        check_ajax_referer( 'tps_search_customers', 'nonce' );

        $term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
        $term = trim( $term );

        if ( $term === '' ) {
            wp_send_json_success( array() );
        }

        $customers = TPS_Customers_Model::get_customers(
            array(
                'search'   => $term,
                'orderby'  => 'name',
                'order'    => 'ASC',
                'per_page' => 20,
                'offset'   => 0,
            )
        );

        $results = array();
        foreach ( $customers as $customer ) {
            $label = (string) $customer->name;
            if ( ! empty( $customer->nuit ) ) {
                $label .= ' - NUIT: ' . (string) $customer->nuit;
            }

            $results[] = array(
                'id'    => (int) $customer->id,
                'name'  => (string) $customer->name,
                'nuit'  => (string) ( $customer->nuit ?? '' ),
                'label' => $label,
            );
        }

        wp_send_json_success( $results );
    }

    // Lista AJAX paginada de documentos
    public static function list_ajax() {

        if ( ! tps_current_user_can_any( array( 'emitir', 'cancelar', 'exportar', 'fiscal' ) ) ) {
            wp_send_json_error( array( 'message' => 'Permissão negada.' ), 403 );
        }

        check_ajax_referer( 'tps_ajax_documents_list', 'nonce' );

        $filters = self::get_list_filters_from_request();
        $page    = max( 1, self::read_get_int( 'paged' ) );
        $per_page = (int) tps_get_per_page();
        if ( $per_page <= 0 ) {
            $per_page = 20;
        }

        $args = array(
            'search'   => $filters['search'],
            'type'     => $filters['doc_type'],
            'status'   => $filters['status'],
            'orderby'  => $filters['orderby'],
            'order'    => $filters['order'],
            'per_page' => $per_page,
            'offset'   => ( $page - 1 ) * $per_page,
        );

        $items = TPS_Documents_Model::get_documents( $args );
        $total_items = (int) TPS_Documents_Model::count_documents(
            array(
                'search' => $filters['search'],
                'type'   => $filters['doc_type'],
                'status' => $filters['status'],
            )
        );
        $total_pages = max( 1, (int) ceil( $total_items / $per_page ) );

        $rows = array();
        foreach ( $items as $item ) {
            $totals = TPS_Documents_Model::fiscal_totals( $item->id );
            $can_issue_document  = tps_current_user_can( 'emitir' );
            $can_cancel_document = tps_current_user_can( 'cancelar' );

            $rows[] = array(
                'id'            => (int) $item->id,
                'number'        => (string) $item->number,
                'type'          => (string) $item->type,
                'customer_name' => (string) ( $item->customer_name ?? '' ),
                'customer_id'   => (int) $item->customer_id,
                'status'        => (string) $item->status,
                'issue_date'    => (string) $item->issue_date,
                'subtotal'      => number_format( (float) $totals['subtotal'], 2 ),
                'iva'           => number_format( (float) $totals['iva'], 2 ),
                'total'         => number_format( (float) $totals['payable_total'], 2 ),
                'edit_url'      => tps_get_page_url( 'tps-documents-add', array( 'document_id' => (int) $item->id ) ),
                'issue_url'     => $can_issue_document ? wp_nonce_url( add_query_arg( array( 'action' => 'tps_issue_document', 'document_id' => (int) $item->id ), tps_get_action_url() ), 'tps_issue_document' ) : '',
                'cancel_url'    => $can_cancel_document ? wp_nonce_url( add_query_arg( array( 'action' => 'tps_cancel_document', 'document_id' => (int) $item->id ), tps_get_action_url() ), 'tps_cancel_document' ) : '',
            );
        }

        wp_send_json_success(
            array(
                'rows'         => $rows,
                'total_items'  => $total_items,
                'total_pages'  => $total_pages,
                'current_page' => min( $page, $total_pages ),
            )
        );
    }

    // Exporta documentos em CSV (com filtros da lista).
    public static function export_documents() {
        if ( ! tps_current_user_can( 'exportar' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        check_admin_referer( 'tps_export_documents' );

        $filters = self::get_list_filters_from_request();

        $total_documents = (int) TPS_Documents_Model::count_documents(
            array(
                'search' => $filters['search'],
                'type'   => $filters['doc_type'],
                'status' => $filters['status'],
            )
        );

        $documents = TPS_Documents_Model::get_documents(
            array(
                'search'   => $filters['search'],
                'type'     => $filters['doc_type'],
                'status'   => $filters['status'],
                'orderby'  => $filters['orderby'],
                'order'    => $filters['order'],
                'per_page' => max( 1, $total_documents ),
                'offset'   => 0,
            )
        );

        while ( ob_get_level() ) {
            ob_end_clean();
        }

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=documentos.csv' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $out = fopen( 'php://output', 'w' );
        fputcsv(
            $out,
            array(
                'number',
                'type',
                'status',
                'issue_date',
                'customer_name',
                'customer_nuit',
                'subtotal',
                'iva',
                'retencao',
                'total',
            )
        );

        foreach ( $documents as $document ) {
            $totals = TPS_Documents_Model::fiscal_totals( (int) $document->id );
            fputcsv(
                $out,
                self::csv_safe_row(
                    array(
                        (string) $document->number,
                        (string) $document->type,
                        (string) $document->status,
                        (string) $document->issue_date,
                        (string) ( $document->customer_name ?? '' ),
                        (string) ( $document->customer_nuit ?? '' ),
                        number_format( (float) $totals['subtotal'], 2, '.', '' ),
                        number_format( (float) $totals['iva'], 2, '.', '' ),
                        number_format( (float) $totals['withholding_amount'], 2, '.', '' ),
                        number_format( (float) $totals['payable_total'], 2, '.', '' ),
                    )
                )
            );
        }

        fclose( $out );
        exit;
    }

    // Exporta layout fiscal AT (MVP) com validação estrutural antes do download.
    public static function export_fiscal_at() {
        if ( ! tps_current_user_can( 'fiscal' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        check_admin_referer( 'tps_export_fiscal_at' );

        $settings             = get_option( 'tps_settings', array() );
        $company_name         = isset( $settings['company_name'] ) ? sanitize_text_field( (string) $settings['company_name'] ) : '';
        $company_nuit         = isset( $settings['company_nuit'] ) ? preg_replace( '/\s+/', '', (string) $settings['company_nuit'] ) : '';
        $fiscal_layout_version = isset( $settings['fiscal_layout_version'] ) ? sanitize_text_field( (string) $settings['fiscal_layout_version'] ) : '';

        if ( '' === $company_name || '' === $company_nuit || '' === $fiscal_layout_version ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents' ),
                        'fiscal_export_invalid_config',
                        'error'
                    )
                )
            );
            exit;
        }

        $filters   = self::get_list_filters_from_request();
        $documents = self::get_documents_for_fiscal_export( $filters );

        if ( empty( $documents ) ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents' ),
                        'fiscal_export_no_documents',
                        'warning'
                    )
                )
            );
            exit;
        }

        $rows = self::build_fiscal_export_rows(
            $documents,
            array(
                'layout_version' => $fiscal_layout_version,
                'company_name'   => $company_name,
                'company_nuit'   => $company_nuit,
            )
        );

        if ( ! self::validate_fiscal_export_rows( $rows ) ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents' ),
                        'fiscal_export_invalid_structure',
                        'error'
                    )
                )
            );
            exit;
        }

        while ( ob_get_level() ) {
            ob_end_clean();
        }

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=fiscal-at-export.csv' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $out = fopen( 'php://output', 'w' );
        foreach ( $rows as $row ) {
            fputcsv( $out, self::csv_safe_row( $row ), ';' );
        }

        fclose( $out );
        exit;
    }

    // Fecha consolidado fiscal mensal.
    public static function close_fiscal_month() {
        if ( ! tps_current_user_can( 'fiscal' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        check_admin_referer( 'tps_close_fiscal_month' );

        $period_ym = self::read_post_text( 'fiscal_period', wp_date( 'Y-m' ) );
        if ( ! preg_match( '/^\d{4}-\d{2}$/', $period_ym ) ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents' ),
                        'fiscal_month_close_invalid_period',
                        'error'
                    )
                )
            );
            exit;
        }

        $closure = TPS_Documents_Model::close_fiscal_month( $period_ym, get_current_user_id() );
        if ( ! $closure ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents' ),
                        'fiscal_month_close_failed',
                        'error'
                    )
                )
            );
            exit;
        }

        wp_safe_redirect(
            esc_url_raw(
                tps_notice_url(
                    tps_get_page_url( 'tps-documents' ),
                    'fiscal_month_closed',
                    'success',
                    array(
                        'period' => $period_ym,
                    )
                )
            )
        );
        exit;
    }

    // Exporta resumo do fecho fiscal mensal.
    public static function export_fiscal_month_close() {
        if ( ! tps_current_user_can( 'fiscal' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        check_admin_referer( 'tps_export_fiscal_month_close' );

        $period_ym = self::read_post_text( 'fiscal_period', wp_date( 'Y-m' ) );
        if ( ! preg_match( '/^\d{4}-\d{2}$/', $period_ym ) ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents' ),
                        'fiscal_month_close_invalid_period',
                        'error'
                    )
                )
            );
            exit;
        }

        $closure = TPS_Documents_Model::get_fiscal_monthly_closure( $period_ym );
        if ( ! $closure ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents' ),
                        'fiscal_month_close_not_found',
                        'warning'
                    )
                )
            );
            exit;
        }

        while ( ob_get_level() ) {
            ob_end_clean();
        }

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=fiscal-month-close-' . $period_ym . '.csv' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $rows = array(
            array( 'field', 'value' ),
            array( 'period_ym', (string) $closure->period_ym ),
            array( 'period_start', (string) $closure->period_start ),
            array( 'period_end', (string) $closure->period_end ),
            array( 'documents_count', (string) $closure->documents_count ),
            array( 'issued_count', (string) $closure->issued_count ),
            array( 'cancelled_count', (string) $closure->cancelled_count ),
            array( 'subtotal', number_format( (float) $closure->subtotal, 2, '.', '' ) ),
            array( 'iva', number_format( (float) $closure->iva, 2, '.', '' ) ),
            array( 'withholding_amount', number_format( (float) $closure->withholding_amount, 2, '.', '' ) ),
            array( 'payable_total', number_format( (float) $closure->payable_total, 2, '.', '' ) ),
            array( 'payments_total', number_format( (float) $closure->payments_total, 2, '.', '' ) ),
            array( 'open_balance_total', number_format( (float) $closure->open_balance_total, 2, '.', '' ) ),
            array( 'closure_prev_hash', (string) $closure->closure_prev_hash ),
            array( 'closure_hash', (string) $closure->closure_hash ),
            array( 'closed_at', (string) $closure->closed_at ),
        );

        $out = fopen( 'php://output', 'w' );
        foreach ( $rows as $row ) {
            fputcsv( $out, self::csv_safe_row( $row ) );
        }
        fclose( $out );
        exit;
    }

    // Guarda documento draft
    public static function save() {

        // Verifica permissão
        if ( ! tps_current_user_can( 'emitir' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        // Verifica nonce
        check_admin_referer( 'tps_save_document' );

        // Valida tipo
        $type = self::read_post_text( 'document_type' );
        if ( ! TPS_Documents_Model::is_valid_type( $type ) ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents-add' ),
                        'document_invalid_type',
                        'error'
                    )
                )
            );
            exit;
        }

        $original_document_id = self::read_post_int( 'original_document_id' );
        $adjustment_reason    = self::read_post_textarea( 'adjustment_reason' );
        $copy_original_lines  = 1 === self::read_post_int( 'copy_original_lines', 1 );

        // Dados básicos
        $data = array(
            'type'        => $type,
            'number'      => TPS_Documents_Model::next_number_preview( $type ),
            'customer_id' => self::read_post_int( 'customer_id' ),
            'original_document_id' => $original_document_id,
            'adjustment_reason'    => $adjustment_reason,
            'issue_date'  => self::read_post_text( 'issue_date' ),
            'due_date'    => self::read_post_text( 'due_date' ),
        );

        if ( TPS_Documents_Model::is_adjustment_type( $type ) ) {
            if ( $original_document_id <= 0 ) {
                wp_safe_redirect(
                    esc_url_raw(
                        tps_notice_url(
                            tps_get_page_url( 'tps-documents-add' ),
                            'document_adjustment_original_required',
                            'error'
                        )
                    )
                );
                exit;
            }

            $original_document = TPS_Documents_Model::get( $original_document_id );
            if ( ! $original_document || 'issued' !== $original_document->status || TPS_Documents_Model::is_adjustment_type( $original_document->type ) || 'quotation' === $original_document->type ) {
                wp_safe_redirect(
                    esc_url_raw(
                        tps_notice_url(
                            tps_get_page_url( 'tps-documents-add' ),
                            'document_adjustment_original_invalid',
                            'error'
                        )
                    )
                );
                exit;
            }

            $data['customer_id'] = (int) $original_document->customer_id;
        } else {
            $data['original_document_id'] = null;
            $data['adjustment_reason']    = '';
        }

        if ( '' === $data['issue_date'] || ! self::is_valid_date_ymd( $data['issue_date'] ) ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents-add' ),
                        'document_invalid_due_date',
                        'error'
                    )
                )
            );
            exit;
        }

        if ( '' !== $data['due_date'] && ! self::is_valid_date_ymd( $data['due_date'] ) ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents-add' ),
                        'document_invalid_due_date',
                        'error'
                    )
                )
            );
            exit;
        }

        if ( '' === $data['due_date'] ) {
            $data['due_date'] = $data['issue_date'];
        }

        if ( '' !== $data['issue_date'] && '' !== $data['due_date'] && strtotime( $data['due_date'] ) < strtotime( $data['issue_date'] ) ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents-add' ),
                        'document_invalid_due_date',
                        'error'
                    )
                )
            );
            exit;
        }

        $customer = TPS_Customers_Model::get( $data['customer_id'] );
        if ( ! $customer ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents-add' ),
                        'document_invalid_customer',
                        'error'
                    )
                )
            );
            exit;
        }

        // Insere documento
        TPS_Documents_Model::insert( $data );

        global $wpdb;
        $document_id = $wpdb->insert_id;

        $draft_notice = 'document_draft_created';
        $draft_type   = 'success';

        if ( $document_id > 0 && TPS_Documents_Model::is_adjustment_type( $type ) ) {
            $copied = null;
            if ( $copy_original_lines ) {
                $copied = TPS_Document_Lines_Model::clone_lines_to_document( (int) $data['original_document_id'], (int) $document_id );
            }

            TPS_Documents_Model::log_fiscal_event(
                $document_id,
                'document_adjustment_linked',
                array(
                    'original_document_id' => (int) $data['original_document_id'],
                    'adjustment_reason'    => (string) $data['adjustment_reason'],
                    'copy_original_lines'  => $copy_original_lines,
                    'lines_copied'         => $copy_original_lines ? ( false !== $copied ) : null,
                ),
                get_current_user_id()
            );

            if ( ! $copy_original_lines ) {
                $draft_notice = 'document_adjustment_lines_skipped';
                $draft_type   = 'info';
            } elseif ( false === $copied ) {
                $draft_notice = 'document_adjustment_lines_copy_failed';
                $draft_type   = 'warning';
            } else {
                $draft_notice = 'document_adjustment_lines_copied';
            }
        }

        // Redireciona para edição
        wp_safe_redirect(
            esc_url_raw(
                tps_notice_url(
                    tps_get_page_url( 'tps-documents-add', array( 'document_id' => $document_id ) ),
                    $draft_notice,
                    $draft_type
                )
            )
        );
        exit;
    }

    // Adiciona linha
    public static function add_line() {

        // Verifica permissão
        if ( ! tps_current_user_can( 'emitir' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        // Verifica nonce
        check_admin_referer( 'tps_add_line' );

        $product_service_id = self::read_post_int( 'product_service_id' );
        $description        = self::read_post_text( 'description' );
        $quantity           = (float) self::read_post_text( 'quantity' );
        $unit_price         = (float) self::read_post_text( 'unit_price' );
        $document_id        = self::read_post_int( 'document_id' );
        $tax_mode           = self::read_post_text( 'tax_mode', 'taxable' );
        $tax_rate           = (float) self::read_post_text( 'tax_rate', (string) ( tps_get_iva_rate() * 100 ) );
        $exemption_code     = self::read_post_text( 'exemption_code', '' );
        $exemption_reason   = self::read_post_text( 'exemption_reason', '' );

        if ( $document_id <= 0 ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents' ),
                        'document_not_found',
                        'error'
                    )
                )
            );
            exit;
        }

        $document = TPS_Documents_Model::get( $document_id );
        if ( ! $document ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents' ),
                        'document_not_found',
                        'error'
                    )
                )
            );
            exit;
        }

        if ( 'draft' !== $document->status ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents-add', array( 'document_id' => $document_id ) ),
                        'document_locked',
                        'warning'
                    )
                )
            );
            exit;
        }

        if ( $product_service_id > 0 && class_exists( 'TPS_Products_Services_Model' ) ) {
            $item = TPS_Products_Services_Model::get( $product_service_id );
            if ( $item ) {
                if ( '' === $description ) {
                    $description = (string) $item->name;
                }
                if ( $unit_price <= 0 ) {
                    $unit_price = (float) $item->price;
                }
            }
        }

        if ( '' === $description || $quantity <= 0 || $unit_price < 0 ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents-add', array( 'document_id' => $document_id ) ),
                        'document_line_invalid',
                        'error'
                    )
                )
            );
            exit;
        }

        $can_manage_fiscal_rules = tps_current_user_can_manage_fiscal_rules();
        $default_tax_rate        = (float) ( tps_get_iva_rate() * 100 );

        if ( ! $can_manage_fiscal_rules ) {
            $has_custom_fiscal_input = ( 'taxable' !== $tax_mode )
                || abs( $tax_rate - $default_tax_rate ) > 0.0001
                || '' !== trim( (string) $exemption_code )
                || '' !== trim( $exemption_reason );

            if ( $has_custom_fiscal_input ) {
                wp_safe_redirect(
                    esc_url_raw(
                        tps_notice_url(
                            tps_get_page_url( 'tps-documents-add', array( 'document_id' => $document_id ) ),
                            'document_line_fiscal_permission_denied',
                            'error'
                        )
                    )
                );
                exit;
            }
        }

        $allowed_tax_modes = array_keys( TPS_Document_Lines_Model::tax_modes() );
        if ( ! in_array( $tax_mode, $allowed_tax_modes, true ) ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents-add', array( 'document_id' => $document_id ) ),
                        'document_line_tax_mode_invalid',
                        'error'
                    )
                )
            );
            exit;
        }

        if ( 'taxable' !== $tax_mode && '' === trim( $exemption_reason ) ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents-add', array( 'document_id' => $document_id ) ),
                        'document_line_exemption_reason_required',
                        'error'
                    )
                )
            );
            exit;
        }

        if ( 'taxable' !== $tax_mode && '' === trim( $exemption_code ) ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents-add', array( 'document_id' => $document_id ) ),
                        'document_line_exemption_code_required',
                        'error'
                    )
                )
            );
            exit;
        }

        if ( 'taxable' !== $tax_mode && ! tps_is_valid_fiscal_exemption_code( $exemption_code ) ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents-add', array( 'document_id' => $document_id ) ),
                        'document_line_exemption_code_invalid',
                        'error'
                    )
                )
            );
            exit;
        }

        if ( 'taxable' !== $tax_mode ) {
            $tax_rate = 0.0;
        }

        if ( $tax_rate < 0 || $tax_rate > 100 ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents-add', array( 'document_id' => $document_id ) ),
                        'document_line_tax_mode_invalid',
                        'error'
                    )
                )
            );
            exit;
        }

        // Dados
        $data = array(
            'document_id'        => $document_id,
            'product_service_id' => $product_service_id,
            'description'        => $description,
            'quantity'           => $quantity,
            'unit_price'         => $unit_price,
            'tax_mode'           => $tax_mode,
            'tax_rate'           => $tax_rate,
            'exemption_code'     => $exemption_code,
            'exemption_reason'   => $exemption_reason,
        );

        // Insere linha
        $line_saved = TPS_Document_Lines_Model::insert( $data );
        if ( false === $line_saved ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents-add', array( 'document_id' => $data['document_id'] ) ),
                        'document_locked',
                        'warning'
                    )
                )
            );
            exit;
        }

        TPS_Documents_Model::sync_payment_totals( $data['document_id'] );

        // Redirecciona de volta
        wp_safe_redirect(
            esc_url_raw(
                tps_notice_url(
                    tps_get_page_url( 'tps-documents-add', array( 'document_id' => $data['document_id'] ) ),
                    'document_line_added',
                    'success'
                )
            )
        );
        exit;
    }

    // Remove linha
    public static function delete_line() {

        // Verifica permissão
        if ( ! tps_current_user_can( 'emitir' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        // Verifica nonce
        check_admin_referer( 'tps_delete_line' );

        $line_id     = self::read_get_int( 'line_id' );
        $document_id = self::read_get_int( 'document_id' );

        if ( ! $line_id || ! $document_id ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents' ),
                        'document_line_invalid',
                        'error'
                    )
                )
            );
            exit;
        }

        $document = TPS_Documents_Model::get( $document_id );
        if ( ! $document ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents' ),
                        'document_not_found',
                        'error'
                    )
                )
            );
            exit;
        }

        if ( $document->status !== 'draft' ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents-add', array( 'document_id' => $document_id ) ),
                        'document_locked',
                        'warning'
                    )
                )
            );
            exit;
        }

        global $wpdb;

        // Remove linha
        $wpdb->delete(
            $wpdb->prefix . 'tps_document_lines',
            array( 'id' => $line_id ),
            array( '%d' )
        );
        TPS_Documents_Model::sync_payment_totals( $document_id );

        // Redirecciona
        wp_safe_redirect(
            esc_url_raw(
                tps_notice_url(
                    tps_get_page_url( 'tps-documents-add', array( 'document_id' => $document_id ) ),
                    'document_line_deleted',
                    'success'
                )
            )
        );
        exit;
    }

    // Emite documento
    public static function issue() {

        // Verifica permissão
        if ( ! tps_current_user_can( 'emitir' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        // Verifica nonce
        check_admin_referer( 'tps_issue_document' );

        $document_id = self::read_document_id_from_request();
        if ( ! $document_id ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents' ),
                        'document_not_found',
                        'error'
                    )
                )
            );
            exit;
        }

        $document = TPS_Documents_Model::get( $document_id );
        if ( ! $document ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents' ),
                        'document_not_found',
                        'error'
                    )
                )
            );
            exit;
        }

        // Só emite draft
        if ( $document->status !== 'draft' ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents-add', array( 'document_id' => $document_id ) ),
                        'document_issue_invalid_status',
                        'warning'
                    )
                )
            );
            exit;
        }

        // Tem linhas?
        if ( ! TPS_Document_Lines_Model::has_lines( $document_id ) ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents-add', array( 'document_id' => $document_id ) ),
                        'document_issue_no_lines',
                        'warning'
                    )
                )
            );
            exit;
        }

        // Obtém número final único e imutável.
        if ( class_exists( 'TPS_Inventory_Model' ) && ! TPS_Inventory_Model::document_has_stock_movements( $document_id, 'document' ) ) {
            $inventory_result = TPS_Inventory_Model::apply_document_issue( $document_id );

            if ( is_wp_error( $inventory_result ) ) {
                $notice_code = 'inventory_issue_failed';
                if ( 'insufficient_stock' === $inventory_result->get_error_code() ) {
                    $notice_code = 'inventory_insufficient_stock';
                }

                wp_safe_redirect(
                    esc_url_raw(
                        tps_notice_url(
                            tps_get_page_url( 'tps-documents-add', array( 'document_id' => $document_id ) ),
                            $notice_code,
                            'error',
                            array(
                                'tps_notice_message' => $inventory_result->get_error_message(),
                            )
                        )
                    )
                );
                exit;
            }
        }

        $final_number = 0;
        $number_set   = false;

        for ( $attempt = 0; $attempt < 20; $attempt++ ) {
            $candidate_number = tps_get_and_increment_document_number( $document->type );
            if ( $candidate_number <= 0 ) {
                break;
            }

            if ( TPS_Documents_Model::set_issued_number( $document_id, $candidate_number ) ) {
                $final_number = (int) $candidate_number;
                $number_set   = true;
                break;
            }
        }

        if ( ! $number_set ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents-add', array( 'document_id' => $document_id ) ),
                        'document_issue_numbering_failed',
                        'error'
                    )
                )
            );
            exit;
        }

        // Emite
        TPS_Documents_Model::issue( $document_id );
        TPS_Documents_Model::log_fiscal_event(
            $document_id,
            'document_issued',
            array(
                'number' => $final_number,
                'type'   => (string) $document->type,
            ),
            get_current_user_id()
        );

        // Volta para edição
        wp_safe_redirect(
            esc_url_raw(
                tps_notice_url(
                    tps_get_page_url( 'tps-documents-add', array( 'document_id' => $document_id ) ),
                    'document_issued',
                    'success'
                )
            )
        );
        exit;
    }

    // Atualiza taxa de retenção em documento rascunho.
    public static function update_withholding() {
        if ( ! tps_current_user_can( 'fiscal' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        check_admin_referer( 'tps_update_document_withholding' );

        $document_id = self::read_post_int( 'document_id' );
        $rate        = (float) self::read_post_text( 'withholding_rate', '0' );

        if ( $document_id <= 0 ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents' ),
                        'document_not_found',
                        'error'
                    )
                )
            );
            exit;
        }

        if ( $rate < 0 || $rate > 100 ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents-add', array( 'document_id' => $document_id ) ),
                        'document_withholding_invalid',
                        'error'
                    )
                )
            );
            exit;
        }

        $updated = TPS_Documents_Model::update_withholding_rate( $document_id, $rate );
        if ( ! $updated ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents-add', array( 'document_id' => $document_id ) ),
                        'document_withholding_locked',
                        'warning'
                    )
                )
            );
            exit;
        }

        wp_safe_redirect(
            esc_url_raw(
                tps_notice_url(
                    tps_get_page_url( 'tps-documents-add', array( 'document_id' => $document_id ) ),
                    'document_withholding_updated',
                    'success'
                )
            )
        );
        exit;
    }


    // Cancela documento
    public static function cancel() {

        // Permissão
        if ( ! tps_current_user_can( 'cancelar' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        // Nonce
        check_admin_referer( 'tps_cancel_document' );

        $document_id = self::read_document_id_from_request();
        if ( ! $document_id ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents' ),
                        'document_not_found',
                        'error'
                    )
                )
            );
            exit;
        }

        $document = TPS_Documents_Model::get( $document_id );
        if ( ! $document ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents' ),
                        'document_not_found',
                        'error'
                    )
                )
            );
            exit;
        }

        // Cancela apenas emitidos
        if ( $document->status !== 'issued' ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents-add', array( 'document_id' => $document_id ) ),
                        'document_cancel_invalid_status',
                        'warning'
                    )
                )
            );
            exit;
        }

        $cancel_reason = self::read_post_text( 'cancel_reason' );
        if ( '' === trim( $cancel_reason ) ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        tps_get_page_url( 'tps-documents-add', array( 'document_id' => $document_id ) ),
                        'document_cancel_reason_required',
                        'error'
                    )
                )
            );
            exit;
        }

        if ( class_exists( 'TPS_Inventory_Model' ) && TPS_Inventory_Model::document_has_stock_movements( $document_id, 'document' ) && ! TPS_Inventory_Model::document_has_stock_movements( $document_id, 'document_cancel' ) ) {
            $inventory_result = TPS_Inventory_Model::reverse_document_issue( $document_id );

            if ( is_wp_error( $inventory_result ) ) {
                wp_safe_redirect(
                    esc_url_raw(
                        tps_notice_url(
                            tps_get_page_url( 'tps-documents-add', array( 'document_id' => $document_id ) ),
                            'inventory_issue_failed',
                            'error',
                            array(
                                'tps_notice_message' => $inventory_result->get_error_message(),
                            )
                        )
                    )
                );
                exit;
            }
        }

        TPS_Documents_Model::cancel( $document_id, $cancel_reason, get_current_user_id() );

        wp_safe_redirect(
            esc_url_raw(
                tps_notice_url(
                    tps_get_page_url( 'tps-documents-add', array( 'document_id' => $document_id ) ),
                    'document_cancelled',
                    'success'
                )
            )
        );
        exit;
    }


    // Faz download do PDF
    public static function download_pdf() {

        // Verifica permissão
        if ( ! tps_current_user_can( 'exportar' ) ) {
            wp_die();
        }

        // Verifica nonce
        check_admin_referer( 'tps_download_document_pdf' );

        $document_id = self::read_get_int( 'document_id' );
        if ( ! $document_id ) {
            wp_die();
        }

        $document = TPS_Documents_Model::get( $document_id );
        if ( ! $document ) {
            wp_die();
        }

        // Permite PDF apenas para emitidos
        if ( $document->status !== 'issued' ) {
            wp_die( 'Apenas documentos emitidos podem ser exportados para PDF.' );
        }

        $lines = TPS_Document_Lines_Model::get_by_document( $document_id );

        // Totais
        $totals   = TPS_Documents_Model::fiscal_totals( $document_id );
        $subtotal = (float) $totals['subtotal'];
        $iva      = (float) $totals['iva'];
        $retencao = (float) $totals['withholding_amount'];
        $total    = (float) $totals['payable_total'];

        // Empresa
        $settings = get_option( 'tps_settings', array() );
        $company  = $settings['company_name'] ?? '';
        $nuit     = $settings['company_nuit'] ?? '';

        // Carrega Composer
        $autoload = TPS_PLUGIN_PATH . 'vendor/autoload.php';
        if ( ! file_exists( $autoload ) ) {
            wp_die( 'Biblioteca de PDF não instalada.' );
        }

        require_once $autoload;

        $mpdf = new \Mpdf\Mpdf();
        $pdf_css_path = tps_get_asset_path( 'assets/css/document-pdf.css' );

        // HTML do PDF
        $html = '<h2>' . esc_html( $company ) . '</h2>';
        $html .= '<div>NUIT: ' . esc_html( $nuit ) . '</div>';
        $html .= '<hr>';
        $html .= '<h3>' . strtoupper( esc_html( $document->type ) ) . ' #' . esc_html( $document->number ) . '</h3>';
        $html .= '<div>Data: ' . esc_html( $document->issue_date ) . '</div>';
        $html .= '<div>Estado: ' . esc_html( $document->status ) . '</div>';
        $html .= '<div>Cliente: ' . esc_html( $document->customer_name ?? '' ) . '</div>';
        if ( ! empty( $document->customer_nuit ) ) {
            $html .= '<div>NUIT do Cliente: ' . esc_html( $document->customer_nuit ) . '</div>';
        }
        $html .= '<div>ID do Cliente: ' . esc_html( $document->customer_id ) . '</div>';
        $html .= '<br>';

        $html .= '<table width="100%" border="1" cellspacing="0" cellpadding="6">';
        $html .= '<thead><tr>';
        $html .= '<th align="left">Descrição</th>';
        $html .= '<th align="right">Qtd.</th>';
        $html .= '<th align="right">Unitário</th>';
        $html .= '<th align="right">Subtotal</th>';
        $html .= '</tr></thead><tbody>';

        foreach ( $lines as $line ) {
            $line_total = TPS_Document_Lines_Model::line_total( $line );

            $html .= '<tr>';
            $html .= '<td>' . esc_html( $line->description ) . '</td>';
            $html .= '<td align="right">' . esc_html( $line->quantity ) . '</td>';
            $html .= '<td align="right">' . number_format( (float) $line->unit_price, 2 ) . '</td>';
            $html .= '<td align="right">' . number_format( $line_total, 2 ) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table><br>';

        $html .= '<table width="40%" align="right" border="1" cellspacing="0" cellpadding="6">';
        $html .= '<tr><th align="left">Subtotal</th><td align="right">' . number_format( $subtotal, 2 ) . '</td></tr>';
        $html .= '<tr><th align="left">IVA (' . number_format( tps_get_iva_rate() * 100, 2 ) . '%)</th><td align="right">' . number_format( $iva, 2 ) . '</td></tr>';
        $html .= '<tr><th align="left">Retencao (' . number_format( (float) $document->withholding_rate, 2 ) . '%)</th><td align="right">-' . number_format( $retencao, 2 ) . '</td></tr>';
        $html .= '<tr><th align="left">Total Liquido</th><td align="right"><strong>' . number_format( $total, 2 ) . '</strong></td></tr>';
        $html .= '</table>';

        // Marca de cancelado
        if ( $document->status === 'cancelled' ) {
            $html .= '<div class="tps-pdf-status-cancelled">CANCELADO</div>';
        }

        if ( file_exists( $pdf_css_path ) ) {
            $mpdf->WriteHTML( file_get_contents( $pdf_css_path ), \Mpdf\HTMLParserMode::HEADER_CSS );
        }

        $mpdf->WriteHTML( $html );

        // Nome do ficheiro
        $filename = strtoupper( $document->type ) . '-' . str_pad( (int) $document->number, 4, '0', STR_PAD_LEFT ) . '.pdf';

        // Download
        $mpdf->Output( $filename, 'D' );
        exit;
    }

    private static function read_get_text( $key, $default = '' ) {
        if ( ! isset( $_GET[ $key ] ) ) {
            return (string) $default;
        }

        return sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
    }

    private static function read_post_text( $key, $default = '' ) {
        if ( ! isset( $_POST[ $key ] ) ) {
            return (string) $default;
        }

        return sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
    }

    private static function read_post_textarea( $key, $default = '' ) {
        if ( ! isset( $_POST[ $key ] ) ) {
            return (string) $default;
        }

        return sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) );
    }

    private static function read_get_int( $key, $default = 0 ) {
        if ( ! isset( $_GET[ $key ] ) ) {
            return (int) $default;
        }

        return (int) wp_unslash( $_GET[ $key ] );
    }

    private static function read_post_int( $key, $default = 0 ) {
        if ( ! isset( $_POST[ $key ] ) ) {
            return (int) $default;
        }

        return (int) wp_unslash( $_POST[ $key ] );
    }

    private static function read_document_id_from_request() {
        $post_id = self::read_post_int( 'document_id' );
        if ( $post_id > 0 ) {
            return $post_id;
        }

        return self::read_get_int( 'document_id' );
    }

    private static function is_valid_date_ymd( $value ) {
        if ( ! is_string( $value ) || '' === $value ) {
            return false;
        }

        $date = \DateTime::createFromFormat( 'Y-m-d', $value );

        return $date instanceof \DateTime && $date->format( 'Y-m-d' ) === $value;
    }

    private static function get_list_filters_from_request() {
        $search   = self::read_get_text( 'search' );
        $doc_type = self::read_get_text( 'doc_type' );
        $status   = self::read_get_text( 'status' );
        $sort     = self::read_get_text( 'sort', 'date' );

        $allowed_types = array_keys( TPS_Documents_Model::types() );
        if ( ! in_array( $doc_type, $allowed_types, true ) ) {
            $doc_type = '';
        }

        $allowed_statuses = array_keys( TPS_Documents_Model::statuses() );
        if ( ! in_array( $status, $allowed_statuses, true ) ) {
            $status = '';
        }

        $orderby = 'issue_date';
        $order   = 'DESC';
        if ( 'name' === $sort ) {
            $orderby = 'customer_name';
            $order   = 'ASC';
        } elseif ( 'city' === $sort ) {
            $orderby = 'customer_city';
            $order   = 'ASC';
        }

        return array(
            'search'  => $search,
            'doc_type'=> $doc_type,
            'status'  => $status,
            'orderby' => $orderby,
            'order'   => $order,
        );
    }

    // Carrega documentos elegiveis para exportacao AT (emitidos/cancelados).
    private static function get_documents_for_fiscal_export( $filters ) {
        $doc_type = isset( $filters['doc_type'] ) ? (string) $filters['doc_type'] : '';
        $search   = isset( $filters['search'] ) ? (string) $filters['search'] : '';
        $orderby  = isset( $filters['orderby'] ) ? (string) $filters['orderby'] : 'issue_date';
        $order    = isset( $filters['order'] ) ? (string) $filters['order'] : 'DESC';
        $status   = isset( $filters['status'] ) ? (string) $filters['status'] : '';

        $statuses = array( 'issued', 'cancelled' );
        if ( in_array( $status, $statuses, true ) ) {
            $statuses = array( $status );
        }

        $documents = array();

        foreach ( $statuses as $status_filter ) {
            $count = (int) TPS_Documents_Model::count_documents(
                array(
                    'search' => $search,
                    'type'   => $doc_type,
                    'status' => $status_filter,
                )
            );

            if ( $count <= 0 ) {
                continue;
            }

            $items = TPS_Documents_Model::get_documents(
                array(
                    'search'   => $search,
                    'type'     => $doc_type,
                    'status'   => $status_filter,
                    'orderby'  => $orderby,
                    'order'    => $order,
                    'per_page' => $count,
                    'offset'   => 0,
                )
            );

            foreach ( $items as $item ) {
                $documents[] = $item;
            }
        }

        usort(
            $documents,
            static function ( $a, $b ) {
                $date_a = isset( $a->issue_date ) ? (string) $a->issue_date : '';
                $date_b = isset( $b->issue_date ) ? (string) $b->issue_date : '';
                if ( $date_a === $date_b ) {
                    return (int) $a->id <=> (int) $b->id;
                }

                return strcmp( $date_a, $date_b );
            }
        );

        return $documents;
    }

    // Monta linhas estruturadas para exportacao fiscal AT.
    private static function build_fiscal_export_rows( $documents, $meta ) {
        $rows = array();

        $document_count   = 0;
        $sum_subtotal     = 0.0;
        $sum_iva          = 0.0;
        $sum_withholding  = 0.0;
        $sum_payable      = 0.0;
        $first_issue_date = '';
        $last_issue_date  = '';

        foreach ( $documents as $document ) {
            $issue_date = isset( $document->issue_date ) ? (string) $document->issue_date : '';
            if ( '' !== $issue_date && ( '' === $first_issue_date || $issue_date < $first_issue_date ) ) {
                $first_issue_date = $issue_date;
            }

            if ( '' !== $issue_date && ( '' === $last_issue_date || $issue_date > $last_issue_date ) ) {
                $last_issue_date = $issue_date;
            }
        }

        $rows[] = array(
            'H',
            (string) ( $meta['layout_version'] ?? '' ),
            wp_date( 'Y-m-d H:i:s' ),
            (string) ( $meta['company_name'] ?? '' ),
            (string) ( $meta['company_nuit'] ?? '' ),
            $first_issue_date,
            $last_issue_date,
            (string) count( $documents ),
        );

        foreach ( $documents as $document ) {
            $document_id = (int) $document->id;
            $totals      = TPS_Documents_Model::fiscal_totals( $document_id );
            $lines       = TPS_Document_Lines_Model::get_by_document( $document_id );

            $document_count++;
            $sum_subtotal    += (float) $totals['subtotal'];
            $sum_iva         += (float) $totals['iva'];
            $sum_withholding += (float) $totals['withholding_amount'];
            $sum_payable     += (float) $totals['payable_total'];

            $rows[] = array(
                'D',
                (string) $document_id,
                (string) $document->type,
                (string) $document->number,
                (string) $document->status,
                (string) $document->issue_date,
                (string) ( $document->customer_nuit ?? '' ),
                number_format( (float) $totals['subtotal'], 2, '.', '' ),
                number_format( (float) $totals['iva'], 2, '.', '' ),
                number_format( (float) $totals['withholding_amount'], 2, '.', '' ),
                number_format( (float) $totals['total'], 2, '.', '' ),
                number_format( (float) $totals['payable_total'], 2, '.', '' ),
                isset( $document->original_document_id ) ? (string) (int) $document->original_document_id : '',
                isset( $document->adjustment_reason ) ? (string) $document->adjustment_reason : '',
            );

            foreach ( $lines as $line ) {
                $line_subtotal = (float) $line->quantity * (float) $line->unit_price;
                $line_iva      = (float) TPS_Document_Lines_Model::line_iva( $line );
                $line_total    = $line_subtotal + $line_iva;

                $rows[] = array(
                    'L',
                    (string) $document_id,
                    (string) (int) $line->id,
                    (string) $line->description,
                    number_format( (float) $line->quantity, 2, '.', '' ),
                    number_format( (float) $line->unit_price, 2, '.', '' ),
                    isset( $line->tax_mode ) ? (string) $line->tax_mode : 'taxable',
                    number_format( isset( $line->tax_rate ) ? (float) $line->tax_rate : (float) ( tps_get_iva_rate() * 100 ), 2, '.', '' ),
                    isset( $line->exemption_code ) ? (string) $line->exemption_code : '',
                    number_format( $line_subtotal, 2, '.', '' ),
                    number_format( $line_iva, 2, '.', '' ),
                    number_format( $line_total, 2, '.', '' ),
                );
            }
        }

        $rows[] = array(
            'T',
            (string) $document_count,
            number_format( $sum_subtotal, 2, '.', '' ),
            number_format( $sum_iva, 2, '.', '' ),
            number_format( $sum_withholding, 2, '.', '' ),
            number_format( $sum_payable, 2, '.', '' ),
        );

        return $rows;
    }

    // Valida estrutura minima do layout fiscal antes do download.
    private static function validate_fiscal_export_rows( $rows ) {
        if ( ! is_array( $rows ) || count( $rows ) < 3 ) {
            return false;
        }

        $has_header = false;
        $has_total  = false;
        $doc_ids    = array();
        $doc_count  = 0;

        foreach ( $rows as $index => $row ) {
            if ( ! is_array( $row ) || empty( $row[0] ) ) {
                return false;
            }

            $record_type = (string) $row[0];

            if ( 'H' === $record_type ) {
                if ( 8 !== count( $row ) || $index !== 0 ) {
                    return false;
                }

                if ( '' === trim( (string) $row[1] ) || '' === trim( (string) $row[3] ) || '' === trim( (string) $row[4] ) ) {
                    return false;
                }

                $has_header = true;
            } elseif ( 'D' === $record_type ) {
                if ( 14 !== count( $row ) ) {
                    return false;
                }

                if ( '' === trim( (string) $row[1] ) || '' === trim( (string) $row[2] ) || '' === trim( (string) $row[3] ) || '' === trim( (string) $row[4] ) || '' === trim( (string) $row[5] ) ) {
                    return false;
                }

                $doc_ids[ (string) $row[1] ] = true;
                $doc_count++;
            } elseif ( 'L' === $record_type ) {
                if ( 12 !== count( $row ) ) {
                    return false;
                }

                if ( '' === trim( (string) $row[1] ) || ! isset( $doc_ids[ (string) $row[1] ] ) ) {
                    return false;
                }
            } elseif ( 'T' === $record_type ) {
                if ( 6 !== count( $row ) ) {
                    return false;
                }

                if ( (int) $row[1] !== $doc_count ) {
                    return false;
                }

                $has_total = true;
            } else {
                return false;
            }
        }

        return $has_header && $has_total && $doc_count > 0;
    }

    private static function csv_safe_row( $row ) {
        if ( ! is_array( $row ) ) {
            return array();
        }

        return array_map( array( __CLASS__, 'csv_safe_cell' ), $row );
    }

    private static function csv_safe_cell( $value ) {
        $cell = (string) $value;

        if ( preg_match( '/^\s*[=+\-@]/', $cell ) ) {
            return "'" . $cell;
        }

        return $cell;
    }

}
