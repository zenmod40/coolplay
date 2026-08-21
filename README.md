# CoolPlay - Vidéos produits

[![Téléchargements](https://img.shields.io/github/downloads/zenmod40/coolplay/total.svg)](https://github.com/zenmod40/coolplay/releases) [![Version](https://img.shields.io/github/v/release/zenmod40/coolplay)](https://github.com/zenmod40/coolplay/releases/latest)

Module PrestaShop de vidéos produits sans impact sur la vitesse. Les vidéos (YouTube ou fichier hébergé) s'affichent en vignettes dans la galerie de la fiche produit avec une façade click-to-load : rien n'est chargé avant le clic, ni iframe, ni script tiers, ni cookie.

Compatible PrestaShop 1.7, 8 et 9. Module libre et open source sous licence OSL 3.0, par ZM40.

## Pourquoi une façade click-to-load

Une iframe YouTube chargée d'office pèse entre 800 Ko et 1,2 Mo de JavaScript tiers, avant même que le visiteur ait décidé de regarder la vidéo. C'est ce qui donne à la vidéo sa réputation de handicap SEO. CoolPlay n'affiche au chargement qu'une miniature JPEG stockée sur votre serveur et un bouton de lecture en CSS. L'iframe (ou la balise video) n'est créée qu'au clic, à la place de l'image principale de la galerie : le visiteur ne quitte jamais la fiche produit.

En complément, le module injecte des données structurées VideoObject (JSON-LD) : la fiche produit devient éligible aux résultats enrichis vidéo de Google. La vidéo passe de handicap SEO à atout SEO.

## Fonctionnalités

- Vidéos YouTube : collez une URL (watch, youtu.be, shorts, embed ou ID brut), la miniature est rapatriée automatiquement sur votre serveur — aucune requête vers YouTube au chargement de la page.
- Hébergement interne : envoi d'un fichier MP4 ou WebM avec image d'aperçu optionnelle, lecture via la balise video native (preload none).
- Lecture sur place : la vidéo se lit à la place de l'image principale de la galerie ; lightbox plein écran en repli pour les thèmes non standard (ou en mode forcé).
- Vignettes intégrées à la galerie via le hook standard displayAfterProductThumbs (thèmes Classic et Hummingbird).
- Gestion directement sur la fiche produit (onglet Modules) : ajout, titre, ordre, visibilité, suppression, sans quitter la page.
- Données structurées VideoObject (JSON-LD) désactivables.
- Lecture YouTube via youtube-nocookie.com et aucun cookie tiers avant le clic : rien à déclarer dans votre bandeau de consentement pour l'affichage de la fiche.
- Plusieurs vidéos par produit, multiboutique et multilingue.

## Compatibilité

- PrestaShop 1.7.x, 8.x, 9.x
- PHP 7.2 à 8.x
- Thèmes Classic, Hummingbird et dérivés ; pour les thèmes sans conteneur de galerie reconnu, la lecture bascule automatiquement en lightbox

## Installation

1. Déposer le dossier `coolplay` dans le répertoire `modules/` de votre boutique (ou installer le ZIP via le back-office).
2. Installer le module depuis Modules > Gestionnaire de modules.
3. Ouvrir une fiche produit, onglet Modules (PrestaShop 8/9) ou onglet CoolPlay (1.7), et ajouter une première vidéo.

## Configuration

- Données structurées VideoObject (SEO) : activées par défaut.
- Toujours ouvrir en lightbox : à activer si votre thème affiche mal la vidéo dans la galerie.
- Fonctions réseau ZM40 : vérification de nouvelle version et liste des autres modules ZM40, désactivables.

## Confidentialité

- Au chargement d'une fiche produit, aucune requête n'est émise vers YouTube ou un autre service tiers. La miniature est servie depuis votre serveur. La connexion à YouTube (youtube-nocookie.com) n'a lieu qu'au clic du visiteur sur le bouton de lecture.
- Le module vérifie périodiquement (au maximum une fois par jour) si une nouvelle version est disponible via l'API publique de GitHub, et récupère la liste des autres modules ZM40 depuis zm40.com. Ces requêtes sont anonymes : aucune donnée de votre boutique n'est transmise. Vous pouvez tout désactiver dans la configuration du module (interrupteur « réseau »).

## Support et services

Le code est offert. Le support gratuit se limite aux bugs reproductibles (issues GitHub). L'installation, la configuration, l'adaptation à votre thème, le débogage spécifique et les développements sur-mesure sont des prestations : zm40.com.

Une version compatible ThirtyBees / PrestaShop 1.6 peut être étudiée sur demande.

## Contribuer

Les pull requests sont les bienvenues. Merci d'ouvrir une issue avant les changements importants.

## Licence

OSL 3.0 — voir le fichier LICENSE.
