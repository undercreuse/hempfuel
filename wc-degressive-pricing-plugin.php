<?php
/**
 * Plugin Name: WooCommerce Produits Dégressifs
 * Description: Ajoute un type de produit avec prix dégressif basé sur une formule personnalisable
 * Version: 1.0.5
 * Author: Claude
 * Text Domain: wc-degressive-pricing
 * Domain Path: /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 8.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Vérifier si WooCommerce est activé
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    class WC_Degressive_Pricing {

        public function __construct() {
            // Ajouter le type de produit dégressif
            add_filter('product_type_selector', array($this, 'add_degressive_product_type'));
            
            // Ajouter les champs de configuration pour le produit dégressif
            add_action('woocommerce_product_options_general_product_data', array($this, 'add_degressive_product_options'));
            
            // HOOKS DE SAUVEGARDE MULTIPLES POUR FORCER LA SAUVEGARDE
            add_action('woocommerce_process_product_meta', array($this, 'save_degressive_product_options'), 5, 1);
            add_action('save_post', array($this, 'save_degressive_product_options'), 5, 1);
            add_action('wp_insert_post', array($this, 'save_degressive_product_options'), 5, 1);
            add_action('edit_post', array($this, 'save_degressive_product_options'), 5, 1);
            
            // Modifier l'affichage du prix sur la page produit
            add_filter('woocommerce_get_price_html', array($this, 'display_degressive_price_html'), 10, 2);
            
            // Ajouter des scripts et styles personnalisés
            add_action('wp_enqueue_scripts', array($this, 'enqueue_degressive_scripts'));
            
            // Ajouter des scripts admin
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
            
            // Mettre à jour le prix du panier en fonction de la quantité
            add_action('woocommerce_before_calculate_totals', array($this, 'update_cart_price'), 10, 1);
            
            // Gérer l'ajout au panier avec quantité personnalisée
            add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_degressive_add_to_cart'), 10, 3);
            add_filter('woocommerce_add_cart_item_data', array($this, 'add_degressive_cart_item_data'), 10, 3);
            
            // Charger les traductions
            add_action('init', array($this, 'load_plugin_textdomain'));
            
            // Ajouter un onglet spécifique pour le produit dégressif
            add_filter('woocommerce_product_data_tabs', array($this, 'add_degressive_product_tab'));
            
            // Contenu de l'onglet produit dégressif
            add_action('woocommerce_product_data_panels', array($this, 'add_degressive_product_tab_content'));
        }

        /**
         * Ajouter le type de produit dégressif à la liste des types de produits
         */
        public function add_degressive_product_type($types) {
            $types['degressive'] = __('Produit dégressif', 'wc-degressive-pricing');
            return $types;
        }

        /**
         * Ajouter un onglet pour le produit dégressif
         */
        public function add_degressive_product_tab($tabs) {
            $tabs['degressive'] = array(
                'label'    => __('Pricing Formula', 'wc-degressive-pricing'),
                'target'   => 'degressive_product_options',
                'class'    => array('show_if_degressive'),
                'priority' => 21
            );
            return $tabs;
        }

        /**
         * Contenu de l'onglet du produit dégressif
         */
        public function add_degressive_product_tab_content() {
            echo '<div id="degressive_product_options" class="panel woocommerce_options_panel">';
            $this->add_degressive_product_options();
            echo '</div>';
        }

        /**
         * Ajouter les champs de configuration pour le produit dégressif
         */
        public function add_degressive_product_options() {
            global $post;
            
            echo '<div class="options_group show_if_degressive">';
            
            // Coefficient (a dans la formule P = a * q^b)
            woocommerce_wp_text_input(array(
                'id'          => '_degressive_coefficient',
                'label'       => __('Coefficient (a)', 'wc-degressive-pricing'),
                'desc_tip'    => true,
                'description' => __('Coefficient "a" dans la formule de prix P = a * q^b (ex: 8.45)', 'wc-degressive-pricing'),
                'type'        => 'number',
                'custom_attributes' => array(
                    'step' => '0.01',
                    'min'  => '0.01'
                ),
                'value'       => get_post_meta($post->ID, '_degressive_coefficient', true) ?: '8.45'
            ));
            
            // Exposant (b dans la formule P = a * q^b)
            woocommerce_wp_text_input(array(
                'id'          => '_degressive_exponent',
                'label'       => __('Exposant (b)', 'wc-degressive-pricing'),
                'desc_tip'    => true,
                'description' => __('Exposant "b" dans la formule de prix P = a * q^b (ex: 0.873)', 'wc-degressive-pricing'),
                'type'        => 'number',
                'custom_attributes' => array(
                    'step' => '0.001',
                    'min'  => '0.001',
                    'max'  => '1'
                ),
                'value'       => get_post_meta($post->ID, '_degressive_exponent', true) ?: '0.873'
            ));
            
            // Prix minimum
            woocommerce_wp_text_input(array(
                'id'          => '_degressive_min_qty',
                'label'       => __('Quantité minimum (g)', 'wc-degressive-pricing'),
                'desc_tip'    => true,
                'description' => __('Quantité minimale à commander (en grammes)', 'wc-degressive-pricing'),
                'type'        => 'number',
                'custom_attributes' => array(
                    'step' => '0.1',
                    'min'  => '0.1'
                ),
                'value'       => get_post_meta($post->ID, '_degressive_min_qty', true) ?: '1'
            ));
            
            // Prix maximum
            woocommerce_wp_text_input(array(
                'id'          => '_degressive_max_qty',
                'label'       => __('Quantité maximum (g)', 'wc-degressive-pricing'),
                'desc_tip'    => true,
                'description' => __('Quantité maximale à commander (en grammes)', 'wc-degressive-pricing'),
                'type'        => 'number',
                'custom_attributes' => array(
                    'step' => '1',
                    'min'  => '1'
                ),
                'value'       => get_post_meta($post->ID, '_degressive_max_qty', true) ?: '100'
            ));
            
            // Arrondir au 0.5€ le plus proche
            woocommerce_wp_checkbox(array(
                'id'          => '_degressive_round',
                'label'       => __('Arrondir au 0.5€', 'wc-degressive-pricing'),
                'desc_tip'    => true,
                'description' => __('Arrondir le prix final au 0,50€ le plus proche', 'wc-degressive-pricing'),
                'value'       => get_post_meta($post->ID, '_degressive_round', true) ?: 'yes'
            ));
            
            // Afficher tableau des quantités/prix prédéfinis
            woocommerce_wp_checkbox(array(
                'id'          => '_degressive_show_table',
                'label'       => __('Afficher tableau des prix', 'wc-degressive-pricing'),
                'desc_tip'    => true,
                'description' => __('Afficher un tableau des quantités/prix prédéfinis sur la page produit', 'wc-degressive-pricing'),
                'value'       => get_post_meta($post->ID, '_degressive_show_table', true) ?: 'yes'
            ));
            
            // Quantités prédéfinies (affichées dans le tableau)
            woocommerce_wp_textarea_input(array(
                'id'          => '_degressive_preset_qty',
                'label'       => __('Quantités prédéfinies', 'wc-degressive-pricing'),
                'desc_tip'    => true,
                'description' => __('Quantités prédéfinies à afficher dans le tableau (en grammes), séparées par des virgules', 'wc-degressive-pricing'),
                'value'       => get_post_meta($post->ID, '_degressive_preset_qty', true) ?: '1, 3, 5, 10, 25, 50, 100'
            ));
            
            echo '</div>';
        }

        /**
         * Ajouter les scripts admin
         */
        public function enqueue_admin_scripts($hook) {
            // Charger seulement sur les pages d'édition de produit
            if ($hook == 'post.php' || $hook == 'post-new.php') {
                global $post;
                if ($post && $post->post_type == 'product') {
                    wp_enqueue_script(
                        'wc-degressive-admin',
                        plugins_url('js/admin-degressive-pricing.js', __FILE__),
                        array('jquery'),
                        '1.0.5',
                        true
                    );
                }
            }
        }

        /**
         * Sauvegarder les options du produit dégressif - VERSION ULTRA AGRESSIVE
         */
        public function save_degressive_product_options($post_id) {
            // Éviter les boucles infinies
            static $saving = false;
            if ($saving) {
                return;
            }
            $saving = true;
            
            // Vérifier que c'est un produit
            if (get_post_type($post_id) !== 'product') {
                $saving = false;
                return;
            }
            
            // Vérifier les permissions
            if (!current_user_can('edit_post', $post_id)) {
                $saving = false;
                return;
            }
            
            // Log pour debug
            error_log('=== DEBUT SAUVEGARDE DEGRESSIVE ===');
            error_log('Post ID: ' . $post_id);
            error_log('Hook: ' . current_action());
            error_log('POST keys: ' . implode(', ', array_keys($_POST)));
            
            // Sauvegarder TOUS les champs dégressifs présents dans $_POST
            $fields_to_save = array(
                '_degressive_coefficient',
                '_degressive_exponent', 
                '_degressive_min_qty',
                '_degressive_max_qty',
                '_degressive_preset_qty'
            );
            
            $saved_count = 0;
            foreach ($fields_to_save as $field) {
                if (isset($_POST[$field])) {
                    $value = sanitize_text_field($_POST[$field]);
                    $old_value = get_post_meta($post_id, $field, true);
                    
                    // Forcer la mise à jour même si la valeur est identique
                    delete_post_meta($post_id, $field);
                    add_post_meta($post_id, $field, $value, true);
                    
                    error_log("SAUVEGARDE: $field = '$value' (ancien: '$old_value')");
                    $saved_count++;
                }
            }
            
            // Checkboxes
            $checkboxes = array('_degressive_round', '_degressive_show_table');
            foreach ($checkboxes as $checkbox) {
                $value = isset($_POST[$checkbox]) ? 'yes' : 'no';
                $old_value = get_post_meta($post_id, $checkbox, true);
                
                delete_post_meta($post_id, $checkbox);
                add_post_meta($post_id, $checkbox, $value, true);
                
                error_log("SAUVEGARDE: $checkbox = '$value' (ancien: '$old_value')");
                $saved_count++;
            }
            
            // Timestamp de dernière mise à jour
            delete_post_meta($post_id, '_degressive_last_update');
            add_post_meta($post_id, '_degressive_last_update', time(), true);
            
            error_log("TOTAL CHAMPS SAUVEGARDES: $saved_count");
            error_log('=== FIN SAUVEGARDE DEGRESSIVE ===');
            
            $saving = false;
        }

        /**
         * Calculer le prix en fonction de la quantité
         */
        public function calculate_degressive_price($product_id, $quantity) {
            // Récupérer les données
            $coefficient = floatval(get_post_meta($product_id, '_degressive_coefficient', true) ?: '8.45');
            $exponent = floatval(get_post_meta($product_id, '_degressive_exponent', true) ?: '0.873');
            $round = (get_post_meta($product_id, '_degressive_round', true) ?: 'yes') === 'yes';
            
            // Calcul du prix selon la formule P = a * q^b
            $price = $coefficient * pow(floatval($quantity), $exponent);
            
            // Arrondir au 0.5€ le plus proche si demandé
            if ($round) {
                $price = round($price * 2) / 2;
            }
            
            return $price;
        }

        /**
         * Modifier l'affichage du prix sur la page produit
         */
        public function display_degressive_price_html($price_html, $product) {
            if ($product->get_type() !== 'degressive') {
                return $price_html;
            }
            
            $product_id = $product->get_id();
            $min_qty = floatval(get_post_meta($product_id, '_degressive_min_qty', true) ?: '1');
            $min_price = $this->calculate_degressive_price($product_id, $min_qty);
            
            $price_html = sprintf(
                __('À partir de %s / %sg', 'wc-degressive-pricing'),
                wc_price($min_price),
                $min_qty
            );
            
            return $price_html;
        }

        /**
         * Ajouter des scripts et styles personnalisés
         */
        public function enqueue_degressive_scripts() {
            if (is_product()) {
                global $product;
                
                if (!is_object($product)) {
                    $product = wc_get_product(get_the_ID());
                }
                
                if ($product && $product->get_type() === 'degressive') {
                    wp_enqueue_script(
                        'wc-degressive-pricing',
                        plugins_url('js/wc-degressive-pricing.js', __FILE__),
                        array('jquery'),
                        '1.0.5',
                        true
                    );
                    
                    $product_id = $product->get_id();
                    $min_qty = get_post_meta($product_id, '_degressive_min_qty', true) ?: '1';
                    $max_qty = get_post_meta($product_id, '_degressive_max_qty', true) ?: '100';
                    $coefficient = get_post_meta($product_id, '_degressive_coefficient', true) ?: '8.45';
                    $exponent = get_post_meta($product_id, '_degressive_exponent', true) ?: '0.873';
                    $round = (get_post_meta($product_id, '_degressive_round', true) ?: 'yes') === 'yes' ? 1 : 0;
                    
                    wp_localize_script('wc-degressive-pricing', 'wc_degressive_params', array(
                        'min_qty'      => $min_qty,
                        'max_qty'      => $max_qty,
                        'coefficient'  => $coefficient,
                        'exponent'     => $exponent,
                        'round'        => $round,
                        'price_format' => get_woocommerce_price_format(),
                        'currency'     => get_woocommerce_currency_symbol(),
                        'decimal_sep'  => wc_get_price_decimal_separator(),
                        'thousand_sep' => wc_get_price_thousand_separator(),
                        'decimals'     => wc_get_price_decimals(),
                        'ajax_url'     => admin_url('admin-ajax.php')
                    ));
                    
                    wp_enqueue_style(
                        'wc-degressive-pricing',
                        plugins_url('css/wc-degressive-pricing.css', __FILE__),
                        array(),
                        '1.0.5'
                    );
                }
            }
        }

        /**
         * Mettre à jour le prix du panier en fonction de la quantité
         */
        public function update_cart_price($cart) {
            if (is_admin() && !defined('DOING_AJAX')) {
                return;
            }
            
            if (did_action('woocommerce_before_calculate_totals') >= 2) {
                return;
            }
            
            foreach ($cart->get_cart() as $cart_item) {
                $product = $cart_item['data'];
                
                if ($product->get_type() === 'degressive') {
                    $product_id = $product->get_id();
                    $quantity = $cart_item['quantity'];
                    
                    // Calculer le prix total pour la quantité
                    $total_price = $this->calculate_degressive_price($product_id, $quantity);
                    
                    // Définir le prix unitaire = prix total / quantité
                    $unit_price = $total_price / $quantity;
                    
                    // Arrondir le prix unitaire à 2 décimales pour éviter les décalages
                    $unit_price = round($unit_price, 2);
                    
                    $product->set_price($unit_price);
                }
            }
        }

        /**
         * Valider l'ajout au panier pour les produits dégressifs
         */
        public function validate_degressive_add_to_cart($passed, $product_id, $quantity) {
            $product = wc_get_product($product_id);
            
            if ($product && $product->get_type() === 'degressive') {
                $min_qty = floatval(get_post_meta($product_id, '_degressive_min_qty', true) ?: '1');
                $max_qty = floatval(get_post_meta($product_id, '_degressive_max_qty', true) ?: '100');
                
                if ($quantity < $min_qty) {
                    wc_add_notice(sprintf(__('La quantité minimale pour ce produit est de %s grammes.', 'wc-degressive-pricing'), $min_qty), 'error');
                    $passed = false;
                } elseif ($quantity > $max_qty) {
                    wc_add_notice(sprintf(__('La quantité maximale pour ce produit est de %s grammes.', 'wc-degressive-pricing'), $max_qty), 'error');
                    $passed = false;
                }
            }
            
            return $passed;
        }

        /**
         * Ajouter des données personnalisées au panier
         */
        public function add_degressive_cart_item_data($cart_item_data, $product_id, $variation_id) {
            $product = wc_get_product($product_id);
            
            if ($product && $product->get_type() === 'degressive') {
                // Marquer comme produit dégressif
                $cart_item_data['degressive_product'] = true;
            }
            
            return $cart_item_data;
        }

        /**
         * Charger les traductions
         */
        public function load_plugin_textdomain() {
            load_plugin_textdomain(
                'wc-degressive-pricing',
                false,
                dirname(plugin_basename(__FILE__)) . '/languages/'
            );
        }

    }

    // Initialiser le plugin
    new WC_Degressive_Pricing();

    /**
     * Classe du produit dégressif
     */
    class WC_Product_Degressive extends WC_Product {
        
        /**
         * Get internal type.
         *
         * @return string
         */
        public function get_type() {
            return 'degressive';
        }
        
        /**
         * Returns whether or not the product is purchasable.
         *
         * @return bool
         */
        public function is_purchasable() {
            return apply_filters('woocommerce_is_purchasable', $this->exists() && 'publish' === $this->get_status(), $this);
        }
        
        /**
         * Returns whether or not the product is in stock.
         *
         * @return bool
         */
        public function is_in_stock() {
            return apply_filters('woocommerce_product_is_in_stock', 'instock' === $this->get_stock_status(), $this);
        }
        
        /**
         * Get the add to cart button text.
         *
         * @return string
         */
        public function add_to_cart_text() {
            $text = $this->is_purchasable() && $this->is_in_stock() ? __('Ajouter au panier', 'wc-degressive-pricing') : __('Lire plus', 'wc-degressive-pricing');
            return apply_filters('woocommerce_product_add_to_cart_text', $text, $this);
        }
        
        /**
         * Get the add to cart button text for the single page.
         *
         * @return string
         */
        public function single_add_to_cart_text() {
            return apply_filters('woocommerce_product_single_add_to_cart_text', __('Ajouter au panier', 'wc-degressive-pricing'), $this);
        }
    }
    
    // Enregistrer la classe du produit dégressif
    add_action('woocommerce_loaded', function() {
        // Enregistrer la classe du produit dégressif
        add_filter('woocommerce_data_stores', function($stores) {
            $stores['product-degressive'] = 'WC_Product_Data_Store_CPT';
            return $stores;
        });
    });
    
    
    // Ajouter le template pour le produit dégressif
    function woocommerce_template_degressive_add_to_cart() {
        global $product;
        
        if ($product && $product->get_type() === 'degressive') {
            $template_path = plugin_dir_path(__FILE__) . 'templates/single-product/add-to-cart/degressive.php';
            
            if (file_exists($template_path)) {
                include $template_path;
            } else {
                wc_get_template('single-product/add-to-cart/simple.php');
            }
        }
    }
    add_action('woocommerce_single_product_summary', 'woocommerce_template_degressive_add_to_cart', 30);
    
    // Ajouter le répertoire du template
    function degressive_template_path($template, $template_name, $template_path) {
        if ($template_name === 'single-product/add-to-cart/degressive.php') {
            $template = plugin_dir_path(__FILE__) . 'templates/single-product/add-to-cart/degressive.php';
        }
        return $template;
    }
    add_filter('woocommerce_locate_template', 'degressive_template_path', 10, 3);
    
    // Shortcode pour afficher le tableau des prix dégressifs
    function degressive_pricing_table_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product_id' => 0,
        ), $atts, 'degressive_table');
        
        $product_id = intval($atts['product_id']);
        
        // Si aucun ID spécifié, essayer de récupérer l'ID du produit courant
        if (!$product_id) {
            global $post, $product;
            
            // Essayer d'abord avec l'objet produit global
            if (is_object($product) && method_exists($product, 'get_id')) {
                $product_id = $product->get_id();
            }
            // Sinon essayer avec le post courant
            elseif (is_object($post) && isset($post->ID)) {
                $product_id = $post->ID;
            }
            // Dernière tentative avec get_the_ID()
            elseif (function_exists('get_the_ID')) {
                $product_id = get_the_ID();
            }
        }
        
        if (!$product_id) {
            return '<p>Erreur: Impossible de déterminer l\'ID du produit. Utilisez [degressive_table product_id="123"] ou placez le shortcode sur une page produit.</p>';
        }
        
        $product = wc_get_product($product_id);
        
        if (!$product || $product->get_type() !== 'degressive') {
            return '';
        }
        
        // Créer une instance pour utiliser calculate_degressive_price
        $degressive_pricing = new WC_Degressive_Pricing();
        
        $min_qty = floatval(get_post_meta($product_id, '_degressive_min_qty', true) ?: '1');
        $max_qty = floatval(get_post_meta($product_id, '_degressive_max_qty', true) ?: '100');
        $preset_qty_string = get_post_meta($product_id, '_degressive_preset_qty', true) ?: '1, 3, 5, 10, 25, 50, 100';
        $preset_quantities = array_map('trim', explode(',', $preset_qty_string));
        
        $output = '<div class="degressive-price-table">';
        $output .= '<h3>' . __('Tableau des prix dégressifs', 'wc-degressive-pricing') . '</h3>';
        $output .= '<table>';
        $output .= '<tr>
            <th>' . __('Quantité (g)', 'wc-degressive-pricing') . '</th>
            <th>' . __('Prix total', 'wc-degressive-pricing') . '</th>
            <th>' . __('Prix unitaire', 'wc-degressive-pricing') . '</th>
        </tr>';
        
        foreach ($preset_quantities as $qty) {
            $qty = floatval($qty);
            if ($qty < $min_qty || $qty > $max_qty) {
                continue;
            }
            
            $price = $degressive_pricing->calculate_degressive_price($product_id, $qty);
            $unit_price = $price / $qty;
            
            $output .= '<tr>';
            $output .= '<td>' . $qty . '</td>';
            $output .= '<td>' . wc_price($price) . '</td>';
            $output .= '<td>' . wc_price($unit_price) . '/' . __('g', 'wc-degressive-pricing') . '</td>';
            $output .= '</tr>';
        }
        
        $output .= '</table>';
        $output .= '</div>';
        
        return $output;
    }
    add_shortcode('degressive_table', 'degressive_pricing_table_shortcode');
}
