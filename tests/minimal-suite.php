<?php
/**
 * Suíte funcional mínima para fluxos críticos do Toque Pro SiG.
 *
 * Cobertura:
 * - Emissão
 * - Fiscal
 * - Recebimentos
 * - Cancelamentos/Correções
 * - Permissões
 *
 * Executar: php tests/minimal-suite.php
 */

$wp_load = __DIR__ . '/../../../../wp-load.php';
if ( ! file_exists( $wp_load ) ) {
    echo "wp-load.php não encontrado em: {$wp_load}\n";
    exit(1);
}

require_once $wp_load;
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$plugin_path = WP_PLUGIN_DIR . '/toque-pro-sig-front/toque-pro-sig.php';
if ( ! file_exists( $plugin_path ) ) {
    echo "Plugin não encontrado em: {$plugin_path}\n";
    exit(1);
}

require_once $plugin_path;
do_action( 'plugins_loaded' );

global $wpdb;

function tps_suite_out( $label, $value = '' ) {
    $text = is_scalar( $value ) ? (string) $value : wp_json_encode( $value );
    echo $text === '' ? "{$label}\n" : "{$label}: {$text}\n";
}

function tps_suite_assert_true( $condition, $message ) {
    if ( ! $condition ) {
        throw new RuntimeException( $message );
    }
}

function tps_suite_assert_equals( $expected, $actual, $message ) {
    if ( $expected !== $actual ) {
        throw new RuntimeException( $message . " | esperado=" . wp_json_encode( $expected ) . " atual=" . wp_json_encode( $actual ) );
    }
}

function tps_suite_find_customer_id_by_nuit( $nuit ) {
    global $wpdb;

    return (int) $wpdb->get_var(
        $wpdb->prepare(
            'SELECT id FROM ' . TPS_Customers_Model::table() . ' WHERE nuit = %s ORDER BY id DESC LIMIT 1',
            (string) $nuit
        )
    );
}

function tps_suite_find_document_id( $type, $number, $customer_id, $issue_date ) {
    global $wpdb;

    return (int) $wpdb->get_var(
        $wpdb->prepare(
            'SELECT id FROM ' . TPS_Documents_Model::table() . ' WHERE type = %s AND number = %d AND customer_id = %d AND issue_date = %s ORDER BY id DESC LIMIT 1',
            (string) $type,
            (int) $number,
            (int) $customer_id,
            (string) $issue_date
        )
    );
}

$created_customer_id = 0;
$created_document_ids = array();
$created_payment_ids = array();

try {
    $now_suffix = time() . '-' . wp_rand( 1000, 9999 );

    tps_suite_out( 'Início', 'Suíte mínima funcional' );

    tps_suite_assert_true( class_exists( 'TPS_Customers_Model' ), 'Classe TPS_Customers_Model não carregada.' );
    tps_suite_assert_true( class_exists( 'TPS_Documents_Model' ), 'Classe TPS_Documents_Model não carregada.' );
    tps_suite_assert_true( class_exists( 'TPS_Document_Lines_Model' ), 'Classe TPS_Document_Lines_Model não carregada.' );
    tps_suite_assert_true( class_exists( 'TPS_Payments_Model' ), 'Classe TPS_Payments_Model não carregada.' );

    // --- Permissões ---
    if ( function_exists( 'tps_sync_role_capabilities' ) ) {
        tps_sync_role_capabilities();
    }

    $cap_map = tps_get_capability_map();
    foreach ( array( 'emitir', 'cancelar', 'receber', 'fiscal', 'admin' ) as $cap_key ) {
        tps_suite_assert_true( ! empty( $cap_map[ $cap_key ] ), 'Capability ausente no mapa: ' . $cap_key );
    }

    $author_role = get_role( 'author' );
    $editor_role = get_role( 'editor' );

    tps_suite_assert_true( (bool) $author_role, 'Role author não encontrada.' );
    tps_suite_assert_true( (bool) $editor_role, 'Role editor não encontrada.' );

    tps_suite_assert_true( $author_role->has_cap( tps_get_capability( 'emitir' ) ), 'Author deveria ter permissão emitir.' );
    tps_suite_assert_true( $author_role->has_cap( tps_get_capability( 'receber' ) ), 'Author deveria ter permissão receber.' );
    tps_suite_assert_true( ! $author_role->has_cap( tps_get_capability( 'cancelar' ) ), 'Author não deveria ter permissão cancelar.' );
    tps_suite_assert_true( $editor_role->has_cap( tps_get_capability( 'fiscal' ) ), 'Editor deveria ter permissão fiscal.' );
    tps_suite_out( 'Permissões', 'ok' );

    // --- Dados base ---
    $customer_data = array(
        'type'    => 'individual',
        'name'    => 'Cliente Suite ' . $now_suffix,
        'nuit'    => 'SUITE-' . $now_suffix,
        'email'   => 'suite+' . $now_suffix . '@example.com',
        'phone'   => '900000000',
        'address' => 'Rua Suite, 1',
        'city'    => 'Maputo',
    );

    $insert_customer = TPS_Customers_Model::insert( $customer_data );
    tps_suite_assert_true( false !== $insert_customer, 'Falha ao inserir cliente.' );

    $created_customer_id = tps_suite_find_customer_id_by_nuit( $customer_data['nuit'] );
    tps_suite_assert_true( $created_customer_id > 0, 'ID de cliente inválido.' );

    $doc_type      = 'invoice';
    $draft_number  = (int) TPS_Documents_Model::next_number_preview( $doc_type );
    $issue_date    = wp_date( 'Y-m-d' );

    $insert_document = TPS_Documents_Model::insert(
        array(
            'type'        => $doc_type,
            'number'      => $draft_number,
            'customer_id' => $created_customer_id,
            'issue_date'  => $issue_date,
            'due_date'    => $issue_date,
        )
    );

    tps_suite_assert_true( false !== $insert_document, 'Falha ao inserir documento draft.' );

    $document_id = tps_suite_find_document_id( $doc_type, $draft_number, $created_customer_id, $issue_date );
    tps_suite_assert_true( $document_id > 0, 'ID de documento inválido.' );
    $created_document_ids[] = $document_id;

    $line_insert = TPS_Document_Lines_Model::insert(
        array(
            'document_id' => $document_id,
            'description' => 'Serviço Suite',
            'quantity'    => 2,
            'unit_price'  => 100.00,
            'tax_mode'    => 'taxable',
            'tax_rate'    => 16,
        )
    );
    tps_suite_assert_true( false !== $line_insert, 'Falha ao inserir linha fiscal.' );

    // --- Fiscal ---
    $withholding_updated = TPS_Documents_Model::update_withholding_rate( $document_id, 10.0 );
    tps_suite_assert_true( true === $withholding_updated, 'Falha ao atualizar taxa de retenção em draft.' );

    $fiscal = TPS_Documents_Model::fiscal_totals( $document_id );
    tps_suite_assert_true( abs( (float) $fiscal['subtotal'] - 200.0 ) < 0.01, 'Subtotal fiscal inesperado.' );
    tps_suite_assert_true( abs( (float) $fiscal['iva'] - 32.0 ) < 0.01, 'IVA fiscal inesperado.' );
    tps_suite_assert_true( abs( (float) $fiscal['withholding_amount'] - 20.0 ) < 0.01, 'Retenção fiscal inesperada.' );
    tps_suite_assert_true( abs( (float) $fiscal['payable_total'] - 212.0 ) < 0.01, 'Total a pagar fiscal inesperado.' );
    tps_suite_out( 'Fiscal', 'ok' );

    // --- Emissão ---
    $issued = TPS_Documents_Model::issue( $document_id );
    tps_suite_assert_true( false !== $issued, 'Falha ao emitir documento.' );

    $issued_doc = TPS_Documents_Model::get( $document_id );
    tps_suite_assert_equals( 'issued', (string) $issued_doc->status, 'Status após emissão inválido.' );
    tps_suite_out( 'Emissão', 'ok' );

    // --- Recebimentos ---
    $payment_id = TPS_Payments_Model::record_payment(
        array(
            'document_id'  => $document_id,
            'customer_id'  => $created_customer_id,
            'payment_date' => $issue_date,
            'amount'       => 100.00,
            'method'       => 'cash',
            'reference'    => 'REC-' . $now_suffix,
            'notes'        => 'Pagamento parcial da suite',
        )
    );

    tps_suite_assert_true( false !== $payment_id, 'Falha ao registar recebimento.' );
    $created_payment_ids[] = (int) $payment_id;

    $doc_after_payment = TPS_Documents_Model::get( $document_id );
    tps_suite_assert_true( abs( (float) $doc_after_payment->paid_total - 100.0 ) < 0.01, 'Total recebido inesperado.' );
    tps_suite_assert_true( abs( (float) $doc_after_payment->balance_due - 112.0 ) < 0.01, 'Saldo em aberto inesperado.' );
    tps_suite_assert_equals( 'partial', (string) $doc_after_payment->payment_status, 'Estado financeiro inesperado após recebimento.' );
    tps_suite_out( 'Recebimentos', 'ok' );

    // --- Correções (nota de crédito) ---
    $credit_note_number = (int) TPS_Documents_Model::next_number_preview( 'credit_note' );

    $credit_note_insert = TPS_Documents_Model::insert(
        array(
            'type'                 => 'credit_note',
            'number'               => $credit_note_number,
            'customer_id'          => $created_customer_id,
            'issue_date'           => $issue_date,
            'due_date'             => $issue_date,
            'original_document_id' => $document_id,
            'adjustment_reason'    => 'Correcao de valores',
        )
    );

    tps_suite_assert_true( false !== $credit_note_insert, 'Falha ao criar nota de crédito.' );

    $credit_note_id = tps_suite_find_document_id(
        'credit_note',
        $credit_note_number,
        $created_customer_id,
        $issue_date
    );
    tps_suite_assert_true( $credit_note_id > 0, 'ID da nota de crédito inválido.' );
    $created_document_ids[] = $credit_note_id;

    $copied_lines = TPS_Document_Lines_Model::clone_lines_to_document( $document_id, $credit_note_id );
    tps_suite_assert_true( false !== $copied_lines, 'Falha ao copiar linhas para nota de crédito.' );

    $credit_note = TPS_Documents_Model::get( $credit_note_id );
    tps_suite_assert_equals( 'credit_note', (string) $credit_note->type, 'Tipo da correção inválido.' );
    tps_suite_assert_equals( (int) $document_id, (int) $credit_note->original_document_id, 'Vínculo ao documento original inválido.' );

    $original_lines = TPS_Document_Lines_Model::get_by_document( $document_id );
    $credit_lines   = TPS_Document_Lines_Model::get_by_document( $credit_note_id );
    tps_suite_assert_equals( count( $original_lines ), count( $credit_lines ), 'Quantidade de linhas da correção divergente.' );
    tps_suite_out( 'Correções', 'ok' );

    // --- Cancelamento ---
    $cancelled = TPS_Documents_Model::cancel( $document_id, 'Cancelado por teste automatizado', get_current_user_id() );
    tps_suite_assert_true( false !== $cancelled, 'Falha ao cancelar documento.' );

    $cancelled_doc = TPS_Documents_Model::get( $document_id );
    tps_suite_assert_equals( 'cancelled', (string) $cancelled_doc->status, 'Status após cancelamento inválido.' );
    tps_suite_assert_true( strpos( (string) $cancelled_doc->cancel_reason, 'teste automatizado' ) !== false, 'Motivo de cancelamento não registado.' );
    tps_suite_out( 'Cancelamentos', 'ok' );

    tps_suite_out( 'Resultado', 'SUITE PASSOU' );
    exit(0);
} catch ( Throwable $e ) {
    tps_suite_out( 'Resultado', 'SUITE FALHOU' );
    tps_suite_out( 'Erro', $e->getMessage() );
    exit(1);
} finally {
    $doc_ids = array_values( array_unique( array_filter( array_map( 'intval', $created_document_ids ) ) ) );

    if ( ! empty( $created_payment_ids ) ) {
        foreach ( $created_payment_ids as $payment_id ) {
            $wpdb->delete( TPS_Payments_Model::allocations_table(), array( 'payment_id' => (int) $payment_id ), array( '%d' ) );
            $wpdb->delete( TPS_Payments_Model::table(), array( 'id' => (int) $payment_id ), array( '%d' ) );
        }
    }

    foreach ( $doc_ids as $doc_id ) {
        $wpdb->delete( TPS_Document_Lines_Model::table(), array( 'document_id' => (int) $doc_id ), array( '%d' ) );
        $wpdb->delete( TPS_Documents_Model::fiscal_events_table(), array( 'document_id' => (int) $doc_id ), array( '%d' ) );
        $wpdb->delete( TPS_Documents_Model::fiscal_snapshots_table(), array( 'document_id' => (int) $doc_id ), array( '%d' ) );
        $wpdb->delete( TPS_Documents_Model::table(), array( 'id' => (int) $doc_id ), array( '%d' ) );
    }

    if ( $created_customer_id > 0 ) {
        TPS_Customers_Model::delete( $created_customer_id );
    }
}
