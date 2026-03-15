<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$payments = TPS_Payments_Model::get_payments(
    array(
        'per_page' => 100,
        'offset'   => 0,
    )
);
$methods = TPS_Payments_Model::methods();
?>

<div class="wrap tps-documents-modern">
    <section class="tps-header">
        <div>
            <h1>Recebimentos</h1>
            <p class="tps-subtitle">Historico de pagamentos registados e emissao de recibos.</p>
        </div>
    </section>

    <section class="tps-table-shell">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Documento</th>
                    <th>Cliente</th>
                    <th>Metodo</th>
                    <th>Referencia</th>
                    <th>Valor</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $payments ) ) : ?>
                    <tr>
                        <td colspan="7">Sem recebimentos registados.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $payments as $payment ) : ?>
                        <tr>
                            <td><?php echo esc_html( (string) $payment->payment_date ); ?></td>
                            <td><?php echo esc_html( strtoupper( (string) $payment->document_type ) . ' #' . (string) $payment->document_number ); ?></td>
                            <td><?php echo esc_html( (string) $payment->customer_name ); ?></td>
                            <td><?php echo esc_html( isset( $methods[ $payment->method ] ) ? $methods[ $payment->method ] : $payment->method ); ?></td>
                            <td><?php echo esc_html( (string) ( $payment->reference ? $payment->reference : '-' ) ); ?></td>
                            <td><?php echo esc_html( number_format( (float) $payment->amount, 2 ) ); ?></td>
                            <td>
                                <a class="tps-row-btn" href="<?php echo esc_url( tps_get_page_url( 'tps-documents-add', array( 'document_id' => (int) $payment->document_id ) ) ); ?>">Abrir Documento</a>
                                <a class="tps-row-btn tps-btn-primary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'tps_download_payment_receipt', 'payment_id' => (int) $payment->id ), tps_get_action_url() ), 'tps_download_payment_receipt' ) ); ?>">Recibo</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>
