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

    }

    // Guarda documento draft
    public static function save() {

        // Verifica permissão
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die();
        }

        // Verifica nonce
        check_admin_referer( 'tps_save_document' );

        // Valida tipo
        $type = sanitize_text_field( $_POST['document_type'] ?? '' );
        if ( ! TPS_Documents_Model::is_valid_type( $type ) ) {
            wp_die();
        }

        // Dados básicos
        $data = array(
            'type'        => $type,
            'number'      => TPS_Documents_Model::next_number_preview( $type ),
            'customer_id' => (int) $_POST['customer_id'],
            'issue_date'  => sanitize_text_field( $_POST['issue_date'] ),
        );

        // Insere documento
        TPS_Documents_Model::insert($data);

        global $wpdb;
        $document_id = $wpdb->insert_id;

        // Redireciona para edição
        wp_safe_redirect(
            admin_url('admin.php?page=tps-documents-add&document_id=' . $document_id)
        );
        exit;
    }

    // Adiciona linha
    public static function add_line() {

        // Verifica permissão
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die();
        }

        // Verifica nonce
        check_admin_referer( 'tps_add_line' );

        // Dados
        $data = array(
            'document_id' => (int) $_POST['document_id'],
            'description' => sanitize_text_field( $_POST['description'] ),
            'quantity'    => (float) $_POST['quantity'],
            'unit_price'  => (float) $_POST['unit_price'],
        );

        // Insere linha
        TPS_Document_Lines_Model::insert( $data );

        // Redirecciona de volta
        wp_safe_redirect( admin_url( 'admin.php?page=tps-documents-add&document_id=' . $data['document_id'] ) );
        exit;
    }

    // Remove linha
    public static function delete_line() {

        // Verifica permissão
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die();
        }

        // Verifica nonce
        check_admin_referer( 'tps_delete_line' );

        $line_id     = isset( $_GET['line_id'] ) ? (int) $_GET['line_id'] : 0;
        $document_id = isset( $_GET['document_id'] ) ? (int) $_GET['document_id'] : 0;

        if ( ! $line_id || ! $document_id ) {
            wp_die();
        }

        global $wpdb;

        // Remove linha
        $wpdb->delete(
            $wpdb->prefix . 'tps_document_lines',
            array( 'id' => $line_id ),
            array( '%d' )
        );

        // Redirecciona
        wp_safe_redirect( admin_url( 'admin.php?page=tps-documents-add&document_id=' . $document_id ) );
        exit;
    }

    // Emite documento
    public static function issue() {

        // Verifica permissão
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die();
        }

        // Verifica nonce
        check_admin_referer( 'tps_issue_document' );

        $document_id = isset( $_REQUEST['document_id'] ) ? (int) $_REQUEST['document_id'] : 0;
        if ( ! $document_id ) {
            wp_die();
        }

        $document = TPS_Documents_Model::get( $document_id );
        if ( ! $document ) {
            wp_die();
        }

        // Só emite draft
        if ( $document->status !== 'draft' ) {
            wp_safe_redirect( admin_url( 'admin.php?page=tps-documents-add&document_id=' . $document_id ) );
            exit;
        }

        // Tem linhas?
        if ( ! TPS_Document_Lines_Model::has_lines( $document_id ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=tps-documents-add&document_id=' . $document_id ) );
            exit;
        }

        // Obtém número final
        $final_number = tps_get_and_increment_document_number( $document->type );

        // Actualiza número e emite
        TPS_Documents_Model::update_number( $document_id, $final_number );
        TPS_Documents_Model::issue( $document_id );

        // Volta para edição
        wp_safe_redirect( admin_url( 'admin.php?page=tps-documents-add&document_id=' . $document_id ) );
        exit;
    }


    // Cancela documento
    public static function cancel() {

        // Permissão
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die();
        }

        // Nonce
        check_admin_referer( 'tps_cancel_document' );

        $document_id = isset( $_REQUEST['document_id'] ) ? (int) $_REQUEST['document_id'] : 0;
        if ( ! $document_id ) {
            wp_die();
        }

        $document = TPS_Documents_Model::get( $document_id );
        if ( ! $document ) {
            wp_die();
        }

        // Cancela apenas emitidos
        if ( $document->status !== 'issued' ) {
            wp_safe_redirect( admin_url( 'admin.php?page=tps-documents-add&document_id=' . $document_id ) );
            exit;
        }

        TPS_Documents_Model::cancel( $document_id );

        wp_safe_redirect( admin_url( 'admin.php?page=tps-documents-add&document_id=' . $document_id ) );
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
            wp_die( 'Only issued documents can be exported to PDF.' );
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
            wp_die( 'PDF library not installed.' );
        }

        require_once $autoload;

        $mpdf = new \Mpdf\Mpdf();

        // HTML do PDF
        $html = '<h2>' . esc_html( $company ) . '</h2>';
        $html .= '<div>NUIT: ' . esc_html( $nuit ) . '</div>';
        $html .= '<hr>';
        $html .= '<h3>' . strtoupper( esc_html( $document->type ) ) . ' #' . esc_html( $document->number ) . '</h3>';
        $html .= '<div>Date: ' . esc_html( $document->issue_date ) . '</div>';
        $html .= '<div>Status: ' . esc_html( $document->status ) . '</div>';
        $html .= '<div>Customer ID: ' . esc_html( $document->customer_id ) . '</div>';
        $html .= '<br>';

        $html .= '<table width="100%" border="1" cellspacing="0" cellpadding="6">';
        $html .= '<thead><tr>';
        $html .= '<th align="left">Description</th>';
        $html .= '<th align="right">Qty</th>';
        $html .= '<th align="right">Unit</th>';
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
            $html .= '
                <div style="
                    margin: 10px 0;
                    padding: 10px;
                    border: 2px solid #a61b1b;
                    color: #a61b1b;
                    font-weight: bold;
                    text-align: center;
                    font-size: 16px;
                ">
                    CANCELLED
                </div>
            ';
        }

        $mpdf->WriteHTML( $html );

        // Nome do ficheiro
        $filename = strtoupper( $document->type ) . '-' . str_pad( (int) $document->number, 4, '0', STR_PAD_LEFT ) . '.pdf';

        // Download
        $mpdf->Output( $filename, 'D' );
        exit;
    }

}
