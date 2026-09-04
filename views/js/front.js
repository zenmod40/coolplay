/**
 * CoolPlay - Façade click-to-load : rien n'est chargé avant le clic.
 * Les vignettes vidéo (rendues par le hook sous la galerie) sont déplacées
 * dans le carrousel de vignettes du thème (ul Classic / Hummingbird, ou swiper) ;
 * la bande sous la galerie reste le repli pour les thèmes non reconnus.
 * Au clic : iframe youtube-nocookie (ou <video>) à la place de l'image
 * principale ; lightbox en repli ou en mode forcé.
 * @license https://opensource.org/licenses/OSL-3.0 Open Software License version 3.0
 */
(function () {
    'use strict';

    var cfg = window.cplConfig || {};
    var current = null;

    /* ------------------------------------------------------------------
     * Lecteur
     * ---------------------------------------------------------------- */

    function playerFor(btn) {
        var el;
        if (btn.getAttribute('data-type') === 'youtube') {
            el = document.createElement('iframe');
            el.src = 'https://www.youtube-nocookie.com/embed/' + btn.getAttribute('data-ref') + '?autoplay=1&rel=0';
            el.allow = 'autoplay; fullscreen; picture-in-picture; encrypted-media';
            el.setAttribute('allowfullscreen', '');
            el.setAttribute('title', btn.getAttribute('data-title') || 'Video');
        } else {
            el = document.createElement('video');
            el.src = btn.getAttribute('data-src');
            el.controls = true;
            el.autoplay = true;
            el.playsInline = true;
            var poster = btn.getAttribute('data-poster');
            if (poster) {
                el.poster = poster;
            }
        }
        el.className = 'cpl-media';
        return el;
    }

    function onKey(e) {
        if (e.key === 'Escape') {
            close();
        }
    }

    /**
     * Le lecteur recouvre l'image principale : si le visiteur clique une
     * vignette IMAGE de la galerie, on referme le lecteur pour laisser le
     * thème afficher l'image choisie. (Capture : certains thèmes stoppent
     * la propagation sur leurs vignettes.)
     */
    function onDocClick(e) {
        if (!current || !e.target.closest || current.node.contains(e.target)) {
            return;
        }
        if (btnFrom(e.target)) {
            return; // changement de vidéo : géré par open()
        }
        if (e.target.closest('.thumb-container, .js-thumb, .js-thumb-container, .product-thumb, ul.product-images li, .product-thumbs .swiper-slide, .carousel-control-prev, .carousel-control-next, [data-bs-toggle="modal"]')) {
            close();
        }
    }

    function close() {
        if (!current) {
            return;
        }
        if (current.node.parentNode) {
            current.node.parentNode.removeChild(current.node);
        }
        if (current.host) {
            current.host.classList.remove('cpl-hosting');
        }
        if (current.carousel) {
            current.carousel.removeEventListener('slide.bs.carousel', current.onSlide);
        }
        current = null;
        document.removeEventListener('keydown', onKey);
        document.removeEventListener('click', onDocClick, true);
    }

    /**
     * Où poser le lecteur ? Si la vignette cliquée est dans la popup images du
     * thème, on prend la place de la GRANDE image de la popup — sinon celle de
     * l'image principale de la page.
     *
     * Sur un carrousel Bootstrap on vise la PISTE (.carousel-inner) et non le
     * carrousel entier : les flèches et le bouton de zoom sont ses voisins, pas
     * ses enfants. Recouvrir le carrousel entier les masquait, et le visiteur
     * se retrouvait enfermé dans la vidéo, sans moyen de revenir aux images
     * autrement qu'en cliquant une vignette.
     */
    function hostFor(btn) {
        if (cfg.forceLightbox) {
            return null;
        }
        var scope = btn.closest('.modal, .js-product-images-modal');
        if (scope) {
            var big = scope.querySelector('.js-modal-product-cover, .product-cover-modal');
            if (big) {
                // <picture> plutôt que <figure> : en Classic la figure contient
                // aussi la légende (description courte), que le lecteur
                // recouvrirait. Le picture épouse exactement l'image.
                return big.closest('picture') || big.closest('figure') || big.parentNode;
            }
            return scope.querySelector('.carousel-inner') || scope.querySelector('.modal-body') || scope;
        }
        // Hors popup, on cherche d'abord DANS le conteneur de la galerie (parent
        // du hook) : la page peut contenir un autre .product-cover hors galerie
        // (quick view, miniature…) qui hébergerait un lecteur invisible.
        var sels = ['.js-product-carousel .carousel-inner', '.product-cover', '.images-container', '.product-images'];
        var wrap = document.getElementById('cpl-thumbs');
        var scopes = wrap && wrap.parentNode ? [wrap.parentNode, document] : [document];
        for (var s = 0; s < scopes.length; s++) {
            for (var i = 0; i < sels.length; i++) {
                var host = scopes[s].querySelector(sels[i]);
                if (host) {
                    return host;
                }
            }
        }
        return null;
    }

    /**
     * La popup du thème est fermée par son propre bouton (Bootstrap déclenche
     * un événement jQuery, qu'un addEventListener natif ne reçoit pas). Sans
     * ça, la vidéo continuerait de jouer — et de s'entendre — derrière une
     * popup refermée. On surveille donc l'attribut de classe de la popup.
     */
    function watchModal(host) {
        var modal = host && host.closest ? host.closest('.modal, .js-product-images-modal') : null;
        if (!modal || modal.cplWatched || typeof MutationObserver === 'undefined') {
            return;
        }
        modal.cplWatched = true;
        new MutationObserver(function () {
            if (current && modal.contains(current.node) && !modal.classList.contains('show')) {
                close();
            }
        }).observe(modal, { attributes: true, attributeFilter: ['class', 'style'] });
    }

    function controlBtn(cls, html, label, onClick) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = cls;
        b.innerHTML = html;
        b.setAttribute('aria-label', label);
        b.addEventListener('click', onClick);
        return b;
    }

    function open(btn, asLightbox) {
        close();
        var host = asLightbox ? null : hostFor(btn);

        var closeBtn = controlBtn('cpl-close', '&times;', cfg.closeLabel || 'Fermer', close);

        var box = document.createElement('div');
        if (host) {
            box.className = 'cpl-player';
            host.classList.add('cpl-hosting');
            box.appendChild(playerFor(btn));
            // Agrandissement à la demande : même geste que le zoom sur les
            // images, mais en lightbox — indépendant du markup du thème.
            box.appendChild(controlBtn('cpl-expand', '&#10530;', cfg.expandLabel || 'Agrandir', function () {
                open(btn, true);
            }));
            box.appendChild(closeBtn);
            host.appendChild(box);
            watchModal(host);
        } else {
            box.className = 'cpl-lightbox';
            var inner = document.createElement('div');
            inner.className = 'cpl-lightbox-box';
            inner.appendChild(playerFor(btn));
            inner.appendChild(closeBtn);
            box.appendChild(inner);
            box.addEventListener('click', function (e) {
                if (e.target === box) {
                    close();
                }
            });
            document.body.appendChild(box);
        }
        current = { node: box, host: host };

        // Le lecteur recouvre la piste du carrousel : l'image change derrière
        // lui sans que rien ne se voie. Tout défilement coupe donc la vidéo,
        // quelle qu'en soit l'origine — flèches, vignette du thème, rail de la
        // popup, clavier, glissement au doigt : Bootstrap émet le même
        // événement pour tous. Sauf le défilement vers notre propre façade,
        // qui est justement le clic en train d'ouvrir le lecteur.
        var carousel = host && host.closest ? host.closest('.carousel') : null;
        if (carousel) {
            current.carousel = carousel;
            current.onSlide = function (e) {
                if (!e.relatedTarget || !e.relatedTarget.classList.contains('cpl-slide')) {
                    close();
                }
            };
            carousel.addEventListener('slide.bs.carousel', current.onSlide);
        }

        document.addEventListener('keydown', onKey);
        document.addEventListener('click', onDocClick, true);
    }

    // Activation par événements pointer en phase capture (document), PAS par
    // « click » seul : les carrousels de vignettes des thèmes (drag/swipe,
    // ex. vCarousel Promokit) font du preventDefault/setPointerCapture qui
    // supprime ou recible le click natif. Le pointerdown en capture au niveau
    // document passe AVANT le code du thème et vise encore le vrai bouton.
    // Un déplacement > 8 px = drag du carrousel, on ne déclenche pas.
    // La délégation fonctionne aussi sur les vignettes clonées/reconstruites
    // par les thèmes aux breakpoints responsive (data-* copiés).
    var pendingTap = null;
    var lastHandled = 0;

    /**
     * Retrouve le bouton vidéo depuis la cible d'un événement. closest() ne
     * remonte que vers les ancêtres : si un overlay du thème (spinner en
     * pseudo-élément, etc.) fait cibler le LI lui-même, on redescend vers le
     * bouton qu'il contient.
     */
    function btnFrom(target) {
        if (!target || !target.closest) {
            return null;
        }
        var btn = target.closest('.cpl-thumb');
        if (btn) {
            return btn;
        }
        var item = target.closest('.cpl-thumb-item');
        return item ? item.querySelector('.cpl-thumb') : null;
    }

    document.addEventListener('pointerdown', function (e) {
        var btn = btnFrom(e.target);
        pendingTap = btn ? { btn: btn, x: e.clientX, y: e.clientY } : null;
    }, true);

    document.addEventListener('pointerup', function (e) {
        if (!pendingTap) {
            return;
        }
        var tap = pendingTap;
        pendingTap = null;
        var dx = e.clientX - tap.x;
        var dy = e.clientY - tap.y;
        if ((dx * dx + dy * dy) <= 64) {
            lastHandled = Date.now();
            open(tap.btn);
        }
    }, true);

    // Repli : clavier (Entrée/Espace sur le bouton) et navigateurs sans
    // Pointer Events. Ignoré si le pointerup vient de traiter l'interaction.
    document.addEventListener('click', function (e) {
        if (Date.now() - lastHandled < 600) {
            return;
        }
        var btn = btnFrom(e.target);
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            open(btn);
        }
    }, true);

    /* ------------------------------------------------------------------
     * Intégration dans le carrousel de vignettes du thème
     * ---------------------------------------------------------------- */

    /**
     * Aligne nos vignettes sur la vignette image du thème.
     *
     * Le bouton est fluide (width:100 %), ce qui suppose un conteneur de
     * largeur définie. Classic n'en donne pas : ses <li class="thumb-container">
     * sont en display:inline et c'est l'image qui porte la taille
     * (.product-images img{width:9.25rem}). Notre 100 % se résolvait donc sur
     * la largeur de toute la bande — vignette géante. On plafonne sur la
     * vignette du thème et on lui reprend ses marges, ce qui donne le même
     * rendu quel que soit le thème ; les carrousels qui dimensionnent déjà
     * leurs slides ne bougent pas, max-width ne mord que si on déborde.
     */
    var sizings = [];

    function applyThumbSize() {
        sizings.forEach(function (s) {
            var rect = s.ref.getBoundingClientRect();
            if (rect.width <= 0) {
                return; // vignette de référence pas encore mise en page
            }
            var cs = window.getComputedStyle(s.ref);
            s.buttons.forEach(function (btn) {
                btn.style.maxWidth = Math.round(rect.width) + 'px';
                btn.style.marginRight = cs.marginRight;
                btn.style.marginBottom = cs.marginBottom;
            });
        });
    }

    var resizeTimer = null;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(applyThumbSize, 150);
    });

    /**
     * Déplace les boutons vidéo dans la liste de vignettes du thème, en
     * copiant les classes et dimensions d'une vignette existante pour
     * hériter du style. Sans carrousel reconnu (ou sans image), la bande
     * rendue par le hook reste en place : c'est le repli.
     */
    function insertInto(container, buttons, isSwiper) {
        // Nos items d'un passage précédent sont retirés d'abord : évite les
        // doublons à la réintégration, et garantit que la vignette de référence
        // mesurée plus bas est bien une vignette IMAGE du thème.
        Array.prototype.forEach.call(container.querySelectorAll('.cpl-thumb-item'), function (n) {
            if (n.parentNode) {
                n.parentNode.removeChild(n);
            }
        });

        var refItem = container.firstElementChild;
        if (!refItem || !refItem.querySelector('img')) {
            return false; // produit sans image : pas de carrousel où s'insérer
        }
        // Classes de layout copiées de la vignette de référence, MAIS pas les
        // hooks de comportement (js-*, selected) : le JS du thème ne doit pas
        // considérer nos items comme des vignettes image (pas de data-image-*).
        var itemClass = refItem.className.split(/\s+/).filter(function (c) {
            return c !== '' && c !== 'selected' && c !== 'active' && c.indexOf('js-') !== 0;
        }).join(' ');
        // Hummingbird 2 met des <button> directement dans le <ul> : un bouton
        // dans un bouton est invalide, on prend un <div> avec les mêmes classes.
        var itemTag = refItem.tagName === 'BUTTON' ? 'div' : refItem.tagName;

        // Hummingbird pilote son carrousel par les attributs Bootstrap portés
        // par ses vignettes, et surligne la vignette active par sa POSITION dans
        // la liste. En reprenant ces attributs, notre vignette fait défiler
        // jusqu'à la diapositive vidéo comme n'importe quelle image, et le thème
        // gère le surlignage tout seul. La classe js-thumb-container est
        // indispensable : sans elle, le thème ne saurait pas retirer le
        // surlignage de notre vignette au retour vers une image.
        //
        // Ces attributs vont exactement au même niveau que chez le thème : sur
        // l'item, ou sur le bouton qu'il contient. Un rail de vignettes peut
        // être un « carousel-indicators », où Bootstrap réduit tout porteur de
        // data-bs-target à un trait de 30 × 3 px ; les thèmes ne défont ce style
        // que sur l'élément où ils ont eux-mêmes posé l'attribut.
        var slideRef = refItem.hasAttribute && refItem.hasAttribute('data-bs-slide-to')
            ? refItem
            : refItem.querySelector('[data-bs-slide-to]');
        var onItem = slideRef === refItem;

        // Position réglable en BO. En tête : toujours visible même quand le
        // carrousel du thème a déjà calculé sa pagination (flèches figées après
        // init). En queue : les images produit d'abord, la vidéo après.
        var atEnd = !!cfg.thumbLast;
        (atEnd ? buttons : buttons.slice().reverse()).forEach(function (btn) {
            var item = document.createElement(itemTag);
            item.className = itemClass + ' cpl-thumb-item';
            if (slideRef) {
                var carrier = onItem ? item : btn;
                carrier.classList.add('js-thumb-container');
                carrier.setAttribute('data-bs-target', slideRef.getAttribute('data-bs-target') || '');
                carrier.setAttribute('data-bs-slide-to', '0');
            }
            item.appendChild(btn);
            if (atEnd) {
                container.appendChild(item);
            } else {
                container.insertBefore(item, container.firstChild);
            }
        });
        if (slideRef) {
            renumber(container);
        }

        var refImg = refItem.querySelector('img');
        if (refImg) {
            sizings.push({ ref: refImg, buttons: buttons });
            // Vignette de référence en lazy-loading : elle peut mesurer 0 tant
            // qu'elle n'est pas décodée, on remesure à son chargement.
            if (!refImg.complete) {
                refImg.addEventListener('load', applyThumbSize, { once: true });
            }
        }

        if (isSwiper) {
            var swiperEl = container.closest('.swiper, .product-thumbs');
            if (swiperEl && swiperEl.swiper && typeof swiperEl.swiper.update === 'function') {
                swiperEl.swiper.update();
            }
        }

        return true;
    }

    /**
     * Renumérote les index de diapositive Bootstrap dans l'ordre du DOM. Les
     * thèmes écrivent ces index côté serveur (0, 1, 2…) : dès qu'on insère une
     * diapositive, tous ceux qui suivent désignent la mauvaise image.
     */
    function renumber(root) {
        Array.prototype.forEach.call(root.querySelectorAll('[data-bs-slide-to]'), function (n, i) {
            n.setAttribute('data-bs-slide-to', i);
        });
    }

    /**
     * Copie invisible d'une image du thème, qui donne sa boîte à la diapositive
     * vidéo. Une image est un élément remplacé : elle sait rester à son format
     * sous une contrainte de hauteur, ce qu'un bloc ne sait pas faire.
     * Le fichier est déjà en cache (c'est une image du carrousel), donc aucune
     * requête supplémentaire.
     */
    function sizerFrom(refImg) {
        // Le <picture> entier plutôt que l'image seule : sans ses <source>, le
        // navigateur retomberait sur le JPEG alors que la page affiche le WebP,
        // et téléchargerait donc un fichier de plus pour une copie invisible.
        var sizer = (refImg.closest('picture') || refImg).cloneNode(true);
        var nodes = [sizer].concat(Array.prototype.slice.call(sizer.querySelectorAll('*')));
        nodes.forEach(function (n) {
            Array.prototype.slice.call(n.attributes).forEach(function (a) {
                if (a.name.indexOf('data-') === 0 || a.name === 'id') {
                    n.removeAttribute(a.name);
                }
            });
        });
        var img = sizer.tagName === 'IMG' ? sizer : sizer.querySelector('img');
        if (img) {
            img.setAttribute('alt', '');
            img.setAttribute('loading', 'eager'); // déjà en cache : c'est l'image du carrousel
        }
        sizer.classList.add('cpl-sizer');
        sizer.setAttribute('aria-hidden', 'true');
        return sizer;
    }

    /**
     * Ajoute une diapositive-façade par vidéo dans une piste de carrousel
     * Bootstrap (Hummingbird). La vidéo devient une diapositive comme une
     * autre : les flèches y mènent, et la popup d'agrandissement l'affiche même
     * lorsqu'elle n'a aucune liste de vignettes où s'insérer.
     *
     * Quand la popup a AUSSI un rail de vignettes, la vidéo y figure deux fois,
     * exactement comme chaque image : une vignette pour choisir, une
     * diapositive pour parcourir. C'est voulu — les deux chemins sont ceux du
     * thème, la vidéo n'a pas de raison d'en emprunter un seul.
     */
    function insertSlides(track, buttons) {
        if (!track) {
            return; // thème sans carrousel Bootstrap (Classic) : rien à faire
        }
        Array.prototype.forEach.call(track.querySelectorAll('.cpl-slide'), function (n) {
            n.parentNode.removeChild(n);
        });

        // La façade doit occuper exactement la boîte des images du thème, et
        // celle-ci dépend de règles qu'un module ne peut pas deviner : largeur
        // fluide, taille réelle de l'image, hauteur bornée à la fenêtre dans une
        // popup, et tout cela recalculé à chaque redimensionnement. Plutôt que
        // de reconstituer ces contraintes en CSS — format, plafond de largeur,
        // budget de hauteur — on laisse le navigateur les appliquer : une copie
        // invisible de l'image du thème dimensionne la diapositive, et la façade
        // se pose par-dessus. Elle hérite ainsi de tout, y compris de ce que le
        // thème changera demain.
        var refImg = track.querySelector('.carousel-item img');

        // Certains thèmes font pointer chaque diapositive vers la popup : nos
        // diapositives doivent porter le même attribut, sinon la renumérotation
        // les saute et décale tout ce qui les suit.
        var refSlide = track.querySelector('.carousel-item[data-bs-slide-to]');

        var atEnd = !!cfg.thumbLast;
        (atEnd ? buttons : buttons.slice().reverse()).forEach(function (btn) {
            var slide = document.createElement('div');
            slide.className = 'carousel-item cpl-slide';
            if (refSlide) {
                slide.setAttribute('data-bs-target', refSlide.getAttribute('data-bs-target') || '');
                slide.setAttribute('data-bs-slide-to', '0');
            }
            var clone = btn.cloneNode(true);
            clone.removeAttribute('style'); // pas le dimensionnement vignette
            if (refImg) {
                clone.insertBefore(sizerFrom(refImg), clone.firstChild);
            }
            slide.appendChild(clone);
            if (atEnd) {
                track.appendChild(slide);
            } else {
                track.insertBefore(slide, track.firstChild);
            }
        });
        renumber(track);
    }

    function integrate() {
        var wrap = document.getElementById('cpl-thumbs');
        if (!wrap) {
            return;
        }
        var buttons = Array.prototype.slice.call(wrap.querySelectorAll('.cpl-thumb'));
        if (!buttons.length) {
            return;
        }
        sizings = [];

        // Les diapositives d'abord : les vignettes insérées juste après portent
        // l'index de la diapositive vidéo, qui doit donc déjà exister.
        var gallery = wrap.parentNode || document;
        insertSlides(gallery.querySelector('.js-product-carousel .carousel-inner'), buttons);
        insertSlides(document.querySelector('.js-product-images-modal-carousel .carousel-inner'), buttons);

        // Classic et dérivés : <ul class="product-images"> de <li class="thumb-container">.
        // Il y en a DEUX sur la fiche : celle de la galerie, et celle de la popup
        // d'agrandissement des images — d'où querySelectorAll, pour que la vidéo
        // soit aussi dans le défilement de la popup.
        // Hummingbird : carrousel Bootstrap, <ul class="thumbnails__list"> (1.x)
        // ou <ul class="product__thumbnails-list"> (2.x) de <li> — pas de swiper.
        var lists = Array.prototype.slice.call(
            document.querySelectorAll('ul.product-images, .js-qv-product-images, ul.thumbnails__list, ul.product__thumbnails-list')
        );
        var isSwiper = false;
        if (!lists.length) {
            // Thèmes à swiper : slides dans .swiper-wrapper.
            var sw = document.querySelector('.product-thumbs .swiper-wrapper');
            if (sw) {
                lists = [sw];
                isSwiper = true;
            }
        }
        if (!lists.length) {
            return; // aucun carrousel reconnu : la bande du hook reste en repli
        }

        var placed = false;
        lists.forEach(function (container, idx) {
            // La galerie reçoit les boutons d'origine, les listes suivantes
            // (popup) des clones : un même nœud ne peut pas être à deux endroits.
            // La délégation d'événements au niveau document gère les clones sans
            // rien réattacher.
            var items = idx === 0 ? buttons : buttons.map(function (b) {
                return b.cloneNode(true);
            });
            if (insertInto(container, items, isSwiper)) {
                placed = true;
            }
        });
        if (!placed) {
            return;
        }
        wrap.style.display = 'none';
        applyThumbSize();

        // ponytail: les flèches de défilement du carrousel Classic sont calculées
        // au init du thème ; si l'ajout fait déborder la liste, un resize force
        // la plupart des thèmes à recalculer.
        window.dispatchEvent(new Event('resize'));
    }

    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    ready(integrate);

    // Classic re-rend toute la galerie en AJAX au changement de déclinaison :
    // le hook est re-rendu avec elle, on réintègre les nouvelles vignettes.
    if (typeof prestashop !== 'undefined' && typeof prestashop.on === 'function') {
        prestashop.on('updatedProduct', function () {
            close();
            integrate();
        });
    }
})();
