<?php
if (! defined('ABSPATH')) {
    exit;
}

$counts = TPS_Documents_Model::count_by_status();
$export_base_url = wp_nonce_url(admin_url('admin-post.php?action=tps_export_documents'), 'tps_export_documents');
?>

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

