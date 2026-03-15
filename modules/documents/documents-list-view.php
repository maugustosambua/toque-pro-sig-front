<?php
if (! defined('ABSPATH')) {
    exit;
}

$counts = TPS_Documents_Model::count_by_status();
$export_base_url = wp_nonce_url(add_query_arg('action', 'tps_export_documents', tps_get_action_url()), 'tps_export_documents');
$default_fiscal_period = wp_date('Y-m');
$fiscal_closures = TPS_Documents_Model::get_fiscal_monthly_closures(array('limit' => 12));
$can_emit_documents = tps_current_user_can('emitir');
$can_export_documents = tps_current_user_can('exportar');
$can_manage_fiscal = tps_current_user_can('fiscal');
?>

<div class="wrap tps-documents-modern">
    <section class="tps-header">
        <div class="tps-title-row">
            <div>
                <h1>Documentos</h1>
                <p class="tps-subtitle">Lista de documentos com filtros e carregamento em tempo real via AJAX.</p>
            </div>
            <div class="tps-actions">
                <?php if ($can_emit_documents) : ?>
                    <a href="<?php echo esc_url(tps_get_page_url('tps-documents-add')); ?>" class="tps-btn tps-btn-primary"><span class="dashicons dashicons-plus-alt2 tps-icon" aria-hidden="true"></span>Adicionar Documento</a>
                <?php endif; ?>
                <?php if ($can_export_documents) : ?>
                    <a id="tps-doc-export" href="<?php echo esc_url($export_base_url); ?>" class="tps-btn tps-btn-secondary"><span class="dashicons dashicons-download tps-icon" aria-hidden="true"></span>Exportar</a>
                <?php endif; ?>
                <?php if ($can_manage_fiscal) : ?>
                    <a id="tps-doc-export-at" href="<?php echo esc_url(wp_nonce_url(add_query_arg('action', 'tps_export_fiscal_at', tps_get_action_url()), 'tps_export_fiscal_at')); ?>" class="tps-btn tps-btn-secondary"><span class="dashicons dashicons-media-spreadsheet tps-icon" aria-hidden="true"></span>Exportar Fiscal AT</a>
                <?php endif; ?>
            </div>
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
        <div>
        <input id="tps-doc-search" class="tps-search tps-input" type="search" placeholder="Pesquisar por número, tipo ou cliente">
        </div>
        <div>
        <select id="tps-doc-type" class="tps-select">
            <option value="">Todos os tipos</option>
            <option value="invoice">Fatura</option>
            <option value="vd">Venda a Dinheiro</option>
            <option value="quotation">Cotação</option>
            <option value="credit_note">Nota de Crédito</option>
            <option value="debit_note">Nota de Débito</option>
        </select>
        </div>
        <div>
        <select id="tps-doc-status" class="tps-select">
            <option value="">Todos os estados</option>
            <option value="draft">Rascunho</option>
            <option value="issued">Emitido</option>
            <option value="cancelled">Cancelado</option>
        </select>
        </div>
        <div>
        <select id="tps-doc-sort" class="tps-select">
            <option value="date">Ordenar por data</option>
            <option value="name">Ordenar por nome</option>
            <option value="city">Ordenar por morada</option>
        </select>
        </div>
    </section>

    <?php if ($can_manage_fiscal) : ?>
    <section class="tps-card">
        <h2 class="tps-title-sm"><span class="dashicons dashicons-calendar-alt tps-icon" aria-hidden="true"></span>Fecho Fiscal Mensal</h2>
        <div class="tps-actions">
            <form method="post" action="<?php echo esc_url(tps_get_action_url()); ?>" class="tps-inline-form">
                <?php wp_nonce_field('tps_close_fiscal_month'); ?>
                <input type="hidden" name="action" value="tps_close_fiscal_month">
                <label for="tps-fiscal-period-close">Periodo</label>
                <input id="tps-fiscal-period-close" class="tps-input" type="month" name="fiscal_period" value="<?php echo esc_attr($default_fiscal_period); ?>" required>
                <button type="submit" class="tps-btn tps-btn-primary"><span class="dashicons dashicons-yes-alt tps-icon" aria-hidden="true"></span>Fechar Mês</button>
            </form>

            <form method="post" action="<?php echo esc_url(tps_get_action_url()); ?>" class="tps-inline-form">
                <?php wp_nonce_field('tps_export_fiscal_month_close'); ?>
                <input type="hidden" name="action" value="tps_export_fiscal_month_close">
                <label for="tps-fiscal-period-export">Periodo</label>
                <input id="tps-fiscal-period-export" class="tps-input" type="month" name="fiscal_period" value="<?php echo esc_attr($default_fiscal_period); ?>" required>
                <button type="submit" class="tps-btn tps-btn-secondary"><span class="dashicons dashicons-download tps-icon" aria-hidden="true"></span>Exportar Fecho</button>
            </form>
        </div>

        <div class="tps-table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Período</th>
                        <th>Documentos</th>
                        <th>Emitidos</th>
                        <th>Cancelados</th>
                        <th>IVA</th>
                        <th>Retenção</th>
                        <th>Recebimentos</th>
                        <th>Saldo Aberto</th>
                        <th>Fechado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($fiscal_closures)) : ?>
                        <?php foreach ($fiscal_closures as $closure) : ?>
                            <tr>
                                <td><?php echo esc_html((string) $closure->period_ym); ?></td>
                                <td><?php echo esc_html((string) (int) $closure->documents_count); ?></td>
                                <td><?php echo esc_html((string) (int) $closure->issued_count); ?></td>
                                <td><?php echo esc_html((string) (int) $closure->cancelled_count); ?></td>
                                <td><?php echo esc_html(number_format((float) $closure->iva, 2)); ?></td>
                                <td><?php echo esc_html(number_format((float) $closure->withholding_amount, 2)); ?></td>
                                <td><?php echo esc_html(number_format((float) $closure->payments_total, 2)); ?></td>
                                <td><?php echo esc_html(number_format((float) $closure->open_balance_total, 2)); ?></td>
                                <td><?php echo esc_html((string) $closure->closed_at); ?></td>
                                <td>
                                    <form method="post" action="<?php echo esc_url(tps_get_action_url()); ?>" class="tps-inline-form">
                                        <?php wp_nonce_field('tps_export_fiscal_month_close'); ?>
                                        <input type="hidden" name="action" value="tps_export_fiscal_month_close">
                                        <input type="hidden" name="fiscal_period" value="<?php echo esc_attr((string) $closure->period_ym); ?>">
                                        <button type="submit" class="tps-row-btn tps-btn-secondary">Exportar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="10">Sem fechos fiscais mensais registados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

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
            <button id="tps-doc-prev" type="button" class="tps-page-btn"><span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>Anterior</button>
            <span id="tps-doc-page">Página 1</span>
            <button id="tps-doc-next" type="button" class="tps-page-btn">Seguinte<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></button>
        </div>
    </section>
</div>

