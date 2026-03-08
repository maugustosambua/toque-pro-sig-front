<?php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPS_Documents_Model {

    // Nome da tabela
    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'tps_documents';
    }

    // Tipos de documento suportados
    public static function types() {
        return array(
            'invoice'   => 'Fatura',
            'vd'        => 'Venda a Dinheiro',
            'quotation' => 'Cotação',
        );
    }

    // Verifica se tipo é válido
    public static function is_valid_type( $type ) {
        return array_key_exists( $type, self::types() );
    }

    // Retorna documentos com filtros
    public static function get_documents( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'orderby'        => 'number',
            'order'          => 'DESC',
            'per_page'       => 20,
            'offset'         => 0,
            'type'           => null,
            'status'         => null,
            'payment_status' => null,
            'search'         => '',
        );

        $args = wp_parse_args( $args, $defaults );
        $where = array();

        if ( $args['type'] ) {
            $where[] = $wpdb->prepare( 'd.type = %s', $args['type'] );
        }

        if ( $args['status'] ) {
            $where[] = $wpdb->prepare( 'd.status = %s', $args['status'] );
        }

        if ( $args['payment_status'] ) {
            $where[] = $wpdb->prepare( 'd.payment_status = %s', $args['payment_status'] );
        }

        if ( ! empty( $args['search'] ) ) {
            $like = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where[] = $wpdb->prepare(
                '(CAST(d.number AS CHAR) LIKE %s OR d.type LIKE %s OR c.name LIKE %s OR c.nuit LIKE %s)',
                $like,
                $like,
                $like,
                $like
            );
        }

        $allowed = array(
            'number'        => 'd.number',
            'type'          => 'd.type',
            'issue_date'    => 'd.issue_date',
            'due_date'      => 'd.due_date',
            'payment_status'=> 'd.payment_status',
            'balance_due'   => 'd.balance_due',
            'customer_name' => 'c.name',
            'customer_city' => 'c.city',
        );
        $orderby = isset( $allowed[ $args['orderby'] ] ) ? $allowed[ $args['orderby'] ] : 'd.number';
        $order   = $args['order'] === 'ASC' ? 'ASC' : 'DESC';

        $customers_table = TPS_Customers_Model::table();
        $sql = 'SELECT d.*, c.name AS customer_name, c.nuit AS customer_nuit, c.email AS customer_email, c.phone AS customer_phone
                FROM ' . self::table() . ' d
                LEFT JOIN ' . $customers_table . ' c ON c.id = d.customer_id';

        if ( $where ) {
            $sql .= ' WHERE ' . implode( ' AND ', $where );
        }

        $sql .= " ORDER BY {$orderby} {$order}";
        if ( 'd.issue_date' === $orderby ) {
            $sql .= ", d.id {$order}";
        }
        $sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $args['per_page'], $args['offset'] );

        return $wpdb->get_results( $sql );
    }

    // Conta documentos com filtros
    public static function count_documents( $args = array() ) {
        global $wpdb;

        $where = array();

        if ( ! empty( $args['type'] ) ) {
            $where[] = $wpdb->prepare( 'd.type = %s', $args['type'] );
        }

        if ( ! empty( $args['status'] ) ) {
            $where[] = $wpdb->prepare( 'd.status = %s', $args['status'] );
        }

        if ( ! empty( $args['payment_status'] ) ) {
            $where[] = $wpdb->prepare( 'd.payment_status = %s', $args['payment_status'] );
        }

        $join = '';
        if ( ! empty( $args['search'] ) ) {
            $like = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $join = ' LEFT JOIN ' . TPS_Customers_Model::table() . ' c ON c.id = d.customer_id';
            $where[] = $wpdb->prepare(
                '(CAST(d.number AS CHAR) LIKE %s OR d.type LIKE %s OR c.name LIKE %s OR c.nuit LIKE %s)',
                $like,
                $like,
                $like,
                $like
            );
        }

        $sql = 'SELECT COUNT(*) FROM ' . self::table() . ' d' . $join;

        if ( $where ) {
            $sql .= ' WHERE ' . implode( ' AND ', $where );
        }

        return (int) $wpdb->get_var( $sql );
    }

    // Insere documento em draft
    public static function insert( $data ) {
        global $wpdb;

        return $wpdb->insert(
            self::table(),
            array(
                'type'        => $data['type'],
                'number'      => $data['number'],
                'customer_id' => $data['customer_id'],
                'status'      => 'draft',
                'issue_date'  => $data['issue_date'],
                'due_date'    => $data['due_date'],
                'payment_status' => 'pending',
                'paid_total'  => 0,
                'balance_due' => 0,
            ),
            array(
                '%s',
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%f',
                '%f',
            )
        );
    }

    // Retorna próximo número provisório
    public static function next_number_preview( $type ) {
        global $wpdb;

        $last = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT MAX(number) FROM " . self::table() . " WHERE type = %s",
                $type
            )
        );

        return (int) $last + 1;
    }

    // Estados suportados
    public static function statuses() {
        return array(
            'draft'     => 'Rascunho',
            'issued'    => 'Emitido',
            'cancelled' => 'Cancelado',
        );
    }

    // Estados financeiros suportados.
    public static function payment_statuses() {
        return array(
            'pending'   => 'Pendente',
            'partial'   => 'Parcial',
            'paid'      => 'Pago',
            'overdue'   => 'Vencido',
            'cancelled' => 'Cancelado',
        );
    }

    // Retorna um documento
    public static function get( $id ) {
        global $wpdb;
        $customers_table = TPS_Customers_Model::table();

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT d.*, c.name AS customer_name, c.nuit AS customer_nuit, c.email AS customer_email, c.phone AS customer_phone, c.address AS customer_address, c.city AS customer_city
                FROM " . self::table() . " d
                LEFT JOIN {$customers_table} c ON c.id = d.customer_id
                WHERE d.id = %d",
                $id
            )
        );
    }

    // Emite documento
    public static function issue( $document_id ) {
        global $wpdb;

        $updated = $wpdb->update(
            self::table(),
            array(
                'status' => 'issued',
            ),
            array(
                'id' => (int) $document_id,
            ),
            array(
                '%s',
            ),
            array(
                '%d',
            )
        );

        self::sync_payment_totals( $document_id );

        return $updated;
    }

    // Actualiza número do documento
    public static function update_number( $document_id, $number ) {
        global $wpdb;

        return $wpdb->update(
            self::table(),
            array(
                'number' => (int) $number,
            ),
            array(
                'id' => (int) $document_id,
            ),
            array(
                '%d',
            ),
            array(
                '%d',
            )
        );
    }

    // Conta documentos por status
    public static function count_by_status() {
        global $wpdb;

        $table = self::table();

        $all = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

        $draft = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE status = %s",
            'draft'
        ) );

        $issued = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE status = %s",
            'issued'
        ) );

        $cancelled = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE status = %s",
            'cancelled'
        ) );

        return array(
            'all'       => $all,
            'draft'     => $draft,
            'issued'    => $issued,
            'cancelled' => $cancelled,
        );
    }

    // Cancela documento
    public static function cancel( $document_id ) {
        global $wpdb;

        $updated = $wpdb->update(
            self::table(),
            array(
                'status' => 'cancelled',
            ),
            array(
                'id' => (int) $document_id,
            ),
            array(
                '%s',
            ),
            array(
                '%d',
            )
        );

        self::sync_payment_totals( $document_id );

        return $updated;
    }

    // Recalcula totais pagos, saldo e estado financeiro.
    public static function sync_payment_totals( $document_id ) {
        global $wpdb;

        $document_id = (int) $document_id;
        if ( $document_id <= 0 ) {
            return false;
        }

        $document = self::get( $document_id );
        if ( ! $document ) {
            return false;
        }

        $totals         = TPS_Document_Lines_Model::totals( $document_id );
        $document_total = (float) $totals['total'];
        $paid_total     = class_exists( 'TPS_Payments_Model' ) ? (float) TPS_Payments_Model::paid_total_by_document( $document_id ) : 0.0;
        $balance_due    = 'issued' === $document->status ? max( 0, $document_total - $paid_total ) : 0.0;
        $payment_status = self::resolve_payment_status( $document, $paid_total, $balance_due, $document_total );

        return $wpdb->update(
            self::table(),
            array(
                'payment_status' => $payment_status,
                'paid_total'     => $paid_total,
                'balance_due'    => $balance_due,
            ),
            array(
                'id' => $document_id,
            ),
            array(
                '%s',
                '%f',
                '%f',
            ),
            array(
                '%d',
            )
        );
    }

    // Resolve o estado financeiro a partir do saldo e vencimento.
    private static function resolve_payment_status( $document, $paid_total, $balance_due, $document_total ) {
        if ( 'cancelled' === $document->status ) {
            return 'cancelled';
        }

        if ( 'issued' !== $document->status ) {
            return 'pending';
        }

        if ( $document_total <= 0 && $paid_total <= 0 ) {
            return 'pending';
        }

        if ( $balance_due <= 0.009 ) {
            return 'paid';
        }

        $today = wp_date( 'Y-m-d' );
        if ( ! empty( $document->due_date ) && $document->due_date < $today ) {
            return 'overdue';
        }

        if ( $paid_total > 0 ) {
            return 'partial';
        }

        return 'pending';
    }


}
