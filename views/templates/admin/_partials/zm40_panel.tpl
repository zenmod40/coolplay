{* ZM40 Common — bloc « libre & open source » + prestations (page de config, en bas).
   La liste des autres modules est affichée dans l'onglet dédié (zm40_modules_tab.tpl). *}

{if isset($zm40_about_name) && $zm40_about_name}
    <div class="panel zm40-about">
        <div class="panel-heading"><i class="icon-leaf"></i> ZM40 &middot; Magic Garden</div>
        <div class="row">
            <div class="col-lg-9">
                <p>{$zm40_about_name|escape:'html':'UTF-8'} est un module <strong>libre et open source</strong>, distribué sous licence {if isset($zm40_about_license) && $zm40_about_license}{$zm40_about_license|escape:'html':'UTF-8'}{else}GPL v3{/if} : code source ouvert, auditable, modifiable et redistribuable sans restriction.</p>
                <p>En complément du module, ZM40 propose des prestations sur devis : analyse et conseils, installation et configuration, adaptation à votre thème, modifications et développements sur-mesure (KPI, connecteurs ERP / marketplace / API), débogage, maintenance et support.</p>
                <p>
                    {if isset($zm40_about_github) && $zm40_about_github}<a href="{$zm40_about_github|escape:'html':'UTF-8'}" target="_blank" rel="noopener">Voir le code sur GitHub</a>{/if}{if isset($zm40_about_site) && $zm40_about_site} &middot; <a href="{$zm40_about_site|escape:'html':'UTF-8'}" target="_blank" rel="noopener">Parlez de votre projet</a>{/if}{if isset($zm40_about_modules) && $zm40_about_modules} &middot; <a href="{$zm40_about_modules|escape:'html':'UTF-8'}" target="_blank" rel="noopener">découvrir nos modules</a>{/if}
                </p>
            </div>
        </div>
    </div>
{/if}
