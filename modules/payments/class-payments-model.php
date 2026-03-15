<?php
// Impede acesso directo.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPS_Payments_Model {

    // Nome da tabela principal.
    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'tps_payments';
    }

    // Nome da tabela de alocacoes.
    public static function allocations_table() {
        global $wpdb;
        return $wpdb->prefix . 'tps_payment_allocations';
    }

    // Metodos suportados.
    public static function methods() {
        return array(
            'cash'          => 'Numerario',
            'bank_transfer' => 'Transferencia',
            'card'          => 'Cartao',
            'mobile_money'  => 'Mobile Money',
            'check'         => 'Cheque',
        );
    }

    // Regista um pagamento e respetiva alocacao.
    public static function record_payment( $data ) {
        global $wpdb;

        $document_id      = (int) $data['document_id'];
        $document_before  = TPS_Documents_Model::get( $document_id );

        $wpdb->query( 'START TRANSACTION' );

        $inserted = $wpdb->insert(
            self::table(),
            array(
                'document_id'  => (int) $data['document_id'],
                'customer_id'  => (int) $data['customer_id'],
                'payment_date' => $data['payment_date'],
                'amount'       => (float) $data['amount'],
                'method'       => $data['method'],
                'reference'    => $data['reference'],
                'notes'        => $data['notes'],
            ),
            array(
                '%d',
                '%d',
                '%s',
                '%f',
                '%s',
                '%s',
                '%s',
            )
        );

        if ( false === $inserted ) {
            $wpdb->query( 'ROLLBACK' );
            return false;
        }

        $payment_id = (int) $wpdb->insert_id;

        $allocated = $wpdb->insert(
            self::allocations_table(),
            array(
                'payment_id'       => $payment_id,
                'document_id'      => (int) $data['document_id'],
                'allocated_amount' => (float) $data['amount'],
            ),
            array(
                '%d',
                '%d',
                '%f',
            )
        );

        if ( false === $allocated ) {
            $wpdb->query( 'ROLLBACK' );
            return false;
        }

        TPS_Documents_Model::sync_payment_totals( $document_id );

        $document_after = TPS_Documents_Model::get( $document_id );

        tps_audit_log(
            'payment_recorded',
            'payment',
            $payment_id,
            null,
            array(
                'payment_id'    => $payment_id,
                'document_id'   => $document_id,
                'customer_id'   => (int) $data['customer_id'],
                'payment_date'  => (string) $data['payment_date'],
                'amount'        => (float) $data['amount'],
                'method'        => (string) $data['method'],
                'reference'     => (string) $data['reference'],
                'notes'         => (string) $data['notes'],
            ),
            array(
                'document_before' => $document_before,
                'document_after'  => $document_after,
            )
        );

        $wpdb->query( 'COMMIT' );

        return $payment_id;
    }

    // Total recebido por documento.
    public static function paid_total_by_document( $document_id ) {
        global $wpdb;

        $sql = $wpdb->prepare(
            'SELECT COALESCE(SUM(allocated_amount), 0) FROM ' . self::allocations_table() . ' WHERE document_id = %d',
            (int) $document_id
        );

        return (float) $wpdb->get_var( $sql );
    }

    // Historico de recebimentos por documento.
    public static function get_payment_history_by_document( $document_id ) {
        global $wpdb;

        $sql = $wpdb->prepare(
            'SELECT p.*
             FROM ' . self::table() . ' p
             INNER JOIN ' . self::allocations_table() . ' a ON a.payment_id = p.id
             WHERE a.document_id = %d
             ORDER BY p.payment_date DESC, p.id DESC',
            (int) $document_id
        );

        return $wpdb->get_results( $sql );
    }

    // Lista de recebimentos.
    public static function get_payments( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'per_page' => 50,
            'offset'   => 0,
        );
        $args      = wp_parse_args( $args, $defaults );

        $docs_table      = TPS_Documents_Model::table();
        $customers_table = TPS_Customers_Model::table();

        $sql = $wpdb->prepare(
            "SELECT p.*, d.number AS document_number, d.type AS document_type, c.name AS customer_name
             FROM " . self::table() . " p
             LEFT JOIN {$docs_table} d ON d.id = p.document_id
             LEFT JOIN {$customers_table} c ON c.id = p.customer_id
             ORDER BY p.payment_date DESC, p.id DESC
             LIMIT %d OFFSET %d",
            (int) $args['per_page'],
            (int) $args['offset']
        );

        return $wpdb->get_results( $sql );
    }

    // Obtem um recebimento.
    public static function get( $payment_id ) {
        global $wpdb;

        $docs_table      = TPS_Documents_Model::table();
        $customers_table = TPS_Customers_Model::table();

        $sql = $wpdb->prepare(
            "SELECT p.*, d.number AS document_number, d.type AS document_type, d.issue_date, d.due_date,
                    c.name AS customer_name, c.nuit AS customer_nuit
             FROM " . self::table() . " p
             LEFT JOIN {$docs_table} d ON d.id = p.document_id
             LEFT JOIN {$customers_table} c ON c.id = p.customer_id
             WHERE p.id = %d",
            (int) $payment_id
        );

        return $wpdb->get_row( $sql );
    }

    // Lista de documentos por receber.
    public static function get_accounts_receivable( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'per_page' => 100,
            'offset'   => 0,
        );
        $args      = wp_parse_args( $args, $defaults );

        $docs_table      = TPS_Documents_Model::table();
        $customers_table = TPS_Customers_Model::table();

        $sql = $wpdb->prepare(
            "SELECT d.*, c.name AS customer_name
             FROM {$docs_table} d
             LEFT JOIN {$customers_table} c ON c.id = d.customer_id
             WHERE d.status = %s
               AND d.balance_due > 0
             ORDER BY
                CASE WHEN d.payment_status = 'overdue' THEN 0 ELSE 1 END ASC,
                d.due_date ASC,
                d.id DESC
             LIMIT %d OFFSET %d",
            'issued',
            (int) $args['per_page'],
            (int) $args['offset']
        );

        return $wpdb->get_results( $sql );
    }
}
