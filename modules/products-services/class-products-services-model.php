<?php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPS_Products_Services_Model {

    // Nome da tabela
    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'tps_products_services';
    }

    // Monta WHERE para filtros
    private static function build_where( $args ) {
        global $wpdb;

        $where = array();

        if ( ! empty( $args['type'] ) ) {
            $where[] = $wpdb->prepare( 'type = %s', $args['type'] );
        }

        if ( ! empty( $args['search'] ) ) {
            $like = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where[] = $wpdb->prepare(
                '(name LIKE %s OR sku LIKE %s OR description LIKE %s)',
                $like,
                $like,
                $like
            );
        }

        if ( array_key_exists( 'track_stock', $args ) && '' !== $args['track_stock'] && null !== $args['track_stock'] ) {
            $where[] = $wpdb->prepare( 'track_stock = %d', (int) $args['track_stock'] ? 1 : 0 );
        }

        if ( ! empty( $args['critical_only'] ) ) {
            $where[] = 'type = "product"';
            $where[] = 'track_stock = 1';
            $where[] = 'stock_qty <= min_stock';
        }

        if ( empty( $where ) ) {
            return '';
        }

        return ' WHERE ' . implode( ' AND ', $where );
    }

    // Monta ORDER BY seguro
    private static function build_order( $args ) {
        $allowed = array( 'name', 'type', 'price', 'created_at', 'stock_qty', 'min_stock', 'cost_price' );

        $orderby = ! empty( $args['orderby'] ) && in_array( $args['orderby'], $allowed, true )
            ? $args['orderby']
            : 'name';

        $order = ( ! empty( $args['order'] ) && 'DESC' === strtoupper( $args['order'] ) )
            ? 'DESC'
            : 'ASC';

        if ( 'created_at' === $orderby ) {
            return " ORDER BY {$orderby} {$order}, id {$order}";
        }

        return " ORDER BY {$orderby} {$order}";
    }

    // Insere item
    public static function insert( $data ) {
        global $wpdb;

        $inserted = $wpdb->insert(
            self::table(),
            array(
                'type'        => $data['type'],
                'name'        => $data['name'],
                'sku'         => $data['sku'],
                'unit'        => $data['unit'],
                'price'       => $data['price'],
                'track_stock' => ! empty( $data['track_stock'] ) ? 1 : 0,
                'min_stock'   => isset( $data['min_stock'] ) ? $data['min_stock'] : 0,
                'stock_qty'   => isset( $data['stock_qty'] ) ? $data['stock_qty'] : 0,
                'cost_price'  => isset( $data['cost_price'] ) ? $data['cost_price'] : 0,
                'description' => $data['description'],
                'created_at'  => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%f', '%d', '%f', '%f', '%f', '%s', '%s' )
        );

        if ( false !== $inserted ) {
            $item_id = (int) $wpdb->insert_id;
            $after   = self::get( $item_id );
            tps_audit_log( 'product_service_created', 'product_service', $item_id, null, $after );
        }

        return $inserted;
    }

    // Actualiza item
    public static function update( $id, $data ) {
        global $wpdb;

        $id     = (int) $id;
        $before = self::get( $id );
        $skip_audit = ! empty( $data['_skip_audit'] );

        $updated = $wpdb->update(
            self::table(),
            array(
                'type'        => $data['type'],
                'name'        => $data['name'],
                'sku'         => $data['sku'],
                'unit'        => $data['unit'],
                'price'       => $data['price'],
                'track_stock' => ! empty( $data['track_stock'] ) ? 1 : 0,
                'min_stock'   => isset( $data['min_stock'] ) ? $data['min_stock'] : 0,
                'stock_qty'   => isset( $data['stock_qty'] ) ? $data['stock_qty'] : 0,
                'cost_price'  => isset( $data['cost_price'] ) ? $data['cost_price'] : 0,
                'description' => $data['description'],
                'updated_at'  => current_time( 'mysql' ),
            ),
            array( 'id' => $id ),
            array( '%s', '%s', '%s', '%s', '%f', '%d', '%f', '%f', '%f', '%s', '%s' ),
            array( '%d' )
        );

        if ( false !== $updated && ! $skip_audit ) {
            $after = self::get( $id );
            tps_audit_log( 'product_service_updated', 'product_service', $id, $before, $after );
        }

        return $updated;
    }

    // Remove item
    public static function delete( $id ) {
        global $wpdb;

        $id     = (int) $id;
        $before = self::get( $id );

        $deleted = $wpdb->delete(
            self::table(),
            array( 'id' => $id ),
            array( '%d' )
        );

        if ( false !== $deleted && null !== $before ) {
            tps_audit_log( 'product_service_deleted', 'product_service', $id, $before, null );
        }

        return $deleted;
    }

    // Retorna item por ID
    public static function get( $id ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . self::table() . ' WHERE id = %d',
                (int) $id
            )
        );
    }

    // Retorna itens com paginação/filtros
    public static function get_items( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'type'     => '',
            'search'   => '',
            'track_stock' => null,
            'critical_only' => false,
            'orderby'  => 'name',
            'order'    => 'ASC',
            'per_page' => 0,
            'offset'   => 0,
        );

        $args = wp_parse_args( $args, $defaults );

        $per_page = (int) $args['per_page'];
        $offset   = (int) $args['offset'];

        $sql  = 'SELECT * FROM ' . self::table();
        $sql .= self::build_where( $args );
        $sql .= self::build_order( $args );

        if ( $per_page > 0 ) {
            $sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $per_page, $offset );
        }

        return $wpdb->get_results( $sql );
    }

    // Conta itens com filtros
    public static function count_items( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'type'   => '',
            'search' => '',
            'track_stock' => null,
            'critical_only' => false,
        );

        $args = wp_parse_args( $args, $defaults );

        $sql  = 'SELECT COUNT(*) FROM ' . self::table();
        $sql .= self::build_where( $args );

        return (int) $wpdb->get_var( $sql );
    }

    // Conta produtos com stock critico.
    public static function count_critical_products() {
        return self::count_items(
            array(
                'critical_only' => true,
            )
        );
    }
}
