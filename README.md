# WooCommerce Produits Dégressifs

Plugin WordPress pour WooCommerce permettant de créer des produits avec prix dégressif basé sur une formule mathématique personnalisable.

## 🚀 Fonctionnalités

- **Type de produit dégressif** : Nouveau type de produit dans WooCommerce
- **Formule personnalisable** : Prix calculé selon P = a × q^b
- **Interface admin intuitive** : Champs pour coefficient et exposant
- **Calcul en temps réel** : Prix mis à jour automatiquement selon la quantité
- **Template personnalisé** : Affichage optimisé sur la page produit
- **Validation des quantités** : Min/max configurables
- **Tableau des prix** : Shortcode pour afficher les prix dégressifs
- **Support panier** : Prix recalculé automatiquement dans le panier

## 📦 Installation

1. Téléchargez le plugin
2. Uploadez le dossier dans `/wp-content/plugins/`
3. Activez le plugin dans l'admin WordPress
4. Assurez-vous que WooCommerce est installé et activé

## 🔧 Configuration

### Créer un produit dégressif

1. Allez dans **Produits > Ajouter un produit**
2. Dans **Données du produit**, sélectionnez **"Produit dégressif"**
3. Configurez les paramètres :
   - **Coefficient (a)** : Coefficient de base (ex: 8.45)
   - **Exposant (b)** : Exposant de dégressivité (ex: 0.873)
   - **Quantité min/max** : Limites en grammes
   - **Quantités prédéfinies** : Pour le tableau des prix

### Formule de calcul

```
Prix = Coefficient × Quantité^Exposant
P = a × q^b
```

**Exemple** avec a=8.45 et b=0.873 :
- 1g → 8.45 × 1^0.873 = 8.45€
- 10g → 8.45 × 10^0.873 = 63.10€
- 100g → 8.45 × 100^0.873 = 471.83€

## 🎨 Affichage

### Page produit
Le plugin remplace automatiquement le formulaire d'ajout au panier standard par :
- Champ de saisie de quantité (en grammes)
- Affichage du prix calculé en temps réel
- Prix unitaire (€/g)
- Bouton "Ajouter au panier"

### Shortcode tableau
```php
[degressive_table]
```
Affiche un tableau avec les quantités prédéfinies et leurs prix.

## 🛠️ Structure des fichiers

```
wc-degressive-pricing-plugin.php    # Plugin principal
css/
  └── wc-degressive-pricing.css     # Styles frontend
js/
  ├── admin-degressive-pricing.js   # Scripts admin
  └── wc-degressive-pricing.js      # Scripts frontend
templates/
  └── single-product/
      └── add-to-cart/
          └── degressive.php        # Template page produit
```

## 🐛 Debug

Le plugin inclut des logs détaillés pour le debug :
- Sauvegarde des champs admin
- Calculs de prix
- Erreurs de validation

Consultez les logs WordPress ou activez `WP_DEBUG` pour voir les détails.

## 🔄 Problèmes connus

### Sauvegarde des champs admin
Si les champs ne se sauvegardent pas :
1. Vérifiez les permissions utilisateur
2. Consultez les logs d'erreur PHP
3. Désactivez temporairement les autres plugins

### Calculs de prix
Si les prix ne s'affichent pas :
1. Vérifiez que JavaScript est activé
2. Ouvrez la console navigateur (F12)
3. Vérifiez les paramètres du produit

## 📝 Changelog

### v1.0.5 (2025-01-26)
- Système de sauvegarde ultra-renforcé
- Logs de debug détaillés
- Correction des calculs JavaScript
- Template personnalisé amélioré

### v1.0.4
- Ajout du support panier
- Validation des quantités
- Shortcode tableau des prix

### v1.0.3
- Interface admin améliorée
- Calculs en temps réel
- Support des décimales

## 🤝 Contribution

Ce plugin est en développement actif. N'hésitez pas à :
- Signaler des bugs via les Issues GitHub
- Proposer des améliorations
- Contribuer au code

## 📄 Licence

GPL v2 ou ultérieure

## 🆘 Support

Pour obtenir de l'aide :
1. Consultez la documentation ci-dessus
2. Vérifiez les Issues GitHub existantes
3. Créez une nouvelle Issue si nécessaire

---

**Développé pour Hempfuel** 🌿
