<?php // modules/customers/customers-list-view.php
if (! defined('ABSPATH')) {
    exit;
}

$total_customers = (int) TPS_Customers_Model::count_customers(array());
$individual_customers = (int) TPS_Customers_Model::count_customers(array( 'type' => 'individual' ));
$company_customers = (int) TPS_Customers_Model::count_customers(array( 'type' => 'company' ));
$export_base_url = wp_nonce_url(add_query_arg('action', 'tps_export_customers', tps_get_action_url()), 'tps_export_customers');
?>

<div class="wrap tps-customers-modern">
    <section class="tps-header">
        <div class="tps-title-row">
            <div>
                <h1>Clientes</h1>
                <p class="tps-subtitle">Lista de clientes com filtros e paginacao em tempo real via AJAX.</p>
            </div>
            <div class="tps-actions">
                <a href="<?php echo esc_url(tps_get_page_url('tps-customers-add')); ?>" class="tps-btn tps-btn-primary"><span class="dashicons dashicons-plus-alt2 tps-icon" aria-hidden="true"></span>Adicionar Cliente</a>
                <a href="<?php echo esc_url(tps_get_page_url('tps-customers-import')); ?>" class="tps-btn tps-btn-secondary"><span class="dashicons dashicons-upload tps-icon" aria-hidden="true"></span>Importar</a>
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
        <div>
            <input id="tps-customers-search" class="tps-search tps-input" type="search" placeholder="Pesquisar por nome, NUIT, e-mail ou telefone">
        </div>
        <div>
            <select id="tps-customers-sort" class="tps-select">
            <option value="name">Ordenar por nome</option>
            <option value="city">Ordenar por morada</option>
            <option value="date">Ordenar por data</option>
            </select>
        </div>
        <div>
        <div class="tps-filters">
            <button type="button" class="tps-filter is-active" data-type=""><span class="dashicons dashicons-list-view" aria-hidden="true"></span>Todos</button>
            <button type="button" class="tps-filter" data-type="individual"><span class="dashicons dashicons-admin-users" aria-hidden="true"></span>Particular</button>
            <button type="button" class="tps-filter" data-type="company"><span class="dashicons dashicons-building" aria-hidden="true"></span>Empresa</button>
        </div>
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
            <button id="tps-customers-prev" type="button" class="tps-page-btn"><span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>Anterior</button>
            <span id="tps-customers-page">Página 1</span>
            <button id="tps-customers-next" type="button" class="tps-page-btn">Seguinte<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></button>
        </div>
    </section>
</div>

