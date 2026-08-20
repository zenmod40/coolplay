/**
 * CoolPlay - Façade click-to-load : rien n'est chargé avant le clic.
 * Les vignettes vidéo (rendues par le hook sous la galerie) sont déplacées
 * dans le carrousel de vignettes du thème (ul Classic ou swiper Hummingbird) ;
 * la bande sous la galerie reste le repli pour les thèmes non reconnus.
 * Au clic : iframe youtube-nocookie (ou <video>) à la place de l'image
 * principale ; lightbox en repli ou en mode forcé.
 * @license GPL-3.0-or-later
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
        if (e.target.closest('.cpl-thumb')) {
            return; // changement de vidéo : géré par open()
        }
        if (e.target.closest('.thumb-container, .js-thumb, .product-thumb, ul.product-images li, .product-thumbs .swiper-slide')) {
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
        current = null;
        document.removeEventListener('keydown', onKey);
        document.removeEventListener('click', onDocClick, true);
    }

    function open(btn) {
        close();
        var host = null;
        if (!cfg.forceLightbox) {
            var sels = ['.product-cover', '.images-container', '.product-images'];
            for (var i = 0; i < sels.length; i++) {
                host = document.querySelector(sels[i]);
                if (host) {
                    break;
                }
            }
        }

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'cpl-close';
        closeBtn.innerHTML = '&times;';
        closeBtn.setAttribute('aria-label', cfg.closeLabel || 'Fermer');
        closeBtn.addEventListener('click', close);

        var box = document.createElement('div');
        if (host) {
            box.className = 'cpl-player';
            host.classList.add('cpl-hosting');
            box.appendChild(playerFor(btn));
            box.appendChild(closeBtn);
            host.appendChild(box);
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
     * Déplace les boutons vidéo dans la liste de vignettes du thème, en
     * copiant les classes et dimensions d'une vignette existante pour
     * hériter du style. Sans carrousel reconnu (ou sans image), la bande
     * rendue par le hook reste en place : c'est le repli.
     */
    function integrate() {
        var wrap = document.getElementById('cpl-thumbs');
        if (!wrap) {
            return;
        }
        var buttons = Array.prototype.slice.call(wrap.querySelectorAll('.cpl-thumb'));

        var container = null;
        var isSwiper = false;
        // Classic et dérivés : <ul class="product-images"> de <li class="thumb-container">.
        var ul = document.querySelector('ul.product-images, .js-qv-product-images');
        if (ul) {
            container = ul;
        } else {
            // Hummingbird et thèmes à swiper : slides dans .swiper-wrapper.
            var sw = document.querySelector('.product-thumbs .swiper-wrapper');
            if (sw) {
                container = sw;
                isSwiper = true;
            }
        }
        if (!container) {
            return;
        }
        var refItem = container.firstElementChild;
        if (!refItem || !container.querySelector('img')) {
            return; // produit sans image : pas de carrousel où s'insérer
        }
        // Classes de layout copiées de la vignette de référence, MAIS pas les
        // hooks de comportement (js-*, selected) : le JS du thème ne doit pas
        // considérer nos items comme des vignettes image (pas de data-image-*).
        // Aucune taille injectée : le bouton suit son conteneur (width 100%,
        // height auto en CSS), donc responsive comme les vignettes du thème.
        var itemClass = refItem.className.split(/\s+/).filter(function (c) {
            return c !== '' && c !== 'selected' && c.indexOf('js-') !== 0;
        }).join(' ');

        // Insertion en TÊTE de liste : toujours visible même quand le carrousel
        // du thème a déjà calculé sa pagination (flèches figées après init),
        // et met la vidéo en avant. Itération inversée pour garder l'ordre BO.
        buttons.slice().reverse().forEach(function (btn) {
            var item = document.createElement(refItem.tagName);
            item.className = itemClass + ' cpl-thumb-item';
            item.appendChild(btn);
            container.insertBefore(item, container.firstChild);
        });
        wrap.style.display = 'none';

        if (isSwiper) {
            var swiperEl = container.closest('.swiper, .product-thumbs');
            if (swiperEl && swiperEl.swiper && typeof swiperEl.swiper.update === 'function') {
                swiperEl.swiper.update();
            }
        }
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
