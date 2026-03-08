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

$subtotal = TPS_Document_Lines_Model::document_total($document_id);
$iva      = tps_calculate_iva($subtotal);
$total    = $subtotal + $iva;

// Empresa (Configuracoes)
$settings = get_option('tps_settings', array());
$company  = $settings['company_name'] ?? '';
$nuit     = $settings['company_nuit'] ?? '';
$type_labels = TPS_Documents_Model::types();
$status_labels = TPS_Documents_Model::statuses();
$back_url = admin_url('admin.php?page=tps-documents-add&document_id=' . (int) $document_id);

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
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M14.7 5.3a1 1 0 0 1 0 1.4L10.41 11H20a1 1 0 1 1 0 2h-9.59l4.3 4.3a1 1 0 0 1-1.42 1.4l-6-6a1 1 0 0 1 0-1.4l6-6a1 1 0 0 1 1.41 0Z"/></svg>
                    <span>Voltar</span>
                </a>
                <button id="tps-print-btn" type="button">Imprimir / Guardar como PDF</button>
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
                    <th>Total</th>
                    <td class="right"><strong><?php echo number_format($total, 2); ?></strong></td>
                </tr>
            </table>

        </div>

        <script src="<?php echo esc_url($print_js_url); ?>?ver=<?php echo esc_attr($print_js_ver); ?>"></script>
    </body>
</html>
