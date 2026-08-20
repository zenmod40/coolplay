{* ZM40 Common — notice de mise à jour (notify-only). À inclure EN HAUT (alerte actionnable).
   Var attendue : $zm40_update = ['available'=>bool,'latest'=>string,'url'=>string] | null *}
{if isset($zm40_update) && $zm40_update && $zm40_update.available}
    <div class="zm40-update-notice">
        <i class="icon-arrow-circle-up"></i>
        <span>Une nouvelle version (<strong>{$zm40_update.latest|escape:'html':'UTF-8'}</strong>) est disponible.</span>
        <a href="{$zm40_update.url|escape:'html':'UTF-8'}" target="_blank" rel="noopener">Voir la release</a>
    </div>
{/if}
