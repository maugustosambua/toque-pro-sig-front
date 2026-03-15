<?php
// Impede acesso directo
if (! defined('ABSPATH')) {
    exit;
}

$document_id = isset($_GET['document_id']) ? (int) $_GET['document_id'] : 0;

if (! $document_id) {
    wp_die();
}

$document = TPS_Documents_Model::get($document_id);
if (! $document) {
    wp_die();
}

$lines = TPS_Document_Lines_Model::get_by_document($document_id);
$fiscal_totals = TPS_Documents_Model::fiscal_totals($document_id);
$subtotal = (float) $fiscal_totals['subtotal'];
$iva      = (float) $fiscal_totals['iva'];
$retencao = (float) $fiscal_totals['withholding_amount'];
$total    = (float) $fiscal_totals['payable_total'];

// Empresa (Configuracoes)
$settings = get_option('tps_settings', array());
$company  = $settings['company_name'] ?? '';
$nuit     = $settings['company_nuit'] ?? '';
$type_labels = TPS_Documents_Model::types();
$status_labels = TPS_Documents_Model::statuses();
$back_url = tps_get_page_url('tps-documents-add', array('document_id' => (int) $document_id));

$print_css_url = tps_get_asset_url( 'assets/css/document-print.css' );
$print_css_ver = tps_get_asset_version( 'assets/css/document-print.css' );

$print_js_url = tps_get_asset_url( 'assets/js/document-print.js' );
$print_js_ver = tps_get_asset_version( 'assets/js/document-print.js' );
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Documento <?php echo esc_html($document->number); ?></title>
        <link rel="stylesheet" href="<?php echo esc_url($print_css_url); ?>?ver=<?php echo esc_attr($print_css_ver); ?>">
    </head>

    <body>
        <div class="wrap">

            <div class="no-print toolbar">
                <a class="back-btn" href="<?php echo esc_url($back_url); ?>">
                    <span>Voltar</span>
                </a>
                <button id="tps-print-btn" type="button">
                    <span>Imprimir / Guardar como PDF</span>
                </button>
            </div>

            <div class="header">
                <div class="meta">
                    <h2><?php echo esc_html($company); ?></h2>
                    <div>NUIT: <?php echo esc_html($nuit); ?></div>
                </div>

                <div class="right meta">
                    <h2><?php echo esc_html((string) ($type_labels[$document->type] ?? strtoupper($document->type))); ?></h2>
                    <div><strong>Numero:</strong> <?php echo esc_html($document->number); ?></div>
                    <div><strong>Data:</strong> <?php echo esc_html($document->issue_date); ?></div>
                    <div><strong>Estado:</strong> <?php echo esc_html((string) ($status_labels[$document->status] ?? $document->status)); ?></div>
                </div>
            </div>

            <div class="box">
                <div><strong>Cliente:</strong> <?php echo esc_html((string) ($document->customer_name ?: ('#' . (int) $document->customer_id))); ?></div>
                <?php if (! empty($document->customer_nuit)) : ?>
                    <div><strong>NUIT do Cliente:</strong> <?php echo esc_html((string) $document->customer_nuit); ?></div>
                <?php endif; ?>
                <?php if (! empty($document->customer_email)) : ?>
                    <div><strong>Email:</strong> <?php echo esc_html((string) $document->customer_email); ?></div>
                <?php endif; ?>
                <?php if (! empty($document->customer_phone)) : ?>
                    <div><strong>Telefone:</strong> <?php echo esc_html((string) $document->customer_phone); ?></div>
                <?php endif; ?>
                <div><strong>ID do Cliente:</strong> <?php echo esc_html((string) $document->customer_id); ?></div>
                <?php if (! empty($document->original_document_id)) : ?>
                    <div><strong>Documento Original:</strong> #<?php echo esc_html((string) ($document->original_document_number ?? $document->original_document_id)); ?></div>
                    <?php if (! empty($document->adjustment_reason)) : ?>
                        <div><strong>Justificativa:</strong> <?php echo esc_html((string) $document->adjustment_reason); ?></div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <table>
                <thead>
                <tr>
                    <th>Descricao</th>
                    <th class="right tps-col-qtd">Qtd.</th>
                    <th class="right tps-col-unit">Unitario</th>
                    <th class="right tps-col-subtotal">Subtotal</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($lines as $line) : ?>
                    <tr>
                        <td><?php echo esc_html($line->description); ?></td>
                        <td class="right"><?php echo esc_html($line->quantity); ?></td>
                        <td class="right"><?php echo number_format((float) $line->unit_price, 2); ?></td>
                        <td class="right"><?php echo number_format(TPS_Document_Lines_Model::line_total($line), 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <table class="totals">
                <tr>
                    <th>Subtotal</th>
                    <td class="right"><?php echo number_format($subtotal, 2); ?></td>
                </tr>
                <tr>
                    <th>IVA (<?php echo number_format(tps_get_iva_rate() * 100, 2); ?>%)</th>
                    <td class="right"><?php echo number_format($iva, 2); ?></td>
                </tr>
                <tr>
                    <th>Retencao (<?php echo number_format((float) $document->withholding_rate, 2); ?>%)</th>
                    <td class="right">-<?php echo number_format($retencao, 2); ?></td>
                </tr>
                <tr>
                    <th>Total Liquido</th>
                    <td class="right"><strong><?php echo number_format($total, 2); ?></strong></td>
                </tr>
            </table>

        </div>

        <script src="<?php echo esc_url($print_js_url); ?>?ver=<?php echo esc_attr($print_js_ver); ?>"></script>
    </body>
</html>
