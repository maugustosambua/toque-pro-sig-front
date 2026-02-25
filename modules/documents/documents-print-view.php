<?php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$document_id = isset( $_GET['document_id'] ) ? (int) $_GET['document_id'] : 0;

if ( ! $document_id ) {
    wp_die();
}

$document = TPS_Documents_Model::get( $document_id );
if ( ! $document ) {
    wp_die();
}

$lines = TPS_Document_Lines_Model::get_by_document( $document_id );

$subtotal = TPS_Document_Lines_Model::document_total( $document_id );
$iva      = tps_calculate_iva( $subtotal );
$total    = $subtotal + $iva;

// Empresa (Configurações)
$settings = get_option( 'tps_settings', array() );
$company  = $settings['company_name'] ?? '';
$nuit     = $settings['company_nuit'] ?? '';
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Document <?php echo esc_html( $document->number ); ?></title>

        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            .wrap { max-width: 800px; margin: 0 auto; }
            .header { display:flex; justify-content:space-between; margin-bottom:20px; }
            .box { border:1px solid #ccc; padding:10px; margin-bottom:10px; }
            table { width:100%; border-collapse:collapse; margin-top:10px; }
            th, td { border:1px solid #ccc; padding:8px; }
            th { text-align:left; }
            .right { text-align:right; }
            .totals { max-width: 300px; margin-left:auto; }
            @media print {
                .no-print { display:none; }
            }
        </style>
    </head>

    <body>
        <div class="wrap">

            <div class="no-print" style="margin-bottom:15px;">
                <button onclick="window.print()">Print / Save as PDF</button>
            </div>

            <div class="header">
                <div>
                    <h2><?php echo esc_html( $company ); ?></h2>
                    <div>NUIT: <?php echo esc_html( $nuit ); ?></div>
                </div>

                <div class="right">
                    <h2><?php echo esc_html( strtoupper( $document->type ) ); ?></h2>
                    <div><strong>Number:</strong> <?php echo esc_html( $document->number ); ?></div>
                    <div><strong>Date:</strong> <?php echo esc_html( $document->issue_date ); ?></div>
                    <div><strong>Status:</strong> <?php echo esc_html( $document->status ); ?></div>
                </div>
            </div>

            <div class="box">
                <strong>Customer ID:</strong> <?php echo esc_html( $document->customer_id ); ?>
            </div>

            <table>
                <thead>
                <tr>
                    <th>Description</th>
                    <th class="right" style="width:90px;">Qty</th>
                    <th class="right" style="width:120px;">Unit</th>
                    <th class="right" style="width:120px;">Subtotal</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ( $lines as $line ) : ?>
                    <tr>
                        <td><?php echo esc_html( $line->description ); ?></td>
                        <td class="right"><?php echo esc_html( $line->quantity ); ?></td>
                        <td class="right"><?php echo number_format( (float) $line->unit_price, 2 ); ?></td>
                        <td class="right"><?php echo number_format( TPS_Document_Lines_Model::line_total( $line ), 2 ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <table class="totals">
                <tr>
                    <th>Subtotal</th>
                    <td class="right"><?php echo number_format( $subtotal, 2 ); ?></td>
                </tr>
                <tr>
                    <th>IVA (<?php echo number_format( tps_get_iva_rate() * 100, 2 ); ?>%)</th>
                    <td class="right"><?php echo number_format( $iva, 2 ); ?></td>
                </tr>
                <tr>
                    <th>Total</th>
                    <td class="right"><strong><?php echo number_format( $total, 2 ); ?></strong></td>
                </tr>
            </table>

        </div>
    </body>
</html>
