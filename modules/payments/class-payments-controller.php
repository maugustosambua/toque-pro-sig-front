<?php
// Impede acesso directo.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPS_Payments_Controller {

    // Inicializa hooks.
    public static function init() {
        add_action( 'admin_post_tps_register_payment', array( __CLASS__, 'register_payment' ) );
        add_action( 'admin_post_tps_download_payment_receipt', array( __CLASS__, 'download_receipt' ) );
    }

    // Regista um recebimento.
    public static function register_payment() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        check_admin_referer( 'tps_register_payment' );

        $document_id = isset( $_POST['document_id'] ) ? (int) $_POST['document_id'] : 0;
        $redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : admin_url( 'admin.php?page=tps-payments' );
        $document    = TPS_Documents_Model::get( $document_id );

        if ( ! $document || 'issued' !== $document->status ) {
            self::redirect_with_notice( $redirect_to, 'payment_invalid_document', 'error' );
        }

        $amount = isset( $_POST['amount'] ) ? (float) $_POST['amount'] : 0;
        if ( $amount <= 0 ) {
            self::redirect_with_notice( $redirect_to, 'payment_invalid_amount', 'error' );
        }

        $balance_due = (float) $document->balance_due;
        if ( $amount - $balance_due > 0.009 ) {
            self::redirect_with_notice( $redirect_to, 'payment_amount_exceeds_balance', 'error' );
        }

        $method  = isset( $_POST['method'] ) ? sanitize_text_field( wp_unslash( $_POST['method'] ) ) : 'cash';
        $methods = TPS_Payments_Model::methods();
        if ( ! isset( $methods[ $method ] ) ) {
            $method = 'cash';
        }

        $payment_date = isset( $_POST['payment_date'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_date'] ) ) : wp_date( 'Y-m-d' );

        $payment_id = TPS_Payments_Model::record_payment(
            array(
                'document_id'  => $document_id,
                'customer_id'  => (int) $document->customer_id,
                'payment_date' => $payment_date,
                'amount'       => $amount,
                'method'       => $method,
                'reference'    => isset( $_POST['reference'] ) ? sanitize_text_field( wp_unslash( $_POST['reference'] ) ) : '',
                'notes'        => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
            )
        );

        if ( ! $payment_id ) {
            self::redirect_with_notice( $redirect_to, 'payment_invalid_amount', 'error' );
        }

        self::redirect_with_notice(
            $redirect_to,
            'payment_recorded',
            'success',
            array( 'payment_id' => $payment_id )
        );
    }

    // Gera recibo em PDF.
    public static function download_receipt() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die();
        }

        check_admin_referer( 'tps_download_payment_receipt' );

        $payment_id = isset( $_GET['payment_id'] ) ? (int) $_GET['payment_id'] : 0;
        $payment    = TPS_Payments_Model::get( $payment_id );

        if ( ! $payment ) {
            wp_die( 'Recebimento nao encontrado.' );
        }

        $autoload = TPS_PLUGIN_PATH . 'vendor/autoload.php';
        if ( ! file_exists( $autoload ) ) {
            wp_die( 'Biblioteca de PDF nao instalada.' );
        }

        require_once $autoload;

        $settings = get_option( 'tps_settings', array() );
        $company  = isset( $settings['company_name'] ) ? $settings['company_name'] : '';
        $nuit     = isset( $settings['company_nuit'] ) ? $settings['company_nuit'] : '';
        $methods  = TPS_Payments_Model::methods();
        $mpdf     = new \Mpdf\Mpdf();
        $css_path = tps_get_asset_path( 'assets/css/document-pdf.css' );

        $html  = '<h2>' . esc_html( $company ) . '</h2>';
        $html .= '<div>NUIT: ' . esc_html( $nuit ) . '</div>';
        $html .= '<hr>';
        $html .= '<h3>Recibo #' . esc_html( $payment->id ) . '</h3>';
        $html .= '<div>Documento: ' . esc_html( strtoupper( (string) $payment->document_type ) ) . ' #' . esc_html( $payment->document_number ) . '</div>';
        $html .= '<div>Cliente: ' . esc_html( $payment->customer_name ) . '</div>';
        $html .= '<div>Data do Recebimento: ' . esc_html( $payment->payment_date ) . '</div>';
        $html .= '<div>Metodo: ' . esc_html( isset( $methods[ $payment->method ] ) ? $methods[ $payment->method ] : $payment->method ) . '</div>';
        $html .= '<div>Valor: <strong>' . esc_html( number_format( (float) $payment->amount, 2 ) ) . '</strong></div>';

        if ( ! empty( $payment->reference ) ) {
            $html .= '<div>Referencia: ' . esc_html( $payment->reference ) . '</div>';
        }

        if ( ! empty( $payment->notes ) ) {
            $html .= '<div>Notas: ' . nl2br( esc_html( $payment->notes ) ) . '</div>';
        }

        if ( file_exists( $css_path ) ) {
            $mpdf->WriteHTML( file_get_contents( $css_path ), \Mpdf\HTMLParserMode::HEADER_CSS );
        }

        $mpdf->WriteHTML( $html );
        $mpdf->Output( 'RECIBO-' . (int) $payment->id . '.pdf', 'D' );
        exit;
    }

    // Redireciona com feedback.
    private static function redirect_with_notice( $url, $notice_code, $notice_type = 'info', $extra_args = array() ) {
        wp_safe_redirect(
            esc_url_raw(
                tps_notice_url( $url, $notice_code, $notice_type, $extra_args )
            )
        );
        exit;
    }
}
