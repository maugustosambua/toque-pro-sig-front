<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$data = TPS_Dashboard_Controller::get_dashboard_data();
$kpis = $data['kpis'];

$format_money = static function ( $value ) {
    return number_format( (float) $value, 2, '.', ',' );
};

$format_int = static function ( $value ) {
    return number_format( (int) $value, 0, '.', ',' );
};

$calc_delta = static function ( $current, $previous ) {
    $current  = (float) $current;
    $previous = (float) $previous;

    if ( 0.0 === $previous ) {
        if ( 0.0 === $current ) {
            return array( 'label' => '0.0%', 'state' => 'neutral' );
        }
        return array( 'label' => '+100.0%', 'state' => 'up' );
    }

    $delta = ( ( $current - $previous ) / $previous ) * 100;
    if ( $delta > 0 ) {
        return array( 'label' => '+' . number_format( $delta, 1 ) . '%', 'state' => 'up' );
    }
    if ( $delta < 0 ) {
        return array( 'label' => number_format( $delta, 1 ) . '%', 'state' => 'down' );
    }
    return array( 'label' => '0.0%', 'state' => 'neutral' );
};

$revenue_delta = $calc_delta( $kpis['revenue_total_month'], $kpis['revenue_total_prev'] );
$issued_delta  = $calc_delta( $kpis['issued_count_month'], $kpis['issued_count_prev'] );
$status_labels = array(
    'draft'     => 'Rascunho',
    'issued'    => 'Emitido',
    'cancelled' => 'Cancelado',
);

$status_total = max(
    1,
    (int) $data['charts']['status_breakdown']['draft'] + (int) $data['charts']['status_breakdown']['issued'] + (int) $data['charts']['status_breakdown']['cancelled']
);

$type_max = 1.0;
foreach ( $data['charts']['type_breakdown'] as $type_row ) {
    $type_max = max( $type_max, (float) $type_row['total'] );
}
?>

<div class="wrap tps-dashboard-modern">
    <section class="tps-header">
        <h1>Painel ERP</h1>
        <p class="tps-subtitle">Visão executiva com comparativos de faturação, documentos, clientes e desempenho comercial.</p>
    </section>

    <section class="tps-grid-kpi">
        <article class="tps-kpi">
            <p class="tps-kpi-head"><span class="dashicons dashicons-chart-line tps-kpi-icon" aria-hidden="true"></span><span class="tps-kpi-label">Receita do Mês</span></p>
            <p class="tps-kpi-value"><?php echo esc_html( $format_money( $kpis['revenue_total_month'] ) ); ?></p>
            <div class="tps-kpi-meta">
                <span>Total c/ IVA</span>
                <span class="tps-delta tps-delta-<?php echo esc_attr( $revenue_delta['state'] ); ?>"><?php echo esc_html( $revenue_delta['label'] ); ?></span>
            </div>
        </article>
        <article class="tps-kpi">
            <p class="tps-kpi-head"><span class="dashicons dashicons-yes-alt tps-kpi-icon" aria-hidden="true"></span><span class="tps-kpi-label">Documentos Emitidos</span></p>
            <p class="tps-kpi-value"><?php echo esc_html( $format_int( $kpis['issued_count_month'] ) ); ?></p>
            <div class="tps-kpi-meta">
                <span>Mês atual</span>
                <span class="tps-delta tps-delta-<?php echo esc_attr( $issued_delta['state'] ); ?>"><?php echo esc_html( $issued_delta['label'] ); ?></span>
            </div>
        </article>
        <article class="tps-kpi">
            <p class="tps-kpi-head"><span class="dashicons dashicons-chart-bar tps-kpi-icon" aria-hidden="true"></span><span class="tps-kpi-label">Ticket Médio</span></p>
            <p class="tps-kpi-value"><?php echo esc_html( $format_money( $kpis['average_ticket_month'] ) ); ?></p>
            <div class="tps-kpi-meta">
                <span>Por documento emitido</span>
                <span><?php echo esc_html( $format_int( $kpis['issued_count_month'] ) ); ?> docs</span>
            </div>
        </article>
        <article class="tps-kpi">
            <p class="tps-kpi-head"><span class="dashicons dashicons-groups tps-kpi-icon" aria-hidden="true"></span><span class="tps-kpi-label">Clientes</span></p>
            <p class="tps-kpi-value"><?php echo esc_html( $format_int( $kpis['customers_total'] ) ); ?></p>
            <div class="tps-kpi-meta">
                <span>Base total</span>
                <span>+<?php echo esc_html( $format_int( $kpis['customers_new_month'] ) ); ?> no mês</span>
            </div>
        </article>
        <article class="tps-kpi">
            <p class="tps-kpi-head"><span class="dashicons dashicons-media-document tps-kpi-icon" aria-hidden="true"></span><span class="tps-kpi-label">Documentos Totais</span></p>
            <p class="tps-kpi-value"><?php echo esc_html( $format_int( $kpis['documents_total'] ) ); ?></p>
            <div class="tps-kpi-meta">
                <span>Rascunhos: <?php echo esc_html( $format_int( $kpis['documents_draft'] ) ); ?></span>
                <span>Cancelados: <?php echo esc_html( $format_int( $kpis['documents_cancelled'] ) ); ?></span>
            </div>
        </article>
        <article class="tps-kpi">
            <p class="tps-kpi-head"><span class="dashicons dashicons-calculator tps-kpi-icon" aria-hidden="true"></span><span class="tps-kpi-label">Subtotal / IVA</span></p>
            <p class="tps-kpi-value"><?php echo esc_html( $format_money( $kpis['revenue_subtotal_month'] ) ); ?></p>
            <div class="tps-kpi-meta">
                <span>IVA mês: <?php echo esc_html( $format_money( $kpis['tax_month'] ) ); ?></span>
                <span>Taxa: <?php echo esc_html( number_format( tps_get_iva_rate() * 100, 2 ) ); ?>%</span>
            </div>
        </article>
        <article class="tps-kpi">
            <p class="tps-kpi-head"><span class="dashicons dashicons-money-alt tps-kpi-icon" aria-hidden="true"></span><span class="tps-kpi-label">Por Receber</span></p>
            <p class="tps-kpi-value"><?php echo esc_html( $format_money( $kpis['receivable_total'] ) ); ?></p>
            <div class="tps-kpi-meta">
                <span>Saldo pendente</span>
                <span>Vencido: <?php echo esc_html( $format_money( $kpis['overdue_total'] ) ); ?></span>
            </div>
        </article>
        <article class="tps-kpi">
            <p class="tps-kpi-head"><span class="dashicons dashicons-warning tps-kpi-icon" aria-hidden="true"></span><span class="tps-kpi-label">Stock Critico</span></p>
            <p class="tps-kpi-value"><?php echo esc_html( $format_int( $kpis['critical_products'] ) ); ?></p>
            <div class="tps-kpi-meta">
                <span>Produtos abaixo do minimo</span>
                <span><a href="<?php echo esc_url( tps_get_page_url( 'tps-inventory' ) ); ?>">Abrir Stock</a></span>
            </div>
        </article>
    </section>

    <section class="tps-grid-main">
        <article class="tps-panel">
            <h2 class="tps-panel-title"><span class="dashicons dashicons-chart-area tps-icon" aria-hidden="true"></span>Faturação dos Últimos 6 Meses</h2>
            <p class="tps-legend">Linha com total mensal (subtotal + IVA).</p>
            <svg id="tps-revenue-chart" class="tps-line-chart" viewBox="0 0 760 260" preserveAspectRatio="none" aria-label="Gráfico de faturação"></svg>
        </article>
        <article class="tps-panel">
            <h2 class="tps-panel-title"><span class="dashicons dashicons-chart-pie tps-icon" aria-hidden="true"></span>Distribuição por Estado</h2>
            <p class="tps-legend">Resumo do volume de documentos por estado no período atual.</p>
            <div class="tps-status-list">
                <div class="tps-bar-row">
                    <div class="tps-bar-label">Emitido</div>
                    <div class="tps-bar-track"><span class="tps-bar-fill" data-fill="<?php echo esc_attr( number_format( ( $data['charts']['status_breakdown']['issued'] / $status_total ) * 100, 2, '.', '' ) ); ?>"></span></div>
                    <div class="tps-bar-value"><?php echo esc_html( $format_int( $data['charts']['status_breakdown']['issued'] ) ); ?></div>
                </div>
                <div class="tps-bar-row">
                    <div class="tps-bar-label">Rascunho</div>
                    <div class="tps-bar-track"><span class="tps-bar-fill" data-fill="<?php echo esc_attr( number_format( ( $data['charts']['status_breakdown']['draft'] / $status_total ) * 100, 2, '.', '' ) ); ?>"></span></div>
                    <div class="tps-bar-value"><?php echo esc_html( $format_int( $data['charts']['status_breakdown']['draft'] ) ); ?></div>
                </div>
                <div class="tps-bar-row">
                    <div class="tps-bar-label">Cancelado</div>
                    <div class="tps-bar-track"><span class="tps-bar-fill" data-fill="<?php echo esc_attr( number_format( ( $data['charts']['status_breakdown']['cancelled'] / $status_total ) * 100, 2, '.', '' ) ); ?>"></span></div>
                    <div class="tps-bar-value"><?php echo esc_html( $format_int( $data['charts']['status_breakdown']['cancelled'] ) ); ?></div>
                </div>
            </div>
            <div class="tps-dashboard-subsection">
                <h3 class="tps-dashboard-subsection-title"><span class="dashicons dashicons-filter tps-icon" aria-hidden="true"></span>Comparação por Tipo (Mês Atual)</h3>
                <p class="tps-muted">Comparativo do total faturado por tipo de documento no mês corrente.</p>
            </div>
            <div class="tps-type-list">
                <?php foreach ( $data['charts']['type_breakdown'] as $row ) : ?>
                    <?php $ratio = ( (float) $row['total'] / $type_max ) * 100; ?>
                    <div class="tps-bar-row">
                        <div class="tps-bar-label"><?php echo esc_html( $row['label'] ); ?></div>
                        <div class="tps-bar-track"><span class="tps-bar-fill" data-fill="<?php echo esc_attr( number_format( $ratio, 2, '.', '' ) ); ?>"></span></div>
                        <div class="tps-bar-value"><?php echo esc_html( $format_money( $row['total'] ) ); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section class="tps-grid-tables">
        <article class="tps-table-shell tps-dashboard-card tps-dashboard-table-card">
            <div class="tps-section-head tps-dashboard-card-head">
                <div>
                    <h2 class="tps-section-title"><span class="dashicons dashicons-star-filled tps-icon" aria-hidden="true"></span>Top Clientes (Últimos 90 dias)</h2>
                    <p class="tps-section-subtitle">Ranking comercial com os clientes que mais faturaram no período recente.</p>
                </div>
            </div>
            <?php if ( empty( $data['top_customers'] ) ) : ?>
                <p class="tps-empty">Sem faturação emitida no período.</p>
            <?php else : ?>
                <table>
                    <thead>
                        <tr><th>Cliente</th><th>NUIT</th><th>Docs</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $data['top_customers'] as $row ) : ?>
                            <tr>
                                <td><?php echo esc_html( $row['customer_name'] ); ?></td>
                                <td><?php echo esc_html( $row['customer_nuit'] ); ?></td>
                                <td><?php echo esc_html( $format_int( $row['docs'] ) ); ?></td>
                                <td><?php echo esc_html( $format_money( $row['total'] ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </article>
        <article class="tps-table-shell tps-dashboard-card tps-dashboard-table-card">
            <div class="tps-section-head tps-dashboard-card-head">
                <div>
                    <h2 class="tps-section-title"><span class="dashicons dashicons-clock tps-icon" aria-hidden="true"></span>Documentos Recentes</h2>
                    <p class="tps-section-subtitle">Últimos documentos criados para facilitar o acompanhamento operacional.</p>
                </div>
            </div>
            <?php if ( empty( $data['recent_documents'] ) ) : ?>
                <p class="tps-empty">Ainda sem documentos.</p>
            <?php else : ?>
                <table>
                    <thead>
                        <tr><th>Número</th><th>Tipo</th><th>Cliente</th><th>Estado</th><th>Ação</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $data['recent_documents'] as $row ) : ?>
                            <tr>
                                <td>#<?php echo esc_html( $format_int( $row['number'] ) ); ?></td>
                                <td><?php echo esc_html( $row['type_label'] ); ?></td>
                                <td><?php echo esc_html( $row['customer_name'] ? $row['customer_name'] : '-' ); ?></td>
                                <td><span class="tps-status-pill tps-status-<?php echo esc_attr( $row['status'] ); ?>"><?php echo esc_html( $status_labels[ $row['status'] ] ?? $row['status'] ); ?></span></td>
                                <td class="tps-actions-col"><a class="tps-row-btn" href="<?php echo esc_url( $row['edit_url'] ); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span>Abrir</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </article>
        <article class="tps-table-shell tps-dashboard-card tps-dashboard-table-card">
            <div class="tps-section-head tps-dashboard-card-head">
                <div>
                    <h2 class="tps-section-title"><span class="dashicons dashicons-warning tps-icon" aria-hidden="true"></span>Produtos Criticos</h2>
                    <p class="tps-section-subtitle">Itens abaixo do stock mínimo para ação rápida da equipa.</p>
                </div>
            </div>
            <?php if ( empty( $data['critical_products'] ) ) : ?>
                <p class="tps-empty">Sem alertas de stock minimo.</p>
            <?php else : ?>
                <table>
                    <thead>
                        <tr><th>Produto</th><th>SKU</th><th>Saldo</th><th>Minimo</th><th>Custo</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $data['critical_products'] as $row ) : ?>
                            <tr>
                                <td><?php echo esc_html( $row['name'] ); ?></td>
                                <td><?php echo esc_html( $row['sku'] ? $row['sku'] : '-' ); ?></td>
                                <td><?php echo esc_html( number_format( (float) $row['stock_qty'], 2 ) ); ?></td>
                                <td><?php echo esc_html( number_format( (float) $row['min_stock'], 2 ) ); ?></td>
                                <td><?php echo esc_html( $format_money( $row['cost_price'] ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </article>
    </section>
</div>

