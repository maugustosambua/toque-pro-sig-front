<?php // modules/customers/class-customers-controller.php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Controller responsável por processar acções de clientes
class TPS_Customers_Controller {

    // Regista os hooks do controller
    public static function init() {
        add_action( 'admin_post_tps_save_customer', array( __CLASS__, 'save' ) );
        add_action( 'admin_post_tps_delete_customer', array( __CLASS__, 'delete' ) );
    }

    // Cria ou actualiza um cliente
    public static function save() {

        // Verifica permissão do utilizador
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Permission denied' );
        }

        // Verifica nonce de segurança
        check_admin_referer( 'tps_save_customer' );

        // Obtém o ID se existir
        $id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

        // Sanitiza os dados recebidos
        $data = array(
            'type'    => isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '',
            'name'    => isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '',
            'nuit'    => isset( $_POST['nuit'] ) ? sanitize_text_field( $_POST['nuit'] ) : '',
            'email'   => isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '',
            'phone'   => isset( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : '',
            'address' => isset( $_POST['address'] ) ? sanitize_textarea_field( $_POST['address'] ) : '',
            'city'    => isset( $_POST['city'] ) ? sanitize_text_field( $_POST['city'] ) : '',
        );

        // Decide entre inserir ou actualizar
        if ( $id > 0 ) {
            TPS_Customers_Model::update( $id, $data );
        } else {
            TPS_Customers_Model::insert( $data );
        }

        // Redirecciona para a lista de clientes
        wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=tps-customers' ) ) );
        exit;
    }

    // Remove um cliente
    public static function delete() {

        // Verifica permissão
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Permission denied' );
        }

        // Verifica nonce
        check_admin_referer( 'tps_delete_customer' );

        // Obtém o ID do cliente
        $id = isset( $_GET['customer_id'] ) ? (int) $_GET['customer_id'] : 0;

        // Apaga o cliente se existir
        if ( $id > 0 ) {
            TPS_Customers_Model::delete( $id );
        }

        // Redirecciona de volta à lista
        wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=tps-customers' ) ) );
        exit;
    }
}
