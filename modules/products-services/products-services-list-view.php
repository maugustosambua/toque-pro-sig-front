<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$total_items     = (int) TPS_Products_Services_Model::count_items( array() );
$total_products  = (int) TPS_Products_Services_Model::count_items( array( 'type' => 'product' ) );
$total_services  = (int) TPS_Products_Services_Model::count_items( array( 'type' => 'service' ) );
$ajax_list_nonce?>

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

