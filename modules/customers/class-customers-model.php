<?php // modules/customers/class-customers-model.php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Model responsável pela tabela de clientes
class TPS_Customers_Model {

    // Retorna o nome completo da tabela
    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'tps_customers';
    }

    // Monta WHERE para filtros e pesquisa
    private static function build_where( $args ) {
        global $wpdb;

        $where = array();

        if ( ! empty( $args['type'] ) ) {
            $where[] = $wpdb->prepare( "type = %s", $args['type'] );
        }

        if ( ! empty( $args['search'] ) ) {
            $like = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where[] = $wpdb->prepare(
                "(name LIKE %s OR nuit LIKE %s OR email LIKE %s OR phone LIKE %s)",
                $like,
                $like,
                $like,
                $like
            );
        }

        if ( empty( $where ) ) {
            return '';
        }

        return ' WHERE ' . implode( ' AND ', $where );
    }

    // Monta ORDER BY seguro
    private static function build_order( $args ) {

        $allowed = array( 'name', 'type', 'created_at' );

        $orderby = ! empty( $args['orderby'] ) && in_array( $args['orderby'], $allowed, true )
            ? $args['orderby']
            : 'name';

        $order = ( ! empty( $args['order'] ) && strtoupper( $args['order'] ) === 'DESC' )
            ? 'DESC'
            : 'ASC';

        return " ORDER BY {$orderby} {$order}";
    }

    // Insere um novo cliente
    public static function insert( $data ) {
        global $wpdb;

        return $wpdb->insert(
            self::table(),
            array(
                'type'       => $data['type'],
                'name'       => $data['name'],
                'nuit'       => $data['nuit'],
                'email'      => $data['email'],
                'phone'      => $data['phone'],
                'address'    => $data['address'],
                'city'       => $data['city'],
                'created_at' => current_time( 'mysql' ),
            ),
            array(
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
            )
        );
    }

    // Actualiza um cliente existente
    public static function update( $id, $data ) {
        global $wpdb;

        return $wpdb->update(
            self::table(),
            array(
                'type'       => $data['type'],
                'name'       => $data['name'],
                'nuit'       => $data['nuit'],
                'email'      => $data['email'],
                'phone'      => $data['phone'],
                'address'    => $data['address'],
                'city'       => $data['city'],
                'updated_at' => current_time( 'mysql' ),
            ),
            array( 'id' => (int) $id ),
            array(
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
            ),
            array( '%d' )
        );
    }

    // Remove um cliente
    public static function delete( $id ) {
        global $wpdb;

        return $wpdb->delete(
            self::table(),
            array( 'id' => (int) $id ),
            array( '%d' )
        );
    }

    // Retorna um cliente pelo ID
    public static function get( $id ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::table() . " WHERE id = %d",
                (int) $id
            )
        );
    }

    // Retorna clientes com filtros/pesquisa/ordenação
    public static function get_customers( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'type'     => '',
            'search'   => '',
            'orderby'  => 'name',
            'order'    => 'ASC',
            'per_page' => 0,
            'offset'   => 0,
        );

        $args = wp_parse_args( $args, $defaults );

        $per_page = (int) $args['per_page'];
        $offset   = (int) $args['offset'];

        $sql  = "SELECT * FROM " . self::table();
        $sql .= self::build_where( $args );
        $sql .= self::build_order( $args );

        if ( $per_page > 0 ) {
            $sql .= $wpdb->prepare( " LIMIT %d OFFSET %d", $per_page, $offset );
        }

        return $wpdb->get_results( $sql );
    }

    // Conta clientes com filtros/pesquisa
    public static function count_customers( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'type'   => '',
            'search' => '',
        );

        $args = wp_parse_args( $args, $defaults );

        $sql  = "SELECT COUNT(*) FROM " . self::table();
        $sql .= self::build_where( $args );

        return (int) $wpdb->get_var( $sql );
    }

    // Retorna clientes por IDs
    public static function get_by_ids( $ids ) {
        global $wpdb;

        $ids = array_map( 'intval', (array) $ids );
        $ids = array_filter( $ids );

        if ( empty( $ids ) ) {
            return array();
        }

        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

        $sql = $wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE id IN ($placeholders)",
            $ids
        );

        return $wpdb->get_results( $sql );
    }

    // Verifica se existe cliente com o mesmo NUIT + Nome
    public static function exists_duplicate( $data ) {
        global $wpdb;

        $nuit = isset( $data['nuit'] ) ? trim( $data['nuit'] ) : '';
        $name = isset( $data['name'] ) ? trim( $data['name'] ) : '';

        if ( $nuit === '' || $name === '' ) {
            return false;
        }

        $found = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM " . self::table() . " WHERE nuit = %s AND LOWER(name) = LOWER(%s)",
                $nuit,
                $name
            )
        );

        return $found > 0;
    }

}
