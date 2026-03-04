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

<style>
    .tps-dashboard-modern {
        --tps-bg: #f6f8fc;
        --tps-panel: #fff;
        --tps-border: #dbe4f0;
        --tps-text: #1a2433;
        --tps-muted: #607089;
        --tps-accent: #0f5ea8;
        --tps-up: #0f7a3a;
        --tps-down: #a61b1b;
        margin-top: 14px;
    }
    .tps-dashboard-modern .tps-icon { display: inline-flex; align-items: center; vertical-align: middle; margin-right: 4px; }
    .tps-dashboard-modern .tps-header {
        border: 1px solid var(--tps-border);
        border-radius: 14px;
        padding: 18px 20px;
        background: linear-gradient(120deg, #f8fbff 0%, #eef4fd 60%, #e6edf8 100%);
        margin-bottom: 14px;
    }
    .tps-dashboard-modern .tps-header h1 {
        margin: 0;
        color: var(--tps-text);
        font-size: 24px;
    }
    .tps-dashboard-modern .tps-subtitle {
        margin: 6px 0 0;
        color: var(--tps-muted);
    }
    .tps-dashboard-modern .tps-grid-kpi {
        display: grid;
        grid-template-columns: repeat(4, minmax(160px, 1fr));
        gap: 10px;
        margin-bottom: 12px;
    }
    .tps-dashboard-modern .tps-kpi {
        background: var(--tps-panel);
        border: 1px solid var(--tps-border);
        border-radius: 12px;
        padding: 12px 14px;
        transition: all .16s ease;
    }
    .tps-dashboard-modern .tps-kpi:hover { border-color: #c7d5e7; box-shadow: 0 8px 16px rgba(26, 36, 51, .07); transform: translateY(-1px); }
    .tps-dashboard-modern .tps-kpi-head {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin: 0 0 7px;
    }
    .tps-dashboard-modern .tps-kpi-icon {
        color: #5d6f88;
        width: 16px;
        height: 16px;
        font-size: 16px;
    }
    .tps-dashboard-modern .tps-kpi-label {
        margin: 0 0 7px;
        color: var(--tps-muted);
        text-transform: uppercase;
        letter-spacing: .04em;
        font-size: 11px;
    }
    .tps-dashboard-modern .tps-kpi-head .tps-kpi-label { margin: 0; }
    .tps-dashboard-modern .tps-kpi-value {
        margin: 0;
        color: var(--tps-text);
        font-size: 26px;
        line-height: 1.1;
        font-weight: 700;
    }
    .tps-dashboard-modern .tps-kpi-meta {
        margin-top: 8px;
        display: flex;
        justify-content: space-between;
        gap: 8px;
        color: var(--tps-muted);
        font-size: 12px;
    }
    .tps-dashboard-modern .tps-delta {
        border-radius: 999px;
        padding: 2px 8px;
        font-weight: 700;
        font-size: 11px;
    }
    .tps-dashboard-modern .tps-delta-up { background: #e8f8ee; color: var(--tps-up); }
    .tps-dashboard-modern .tps-delta-down { background: #fdebec; color: var(--tps-down); }
    .tps-dashboard-modern .tps-delta-neutral { background: #edf2f9; color: #3e4f68; }
    .tps-dashboard-modern .tps-grid-main {
        display: grid;
        grid-template-columns: 1.4fr 1fr;
        gap: 12px;
        margin-bottom: 12px;
    }
    .tps-dashboard-modern .tps-panel {
        background: var(--tps-panel);
        border: 1px solid var(--tps-border);
        border-radius: 12px;
        padding: 14px;
        transition: box-shadow .16s ease, transform .16s ease;
    }
    .tps-dashboard-modern .tps-panel:hover { box-shadow: 0 8px 18px rgba(26, 36, 51, .08); transform: translateY(-1px); }
    .tps-dashboard-modern .tps-panel h2 {
        margin: 0 0 10px;
        color: var(--tps-text);
        font-size: 16px;
    }
    .tps-dashboard-modern .tps-panel-title { display: inline-flex; align-items: center; gap: 6px; }
    .tps-dashboard-modern .tps-legend {
        margin: 0 0 10px;
        color: var(--tps-muted);
        font-size: 12px;
    }
    .tps-dashboard-modern .tps-line-chart {
        width: 100%;
        height: 260px;
        background: var(--tps-bg);
        border: 1px solid #e5edf7;
        border-radius: 10px;
    }
    .tps-dashboard-modern .tps-status-list,
    .tps-dashboard-modern .tps-type-list {
        display: grid;
        gap: 10px;
    }
    .tps-dashboard-modern .tps-bar-row {
        display: grid;
        grid-template-columns: 110px 1fr auto;
        gap: 8px;
        align-items: center;
        transition: all .16s ease;
        border-radius: 8px;
        padding: 4px 6px;
    }
    .tps-dashboard-modern .tps-bar-row:hover { background: #f8fbff; }
    .tps-dashboard-modern .tps-bar-label {
        color: var(--tps-text);
        font-size: 13px;
        font-weight: 600;
    }
    .tps-dashboard-modern .tps-bar-track {
        position: relative;
        height: 10px;
        background: #edf2f9;
        border-radius: 999px;
        overflow: hidden;
    }
    .tps-dashboard-modern .tps-bar-fill {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        background: linear-gradient(90deg, #9bc2ec 0%, #0f5ea8 100%);
    }
    .tps-dashboard-modern .tps-bar-value {
        color: var(--tps-muted);
        font-size: 12px;
        min-width: 90px;
        text-align: right;
    }
    .tps-dashboard-modern .tps-grid-tables {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    .tps-dashboard-modern table {
        width: 100%;
        border-collapse: collapse;
    }
    .tps-dashboard-modern th,
    .tps-dashboard-modern td {
        text-align: left;
        border-bottom: 1px solid #eef3f9;
        padding: 9px 8px;
        font-size: 12px;
    }
    .tps-dashboard-modern th {
        color: var(--tps-muted);
        text-transform: uppercase;
        letter-spacing: .03em;
        font-size: 11px;
        background: #f7faff;
    }
    .tps-dashboard-modern tbody tr td { transition: background-color .16s ease, box-shadow .16s ease; }
    .tps-dashboard-modern tbody tr:hover td { background: #eaf4ff; }
    .tps-dashboard-modern tbody tr:hover td:first-child { box-shadow: inset 3px 0 0 #0f5ea8; }
    .tps-dashboard-modern a { color: var(--tps-accent); text-decoration: none; transition: color .16s ease, text-decoration-color .16s ease; text-decoration-color: transparent; }
    .tps-dashboard-modern a:hover { color: #0d528f; text-decoration: underline; text-decoration-color: #0d528f; }
    .tps-dashboard-modern a:focus-visible { outline: 2px solid #0f5ea8; outline-offset: 2px; }
    .tps-dashboard-modern .tps-status-pill {
        display: inline-block;
        border-radius: 999px;
        padding: 2px 8px;
        border: 1px solid;
        font-size: 11px;
        transition: all .16s ease;
    }
    .tps-dashboard-modern .tps-status-pill:hover { filter: brightness(.98); transform: translateY(-1px); }
    .tps-dashboard-modern .tps-status-draft { background: #fff8e5; color: #8a6d00; border-color: #f1d48b; }
    .tps-dashboard-modern .tps-status-issued { background: #e7f7ed; color: #1f7a39; border-color: #9ad3ac; }
    .tps-dashboard-modern .tps-status-cancelled { background: #fde8e8; color: #a61b1b; border-color: #f1a6a6; }
    .tps-dashboard-modern .tps-empty {
        margin: 0;
        color: var(--tps-muted);
    }
    @media (max-width: 1080px) {
        .tps-dashboard-modern .tps-grid-kpi { grid-template-columns: repeat(2, minmax(150px, 1fr)); }
        .tps-dashboard-modern .tps-grid-main,
        .tps-dashboard-modern .tps-grid-tables { grid-template-columns: 1fr; }
    }
</style>

<div class="wrap tps-dashboard-modern">
    <section class="tps-header">
        <h1><span class="dashicons dashicons-chart-area" aria-hidden="true"></span> Painel ERP</h1>
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
    </section>

    <section class="tps-grid-main">
        <article class="tps-panel">
            <h2 class="tps-panel-title"><span class="dashicons dashicons-chart-area tps-icon" aria-hidden="true"></span>Faturação dos Últimos 6 Meses</h2>
            <p class="tps-legend">Linha com total mensal (subtotal + IVA).</p>
            <svg id="tps-revenue-chart" class="tps-line-chart" viewBox="0 0 760 260" preserveAspectRatio="none" aria-label="Gráfico de faturação"></svg>
        </article>
        <article class="tps-panel">
            <h2 class="tps-panel-title"><span class="dashicons dashicons-chart-pie tps-icon" aria-hidden="true"></span>Distribuição por Estado</h2>
            <div class="tps-status-list">
                <div class="tps-bar-row">
                    <div class="tps-bar-label">Emitido</div>
                    <div class="tps-bar-track"><span class="tps-bar-fill" style="width: <?php echo esc_attr( number_format( ( $data['charts']['status_breakdown']['issued'] / $status_total ) * 100, 2, '.', '' ) ); ?>%;"></span></div>
                    <div class="tps-bar-value"><?php echo esc_html( $format_int( $data['charts']['status_breakdown']['issued'] ) ); ?></div>
                </div>
                <div class="tps-bar-row">
                    <div class="tps-bar-label">Rascunho</div>
                    <div class="tps-bar-track"><span class="tps-bar-fill" style="width: <?php echo esc_attr( number_format( ( $data['charts']['status_breakdown']['draft'] / $status_total ) * 100, 2, '.', '' ) ); ?>%;"></span></div>
                    <div class="tps-bar-value"><?php echo esc_html( $format_int( $data['charts']['status_breakdown']['draft'] ) ); ?></div>
                </div>
                <div class="tps-bar-row">
                    <div class="tps-bar-label">Cancelado</div>
                    <div class="tps-bar-track"><span class="tps-bar-fill" style="width: <?php echo esc_attr( number_format( ( $data['charts']['status_breakdown']['cancelled'] / $status_total ) * 100, 2, '.', '' ) ); ?>%;"></span></div>
                    <div class="tps-bar-value"><?php echo esc_html( $format_int( $data['charts']['status_breakdown']['cancelled'] ) ); ?></div>
                </div>
            </div>
            <h2 class="tps-panel-title" style="margin-top:18px;"><span class="dashicons dashicons-filter tps-icon" aria-hidden="true"></span>Comparação por Tipo (Mês Atual)</h2>
            <div class="tps-type-list">
                <?php foreach ( $data['charts']['type_breakdown'] as $row ) : ?>
                    <?php $ratio = ( (float) $row['total'] / $type_max ) * 100; ?>
                    <div class="tps-bar-row">
                        <div class="tps-bar-label"><?php echo esc_html( $row['label'] ); ?></div>
                        <div class="tps-bar-track"><span class="tps-bar-fill" style="width: <?php echo esc_attr( number_format( $ratio, 2, '.', '' ) ); ?>%;"></span></div>
                        <div class="tps-bar-value"><?php echo esc_html( $format_money( $row['total'] ) ); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section class="tps-grid-tables">
        <article class="tps-panel">
            <h2 class="tps-panel-title"><span class="dashicons dashicons-star-filled tps-icon" aria-hidden="true"></span>Top Clientes (Últimos 90 dias)</h2>
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
        <article class="tps-panel">
            <h2 class="tps-panel-title"><span class="dashicons dashicons-clock tps-icon" aria-hidden="true"></span>Documentos Recentes</h2>
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
                                <td><a href="<?php echo esc_url( $row['edit_url'] ); ?>">Abrir</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </article>
    </section>
</div>

<script>
(function () {
    var data = <?php echo wp_json_encode( $data['charts']['monthly_revenue'] ); ?>;
    var svg = document.getElementById('tps-revenue-chart');
    if (!svg || !data || !data.length) return;

    var width = 760;
    var height = 260;
    var padding = { top: 20, right: 18, bottom: 36, left: 48 };
    var innerW = width - padding.left - padding.right;
    var innerH = height - padding.top - padding.bottom;

    var values = data.map(function (d) { return Number(d.total || 0); });
    var max = Math.max.apply(null, values);
    if (max <= 0) max = 1;

    function xAt(i) {
        if (data.length === 1) return padding.left + innerW / 2;
        return padding.left + (innerW * i / (data.length - 1));
    }
    function yAt(v) {
        return padding.top + (innerH - ((v / max) * innerH));
    }

    var points = values.map(function (v, i) { return xAt(i).toFixed(2) + ',' + yAt(v).toFixed(2); }).join(' ');
    var areaPath = 'M ' + xAt(0).toFixed(2) + ' ' + (padding.top + innerH).toFixed(2) + ' L ' +
        values.map(function (v, i) { return xAt(i).toFixed(2) + ' ' + yAt(v).toFixed(2); }).join(' L ') +
        ' L ' + xAt(values.length - 1).toFixed(2) + ' ' + (padding.top + innerH).toFixed(2) + ' Z';

    var ns = 'http://www.w3.org/2000/svg';
    function el(name, attrs) {
        var node = document.createElementNS(ns, name);
        Object.keys(attrs).forEach(function (k) { node.setAttribute(k, attrs[k]); });
        return node;
    }

    svg.innerHTML = '';
    svg.appendChild(el('rect', { x: 0, y: 0, width: width, height: height, fill: '#f6f8fc' }));

    for (var g = 0; g <= 4; g++) {
        var gy = padding.top + (innerH * g / 4);
        svg.appendChild(el('line', { x1: padding.left, y1: gy, x2: width - padding.right, y2: gy, stroke: '#dde8f5', 'stroke-width': 1 }));
    }

    svg.appendChild(el('path', { d: areaPath, fill: 'rgba(15, 94, 168, 0.16)' }));
    svg.appendChild(el('polyline', { points: points, fill: 'none', stroke: '#0f5ea8', 'stroke-width': 3, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }));

    values.forEach(function (v, i) {
        svg.appendChild(el('circle', { cx: xAt(i), cy: yAt(v), r: 3.8, fill: '#0f5ea8' }));
        svg.appendChild(el('text', {
            x: xAt(i),
            y: height - 12,
            'text-anchor': 'middle',
            'font-size': '11',
            fill: '#62758f'
        })).textContent = String(data[i].label || '');
    });
})();
</script>
