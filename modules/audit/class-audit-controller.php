<?php
// Impede acesso directo.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPS_Audit_Controller {

    // Inicializa o módulo de auditoria.
    public static function init() {
        // Módulo apenas de leitura no momento.
    }

    // Monta dados da listagem com filtros e paginação.
    public static function get_list_data() {
        global $wpdb;

        $per_page = (int) tps_get_per_page();
        if ( $per_page <= 0 ) {
            $per_page = 20;
        }

        $paged = isset( $_GET['audit_page'] ) ? max( 1, (int) $_GET['audit_page'] ) : 1;

        $filters = array(
            'event_type'  => isset( $_GET['event_type'] ) ? sanitize_key( wp_unslash( $_GET['event_type'] ) ) : '',
            'entity_type' => isset( $_GET['entity_type'] ) ? sanitize_key( wp_unslash( $_GET['entity_type'] ) ) : '',
            'user_id'     => isset( $_GET['user_id'] ) ? max( 0, (int) $_GET['user_id'] ) : 0,
            'from_date'   => isset( $_GET['from_date'] ) ? self::sanitize_date_ymd( wp_unslash( $_GET['from_date'] ) ) : '',
            'to_date'     => isset( $_GET['to_date'] ) ? self::sanitize_date_ymd( wp_unslash( $_GET['to_date'] ) ) : '',
        );

        $table      = tps_audit_table();
        $users_table = $wpdb->users;
        $where_data = self::build_where_clause( $filters );

        $count_sql = 'SELECT COUNT(*) FROM ' . $table . ' a' . $where_data['sql'];
        $total_items = 0;

        if ( ! empty( $where_data['args'] ) ) {
            $count_sql   = $wpdb->prepare( $count_sql, $where_data['args'] );
            $total_items = (int) $wpdb->get_var( $count_sql );
        } else {
            $total_items = (int) $wpdb->get_var( $count_sql );
        }

        $total_pages = max( 1, (int) ceil( $total_items / $per_page ) );
        $offset      = ( min( $paged, $total_pages ) - 1 ) * $per_page;

        $items_sql = 'SELECT a.*, u.display_name AS user_display_name
            FROM ' . $table . ' a
            LEFT JOIN ' . $users_table . ' u ON u.ID = a.user_id'
            . $where_data['sql']
            . ' ORDER BY a.created_at DESC, a.id DESC LIMIT %d OFFSET %d';

        $items_args = $where_data['args'];
        $items_args[] = $per_page;
        $items_args[] = $offset;

        $items = $wpdb->get_results( $wpdb->prepare( $items_sql, $items_args ) );

        return array(
            'items'         => is_array( $items ) ? $items : array(),
            'filters'       => $filters,
            'event_options' => self::get_event_options(),
            'entity_options'=> self::get_entity_options(),
            'user_options'  => self::get_user_options(),
            'paged'         => min( $paged, $total_pages ),
            'total_pages'   => $total_pages,
            'total_items'   => $total_items,
        );
    }

    // Lista de tipos de evento disponíveis para filtro.
    private static function get_event_options() {
        global $wpdb;

        $rows = $wpdb->get_col( 'SELECT DISTINCT event_type FROM ' . tps_audit_table() . ' WHERE event_type IS NOT NULL AND event_type <> "" ORDER BY event_type ASC' );

        return is_array( $rows ) ? array_values( array_map( 'sanitize_key', $rows ) ) : array();
    }

    // Lista de tipos de entidade disponíveis para filtro.
    private static function get_entity_options() {
        global $wpdb;

        $rows = $wpdb->get_col( 'SELECT DISTINCT entity_type FROM ' . tps_audit_table() . ' WHERE entity_type IS NOT NULL AND entity_type <> "" ORDER BY entity_type ASC' );

        return is_array( $rows ) ? array_values( array_map( 'sanitize_key', $rows ) ) : array();
    }

    // Lista de utilizadores que aparecem na trilha de auditoria.
    private static function get_user_options() {
        global $wpdb;

        $rows = $wpdb->get_results(
            'SELECT DISTINCT a.user_id, u.display_name
            FROM ' . tps_audit_table() . ' a
            INNER JOIN ' . $wpdb->users . ' u ON u.ID = a.user_id
            WHERE a.user_id IS NOT NULL
            ORDER BY u.display_name ASC'
        );

        return is_array( $rows ) ? $rows : array();
    }

    // Constrói cláusula WHERE com filtros.
    private static function build_where_clause( $filters ) {
        global $wpdb;

        $where = array();
        $args  = array();

        if ( '' !== $filters['event_type'] ) {
            $where[] = 'a.event_type = %s';
            $args[]  = $filters['event_type'];
        }

        if ( '' !== $filters['entity_type'] ) {
            $where[] = 'a.entity_type = %s';
            $args[]  = $filters['entity_type'];
        }

        if ( (int) $filters['user_id'] > 0 ) {
            $where[] = 'a.user_id = %d';
            $args[]  = (int) $filters['user_id'];
        }

        if ( '' !== $filters['from_date'] ) {
            $where[] = 'DATE(a.created_at) >= %s';
            $args[]  = $filters['from_date'];
        }

        if ( '' !== $filters['to_date'] ) {
            $where[] = 'DATE(a.created_at) <= %s';
            $args[]  = $filters['to_date'];
        }

        if ( empty( $where ) ) {
            return array(
                'sql'  => '',
                'args' => array(),
            );
        }

        return array(
            'sql'  => ' WHERE ' . implode( ' AND ', $where ),
            'args' => $args,
        );
    }

    // Sanitiza data no formato Y-m-d.
    private static function sanitize_date_ymd( $value ) {
        $value = sanitize_text_field( (string) $value );
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
            return '';
        }

        $date = \DateTime::createFromFormat( 'Y-m-d', $value );

        return $date instanceof \DateTime && $date->format( 'Y-m-d' ) === $value ? $value : '';
    }
}
