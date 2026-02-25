<?php // modules/customers/customers-list-view.php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$list_table = new TPS_Customers_List_Table();
$list_table->prepare_items();

// URL do Export (lista completa ou filtrada)
$export_url = wp_nonce_url(
    add_query_arg(
        array(
            'action' => 'tps_export_customers',
            'type'   => isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : '',
            's'      => isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '',
        ),
        admin_url( 'admin-post.php' )
    ),
    'tps_export_customers'
);
?>

<div class="wrap">

    <?php
    $imported = isset( $_GET['imported'] ) ? (int) $_GET['imported'] : 0;
    $skipped_duplicates = isset( $_GET['skipped_duplicates'] ) ? (int) $_GET['skipped_duplicates'] : 0;
    $skipped_invalid = isset( $_GET['skipped_invalid'] ) ? (int) $_GET['skipped_invalid'] : 0;

    if ( $imported || $skipped_duplicates || $skipped_invalid ) :
    ?>
        <div class="notice notice-success is-dismissible">
            <p>
                Import finished:
                <strong><?php echo (int) $imported; ?></strong> imported,
                <strong><?php echo (int) $skipped_duplicates; ?></strong> duplicates skipped,
                <strong><?php echo (int) $skipped_invalid; ?></strong> invalid rows skipped.
            </p>
        </div>
    <?php endif; ?>

    <h1 class="wp-heading-inline">Customers</h1>

    <a href="<?php echo esc_url( admin_url( 'admin.php?page=tps-customers-add' ) ); ?>" class="page-title-action">
        Add New
    </a>

    <a href="<?php echo esc_url( admin_url( 'admin.php?page=tps-customers-import' ) ); ?>" class="page-title-action">
        Import
    </a>

    <a href="<?php echo esc_url( $export_url ); ?>" class="page-title-action">
        Export
    </a>

    <hr class="wp-header-end">

    <form method="get">
        <input type="hidden" name="page" value="tps-customers">

        <?php $list_table->search_box( 'Search Customers', 'tps-customers' ); ?>

        <?php $list_table->views(); ?>

        <?php $list_table->display(); ?>
    </form>
</div>
