# Changelog

Toutes les modifications notables de ce module sont documentées ici.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/) et le module suit le [Versionnement sémantique](https://semver.org/lang/fr/).

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
