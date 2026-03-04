<?php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPS_Products_Services_Controller {

    // Inicializa hooks do módulo
    public static function init() {
        add_action( 'admin_post_tps_save_product_service', array( __CLASS__, 'save' ) );
        add_action( 'admin_post_tps_delete_product_service', array( __CLASS__, 'delete' ) );
        add_action( 'wp_ajax_tps_ajax_products_services_list', array( __CLASS__, 'list_ajax' ) );
    }

    // Lista AJAX paginada
    public static function list_ajax() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permissão negada.' ), 403 );
        }

        check_ajax_referer( 'tps_ajax_products_services_list', 'nonce' );

        $type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';
        if ( ! in_array( $type, array( '', 'product', 'service' ), true ) ) {
            $type = '';
        }

        $search   = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
        $sort     = isset( $_GET['sort'] ) ? sanitize_text_field( wp_unslash( $_GET['sort'] ) ) : 'name';
        $page     = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
        $per_page = (int) tps_get_per_page();
        if ( $per_page <= 0 ) {
            $per_page = 20;
        }

        $orderby = 'name';
        $order   = 'ASC';
        if ( 'price' === $sort ) {
            $orderby = 'price';
        } elseif ( 'date' === $sort ) {
            $orderby = 'created_at';
            $order   = 'DESC';
        }

        $args = array(
            'type'     => $type,
            'search'   => $search,
            'orderby'  => $orderby,
            'order'    => $order,
            'per_page' => $per_page,
            'offset'   => ( $page - 1 ) * $per_page,
        );

        $items       = TPS_Products_Services_Model::get_items( $args );
        $total_items = (int) TPS_Products_Services_Model::count_items(
            array(
                'type'   => $type,
                'search' => $search,
            )
        );
        $total_pages = max( 1, (int) ceil( $total_items / $per_page ) );

        $rows = array();
        foreach ( $items as $item ) {
            $id     = (int) $item->id;
            $rows[] = array(
                'name'       => (string) $item->name,
                'type'       => (string) $item->type,
                'sku'        => (string) ( $item->sku ?? '' ),
                'unit'       => (string) ( $item->unit ?? '' ),
                'price'      => number_format( (float) $item->price, 2 ),
                'edit_url'   => admin_url( 'admin.php?page=tps-products-services-add&ps_id=' . $id ),
                'delete_url' => wp_nonce_url( admin_url( 'admin-post.php?action=tps_delete_product_service&ps_id=' . $id ), 'tps_delete_product_service' ),
            );
        }

        wp_send_json_success(
            array(
                'rows'         => $rows,
                'total_items'  => $total_items,
                'total_pages'  => $total_pages,
                'current_page' => min( $page, $total_pages ),
            )
        );
    }

    // Cria ou actualiza item
    public static function save() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        check_admin_referer( 'tps_save_product_service' );

        $id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

        $raw_price = isset( $_POST['price'] ) ? wp_unslash( $_POST['price'] ) : '0';
        $raw_price = str_replace( ',', '.', (string) $raw_price );
        $price     = is_numeric( $raw_price ) ? (float) $raw_price : -1;

        $data = array(
            'type'        => isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '',
            'name'        => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
            'sku'         => isset( $_POST['sku'] ) ? sanitize_text_field( wp_unslash( $_POST['sku'] ) ) : '',
            'unit'        => isset( $_POST['unit'] ) ? sanitize_text_field( wp_unslash( $_POST['unit'] ) ) : '',
            'price'       => $price,
            'description' => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
        );

        if ( empty( $data['name'] ) || ! in_array( $data['type'], array( 'product', 'service' ), true ) || $data['price'] < 0 ) {
            $redirect = admin_url( 'admin.php?page=tps-products-services-add' );
            if ( $id > 0 ) {
                $redirect = add_query_arg( 'ps_id', $id, $redirect );
            }

            wp_safe_redirect( esc_url_raw( tps_notice_url( $redirect, 'product_service_invalid_data', 'error' ) ) );
            exit;
        }

        if ( $id > 0 ) {
            TPS_Products_Services_Model::update( $id, $data );
            $notice = 'product_service_updated';
        } else {
            TPS_Products_Services_Model::insert( $data );
            $notice = 'product_service_created';
        }

        wp_safe_redirect( esc_url_raw( tps_notice_url( admin_url( 'admin.php?page=tps-products-services' ), $notice, 'success' ) ) );
        exit;
    }

    // Remove item
    public static function delete() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        check_admin_referer( 'tps_delete_product_service' );

        $id = isset( $_GET['ps_id'] ) ? (int) $_GET['ps_id'] : 0;
        if ( $id > 0 ) {
            TPS_Products_Services_Model::delete( $id );
            $redirect = tps_notice_url( admin_url( 'admin.php?page=tps-products-services' ), 'product_service_deleted', 'success' );
        } else {
            $redirect = tps_notice_url( admin_url( 'admin.php?page=tps-products-services' ), 'product_service_delete_invalid', 'error' );
        }

        wp_safe_redirect( esc_url_raw( $redirect ) );
        exit;
    }
}
