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
            'invoice'   => 'Invoice',
            'vd'        => 'Sales Receipt',
            'quotation' => 'Quotation',
        );
    }

    // Verifica se tipo é válido
    public static function is_valid_type( $type ) {
        return array_key_exists( $type, self::types() );
    }

    // Retorna label do tipo
    public static function type_label( $type ) {
        $types = self::types();
        return $types[ $type ] ?? '';
    }

    // Retorna documentos com filtros
    public static function get_documents( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'orderby'  => 'number',
            'order'    => 'DESC',
            'per_page' => 20,
            'offset'   => 0,
            'type'     => null,
            'status'   => null,
        );

        $args = wp_parse_args( $args, $defaults );
        $where = array();

        if ( $args['type'] ) {
            $where[] = $wpdb->prepare( 'type = %s', $args['type'] );
        }

        if ( $args['status'] ) {
            $where[] = $wpdb->prepare( 'status = %s', $args['status'] );
        }

        $allowed = array( 'number', 'type', 'issue_date' );
        $orderby = in_array( $args['orderby'], $allowed, true ) ? $args['orderby'] : 'number';
        $order   = $args['order'] === 'ASC' ? 'ASC' : 'DESC';

        $sql = 'SELECT * FROM ' . self::table();

        if ( $where ) {
            $sql .= ' WHERE ' . implode( ' AND ', $where );
        }

        $sql .= " ORDER BY {$orderby} {$order}";
        $sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $args['per_page'], $args['offset'] );

        return $wpdb->get_results( $sql );
    }

    // Conta documentos com filtros
    public static function count_documents( $args = array() ) {
        global $wpdb;

        $where = array();

        if ( ! empty( $args['type'] ) ) {
            $where[] = $wpdb->prepare( 'type = %s', $args['type'] );
        }

        if ( ! empty( $args['status'] ) ) {
            $where[] = $wpdb->prepare( 'status = %s', $args['status'] );
        }

        $sql = 'SELECT COUNT(*) FROM ' . self::table();

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
            ),
            array(
                '%s',
                '%d',
                '%d',
                '%s',
                '%s',
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
            'draft'     => 'Draft',
            'issued'    => 'Issued',
            'cancelled' => 'Cancelled',
        );
    }

    // Retorna um documento
    public static function get( $id ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::table() . " WHERE id = %d",
                $id
            )
        );
    }

    // Emite documento
    public static function issue( $document_id ) {
        global $wpdb;

        return $wpdb->update(
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

        return $wpdb->update(
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
    }


}
