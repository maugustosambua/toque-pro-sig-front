<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$customer           = isset( $customer ) ? $customer : null;
$id                 = isset( $id ) ? (int) $id : 0;
$form_title         = isset( $form_title ) ? (string) $form_title : ( $id ? 'Editar Cliente' : 'Adicionar Cliente' );
$form_subtitle      = isset( $form_subtitle ) ? (string) $form_subtitle : ( $id ? 'Atualize o perfil do cliente e os dados de contacto.' : 'Crie um novo perfil de cliente para as suas operações.' );
$form_error_message = isset( $form_error_message ) ? (string) $form_error_message : '';
$back_url           = isset( $back_url ) ? (string) $back_url : tps_get_page_url( 'tps-customers' );

$customer = is_object( $customer ) ? $customer : (object) array(
    'type'    => $customer['type'] ?? '',
    'name'    => $customer['name'] ?? '',
    'nuit'    => $customer['nuit'] ?? '',
    'email'   => $customer['email'] ?? '',
    'phone'   => $customer['phone'] ?? '',
    'city'    => $customer['city'] ?? '',
    'address' => $customer['address'] ?? '',
);
?>
<form class="tps-customer-entry-form" method="post" action="<?php echo esc_url( tps_get_action_url() ); ?>">
    <input type="hidden" name="action" value="tps_save_customer">
    <?php wp_nonce_field( 'tps_save_customer' ); ?>

    <?php if ( $id ) : ?>
        <input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
    <?php endif; ?>

    <?php if ( '' !== $form_error_message ) : ?>
        <div class="tps-notice tps-inline-notice tps-notice--error" role="alert">
            <p class="tps-notice__message"><?php echo esc_html( $form_error_message ); ?></p>
        </div>
    <?php endif; ?>

    <div class="tps-body">
        <div class="tps-grid">
            <div>
                <label for="tps-type">Tipo</label>
                <select id="tps-type" class="tps-select" name="type" required>
                    <option value="individual" <?php selected( $customer->type ?? '', 'individual' ); ?>>Particular</option>
                    <option value="company" <?php selected( $customer->type ?? '', 'company' ); ?>>Empresa</option>
                </select>
            </div>

            <div>
                <label for="tps-name">Nome</label>
                <input id="tps-name" class="tps-input" type="text" name="name" required value="<?php echo esc_attr( $customer->name ?? '' ); ?>">
            </div>

            <div>
                <label for="tps-nuit">NUIT</label>
                <input id="tps-nuit" class="tps-input" type="text" name="nuit" value="<?php echo esc_attr( $customer->nuit ?? '' ); ?>">
            </div>

            <div>
                <label for="tps-email">Email</label>
                <input id="tps-email" class="tps-input" type="email" name="email" value="<?php echo esc_attr( $customer->email ?? '' ); ?>">
            </div>

            <div>
                <label for="tps-phone">Telefone</label>
                <input id="tps-phone" class="tps-input" type="text" name="phone" value="<?php echo esc_attr( $customer->phone ?? '' ); ?>">
            </div>

            <div>
                <label for="tps-city">Morada</label>
                <input id="tps-city" class="tps-input" type="text" name="city" value="<?php echo esc_attr( $customer->city ?? '' ); ?>">
            </div>

            <div class="tps-field-full">
                <label for="tps-address">Endereço</label>
                <textarea id="tps-address" class="tps-textarea" name="address"><?php echo esc_textarea( $customer->address ?? '' ); ?></textarea>
            </div>
        </div>
    </div>

    <div class="tps-actions">
        <?php submit_button( $id ? 'Atualizar Cliente' : 'Criar Cliente', 'primary', 'submit', false ); ?>
    </div>
</form>
