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

require_once _PS_MODULE_DIR_ . 'coolplay/classes/CplVideo.php';

/**
 * Contrôleur AJAX de gestion des vidéos, appelé depuis l'onglet CoolPlay de
 * la fiche produit (onglet BO invisible : aucune entrée de menu).
 * Toutes les réponses : JSON {ok, error?, rows?}.
 */
class AdminCoolPlayController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    /**
     * Compatibilité traduction cross-version : PrestaShop 9 a retiré la méthode
     * legacy l() des contrôleurs admin. On délègue au natif sur 1.7/8, sinon on
     * passe par le traducteur Symfony (repli : chaîne source).
     */
    public function l($string, $class = null, $addslashes = false, $htmlentities = true)
    {
        if (method_exists(get_parent_class($this), 'l')) {
            return parent::l($string, $class, $addslashes, $htmlentities);
        }
        if (method_exists($this, 'trans')) {
            return $this->trans($string, array(), 'Modules.Coolplay.Admin');
        }

        return $string;
    }

    /**
     * Contrôleur AJAX uniquement : un accès direct n'affiche rien.
     */
    public function initContent()
    {
        $this->content = '';
        $this->context->smarty->assign('content', '');
    }

    /* ----------------------------------------------------------------------
     * Actions AJAX
     * -------------------------------------------------------------------- */

    public function ajaxProcessAddVideo()
    {
        $idProduct = (int) Tools::getValue('id_product');
        if ($idProduct <= 0) {
            $this->jsonOut(array('ok' => false, 'error' => $this->l('Produit inconnu.')));
        }

        $mode = (string) Tools::getValue('mode');
        $title = trim((string) Tools::getValue('title'));

        $video = new CplVideo();
        $video->id_product = $idProduct;
        $video->id_shop = (int) $this->context->shop->id;
        $video->position = CplVideo::maxPosition($idProduct, (int) $this->context->shop->id) + 1;
        $video->active = 1;

        if ($mode === 'youtube') {
            $ytId = CplVideo::parseYoutubeId(Tools::getValue('url'));
            if ($ytId === '') {
                $this->jsonOut(array('ok' => false, 'error' => $this->l('URL YouTube non reconnue. Formats acceptés : youtube.com/watch?v=..., youtu.be/..., shorts, embed ou ID brut.')));
            }
            $video->type = CplVideo::TYPE_YOUTUBE;
            $video->video_ref = $ytId;
            // Miniature rapatriée en local : zéro requête tierce sur la fiche
            // produit avant le clic. Fail-soft si YouTube est injoignable.
            $video->thumb = CplVideo::fetchYoutubeThumb($ytId);
        } elseif ($mode === 'file') {
            $file = $this->saveUpload('cpl_file', array('mp4', 'webm'), 'vid_' . $idProduct, CplVideo::uploadsDir());
            if ($file === '') {
                $this->jsonOut(array('ok' => false, 'error' => $this->l('Fichier vidéo manquant ou refusé (MP4 ou WebM uniquement, vérifiez aussi la limite d\'envoi de votre serveur).')));
            }
            $video->type = CplVideo::TYPE_FILE;
            $video->video_ref = $file;
            $video->thumb = $this->saveUpload('cpl_poster', array('jpg', 'jpeg', 'png', 'webp'), 'poster_' . $idProduct, CplVideo::thumbsDir());
        } else {
            $this->jsonOut(array('ok' => false, 'error' => $this->l('Action invalide.')));
        }

        foreach (Language::getLanguages(false) as $lang) {
            $video->title[(int) $lang['id_lang']] = $title;
        }

        try {
            $video->add();
        } catch (Exception $e) {
            $this->jsonOut(array('ok' => false, 'error' => $this->l('Enregistrement impossible : ') . $e->getMessage()));
        }

        $this->jsonOut(array('ok' => true, 'rows' => $this->renderRows($idProduct)));
    }

    public function ajaxProcessDeleteVideo()
    {
        $video = $this->loadVideo();
        $idProduct = (int) $video->id_product;
        $video->delete();
        $this->jsonOut(array('ok' => true, 'rows' => $this->renderRows($idProduct)));
    }

    public function ajaxProcessToggleVideo()
    {
        $video = $this->loadVideo();
        $video->active = $video->active ? 0 : 1;
        $video->update();
        $this->jsonOut(array('ok' => true, 'rows' => $this->renderRows((int) $video->id_product)));
    }

    public function ajaxProcessMoveVideo()
    {
        $video = $this->loadVideo();
        CplVideo::move((int) $video->id, Tools::getValue('dir') === 'up');
        $this->jsonOut(array('ok' => true, 'rows' => $this->renderRows((int) $video->id_product)));
    }

    public function ajaxProcessSaveTitle()
    {
        $video = $this->loadVideo();
        $title = trim((string) Tools::getValue('title'));
        foreach (Language::getLanguages(false) as $lang) {
            $video->title[(int) $lang['id_lang']] = $title;
        }
        $video->update();
        $this->jsonOut(array('ok' => true));
    }

    /* ----------------------------------------------------------------------
     * Internes
     * -------------------------------------------------------------------- */

    /**
     * @return CplVideo chargé, ou sortie JSON en erreur
     */
    private function loadVideo()
    {
        $video = new CplVideo((int) Tools::getValue('id_video'));
        if (!Validate::isLoadedObject($video)) {
            $this->jsonOut(array('ok' => false, 'error' => $this->l('Vidéo introuvable.')));
        }

        return $video;
    }

    /**
     * Déplace un upload validé (extension) vers $dir avec un nom sûr.
     *
     * @return string nom de fichier, ou ''
     */
    private function saveUpload($field, array $allowedExt, $prefix, $dir)
    {
        if (empty($_FILES[$field]['tmp_name']) || !empty($_FILES[$field]['error'])
            || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
            return '';
        }
        $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            return '';
        }
        CplVideo::ensureDirs();
        $name = $prefix . '_' . Tools::passwdGen(8) . '.' . $ext;
        if (!@move_uploaded_file($_FILES[$field]['tmp_name'], $dir . $name)) {
            return '';
        }

        return $name;
    }

    private function renderRows($idProduct)
    {
        $module = Module::getInstanceByName('coolplay');

        return $module ? $module->renderProductRows((int) $idProduct) : '';
    }

    private function jsonOut(array $data)
    {
        header('Content-Type: application/json');
        die(json_encode($data));
    }
}
