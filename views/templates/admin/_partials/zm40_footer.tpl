{* ZM40 Common — footer d'attribution. Var attendue : $zm40_footer_html (HTML pré-rendu par Zm40Common::footer). *}
{if isset($zm40_footer_html) && $zm40_footer_html}
    {$zm40_footer_html nofilter}
{/if}
