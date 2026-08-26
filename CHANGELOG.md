# Changelog

Toutes les modifications notables de ce module sont documentées ici.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/) et le module suit le [Versionnement sémantique](https://semver.org/lang/fr/).

## [1.0.2] - 2026-08-26

### Ajouté

- **La vidéo apparaît aussi dans la popup d'agrandissement des images.** Les thèmes qui proposent une popup de zoom y affichent leur propre bande de vignettes : la vidéo s'y insère au même titre qu'une image, et la lecture remplace la grande image de la popup. Le visiteur qui parcourt les visuels en grand format n'a plus à en sortir pour voir la vidéo.
- **Bouton d'agrandissement sur le lecteur.** Pendant la lecture dans la galerie, un bouton bascule la vidéo en plein écran. Il ne dépend d'aucun élément du thème et fonctionne donc partout, y compris sur les thèmes dépourvus de popup d'images.
- La lecture s'arrête automatiquement à la fermeture de la popup du thème : sans cela, le son continuait derrière une popup refermée.

### Corrigé

- **Vignette vidéo démesurée et rejetée hors de la bande sur le thème Classic.** Le thème ne donne aucune largeur au conteneur de ses vignettes (`display:inline`) et dimensionne l'image elle-même : la vignette vidéo, en largeur fluide, se calait alors sur la largeur totale de la galerie, et son affichage en bloc la renvoyait sur une ligne à part, au-dessus des images. Elle reprend désormais la taille et les marges de la vignette image du thème, recalculées au redimensionnement de la fenêtre. Les thèmes qui dimensionnent déjà leurs vignettes ne sont pas affectés.

## [1.0.1] - 2026-08-21

### Modifié
- **Licence : GPL v3 vers Open Software License 3.0 (OSL-3.0).** Le cœur de PrestaShop est publié sous OSL-3.0, licence notoirement incompatible avec la GPL quelle que soit sa version. Un module ne pouvant fonctionner sans le cœur, la combinaison des deux ne peut satisfaire les deux copyleft à la fois, ce qui plaçait quiconque redistribue une boutique dans une situation insoluble. L'OSL-3.0 lève l'ambiguïté, aligne le module sur la licence de l'écosystème, et conserve ce qui comptait : l'obligation d'attribution et le partage des modifications. Les versions déjà publiées restent régies par la licence sous laquelle elles ont été distribuées.

## [1.0.0] - 2026-08-20

### Ajouté

- Première publication open source.
- Vidéos YouTube par URL (watch, youtu.be, shorts, embed, ID brut) avec rapatriement automatique de la miniature sur le serveur de la boutique.
- Hébergement interne : envoi de fichiers MP4 / WebM avec image d'aperçu optionnelle.
- Façade click-to-load : aucun iframe ni requête tierce au chargement de la fiche produit, lecteur créé au clic (youtube-nocookie.com ou balise video native).
- Vignettes vidéo dans la galerie produit (hook displayAfterProductThumbs), lecture à la place de l'image principale, lightbox en repli ou en mode forcé.
- Gestion des vidéos sur la fiche produit du back-office (onglet Modules) : ajout, titre, ordre, visibilité, suppression, en AJAX.
- Données structurées VideoObject (JSON-LD) pour l'éligibilité aux résultats enrichis vidéo, désactivables.
- Compatibilité PrestaShop 1.7 / 8 / 9, multiboutique et multilingue.
