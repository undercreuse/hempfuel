<?php
/**
 * Degressive product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/degressive.php
 *
 * @package WooCommerce Produits Dégressifs
 */

defined('ABSPATH') || exit;

global $product;

if (!$product->is_purchasable()) {
    return;
}

echo wc_get_stock_html($product);

if ($product->is_in_stock()) : ?>

    <?php do_action('woocommerce_before_add_to_cart_form'); ?>

    <form class="cart" action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>" method="post" enctype="multipart/form-data">
        <?php do_action('woocommerce_before_add_to_cart_button'); ?>

        <?php
        // Récupérer les paramètres du produit dégressif
        $product_id = $product->get_id();
        $min_qty = floatval(get_post_meta($product_id, '_degressive_min_qty', true) ?: 1);
        $max_qty = floatval(get_post_meta($product_id, '_degressive_max_qty', true) ?: 100);
        
        // Le tableau des prix est maintenant affiché uniquement via le shortcode [degressive_table]
        ?>

        <div class="degressive-product-form">
            <div class="degressive-qty-wrapper">
                <label for="degressive-qty"><?php esc_html_e('Quantité (grammes)', 'wc-degressive-pricing'); ?></label>
                <input 
                    type="number" 
                    id="degressive-qty" 
                    name="degressive_qty" 
                    value="<?php echo esc_attr($min_qty); ?>" 
                    min="<?php echo esc_attr($min_qty); ?>" 
                    max="<?php echo esc_attr($max_qty); ?>" 
                    step="1" 
                />
                <input type="hidden" name="quantity" value="<?php echo esc_attr($min_qty); ?>" />
                <input type="hidden" name="add-to-cart" value="<?php echo absint($product->get_id()); ?>" />
            </div>
            
            <div class="degressive-price-display">
                <p><?php esc_html_e('Prix calculé:', 'wc-degressive-pricing'); ?> <span id="degressive-calculated-price"></span></p>
            </div>
            
            <div class="degressive-add-to-cart">
                <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" class="single_add_to_cart_button button alt"><?php echo esc_html($product->single_add_to_cart_text()); ?></button>
            </div>
        </div>

        <?php do_action('woocommerce_after_add_to_cart_button'); ?>
    </form>

    <?php do_action('woocommerce_after_add_to_cart_form'); ?>

<?php endif; ?>
