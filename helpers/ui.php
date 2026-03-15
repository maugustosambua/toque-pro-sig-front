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
        case 'document_adjustment_original_required':
            return 'Informe o documento original para criar a nota de ajuste.';
        case 'document_adjustment_original_invalid':
            return 'O documento original deve existir, estar emitido e nao pode ser uma cotacao ou outra nota de ajuste.';
        case 'document_adjustment_lines_copied':
            return 'Rascunho de nota criado com as linhas copiadas do documento original.';
        case 'document_adjustment_lines_copy_failed':
            return 'Rascunho de nota criado, mas nao foi possivel copiar automaticamente as linhas do documento original.';
        case 'document_adjustment_lines_skipped':
            return 'Rascunho de nota criado sem copia automatica das linhas. Adicione as linhas manualmente.';
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
        case 'document_line_tax_mode_invalid':
            return 'Modo fiscal da linha invalido.';
        case 'document_line_exemption_code_required':
            return 'Selecione um codigo fiscal de isencao/nao sujeicao.';
        case 'document_line_exemption_code_invalid':
            return 'Codigo fiscal de isencao/nao sujeicao invalido.';
        case 'document_line_exemption_reason_required':
            return 'Informe o motivo da isencao ou nao sujeicao na linha.';
        case 'document_line_fiscal_permission_denied':
            return 'Nao tem permissao para definir linhas isentas ou nao sujeitas.';
        case 'document_locked':
            return 'Este documento esta bloqueado para edicao.';
        case 'document_issued':
            return 'Documento emitido com sucesso.';
        case 'document_issue_no_lines':
            return 'Adicione pelo menos uma linha antes de emitir o documento.';
        case 'document_issue_invalid_status':
            return 'So e possivel emitir documentos em rascunho.';
        case 'document_issue_numbering_failed':
            return 'Nao foi possivel reservar o numero do documento. Tente novamente.';
        case 'document_cancelled':
            return 'Documento cancelado com sucesso.';
        case 'document_cancel_reason_required':
            return 'Informe o motivo do cancelamento.';
        case 'document_cancel_invalid_status':
            return 'So e possivel cancelar documentos emitidos.';
        case 'document_withholding_updated':
            return 'Retencao atualizada com sucesso.';
        case 'document_withholding_invalid':
            return 'A taxa de retencao deve estar entre 0 e 100.';
        case 'document_withholding_locked':
            return 'A retencao so pode ser alterada em documentos em rascunho.';
        case 'document_invalid_due_date':
            return 'A data de vencimento deve ser igual ou posterior a data de emissao.';
        case 'fiscal_export_invalid_config':
            return 'Configuracao fiscal incompleta para exportacao AT. Verifique nome da empresa, NUIT e versao do layout fiscal.';
        case 'fiscal_export_no_documents':
            return 'Nao existem documentos emitidos/cancelados para exportacao fiscal no filtro atual.';
        case 'fiscal_export_invalid_structure':
            return 'A estrutura do ficheiro fiscal AT falhou na validacao interna.';
        case 'fiscal_month_close_invalid_period':
            return 'Periodo fiscal invalido. Use o formato AAAA-MM.';
        case 'fiscal_month_close_failed':
            return 'Nao foi possivel concluir o fecho fiscal mensal.';
        case 'fiscal_month_closed':
            $period = isset( $_GET['period'] ) ? sanitize_text_field( wp_unslash( $_GET['period'] ) ) : '';
            return '' !== $period ? sprintf( 'Fecho fiscal mensal concluido para %s.', $period ) : 'Fecho fiscal mensal concluido com sucesso.';
        case 'fiscal_month_close_not_found':
            return 'Nao existe fecho fiscal para o periodo informado. Execute o fecho antes de exportar.';
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
        case 'user_created':
            return 'Utilizador criado com sucesso.';
        case 'user_updated':
            return 'Utilizador atualizado com sucesso.';
        case 'user_deleted':
            return 'Utilizador removido com sucesso.';
        case 'user_not_found':
            return 'Utilizador nao encontrado.';
        case 'user_invalid_data':
            return 'Preencha os campos obrigatorios do utilizador.';
        case 'user_duplicate_login':
            return 'O nome de utilizador ja existe.';
        case 'user_duplicate_email':
            return 'O email informado ja esta em uso.';
        case 'user_delete_invalid':
            return 'Nao foi possivel remover o utilizador. ID invalido.';
        case 'user_delete_current':
            return 'Nao pode remover a sua propria conta a partir desta area.';
        case 'user_delete_last_admin':
            return 'Nao pode remover o ultimo administrador do sistema.';
        default:
            return '';
    }
}

// Resolve os dados da notificacao com base na query string.
function tps_get_notice_data_from_query() {
    $notice_code = isset( $_GET['tps_notice'] ) ? sanitize_key( wp_unslash( $_GET['tps_notice'] ) ) : '';
    if ( '' === $notice_code ) {
        return null;
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
        return null;
    }

    return array(
        'type'    => $notice_type,
        'message' => $message,
    );
}

// Mostra notificacao baseada na query string.
function tps_render_notice_from_query() {
    static $rendered = false;
    if ( $rendered ) {
        return;
    }

    $notice = tps_get_notice_data_from_query();
    if ( empty( $notice ) ) {
        return;
    }

    $rendered = true;
    echo '<div class="tps-notice tps-top-notice tps-notice--' . esc_attr( $notice['type'] ) . '" role="status" aria-live="polite"><p class="tps-notice__message">' . esc_html( $notice['message'] ) . '</p><button type="button" class="tps-notice-close" aria-label="Fechar aviso">&times;</button></div>';
}

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

// Define o contexto frontend atual para montagem de URLs do plugin.
function tps_set_frontend_context( $base_url ) {
    $GLOBALS['tps_frontend_base_url'] = esc_url_raw( (string) $base_url );
}

// Limpa o contexto frontend atual.
function tps_clear_frontend_context() {
    unset( $GLOBALS['tps_frontend_base_url'] );
}

// Detecta a base da pagina frontend onde o shortcode do plugin esta montado.
function tps_detect_frontend_base_url() {
    $urls = array();

    if ( ! empty( $GLOBALS['tps_frontend_base_url'] ) ) {
        $urls[] = $GLOBALS['tps_frontend_base_url'];
    }

    $referer = wp_get_referer();
    if ( $referer ) {
        $urls[] = $referer;
    }

    if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
        $urls[] = home_url( wp_unslash( $_SERVER['REQUEST_URI'] ) );
    }

    $plugin_args = array(
        'tps_view',
        'tab',
        'customer_id',
        'document_id',
        'ps_id',
        'search',
        'movement_type',
        'tps_notice',
        'tps_notice_type',
        'tps_notice_message',
        'imported',
        'skipped_duplicates',
        'skipped_invalid',
        'col',
        'settings-updated',
        'payment_id',
        'audit_page',
        'event_type',
        'entity_type',
        'user_id',
        'from_date',
        'to_date',
    );

    foreach ( $urls as $candidate ) {
        if ( empty( $candidate ) || ! is_string( $candidate ) ) {
            continue;
        }

        $query = wp_parse_url( $candidate, PHP_URL_QUERY );
        parse_str( (string) $query, $args );

        if ( ! isset( $args['tps_view'] ) && empty( $GLOBALS['tps_frontend_base_url'] ) ) {
            continue;
        }

        return remove_query_arg( $plugin_args, $candidate );
    }

    return '';
}

// Indica se o plugin deve gerar links para o frontend em vez do wp-admin.
function tps_is_frontend_context() {
    return '' !== tps_detect_frontend_base_url();
}

// URL base do endpoint admin-post.
function tps_get_action_url() {
    return admin_url( 'admin-post.php' );
}

// URL base do endpoint admin-ajax.
function tps_get_ajax_url() {
    return admin_url( 'admin-ajax.php' );
}

// Monta URL de uma tela do plugin, respeitando contexto admin/frontend.
function tps_get_page_url( $page, $args = array() ) {
    $page = sanitize_key( (string) $page );

    if ( tps_is_frontend_context() ) {
        $base_url = tps_detect_frontend_base_url();
        $query    = array_merge( array( 'tps_view' => $page ), $args );

        return add_query_arg( $query, $base_url );
    }

    $query = array_merge( array( 'page' => $page ), $args );

    return add_query_arg( $query, admin_url( 'admin.php' ) );
}

// URL de uma aba das configuracoes.
function tps_get_settings_tab_url( $tab ) {
    return tps_get_page_url( 'tps-settings', array( 'tab' => sanitize_key( (string) $tab ) ) );
}

// Resolve a URL publica do app frontend com base na pagina que contem o shortcode.
function tps_get_frontend_app_url( $page = 'tps-dashboard', $args = array() ) {
    global $wpdb;

    $page = sanitize_key( (string) $page );

    $base_url = '';
    if ( ! empty( $GLOBALS['tps_frontend_base_url'] ) && is_string( $GLOBALS['tps_frontend_base_url'] ) ) {
        $base_url = $GLOBALS['tps_frontend_base_url'];
    }

    if ( '' === $base_url ) {
        $shortcode_like = '%' . $wpdb->esc_like( '[tps_frontend' ) . '%';
        $post_id        = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID
                FROM {$wpdb->posts}
                WHERE post_status = 'publish'
                    AND post_type IN ('page', 'post')
                    AND post_content LIKE %s
                ORDER BY post_type = 'page' DESC, ID ASC
                LIMIT 1",
                $shortcode_like
            )
        );

        if ( $post_id > 0 ) {
            $base_url = get_permalink( $post_id );
        }
    }

    if ( ! $base_url ) {
        return tps_get_page_url( $page, $args );
    }

    return add_query_arg( array_merge( array( 'tps_view' => $page ), $args ), $base_url );
}
