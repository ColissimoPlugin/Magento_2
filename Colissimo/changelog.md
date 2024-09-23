# Colissimo Magento 2 Changelog

# 1.6.0

### Fonctionnalités

- Une nouvelle option a été ajoutée pour que vous puissiez permettre à vos clients d'effectuer un retour sécurisé en bureau de poste
- Ajout de la compatibilité avec les sites Adobe Commerce

### Améliorations

- Des sécurités ont été ajoutées pour éviter les erreurs qui pouvaient se présenter avec le widget Colissimo

### Correctifs

- Une erreur pouvant survenir lors de la récupération des informations du compte a été corrigée lorsqu'un utilisateur avancé est utilisé
- Choisir un point de retrait puis changer de méthode de livraison ne conserve plus le nom du point de retrait dans le champ société de l'adresse de livraison
- Le stock des commandes passées avec une autre méthode de livraison ne sont plus impactés
- Le nom du client est maintenant bien utilisé dans l'email de suivi à la place de son adresse email

# 1.5.0

### Fonctionnalités

- Il est à présent possible de s'authentifier avec une clé de connexion Colissimo
- La fonctionnalité de code sécurisé à la livraison est maintenant paramétrable par commande ou globalement en se basant sur le prix de commande
- Un lien a été ajouté dans la configuration pour vous rendre sur la page de gestion de vos services Colissimo

### Améliorations

- Le retour des colis pour la Norvège a été activé
- Il n'est plus nécessaire d'avoir un numéro français pour les envois en point de retrait en France
- Le lien vers la Colissimo Box vous connecte à présent à votre compte Colissimo

### Correctifs

- L'affichage de la carte des points de retrait en mode Google Maps et Leaflet a été corrigé pour Magento 2.4.7 en modifiant les règles CSP

# 1.4.2

### Correctifs

- Un formulaire a été ajouté dans les réglages afin de vous permettre de nous fournir un retour sur le module

# 1.4.1

### Améliorations

- La vérification des identifiants Colissimo dans les réglages et l'affichage du widget Colissimo ont été corrigés

# 1.4.0

### Fonctionnalités

- Une option a été ajoutée pour choisir si les tarifs de livraison sont calculés avant ou après application des coupons

### Améliorations

- Une nouvelle section a été ajoutée dans les réglages pour aider à la mise en place du module et de ses principales fonctionnalités
- La méthode de livraison Colissimo Internationale à été dépréciée au profit de l'envoi avec signature (étant la même méthode côté Colissimo)
- Les codes postaux des États-Unis contenant un tiret sont maintenant acceptés
- Des formats d'impression pour les étiquettes ont été ajoutés (10x10cm et 10x12cm)
- Certaines traductions ont été revues
- Diverses optimisations afin de réduire le nombre d'appels aux services Colissimo
- Une sécurité a été ajoutée pour gérer les cas où un autre module rentrerait en conflit lors d'une commande en point de retrait

### Correctifs

- Les étiquettes de retour pour l'international utilisent à présent bien le format PDF A4
- Lors d'une livraison en point de retrait pour un panier de plus de 20kg, les points non éligibles ne sont plus proposés
- Le champs du numéro de téléphone n'est plus obligatoire pour certaines commandes si la livraison n'est pas en point de retrait

# 1.3.5

### Fonctionnalités

- Le nombre d'exemplaires de la CN23 est à présent paramétrable dans la configuration
- Vous pouvez maintenant télécharger les logs Colissimo depuis la configuration du module
- Il est maintenant possible de paramétrer le format de la CN23 générée, indépendamment du format de l'étiquette
- Une option a été ajoutée afin de pouvoir importer les règles d'affichage et de prix des méthodes de livraison en utilisant un fichier CSV
- Vous pouvez à présent exporter vos règles d'affichage et de prix des méthodes de livraison au format CSV, afin de les modifier plus facilement ou les importer sur un autre site
- Une option a été ajoutée pour permettre aux clients d'indiquer des instructions de livraison sur l'étiquette
- Il est maintenant possible d'ouvrir la carte des points de retrait lors de l'édition d'une commande depuis la partie admin du site

### Améliorations

- Des logs supplémentaires ont été ajoutés lors de la sélection d'un point de retrait afin de faciliter les interventions en cas de problème
- Le numéro de version actuel du module est à présent affiché dans la configuration
- Un formulaire a été ajouté dans la configuration afin de vous permettre de nous fournir un retour sur le module
- Le mot de passe Colissimo rentré dans la configuration est maintenant encodé dans la base de données pour plus de sécurité

### Correctifs

- L'affichage de l'icône du marqueur a été corrigé lorsque la sélection automatique du point de retrait le plus proche est activée

# 1.3.4

### Fonctionnalités

- Un nouveau projet GitHub a été mis à disposition pour recueillir les suggestions de modifications et ajouts : https://github.com/ColissimoPlugin/Magento_2

### Améliorations

- Les prix par défaut installés à la première activation ont été mis à jour pour suivre la grille tarifaire de 2023
- Un message informatif a été ajouté pour les comptes Facilité n'ayant pas encore accepté les CGV

### Correctifs

- L'inclusion de scripts a été corrigée pour certaines configurations système sur la page de modification d'une commande

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
