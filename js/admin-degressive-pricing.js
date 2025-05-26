jQuery(document).ready(function($) {
    // Fonction pour afficher/masquer les champs selon le type de produit
    function toggleDegressiveFields() {
        var productType = $('#product-type').val();
        
        if (productType === 'degressive') {
            $('.show_if_degressive').show();
            $('.hide_if_degressive').hide();
        } else {
            $('.show_if_degressive').hide();
            $('.hide_if_degressive').show();
        }
    }
    
    // Exécuter au chargement de la page
    toggleDegressiveFields();
    
    // Exécuter quand le type de produit change
    $('#product-type').on('change', function() {
        toggleDegressiveFields();
    });
    
    // Exécuter aussi sur les événements WooCommerce
    $('body').on('woocommerce-product-type-change', function() {
        toggleDegressiveFields();
    });
});
