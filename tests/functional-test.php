<?php
/**
 * Script funcional de teste para Toque Pro SiG
 * Executar: php tests/functional-test.php
 */

// Carrega WordPress
$wp_load = __DIR__ . '/../../../../wp-load.php';
if ( ! file_exists( $wp_load ) ) {
    echo "wp-load.php não encontrado em: $wp_load\n";
    exit(1);
}

require_once $wp_load;

// Carrega funções do plugin
require_once ABSPATH . 'wp-admin/includes/plugin.php';

// Tenta incluir o plugin
$plugin_file = 'toque-pro-sig-front/toque-pro-sig.php';
$plugin_path = WP_PLUGIN_DIR . '/toque-pro-sig-front/toque-pro-sig.php';
if ( ! file_exists( $plugin_path ) ) {
    echo "Plugin não encontrado em: $plugin_path\n";
    exit(1);
}

require_once $plugin_path;

do_action( 'plugins_loaded' );

global $wpdb;

// Função auxiliar para output formatado
function out( $label, $value ) {
    echo sprintf( "%s: %s\n", $label, is_scalar( $value ) ? $value : json_encode( $value ) );
}

// Cria cliente de teste
$test_nuit = 'TEST-' . time();
$customer_data = array(
    'type'    => 'individual',
    'name'    => 'Cliente Teste ' . rand( 1000, 9999 ),
    'nuit'    => $test_nuit,
    'email'   => 'test+' . time() . '@example.com',
    'phone'   => '900000000',
    'address' => 'Rua de Teste, 1',
    'city'    => 'Cidade',
);

out( 'Inserindo cliente', json_encode( $customer_data ) );
$insert_ok = TPS_Customers_Model::insert( $customer_data );
$customer_id = $wpdb->insert_id;

if ( ! $insert_ok ) {
    out( 'Erro', 'Não foi possível inserir cliente' );
    exit(1);
}

out( 'Cliente ID', $customer_id );

// Cria documento draft
$type = 'invoice';
$number_preview = TPS_Documents_Model::next_number_preview( $type );
$doc_data = array(
    'type'        => $type,
    'number'      => $number_preview,
    'customer_id' => (int) $customer_id,
    'issue_date'  => date( 'Y-m-d' ),
);

$out = TPS_Documents_Model::insert( $doc_data );
$document_id = $wpdb->insert_id;

if ( ! $out ) {
    out( 'Erro', 'Não foi possível inserir documento' );
    // Cleanup cliente
    TPS_Customers_Model::delete( $customer_id );
    exit(1);
}

out( 'Documento ID', $document_id );

// Adiciona linha
$line = array(
    'document_id' => (int) $document_id,
    'description' => 'Serviço de Teste',
    'quantity'    => 2.5,
    'unit_price'  => 10.00,
);

$line_out = TPS_Document_Lines_Model::insert( $line );
$out_line_id = $wpdb->insert_id;

out( 'Linha inserida ID', $out_line_id );

// Calcula totais
$totals = TPS_Document_Lines_Model::totals( $document_id );
out( 'Totais', $totals );

// Emite documento (incrementa número)
$final_number = tps_get_and_increment_document_number( $type );
TPS_Documents_Model::update_number( $document_id, $final_number );
TPS_Documents_Model::issue( $document_id );

$out_doc = TPS_Documents_Model::get( $document_id );
out( 'Documento após emissão (status)', $out_doc->status ?? 'N/A' );
out( 'Número final', $out_doc->number ?? $final_number );

// Limpeza automática: remover linhas, documento e cliente de teste
$lines_deleted = $wpdb->delete( $wpdb->prefix . 'tps_document_lines', array( 'document_id' => $document_id ), array( '%d' ) );
$doc_deleted   = $wpdb->delete( $wpdb->prefix . 'tps_documents', array( 'id' => $document_id ), array( '%d' ) );
$cus_deleted   = TPS_Customers_Model::delete( $customer_id );

out( 'Limpeza - linhas removidas', $lines_deleted );
out( 'Limpeza - doc removido', $doc_deleted );
out( 'Limpeza - cliente removido', $cus_deleted );

out( 'Teste', 'Concluído' );

