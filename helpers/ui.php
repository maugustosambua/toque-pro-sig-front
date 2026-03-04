<?php
// Impede acesso directo
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
            return 'Cliente não encontrado.';
        case 'customer_invalid_data':
            return 'Preencha os campos obrigatórios do cliente.';
        case 'customer_delete_invalid':
            return 'Não foi possível remover o cliente. ID inválido.';
        case 'import_no_file':
            return 'Nenhum ficheiro foi enviado.';
        case 'import_invalid_file_type':
            return 'Tipo de ficheiro inválido. Envie um CSV.';
        case 'import_unable_to_open':
            return 'Não foi possível abrir o ficheiro.';
        case 'import_empty_file':
            return 'O ficheiro enviado está vazio.';
        case 'import_invalid_header':
            return 'Cabeçalho CSV inválido. Verifique a primeira linha.';
        case 'import_missing_column':
            $column = isset( $_GET['col'] ) ? sanitize_text_field( wp_unslash( $_GET['col'] ) ) : '';
            if ( $column ) {
                return sprintf( 'Falta a coluna obrigatoria: %s.', $column );
            }
            return 'Falta uma coluna obrigatória no CSV.';
        case 'import_result':
            $imported           = isset( $_GET['imported'] ) ? max( 0, (int) $_GET['imported'] ) : 0;
            $skipped_duplicates = isset( $_GET['skipped_duplicates'] ) ? max( 0, (int) $_GET['skipped_duplicates'] ) : 0;
            $skipped_invalid    = isset( $_GET['skipped_invalid'] ) ? max( 0, (int) $_GET['skipped_invalid'] ) : 0;
            return sprintf(
                'Importação concluída: %d importados, %d duplicados ignorados e %d inválidos ignorados.',
                $imported,
                $skipped_duplicates,
                $skipped_invalid
            );
        case 'document_invalid_type':
            return 'Tipo de documento inválido.';
        case 'document_invalid_customer':
            return 'Cliente inválido. Selecione um cliente existente.';
        case 'document_draft_created':
            return 'Rascunho do documento criado com sucesso.';
        case 'document_not_found':
            return 'Documento não encontrado.';
        case 'document_line_added':
            return 'Linha adicionada com sucesso.';
        case 'document_line_deleted':
            return 'Linha removida com sucesso.';
        case 'document_line_invalid':
            return 'Não foi possível remover a linha. Dados inválidos.';
        case 'document_locked':
            return 'Este documento está bloqueado para edição.';
        case 'document_issued':
            return 'Documento emitido com sucesso.';
        case 'document_issue_no_lines':
            return 'Adicione pelo menos uma linha antes de emitir o documento.';
        case 'document_issue_invalid_status':
            return 'Só é possível emitir documentos em rascunho.';
        case 'document_cancelled':
            return 'Documento cancelado com sucesso.';
        case 'document_cancel_invalid_status':
            return 'Só é possível cancelar documentos emitidos.';
        case 'product_service_created':
            return 'Produto/Serviço criado com sucesso.';
        case 'product_service_updated':
            return 'Produto/Serviço atualizado com sucesso.';
        case 'product_service_deleted':
            return 'Produto/Serviço removido com sucesso.';
        case 'product_service_invalid_data':
            return 'Preencha os campos obrigatórios do item.';
        case 'product_service_delete_invalid':
            return 'Não foi possível remover o item. ID inválido.';
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

// Padroniza espacamento dos notices nas views do plugin.
function tps_admin_notice_spacing_styles() {
    ?>
    <style>
    .tps-customers-modern .notice,
    .tps-documents-modern .notice,
    .tps-customer-form .notice,
    .tps-products-modern .notice,
    .tps-product-form .notice,
    .tps-document-form .notice,
    .tps-import-modern .notice,
    .tps-settings-modern .notice {
        width: 100%;
        box-sizing: border-box;
        margin: 12px 0 16px;
    }
    </style>
    <?php
}
add_action( 'admin_head', 'tps_admin_notice_spacing_styles', 1 );

// Garante posicao visual dos feedbacks logo abaixo do titulo/cabecalho.
function tps_position_notice_below_title() {
    $notice_code = isset( $_GET['tps_notice'] ) ? sanitize_key( wp_unslash( $_GET['tps_notice'] ) ) : '';
    if ( '' === $notice_code ) {
        return;
    }
    ?>
    <script>
    (function () {
        function placeNoticeBelowTitle() {
            var notice = document.querySelector('.tps-top-notice');
            var content = document.getElementById('wpbody-content');
            var wrap = content ? content.querySelector('.wrap') : null;
            if (!notice || !wrap || !wrap.parentNode) {
                return false;
            }

            // Em telas modernas, prefere posicionar abaixo do cabecalho visual.
            var header = wrap.querySelector('.tps-header');
            if (header && header.parentNode) {
                if (header.nextElementSibling !== notice) {
                    header.parentNode.insertBefore(notice, header.nextSibling);
                }
                return true;
            }

            // Fallback: posiciona logo abaixo do primeiro titulo da pagina.
            var title = wrap.querySelector('h1');
            if (title && title.parentNode) {
                if (title.nextElementSibling !== notice) {
                    title.parentNode.insertBefore(notice, title.nextSibling);
                }
                return true;
            }

            // Ultimo fallback: inicio do conteudo do wrap.
            if (wrap.firstChild !== notice) {
                wrap.insertBefore(notice, wrap.firstChild);
            }
            return true;
        }

        placeNoticeBelowTitle();

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', placeNoticeBelowTitle);
        } else {
            placeNoticeBelowTitle();
        }

        var content = document.getElementById('wpbody-content');
        if (content && window.MutationObserver) {
            var observer = new MutationObserver(function () {
                placeNoticeBelowTitle();
            });
            observer.observe(content, { childList: true, subtree: true });
            setTimeout(function () { observer.disconnect(); }, 5000);
        }
    })();
    </script>
    <style>
    .tps-top-notice {
        display: block;
        width: 100%;
        box-sizing: border-box;
        margin: 12px 0 16px;
    }
    </style>
    <?php
}
add_action( 'admin_footer', 'tps_position_notice_below_title', 1 );

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
