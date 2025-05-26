jQuery(document).ready(function($) {
    // Éléments DOM
    const qtyInput = $('#degressive-qty');
    const priceDisplay = $('#degressive-calculated-price');
    const addToCartButton = $('button.single_add_to_cart_button');
    
    // Paramètres
    const params = wc_degressive_params;
    
    /**
     * Formater le prix selon les paramètres de WooCommerce
     */
    function formatPrice(price) {
        price = parseFloat(price);
        
        // Arrondir au 0.5€ le plus proche si demandé
        if (params.round === 1) {
            price = Math.round(price * 2) / 2;
        }
        
        // Formater le prix avec les décimales
        price = price.toFixed(params.decimals);
        
        // Remplacer le séparateur de décimales
        price = price.replace('.', params.decimal_sep);
        
        // Ajouter les séparateurs de milliers
        if (params.thousand_sep) {
            price = price.replace(/\B(?=(\d{3})+(?!\d))/g, params.thousand_sep);
        }
        
        // Formater selon le format de prix (position du symbole monétaire)
        let formattedPrice = params.price_format
            .replace('%1$s', params.currency)
            .replace('%2$s', price);
            
        return formattedPrice;
    }
    
    /**
     * Calculer le prix en fonction de la quantité
     */
    function calculatePrice(quantity) {
        // P = a * q^b
        const coefficient = parseFloat(params.coefficient);
        const exponent = parseFloat(params.exponent);
        const qty = parseFloat(quantity);
        
        console.log('Calcul prix:', {
            coefficient: coefficient,
            exponent: exponent,
            quantity: qty,
            result: coefficient * Math.pow(qty, exponent)
        });
        
        return coefficient * Math.pow(qty, exponent);
    }
    
    /**
     * Mettre à jour l'affichage du prix
     */
    function updatePriceDisplay() {
        const quantity = parseFloat(qtyInput.val());
        
        // Vérifier que la quantité est valide
        if (isNaN(quantity) || quantity < params.min_qty || quantity > params.max_qty) {
            priceDisplay.html('<span class="error">Quantité invalide</span>');
            addToCartButton.prop('disabled', true);
            return;
        }
        
        // Calculer le prix
        const price = calculatePrice(quantity);
        const unitPrice = price / quantity;
        
        // Mettre à jour l'affichage
        priceDisplay.html(
            formatPrice(price) + 
            ' <span class="unit-price">(' + 
            formatPrice(unitPrice) + 
            '/g)</span>'
        );
        
        // Activer le bouton d'ajout au panier
        addToCartButton.prop('disabled', false);
    }
    
    // Debug: afficher les paramètres reçus
    console.log('Paramètres reçus:', params);
    
    // Initialiser l'affichage du prix
    updatePriceDisplay();
    
    // Mettre à jour la quantité cachée et le prix quand la quantité change
    qtyInput.on('input change', function() {
        $('input[name="quantity"]').val($(this).val());
        updatePriceDisplay();
    });
    
    // Mettre à jour la quantité cachée avant de soumettre le formulaire
    $('form.cart').on('submit', function() {
        $('input[name="quantity"]').val(qtyInput.val());
    });
});
