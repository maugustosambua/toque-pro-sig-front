<?php
// Impede acesso directo
if (! defined('ABSPATH')) {
    exit;
}

$document_id = isset($_GET['document_id']) ? (int) $_GET['document_id'] : 0;
$back_url = admin_url('admin.php?page=tps-documents');
?>

<style>
    .tps-document-form {
        --tps-panel: #ffffff;
        --tps-border: #dde5ef;
        --tps-text: #1a2433;
        --tps-text-muted: #5f6b7a;
        --tps-accent: #0f5ea8;
        margin-top: 14px;
    }
    .tps-document-form .tps-header {
        background: linear-gradient(115deg, #f8fbff 0%, #edf4fc 55%, #e4edf8 100%);
        border: 1px solid var(--tps-border);
        border-radius: 14px;
        padding: 18px 20px;
        margin-bottom: 16px;
    }
    .tps-document-form .tps-header-row {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .tps-document-form .tps-header-content {
        min-width: 0;
    }
    .tps-document-form h1 {
        margin: 0;
        color: var(--tps-text);
    }
    .tps-document-form .tps-back-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid var(--tps-border);
        border-radius: 999px;
        padding: 6px 12px;
        text-decoration: none;
        color: var(--tps-text);
        background: #fff;
        font-weight: 600;
        line-height: 1;
        transition: all .16s ease;
    }
    .tps-document-form .tps-back-btn:hover { border-color: #b8cbe3; background: #f6faff; color: #123f67; transform: translateY(-1px); }
    .tps-document-form .tps-back-btn:focus-visible { outline: 2px solid #0f5ea8; outline-offset: 2px; }
    .tps-document-form .tps-back-btn .dashicons {
        font-size: 16px;
        width: 16px;
        height: 16px;
    }
    .tps-document-form .tps-subtitle {
        margin: 6px 0 0;
        color: var(--tps-text-muted);
    }
    .tps-document-form .tps-icon {
        display: inline-flex;
        align-items: center;
        vertical-align: middle;
        margin-right: 4px;
    }
    .tps-document-form .tps-card {
        background: var(--tps-panel);
        border: 1px solid var(--tps-border);
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 14px;
        transition: all .16s ease;
    }
    .tps-document-form .tps-card:hover { border-color: #c7d5e7; box-shadow: 0 8px 16px rgba(26, 36, 51, .06); }
    .tps-document-form .tps-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(190px, 1fr));
        gap: 12px;
    }
    .tps-document-form .tps-grid-2 {
        display: grid;
        grid-template-columns: repeat(2, minmax(240px, 1fr));
        gap: 14px;
    }
    .tps-document-form label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
    }
    .tps-document-form input[type="text"],
    .tps-document-form input[type="number"],
    .tps-document-form input[type="date"],
    .tps-document-form select,
    .tps-document-form textarea {
        width: 100%;
        min-height: 38px;
        border: 1px solid #ccd7e3;
        border-radius: 8px;
        padding: 8px 10px;
    }
    .tps-document-form .tps-table-shell table {
        margin-top: 0;
    }
    .tps-document-form .tps-title-sm {
        margin: 0 0 12px;
        font-size: 16px;
        color: var(--tps-text);
    }
    .tps-document-form .tps-kv {
        margin: 0;
        display: grid;
        gap: 8px;
    }
    .tps-document-form .tps-kv li {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        padding-bottom: 8px;
        border-bottom: 1px solid #edf1f6;
        transition: all .16s ease;
    }
    .tps-document-form .tps-kv li:hover { background: #fbfdff; }
    .tps-document-form .tps-kv li:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }
    .tps-document-form .tps-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 999px;
        font-size: 12px;
        border: 1px solid;
    }
    .tps-document-form .tps-badge-draft {
        background: #fff8e5;
        color: #8a6d00;
        border-color: #f1d48b;
    }
    .tps-document-form .tps-badge-issued {
        background: #e7f7ed;
        color: #1f7a39;
        border-color: #9ad3ac;
    }
    .tps-document-form .tps-badge-cancelled {
        background: #fde8e8;
        color: #a61b1b;
        border-color: #f1a6a6;
    }
    .tps-document-form .tps-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }
    .tps-document-form .tps-muted {
        color: var(--tps-text-muted);
    }
    .tps-document-form .button {
        border-radius: 8px;
        transition: all .16s ease;
    }
    .tps-document-form .button:hover { transform: translateY(-1px); box-shadow: 0 8px 16px rgba(26, 36, 51, .10); }
    .tps-document-form .button:focus-visible { outline: 2px solid #0f5ea8; outline-offset: 2px; }
    .tps-document-form .tps-btn-danger {
        border-color: #e5b4ba !important;
        background: #fff2f4 !important;
        color: #ad2433 !important;
    }
    .tps-document-form .tps-btn-danger:hover,
    .tps-document-form .tps-btn-danger:focus {
        border-color: #d8939b !important;
        background: #fee8eb !important;
        color: #8f1d2a !important;
    }
    .tps-document-form .button .dashicons {
        width: 14px;
        height: 14px;
        font-size: 14px;
        line-height: 1;
        vertical-align: middle;
        margin-right: 4px;
    }
    .tps-document-form .tps-search-wrap {
        position: relative;
    }
    .tps-document-form .tps-search-results {
        position: absolute;
        z-index: 20;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        background: #fff;
        border: 1px solid #ccd7e3;
        border-radius: 8px;
        max-height: 220px;
        overflow-y: auto;
        box-shadow: 0 8px 24px rgba(26, 36, 51, 0.08);
    }
    .tps-document-form .tps-search-item {
        display: block;
        width: 100%;
        border: 0;
        background: transparent;
        text-align: left;
        padding: 8px 10px;
        cursor: pointer;
        transition: all .12s ease;
    }
    .tps-document-form .tps-search-item:hover {
        background: #f5f8fc;
    }
    .tps-document-form .tps-search-empty {
        padding: 8px 10px;
        color: var(--tps-text-muted);
    }
    .tps-document-form .tps-selected-customer {
        margin-top: 6px;
        color: var(--tps-text-muted);
        font-size: 12px;
    }
    @media (max-width: 960px) {
        .tps-document-form .tps-grid,
        .tps-document-form .tps-grid-2 {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="wrap tps-document-form">

<?php
if (! $document_id) :
    $types = TPS_Documents_Model::types();
    $default_type = isset($_GET['document_type']) ? sanitize_text_field(wp_unslash($_GET['document_type'])) : '';
    if (! array_key_exists($default_type, $types)) {
        $default_type = '';
    }
    $has_customers = TPS_Customers_Model::count_customers() > 0;
    $search_nonce = wp_create_nonce('tps_search_customers');
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

            <p style="margin-top:14px;">
                <?php submit_button('Criar Rascunho', 'primary', 'submit', false, ! $has_customers ? array( 'disabled' => 'disabled' ) : array()); ?>
            </p>
        </form>
    </section>
    <?php if ($has_customers) : ?>
    <script>
    (function () {
        const form = document.querySelector('.tps-document-form form');
        const searchInput = document.getElementById('tps-customer-search');
        const hiddenInput = document.getElementById('tps-customer-id');
        const resultsBox = document.getElementById('tps-customer-results');
        const selectedBox = document.getElementById('tps-selected-customer');
        const ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
        const nonce = <?php echo wp_json_encode($search_nonce); ?>;
        let timer = null;

        if (!searchInput || !hiddenInput || !resultsBox) {
            return;
        }

        function clearResults() {
            resultsBox.innerHTML = '';
            resultsBox.hidden = true;
        }

        function renderResults(items) {
            if (!items.length) {
                resultsBox.innerHTML = '<div class="tps-search-empty">Nenhum cliente encontrado.</div>';
                resultsBox.hidden = false;
                return;
            }

            resultsBox.innerHTML = '';
            items.forEach(function (item) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'tps-search-item';
                btn.textContent = item.label;
                btn.addEventListener('click', function () {
                    hiddenInput.value = String(item.id);
                    searchInput.value = item.label;
                    selectedBox.textContent = 'Cliente selecionado: ' + item.label;
                    clearResults();
                });
                resultsBox.appendChild(btn);
            });
            resultsBox.hidden = false;
        }

        async function fetchCustomers(term) {
            const params = new URLSearchParams({
                action: 'tps_search_customers',
                nonce: nonce,
                term: term
            });

            const response = await fetch(ajaxUrl + '?' + params.toString(), {
                method: 'GET',
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('A pesquisa falhou');
            }

            return response.json();
        }

        searchInput.addEventListener('input', function () {
            const term = searchInput.value.trim();
            hiddenInput.value = '';
            selectedBox.textContent = '';

            if (timer) {
                clearTimeout(timer);
            }

            if (term.length < 2) {
                clearResults();
                return;
            }

            timer = setTimeout(async function () {
                try {
                    const payload = await fetchCustomers(term);
                    if (!payload || !payload.success) {
                        clearResults();
                        return;
                    }
                    renderResults(payload.data || []);
                } catch (e) {
                    clearResults();
                }
            }, 250);
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('.tps-search-wrap')) {
                clearResults();
            }
        });

        if (form) {
            form.addEventListener('submit', function (event) {
                if (!hiddenInput.value) {
                    event.preventDefault();
                    selectedBox.textContent = 'Selecione um cliente nos resultados da pesquisa.';
                    searchInput.focus();
                }
            });
        }
    })();
    </script>
    <?php endif; ?>
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
$catalog_items = class_exists('TPS_Products_Services_Model')
    ? TPS_Products_Services_Model::get_items(
        array(
            'orderby'  => 'name',
            'order'    => 'ASC',
            'per_page' => 1000,
            'offset'   => 0,
        )
    )
    : array();
$catalog_items_payload = array();
foreach ($catalog_items as $catalog_item) {
    $catalog_label = (string) $catalog_item->name;
    if (! empty($catalog_item->sku)) {
        $catalog_label .= ' - ' . (string) $catalog_item->sku;
    }

    $catalog_items_payload[] = array(
        'id'    => (int) $catalog_item->id,
        'name'  => (string) $catalog_item->name,
        'price' => (float) $catalog_item->price,
        'label' => $catalog_label,
    );
}

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

                <p style="margin-top:14px;">
                    <?php submit_button('Adicionar Linha', 'primary', 'submit', false); ?>
                </p>
            </form>
        </section>
        <script>
        (function () {
            const itemSearchInput = document.getElementById('tps-line-catalog-search');
            const itemResultsBox = document.getElementById('tps-line-catalog-results');
            const itemSelectedBox = document.getElementById('tps-line-selected-item');
            const itemIdInput = document.getElementById('tps-line-product-service-id');
            const descriptionInput = document.getElementById('tps-line-description');
            const unitPriceInput = document.getElementById('tps-line-unit-price');
            const catalogItems = <?php echo wp_json_encode($catalog_items_payload); ?> || [];

            if (!itemSearchInput || !itemResultsBox || !itemSelectedBox || !itemIdInput || !descriptionInput || !unitPriceInput) {
                return;
            }

            function clearResults() {
                itemResultsBox.innerHTML = '';
                itemResultsBox.hidden = true;
            }

            function renderResults(items) {
                if (!items.length) {
                    itemResultsBox.innerHTML = '<div class="tps-search-empty">Nenhum item encontrado.</div>';
                    itemResultsBox.hidden = false;
                    return;
                }

                itemResultsBox.innerHTML = '';
                items.forEach(function (item) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'tps-search-item';
                    btn.textContent = item.label + ' (' + Number(item.price).toFixed(2) + ')';
                    btn.addEventListener('click', function () {
                        itemIdInput.value = String(item.id);
                        itemSearchInput.value = item.label;
                        itemSelectedBox.textContent = 'Item selecionado: ' + item.label;
                        descriptionInput.value = item.name || '';
                        unitPriceInput.value = Number(item.price).toFixed(2);
                        clearResults();
                    });
                    itemResultsBox.appendChild(btn);
                });
                itemResultsBox.hidden = false;
            }

            function filterItems(term) {
                const q = term.toLowerCase();
                return catalogItems.filter(function (item) {
                    return String(item.label || '').toLowerCase().includes(q)
                        || String(item.name || '').toLowerCase().includes(q);
                }).slice(0, 20);
            }

            itemSearchInput.addEventListener('input', function () {
                const term = itemSearchInput.value.trim();
                itemIdInput.value = '0';
                itemSelectedBox.textContent = '';

                if (term.length < 1) {
                    clearResults();
                    return;
                }

                renderResults(filterItems(term));
            });

            document.addEventListener('click', function (event) {
                if (!event.target.closest('#tps-line-catalog-search') && !event.target.closest('#tps-line-catalog-results')) {
                    clearResults();
                }
            });
        })();
        </script>
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


