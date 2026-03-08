<?php
// Impede acesso directo.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Constroi URL com feedback para o utilizador.
function tps_notice_url( $url, $notice_code, $notice_type = 'success', $extra_args = array() ) {
    $allowed_types = array( 'success', 'error', 'warning', 'info' );
    if ( ! in_array( $notice_type, $allowed_types, true ) ) {
        $notice_type = 'info';
    }

    $args = array_merge(
        array(
            'tps_notice'      => sanitize_key( (string) $notice_code ),
            'tps_notice_type' => $notice_type,
        ),
        $extra_args
    );

    return add_query_arg( $args, $url );
}

// Resolve texto de feedback por codigo.
function tps_get_notice_message( $notice_code ) {
    switch ( $notice_code ) {
        case 'customer_created':
            return 'Cliente criado com sucesso.';
        case 'customer_updated':
            return 'Cliente atualizado com sucesso.';
        case 'customer_deleted':
            return 'Cliente removido com sucesso.';
        case 'customer_not_found':
            return 'Cliente nao encontrado.';
        case 'customer_invalid_data':
            return 'Preencha os campos obrigatorios do cliente.';
        case 'customer_delete_invalid':
            return 'Nao foi possivel remover o cliente. ID invalido.';
        case 'import_no_file':
            return 'Nenhum ficheiro foi enviado.';
        case 'import_invalid_file_type':
            return 'Tipo de ficheiro invalido. Envie um CSV.';
        case 'import_unable_to_open':
            return 'Nao foi possivel abrir o ficheiro.';
        case 'import_empty_file':
            return 'O ficheiro enviado esta vazio.';
        case 'import_invalid_header':
            return 'Cabecalho CSV invalido. Verifique a primeira linha.';
        case 'import_missing_column':
            $column = isset( $_GET['col'] ) ? sanitize_text_field( wp_unslash( $_GET['col'] ) ) : '';
            if ( $column ) {
                return sprintf( 'Falta a coluna obrigatoria: %s.', $column );
            }
            return 'Falta uma coluna obrigatoria no CSV.';
        case 'import_result':
            $imported           = isset( $_GET['imported'] ) ? max( 0, (int) $_GET['imported'] ) : 0;
            $skipped_duplicates = isset( $_GET['skipped_duplicates'] ) ? max( 0, (int) $_GET['skipped_duplicates'] ) : 0;
            $skipped_invalid    = isset( $_GET['skipped_invalid'] ) ? max( 0, (int) $_GET['skipped_invalid'] ) : 0;
            return sprintf(
                'Importacao concluida: %d importados, %d duplicados ignorados e %d invalidos ignorados.',
                $imported,
                $skipped_duplicates,
                $skipped_invalid
            );
        case 'document_invalid_type':
            return 'Tipo de documento invalido.';
        case 'document_invalid_customer':
            return 'Cliente invalido. Selecione um cliente existente.';
        case 'document_draft_created':
            return 'Rascunho do documento criado com sucesso.';
        case 'document_not_found':
            return 'Documento nao encontrado.';
        case 'document_line_added':
            return 'Linha adicionada com sucesso.';
        case 'document_line_deleted':
            return 'Linha removida com sucesso.';
        case 'document_line_invalid':
            return 'Nao foi possivel remover a linha. Dados invalidos.';
        case 'document_locked':
            return 'Este documento esta bloqueado para edicao.';
        case 'document_issued':
            return 'Documento emitido com sucesso.';
        case 'document_issue_no_lines':
            return 'Adicione pelo menos uma linha antes de emitir o documento.';
        case 'document_issue_invalid_status':
            return 'So e possivel emitir documentos em rascunho.';
        case 'document_cancelled':
            return 'Documento cancelado com sucesso.';
        case 'document_cancel_invalid_status':
            return 'So e possivel cancelar documentos emitidos.';
        case 'document_invalid_due_date':
            return 'A data de vencimento deve ser igual ou posterior a data de emissao.';
        case 'payment_invalid_document':
            return 'O recebimento so pode ser registado para documentos emitidos.';
        case 'payment_invalid_amount':
            return 'O valor do recebimento e invalido.';
        case 'payment_recorded':
            return 'Recebimento registado com sucesso.';
        case 'payment_amount_exceeds_balance':
            return 'O valor do recebimento nao pode exceder o saldo pendente.';
        case 'payment_not_found':
            return 'Recebimento nao encontrado.';
        case 'product_service_created':
            return 'Produto/Servico criado com sucesso.';
        case 'product_service_updated':
            return 'Produto/Servico atualizado com sucesso.';
        case 'product_service_deleted':
            return 'Produto/Servico removido com sucesso.';
        case 'product_service_invalid_data':
            return 'Preencha os campos obrigatorios do item.';
        case 'product_service_delete_invalid':
            return 'Nao foi possivel remover o item. ID invalido.';
        case 'inventory_movement_saved':
            return 'Movimento de stock registado com sucesso.';
        case 'inventory_invalid_movement':
            return 'Verifique os dados do movimento de stock.';
        case 'inventory_insufficient_stock':
            return 'Stock insuficiente para concluir a operacao.';
        case 'inventory_issue_failed':
            return 'Nao foi possivel actualizar o stock deste documento.';
        default:
            return '';
    }
}

// Mostra notificacao baseada na query string.
function tps_render_notice_from_query() {
    static $rendered = false;
    if ( $rendered ) {
        return;
    }

    $notice_code = isset( $_GET['tps_notice'] ) ? sanitize_key( wp_unslash( $_GET['tps_notice'] ) ) : '';
    if ( '' === $notice_code ) {
        return;
    }

    $notice_type   = isset( $_GET['tps_notice_type'] ) ? sanitize_key( wp_unslash( $_GET['tps_notice_type'] ) ) : 'info';
    $allowed_types = array( 'success', 'error', 'warning', 'info' );
    if ( ! in_array( $notice_type, $allowed_types, true ) ) {
        $notice_type = 'info';
    }

    $message = isset( $_GET['tps_notice_message'] ) ? sanitize_text_field( wp_unslash( $_GET['tps_notice_message'] ) ) : '';
    if ( '' === $message ) {
        $message = tps_get_notice_message( $notice_code );
    }
    if ( '' === $message ) {
        return;
    }

    $rendered = true;
    echo '<div class="notice notice-' . esc_attr( $notice_type ) . ' is-dismissible tps-top-notice"><p>' . esc_html( $message ) . '</p></div>';
}

// Forca exibicao no topo da pagina admin.
add_action( 'in_admin_header', 'tps_render_notice_from_query', 1 );
// Fallback para ecras onde o hook acima nao dispare.
add_action( 'admin_notices', 'tps_render_notice_from_query', 1 );

// Retorna linhas por pagina (global).
function tps_get_per_page() {
    $settings = get_option( 'tps_settings', array() );

    $per_page = isset( $settings['per_page'] ) ? (int) $settings['per_page'] : 20;

    if ( $per_page < 5 ) {
        $per_page = 5;
    }

    if ( $per_page > 200 ) {
        $per_page = 200;
    }

    return $per_page;
}

// Resolve URL versionada de um asset do plugin.
function tps_get_asset_url( $relative_path ) {
    return TPS_PLUGIN_URL . ltrim( (string) $relative_path, '/\\' );
}

// Resolve caminho absoluto de um asset do plugin.
function tps_get_asset_path( $relative_path ) {
    return TPS_PLUGIN_PATH . ltrim( (string) $relative_path, '/\\' );
}

// Resolve versao do asset via filemtime.
function tps_get_asset_version( $relative_path ) {
    $path = tps_get_asset_path( $relative_path );

    return file_exists( $path ) ? (string) filemtime( $path ) : '1.0.0';
}
