<?php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Classe executada na desactivacao do plugin
class TPS_Deactivator {

    // Metodo chamado ao desactivar o plugin
    public static function deactivate() {
        // Placeholder para futuras limpezas (cron, cache, etc.)
    }
}
