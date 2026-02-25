<?php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$document_id = isset( $_GET['document_id'] ) ? (int) $_GET['document_id'] : 0;

// Se não existe documento, mostrar formulário de criação
if ( ! $document_id ) {

    $types = TPS_Documents_Model::types();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Create Document</h1>
        <hr class="wp-header-end">

        <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
            <?php wp_nonce_field( 'tps_save_document' ); ?>
            <input type="hidden" name="action" value="tps_save_document">

            <table class="widefat">
                    <thead>
                        <th>Type</th>
                    <th>Customer ID</th>
                        <th>Issue Date</th>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <select name="document_type" class="widefat" required>
                                    <?php foreach ( $types as $key => $label ) : ?>
                                        <option value="<?php echo esc_attr( $key ); ?>">
                                            <?php echo esc_html( $label ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span>Select the document type</span>
                            </td>
                            <td>
                                <input type="number" name="customer_id" class="widefat" required>
                                <span>Insert Customer ID</span>
                            </td>
                            <td>
                                <input type="date" name="issue_date" value="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>" class="widefat">
                            </td>
                        </tr>
                    </tbody>
            </table>

            <?php submit_button( 'Create Draft' ); ?>
        </form>
    </div>
    <?php
    return;
}

// Documento actual
$document = TPS_Documents_Model::get( $document_id );

// Linhas do documento
$lines = TPS_Document_Lines_Model::get_by_document( $document_id );
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Edit Document</h1>
    <hr class="wp-header-end">

    <table class="widefat">
                <thead>
                    <tr>
                        <th><strong>Document details</strong></th>
                        <th></th>
                    </tr>
                </thead>
        <tbody>
            <tr><th>Type:</th><td><?php echo esc_html( $document->type ); ?></td></tr>
            <tr><th>Number:</th><td><?php echo esc_html( $document->number ); ?></td></tr>
            <tr><th>Customer:</th><td><?php echo esc_html( $document->customer_id ); ?></td>
            </tr><tr><th>Status:</th><td><?php echo esc_html( $document->status ); ?></td></tr>
            <tr><th>Issue Date:</th><td><?php echo esc_html( $document->issue_date ); ?></td></tr>
        </tbody>
    </table>

    <h2>Lines</h2>

    <table class="widefat">
        <thead>
            <tr>
                <th><strong>Description</strong></th>
                <th><strong>Quantity</strong></th>
                <th><strong>Unit Price</strong></th>
                <th><strong>Subtotal</strong></th>
                <th><strong>Actions</strong></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( $lines ) : ?>
                <?php foreach ( $lines as $line ) : ?>
                    <tr>
                        <td><?php echo esc_html( $line->description ); ?></td>
                        <td><?php echo esc_html( $line->quantity ); ?></td>
                        <td><?php echo esc_html( $line->unit_price ); ?></td>
                        <td><?php echo esc_html( number_format( TPS_Document_Lines_Model::line_total( $line ), 2 ) ); ?></td>

                        <td>
                            <?php if ( $document->status === 'draft' ) : ?>
                                <a href="<?php echo wp_nonce_url(
                                    admin_url( 'admin-post.php?action=tps_delete_line&line_id=' . $line->id . '&document_id=' . $document_id ),
                                    'tps_delete_line'
                                ); ?>">Delete</a>
                            <?php else : ?>
                                —
                            <?php endif; ?>
                        </td>

                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4">No lines yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <table class="widefat">
        <tr>
            <th>SUBTOTAL:</th>
            <th>
                <strong><?php echo number_format( TPS_Document_Lines_Model::document_total( $document_id ), 2 ); ?></strong>
            </th>
            <th></th>
        </tr>
        <tr>
            <th>IVA (<?php echo number_format( tps_get_iva_rate() * 100, 2 ); ?>%)</th>
            <th>
                <strong>
                    <?php $subtotal = TPS_Document_Lines_Model::document_total( $document_id );
                    echo number_format( tps_calculate_iva( $subtotal ), 2 ); ?>
                </strong>
            </th>
        </tr>
        <tr>
            <th>TOTAL:</th>
            <th>
                <strong><?php echo number_format( TPS_Document_Lines_Model::document_total_with_tax( $document_id ), 2 ); ?></strong>
            </th>
        </tr>
    </table>


        <?php if ( $document->status === 'draft' ) : ?>

        <h3>Add Line</h3>

        <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
            <?php wp_nonce_field( 'tps_add_line' ); ?>
            <input type="hidden" name="action" value="tps_add_line">
            <input type="hidden" name="document_id" value="<?php echo esc_attr( $document_id ); ?>">

            <table class="widefat">
                <thead>
                    <tr>
                <th>Description</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th></th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td><input type="text" name="description" class="widefat" required></td>
                        <td><input type="number" step="0.01" name="quantity" value="1" required></td>
                        <td><input type="number" step="0.01" name="unit_price" value="0" required></td>
                        <td><button class="button button-primary">Add</button></td>
                    </tr>
                </tbody>
            </table>
        </form>

        <?php else : ?>
            <p><em>This document is issued and cannot be edited.</em></p>
        <?php endif; ?>


    <?php if ( $document->status === 'draft' ) : ?>
        <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
            <?php wp_nonce_field( 'tps_issue_document' ); ?>
            <input type="hidden" name="action" value="tps_issue_document">
            <input type="hidden" name="document_id" value="<?php echo esc_attr( $document_id ); ?>">
            <button class="button button-primary">Issue Document</button>
        </form>
    <?php endif; ?>


    <?php if ( $document->status === 'issued' ) : ?>
        <a class="button"
        href="<?php echo esc_url(
            wp_nonce_url(
                admin_url( 'admin-post.php?action=tps_download_document_pdf&document_id=' . $document_id ),
                'tps_download_document_pdf'
            )
        ); ?>">
        Download PDF
        </a>
    <?php else : ?>
        <span class="description">PDF available after issuing the document.</span>
    <?php endif; ?>


    <?php if ( $document->status === 'issued' ) : ?>
        <a class="button"
        href="<?php echo esc_url(
            wp_nonce_url(
                admin_url( 'admin-post.php?action=tps_cancel_document&document_id=' . $document_id ),
                'tps_cancel_document'
            )
        ); ?>"
        onclick="return confirm('Cancel this document?');">
        Cancel Document
        </a>
    <?php endif; ?>
</div>
