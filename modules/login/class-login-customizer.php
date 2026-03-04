<?php
// Impede acesso directo
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
        ?>
        <style>
        :root {
            --tps-login-bg: #f0f0f1;
            --tps-login-card: #ffffff;
            --tps-login-border: #c3c4c7;
            --tps-login-text: #10203b;
            --tps-login-muted: #5d6c84;
            --tps-login-accent: #0f5ea8;
            --tps-login-accent-2: #0a4b86;
            --tps-login-warning: #9f3b16;
            --tps-login-error: #b42318;
            --tps-login-success: #0f7a3a;
        }
        body.login {
            min-height: 100vh;
            background: var(--tps-login-bg);
            font-family: "IBM Plex Sans", "Segoe UI", "Trebuchet MS", sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
        }
        body.login::before {
            display: none;
        }
        .login h1,
        .login h1 a {
            display: none;
        }
        .login #login {
            width: min(92vw, 400px);
            padding: 0;
            position: relative;
            z-index: 1;
            margin: 0 auto;
            order: 1;
            animation: tps-login-enter 420ms ease-out;
        }
        .login #login::before {
            content: "TOQUE PRO ERP";
            display: block;
            margin-bottom: 12px;
            text-align: center;
            color: #1d2327;
            font-family: "Space Grotesk", "Segoe UI", sans-serif;
            font-weight: 700;
            font-size: 26px;
            letter-spacing: 0.03em;
        }
        .login #login::after {
            content: "Acesso ao sistema";
            display: block;
            margin: -4px 0 14px;
            text-align: center;
            color: #646970;
            font-size: 13px;
            letter-spacing: 0.02em;
        }
        @keyframes tps-login-enter {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login form {
            margin-top: 0;
            border: 1px solid var(--tps-login-border);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            background: var(--tps-login-card);
            padding: 24px 24px 20px;
        }
        .login label {
            color: var(--tps-login-text);
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.01em;
            position: relative;
            padding-left: 24px;
            display: block;
            margin-bottom: 6px;
        }
        .login label[for="user_login"]::before,
        .login label[for="user_pass"]::before {
            position: absolute;
            left: 0;
            top: -1px;
            font-family: dashicons;
            font-size: 16px;
            line-height: 1;
            color: #466088;
        }
        .login label[for="user_login"]::before {
            content: "\f110";
        }
        .login label[for="user_pass"]::before {
            content: "\f160";
        }
        .login form .input,
        .login input[type="text"],
        .login input[type="password"] {
            border: 1px solid #c6d2e2;
            border-radius: 8px;
            min-height: 40px;
            padding: 8px 10px;
            font-size: 14px;
            box-shadow: none;
            color: var(--tps-login-text);
        }
        .login form p {
            margin-bottom: 16px;
        }
        .login form .input:focus,
        .login input[type="text"]:focus,
        .login input[type="password"]:focus {
            border-color: var(--tps-login-accent);
            box-shadow: 0 0 0 3px rgba(15, 94, 168, 0.2);
        }
        .login .button.wp-hide-pw {
            color: #2d446b;
            transition: all 120ms ease;
        }
        .login .button.wp-hide-pw:hover {
            color: #163f67;
            background: #f0f6ff;
        }
        .login .forgetmenot {
            margin-top: 0;
            margin-bottom: 10px;
            float: none;
        }
        .login .forgetmenot label {
            color: var(--tps-login-muted);
            font-weight: 500;
        }
        .login .submit {
            margin-top: 6px;
            margin-bottom: 0;
            float: none;
            clear: both;
        }
        .login .button-primary {
            width: 100%;
            min-height: 40px;
            border-radius: 8px;
            float: none;
            border: 0;
            font-weight: 700;
            letter-spacing: 0.02em;
            background: linear-gradient(135deg, var(--tps-login-accent) 0%, #167ccf 100%);
            box-shadow: 0 8px 18px rgba(15, 94, 168, 0.36);
            transition: transform 120ms ease, box-shadow 120ms ease, background-color 120ms ease;
        }
        .login .button-primary:hover,
        .login .button-primary:focus {
            background: linear-gradient(135deg, var(--tps-login-accent-2) 0%, #116ab2 100%);
            transform: translateY(-1px);
            box-shadow: 0 11px 22px rgba(10, 75, 134, 0.42);
        }
        .login #nav,
        .login #backtoblog {
            text-align: center;
            padding: 0;
            margin: 12px 0 0;
        }
        .login #backtoblog {
            display: none;
        }
        .login #nav a,
        .login #backtoblog a {
            color: #50575e;
            font-weight: 500;
            transition: color 120ms ease, text-decoration-color 120ms ease;
            text-decoration-color: transparent;
        }
        .login #nav a:hover,
        .login #backtoblog a:hover {
            color: #1d2327;
            text-decoration: underline;
            text-decoration-color: #1d2327;
        }
        .login .message,
        .login .notice,
        .login #login_error {
            border-radius: 0;
            border-left-width: 4px;
            box-shadow: none;
            margin-bottom: 14px;
        }
        .login .message {
            border-left-color: var(--tps-login-success);
        }
        .login .notice {
            border-left-color: var(--tps-login-warning);
        }
        .login #login_error {
            border-left-color: var(--tps-login-error);
        }
        .login .privacy-policy-page-link,
        .language-switcher {
            position: relative;
            z-index: 1;
        }
        .language-switcher {
            order: 2;
            width: min(92vw, 400px);
            margin: 12px auto 0;
            text-align: center;
        }
        .language-switcher form {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .login .language-switcher .language-switcher-shortcode {
            border-radius: 8px;
        }
        @media (max-width: 520px) {
            body.login {
                padding: 14px;
            }
            .login #login {
                width: 100%;
            }
            .login form {
                padding: 20px 16px 16px;
                border-radius: 10px;
            }
        }
        </style>
        <?php
    }
}
