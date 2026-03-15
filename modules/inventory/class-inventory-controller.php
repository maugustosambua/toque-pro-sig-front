<?php
// Impede acesso directo.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPS_Inventory_Controller {

    // Inicializa hooks do modulo.
    public static function init() {
        add_action( 'admin_post_tps_save_inventory_movement', array( __CLASS__, 'save_movement' ) );
        add_action( 'wp_ajax_tps_search_inventory_products', array( __CLASS__, 'search_products_ajax' ) );
    }

    // Pesquisa produtos controlados por stock para o formulario de movimentos.
    public static function search_products_ajax() {
        if ( ! tps_current_user_can( 'admin' ) ) {
            wp_send_json_error( array( 'message' => 'Permissao negada.' ), 403 );
        }

        check_ajax_referer( 'tps_search_inventory_products', 'nonce' );

        $term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
        $term = trim( $term );

        if ( '' === $term ) {
            wp_send_json_success( array() );
        }

        $items = TPS_Products_Services_Model::get_items(
            array(
                'type'        => 'product',
                'track_stock' => 1,
                'search'      => $term,
                'orderby'     => 'name',
                'order'       => 'ASC',
                'per_page'    => 20,
                'offset'      => 0,
            )
        );

        $results = array();
        foreach ( $items as $item ) {
            $label = (string) $item->name;
            if ( ! empty( $item->sku ) ) {
                $label .= ' - ' . (string) $item->sku;
            }

            $results[] = array(
                'id'        => (int) $item->id,
                'name'      => (string) $item->name,
                'sku'       => (string) ( $item->sku ?? '' ),
                'stock_qty' => (float) $item->stock_qty,
                'label'     => $label,
            );
        }

        wp_send_json_success( $results );
    }

    // Regista movimento manual de stock.
    public static function save_movement() {
        if ( ! tps_current_user_can( 'admin' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        check_admin_referer( 'tps_save_inventory_movement' );

        $redirect = tps_get_page_url( 'tps-inventory' );

        $product_id     = isset( $_POST['product_id'] ) ? (int) $_POST['product_id'] : 0;
        $movement_type  = isset( $_POST['movement_type'] ) ? sanitize_key( wp_unslash( $_POST['movement_type'] ) ) : '';
        $quantity_raw   = isset( $_POST['quantity'] ) ? str_replace( ',', '.', (string) wp_unslash( $_POST['quantity'] ) ) : '0';
        $unit_cost_raw  = isset( $_POST['unit_cost'] ) ? str_replace( ',', '.', (string) wp_unslash( $_POST['unit_cost'] ) ) : '0';
        $target_qty_raw = isset( $_POST['target_qty'] ) ? str_replace( ',', '.', (string) wp_unslash( $_POST['target_qty'] ) ) : '0';
        $movement_date  = isset( $_POST['movement_date'] ) ? sanitize_text_field( wp_unslash( $_POST['movement_date'] ) ) : current_time( 'mysql' );
        $notes          = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';
        $movement_date  = str_replace( 'T', ' ', $movement_date );
        if ( 16 === strlen( $movement_date ) ) {
            $movement_date .= ':00';
        }

        $quantity   = is_numeric( $quantity_raw ) ? (float) $quantity_raw : -1;
        $unit_cost  = is_numeric( $unit_cost_raw ) ? (float) $unit_cost_raw : -1;
        $target_qty = is_numeric( $target_qty_raw ) ? (float) $target_qty_raw : -1;

        if ( $product_id <= 0 || ! isset( TPS_Inventory_Model::movement_types()[ $movement_type ] ) || $unit_cost < 0 ) {
            wp_safe_redirect( esc_url_raw( tps_notice_url( $redirect, 'inventory_invalid_movement', 'error' ) ) );
            exit;
        }

        $payload = array(
            'product_id'     => $product_id,
            'movement_type'  => $movement_type,
            'unit_cost'      => $unit_cost,
            'reference_type' => 'manual',
            'reference_id'   => 0,
            'movement_date'  => $movement_date,
            'notes'          => $notes,
        );

        if ( 'adjustment' === $movement_type ) {
            if ( $target_qty < 0 ) {
                wp_safe_redirect( esc_url_raw( tps_notice_url( $redirect, 'inventory_invalid_movement', 'error' ) ) );
                exit;
            }

            $payload['quantity']   = 1;
            $payload['target_qty'] = $target_qty;
        } else {
            if ( $quantity <= 0 ) {
                wp_safe_redirect( esc_url_raw( tps_notice_url( $redirect, 'inventory_invalid_movement', 'error' ) ) );
                exit;
            }

            $payload['quantity'] = $quantity;
        }

        $result = TPS_Inventory_Model::create_movement( $payload );
        if ( is_wp_error( $result ) ) {
            $notice = 'inventory_invalid_movement';
            $type   = 'error';

            if ( 'insufficient_stock' === $result->get_error_code() ) {
                $notice = 'inventory_insufficient_stock';
            }

            wp_safe_redirect(
                esc_url_raw(
                    tps_notice_url(
                        $redirect,
                        $notice,
                        $type,
                        array( 'tps_notice_message' => $result->get_error_message() )
                    )
                )
            );
            exit;
        }

        wp_safe_redirect( esc_url_raw( tps_notice_url( $redirect, 'inventory_movement_saved', 'success' ) ) );
        exit;
    }
}
