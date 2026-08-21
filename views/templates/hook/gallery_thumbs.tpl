{*
 * CoolPlay - Vignettes vidéo dans la galerie produit (façade click-to-load)
 * Aucun iframe, aucune requête tierce avant le clic : une image locale + CSS.
 * @license https://opensource.org/licenses/OSL-3.0 Open Software License version 3.0
 *}
{if $cpl_videos|@count}
    <div id="cpl-thumbs" class="cpl-thumbs">
        {foreach from=$cpl_videos item=v}
            <button type="button" class="cpl-thumb"
                data-type="{$v.type|escape:'html':'UTF-8'}"
                {if $v.type == 'youtube'}
                    data-ref="{$v.video_ref|escape:'html':'UTF-8'}"
                {else}
                    data-src="{$v.file_url|escape:'html':'UTF-8'}"
                    {if $v.thumb_url}data-poster="{$v.thumb_url|escape:'html':'UTF-8'}"{/if}
                {/if}
                data-title="{$v.title|escape:'html':'UTF-8'}"
                aria-label="{l s='Lire la vidéo' mod='coolplay'}{if $v.title} : {$v.title|escape:'html':'UTF-8'}{/if}">
                {if $v.thumb_url}
                    <img src="{$v.thumb_url|escape:'html':'UTF-8'}" alt="{$v.title|escape:'html':'UTF-8'}" loading="lazy">
                {/if}
                <span class="cpl-play" aria-hidden="true"></span>
            </button>
        {/foreach}
    </div>
{/if}
