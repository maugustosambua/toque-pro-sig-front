<?php
// Impede acesso directo
if (! defined('ABSPATH')) {
    exit;
}

$document_id = isset($_GET['document_id']) ? (int) $_GET['document_id'] : 0;
$back_url = tps_get_page_url('tps-documents');
$can_emit_documents = tps_current_user_can('emitir');
$can_cancel_documents = tps_current_user_can('cancelar');
$can_receive_payments = tps_current_user_can('receber');
$can_export_documents = tps_current_user_can('exportar');
$can_manage_fiscal_rules = function_exists('tps_current_user_can_manage_fiscal_rules') ? tps_current_user_can_manage_fiscal_rules() : tps_current_user_can('fiscal');
?>

<div class="wrap tps-document-form">

<?php
if (! $document_id) :
    if (! $can_emit_documents) :
    ?>
    <section class="tps-card">
        <p class="tps-muted">O seu perfil nao tem permissao para criar documentos.</p>
    </section>
    <?php
        return;
    endif;

    $types = TPS_Documents_Model::types();
    $default_type = isset($_GET['document_type']) ? sanitize_text_field(wp_unslash($_GET['document_type'])) : '';
    $default_original_document_id = isset($_GET['original_document_id']) ? (int) $_GET['original_document_id'] : 0;
    $default_original_document = $default_original_document_id > 0 ? TPS_Documents_Model::get($default_original_document_id) : null;
    if (! $default_original_document || (string) $default_original_document->status !== 'issued') {
        $default_original_document_id = 0;
        $default_original_document = null;
    }
    if (! array_key_exists($default_type, $types)) {
        $default_type = '';
    }
    $has_customers = TPS_Customers_Model::count_customers() > 0;
    ?>
    <section class="tps-header">
        <div class="tps-header-row tps-header-row--back">
            <a class="tps-back-btn" href="<?php echo esc_url($back_url); ?>">
                <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
                <span>Voltar</span>
            </a>
            <div class="tps-header-content">
                <h1>Criar Documento</h1>
                <p class="tps-subtitle">Inicie um rascunho e complete as linhas antes de emitir.</p>
            </div>
        </div>
    </section>

    <section class="tps-card">
        <?php if (! $has_customers) : ?>
            <div class="tps-notice tps-notice--warning">
                <p>Nenhum cliente encontrado. Crie pelo menos um cliente antes de criar documentos.</p>
            </div>
        <?php endif; ?>
        <form method="post" action="<?php echo esc_url(tps_get_action_url()); ?>">
            <?php wp_nonce_field('tps_save_document'); ?>
            <input type="hidden" name="action" value="tps_save_document">

            <div class="tps-body">
                <div class="tps-grid">
                <div>
                    <label for="tps-document-type">Tipo</label>
                    <select id="tps-document-type" class="tps-select" name="document_type" required>
                        <?php foreach ($types as $key => $label) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($default_type, $key); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="tps-original-document-id">Documento Original (ID)</label>
                    <input id="tps-original-document-id" class="tps-input" type="number" min="1" name="original_document_id" value="<?php echo esc_attr((string) $default_original_document_id); ?>" placeholder="Obrigatorio para nota de credito/debito">
                    <?php if ($default_original_document) : ?>
                        <small class="tps-muted">Selecionado: #<?php echo esc_html((string) $default_original_document->number); ?> (<?php echo esc_html((string) ($types[$default_original_document->type] ?? $default_original_document->type)); ?>)</small>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="tps-customer-search">Cliente</label>
                    <div class="tps-search-wrap">
                        <input
                            id="tps-customer-search"
                            class="tps-input"
                            type="text"
                            autocomplete="off"
                            placeholder="Pesquisar por nome, NUIT, e-mail ou telefone"
                            <?php disabled(! $has_customers); ?>
                        >
                        <input id="tps-customer-id" type="hidden" name="customer_id" required>
                        <div id="tps-customer-results" class="tps-search-results" hidden></div>
                    </div>
                </div>
                <div>
                    <label for="tps-issue-date">Data de Emissao</label>
                    <input id="tps-issue-date" class="tps-input" type="date" name="issue_date" value="<?php echo esc_attr(date('Y-m-d')); ?>">
                </div>
                <div>
                    <label for="tps-due-date">Data de Vencimento</label>
                    <input id="tps-due-date" class="tps-input" type="date" name="due_date" value="<?php echo esc_attr(date('Y-m-d')); ?>">
                </div>
                <div>
                    <label for="tps-adjustment-reason">Justificativa do Ajuste</label>
                    <textarea id="tps-adjustment-reason" class="tps-textarea" name="adjustment_reason" rows="2" placeholder="Opcional para nota de credito/debito"></textarea>
                </div>
                <div>
                    <label for="tps-copy-original-lines">Linhas da Nota</label>
                    <input type="hidden" name="copy_original_lines" value="0">
                    <label class="tps-muted" for="tps-copy-original-lines">
                        <input id="tps-copy-original-lines" type="checkbox" name="copy_original_lines" value="1" checked>
                        Copiar automaticamente as linhas do documento original (nota de credito/debito)
                    </label>
                </div>
                </div>
            </div>

            <p class="tps-mt-14 px-3 pb-3 mb-0">
                <?php submit_button('Criar Rascunho', 'primary', 'submit', false, ! $has_customers ? array( 'disabled' => 'disabled' ) : array()); ?>
            </p>
        </form>
    </section>
    <?php
    return;
endif;

$document = TPS_Documents_Model::get($document_id);
$lines = TPS_Document_Lines_Model::get_by_document($document_id);
$fiscal_totals = TPS_Documents_Model::fiscal_totals($document_id);
$subtotal = (float) $fiscal_totals['subtotal'];
$tax_amount = (float) $fiscal_totals['iva'];
$withholding_amount = (float) $fiscal_totals['withholding_amount'];
$grand_total = (float) $fiscal_totals['total'];
$payable_total = (float) $fiscal_totals['payable_total'];
$type_labels = TPS_Documents_Model::types();
$status_labels = TPS_Documents_Model::statuses();
$payment_status_labels = TPS_Documents_Model::payment_statuses();
$tax_mode_labels = TPS_Document_Lines_Model::tax_modes();
$exemption_reasons_catalog = function_exists('tps_get_fiscal_exemption_reasons') ? tps_get_fiscal_exemption_reasons() : array();
$payment_history = class_exists('TPS_Payments_Model') ? TPS_Payments_Model::get_payment_history_by_document($document_id) : array();

$status_class = 'tps-badge-draft';
if (($document->status ?? '') === 'issued') {
    $status_class = 'tps-badge-issued';
}
if (($document->status ?? '') === 'cancelled') {
    $status_class = 'tps-badge-cancelled';
}
?>

    <section class="tps-header">
        <div class="tps-header-row tps-header-row--back">
            <a class="tps-back-btn" href="<?php echo esc_url($back_url); ?>">
                <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
                <span>Voltar</span>
            </a>
            <div class="tps-header-content">
                <h1>Editar Documento</h1>
                <p class="tps-subtitle">Gerir linhas, totais e acoes do ciclo de vida deste documento.</p>
            </div>
        </div>
    </section>

    <section class="tps-grid-2">
        <article class="tps-card">
            <h2 class="tps-title-sm"><span class="dashicons dashicons-media-text tps-icon" aria-hidden="true"></span>Detalhes do Documento</h2>
            <ul class="tps-kv">
                <li><span class="tps-kv-label">Tipo:</span> <span class="tps-kv-value"><?php echo esc_html((string) ($type_labels[$document->type] ?? $document->type)); ?></span></li>
                <li><span class="tps-kv-label">Numero:</span> <span class="tps-kv-value"><?php echo esc_html((string) $document->number); ?></span></li>
                <li><span class="tps-kv-label">Cliente:</span> <span class="tps-kv-value"><?php echo esc_html((string) ($document->customer_name ?: ('#' . (int) $document->customer_id))); ?></span></li>
                <?php if (! empty($document->customer_nuit)) : ?>
                    <li><span class="tps-kv-label">NUIT do Cliente:</span> <span class="tps-kv-value"><?php echo esc_html((string) $document->customer_nuit); ?></span></li>
                <?php endif; ?>
                <?php if (! empty($document->original_document_id)) : ?>
                    <li>
                        <span class="tps-kv-label">Documento Original:</span>
                        <span class="tps-kv-value">
                            <a href="<?php echo esc_url(tps_get_page_url('tps-documents-add', array('document_id' => (int) $document->original_document_id))); ?>">
                                #<?php echo esc_html((string) ($document->original_document_number ?? $document->original_document_id)); ?>
                            </a>
                        </span>
                    </li>
                    <?php if (! empty($document->adjustment_reason)) : ?>
                        <li><span class="tps-kv-label">Justificativa:</span> <span class="tps-kv-value"><?php echo esc_html((string) $document->adjustment_reason); ?></span></li>
                    <?php endif; ?>
                <?php endif; ?>
                <li><span class="tps-kv-label">Data de Emissao:</span> <span class="tps-kv-value"><?php echo esc_html((string) $document->issue_date); ?></span></li>
                <li><span class="tps-kv-label">Data de Vencimento:</span> <span class="tps-kv-value"><?php echo esc_html((string) ($document->due_date ?: '-')); ?></span></li>
                <?php if (! empty($document->fiscal_hash)) : ?>
                    <li><span class="tps-kv-label">Hash Fiscal:</span> <span class="tps-kv-value"><?php echo esc_html((string) $document->fiscal_hash); ?></span></li>
                    <li><span class="tps-kv-label">Hash Anterior:</span> <span class="tps-kv-value"><?php echo esc_html((string) ($document->fiscal_prev_hash ?: '-')); ?></span></li>
                    <li><span class="tps-kv-label">Data do Hash:</span> <span class="tps-kv-value"><?php echo esc_html((string) ($document->fiscal_hashed_at ?: '-')); ?></span></li>
                <?php endif; ?>
                <li>
                    <span class="tps-kv-label">Estado:</span>
                    <span class="tps-kv-value"><span class="tps-badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html((string) ($status_labels[$document->status] ?? $document->status)); ?></span></span>
                </li>
            </ul>
        </article>

        <article class="tps-card">
            <h2 class="tps-title-sm"><span class="dashicons dashicons-chart-line tps-icon" aria-hidden="true"></span>Totais</h2>
            <ul class="tps-kv">
                <li><span class="tps-kv-label">Subtotal:</span> <span class="tps-kv-value"><?php echo esc_html(number_format($subtotal, 2)); ?></span></li>
                <li><span class="tps-kv-label">IVA (<?php echo esc_html(number_format(tps_get_iva_rate() * 100, 2)); ?>%):</span> <span class="tps-kv-value"><?php echo esc_html(number_format($tax_amount, 2)); ?></span></li>
                <li><span class="tps-kv-label">Total Bruto:</span> <span class="tps-kv-value"><?php echo esc_html(number_format($grand_total, 2)); ?></span></li>
                <li><span class="tps-kv-label">Retencao (<?php echo esc_html(number_format((float) $document->withholding_rate, 2)); ?>%):</span> <span class="tps-kv-value">-<?php echo esc_html(number_format($withholding_amount, 2)); ?></span></li>
                <li><span class="tps-kv-label">Total Liquido:</span> <span class="tps-kv-value"><?php echo esc_html(number_format($payable_total, 2)); ?></span></li>
                <li><span class="tps-kv-label">Recebido:</span> <span class="tps-kv-value"><?php echo esc_html(number_format((float) $document->paid_total, 2)); ?></span></li>
                <li><span class="tps-kv-label">Saldo Pendente:</span> <span class="tps-kv-value"><?php echo esc_html(number_format((float) $document->balance_due, 2)); ?></span></li>
                <li><span class="tps-kv-label">Estado Financeiro:</span> <span class="tps-kv-value"><?php echo esc_html((string) ($payment_status_labels[$document->payment_status] ?? $document->payment_status)); ?></span></li>
            </ul>
        </article>
    </section>

    <section class="tps-card">
        <h2 class="tps-title-sm"><span class="dashicons dashicons-calculator tps-icon" aria-hidden="true"></span>Retencao na Fonte</h2>
        <?php if ($document->status === 'draft' && $can_manage_fiscal_rules) : ?>
            <form method="post" action="<?php echo esc_url(tps_get_action_url()); ?>">
                <?php wp_nonce_field('tps_update_document_withholding'); ?>
                <input type="hidden" name="action" value="tps_update_document_withholding">
                <input type="hidden" name="document_id" value="<?php echo esc_attr((string) $document_id); ?>">
                <div class="tps-body">
                    <div class="tps-grid">
                        <div>
                            <label for="tps-withholding-rate">Taxa de Retencao (%)</label>
                            <input id="tps-withholding-rate" class="tps-input" type="number" min="0" max="100" step="0.01" name="withholding_rate" value="<?php echo esc_attr(number_format((float) $document->withholding_rate, 2, '.', '')); ?>">
                        </div>
                    </div>
                </div>

                <p class="tps-mt-14 px-3 pb-3 mb-0">
                    <?php submit_button('Atualizar Retencao', 'secondary', 'submit', false); ?>
                </p>
            </form>
        <?php elseif ($document->status === 'draft') : ?>
            <p class="tps-muted">O seu perfil nao pode alterar retencao fiscal.</p>
        <?php else : ?>
            <p class="tps-muted">A retencao fica bloqueada apos a emissao do documento.</p>
        <?php endif; ?>
    </section>

    <section class="tps-card tps-table-shell">
        <h2 class="tps-title-sm"><span class="dashicons dashicons-editor-table tps-icon" aria-hidden="true"></span>Linhas</h2>
        <table>
            <thead>
                <tr>
                    <th>Descricao</th>
                    <th>Quantidade</th>
                    <th>Preco Unitario</th>
                    <th>Tratamento IVA</th>
                    <th>IVA</th>
                    <th>Subtotal</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($lines) : ?>
                    <?php foreach ($lines as $line) : ?>
                        <tr>
                            <td><?php echo esc_html((string) $line->description); ?></td>
                            <td><?php echo esc_html((string) $line->quantity); ?></td>
                            <td><?php echo esc_html(number_format((float) $line->unit_price, 2)); ?></td>
                            <td>
                                <?php
                                $line_tax_mode = isset($line->tax_mode) ? (string) $line->tax_mode : 'taxable';
                                echo esc_html((string) ($tax_mode_labels[$line_tax_mode] ?? 'Tributado'));
                                if ('taxable' !== $line_tax_mode && ! empty($line->exemption_code)) {
                                    $code = strtoupper((string) $line->exemption_code);
                                    $label = isset($exemption_reasons_catalog[$code]) ? (string) $exemption_reasons_catalog[$code] : '';
                                    echo '<br><small>' . esc_html($code);
                                    if ($label !== '') {
                                        echo ' - ' . esc_html($label);
                                    }
                                    echo '</small>';
                                }
                                if ('taxable' !== $line_tax_mode && ! empty($line->exemption_reason)) {
                                    echo '<br><small>' . esc_html((string) $line->exemption_reason) . '</small>';
                                }
                                ?>
                            </td>
                            <td><?php echo esc_html(number_format((float) TPS_Document_Lines_Model::line_iva($line), 2)); ?></td>
                            <td><?php echo esc_html(number_format(TPS_Document_Lines_Model::line_total($line), 2)); ?></td>
                            <td>
                                <?php if ($document->status === 'draft' && $can_emit_documents) : ?>
                                    <a class="tps-row-btn tps-row-btn-danger" href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action' => 'tps_delete_line', 'line_id' => (int) $line->id, 'document_id' => (int) $document_id), tps_get_action_url()), 'tps_delete_line')); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span>Eliminar</a>
                                <?php else : ?>
                                    <span class="tps-muted">Bloqueado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7">Sem linhas ainda.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <?php if ($document->status === 'draft' && $can_emit_documents) : ?>
        <section class="tps-card">
            <h2 class="tps-title-sm"><span class="dashicons dashicons-plus-alt2 tps-icon" aria-hidden="true"></span>Adicionar Linha</h2>
            <form method="post" action="<?php echo esc_url(tps_get_action_url()); ?>">
                <?php wp_nonce_field('tps_add_line'); ?>
                <input type="hidden" name="action" value="tps_add_line">
                <input type="hidden" name="document_id" value="<?php echo esc_attr((string) $document_id); ?>">
                <input id="tps-line-product-service-id" type="hidden" name="product_service_id" value="0">

                <div class="tps-body">
                <div class="tps-grid">
                    <div>
                        <label for="tps-line-catalog-search">Item do Catalogo</label>
                        <div class="tps-search-wrap">
                            <input
                                id="tps-line-catalog-search"
                                class="tps-input"
                                type="text"
                                autocomplete="off"
                                placeholder="Pesquisar item por nome ou codigo"
                            >
                            <div id="tps-line-catalog-results" class="tps-search-results" hidden></div>
                        </div>
                    </div>
                    <div>
                        <label for="tps-line-description">Descricao</label>
                        <input id="tps-line-description" class="tps-input" type="text" name="description" required>
                    </div>
                    <div>
                        <label for="tps-line-quantity">Quantidade</label>
                        <input id="tps-line-quantity" class="tps-input" type="number" step="0.01" name="quantity" value="1" required>
                    </div>
                    <div>
                        <label for="tps-line-unit-price">Preco Unitario</label>
                        <input id="tps-line-unit-price" class="tps-input" type="number" step="0.01" name="unit_price" value="0" required>
                    </div>
                    <div>
                        <label for="tps-line-tax-mode">Tratamento de IVA</label>
                        <select id="tps-line-tax-mode" class="tps-select" name="tax_mode" required>
                            <option value="taxable">Tributado</option>
                            <?php if ($can_manage_fiscal_rules) : ?>
                                <option value="exempt">Isento</option>
                                <option value="non_taxable">Nao sujeito</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <label for="tps-line-tax-rate">Taxa IVA (%)</label>
                        <input id="tps-line-tax-rate" class="tps-input" type="number" min="0" max="100" step="0.01" name="tax_rate" value="<?php echo esc_attr(number_format(tps_get_iva_rate() * 100, 2, '.', '')); ?>" <?php echo $can_manage_fiscal_rules ? '' : 'readonly'; ?>>
                    </div>
                    <div>
                        <label for="tps-line-exemption-code">Codigo Fiscal de Isencao</label>
                        <select id="tps-line-exemption-code" class="tps-select" name="exemption_code" <?php echo $can_manage_fiscal_rules ? '' : 'disabled'; ?>>
                            <option value="">Selecionar codigo</option>
                            <?php foreach ($exemption_reasons_catalog as $code => $label) : ?>
                                <option value="<?php echo esc_attr((string) $code); ?>"><?php echo esc_html((string) $code . ' - ' . (string) $label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="tps-field-full">
                        <label for="tps-line-exemption-reason">Motivo da Isencao / Nao Sujeicao</label>
                        <input id="tps-line-exemption-reason" class="tps-input" type="text" name="exemption_reason" placeholder="Obrigatorio para linhas isentas ou nao sujeitas" <?php echo $can_manage_fiscal_rules ? '' : 'readonly'; ?>>
                    </div>
                </div>
                </div>

                <?php if (!$can_manage_fiscal_rules) : ?>
                    <p class="tps-muted px-3">Seu perfil nao permite alterar regras fiscais de IVA por linha.</p>
                <?php endif; ?>

                <p class="tps-mt-14 px-3 pb-3 mb-0">
                    <?php submit_button('Adicionar Linha', 'primary', 'submit', false); ?>
                </p>
            </form>
        </section>
    <?php elseif ($document->status === 'draft') : ?>
        <section class="tps-card">
            <p class="tps-muted"><em>O seu perfil nao pode editar linhas neste documento.</em></p>
        </section>
    <?php else : ?>
        <section class="tps-card">
            <p class="tps-muted"><em>Este documento nao e editavel no estado atual.</em></p>
        </section>
    <?php endif; ?>

    <section class="tps-card">
        <h2 class="tps-title-sm"><span class="dashicons dashicons-money-alt tps-icon" aria-hidden="true"></span>Recebimentos</h2>
        <?php if ($document->status === 'issued' && (float) $document->balance_due > 0 && $can_receive_payments) : ?>
            <form method="post" action="<?php echo esc_url(tps_get_action_url()); ?>">
                <?php wp_nonce_field('tps_register_payment'); ?>
                <input type="hidden" name="action" value="tps_register_payment">
                <input type="hidden" name="document_id" value="<?php echo esc_attr((string) $document_id); ?>">
                <input type="hidden" name="redirect_to" value="<?php echo esc_url(tps_get_page_url('tps-documents-add', array('document_id' => (int) $document_id))); ?>">
                <div class="tps-body">
                <div class="tps-grid">
                    <div>
                        <label for="tps-payment-date">Data do Recebimento</label>
                        <input id="tps-payment-date" class="tps-input" type="date" name="payment_date" value="<?php echo esc_attr(date('Y-m-d')); ?>" required>
                    </div>
                    <div>
                        <label for="tps-payment-amount">Valor</label>
                        <input id="tps-payment-amount" class="tps-input" type="number" step="0.01" min="0.01" max="<?php echo esc_attr(number_format((float) $document->balance_due, 2, '.', '')); ?>" name="amount" value="<?php echo esc_attr(number_format((float) $document->balance_due, 2, '.', '')); ?>" required>
                    </div>
                    <div>
                        <label for="tps-payment-method">Metodo</label>
                        <select id="tps-payment-method" class="tps-select" name="method">
                            <?php foreach (TPS_Payments_Model::methods() as $method_key => $method_label) : ?>
                                <option value="<?php echo esc_attr($method_key); ?>"><?php echo esc_html($method_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="tps-payment-reference">Referencia</label>
                        <input id="tps-payment-reference" class="tps-input" type="text" name="reference">
                    </div>
                    <div>
                        <label for="tps-payment-notes">Notas</label>
                        <textarea id="tps-payment-notes" class="tps-textarea" name="notes" rows="3"></textarea>
                    </div>
                </div>
                </div>

                <p class="tps-mt-14 px-3 pb-3 mb-0">
                    <?php submit_button('Registar Recebimento', 'primary', 'submit', false); ?>
                </p>
            </form>
        <?php elseif ($document->status === 'issued' && ! $can_receive_payments) : ?>
            <p class="tps-muted">O seu perfil nao tem permissao para registar recebimentos.</p>
        <?php elseif ($document->status !== 'issued') : ?>
            <p class="tps-muted">Os recebimentos ficam disponiveis apenas depois da emissao do documento.</p>
        <?php else : ?>
            <p class="tps-muted">Este documento ja esta totalmente pago.</p>
        <?php endif; ?>

        <div class="tps-table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Metodo</th>
                        <th>Referencia</th>
                        <th>Valor</th>
                        <th>Recibo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($payment_history)) : ?>
                        <?php foreach ($payment_history as $payment) : ?>
                            <tr>
                                <td><?php echo esc_html((string) $payment->payment_date); ?></td>
                                <td><?php echo esc_html((string) (TPS_Payments_Model::methods()[$payment->method] ?? $payment->method)); ?></td>
                                <td><?php echo esc_html((string) ($payment->reference ?: '-')); ?></td>
                                <td><?php echo esc_html(number_format((float) $payment->amount, 2)); ?></td>
                                <td>
                                    <?php if ($can_receive_payments || $can_export_documents) : ?>
                                        <a class="tps-row-btn tps-btn-primary" href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action' => 'tps_download_payment_receipt', 'payment_id' => (int) $payment->id), tps_get_action_url()), 'tps_download_payment_receipt')); ?>">Recibo</a>
                                    <?php else : ?>
                                        <span class="tps-muted">Sem permissao</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5">Sem recebimentos registados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="tps-card">
        <h2 class="tps-title-sm"><span class="dashicons dashicons-admin-tools tps-icon" aria-hidden="true"></span>Acoes</h2>
        <div class="tps-actions">
            <?php if ($document->status === 'draft' && $can_emit_documents) : ?>
                <form method="post" action="<?php echo esc_url(tps_get_action_url()); ?>">
                    <?php wp_nonce_field('tps_issue_document'); ?>
                    <input type="hidden" name="action" value="tps_issue_document">
                    <input type="hidden" name="document_id" value="<?php echo esc_attr((string) $document_id); ?>">
                    <button class="tps-btn tps-btn-primary"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>Emitir Documento</button>
                </form>
            <?php endif; ?>

            <?php if ($document->status === 'issued' && $can_export_documents) : ?>
                <a class="tps-btn tps-btn-secondary" href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action' => 'tps_download_document_pdf', 'document_id' => (int) $document_id), tps_get_action_url()), 'tps_download_document_pdf')); ?>"><span class="dashicons dashicons-download" aria-hidden="true"></span>Transferir PDF</a>
                <?php if ($can_emit_documents && ! in_array((string) $document->type, array('credit_note', 'debit_note'), true)) : ?>
                    <a class="tps-btn tps-btn-secondary" href="<?php echo esc_url(tps_get_page_url('tps-documents-add', array('document_type' => 'credit_note', 'original_document_id' => (int) $document_id))); ?>"><span class="dashicons dashicons-minus" aria-hidden="true"></span>Criar Nota de Credito</a>
                    <a class="tps-btn tps-btn-secondary" href="<?php echo esc_url(tps_get_page_url('tps-documents-add', array('document_type' => 'debit_note', 'original_document_id' => (int) $document_id))); ?>"><span class="dashicons dashicons-plus" aria-hidden="true"></span>Criar Nota de Debito</a>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($document->status === 'issued' && $can_cancel_documents) : ?>
                <form method="post" action="<?php echo esc_url(tps_get_action_url()); ?>" class="tps-cancel-form">
                    <?php wp_nonce_field('tps_cancel_document'); ?>
                    <input type="hidden" name="action" value="tps_cancel_document">
                    <input type="hidden" name="document_id" value="<?php echo esc_attr((string) $document_id); ?>">
                    <input class="tps-input" type="text" name="cancel_reason" placeholder="Motivo do cancelamento" required>
                    <button class="tps-btn tps-btn-danger" onclick="return confirm('Cancelar este documento?');"><span class="dashicons dashicons-dismiss" aria-hidden="true"></span>Cancelar Documento</button>
                </form>
            <?php endif; ?>

            <?php if ($document->status !== 'issued') : ?>
                <span class="tps-muted">O PDF e o cancelamento so ficam disponiveis apos a emissao.</span>
            <?php elseif (! $can_export_documents && ! $can_cancel_documents && ! $can_emit_documents) : ?>
                <span class="tps-muted">O seu perfil nao tem permissao para acoes adicionais neste documento.</span>
            <?php endif; ?>
        </div>
        <?php if ($document->status === 'cancelled' && ! empty($document->cancel_reason)) : ?>
            <p class="tps-muted"><strong>Motivo do cancelamento:</strong> <?php echo esc_html((string) $document->cancel_reason); ?></p>
        <?php endif; ?>
    </section>

</div>
