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

// Empresa (Configurações)
$settings = get_option('tps_settings', array());
$company  = $settings['company_name'] ?? '';
$nuit     = $settings['company_nuit'] ?? '';
$type_labels = TPS_Documents_Model::types();
$status_labels = TPS_Documents_Model::statuses();
$back_url = admin_url('admin.php?page=tps-documents-add&document_id=' . (int) $document_id);
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Documento <?php echo esc_html($document->number); ?></title>

        <style>
            body { font-family: "Segoe UI", Arial, sans-serif; font-size: 12px; color: #1a2433; background: #f4f7fb; }
            .wrap { max-width: 860px; margin: 20px auto; background: #fff; border: 1px solid #dde5ef; border-radius: 12px; padding: 18px; }
            .toolbar { margin-bottom: 14px; display: flex; gap: 8px; flex-wrap: wrap; }
            .toolbar button { border: 1px solid #0f5ea8; background: #0f5ea8; color: #fff; border-radius: 8px; padding: 8px 12px; cursor: pointer; transition: all .16s ease; }
            .toolbar button:hover { transform: translateY(-1px); box-shadow: 0 8px 16px rgba(26, 36, 51, .10); background: #0d528f; border-color: #0d528f; }
            .toolbar button:focus-visible { outline: 2px solid #0f5ea8; outline-offset: 2px; }
            .toolbar .back-btn { display: inline-flex; align-items: center; gap: 6px; border: 1px solid #dde5ef; background: #fff; color: #1a2433; border-radius: 999px; padding: 8px 12px; text-decoration: none; font-weight: 600; line-height: 1; transition: all .16s ease; }
            .toolbar .back-btn:hover { border-color: #b8cbe3; background: #f6faff; color: #123f67; transform: translateY(-1px); }
            .toolbar .back-btn:focus-visible { outline: 2px solid #0f5ea8; outline-offset: 2px; }
            .toolbar .back-btn svg { width: 14px; height: 14px; }
            .header { display:flex; justify-content:space-between; margin-bottom:18px; background: linear-gradient(115deg, #f8fbff 0%, #edf4fc 55%, #e4edf8 100%); border: 1px solid #dde5ef; border-radius: 10px; padding: 12px; }
            .meta h2 { margin: 0 0 4px; }
            .meta div { color: #5f6b7a; }
            .box { border:1px solid #dde5ef; border-radius: 10px; padding:10px; margin-bottom:12px; background: #fff; }
            table { width:100%; border-collapse:collapse; margin-top:10px; }
            th, td { border:1px solid #dde5ef; padding:8px; }
            th { text-align:left; background: #f8fbff; }
            .right { text-align:right; }
            .totals { max-width: 320px; margin-left:auto; margin-top: 14px; }
            @media print {
                .no-print { display:none; }
                body { background: #fff; }
                .wrap { margin: 0 auto; border: 0; border-radius: 0; padding: 0; }
            }
        </style>
    </head>

    <body>
        <div class="wrap">

            <div class="no-print toolbar">
                <a class="back-btn" href="<?php echo esc_url($back_url); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M14.7 5.3a1 1 0 0 1 0 1.4L10.41 11H20a1 1 0 1 1 0 2h-9.59l4.3 4.3a1 1 0 0 1-1.42 1.4l-6-6a1 1 0 0 1 0-1.4l6-6a1 1 0 0 1 1.41 0Z"/></svg>
                    <span>Voltar</span>
                </a>
                <button onclick="window.print()">Imprimir / Guardar como PDF</button>
            </div>

            <div class="header">
                <div class="meta">
                    <h2><?php echo esc_html($company); ?></h2>
                    <div>NUIT: <?php echo esc_html($nuit); ?></div>
                </div>

                <div class="right meta">
                    <h2><?php echo esc_html((string) ($type_labels[$document->type] ?? strtoupper($document->type))); ?></h2>
                    <div><strong>Número:</strong> <?php echo esc_html($document->number); ?></div>
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
                    <th>Descrição</th>
                    <th class="right" style="width:90px;">Qtd.</th>
                    <th class="right" style="width:120px;">Unitário</th>
                    <th class="right" style="width:120px;">Subtotal</th>
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
    </body>
</html>
