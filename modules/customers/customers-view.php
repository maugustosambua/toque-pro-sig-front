<?php // modules/customers/customers-view.php
// Impede acesso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$id = isset( $_GET['customer_id'] ) ? (int) $_GET['customer_id'] : 0;
$customer = $id ? TPS_Customers_Model::get( $id ) : null;
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html( $id ? 'Edit Customer' : 'Add Customer' ); ?></h1>
    <hr class="wp-header-end">

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="tps_save_customer">
        <?php wp_nonce_field( 'tps_save_customer' ); ?>

        <?php if ( $id ) : ?>
            <input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
        <?php endif; ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="tps-type">Type</label></th>
                <td>
                    <select id="tps-type" name="type" required>
                        <option value="individual" <?php selected( $customer->type ?? '', 'individual' ); ?>>Individual</option>
                        <option value="company" <?php selected( $customer->type ?? '', 'company' ); ?>>Company</option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="tps-name">Name</label></th>
                <td>
                    <input id="tps-name" type="text" name="name" class="regular-text" required value="<?php echo esc_attr( $customer->name ?? '' ); ?>">
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="tps-nuit">NUIT</label></th>
                <td>
                    <input id="tps-nuit" type="text" name="nuit" class="regular-text" value="<?php echo esc_attr( $customer->nuit ?? '' ); ?>">
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="tps-email">Email</label></th>
                <td>
                    <input id="tps-email" type="email" name="email" class="regular-text" value="<?php echo esc_attr( $customer->email ?? '' ); ?>">
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="tps-phone">Phone</label></th>
                <td>
                    <input id="tps-phone" type="text" name="phone" class="regular-text" value="<?php echo esc_attr( $customer->phone ?? '' ); ?>">
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="tps-address">Address</label></th>
                <td>
                    <textarea id="tps-address" name="address" class="large-text"><?php echo esc_textarea( $customer->address ?? '' ); ?></textarea>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="tps-city">City</label></th>
                <td>
                    <input id="tps-city" type="text" name="city" class="regular-text" value="<?php echo esc_attr( $customer->city ?? '' ); ?>">
                </td>
            </tr>
        </table>

        <?php submit_button( $id ? 'Update Customer' : 'Create Customer' ); ?>
    </form>
</div>
