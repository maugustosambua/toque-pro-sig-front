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

        // Cancela documento
        add_action( 'admin_post_tps_cancel_document', array( __CLASS__, 'cancel' ) );

        // Download do pdf
        add_action( 'admin_post_tps_download_document_pdf', array( __CLASS__, 'download_pdf' ) );
        add_action( 'admin_post_tps_export_documents', array( __CLASS__, 'export_documents' ) );

        // Pesquisa de clientes (AJAX)
        add_action( 'wp_ajax_tps_search_customers', array( __CLASS__, 'search_customers_ajax' ) );
        add_action( 'wp_ajax_tps_ajax_documents_list', array( __CLASS__, 'list_ajax' ) );

    }

    // Pesquisa clientes para seleccao no documento
    public static function search_customers_ajax() {

        if ( ! current_user_can( 'manage_options' ) ) {
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

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permissão negada.' ), 403 );
        }

        check_ajax_referer( 'tps_ajax_documents_list', 'nonce' );

        $search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
        $doc_type = isset( $_GET['doc_type'] ) ? sanitize_text_field( wp_unslash( $_GET['doc_type'] ) ) : '';
        $status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
        $sort = isset( $_GET['sort'] ) ? sanitize_text_field( wp_unslash( $_GET['sort'] ) ) : 'date';
        $page = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
        $per_page = (int) tps_get_per_page();
        if ( $per_page <= 0 ) {
            $per_page = 20;
        }

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

        $args = array(
            'search'   => $search,
            'type'     => $doc_type,
            'status'   => $status,
            'orderby'  => $orderby,
            'order'    => $order,
            'per_page' => $per_page,
            'offset'   => ( $page - 1 ) * $per_page,
        );

        $items = TPS_Documents_Model::get_documents( $args );
        $total_items = (int) TPS_Documents_Model::count_documents(
            array(
                'search' => $search,
                'type'   => $doc_type,
                'status' => $status,
            )
        );
        $total_pages = max( 1, (int) ceil( $total_items / $per_page ) );

        $rows = array();
        foreach ( $items as $item ) {
            $totals = TPS_Document_Lines_Model::totals( $item->id );
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
                'total'         => number_format( (float) $totals['total'], 2 ),
                'edit_url'      => admin_url( 'admin.php?page=tps-documents-add&document_id=' . (int) $item->id ),
                'issue_url'     => wp_nonce_url( admin_url( 'admin-post.php?action=tps_issue_document&document_id=' . (int) $item->id ), 'tps_issue_document' ),
                'cancel_url'    => wp_nonce_url( admin_url( 'admin-post.php?action=tps_cancel_document&document_id=' . (int) $item->id ), 'tps_cancel_document' ),
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
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        check_admin_referer( 'tps_export_documents' );

        $search   = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
        $doc_type = isset( $_GET['doc_type'] ) ? sanitize_text_field( wp_unslash( $_GET['doc_type'] ) ) : '';
        $status   = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
        $sort     = isset( $_GET['sort'] ) ? sanitize_text_field( wp_unslash( $_GET['sort'] ) ) : 'date';

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

        $total_documents = (int) TPS_Documents_Model::count_documents(
            array(
                'search' => $search,
                'type'   => $doc_type,
                'status' => $status,
            )
        );

        $documents = TPS_Documents_Model::get_documents(
            array(
                'search'   => $search,
                'type'     => $doc_type,
                'status'   => $status,
                'orderby'  => $orderby,
                'order'    => $order,
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
                'total',
            )
        );

        foreach ( $documents as $document ) {
            $totals = TPS_Document_Lines_Model::totals( (int) $document->id );
            fputcsv(
                $out,
                array(
                    (string) $document->number,
                    (string) $document->type,
                    (string) $document->status,
                    (string) $document->issue_date,
                    (string) ( $document->customer_name ?? '' ),
                    (string) ( $document->customer_nuit ?? '' ),
                    number_format( (float) $totals['subtotal'], 2, '.', '' ),
                    number_format( (float) $totals['iva'], 2, '.', '' ),
                    number_format( (float) $totals['total'], 2, '.', '' ),
                )
            );
        }

        fclose( $out );
        exit;
    }

    // Guarda documento draft
    public static function save() {

        // Verifica permissão
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        // Verifica nonce
        check_admin_referer( 'tps_save_document' );

        // Valida tipo
        $type = sanitize_text_field( $_POST['document_type'] ?? '' );
        if ( ! TPS_Documents_Model::is_valid_type( $type ) ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        admin_url( 'admin.php?page=tps-documents-add' ),
                        'document_invalid_type',
                        'error'
                    )
                )
            );
            exit;
        }

        // Dados básicos
        $data = array(
            'type'        => $type,
            'number'      => TPS_Documents_Model::next_number_preview( $type ),
            'customer_id' => isset( $_POST['customer_id'] ) ? (int) $_POST['customer_id'] : 0,
            'issue_date'  => sanitize_text_field( $_POST['issue_date'] ),
        );

        $customer = TPS_Customers_Model::get( $data['customer_id'] );
        if ( ! $customer ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        admin_url( 'admin.php?page=tps-documents-add' ),
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

        // Redireciona para edição
        wp_safe_redirect(
            esc_url_raw(
                tps_notice_url(
                    admin_url( 'admin.php?page=tps-documents-add&document_id=' . $document_id ),
                    'document_draft_created',
                    'success'
                )
            )
        );
        exit;
    }

    // Adiciona linha
    public static function add_line() {

        // Verifica permissão
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        // Verifica nonce
        check_admin_referer( 'tps_add_line' );

        $product_service_id = isset( $_POST['product_service_id'] ) ? (int) $_POST['product_service_id'] : 0;
        $description        = isset( $_POST['description'] ) ? sanitize_text_field( wp_unslash( $_POST['description'] ) ) : '';
        $quantity           = isset( $_POST['quantity'] ) ? (float) $_POST['quantity'] : 0;
        $unit_price         = isset( $_POST['unit_price'] ) ? (float) $_POST['unit_price'] : 0;

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
                        admin_url( 'admin.php?page=tps-documents-add&document_id=' . (int) $_POST['document_id'] ),
                        'document_line_invalid',
                        'error'
                    )
                )
            );
            exit;
        }

        // Dados
        $data = array(
            'document_id' => (int) $_POST['document_id'],
            'description' => $description,
            'quantity'    => $quantity,
            'unit_price'  => $unit_price,
        );

        // Insere linha
        TPS_Document_Lines_Model::insert( $data );

        // Redirecciona de volta
        wp_safe_redirect(
            esc_url_raw(
                tps_notice_url(
                    admin_url( 'admin.php?page=tps-documents-add&document_id=' . $data['document_id'] ),
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
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        // Verifica nonce
        check_admin_referer( 'tps_delete_line' );

        $line_id     = isset( $_GET['line_id'] ) ? (int) $_GET['line_id'] : 0;
        $document_id = isset( $_GET['document_id'] ) ? (int) $_GET['document_id'] : 0;

        if ( ! $line_id || ! $document_id ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        admin_url( 'admin.php?page=tps-documents' ),
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
                        admin_url( 'admin.php?page=tps-documents' ),
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
                        admin_url( 'admin.php?page=tps-documents-add&document_id=' . $document_id ),
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

        // Redirecciona
        wp_safe_redirect(
            esc_url_raw(
                tps_notice_url(
                    admin_url( 'admin.php?page=tps-documents-add&document_id=' . $document_id ),
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
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        // Verifica nonce
        check_admin_referer( 'tps_issue_document' );

        $document_id = isset( $_REQUEST['document_id'] ) ? (int) $_REQUEST['document_id'] : 0;
        if ( ! $document_id ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        admin_url( 'admin.php?page=tps-documents' ),
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
                        admin_url( 'admin.php?page=tps-documents' ),
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
                        admin_url( 'admin.php?page=tps-documents-add&document_id=' . $document_id ),
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
                        admin_url( 'admin.php?page=tps-documents-add&document_id=' . $document_id ),
                        'document_issue_no_lines',
                        'warning'
                    )
                )
            );
            exit;
        }

        // Obtém número final
        $final_number = tps_get_and_increment_document_number( $document->type );

        // Actualiza número e emite
        TPS_Documents_Model::update_number( $document_id, $final_number );
        TPS_Documents_Model::issue( $document_id );

        // Volta para edição
        wp_safe_redirect(
            esc_url_raw(
                tps_notice_url(
                    admin_url( 'admin.php?page=tps-documents-add&document_id=' . $document_id ),
                    'document_issued',
                    'success'
                )
            )
        );
        exit;
    }


    // Cancela documento
    public static function cancel() {

        // Permissão
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        // Nonce
        check_admin_referer( 'tps_cancel_document' );

        $document_id = isset( $_REQUEST['document_id'] ) ? (int) $_REQUEST['document_id'] : 0;
        if ( ! $document_id ) {
            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        admin_url( 'admin.php?page=tps-documents' ),
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
                        admin_url( 'admin.php?page=tps-documents' ),
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
                        admin_url( 'admin.php?page=tps-documents-add&document_id=' . $document_id ),
                        'document_cancel_invalid_status',
                        'warning'
                    )
                )
            );
            exit;
        }

        TPS_Documents_Model::cancel( $document_id );

        wp_safe_redirect(
            esc_url_raw(
                tps_notice_url(
                    admin_url( 'admin.php?page=tps-documents-add&document_id=' . $document_id ),
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
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die();
        }

        // Verifica nonce
        check_admin_referer( 'tps_download_document_pdf' );

        $document_id = isset( $_GET['document_id'] ) ? (int) $_GET['document_id'] : 0;
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
        $subtotal = TPS_Document_Lines_Model::document_total( $document_id );
        $iva      = tps_calculate_iva( $subtotal );
        $total    = $subtotal + $iva;

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
        $html .= '<tr><th align="left">Total</th><td align="right"><strong>' . number_format( $total, 2 ) . '</strong></td></tr>';
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

}
