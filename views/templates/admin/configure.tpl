{*
 * CoolPlay - Page de configuration (back-office)
 * @license https://opensource.org/licenses/OSL-3.0 Open Software License version 3.0
 *}
{include file="./_partials/zm40_update.tpl"}

{* En-tête ZM40 inline (autonome, pas de marqueur à résoudre au build). *}
<style>
.zm40-ah { background: linear-gradient(135deg, #DC2626 0%, #7F1D1D 100%); color:#fff; padding:28px 32px; border-radius:8px; margin-bottom:24px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; }
.zm40-ah * { box-sizing:border-box; }
.zm40-ah h2 { margin:0; font-size:22px; font-weight:600; color:#fff; line-height:1.2; }
.zm40-ah-sub { opacity:.85; font-size:13px; margin-top:4px; color:#fff; }
.zm40-ah-badge { background:rgba(255,255,255,.2); padding:6px 14px; border-radius:20px; font-size:12px; font-weight:500; color:#fff; white-space:nowrap; }
</style>
<div class="zm40-ah">
    <div>
        <h2>{$zm40_ah_name|escape:'html':'UTF-8'}</h2>
        <div class="zm40-ah-sub">{$zm40_ah_sub|escape:'html':'UTF-8'} &middot; v{$zm40_ah_version|escape:'html':'UTF-8'}</div>
    </div>
    {if isset($zm40_ah_shop) && $zm40_ah_shop}<span class="zm40-ah-badge">{$zm40_ah_shop|escape:'html':'UTF-8'}</span>{/if}
</div>

<div class="alert alert-info">
    <i class="icon-film"></i>
    {if $cpl_count_videos > 0}
        {$cpl_count_videos|intval} {if $cpl_count_videos > 1}{l s='vidéos sur' mod='coolplay'}{else}{l s='vidéo sur' mod='coolplay'}{/if} {$cpl_count_products|intval} {if $cpl_count_products > 1}{l s='produits.' mod='coolplay'}{else}{l s='produit.' mod='coolplay'}{/if}
    {/if}
    {l s='Les vidéos se gèrent directement sur chaque fiche produit, dans le bloc CoolPlay affiché sous la zone d\'images.' mod='coolplay'}
</div>

{assign var=zm40_has_modules value=(isset($zm40_modules) && $zm40_modules|@count)}

<ul class="nav nav-tabs" id="cpl-config-tabs">
    <li class="active"><a href="#cpl-tab-config" data-toggle="tab"><i class="icon-cogs"></i> {l s='Configuration' mod='coolplay'}</a></li>
    <li><a href="#cpl-tab-products" data-toggle="tab"><i class="icon-film"></i> {l s='Produits avec vidéos' mod='coolplay'} ({$cpl_products|@count})</a></li>
    {if $zm40_has_modules}
        <li><a href="#cpl-tab-modules" data-toggle="tab"><i class="icon-th-large"></i> {l s='Modules ZM40' mod='coolplay'}</a></li>
    {/if}
</ul>
<div class="tab-content" style="padding-top:15px;">
    <div class="tab-pane active" id="cpl-tab-config">
        {$cpl_form nofilter}
    </div>
    <div class="tab-pane" id="cpl-tab-products">
        <div class="panel">
            <div class="panel-heading"><i class="icon-film"></i> {l s='Produits avec vidéos' mod='coolplay'}</div>
            {if $cpl_products|@count}
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width:70px">{l s='ID' mod='coolplay'}</th>
                            <th>{l s='Produit' mod='coolplay'}</th>
                            <th style="width:90px">{l s='Vidéos' mod='coolplay'}</th>
                            <th>{l s='État' mod='coolplay'}</th>
                            <th style="width:260px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$cpl_products item=p}
                            <tr>
                                <td>{$p.id_product|intval}</td>
                                <td>{if $p.name}{$p.name|escape:'html':'UTF-8'}{else}<em>{l s='(produit introuvable dans cette boutique)' mod='coolplay'}</em>{/if}</td>
                                <td>{$p.nb|intval}</td>
                                <td>
                                    {if $p.nb - $p.nb_active - $p.nb_unavailable > 0}
                                        <span class="label label-warning">{($p.nb - $p.nb_active - $p.nb_unavailable)|intval} {if $p.nb - $p.nb_active - $p.nb_unavailable > 1}{l s='masquées' mod='coolplay'}{else}{l s='masquée' mod='coolplay'}{/if}</span>
                                    {/if}
                                    {if $p.nb_unavailable > 0}
                                        <span class="label label-danger" title="{l s='Vidéo supprimée ou rendue privée sur YouTube : désactivée automatiquement, elle n\'apparaît plus en boutique. Remplacez-la ou supprimez-la depuis la fiche produit.' mod='coolplay'}">{$p.nb_unavailable|intval} {if $p.nb_unavailable > 1}{l s='introuvables sur YouTube' mod='coolplay'}{else}{l s='introuvable sur YouTube' mod='coolplay'}{/if}</span>
                                    {/if}
                                    {if $p.nb_no_thumb > 0}
                                        <span class="label label-default" title="{l s='Miniature YouTube pas encore rapatriée sur le serveur : lancez la reconstruction en bas de page.' mod='coolplay'}">{$p.nb_no_thumb|intval} {l s='sans miniature' mod='coolplay'}</span>
                                    {/if}
                                </td>
                                <td class="text-right">
                                    {if $p.bo_link}
                                        <a class="btn btn-default btn-xs" href="{$p.bo_link|escape:'html':'UTF-8'}"><i class="icon-pencil"></i> {l s='Modifier la fiche' mod='coolplay'}</a>
                                    {/if}
                                    {if $p.front_link}
                                        <a class="btn btn-default btn-xs" href="{$p.front_link|escape:'html':'UTF-8'}" target="_blank" rel="noopener"><i class="icon-eye"></i> {l s='Voir en boutique' mod='coolplay'}</a>
                                    {/if}
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            {else}
                <p style="margin:0">{l s='Aucune vidéo pour le moment. Ouvrez une fiche produit : le bloc CoolPlay se trouve sous la zone d\'images.' mod='coolplay'}</p>
            {/if}
        </div>
    </div>
    {if $zm40_has_modules}
        <div class="tab-pane" id="cpl-tab-modules">
            {include file="./_partials/zm40_modules.tpl"}
        </div>
    {/if}
</div>

{* Panneau SEO — sous le bloc de configuration *}
<div class="panel">
    <div class="panel-heading"><i class="icon-dashboard"></i> {l s='SEO et performance : ce que le chargement au clic change' mod='coolplay'}</div>
    <p>{l s='La mauvaise réputation SEO des vidéos vient d\'un seul problème : une iframe YouTube chargée d\'office pèse entre 800 Ko et 1,2 Mo de JavaScript tiers, avant même que le visiteur ait décidé de regarder. Ici, l\'iframe n\'est insérée qu\'au clic sur la vignette.' mod='coolplay'}</p>
    <ul style="margin:8px 0 4px 18px; line-height:1.7">
        <li><strong>{l s='Zéro impact sur la vitesse (Core Web Vitals préservés).' mod='coolplay'}</strong> {l s='Au chargement de la fiche produit, seuls une miniature JPEG servie par votre serveur et environ 2 Ko de CSS/JS sont chargés : aucune iframe, aucun script tiers, aucune requête vers YouTube. LCP, TBT et CLS restent intacts — c\'est la technique de « façade » recommandée par Google dans ses audits Lighthouse.' mod='coolplay'}</li>
        <li><strong>{l s='Résultats enrichis vidéo (VideoObject).' mod='coolplay'}</strong> {l s='Chaque fiche avec vidéo reçoit des données structurées JSON-LD (titre, description, miniature, date, URL d\'embarquement) : votre produit devient éligible aux résultats enrichis vidéo et à l\'onglet Vidéos de Google. Renseignez le titre de chaque vidéo pour en tirer le meilleur parti.' mod='coolplay'}</li>
        <li><strong>{l s='Conversion préservée.' mod='coolplay'}</strong> {l s='La vidéo se lit sur place, dans la galerie : le visiteur ne part pas sur YouTube où l\'algorithme lui recommanderait d\'autres contenus, y compris ceux de vos concurrents.' mod='coolplay'}</li>
        <li><strong>{l s='RGPD sans effort.' mod='coolplay'}</strong> {l s='Aucun cookie ni requête tierce avant le clic du visiteur ; la lecture passe par youtube-nocookie.com. L\'affichage de la fiche produit n\'a donc rien à déclarer dans votre bandeau de consentement.' mod='coolplay'}</li>
    </ul>
</div>

{* Maintenance : reconstruction des miniatures YouTube après un import direct en base *}
<div class="panel">
    <div class="panel-heading"><i class="icon-refresh"></i> {l s='Maintenance des miniatures YouTube' mod='coolplay'}</div>
    <p>{l s='Après une migration ou un import direct en base, les vidéos YouTube ajoutées sans passer par la fiche produit n\'ont pas de miniature locale : la fiche produit retombe alors sur i.ytimg.com, une requête tierce avant le clic. Le bouton rejoue le rapatriement fait à l\'ajout (qualité maximale d\'abord, repli inclus) et met la base à jour. Les miniatures déjà présentes ne sont pas retéléchargées. Une vidéo que YouTube ne trouve plus (supprimée ou privée) est désactivée et signalée dans l\'onglet Produits avec vidéos, au lieu d\'être retentée à chaque clic.' mod='coolplay'}</p>
    <p style="margin-bottom:8px">
        {if $cpl_thumbs_missing > 0}
            <span class="label label-warning">{$cpl_thumbs_missing|intval} {if $cpl_thumbs_missing > 1}{l s='miniatures manquantes sur le serveur' mod='coolplay'}{else}{l s='miniature manquante sur le serveur' mod='coolplay'}{/if}</span>
        {else}
            <span class="label label-success"><i class="icon-ok"></i> {l s='Toutes les miniatures sont présentes' mod='coolplay'}</span>
        {/if}
    </p>
    <form method="post" action="{$cpl_rebuild_action|escape:'html':'UTF-8'}">
        <input type="hidden" name="token" value="{$cpl_rebuild_token|escape:'html':'UTF-8'}">
        <button type="submit" name="submitCplRebuildThumbs" value="1" class="btn btn-default" data-confirm="{l s='Reconstruire les miniatures YouTube manquantes ? Chaque vidéo absente du serveur sera téléchargée depuis YouTube : cela peut prendre quelques instants.' mod='coolplay'}" onclick="return confirm(this.getAttribute('data-confirm'));">
            <i class="icon-refresh"></i> {l s='Reconstruire les miniatures YouTube' mod='coolplay'}
        </button>
    </form>
</div>

{* Panel « libre & open source » + prestations — toujours en bas, visible quel que soit l'onglet actif *}
{include file="./_partials/zm40_panel.tpl"}

{include file="./_partials/zm40_footer.tpl"}
