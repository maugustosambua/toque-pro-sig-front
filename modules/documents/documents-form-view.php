<?php
// Impede acesso directo
if (! defined('ABSPATH')) {
    exit;
}

$document_id = isset($_GET['document_id']) ? (int) $_GET['document_id'] : 0;
$back_url = admin_url('admin.php?page=tps-documents');
?>

<div class="wrap tps-document-form">

<?php
if (! $document_id) :
    $types = TPS_Documents_Model::types();
    $default_type = isset($_GET['document_type']) ? sanitize_text_field(wp_unslash($_GET['document_type'])) : '';
    if (! array_key_exists($default_type, $types)) {
        $default_type = '';
    }
    $has_customers = TPS_Customers_Model::count_customers() > 0;
    ?>
    <section class="tps-header">
        <div class="tps-header-row">
            <a class="tps-back-btn" href="<?php echo esc_url($back_url); ?>">
                <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
                <span>Voltar</span>
            </a>
            <div class="tps-header-content">
                <h1><span class="dashicons dashicons-media-document tps-icon" aria-hidden="true"></span>Criar Documento</h1>
                <p class="tps-subtitle">Inicie um rascunho e complete as linhas antes de emitir.</p>
            </div>
        </div>
    </section>

    <section class="tps-card">
        <?php if (! $has_customers) : ?>
            <div class="notice notice-warning">
                <p>Nenhum cliente encontrado. Crie pelo menos um cliente antes de criar documentos.</p>
            </div>
        <?php endif; ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('tps_save_document'); ?>
            <input type="hidden" name="action" value="tps_save_document">

            <div class="tps-grid">
                <div>
                    <label for="tps-document-type">Tipo</label>
                    <select id="tps-document-type" name="document_type" required>
                        <?php foreach ($types as $key => $label) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($default_type, $key); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="tps-customer-search">Cliente</label>
                    <div class="tps-search-wrap">
                        <input
                            id="tps-customer-search"
                            type="text"
                            autocomplete="off"
                            placeholder="Pesquisar por nome, NUIT, e-mail ou telefone"
                            <?php disabled(! $has_customers); ?>
                        >
                        <input id="tps-customer-id" type="hidden" name="customer_id" required>
                        <div id="tps-customer-results" class="tps-search-results" hidden></div>
                    </div>
                    <div id="tps-selected-customer" class="tps-selected-customer"></div>
                </div>
                <div>
                    <label for="tps-issue-date">Data de Emissao</label>
                    <input id="tps-issue-date" type="date" name="issue_date" value="<?php echo esc_attr(date('Y-m-d')); ?>">
                </div>
            </div>

            <p class="tps-mt-14">
                <?php submit_button('Criar Rascunho', 'primary', 'submit', false, ! $has_customers ? array( 'disabled' => 'disabled' ) : array()); ?>
            </p>
        </form>
    </section>
    <?php
    return;
endif;

$document = TPS_Documents_Model::get($document_id);
$lines = TPS_Document_Lines_Model::get_by_document($document_id);
$subtotal = TPS_Document_Lines_Model::document_total($document_id);
$tax_amount = tps_calculate_iva($subtotal);
$grand_total = TPS_Document_Lines_Model::document_total_with_tax($document_id);
$type_labels = TPS_Documents_Model::types();
$status_labels = TPS_Documents_Model::statuses();

$status_class = 'tps-badge-draft';
if (($document->status ?? '') === 'issued') {
    $status_class = 'tps-badge-issued';
}
if (($document->status ?? '') === 'cancelled') {
    $status_class = 'tps-badge-cancelled';
}
?>

    <section class="tps-header">
        <div class="tps-header-row">
            <a class="tps-back-btn" href="<?php echo esc_url($back_url); ?>">
                <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
                <span>Voltar</span>
            </a>
            <div class="tps-header-content">
                <h1><span class="dashicons dashicons-edit-page tps-icon" aria-hidden="true"></span>Editar Documento</h1>
                <p class="tps-subtitle">Gerir linhas, totais e acoes do ciclo de vida deste documento.</p>
            </div>
        </div>
    </section>

    <section class="tps-grid-2">
        <article class="tps-card">
            <h2 class="tps-title-sm"><span class="dashicons dashicons-media-text tps-icon" aria-hidden="true"></span>Detalhes do Documento</h2>
            <ul class="tps-kv">
                <li><span>Tipo</span><strong><?php echo esc_html((string) ($type_labels[$document->type] ?? $document->type)); ?></strong></li>
                <li><span>Numero</span><strong><?php echo esc_html((string) $document->number); ?></strong></li>
                <li><span>Cliente</span><strong><?php echo esc_html((string) ($document->customer_name ?: ('#' . (int) $document->customer_id))); ?></strong></li>
                <?php if (! empty($document->customer_nuit)) : ?>
                    <li><span>NUIT do Cliente</span><strong><?php echo esc_html((string) $document->customer_nuit); ?></strong></li>
                <?php endif; ?>
                <li><span>Data de Emissao</span><strong><?php echo esc_html((string) $document->issue_date); ?></strong></li>
                <li>
                    <span>Estado</span>
                    <strong><span class="tps-badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html((string) ($status_labels[$document->status] ?? $document->status)); ?></span></strong>
                </li>
            </ul>
        </article>

        <article class="tps-card">
            <h2 class="tps-title-sm"><span class="dashicons dashicons-chart-line tps-icon" aria-hidden="true"></span>Totais</h2>
            <ul class="tps-kv">
                <li><span>Subtotal</span><strong><?php echo esc_html(number_format($subtotal, 2)); ?></strong></li>
                <li><span>IVA (<?php echo esc_html(number_format(tps_get_iva_rate() * 100, 2)); ?>%)</span><strong><?php echo esc_html(number_format($tax_amount, 2)); ?></strong></li>
                <li><span>Total</span><strong><?php echo esc_html(number_format($grand_total, 2)); ?></strong></li>
            </ul>
        </article>
    </section>

    <section class="tps-card tps-table-shell">
        <h2 class="tps-title-sm"><span class="dashicons dashicons-editor-table tps-icon" aria-hidden="true"></span>Linhas</h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Descricao</th>
                    <th>Quantidade</th>
                    <th>Preco Unitario</th>
                    <th>Subtotal</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($lines) : ?>
                    <?php foreach ($lines as $line) : ?>
                        <tr>
                            <td><?php echo esc_html((string) $line->description); ?></td>
                            <td><?php echo esc_html((string) $line->quantity); ?></td>
                            <td><?php echo esc_html(number_format((float) $line->unit_price, 2)); ?></td>
                            <td><?php echo esc_html(number_format(TPS_Document_Lines_Model::line_total($line), 2)); ?></td>
                            <td>
                                <?php if ($document->status === 'draft') : ?>
                                    <a class="button button-small tps-btn-danger" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=tps_delete_line&line_id=' . (int) $line->id . '&document_id=' . (int) $document_id), 'tps_delete_line')); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span>Eliminar</a>
                                <?php else : ?>
                                    <span class="tps-muted">Bloqueado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5">Sem linhas ainda.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <?php if ($document->status === 'draft') : ?>
        <section class="tps-card">
            <h2 class="tps-title-sm"><span class="dashicons dashicons-plus-alt2 tps-icon" aria-hidden="true"></span>Adicionar Linha</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('tps_add_line'); ?>
                <input type="hidden" name="action" value="tps_add_line">
                <input type="hidden" name="document_id" value="<?php echo esc_attr((string) $document_id); ?>">
                <input id="tps-line-product-service-id" type="hidden" name="product_service_id" value="0">

                <div class="tps-grid">
                    <div>
                        <label for="tps-line-catalog-search">Item do Catalogo</label>
                        <div class="tps-search-wrap">
                            <input
                                id="tps-line-catalog-search"
                                type="text"
                                autocomplete="off"
                                placeholder="Pesquisar item por nome ou codigo"
                            >
                            <div id="tps-line-catalog-results" class="tps-search-results" hidden></div>
                        </div>
                        <div id="tps-line-selected-item" class="tps-selected-customer"></div>
                    </div>
                    <div>
                        <label for="tps-line-description">Descricao</label>
                        <input id="tps-line-description" type="text" name="description" required>
                    </div>
                    <div>
                        <label for="tps-line-quantity">Quantidade</label>
                        <input id="tps-line-quantity" type="number" step="0.01" name="quantity" value="1" required>
                    </div>
                    <div>
                        <label for="tps-line-unit-price">Preco Unitario</label>
                        <input id="tps-line-unit-price" type="number" step="0.01" name="unit_price" value="0" required>
                    </div>
                </div>

                <p class="tps-mt-14">
                    <?php submit_button('Adicionar Linha', 'primary', 'submit', false); ?>
                </p>
            </form>
        </section>
    <?php else : ?>
        <section class="tps-card">
            <p class="tps-muted"><em>Este documento nao e editavel no estado atual.</em></p>
        </section>
    <?php endif; ?>

    <section class="tps-card">
        <h2 class="tps-title-sm"><span class="dashicons dashicons-admin-tools tps-icon" aria-hidden="true"></span>Acoes</h2>
        <div class="tps-actions">
            <?php if ($document->status === 'draft') : ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('tps_issue_document'); ?>
                    <input type="hidden" name="action" value="tps_issue_document">
                    <input type="hidden" name="document_id" value="<?php echo esc_attr((string) $document_id); ?>">
                    <button class="button button-primary"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>Emitir Documento</button>
                </form>
            <?php endif; ?>

            <?php if ($document->status === 'issued') : ?>
                <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=tps_download_document_pdf&document_id=' . (int) $document_id), 'tps_download_document_pdf')); ?>"><span class="dashicons dashicons-download" aria-hidden="true"></span>Transferir PDF</a>
                <a class="button tps-btn-danger" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=tps_cancel_document&document_id=' . (int) $document_id), 'tps_cancel_document')); ?>" onclick="return confirm('Cancelar este documento?');"><span class="dashicons dashicons-dismiss" aria-hidden="true"></span>Cancelar Documento</a>
            <?php else : ?>
                <span class="tps-muted">O PDF e o cancelamento so ficam disponiveis apos a emissao.</span>
            <?php endif; ?>
        </div>
    </section>

</div>