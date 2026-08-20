{*
 * CoolPlay - Liste des vidéos d'un produit (fragment re-rendu en AJAX)
 * @license GPL-3.0-or-later
 *}
{if $cpl_videos|@count}
    <table class="table cpl-table">
        <thead>
            <tr>
                <th></th>
                <th>{l s='Type' mod='coolplay'}</th>
                <th>{l s='Titre' mod='coolplay'}</th>
                <th>{l s='Référence' mod='coolplay'}</th>
                <th>{l s='Ordre' mod='coolplay'}</th>
                <th>{l s='Visibilité' mod='coolplay'}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$cpl_videos item=v}
                <tr data-id="{$v.id_cpl_video|intval}">
                    <td>
                        {if $v.thumb_url}
                            <img class="cpl-mini" src="{$v.thumb_url|escape:'html':'UTF-8'}" alt="">
                        {else}
                            <span class="cpl-mini-ph"></span>
                        {/if}
                    </td>
                    <td>
                        {if $v.type == 'youtube'}
                            <span class="cpl-badge cpl-badge-yt">YouTube</span>
                        {else}
                            <span class="cpl-badge">{l s='Fichier' mod='coolplay'}</span>
                        {/if}
                    </td>
                    <td><input type="text" class="cpl-title" value="{$v.title|escape:'html':'UTF-8'}" data-id="{$v.id_cpl_video|intval}" placeholder="{l s='Titre (SEO)' mod='coolplay'}"></td>
                    <td class="cpl-ref" title="{$v.video_ref|escape:'html':'UTF-8'}">{$v.video_ref|escape:'html':'UTF-8'}</td>
                    <td>
                        <button type="button" class="cpl-btn" data-act="up" data-id="{$v.id_cpl_video|intval}" title="{l s='Monter' mod='coolplay'}">&uarr;</button>
                        <button type="button" class="cpl-btn" data-act="down" data-id="{$v.id_cpl_video|intval}" title="{l s='Descendre' mod='coolplay'}">&darr;</button>
                    </td>
                    <td>
                        <button type="button" class="cpl-btn{if !$v.active} cpl-badge-off{/if}" data-act="toggle" data-id="{$v.id_cpl_video|intval}">
                            {if $v.active}{l s='Visible' mod='coolplay'}{else}{l s='Masquée' mod='coolplay'}{/if}
                        </button>
                    </td>
                    <td>
                        <button type="button" class="cpl-btn cpl-btn-danger" data-act="del" data-id="{$v.id_cpl_video|intval}">{l s='Supprimer' mod='coolplay'}</button>
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>
{else}
    <p class="cpl-empty">{l s='Aucune vidéo pour ce produit.' mod='coolplay'}</p>
{/if}
