<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$total_items     = (int) TPS_Products_Services_Model::count_items( array() );
$total_products  = (int) TPS_Products_Services_Model::count_items( array( 'type' => 'product' ) );
$total_services  = (int) TPS_Products_Services_Model::count_items( array( 'type' => 'service' ) );
$ajax_list_nonce = wp_create_nonce( 'tps_ajax_products_services_list' );
?>

<style>
    .tps-products-modern .tps-stats { grid-template-columns: repeat(3, minmax(120px, 1fr)); }
    .tps-products-modern .tps-toolbar { grid-template-columns: 1fr auto auto; }
    @media (max-width: 960px) {
        .tps-products-modern .tps-toolbar,
        .tps-products-modern .tps-stats {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="wrap tps-products-modern">
    <section class="tps-header">
        <div>
            <h1><span class="dashicons dashicons-cart tps-icon" aria-hidden="true"></span>Produtos e Serviços</h1>
            <p class="tps-subtitle">Gestão de catálogo com filtros e paginação via AJAX.</p>
        </div>
        <div class="tps-actions">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=tps-products-services-add' ) ); ?>" class="tps-btn"><span class="dashicons dashicons-plus-alt2 tps-icon" aria-hidden="true"></span>Adicionar Item</a>
        </div>
    </section>

    <section class="tps-stats">
        <article class="tps-stat">
            <p class="tps-stat-head"><span class="dashicons dashicons-chart-bar tps-stat-icon" aria-hidden="true"></span><span class="tps-stat-label">Total</span></p>
            <p class="tps-stat-value"><?php echo esc_html( (string) $total_items ); ?></p>
        </article>
        <article class="tps-stat">
            <p class="tps-stat-head"><span class="dashicons dashicons-products tps-stat-icon" aria-hidden="true"></span><span class="tps-stat-label">Produtos</span></p>
            <p class="tps-stat-value"><?php echo esc_html( (string) $total_products ); ?></p>
        </article>
        <article class="tps-stat">
            <p class="tps-stat-head"><span class="dashicons dashicons-admin-tools tps-stat-icon" aria-hidden="true"></span><span class="tps-stat-label">Serviços</span></p>
            <p class="tps-stat-value"><?php echo esc_html( (string) $total_services ); ?></p>
        </article>
    </section>

    <section class="tps-toolbar">
        <input id="tps-ps-search" class="tps-search" type="search" placeholder="Pesquisar por nome, código ou descrição">
        <select id="tps-ps-sort" class="tps-select">
            <option value="name">Ordenar por nome</option>
            <option value="price">Ordenar por preço</option>
            <option value="date">Ordenar por data</option>
        </select>
        <div class="tps-filters">
            <button class="tps-filter is-active" data-type=""><span class="dashicons dashicons-list-view" aria-hidden="true"></span>Todos</button>
            <button class="tps-filter" data-type="product"><span class="dashicons dashicons-products" aria-hidden="true"></span>Produtos</button>
            <button class="tps-filter" data-type="service"><span class="dashicons dashicons-admin-tools" aria-hidden="true"></span>Serviços</button>
        </div>
    </section>

    <section class="tps-table-shell">
        <table>
            <thead>
                <tr>
                    <th>Nome</th><th>Tipo</th><th>Código</th><th>Unidade</th><th>Preço</th><th>Ações</th>
                </tr>
            </thead>
            <tbody id="tps-ps-tbody"></tbody>
        </table>
        <div id="tps-ps-empty" class="tps-empty" hidden>Nenhum item encontrado.</div>
        <div class="tps-pagination">
            <button id="tps-ps-prev" class="tps-page-btn">Anterior</button>
            <span id="tps-ps-page">Página 1</span>
            <button id="tps-ps-next" class="tps-page-btn">Seguinte</button>
        </div>
    </section>
</div>

<script>
(function () {
    const tbody = document.getElementById('tps-ps-tbody');
    const empty = document.getElementById('tps-ps-empty');
    const search = document.getElementById('tps-ps-search');
    const sortSelect = document.getElementById('tps-ps-sort');
    const prevBtn = document.getElementById('tps-ps-prev');
    const nextBtn = document.getElementById('tps-ps-next');
    const pageLabel = document.getElementById('tps-ps-page');
    const filters = Array.from(document.querySelectorAll('.tps-products-modern .tps-filter'));
    const ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
    const nonce = <?php echo wp_json_encode( $ajax_list_nonce ); ?>;
    let state = { paged: 1, type: '', search: '', sort: 'name' };
    let totalPages = 1;
    let timer = null;

    function esc(v) {
        return String(v || '').replace(/[&<>"']/g, function (m) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
        });
    }

    function typeLabel(type) {
        if (type === 'product') return 'Produto';
        if (type === 'service') return 'Serviço';
        return type;
    }

    function typeIcon(type) {
        if (type === 'product') return 'dashicons-products';
        if (type === 'service') return 'dashicons-admin-tools';
        return 'dashicons-category';
    }

    async function loadRows() {
        const params = new URLSearchParams({
            action: 'tps_ajax_products_services_list',
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
                + '<td>' + esc(r.sku) + '</td>'
                + '<td>' + esc(r.unit) + '</td>'
                + '<td>' + esc(r.price) + '</td>'
                + '<td class="tps-actions-col">'
                + '<a class="tps-row-btn" href="' + esc(r.edit_url) + '"><span class="dashicons dashicons-edit" aria-hidden="true"></span>Editar</a>'
                + '<a class="tps-row-btn tps-row-btn-danger" href="' + esc(r.delete_url) + '" onclick="return confirm(\'Eliminar este item?\');"><span class="dashicons dashicons-trash" aria-hidden="true"></span>Eliminar</a>'
                + '</td>'
                + '</tr>';
        }).join('');

        empty.hidden = rows.length > 0;
        pageLabel.textContent = 'Página ' + state.paged + ' de ' + totalPages;
        prevBtn.disabled = state.paged <= 1;
        nextBtn.disabled = state.paged >= totalPages;
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
        if (state.paged > 1) {
            state.paged -= 1;
            loadRows();
        }
    });

    nextBtn.addEventListener('click', function () {
        if (state.paged < totalPages) {
            state.paged += 1;
            loadRows();
        }
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

