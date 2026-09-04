<?php
/**
 * CoolPlay - Vidéos produits sans impact sur la vitesse, pour PrestaShop
 *
 * @author    ZM40 — Nicolas Michaud (Magic Garden)
 * @copyright 2026 Nicolas Michaud — ZM40 / Magic Garden
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License version 3.0
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/classes/CplVideo.php';

class CoolPlay extends Module
{
    const ADMIN_CONTROLLER = 'AdminCoolPlay';

    public function __construct()
    {
        $this->name = 'coolplay';
        $this->tab = 'front_office_features';
        $this->version = '1.0.4';
        $this->author = 'ZM40';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);

        parent::__construct();

        $this->displayName = $this->l('CoolPlay - Vidéos produits');
        $this->description = $this->l('Vidéos YouTube ou MP4 dans la galerie produit, sans impact sur la vitesse : façade click-to-load (aucun iframe au chargement) et données structurées VideoObject pour le SEO.');
        $this->confirmUninstall = $this->l('Désinstaller CoolPlay ? Les vidéos associées aux produits (références, miniatures et fichiers envoyés) seront supprimées.');
    }

    /* ----------------------------------------------------------------------
     * Install / uninstall
     * -------------------------------------------------------------------- */

    public function install()
    {
        if (!parent::install()
            || !$this->registerHook('displayHeader')
            || !$this->registerHook('displayAfterProductThumbs')
            || !$this->registerHook('displayAdminProductsExtra')
            || !$this->registerHook('actionProductDelete')
        ) {
            return false;
        }

        if (!$this->installSql() || !$this->installTab()) {
            return false;
        }

        CplVideo::ensureDirs();
        $this->setDefaults();

        return true;
    }

    public function uninstall()
    {
        $this->uninstallTab();
        $this->uninstallSql();
        CplVideo::removeAllFiles();

        foreach ($this->configKeys() as $key) {
            Configuration::deleteByName($key);
        }
        // Clés ZM40 Common (réseau) — nettoyage standard.
        foreach (array('ZM40_NET_ENABLED', 'ZM40_FEED_CACHE', 'ZM40_FEED_CACHE_TS', 'ZM40_LASTCHECK_COOLPLAY', 'ZM40_LATEST_COOLPLAY') as $k) {
            Configuration::deleteByName($k);
        }

        return parent::uninstall();
    }

    private function installSql()
    {
        $sql = include dirname(__FILE__) . '/sql/install.php';
        if (!is_array($sql)) {
            return false;
        }
        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    private function uninstallSql()
    {
        $sql = include dirname(__FILE__) . '/sql/uninstall.php';
        if (is_array($sql)) {
            foreach ($sql as $query) {
                try {
                    Db::getInstance()->execute($query);
                } catch (Exception $e) {
                    // best effort
                }
            }
        }

        return true;
    }

    /**
     * Onglet invisible (id_parent -1) : porte le contrôleur AJAX de gestion
     * des vidéos appelé depuis l'onglet produit du back-office.
     */
    private function installTab()
    {
        if (Tab::getIdFromClassName(self::ADMIN_CONTROLLER)) {
            return true;
        }

        $tab = new Tab();
        $tab->class_name = self::ADMIN_CONTROLLER;
        $tab->module = $this->name;
        $tab->active = 1;
        $tab->id_parent = -1;

        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[(int) $lang['id_lang']] = 'CoolPlay';
        }

        try {
            return (bool) $tab->add();
        } catch (Exception $e) {
            return false;
        }
    }

    private function uninstallTab()
    {
        $id = (int) Tab::getIdFromClassName(self::ADMIN_CONTROLLER);
        if ($id) {
            try {
                $tab = new Tab($id);
                $tab->delete();
            } catch (Exception $e) {
                // best effort
            }
        }

        return true;
    }

    /**
     * Clés de configuration du module (pour le nettoyage à la désinstallation).
     *
     * @return string[]
     */
    private function configKeys()
    {
        return array('CPL_SCHEMA', 'CPL_FORCE_LIGHTBOX', 'CPL_THUMB_LAST');
    }

    private function setDefaults()
    {
        $defaults = array(
            'CPL_SCHEMA'         => 1,
            'CPL_FORCE_LIGHTBOX' => 0,
            'CPL_THUMB_LAST'     => 1,
            'ZM40_NET_ENABLED'   => 1,
        );
        foreach ($defaults as $k => $v) {
            Configuration::updateValue($k, $v);
        }
    }

    /* ----------------------------------------------------------------------
     * Front : médias + JSON-LD (fiche produit uniquement)
     * -------------------------------------------------------------------- */

    public function hookDisplayHeader()
    {
        $videos = $this->getCurrentProductVideos();
        if (empty($videos)) {
            return '';
        }

        $this->context->controller->addCSS($this->_path . 'views/css/front.css', 'all');
        $this->context->controller->addJS($this->_path . 'views/js/front.js');

        Media::addJsDef(array(
            'cplConfig' => array(
                'forceLightbox' => (int) Configuration::get('CPL_FORCE_LIGHTBOX'),
                'thumbLast'     => (int) Configuration::get('CPL_THUMB_LAST'),
                'closeLabel'    => $this->l('Fermer la vidéo'),
                'expandLabel'   => $this->l('Agrandir la vidéo'),
            ),
        ));

        if (!(int) Configuration::get('CPL_SCHEMA')) {
            return '';
        }

        return $this->renderSchema($videos, (int) Tools::getValue('id_product'));
    }

    /**
     * Vignettes vidéo ajoutées à la suite des vignettes images de la galerie
     * (hook standard des thèmes Classic et Hummingbird).
     */
    public function hookDisplayAfterProductThumbs($params)
    {
        $videos = $this->getCurrentProductVideos();
        if (empty($videos)) {
            return '';
        }

        $this->context->smarty->assign(array(
            'cpl_videos' => $this->hydrateForFront($videos),
        ));

        return $this->display(__FILE__, 'views/templates/hook/gallery_thumbs.tpl');
    }

    /**
     * Vidéos actives du produit affiché, ou [] hors fiche produit.
     * Mémoïsé : appelé par displayHeader ET displayAfterProductThumbs.
     */
    private $currentVideos;

    private function getCurrentProductVideos()
    {
        if ($this->currentVideos !== null) {
            return $this->currentVideos;
        }
        $this->currentVideos = array();

        $ctrl = isset($this->context->controller) ? $this->context->controller : null;
        if (!$ctrl || !isset($ctrl->php_self) || $ctrl->php_self !== 'product') {
            return $this->currentVideos;
        }
        $idProduct = (int) Tools::getValue('id_product');
        if ($idProduct > 0) {
            $this->currentVideos = CplVideo::getByProduct(
                $idProduct,
                (int) $this->context->shop->id,
                (int) $this->context->language->id,
                true
            );
        }

        return $this->currentVideos;
    }

    /**
     * Prépare les lignes DB pour le template front (URLs résolues).
     */
    private function hydrateForFront(array $videos)
    {
        $out = array();
        foreach ($videos as $v) {
            $v['thumb_url'] = CplVideo::thumbUrl($v);
            $v['file_url'] = ($v['type'] === CplVideo::TYPE_FILE) ? CplVideo::uploadUrl($v['video_ref']) : '';
            $out[] = $v;
        }

        return $out;
    }

    /**
     * JSON-LD VideoObject : rend la fiche éligible aux résultats enrichis
     * vidéo. C'est ce qui transforme la vidéo en atout SEO au lieu d'un poids.
     */
    private function renderSchema(array $videos, $idProduct)
    {
        $product = new Product($idProduct, false, (int) $this->context->language->id);
        if (!Validate::isLoadedObject($product)) {
            return '';
        }
        $productName = is_array($product->name) ? reset($product->name) : (string) $product->name;
        $desc = trim(strip_tags((string) $product->description_short));

        // Les URLs locales (thumbUrl/uploadUrl) sont déjà relatives à la racine
        // (__PS_BASE_URI__ inclus) : on ne préfixe que le domaine.
        $domain = Tools::getShopDomainSsl(true, true);

        $entries = array();
        foreach ($videos as $v) {
            $title = trim((string) $v['title']);
            if ($title === '') {
                $title = $productName;
            }
            $thumb = CplVideo::thumbUrl($v);
            if ($thumb !== '' && strpos($thumb, 'http') !== 0) {
                $thumb = $domain . $thumb;
            }
            $entry = array(
                '@context'     => 'https://schema.org',
                '@type'        => 'VideoObject',
                'name'         => $title,
                'description'  => ($desc !== '') ? Tools::substr($desc, 0, 300) : $title,
                'uploadDate'   => date('c', strtotime($v['date_add'])),
            );
            if ($thumb !== '') {
                $entry['thumbnailUrl'] = $thumb;
            }
            if ($v['type'] === CplVideo::TYPE_YOUTUBE) {
                $entry['embedUrl'] = 'https://www.youtube-nocookie.com/embed/' . $v['video_ref'];
            } else {
                $entry['contentUrl'] = $domain . CplVideo::uploadUrl($v['video_ref']);
            }
            $entries[] = $entry;
        }

        // JSON_HEX_TAG : échappe < et > (<...) pour qu'un titre contenant
        // "</script>" ne puisse pas fermer la balise d'injection.
        $json = json_encode(count($entries) === 1 ? $entries[0] : $entries, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);

        return '<script type="application/ld+json">' . $json . '</script>';
    }

    /* ----------------------------------------------------------------------
     * Back-office : onglet fiche produit + nettoyage
     * -------------------------------------------------------------------- */

    /**
     * Onglet « Modules » de la fiche produit (hook stable 1.7 → 9).
     * Tout est géré en AJAX vers AdminCoolPlay : aucun couplage avec la
     * soumission du formulaire produit (qui diffère entre 1.7 et 8/9).
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        $idProduct = isset($params['id_product']) ? (int) $params['id_product'] : 0;
        if (!$idProduct) {
            $idProduct = (int) Tools::getValue('id_product');
        }
        if (!$idProduct) {
            return '';
        }

        $this->context->smarty->assign(array(
            'cpl_id_product' => $idProduct,
            'cpl_ajax_url'   => $this->context->link->getAdminLink(self::ADMIN_CONTROLLER),
            'cpl_rows'       => $this->renderProductRows($idProduct),
            'cpl_upload_max' => ini_get('upload_max_filesize'),
        ));

        return $this->display(__FILE__, 'views/templates/admin/product_tab.tpl');
    }

    /**
     * Rendu de la liste des vidéos d'un produit (partagé avec le contrôleur
     * AJAX qui re-rend ce fragment après chaque action).
     */
    public function renderProductRows($idProduct)
    {
        $videos = CplVideo::getByProduct(
            (int) $idProduct,
            (int) $this->context->shop->id,
            (int) $this->context->language->id,
            false
        );
        foreach ($videos as &$v) {
            $v['thumb_url'] = CplVideo::thumbUrl($v);
        }
        unset($v);

        $this->context->smarty->assign(array('cpl_videos' => $videos));

        return $this->context->smarty->fetch(dirname(__FILE__) . '/views/templates/admin/product_tab_rows.tpl');
    }

    public function hookActionProductDelete($params)
    {
        $idProduct = isset($params['id_product']) ? (int) $params['id_product'] : 0;
        if ($idProduct > 0) {
            try {
                CplVideo::deleteByProduct($idProduct);
            } catch (Exception $e) {
                // best effort
            }
        }
    }

    /* ----------------------------------------------------------------------
     * Back-office : page de configuration
     * -------------------------------------------------------------------- */

    public function getContent()
    {
        require_once dirname(__FILE__) . '/lib/zm40/Zm40CommonCpl.php';

        $output = '';
        if (Tools::isSubmit('submitCplConfig')) {
            $output .= $this->postProcessConfig();
        }

        $this->context->controller->addCSS($this->_path . 'views/css/zm40-common.css');

        $this->assignZm40();
        $this->context->smarty->assign(array(
            'cpl_form'           => $this->renderConfigForm(),
            'cpl_products'       => $this->getProductsWithVideos(),
            'cpl_count_videos'   => (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'cpl_video`'),
            'cpl_count_products' => (int) Db::getInstance()->getValue('SELECT COUNT(DISTINCT id_product) FROM `' . _DB_PREFIX_ . 'cpl_video`'),
            'zm40_ah_name'       => $this->displayName,
            'zm40_ah_sub'        => $this->l('Vidéos produits sans impact sur la vitesse'),
            'zm40_ah_version'    => $this->version,
            'zm40_ah_shop'       => Configuration::get('PS_SHOP_NAME'),
        ));

        return $output . $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    /**
     * Produits de la boutique courante ayant au moins une vidéo, avec liens
     * vers la fiche BO (édition) et la fiche front.
     *
     * @return array [{id_product, name, nb, nb_active, bo_link, front_link}]
     */
    private function getProductsWithVideos()
    {
        $idShop = (int) $this->context->shop->id;
        $idLang = (int) $this->context->language->id;

        $rows = Db::getInstance()->executeS(
            'SELECT v.id_product, COUNT(*) AS nb, SUM(v.active) AS nb_active, pl.name
             FROM `' . _DB_PREFIX_ . 'cpl_video` v
             LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                ON (pl.id_product = v.id_product AND pl.id_lang = ' . $idLang . ' AND pl.id_shop = ' . $idShop . ')
             WHERE v.id_shop = ' . $idShop . '
             GROUP BY v.id_product
             ORDER BY pl.name ASC, v.id_product ASC'
        );
        if (!is_array($rows)) {
            return array();
        }

        foreach ($rows as &$row) {
            $id = (int) $row['id_product'];
            // Lien d'édition produit : LinkCore mappe lui-même vers la bonne
            // route Symfony selon la version (1.7 / 8 / 9).
            try {
                $row['bo_link'] = $this->context->link->getAdminLink(
                    'AdminProducts',
                    true,
                    array('id_product' => $id, 'updateproduct' => 1),
                    array('id_product' => $id, 'updateproduct' => 1)
                );
            } catch (Exception $e) {
                $row['bo_link'] = '';
            }
            try {
                $row['front_link'] = $this->context->link->getProductLink($id);
            } catch (Exception $e) {
                $row['front_link'] = '';
            }
        }
        unset($row);

        return $rows;
    }

    private function assignZm40()
    {
        $this->context->smarty->assign(array(
            'zm40_net_enabled'   => Zm40CommonCpl::isNetEnabled() ? 1 : 0,
            'zm40_footer_html'   => Zm40CommonCpl::footer($this->displayName, $this->version, $this->name),
            'zm40_update'        => Zm40CommonCpl::checkUpdate($this->name, $this->version),
            'zm40_modules'       => Zm40CommonCpl::modulesFeed($this->name),
            'zm40_about_name'    => $this->displayName,
            'zm40_about_license' => 'OSL 3.0',
            'zm40_about_github'  => Zm40CommonCpl::githubUrl($this->name),
            'zm40_about_site'    => Zm40CommonCpl::siteUrl($this->name, 'panel', '/contact'),
            'zm40_about_modules' => Zm40CommonCpl::siteUrl($this->name, 'panel', '/'),
        ));
    }

    private function postProcessConfig()
    {
        foreach (array('CPL_SCHEMA', 'CPL_FORCE_LIGHTBOX', 'CPL_THUMB_LAST', 'ZM40_NET_ENABLED') as $k) {
            Configuration::updateValue($k, (int) Tools::getValue($k));
        }
        Zm40CommonCpl::clearFeedCache();

        return $this->displayConfirmation($this->l('Paramètres enregistrés.'));
    }

    private function renderConfigForm()
    {
        $onOff = function ($name, $label, $desc = '') {
            return array(
                'type' => 'switch', 'label' => $label, 'name' => $name, 'is_bool' => true, 'desc' => $desc,
                'values' => array(
                    array('id' => $name . '_on', 'value' => 1, 'label' => $this->l('Oui')),
                    array('id' => $name . '_off', 'value' => 0, 'label' => $this->l('Non')),
                ),
            );
        };

        $fields_form = array(
            'form' => array(
                'legend' => array('title' => $this->l('Configuration'), 'icon' => 'icon-cogs'),
                'input' => array(
                    $onOff('CPL_SCHEMA', $this->l('Données structurées VideoObject (SEO)'),
                        $this->l('JSON-LD injecté sur les fiches produit avec vidéo : éligibilité aux résultats enrichis vidéo de Google. Recommandé.')),
                    $onOff('CPL_FORCE_LIGHTBOX', $this->l('Toujours ouvrir en lightbox'),
                        $this->l('Par défaut la vidéo se lit à la place de l\'image principale de la galerie ; si votre thème s\'y prête mal, forcez l\'ouverture en lightbox plein écran.')),
                    $onOff('CPL_THUMB_LAST', $this->l('Vignettes vidéo en fin de liste'),
                        $this->l('Oui : les vignettes vidéo suivent les images du produit. Non : elles sont placées en tête de la galerie, ce qui les garde visibles sur les carrousels dont la pagination est figée au chargement.')),
                    $onOff('ZM40_NET_ENABLED', $this->l('Fonctions réseau ZM40'),
                        $this->l('Vérification de nouvelle version (API publique GitHub) et liste des autres modules ZM40. Requêtes anonymes, aucune donnée boutique transmise.')),
                ),
                'submit' => array('title' => $this->l('Enregistrer')),
            ),
        );

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitCplConfig';
        $helper->default_form_language = (int) $this->context->language->id;
        $helper->fields_value = array(
            'CPL_SCHEMA'         => Configuration::get('CPL_SCHEMA'),
            'CPL_FORCE_LIGHTBOX' => Configuration::get('CPL_FORCE_LIGHTBOX'),
            'CPL_THUMB_LAST'     => Configuration::get('CPL_THUMB_LAST'),
            'ZM40_NET_ENABLED'   => Configuration::get('ZM40_NET_ENABLED'),
        );

        return $helper->generateForm(array($fields_form));
    }
}
