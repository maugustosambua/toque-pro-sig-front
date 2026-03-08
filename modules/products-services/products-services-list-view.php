<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$total_items    = (int) TPS_Products_Services_Model::count_items( array() );
$total_products = (int) TPS_Products_Services_Model::count_items( array( 'type' => 'product' ) );
$total_services = (int) TPS_Products_Services_Model::count_items( array( 'type' => 'service' ) );
$critical_items = (int) TPS_Products_Services_Model::count_critical_products();
?>

<div class="wrap tps-products-modern">
    <section class="tps-header">
        <div>
            <h1><span class="dashicons dashicons-cart tps-icon" aria-hidden="true"></span>Produtos e Servicos</h1>
            <p class="tps-subtitle">Gestao de catalogo com filtros, stock e custo medio.</p>
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
            <p class="tps-stat-head"><span class="dashicons dashicons-admin-tools tps-stat-icon" aria-hidden="true"></span><span class="tps-stat-label">Servicos</span></p>
            <p class="tps-stat-value"><?php echo esc_html( (string) $total_services ); ?></p>
        </article>
        <article class="tps-stat">
            <p class="tps-stat-head"><span class="dashicons dashicons-warning tps-stat-icon" aria-hidden="true"></span><span class="tps-stat-label">Stock Critico</span></p>
            <p class="tps-stat-value"><?php echo esc_html( (string) $critical_items ); ?></p>
        </article>
    </section>

    <section class="tps-toolbar">
        <input id="tps-ps-search" class="tps-search" type="search" placeholder="Pesquisar por nome, codigo ou descricao">
        <select id="tps-ps-sort" class="tps-select">
            <option value="name">Ordenar por nome</option>
            <option value="price">Ordenar por preco</option>
            <option value="stock">Ordenar por stock</option>
            <option value="date">Ordenar por data</option>
        </select>
        <div class="tps-filters">
            <button class="tps-filter is-active" data-type=""><span class="dashicons dashicons-list-view" aria-hidden="true"></span>Todos</button>
            <button class="tps-filter" data-type="product"><span class="dashicons dashicons-products" aria-hidden="true"></span>Produtos</button>
            <button class="tps-filter" data-type="service"><span class="dashicons dashicons-admin-tools" aria-hidden="true"></span>Servicos</button>
        </div>
    </section>

    <section class="tps-table-shell">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Codigo</th>
                    <th>Unidade</th>
                    <th>Preco</th>
                    <th>Stock</th>
                    <th>Minimo</th>
                    <th>Custo Medio</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody id="tps-ps-tbody"></tbody>
        </table>
        <div id="tps-ps-empty" class="tps-empty" hidden>Nenhum item encontrado.</div>
        <div class="tps-pagination">
            <button id="tps-ps-prev" class="tps-page-btn">Anterior</button>
            <span id="tps-ps-page">Pagina 1</span>
            <button id="tps-ps-next" class="tps-page-btn">Seguinte</button>
        </div>
    </section>
</div>
