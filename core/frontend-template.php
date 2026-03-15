<?php
// Impede acesso directo.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class( 'tps-standalone-app' ); ?>>
<?php wp_body_open(); ?>
<main class="tps-standalone-shell">
    <?php TPS_Frontend_App::render_template_content(); ?>
</main>
<?php wp_footer(); ?>
</body>
</html>
