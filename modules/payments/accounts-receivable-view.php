<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$items                 = TPS_Payments_Model::get_accounts_receivable();
$payment_status_labels = TPS_Documents_Model::payment_statuses();
?>

<div class="wrap tps-documents-modern">
    <section class="tps-header">
        <div class="tps-header-row tps-header-row--back">
            <a class="tps-back-btn" href="<?php echo esc_url( tps_get_page_url( 'tps-payments' ) ); ?>">
                <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
                <span>Voltar</span>
            </a>
            <div class="tps-header-content">
                <h1>Contas a Receber</h1>
                <p class="tps-subtitle">Documentos emitidos com saldo pendente, incluindo valores vencidos.</p>
            </div>
        </div>
    </section>

    <section class="tps-table-shell">
        <table>
            <thead>
                <tr>
                    <th>Documento</th>
                    <th>Cliente</th>
                    <th>Emissao</th>
                    <th>Vencimento</th>
                    <th>Estado</th>
                    <th>Recebido</th>
                    <th>Saldo</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $items ) ) : ?>
                    <tr>
                        <td colspan="8">Sem contas a receber neste momento.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $items as $item ) : ?>
                        <tr>
                            <td><?php echo esc_html( strtoupper( (string) $item->type ) . ' #' . (string) $item->number ); ?></td>
                            <td><?php echo esc_html( (string) $item->customer_name ); ?></td>
                            <td><?php echo esc_html( (string) $item->issue_date ); ?></td>
                            <td><?php echo esc_html( (string) ( $item->due_date ? $item->due_date : '-' ) ); ?></td>
                            <td><?php echo esc_html( (string) ( $payment_status_labels[ $item->payment_status ] ?? $item->payment_status ) ); ?></td>
                            <td><?php echo esc_html( number_format( (float) $item->paid_total, 2 ) ); ?></td>
                            <td><?php echo esc_html( number_format( (float) $item->balance_due, 2 ) ); ?></td>
                            <td><a class="tps-row-btn" href="<?php echo esc_url( tps_get_page_url( 'tps-documents-add', array( 'document_id' => (int) $item->id ) ) ); ?>">Abrir</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>
