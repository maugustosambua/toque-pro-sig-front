<?php
if (! defined('ABSPATH')) {
    exit;
}

$counts = TPS_Documents_Model::count_by_status();
$ajax_list_nonce = wp_create_nonce('tps_ajax_documents_list');
$export_base_url = wp_nonce_url(admin_url('admin-post.php?action=tps_export_documents'), 'tps_export_documents');
?>

<style>
    .tps-documents-modern .tps-stats { grid-template-columns: repeat(4, minmax(120px, 1fr)); }
    .tps-documents-modern .tps-toolbar { grid-template-columns: 1fr auto auto auto; }
    .tps-documents-modern .tps-badge { display: inline-block; padding: 3px 8px; border-radius: 999px; font-size: 12px; border: 1px solid; }
    .tps-documents-modern .tps-badge-draft { background:#fff8e5; color:#8a6d00; border-color:#f1d48b; }
    .tps-documents-modern .tps-badge-issued { background:#e7f7ed; color:#1f7a39; border-color:#9ad3ac; }
    .tps-documents-modern .tps-badge-cancelled { background:#fde8e8; color:#a61b1b; border-color:#f1a6a6; }
    @media (max-width: 980px) { .tps-documents-modern .tps-toolbar { grid-template-columns: 1fr; } .tps-documents-modern .tps-stats { grid-template-columns: 1fr; } }
</style>

<div class="wrap tps-documents-modern">
    <section class="tps-header">
        <div>
            <h1><span class="dashicons dashicons-media-document tps-icon" aria-hidden="true"></span>Documentos</h1>
            <p class="tps-subtitle">Lista de documentos com filtros e carregamento em tempo real via AJAX.</p>
        </div>
        <div class="tps-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=tps-documents-add')); ?>" class="tps-btn"><span class="dashicons dashicons-plus-alt2 tps-icon" aria-hidden="true"></span>Adicionar Documento</a>
            <a id="tps-doc-export" href="<?php echo esc_url($export_base_url); ?>" class="tps-btn tps-btn-secondary"><span class="dashicons dashicons-download tps-icon" aria-hidden="true"></span>Exportar</a>
        </div>
    </section>

    <section class="tps-stats">
        <article class="tps-stat">
            <p class="tps-stat-head"><span class="dashicons dashicons-media-document tps-stat-icon" aria-hidden="true"></span><span class="tps-stat-label">Todos</span></p>
            <p class="tps-stat-value"><?php echo esc_html((string) (int) ($counts['all'] ?? 0)); ?></p>
        </article>
        <article class="tps-stat">
            <p class="tps-stat-head"><span class="dashicons dashicons-edit-page tps-stat-icon" aria-hidden="true"></span><span class="tps-stat-label">Rascunho</span></p>
            <p class="tps-stat-value"><?php echo esc_html((string) (int) ($counts['draft'] ?? 0)); ?></p>
        </article>
        <article class="tps-stat">
            <p class="tps-stat-head"><span class="dashicons dashicons-yes-alt tps-stat-icon" aria-hidden="true"></span><span class="tps-stat-label">Emitido</span></p>
            <p class="tps-stat-value"><?php echo esc_html((string) (int) ($counts['issued'] ?? 0)); ?></p>
        </article>
        <article class="tps-stat">
            <p class="tps-stat-head"><span class="dashicons dashicons-dismiss tps-stat-icon" aria-hidden="true"></span><span class="tps-stat-label">Cancelado</span></p>
            <p class="tps-stat-value"><?php echo esc_html((string) (int) ($counts['cancelled'] ?? 0)); ?></p>
        </article>
    </section>

    <section class="tps-toolbar">
        <input id="tps-doc-search" class="tps-search" type="search" placeholder="Pesquisar por número, tipo ou cliente">
        <select id="tps-doc-type" class="tps-select">
            <option value="">Todos os tipos</option>
            <option value="invoice">Fatura</option>
            <option value="vd">Venda a Dinheiro</option>
            <option value="quotation">Cotação</option>
        </select>
        <select id="tps-doc-status" class="tps-select">
            <option value="">Todos os estados</option>
            <option value="draft">Rascunho</option>
            <option value="issued">Emitido</option>
            <option value="cancelled">Cancelado</option>
        </select>
        <select id="tps-doc-sort" class="tps-select">
            <option value="date">Ordenar por data</option>
            <option value="name">Ordenar por nome</option>
            <option value="city">Ordenar por morada</option>
        </select>
    </section>

    <section class="tps-table-shell">
        <table>
            <thead>
                <tr>
                    <th>Número</th><th>Tipo</th><th>Cliente</th><th>Estado</th><th>Data de Emissão</th><th>Subtotal</th><th>IVA</th><th>Total</th><th>Ações</th>
                </tr>
            </thead>
            <tbody id="tps-doc-tbody"></tbody>
        </table>
        <div id="tps-doc-empty" class="tps-empty" hidden>Nenhum documento encontrado.</div>
        <div class="tps-pagination">
            <button id="tps-doc-prev" class="tps-page-btn"><span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>Anterior</button>
            <span id="tps-doc-page">Página 1</span>
            <button id="tps-doc-next" class="tps-page-btn">Seguinte<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></button>
        </div>
    </section>
</div>

<script>
(function () {
    const tbody = document.getElementById('tps-doc-tbody');
    const empty = document.getElementById('tps-doc-empty');
    const prevBtn = document.getElementById('tps-doc-prev');
    const nextBtn = document.getElementById('tps-doc-next');
    const pageLabel = document.getElementById('tps-doc-page');
    const searchInput = document.getElementById('tps-doc-search');
    const typeSelect = document.getElementById('tps-doc-type');
    const statusSelect = document.getElementById('tps-doc-status');
    const sortSelect = document.getElementById('tps-doc-sort');
    const exportBtn = document.getElementById('tps-doc-export');
    const ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
    const nonce = <?php echo wp_json_encode($ajax_list_nonce); ?>;
    const exportBaseUrl = (<?php echo wp_json_encode($export_base_url); ?>).replace(/&amp;/g, '&');
    let state = { paged: 1, search: '', doc_type: '', status: '', sort: 'date' };
    let totalPages = 1;
    let timer = null;

    function esc(v) {
        return String(v || '').replace(/[&<>"']/g, function (m) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
        });
    }

    function badge(status) {
        if (status === 'issued') return '<span class="tps-badge tps-badge-issued">Emitido</span>';
        if (status === 'cancelled') return '<span class="tps-badge tps-badge-cancelled">Cancelado</span>';
        return '<span class="tps-badge tps-badge-draft">Rascunho</span>';
    }

    function typeLabel(type) {
        if (type === 'invoice') return 'Fatura';
        if (type === 'vd') return 'Venda a Dinheiro';
        if (type === 'quotation') return 'Cotação';
        return type;
    }

    function typeIcon(type) {
        if (type === 'invoice') return 'dashicons-media-spreadsheet';
        if (type === 'vd') return 'dashicons-tickets-alt';
        if (type === 'quotation') return 'dashicons-media-text';
        return 'dashicons-media-document';
    }

    function updateExportUrl() {
        if (!exportBtn) return;
        const url = new URL(exportBaseUrl, window.location.origin);
        if (state.search) url.searchParams.set('search', state.search);
        if (state.doc_type) url.searchParams.set('doc_type', state.doc_type);
        if (state.status) url.searchParams.set('status', state.status);
        if (state.sort) url.searchParams.set('sort', state.sort);
        exportBtn.href = url.pathname + url.search;
    }

    async function loadRows() {
        const params = new URLSearchParams({
            action: 'tps_ajax_documents_list',
            nonce: nonce,
            paged: String(state.paged),
            search: state.search,
            doc_type: state.doc_type,
            status: state.status,
            sort: state.sort
        });
        const res = await fetch(ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' });
        if (!res.ok) return;
        const payload = await res.json();
        if (!payload || !payload.success) return;
        const data = payload.data || {};
        const rows = data.rows || [];
        totalPages = data.total_pages || 1;
        state.paged = data.current_page || 1;

        tbody.innerHTML = rows.map(function (r) {
            const customer = r.customer_name ? r.customer_name : ('#' + r.customer_id);
            const iconClass = typeIcon(r.type);
            let actions = '<a class="tps-row-btn" href="' + esc(r.edit_url) + '"><span class="dashicons dashicons-edit" aria-hidden="true"></span>Editar</a>';
            if (r.status === 'draft') actions += '<a class="tps-row-btn" href="' + esc(r.issue_url) + '"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>Emitir</a>';
            if (r.status === 'issued') actions += '<a class="tps-row-btn tps-row-btn-danger" href="' + esc(r.cancel_url) + '" onclick="return confirm(\'Cancelar este documento?\');"><span class="dashicons dashicons-dismiss" aria-hidden="true"></span>Cancelar</a>';
            return '<tr>'
                + '<td><strong>' + esc(r.number) + '</strong></td>'
                + '<td><span class="tps-cell-with-icon"><span class="dashicons ' + esc(iconClass) + '" aria-hidden="true"></span>' + esc(typeLabel(r.type)) + '</span></td>'
                + '<td>' + esc(customer) + '</td>'
                + '<td>' + badge(r.status) + '</td>'
                + '<td>' + esc(r.issue_date) + '</td>'
                + '<td>' + esc(r.subtotal) + '</td>'
                + '<td>' + esc(r.iva) + '</td>'
                + '<td>' + esc(r.total) + '</td>'
                + '<td>' + actions + '</td>'
                + '</tr>';
        }).join('');

        empty.hidden = rows.length > 0;
        pageLabel.textContent = 'Página ' + state.paged + ' de ' + totalPages;
        prevBtn.disabled = state.paged <= 1;
        nextBtn.disabled = state.paged >= totalPages;
        updateExportUrl();
    }

    searchInput.addEventListener('input', function () {
        if (timer) clearTimeout(timer);
        timer = setTimeout(function () {
            state.search = searchInput.value.trim();
            state.paged = 1;
            loadRows();
        }, 250);
    });

    typeSelect.addEventListener('change', function () {
        state.doc_type = typeSelect.value;
        state.paged = 1;
        loadRows();
    });

    statusSelect.addEventListener('change', function () {
        state.status = statusSelect.value;
        state.paged = 1;
        loadRows();
    });

    sortSelect.addEventListener('change', function () {
        state.sort = sortSelect.value || 'date';
        state.paged = 1;
        loadRows();
    });

    prevBtn.addEventListener('click', function () {
        if (state.paged > 1) { state.paged -= 1; loadRows(); }
    });
    nextBtn.addEventListener('click', function () {
        if (state.paged < totalPages) { state.paged += 1; loadRows(); }
    });

    loadRows();
    updateExportUrl();
})();
</script>

