<?php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Classe executada na activação do plugin
class TPS_Activator {

    // Método chamado ao activar o plugin
    public static function activate() {
        require_once TPS_PLUGIN_PATH . 'database/class-schema.php'; // Carrega schema
        TPS_Schema::install(); // Cria as tabelas
    }
}
