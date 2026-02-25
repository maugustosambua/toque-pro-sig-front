<?php
/**
 * Plugin Name: Toque Pro SiG
 * Description: Sistema de Gestão e Facturação
 * Author: Toque Pro Lda.
 * Version: 1.0.0
 */

// Bloqueia acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define caminho base do plugin
define( 'TPS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// Hook de activação do plugin
require_once TPS_PLUGIN_PATH . 'core/class-activator.php';
register_activation_hook(
    __FILE__,
    array( 'TPS_Activator', 'activate' )
);

// Hook de desactivacao do plugin
require_once TPS_PLUGIN_PATH . 'core/class-deactivator.php';
register_deactivation_hook(
    __FILE__,
    array( 'TPS_Deactivator', 'deactivate' )
);

// Carregador
require_once TPS_PLUGIN_PATH . 'core/class-loader.php';

// Inicializa o sistema
add_action( 'plugins_loaded', array( 'TPS_Loader', 'init' ) );

