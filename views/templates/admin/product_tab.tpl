{*
 * CoolPlay - Onglet fiche produit (back-office)
 * Autonome : styles et JS inline (le rendu de cet onglet diffère entre
 * PS 1.7 (chargement AJAX legacy) et PS 8/9 (page produit Symfony) —
 * l'inline fonctionne dans les deux cas.
 * @license GPL-3.0-or-later
 *}
<style>
#cpl-tab { max-width: 980px; }
/* Bloc relocalisé sous la zone d'images de la fiche produit (carte native) */
#cpl-tab.cpl-card { background:#fff; border:1px solid #e3e5e7; border-radius:8px; padding:16px 20px; margin-top:16px; max-width:none; }
#cpl-tab.cpl-card h3 { margin-top:0; font-size:15px; }
#cpl-tab .cpl-head { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:10px; }
#cpl-tab .cpl-head h3 { margin:0; }
#cpl-tab #cpl-add-toggle { white-space:nowrap; }
#cpl-tab #cpl-add-panel { border-top:1px solid #e3e5e7; padding-top:12px; margin-top:4px; }
#cpl-tab .cpl-intro { color:#6d7175; font-size:12.5px; margin:4px 0 16px; }
#cpl-tab .cpl-add { display:flex; gap:20px; flex-wrap:wrap; margin-bottom:20px; }
#cpl-tab .cpl-add-col { flex:1 1 320px; border:1px solid #e3e5e7; border-radius:8px; padding:14px 16px; background:#fafbfc; }
#cpl-tab .cpl-add-col h4 { margin:0 0 10px; font-size:13px; font-weight:600; }
#cpl-tab .cpl-add-col input[type=text] { width:100%; margin-bottom:8px; }
#cpl-tab .cpl-add-col input[type=file] { margin-bottom:8px; font-size:12px; }
#cpl-tab .cpl-add-col label { font-size:12px; font-weight:600; display:block; margin-bottom:3px; }
#cpl-tab .cpl-hint { font-size:11.5px; color:#8a8f99; margin:4px 0 0; }
#cpl-tab .cpl-mini { width:72px; height:40px; object-fit:cover; border-radius:4px; display:inline-block; background:#111; }
#cpl-tab .cpl-mini-ph { width:72px; height:40px; border-radius:4px; display:inline-block; background:linear-gradient(135deg,#374151,#111827); }
#cpl-tab .cpl-table td { vertical-align:middle; }
#cpl-tab .cpl-table .cpl-title { width:100%; min-width:160px; }
#cpl-tab .cpl-ref { font-family:monospace; font-size:11.5px; color:#6d7175; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
#cpl-tab .cpl-btn { border:1px solid #cbd5e1; background:#fff; border-radius:5px; padding:3px 9px; cursor:pointer; font-size:12px; line-height:1.4; }
#cpl-tab .cpl-btn:hover { background:#f1f5f9; }
#cpl-tab .cpl-btn-danger { color:#b91c1c; border-color:#fecaca; }
#cpl-tab .cpl-btn-danger:hover { background:#fef2f2; }
#cpl-tab .cpl-badge { display:inline-block; font-size:11px; font-weight:600; padding:2px 8px; border-radius:4px; background:#f1f3f5; border:1px solid #e3e5e7; color:#5b6770; }
#cpl-tab .cpl-badge-yt { color:#b91c1c; background:#fef2f2; border-color:#fecaca; }
#cpl-tab .cpl-badge-off { color:#8a8f99; }
#cpl-tab .cpl-empty { color:#8a8f99; font-style:italic; }
#cpl-tab #cpl-msg { margin-top:10px; }
#cpl-tab #cpl-msg .alert { margin-bottom:0; }
</style>

<div id="cpl-tab" data-ajax="{$cpl_ajax_url|escape:'html':'UTF-8'}" data-product="{$cpl_id_product|intval}">
    <div class="cpl-head">
        <h3>{l s='CoolPlay — Vidéos du produit' mod='coolplay'}</h3>
        <button type="button" class="btn btn-primary" id="cpl-add-toggle"
            data-label-open="+ {l s='Ajouter une vidéo' mod='coolplay'}"
            data-label-close="&minus; {l s='Fermer' mod='coolplay'}">+ {l s='Ajouter une vidéo' mod='coolplay'}</button>
    </div>

    <div id="cpl-rows">{$cpl_rows nofilter}</div>
    <div id="cpl-msg"></div>

    <div id="cpl-add-panel" hidden>
    <p class="cpl-intro">
        {l s='Les vidéos s\'affichent en vignettes dans la galerie de la fiche produit. Rien n\'est chargé avant le clic (façade click-to-load) : aucun impact sur la vitesse de la page, aucun cookie YouTube avant lecture.' mod='coolplay'}
    </p>

    <div class="cpl-add">
        <div class="cpl-add-col">
            <h4>{l s='Vidéo YouTube' mod='coolplay'}</h4>
            <label for="cpl-yt-url">{l s='URL de la vidéo' mod='coolplay'}</label>
            <input type="text" id="cpl-yt-url" placeholder="https://www.youtube.com/watch?v=...">
            <label for="cpl-yt-title">{l s='Titre (optionnel, utilisé pour le SEO)' mod='coolplay'}</label>
            <input type="text" id="cpl-yt-title" placeholder="{l s='Ex. : Démonstration en 2 minutes' mod='coolplay'}">
            <button type="button" class="btn btn-primary" id="cpl-add-yt">{l s='Ajouter la vidéo YouTube' mod='coolplay'}</button>
            <p class="cpl-hint">{l s='La miniature est rapatriée sur votre serveur automatiquement.' mod='coolplay'}</p>
        </div>
        <div class="cpl-add-col">
            <h4>{l s='Fichier vidéo (hébergement interne)' mod='coolplay'}</h4>
            <label for="cpl-file">{l s='Fichier MP4 ou WebM' mod='coolplay'}</label>
            <input type="file" id="cpl-file" accept="video/mp4,video/webm">
            <label for="cpl-poster">{l s='Image d\'aperçu (recommandée)' mod='coolplay'}</label>
            <input type="file" id="cpl-poster" accept="image/jpeg,image/png,image/webp">
            <label for="cpl-file-title">{l s='Titre (optionnel)' mod='coolplay'}</label>
            <input type="text" id="cpl-file-title">
            <button type="button" class="btn btn-primary" id="cpl-add-file">{l s='Ajouter le fichier' mod='coolplay'}</button>
            <p class="cpl-hint">{l s='Limite d\'envoi du serveur :' mod='coolplay'} {$cpl_upload_max|escape:'html':'UTF-8'}</p>
        </div>
    </div>
    </div>{* /cpl-add-panel *}
</div>

<script>
{literal}
(function () {
    'use strict';
    var root = document.getElementById('cpl-tab');
    if (!root || root.dataset.cplInit) { return; }
    root.dataset.cplInit = '1';

    var ajaxUrl = root.getAttribute('data-ajax');
    var idProduct = root.getAttribute('data-product');

    // UX : plutôt que de laisser le bloc au fond de l'onglet « Modules »,
    // on le déplace sous la zone d'images de la fiche produit.
    // PS 8/9 (page produit Symfony) : #product_description_images (.image-dropzone,
    // onglet Description). PS 1.7 legacy : #product-images-dropzone.
    // Cible introuvable (version exotique) → le bloc reste où il est.
    (function relocate() {
        var target = null;
        var sels = ['#product_description_images', '.image-dropzone', '#product-images-dropzone'];
        for (var i = 0; i < sels.length; i++) {
            target = document.querySelector(sels[i]);
            if (target) { break; }
        }
        if (target && target.parentNode) {
            root.classList.add('cpl-card');
            target.parentNode.insertBefore(root, target.nextSibling);
        }
    })();

    function msg(text, ok) {
        var box = document.getElementById('cpl-msg');
        box.innerHTML = text
            ? '<div class="alert alert-' + (ok ? 'success' : 'danger') + '">' + text + '</div>'
            : '';
        if (text && ok) { setTimeout(function () { box.innerHTML = ''; }, 2500); }
    }

    function call(action, fields, files) {
        var fd = new FormData();
        fd.append('ajax', '1');
        fd.append('action', action);
        fd.append('id_product', idProduct);
        Object.keys(fields || {}).forEach(function (k) { fd.append(k, fields[k]); });
        Object.keys(files || {}).forEach(function (k) {
            var input = files[k];
            if (input && input.files && input.files[0]) { fd.append(k, input.files[0]); }
        });
        return fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.rows !== undefined) { document.getElementById('cpl-rows').innerHTML = res.rows; }
                msg(res.ok ? '' : (res.error || 'Erreur inconnue'), res.ok);
                return res;
            })
            .catch(function () { msg('Erreur de communication avec le serveur.', false); });
    }

    var addPanel = document.getElementById('cpl-add-panel');
    var addToggle = document.getElementById('cpl-add-toggle');
    function setPanel(openIt) {
        addPanel.hidden = !openIt;
        addToggle.textContent = addToggle.getAttribute(openIt ? 'data-label-close' : 'data-label-open');
    }
    addToggle.addEventListener('click', function () { setPanel(addPanel.hidden); });

    document.getElementById('cpl-add-yt').addEventListener('click', function () {
        var url = document.getElementById('cpl-yt-url');
        if (!url.value.trim()) { msg('Indiquez une URL YouTube.', false); return; }
        this.disabled = true;
        var btn = this;
        call('AddVideo', { mode: 'youtube', url: url.value, title: document.getElementById('cpl-yt-title').value })
            .then(function (res) {
                btn.disabled = false;
                if (res && res.ok) {
                    url.value = '';
                    document.getElementById('cpl-yt-title').value = '';
                    setPanel(false);
                    msg('Vidéo ajoutée.', true);
                }
            });
    });

    document.getElementById('cpl-add-file').addEventListener('click', function () {
        var file = document.getElementById('cpl-file');
        if (!file.files || !file.files[0]) { msg('Choisissez un fichier vidéo.', false); return; }
        this.disabled = true;
        var btn = this;
        call('AddVideo', { mode: 'file', title: document.getElementById('cpl-file-title').value },
            { cpl_file: file, cpl_poster: document.getElementById('cpl-poster') })
            .then(function (res) {
                btn.disabled = false;
                if (res && res.ok) {
                    file.value = '';
                    document.getElementById('cpl-poster').value = '';
                    document.getElementById('cpl-file-title').value = '';
                    setPanel(false);
                    msg('Vidéo ajoutée.', true);
                }
            });
    });

    // Actions des lignes (délégation : les lignes sont re-rendues en AJAX).
    root.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-act]');
        if (!btn || btn.tagName === 'INPUT') { return; }
        var id = btn.getAttribute('data-id');
        var act = btn.getAttribute('data-act');
        if (act === 'del') {
            if (!window.confirm('Supprimer cette vidéo ?')) { return; }
            call('DeleteVideo', { id_video: id });
        } else if (act === 'toggle') {
            call('ToggleVideo', { id_video: id });
        } else if (act === 'up' || act === 'down') {
            call('MoveVideo', { id_video: id, dir: act });
        }
    });

    root.addEventListener('change', function (e) {
        var input = e.target.closest('input.cpl-title');
        if (input) {
            call('SaveTitle', { id_video: input.getAttribute('data-id'), title: input.value })
                .then(function (res) { if (res && res.ok) { msg('Titre enregistré.', true); } });
        }
    });
})();
{/literal}
</script>
