<?php // modules/customers/customers-list-view.php
if (! defined('ABSPATH')) {
    exit;
}

$total_customers = (int) TPS_Customers_Model::count_customers(array());
$individual_customers = (int) TPS_Customers_Model::count_customers(array( 'type' => 'individual' ));
$company_customers = (int) TPS_Customers_Model::count_customers(array( 'type' => 'company' ));
$ajax_list_nonce = wp_create_nonce('tps_ajax_customers_list');
$export_base_url = wp_nonce_url(admin_url('admin-post.php?action=tps_export_customers'), 'tps_export_customers');
?>

<style>
    .tps-customers-modern .tps-stats { grid-template-columns: repeat(3, minmax(120px, 1fr)); }
    .tps-customers-modern .tps-toolbar { grid-template-columns: 1fr auto auto; }
    @media (max-width: 960px) { .tps-customers-modern .tps-toolbar { grid-template-columns: 1fr; } .tps-customers-modern .tps-stats { grid-template-columns: 1fr; } }
</style>

<div class="wrap tps-customers-modern">
    <section class="tps-header">
        <div class="tps-title-row">
            <div>
                <h1><span class="dashicons dashicons-groups tps-icon" aria-hidden="true"></span>Clientes</h1>
                <p class="tps-subtitle">Lista de clientes com filtros e paginacao em tempo real via AJAX.</p>
            </div>
            <div class="tps-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=tps-customers-add')); ?>" class="tps-btn tps-btn-primary"><span class="dashicons dashicons-plus-alt2 tps-icon" aria-hidden="true"></span>Adicionar Cliente</a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=tps-customers-import')); ?>" class="tps-btn tps-btn-secondary"><span class="dashicons dashicons-upload tps-icon" aria-hidden="true"></span>Importar</a>
                <a id="tps-customers-export" href="<?php echo esc_url($export_base_url); ?>" class="tps-btn tps-btn-secondary"><span class="dashicons dashicons-download tps-icon" aria-hidden="true"></span>Exportar</a>
            </div>
        </div>
    </section>

    <section class="tps-stats">
        <article class="tps-stat">
            <p class="tps-stat-head"><span class="dashicons dashicons-groups tps-stat-icon" aria-hidden="true"></span><span class="tps-stat-label">Total</span></p>
            <p class="tps-stat-value"><?php echo esc_html((string) $total_customers); ?></p>
        </article>
        <article class="tps-stat">
            <p class="tps-stat-head"><span class="dashicons dashicons-admin-users tps-stat-icon" aria-hidden="true"></span><span class="tps-stat-label">Particular</span></p>
            <p class="tps-stat-value"><?php echo esc_html((string) $individual_customers); ?></p>
        </article>
        <article class="tps-stat">
            <p class="tps-stat-head"><span class="dashicons dashicons-building tps-stat-icon" aria-hidden="true"></span><span class="tps-stat-label">Empresa</span></p>
            <p class="tps-stat-value"><?php echo esc_html((string) $company_customers); ?></p>
        </article>
    </section>

    <section class="tps-toolbar">
        <input id="tps-customers-search" class="tps-search" type="search" placeholder="Pesquisar por nome, NUIT, e-mail ou telefone">
        <select id="tps-customers-sort" class="tps-select">
            <option value="name">Ordenar por nome</option>
            <option value="city">Ordenar por morada</option>
            <option value="date">Ordenar por data</option>
        </select>
        <div class="tps-filters">
            <button class="tps-filter is-active" data-type=""><span class="dashicons dashicons-list-view" aria-hidden="true"></span>Todos</button>
            <button class="tps-filter" data-type="individual"><span class="dashicons dashicons-admin-users" aria-hidden="true"></span>Particular</button>
            <button class="tps-filter" data-type="company"><span class="dashicons dashicons-building" aria-hidden="true"></span>Empresa</button>
        </div>
    </section>

    <section class="tps-table-shell">
        <table>
            <thead>
                <tr>
                    <th>Nome</th><th>Tipo</th><th>NUIT</th><th>Telefone</th><th>Morada</th><th>Ações</th>
                </tr>
            </thead>
            <tbody id="tps-customers-tbody"></tbody>
        </table>
        <div id="tps-customers-empty" class="tps-empty" hidden>Nenhum cliente encontrado.</div>
        <div class="tps-pagination">
            <button id="tps-customers-prev" class="tps-page-btn"><span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>Anterior</button>
            <span id="tps-customers-page">Página 1</span>
            <button id="tps-customers-next" class="tps-page-btn">Seguinte<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></button>
        </div>
    </section>
</div>

<script>
(function () {
    const tbody = document.getElementById('tps-customers-tbody');
    const empty = document.getElementById('tps-customers-empty');
    const search = document.getElementById('tps-customers-search');
    const sortSelect = document.getElementById('tps-customers-sort');
    const prevBtn = document.getElementById('tps-customers-prev');
    const nextBtn = document.getElementById('tps-customers-next');
    const pageLabel = document.getElementById('tps-customers-page');
    const filters = Array.from(document.querySelectorAll('.tps-customers-modern .tps-filter'));
    const exportBtn = document.getElementById('tps-customers-export');
    const ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
    const nonce = <?php echo wp_json_encode($ajax_list_nonce); ?>;
    const exportBaseUrl = (<?php echo wp_json_encode($export_base_url); ?>).replace(/&amp;/g, '&');
    let state = { paged: 1, type: '', search: '', sort: 'name' };
    let timer = null;
    let totalPages = 1;

    function esc(v) {
        return String(v || '').replace(/[&<>"']/g, function (m) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
        });
    }

    function typeLabel(type) {
        if (type === 'individual') return 'Particular';
        if (type === 'company') return 'Empresa';
        return type;
    }

    function typeIcon(type) {
        if (type === 'individual') return 'dashicons-admin-users';
        if (type === 'company') return 'dashicons-building';
        return 'dashicons-id';
    }

    function updateExportUrl() {
        const url = new URL(exportBaseUrl, window.location.origin);
        if (state.type) url.searchParams.set('type', state.type);
        if (state.search) url.searchParams.set('s', state.search);
        if (state.sort) url.searchParams.set('sort', state.sort);
        exportBtn.href = url.pathname + url.search;
    }

    async function loadRows() {
        const params = new URLSearchParams({
            action: 'tps_ajax_customers_list',
            nonce: nonce,
            paged: String(state.paged),
            type: state.type,
            search: state.search,
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
            const iconClass = typeIcon(r.type);
            return '<tr>'
                + '<td><span class="tps-cell-with-icon"><span class="dashicons ' + esc(iconClass) + '" aria-hidden="true"></span><strong>' + esc(r.name) + '</strong></span></td>'
                + '<td>' + esc(typeLabel(r.type)) + '</td>'
                + '<td>' + esc(r.nuit) + '</td>'
                + '<td>' + esc(r.phone) + '</td>'
                + '<td>' + esc(r.city) + '</td>'
                + '<td class="tps-actions-col">'
                + '<a class="tps-row-btn" href="' + esc(r.edit_url) + '"><span class="dashicons dashicons-edit" aria-hidden="true"></span>Editar</a>'
                + '<a class="tps-row-btn" href="' + esc(r.export_url) + '"><span class="dashicons dashicons-download" aria-hidden="true"></span>Exportar</a>'
                + '<a class="tps-row-btn tps-row-btn-danger" href="' + esc(r.delete_url) + '" onclick="return confirm(\'Eliminar este cliente?\');"><span class="dashicons dashicons-trash" aria-hidden="true"></span>Eliminar</a>'
                + '</td>'
                + '</tr>';
        }).join('');

        empty.hidden = rows.length > 0;
        pageLabel.textContent = 'Página ' + state.paged + ' de ' + totalPages;
        prevBtn.disabled = state.paged <= 1;
        nextBtn.disabled = state.paged >= totalPages;
        updateExportUrl();
    }

    search.addEventListener('input', function () {
        if (timer) clearTimeout(timer);
        timer = setTimeout(function () {
            state.search = search.value.trim();
            state.paged = 1;
            loadRows();
        }, 250);
    });

    sortSelect.addEventListener('change', function () {
        state.sort = sortSelect.value || 'name';
        state.paged = 1;
        loadRows();
    });

    prevBtn.addEventListener('click', function () {
        if (state.paged > 1) { state.paged -= 1; loadRows(); }
    });
    nextBtn.addEventListener('click', function () {
        if (state.paged < totalPages) { state.paged += 1; loadRows(); }
    });

    filters.forEach(function (btn) {
        btn.addEventListener('click', function () {
            filters.forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            state.type = btn.getAttribute('data-type') || '';
            state.paged = 1;
            loadRows();
        });
    });

    loadRows();
})();
</script>
