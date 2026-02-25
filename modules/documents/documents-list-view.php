<?php
// Impede acesso directo
if (! defined('ABSPATH')) {
    exit;
}

$list_table = new TPS_Documents_List_Table();
$list_table->prepare_items();
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Documents</h1>

    <a href="<?php echo esc_url(admin_url('admin.php?page=tps-documents-add')); ?>" class="page-title-action">
        Add New
    </a>
    <hr class="wp-header-end">

    <form method="get">
        <input type="hidden" name="page" value="tps-documents">

        <?php $list_table->views(); ?>

        <?php $list_table->display(); ?>
    </form>
</div>
