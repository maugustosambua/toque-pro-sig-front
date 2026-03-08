<?php
// Impede acesso directo.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Personaliza a tela de login com visual moderno de ERP.
 */
class TPS_Login_Customizer {

    // Inicializa hooks do login.
    public static function init() {
        add_action( 'login_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
        add_filter( 'login_headerurl', array( __CLASS__, 'header_url' ) );
        add_filter( 'login_headertext', array( __CLASS__, 'header_text' ) );
        add_filter( 'login_redirect', array( __CLASS__, 'redirect_after_login' ), 10, 3 );
    }

    // URL do logo do login.
    public static function header_url() {
        return home_url( '/' );
    }

    // Texto alternativo do logo.
    public static function header_text() {
        return get_bloginfo( 'name' ) . ' ERP';
    }

    // Redirecciona para o dashboard apos login.
    public static function redirect_after_login( $redirect_to, $requested_redirect_to, $user ) {
        if ( is_wp_error( $user ) || ! ( $user instanceof WP_User ) ) {
            return $redirect_to;
        }

        if ( user_can( $user, 'manage_options' ) ) {
            return admin_url( 'admin.php?page=tps-dashboard' );
        }

        if ( ! empty( $requested_redirect_to ) ) {
            return $requested_redirect_to;
        }

        return $redirect_to;
    }

    // Estilos da pagina de login.
    public static function enqueue_styles() {
        $style_url = tps_get_asset_url( 'assets/css/login-modern.css' );
        $version   = tps_get_asset_version( 'assets/css/login-modern.css' );

        wp_enqueue_style( 'tps-login-modern', $style_url, array(), $version );
    }
}
