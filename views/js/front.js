/**
 * CoolPlay - Façade click-to-load : rien n'est chargé avant le clic.
 * Les vignettes vidéo (rendues par le hook sous la galerie) sont déplacées
 * dans le carrousel de vignettes du thème (ul Classic ou swiper Hummingbird) ;
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

    /**
     * Où poser le lecteur ? Si la vignette cliquée est dans la popup images du
     * thème, on prend la place de la GRANDE image de la popup — sinon celle de
     * l'image principale de la page.
     */
    function hostFor(btn) {
        if (cfg.forceLightbox) {
            return null;
        }
        var modal = btn.closest('.modal, .js-product-images-modal');
        if (modal) {
            var big = modal.querySelector('.js-modal-product-cover, .product-cover-modal');
            if (big) {
                // <picture> plutôt que <figure> : en Classic la figure contient
                // aussi la légende (description courte), que le lecteur
                // recouvrirait. Le picture épouse exactement l'image.
                return big.closest('picture') || big.closest('figure') || big.parentNode;
            }
            return modal.querySelector('.modal-body') || modal;
        }
        var sels = ['.product-cover', '.images-container', '.product-images'];
        for (var i = 0; i < sels.length; i++) {
            var host = document.querySelector(sels[i]);
            if (host) {
                return host;
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
        if (!refItem || !container.querySelector('img')) {
            return false; // produit sans image : pas de carrousel où s'insérer
        }
        // Classes de layout copiées de la vignette de référence, MAIS pas les
        // hooks de comportement (js-*, selected) : le JS du thème ne doit pas
        // considérer nos items comme des vignettes image (pas de data-image-*).
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

        // Classic et dérivés : <ul class="product-images"> de <li class="thumb-container">.
        // Il y en a DEUX sur la fiche : celle de la galerie, et celle de la popup
        // d'agrandissement des images — d'où querySelectorAll, pour que la vidéo
        // soit aussi dans le défilement de la popup.
        var lists = Array.prototype.slice.call(
            document.querySelectorAll('ul.product-images, .js-qv-product-images')
        );
        var isSwiper = false;
        if (!lists.length) {
            // Hummingbird et thèmes à swiper : slides dans .swiper-wrapper.
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
