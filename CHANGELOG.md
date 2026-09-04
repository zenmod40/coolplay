# Changelog

Toutes les modifications notables de ce module sont documentées ici.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/) et le module suit le [Versionnement sémantique](https://semver.org/lang/fr/).

## [1.0.4] - 2026-09-04

### Ajouté

- **La vidéo devient une diapositive de la galerie, et plus seulement une vignette.** Sur les thèmes dont la galerie est un carrousel, les flèches de défilement parcouraient les images sans jamais atteindre la vidéo : elle n'existait que dans la bande de vignettes. Elle est désormais une diapositive à part entière, sur la fiche comme dans la popup d'agrandissement — y compris dans les popups qui n'ont aucune vignette où s'insérer. Un clic dessus lance la lecture.
- **Position des vignettes vidéo réglable depuis la configuration du module.** En fin de liste par défaut, à la suite des images du produit. En tête si vous préférez mettre la vidéo en avant, ce qui la garde aussi visible sur les carrousels dont la pagination est figée au chargement.

### Corrigé

- **Thème Hummingbird : la vignette restait sous la galerie, et la lecture donnait le son sans l'image.** Le module cherchait un carrousel de vignettes de type swiper, qu'Hummingbird n'a jamais eu : sa galerie est un carrousel Bootstrap. Faute de le reconnaître, la vignette ne rejoignait pas la bande du thème et le lecteur se posait sur un élément invisible de la page. Les deux listes de vignettes d'Hummingbird sont maintenant reconnues, et le lecteur est cherché dans la galerie elle-même avant de l'être ailleurs.
- **Le lecteur masquait les flèches du carrousel et le bouton de zoom.** Il recouvrait le carrousel entier alors que ces commandes en sont les voisines et non le contenu. Le visiteur ne pouvait plus revenir aux images sans cliquer une vignette. Le lecteur ne recouvre plus que la piste des diapositives.
- **La lecture continuait pendant qu'on changeait d'image.** L'image défilait derrière le lecteur, invisible, et le son de la vidéo se superposait. La lecture s'arrête maintenant à tout défilement de la galerie, quelle qu'en soit l'origine : flèches, vignette, clavier ou glissement au doigt.
- **Les vignettes du thème désignaient la mauvaise image.** Les thèmes inscrivent le rang de chaque diapositive dans leurs vignettes au moment du rendu. Insérer la vidéo décalait d'un cran toutes celles qui la suivaient. Les rangs sont renumérotés après insertion.
- **Vignette vidéo réduite à un trait dans certaines popups.** Quand la bande de vignettes d'une popup sert aussi d'indicateur de position au carrousel, Bootstrap habille ses éléments en trait de trente pixels sur trois. La vignette vidéo en héritait. Le module reprend désormais l'habillage du thème au niveau exact où celui-ci le pose, et neutralise ce style d'indicateur sur ses propres vignettes.
- **La diapositive vidéo ne tenait pas la même place que les images.** Elle changeait la hauteur du bloc sur la fiche, et le gabarit de la popup selon la taille de la fenêtre. Sa boîte est maintenant donnée par une copie invisible d'une image du thème : elle hérite des règles de dimensionnement du thème, y compris de celles qui bornent une image à la hauteur de l'écran, et suit le redimensionnement de la fenêtre sans calcul. Aucune valeur n'est plus estimée par le module.
- **Bandes sombres doublées sur la miniature en grand format.** La miniature fournie par YouTube est au format 4/3 avec les bandes du 16/9 déjà incrustées ; le fond de la façade en ajoutait une seconde d'une autre teinte. Le fond passe au noir pour ce format, ce qui prolonge les bandes du fichier au lieu de les encadrer. La miniature n'est pas recadrée : en grand format elle porte souvent le titre de la vidéo.

## [1.0.3] - 2026-08-29

### Modifié

- **Reformulation du panneau SEO de la page de configuration.** Son titre annonçait « pourquoi CoolPlay ne pénalise pas votre référencement » et le texte affirmait que le module « élimine le problème à la source » : une tournure défensive et un argument commercial, là où le lecteur attend une explication technique. Le panneau décrit maintenant ce que fait le chargement au clic. Les données chiffrées et les points sur les Core Web Vitals, VideoObject et le RGPD sont inchangés.

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
