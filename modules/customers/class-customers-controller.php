<?php

// modules/customers/class-customers-controller.php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Controller responsavel por processar accoes de clientes.
class TPS_Customers_Controller {
    // Regista os hooks do controller.
    public static function init() {
        add_action( 'admin_post_tps_save_customer', array( __CLASS__, 'save' ) );
        add_action( 'admin_post_tps_delete_customer', array( __CLASS__, 'delete' ) );
        add_action( 'wp_ajax_tps_ajax_customers_list', array( __CLASS__, 'list_ajax' ) );
    }

    // Lista AJAX paginada de clientes.
    public static function list_ajax() {
        if ( ! tps_current_user_can( 'admin' ) ) {
            wp_send_json_error( array( 'message' => 'Permissão negada.' ), 403 );
        }

        check_ajax_referer( 'tps_ajax_customers_list', 'nonce' );

        $type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';
        if ( ! in_array( $type, array( '', 'individual', 'company' ), true ) ) {
            $type = '';
        }

        $search   = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
        $page     = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
        $sort     = isset( $_GET['sort'] ) ? sanitize_text_field( wp_unslash( $_GET['sort'] ) ) : 'name';
        $per_page = (int) tps_get_per_page();
        if ( $per_page <= 0 ) {
            $per_page = 20;
        }

        $orderby = 'name';
        $order   = 'ASC';
        if ( 'city' === $sort ) {
            $orderby = 'city';
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

        $items       = TPS_Customers_Model::get_customers( $args );
        $total_items = (int) TPS_Customers_Model::count_customers(
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
                'id'         => $id,
                'name'       => (string) $item->name,
                'type'       => (string) $item->type,
                'nuit'       => (string) ( $item->nuit ?? '' ),
                'phone'      => (string) ( $item->phone ?? '' ),
                'city'       => (string) ( $item->city ?? '' ),
                'edit_url'   => tps_get_page_url( 'tps-customers-add', array( 'customer_id' => $id ) ),
                'export_url' => wp_nonce_url( add_query_arg( array( 'action' => 'tps_export_customer', 'customer_id' => $id ), tps_get_action_url() ), 'tps_export_customer' ),
                'delete_url' => wp_nonce_url( add_query_arg( array( 'action' => 'tps_delete_customer', 'customer_id' => $id ), tps_get_action_url() ), 'tps_delete_customer' ),
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

    // Cria ou actualiza um cliente.
    public static function save() {
        if ( ! tps_current_user_can( 'admin' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        check_admin_referer( 'tps_save_customer' );

        $id   = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
        $data = self::sanitize_customer_payload( $_POST );

        if ( ! self::is_customer_payload_valid( $data ) ) {
            $redirect = tps_get_page_url( 'tps-customers-add' );
            if ( $id > 0 ) {
                $redirect = add_query_arg( 'customer_id', $id, $redirect );
            }
            wp_safe_redirect( esc_url_raw( tps_notice_url( $redirect, 'customer_invalid_data', 'error' ) ) );
            exit;
        }

        $notice = self::persist_customer( $id, $data );

        wp_safe_redirect( esc_url_raw( tps_notice_url( tps_get_page_url( 'tps-customers' ), $notice, 'success' ) ) );
        exit;
    }

    // Remove um cliente.
    public static function delete() {
        if ( ! tps_current_user_can( 'admin' ) ) {
            wp_die( 'Sem permissao para executar esta accao.' );
        }

        check_admin_referer( 'tps_delete_customer' );

        $id = isset( $_GET['customer_id'] ) ? (int) $_GET['customer_id'] : 0;
        if ( $id > 0 ) {
            TPS_Customers_Model::delete( $id );
            $redirect = tps_notice_url( tps_get_page_url( 'tps-customers' ), 'customer_deleted', 'success' );
        } else {
            $redirect = tps_notice_url( tps_get_page_url( 'tps-customers' ), 'customer_delete_invalid', 'error' );
        }

        wp_safe_redirect( esc_url_raw( $redirect ) );
        exit;
    }

    private static function sanitize_customer_payload( $source ) {
        return array(
            'type'    => isset( $source['type'] ) ? sanitize_text_field( wp_unslash( $source['type'] ) ) : '',
            'name'    => isset( $source['name'] ) ? sanitize_text_field( wp_unslash( $source['name'] ) ) : '',
            'nuit'    => isset( $source['nuit'] ) ? sanitize_text_field( wp_unslash( $source['nuit'] ) ) : '',
            'email'   => isset( $source['email'] ) ? sanitize_email( wp_unslash( $source['email'] ) ) : '',
            'phone'   => isset( $source['phone'] ) ? sanitize_text_field( wp_unslash( $source['phone'] ) ) : '',
            'address' => isset( $source['address'] ) ? sanitize_textarea_field( wp_unslash( $source['address'] ) ) : '',
            'city'    => isset( $source['city'] ) ? sanitize_text_field( wp_unslash( $source['city'] ) ) : '',
        );
    }

    private static function is_customer_payload_valid( $data ) {
        return ! empty( $data['name'] ) && in_array( $data['type'], array( 'individual', 'company' ), true );
    }

    private static function persist_customer( $id, $data ) {
        if ( $id > 0 ) {
            TPS_Customers_Model::update( $id, $data );
            return 'customer_updated';
        }

        TPS_Customers_Model::insert( $data );
        return 'customer_created';
    }
}
