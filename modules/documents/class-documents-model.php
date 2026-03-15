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

    // Nome da tabela de eventos fiscais.
    public static function fiscal_events_table() {
        global $wpdb;
        return $wpdb->prefix . 'tps_fiscal_events';
    }

    // Nome da tabela de snapshots fiscais.
    public static function fiscal_snapshots_table() {
        global $wpdb;
        return $wpdb->prefix . 'tps_fiscal_snapshots';
    }

    // Nome da tabela de fechos fiscais mensais.
    public static function fiscal_monthly_closures_table() {
        global $wpdb;
        return $wpdb->prefix . 'tps_fiscal_monthly_closures';
    }

    // Tipos de documento suportados
    public static function types() {
        return array(
            'invoice'   => 'Fatura',
            'vd'        => 'Venda a Dinheiro',
            'quotation' => 'Cotação',
            'credit_note' => 'Nota de Crédito',
            'debit_note'  => 'Nota de Débito',
        );
    }

    // Tipos fiscais de ajuste que exigem vínculo ao documento original.
    public static function adjustment_types() {
        return array( 'credit_note', 'debit_note' );
    }

    // Verifica se tipo é nota de ajuste.
    public static function is_adjustment_type( $type ) {
        return in_array( (string) $type, self::adjustment_types(), true );
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

        $original_document_id = isset( $data['original_document_id'] ) ? (int) $data['original_document_id'] : null;
        if ( $original_document_id <= 0 ) {
            $original_document_id = null;
        }

        $adjustment_reason = isset( $data['adjustment_reason'] ) ? sanitize_textarea_field( (string) $data['adjustment_reason'] ) : '';
        if ( '' === $adjustment_reason ) {
            $adjustment_reason = null;
        }

        $inserted = $wpdb->insert(
            self::table(),
            array(
                'type'        => $data['type'],
                'number'      => $data['number'],
                'customer_id' => $data['customer_id'],
                'original_document_id' => $original_document_id,
                'adjustment_reason'    => $adjustment_reason,
                'status'      => 'draft',
                'issue_date'  => $data['issue_date'],
                'due_date'    => $data['due_date'],
                'withholding_rate'   => 0,
                'withholding_amount' => 0,
                'payment_status' => 'pending',
                'paid_total'  => 0,
                'balance_due' => 0,
            ),
            array(
                '%s',
                '%d',
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%f',
                '%f',
                '%s',
                '%f',
                '%f',
            )
        );

        if ( false !== $inserted ) {
            $document_id = (int) $wpdb->insert_id;
            $after       = self::get( $document_id );
            tps_audit_log( 'document_draft_created', 'document', $document_id, null, $after );
        }

        return $inserted;
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
                "SELECT d.*, c.name AS customer_name, c.nuit AS customer_nuit, c.email AS customer_email, c.phone AS customer_phone, c.address AS customer_address, c.city AS customer_city,
                        od.number AS original_document_number, od.type AS original_document_type, od.status AS original_document_status
                FROM " . self::table() . " d
                LEFT JOIN {$customers_table} c ON c.id = d.customer_id
                LEFT JOIN " . self::table() . " od ON od.id = d.original_document_id
                WHERE d.id = %d",
                $id
            )
        );
    }

    // Emite documento
    public static function issue( $document_id ) {
        global $wpdb;

        $document_id = (int) $document_id;
        $before      = self::get( $document_id );

        $updated = $wpdb->update(
            self::table(),
            array(
                'status' => 'issued',
            ),
            array(
                'id' => $document_id,
            ),
            array(
                '%s',
            ),
            array(
                '%d',
            )
        );

        self::sync_payment_totals( $document_id );

        if ( false !== $updated ) {
            self::seal_document_hash( $document_id );
            self::create_fiscal_snapshot( $document_id, 'issued' );

            $after = self::get( $document_id );
            tps_audit_log( 'document_issued', 'document', $document_id, $before, $after );
        }

        return $updated;
    }

    // Actualiza número do documento.
    public static function update_number( $document_id, $number ) {
        global $wpdb;

        $document_id = (int) $document_id;
        $before      = self::get( $document_id );

        $updated = $wpdb->update(
            self::table(),
            array(
                'number' => (int) $number,
            ),
            array(
                'id' => $document_id,
            ),
            array(
                '%d',
            ),
            array(
                '%d',
            )
        );

        if ( false !== $updated ) {
            $after = self::get( $document_id );
            tps_audit_log( 'document_number_updated', 'document', $document_id, $before, $after );
        }

        return $updated;
    }

    // Actualiza taxa de retencao em rascunho.
    public static function update_withholding_rate( $document_id, $rate ) {
        global $wpdb;

        $document_id = (int) $document_id;
        $rate        = max( 0, min( 100, (float) $rate ) );
        $document    = self::get( $document_id );

        if ( ! $document || 'draft' !== $document->status ) {
            return false;
        }

        $updated = $wpdb->update(
            self::table(),
            array(
                'withholding_rate' => $rate,
            ),
            array(
                'id' => $document_id,
            ),
            array(
                '%f',
            ),
            array(
                '%d',
            )
        );

        if ( false === $updated ) {
            return false;
        }

        self::sync_payment_totals( $document_id );

        $after = self::get( $document_id );

        self::log_fiscal_event(
            $document_id,
            'withholding_updated',
            array(
                'withholding_rate' => $rate,
            )
        );

        tps_audit_log( 'document_withholding_updated', 'document', $document_id, $document, $after );

        return true;
    }

    // Define número final apenas para documento em rascunho e sem colisão.
    public static function set_issued_number( $document_id, $number ) {
        $document_id = (int) $document_id;
        $number      = (int) $number;

        if ( $document_id <= 0 || $number <= 0 ) {
            return false;
        }

        $document = self::get( $document_id );
        if ( ! $document || 'draft' !== $document->status ) {
            return false;
        }

        if ( self::number_exists_for_type( $document->type, $number, $document_id ) ) {
            return false;
        }

        return false !== self::update_number( $document_id, $number );
    }

    // Verifica colisao de numero para tipo do documento.
    public static function number_exists_for_type( $type, $number, $exclude_document_id = 0 ) {
        global $wpdb;

        $exclude_document_id = (int) $exclude_document_id;

        $sql = 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE type = %s AND number = %d AND status IN (%s, %s)';
        if ( $exclude_document_id > 0 ) {
            $sql .= ' AND id <> %d';

            $count = (int) $wpdb->get_var(
                $wpdb->prepare(
                    $sql,
                    $type,
                    (int) $number,
                    'issued',
                    'cancelled',
                    $exclude_document_id
                )
            );

            return $count > 0;
        }

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                $sql,
                $type,
                (int) $number,
                'issued',
                'cancelled'
            )
        );

        return $count > 0;
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

    // Cancela documento.
    public static function cancel( $document_id, $reason = '', $user_id = 0 ) {
        global $wpdb;

        $document_id = (int) $document_id;
        $reason      = sanitize_textarea_field( (string) $reason );
        $user_id     = (int) $user_id;
        $before      = self::get( $document_id );

        $updated = $wpdb->update(
            self::table(),
            array(
                'status'        => 'cancelled',
                'cancel_reason' => $reason,
                'cancelled_at'  => current_time( 'mysql' ),
                'cancelled_by'  => $user_id > 0 ? $user_id : null,
            ),
            array(
                'id' => $document_id,
            ),
            array(
                '%s',
                '%s',
                '%s',
                '%d',
            ),
            array(
                '%d',
            )
        );

        self::sync_payment_totals( $document_id );

        if ( false !== $updated ) {
            self::seal_document_hash( $document_id );
            self::log_fiscal_event(
                $document_id,
                'document_cancelled',
                array(
                    'reason' => $reason,
                ),
                $user_id
            );

            $after = self::get( $document_id );
            tps_audit_log(
                'document_cancelled',
                'document',
                $document_id,
                $before,
                $after,
                array(
                    'reason' => $reason,
                )
            );
        }

        return $updated;
    }

    // Retorna totais fiscais e valor líquido a pagar.
    public static function fiscal_totals( $document_id ) {
        $document = self::get( $document_id );
        $totals   = TPS_Document_Lines_Model::totals( $document_id );

        $withholding_rate = $document ? max( 0, min( 100, (float) $document->withholding_rate ) ) : 0;
        $withholding      = (float) $totals['subtotal'] * ( $withholding_rate / 100 );
        $payable_total    = max( 0, (float) $totals['total'] - $withholding );

        return array(
            'subtotal'           => (float) $totals['subtotal'],
            'iva'                => (float) $totals['iva'],
            'total'              => (float) $totals['total'],
            'withholding_rate'   => $withholding_rate,
            'withholding_amount' => $withholding,
            'payable_total'      => $payable_total,
        );
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

        $totals         = self::fiscal_totals( $document_id );
        $document_total = (float) $totals['payable_total'];
        $paid_total     = class_exists( 'TPS_Payments_Model' ) ? (float) TPS_Payments_Model::paid_total_by_document( $document_id ) : 0.0;
        $balance_due    = 'issued' === $document->status ? max( 0, $document_total - $paid_total ) : 0.0;
        $payment_status = self::resolve_payment_status( $document, $paid_total, $balance_due, $document_total );

        return $wpdb->update(
            self::table(),
            array(
                'withholding_amount' => (float) $totals['withholding_amount'],
                'payment_status' => $payment_status,
                'paid_total'     => $paid_total,
                'balance_due'    => $balance_due,
            ),
            array(
                'id' => $document_id,
            ),
            array(
                '%f',
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

    // Regista evento fiscal para auditoria.
    public static function log_fiscal_event( $document_id, $event_type, $payload = array(), $user_id = 0 ) {
        global $wpdb;

        $document_id = (int) $document_id;
        if ( $document_id <= 0 ) {
            return false;
        }

        $event_type       = sanitize_key( (string) $event_type );
        $payload_json     = wp_json_encode( $payload );
        $created_at       = current_time( 'mysql' );
        $prev_event_hash  = self::get_last_event_hash();
        $event_hash_input = implode(
            '|',
            array(
                (string) $document_id,
                $event_type,
                (string) $payload_json,
                (string) ( (int) $user_id > 0 ? (int) $user_id : 0 ),
                $created_at,
                (string) $prev_event_hash,
            )
        );
        $event_hash = hash( 'sha256', $event_hash_input );

        return false !== $wpdb->insert(
            self::fiscal_events_table(),
            array(
                'document_id' => $document_id,
                'event_type'  => $event_type,
                'payload'     => $payload_json,
                'user_id'     => (int) $user_id > 0 ? (int) $user_id : null,
                'prev_event_hash' => $prev_event_hash,
                'event_hash'      => $event_hash,
                'created_at'      => $created_at,
            ),
            array(
                '%d',
                '%s',
                '%s',
                '%d',
                '%s',
                '%s',
                '%s',
            )
        );
    }

    // Obtém último hash da cadeia de eventos fiscais.
    private static function get_last_event_hash() {
        global $wpdb;

        $hash = $wpdb->get_var( 'SELECT event_hash FROM ' . self::fiscal_events_table() . ' WHERE event_hash IS NOT NULL AND event_hash <> "" ORDER BY id DESC LIMIT 1' );

        return is_string( $hash ) ? $hash : '';
    }

    // Gera hash encadeado do documento no momento fiscal (emissão).
    public static function seal_document_hash( $document_id ) {
        global $wpdb;

        $document_id = (int) $document_id;
        if ( $document_id <= 0 ) {
            return false;
        }

        $document = self::get( $document_id );
        if ( ! $document ) {
            return false;
        }

        if ( ! empty( $document->fiscal_hash ) ) {
            return true;
        }

        if ( ! in_array( (string) $document->status, array( 'issued', 'cancelled' ), true ) ) {
            return false;
        }

        $prev_hash   = self::get_last_document_hash( $document_id );
        $lines       = TPS_Document_Lines_Model::get_by_document( $document_id );
        $lines_fingerprint = array();

        foreach ( $lines as $line ) {
            $lines_fingerprint[] = array(
                'id'              => (int) $line->id,
                'description'     => (string) $line->description,
                'quantity'        => (float) $line->quantity,
                'unit_price'      => (float) $line->unit_price,
                'tax_mode'        => isset( $line->tax_mode ) ? (string) $line->tax_mode : 'taxable',
                'tax_rate'        => isset( $line->tax_rate ) ? (float) $line->tax_rate : 0,
                'exemption_code'  => isset( $line->exemption_code ) ? (string) $line->exemption_code : '',
                'exemption_reason'=> isset( $line->exemption_reason ) ? (string) $line->exemption_reason : '',
            );
        }

        $totals = self::fiscal_totals( $document_id );
        $hashed_at = current_time( 'mysql' );
        $hash_payload = array(
            'document_id'          => $document_id,
            'type'                 => (string) $document->type,
            'number'               => (int) $document->number,
            'status'               => (string) $document->status,
            'customer_id'          => (int) $document->customer_id,
            'issue_date'           => (string) $document->issue_date,
            'due_date'             => (string) $document->due_date,
            'original_document_id' => isset( $document->original_document_id ) ? (int) $document->original_document_id : 0,
            'totals'               => array(
                'subtotal'           => number_format( (float) $totals['subtotal'], 2, '.', '' ),
                'iva'                => number_format( (float) $totals['iva'], 2, '.', '' ),
                'withholding_amount' => number_format( (float) $totals['withholding_amount'], 2, '.', '' ),
                'payable_total'      => number_format( (float) $totals['payable_total'], 2, '.', '' ),
            ),
            'lines'                => $lines_fingerprint,
            'prev_hash'            => $prev_hash,
            'hashed_at'            => $hashed_at,
        );

        $fiscal_hash = hash( 'sha256', wp_json_encode( $hash_payload ) );

        return false !== $wpdb->update(
            self::table(),
            array(
                'fiscal_prev_hash' => $prev_hash,
                'fiscal_hash'      => $fiscal_hash,
                'fiscal_hashed_at' => $hashed_at,
            ),
            array(
                'id' => $document_id,
            ),
            array(
                '%s',
                '%s',
                '%s',
            ),
            array(
                '%d',
            )
        );
    }

    // Obtém último hash da cadeia de documentos fiscais.
    private static function get_last_document_hash( $exclude_document_id = 0 ) {
        global $wpdb;

        $exclude_document_id = (int) $exclude_document_id;

        $sql = 'SELECT fiscal_hash FROM ' . self::table() . ' WHERE fiscal_hash IS NOT NULL AND fiscal_hash <> ""';
        if ( $exclude_document_id > 0 ) {
            $sql .= $wpdb->prepare( ' AND id <> %d', $exclude_document_id );
        }
        $sql .= ' ORDER BY id DESC LIMIT 1';

        $hash = $wpdb->get_var( $sql );

        return is_string( $hash ) ? $hash : '';
    }

    // Cria snapshot fiscal imutável do documento (ex.: emissão).
    public static function create_fiscal_snapshot( $document_id, $snapshot_type = 'issued', $created_by = 0 ) {
        global $wpdb;

        $document_id = (int) $document_id;
        if ( $document_id <= 0 ) {
            return false;
        }

        $document = self::get( $document_id );
        if ( ! $document ) {
            return false;
        }

        $snapshot_type = sanitize_key( (string) $snapshot_type );
        if ( '' === $snapshot_type ) {
            $snapshot_type = 'issued';
        }

        $totals = self::fiscal_totals( $document_id );
        $lines  = TPS_Document_Lines_Model::get_by_document( $document_id );
        $lines_payload = array();

        foreach ( $lines as $line ) {
            $lines_payload[] = array(
                'id'              => (int) $line->id,
                'description'     => (string) $line->description,
                'quantity'        => (float) $line->quantity,
                'unit_price'      => (float) $line->unit_price,
                'tax_mode'        => isset( $line->tax_mode ) ? (string) $line->tax_mode : 'taxable',
                'tax_rate'        => isset( $line->tax_rate ) ? (float) $line->tax_rate : (float) ( tps_get_iva_rate() * 100 ),
                'exemption_code'  => isset( $line->exemption_code ) ? (string) $line->exemption_code : '',
                'exemption_reason'=> isset( $line->exemption_reason ) ? (string) $line->exemption_reason : '',
                'line_subtotal'   => number_format( TPS_Document_Lines_Model::line_total( $line ), 2, '.', '' ),
                'line_iva'        => number_format( TPS_Document_Lines_Model::line_iva( $line ), 2, '.', '' ),
            );
        }

        $payload = array(
            'document_id'          => $document_id,
            'snapshot_type'        => $snapshot_type,
            'type'                 => (string) $document->type,
            'number'               => (int) $document->number,
            'status'               => (string) $document->status,
            'issue_date'           => (string) $document->issue_date,
            'due_date'             => (string) $document->due_date,
            'customer_id'          => (int) $document->customer_id,
            'customer_nuit'        => (string) ( $document->customer_nuit ?? '' ),
            'original_document_id' => isset( $document->original_document_id ) ? (int) $document->original_document_id : 0,
            'totals'               => array(
                'subtotal'           => number_format( (float) $totals['subtotal'], 2, '.', '' ),
                'iva'                => number_format( (float) $totals['iva'], 2, '.', '' ),
                'total'              => number_format( (float) $totals['total'], 2, '.', '' ),
                'withholding_rate'   => number_format( (float) $totals['withholding_rate'], 2, '.', '' ),
                'withholding_amount' => number_format( (float) $totals['withholding_amount'], 2, '.', '' ),
                'payable_total'      => number_format( (float) $totals['payable_total'], 2, '.', '' ),
            ),
            'lines'                => $lines_payload,
            'fiscal_prev_hash'     => (string) ( $document->fiscal_prev_hash ?? '' ),
            'fiscal_hash'          => (string) ( $document->fiscal_hash ?? '' ),
        );

        $payload_json = wp_json_encode( $payload );
        $prev_hash    = self::get_last_snapshot_hash();
        $snapshot_hash = hash( 'sha256', $payload_json . '|' . $prev_hash );

        return false !== $wpdb->insert(
            self::fiscal_snapshots_table(),
            array(
                'document_id'         => $document_id,
                'snapshot_type'       => $snapshot_type,
                'prev_snapshot_hash'  => $prev_hash,
                'snapshot_hash'       => $snapshot_hash,
                'payload'             => $payload_json,
                'created_by'          => (int) $created_by > 0 ? (int) $created_by : null,
                'created_at'          => current_time( 'mysql' ),
            ),
            array(
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%d',
                '%s',
            )
        );
    }

    // Obtém último hash da cadeia de snapshots fiscais.
    private static function get_last_snapshot_hash() {
        global $wpdb;

        $hash = $wpdb->get_var( 'SELECT snapshot_hash FROM ' . self::fiscal_snapshots_table() . ' WHERE snapshot_hash IS NOT NULL AND snapshot_hash <> "" ORDER BY id DESC LIMIT 1' );

        return is_string( $hash ) ? $hash : '';
    }

    // Fecha um período fiscal mensal (YYYY-MM) com consolidação financeira.
    public static function close_fiscal_month( $period_ym, $closed_by = 0 ) {
        global $wpdb;

        $period_ym = trim( (string) $period_ym );
        if ( ! preg_match( '/^\d{4}-\d{2}$/', $period_ym ) ) {
            return false;
        }

        $period_start = $period_ym . '-01';
        $period_end   = wp_date( 'Y-m-t', strtotime( $period_start ) );

        $document_rows = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT id, status FROM ' . self::table() . ' WHERE issue_date BETWEEN %s AND %s AND status IN (%s, %s)',
                $period_start,
                $period_end,
                'issued',
                'cancelled'
            )
        );

        $documents_count = 0;
        $issued_count    = 0;
        $cancelled_count = 0;
        $subtotal        = 0.0;
        $iva             = 0.0;
        $withholding     = 0.0;
        $payable_total   = 0.0;

        foreach ( $document_rows as $row ) {
            $documents_count++;
            if ( 'issued' === (string) $row->status ) {
                $issued_count++;
            }
            if ( 'cancelled' === (string) $row->status ) {
                $cancelled_count++;
            }

            $totals       = self::fiscal_totals( (int) $row->id );
            $subtotal    += (float) $totals['subtotal'];
            $iva         += (float) $totals['iva'];
            $withholding += (float) $totals['withholding_amount'];
            $payable_total += (float) $totals['payable_total'];
        }

        $payments_total = 0.0;
        if ( class_exists( 'TPS_Payments_Model' ) ) {
            $payments_total = (float) $wpdb->get_var(
                $wpdb->prepare(
                    'SELECT COALESCE(SUM(amount), 0) FROM ' . TPS_Payments_Model::table() . ' WHERE payment_date BETWEEN %s AND %s',
                    $period_start,
                    $period_end
                )
            );
        }

        $open_balance_total = (float) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COALESCE(SUM(balance_due), 0) FROM ' . self::table() . ' WHERE status = %s AND issue_date <= %s',
                'issued',
                $period_end
            )
        );

        $payload = array(
            'period_ym'          => $period_ym,
            'period_start'       => $period_start,
            'period_end'         => $period_end,
            'documents_count'    => $documents_count,
            'issued_count'       => $issued_count,
            'cancelled_count'    => $cancelled_count,
            'subtotal'           => (float) $subtotal,
            'iva'                => (float) $iva,
            'withholding_amount' => (float) $withholding,
            'payable_total'      => (float) $payable_total,
            'payments_total'     => (float) $payments_total,
            'open_balance_total' => (float) $open_balance_total,
        );

        $prev_hash = self::get_last_monthly_closure_hash( $period_ym );
        $closed_at = current_time( 'mysql' );
        $hash_input = wp_json_encode(
            array(
                'period'    => $period_ym,
                'payload'   => $payload,
                'prev_hash' => $prev_hash,
                'closed_at' => $closed_at,
            )
        );
        $closure_hash = hash( 'sha256', (string) $hash_input );

        $inserted = $wpdb->replace(
            self::fiscal_monthly_closures_table(),
            array(
                'period_ym'          => $period_ym,
                'period_start'       => $period_start,
                'period_end'         => $period_end,
                'documents_count'    => $documents_count,
                'issued_count'       => $issued_count,
                'cancelled_count'    => $cancelled_count,
                'subtotal'           => (float) $subtotal,
                'iva'                => (float) $iva,
                'withholding_amount' => (float) $withholding,
                'payable_total'      => (float) $payable_total,
                'payments_total'     => (float) $payments_total,
                'open_balance_total' => (float) $open_balance_total,
                'closure_prev_hash'  => $prev_hash,
                'closure_hash'       => $closure_hash,
                'payload'            => wp_json_encode( $payload ),
                'closed_by'          => (int) $closed_by > 0 ? (int) $closed_by : null,
                'closed_at'          => $closed_at,
            ),
            array(
                '%s',
                '%s',
                '%s',
                '%d',
                '%d',
                '%d',
                '%f',
                '%f',
                '%f',
                '%f',
                '%f',
                '%f',
                '%s',
                '%s',
                '%s',
                '%d',
                '%s',
            )
        );

        if ( false === $inserted ) {
            return false;
        }

        return self::get_fiscal_monthly_closure( $period_ym );
    }

    // Obtém fecho fiscal mensal por período.
    public static function get_fiscal_monthly_closure( $period_ym ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . self::fiscal_monthly_closures_table() . ' WHERE period_ym = %s LIMIT 1',
                (string) $period_ym
            )
        );
    }

    // Lista fechos fiscais mensais mais recentes.
    public static function get_fiscal_monthly_closures( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'limit' => 12,
        );
        $args = wp_parse_args( $args, $defaults );

        $limit = max( 1, (int) $args['limit'] );

        $sql = $wpdb->prepare(
            'SELECT * FROM ' . self::fiscal_monthly_closures_table() . ' ORDER BY period_ym DESC LIMIT %d',
            $limit
        );

        return $wpdb->get_results( $sql );
    }

    // Obtém último hash de fecho mensal (excluindo período atual quando necessário).
    private static function get_last_monthly_closure_hash( $exclude_period_ym = '' ) {
        global $wpdb;

        $exclude_period_ym = (string) $exclude_period_ym;
        $sql = 'SELECT closure_hash FROM ' . self::fiscal_monthly_closures_table() . ' WHERE closure_hash IS NOT NULL AND closure_hash <> ""';
        if ( '' !== $exclude_period_ym ) {
            $sql .= $wpdb->prepare( ' AND period_ym <> %s', $exclude_period_ym );
        }
        $sql .= ' ORDER BY id DESC LIMIT 1';

        $hash = $wpdb->get_var( $sql );

        return is_string( $hash ) ? $hash : '';
    }


}
