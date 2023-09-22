# Colissimo Magento 2 Changelog

# 1.3.3

### Fonctionnalités

- Une section comportant des vidéos explicatives a été ajoutée dans la configuration du module
- L'option de livraison en DDP est activée pour l'Australie
- Les retours colis sont à présent disponibles pour le Danemark et la Suède
- L'export pour Coliship gère maintenant la CN23

### Améliorations

- La carte des points de retrait a été améliorée (nombre et type de point de retrait à afficher, affichage ou non de la carte en mobile, etc...)
- Préparation de compatibilité avec PHP 8.3
- Les coupons rendant la livraison gratuite sont à présent pris en compte

# 1.3.2

### Correctifs

- Ajout de l'envoi avec et sans signature pour Saint-Martin
- Correction d'un message de warning lors de la génération manuelle d'une étiquette, sans multi-colis
- Correction de l'affichage du nom et de l'adresse du destinataire sur l'étiquette lorsque des caractères accentués sont utilisés

# 1.3.1

### Correctifs

- Correction d'un message de warning lors de la génération manuelle d'une étiquette en même temps que l'expédition, sans assurance
- Correction d'un message de warning lors de la génération manuelle d'une étiquette en même temps que l'expédition, sans multi-colis

# 1.3.0

### Fonctionnalités

- Une nouvelle option gratuite d'affichage de la carte des points de retrait est disponible
- Il est maintenant possible de personnaliser le montant de l'assurance lors de la génération manuelle d'une étiquette depuis l'expédition
- Une option a été ajoutée pour proposer la sélection automatique du point de retrait le plus proche (modifiable par le client)
- Il est maintenant possible d'assurer le retour d'un colis
- L'envoi en multi-colis est maintenant disponible pour un envoi France => Outre-Mer

### Améliorations

- L'option d'ID de compte parent a été temporairement retirée, le temps que la fonctionnalité soit déployée pour tous les services
- De nouvelles options ont été ajoutées pour pouvoir personnaliser le partenaire postal par pays pour l'Allemagne, l'Autriche, l'Italie et le Luxembourg
- Le design de la carte des points de retrait a été amélioré pour la version mobile et PC
- La méthode de sélection du point de retrait a été améliorée, un bouton "Choisir mon point de retrait" a été ajouté et le point sélectionné n'est plus supprimé lors du clic sur la méthode d'expédition
- Un message explicatif a été ajouté lors d'une tentative de génération d'étiquette à l'étranger pour une livraison gratuite

### Correctifs

- La confirmation de sélection du point de retrait a été corrigée, elle pouvait être dupliquée après avoir modifié le point de retrait

# 1.2.6

### Fonctionnalités

- L'envoi en point de retrait a été activé pour l'Italie et l'Irlande
- Une option a été ajoutée pour changer le statut d'une commande lorsque l'envoi est partiel (une expédition est créée pour 2 produits sur 4 par exemple)

### Améliorations

- Des restrictions ont été appliquées pour l'envoi DDP au Royaume-Uni : autorisé uniquement en envoi commercial entre 160€ et 1050€
- Lorsqu'une erreur survient lors de la génération d'une étiquette, elle est sauvegardée et affichée sur le listing Colissimo jusqu'à la prochaine génération d'étiquette
- La compatibilité avec le système de panier de Magento 2.4.5 a été ajoutée pour les tarifs basés sur le prix du panier

### Correctifs

- Lorsqu'une seule commande est affichée sur le listing Colissimo du fait des filtres appliqués, si cette commande est sélectionnée alors les commandes cachées ne sont plus aussi sélectionnées

# 1.2.5

### Fonctionnalités

- De nouvelles actions sont disponibles sur le listing des commandes pour expédier des commandes existantes avec Colissimo
- Un numéro de contact Colissimo a été ajouté dans la configuration du module en cas de besoin

### Améliorations

- Le numéro de téléphone fourni est à présent vérifié en amont lors d'une livraison en point de retrait pour la France ou la Belgique
- Un message informatif a été ajouté lorsque le montant de l'assurance est plafonné
- L'assurance est à présent disponible pour toutes les destinations sauf le Soudan du Sud et la partie Néerlandaise de Saint-Martin
- La compatibilité avec Magento 2.4.4 a été retravaillée

# 1.2.4

### Correctifs

- Correction sur l'utilisation de l'assurance des colis lors de l'envoi Expert

# 1.2.3

### Fonctionnalités

- L'envoi avec signature et expert est désormais disponible en DDP (paiement des frais de douane à l'avance) pour certaines destinations
- Lors de la première installation, les tarifs Colissimo sont ajoutés par défaut aux différentes méthodes d'envoi

### Améliorations

- Une compatibilité avec Magento 2.4.x a été ajoutée, la compatibilité avec Magento 2.2.0 et moins a été retirée (les deux ne peuvent coexister)
- L'import et l'export Coliship ont été améliorés
- Le numéro de suivi et l'adresse de livraison ont été ajoutés à l'email de suivi
- La carte des points de retrait à été mise à jour pour mieux gérer les petits écrans
- Le montant maximum de l'assurance a été mis à jour pour l'envoi classique et en point de retrait
- La compatibilité avec PHP 8.1.5 a été ajoutée
- 10 pays ont été ajoutés aux destinations autorisées pour l'envoi via Colissimo
- Le retour des colis est mieux géré pour les pays expéditeurs membres de la zone OM1

# 1.2.2

### Fonctionnalités

- Un nouveau bouton a été ajouté dans la configuration pour vérifier l'état des services Colissimo
- Une action de masse a été ajoutée pour supprimer les étiquettes
- Une option a été ajoutée pour permettre l'envoi via un partenaire postal pour l'Allemagne, l'Autriche, L'Italie et le Luxembourg
- Il est à présent possible de générer des étiquettes avec un utilisateur avancé

### Correctifs

- Les options pour Colissimo Flash ont été temporairement retirées, le service n'étant pas disponible
- Le calcul des méthodes d'envoi a été corrigé pour les deux zones de Saint Martin

# 1.2.1

### Correctifs

- La configuration de la grille des prix accepte désormais une livraison gratuite
- L'option de prix maximal sur la grille des prix fonctionne maintenant correctement

# 1.2.0

### Fonctionnalités

- Les envois depuis les DOM1 sont à présent gérés par le module
- L'envoi de documents douaniers pour la Guyanne Française est à présent possible après la génération d'une étiquette
- La chronologie des événements pour la livraison d'un colis est désormais affichée sur la page de suivi du colis
- Une nouvelle option vous permet d'afficher ou non le logo Colissimo près des méthodes de livraison lors de l'achat
- Une nouvelle option vous permet de choisir à quel moment l'email de suivi de colis est envoyé : à la génération de l'étiquette, à la génération du bordereau, ou pas d'envoi d'email

### Améliorations

- La configuration des prix pour toutes les méthodes d'envoi se fait à présent par le biais d'une grille tarifaire unifiée
- Un bouton de contact est à présent disponible dans la configuration pour nous joindre en cas de questions
- Le type du point relais est maintenant affiché dans le descriptif de celui-ci lors de l'édition d'une commande

# 1.1.0

### Fonctionnalités

- Livraison depuis Monaco : les méthodes de livraison Colissimo sont désormais disponibles pour un magasin situé à Monaco
- Les destinations Andorre et Monaco sont ajoutées

### Améliorations

- Ajout de champs "EORI" et "EORI Royaume-Uni" dans la configuration avancée pour la livraison vers les destinations soumises à une déclaration douanière CN23
- Ajout d'un champ "N°TVA" dans la configuration avancée pour la livraison en direction du Royaume-Uni, destination pour laquelle il est désormais nécessaire d'avoir cette donnée
- Utilisation de la version 2 du webservice d'affranchissement
- Ajout de nouveaux statuts de livraison

### Correctifs

- Lors de l'import ColiShip, un problème pouvait se poser si l'identifiant de la commande commençait par une lettre
- Sur les règles panier, les noms des méthodes de livraison étaient mal affichées
- Correctifs divers
- Dans la configuration, notre module avait une incidence sur la place de l'entrée du menu "Methodes de livraison" dans le menu "Ventes"

# 1.0.13

## Améliorations

- Compatibilité avec Magento 2.4

# 1.0.12

## Améliorations

- L'URL de suivi des colis est désormais l'URL La Poste Publique, et non celle professionelle
- Application du Brexit : à partir du 31/12, la livraison en point relais pour le Royaume-Uni ne sera plus disponible et un formulaire CN23 sera généré avec les étiquettes de livraison

## Correctifs

- Lorsque seules les méthodes de livraison Colissimo étaient utilisées, le tunnel de commande pouvait mal se rafraichir pour afficher les méthodes de livraison disponibles
- Correctifs divers

# 1.0.11

## Correctifs

- Correction de la mise à jour des status de livraison qui pouvait prendre beaucoup de ressources
- Sur le checkout, dans le cas d'une livraison en point relais, l'adresse de livraison pouvait être incorrecte

## 1.0.10

### Améliorations

- Ajout du paramètre FTD dans l'export ColiShip et dans le fichier d'exemple FMT
- Plus d'informations sont désormais ajoutées au fichier de log lors du debug

### Correctifs

- Correction d'un bug qui ne prenait pas en compte la virgule dans le tableau de définition des prix des méthodes de livraison

## 1.0.9

### Nouvelles fonctionnalités

- Impression en masse des étiquettes de livraison depuis le listing des expéditions Colissimo

### Améliorations

- Ajout de la référence de la commande sur l'étiquette de livraison
- Lors de l'impression d'une étiquette de livraison, la facture n'est plus présente
- Ajout du paramètre CUSER_INFO_TEXT dans l'export ColiShip et dans le fichier d'exemple FMT

### Corectifs

- Correction d'un bug d'afffichage de l'adresse de l'expéditeur sur les étiquettes de livraison si celle ci était sur deux lignes
