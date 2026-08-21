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

/**
 * Une vidéo attachée à un produit : YouTube (référence = ID 11 caractères)
 * ou fichier hébergé sur la boutique (référence = nom de fichier dans uploads/).
 * La miniature est toujours un fichier local (uploads/thumbs/) pour que la
 * fiche produit ne fasse AUCUNE requête tierce avant le clic.
 */
class CplVideo extends ObjectModel
{
    const TYPE_YOUTUBE = 'youtube';
    const TYPE_FILE = 'file';

    public $id_product;
    public $id_shop;
    public $type;
    public $video_ref;
    public $thumb;
    public $position;
    public $active;
    public $date_add;
    public $date_upd;
    /** @var string|string[] multilang */
    public $title;

    public static $definition = array(
        'table' => 'cpl_video',
        'primary' => 'id_cpl_video',
        'multilang' => true,
        'fields' => array(
            'id_product' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_shop'    => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'type'       => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 16, 'required' => true),
            'video_ref'  => array('type' => self::TYPE_STRING, 'size' => 255, 'required' => true),
            'thumb'      => array('type' => self::TYPE_STRING, 'size' => 255),
            'position'   => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'active'     => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'date_add'   => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd'   => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            // lang
            'title'      => array('type' => self::TYPE_STRING, 'lang' => true, 'size' => 255),
        ),
    );

    /* ----------------------------------------------------------------------
     * Requêtes
     * -------------------------------------------------------------------- */

    /**
     * @return array lignes DB (avec title dans la langue demandée)
     */
    public static function getByProduct($idProduct, $idShop, $idLang, $activeOnly = false)
    {
        $sql = 'SELECT v.*, vl.title
            FROM `' . _DB_PREFIX_ . 'cpl_video` v
            LEFT JOIN `' . _DB_PREFIX_ . 'cpl_video_lang` vl
                ON (vl.id_cpl_video = v.id_cpl_video AND vl.id_lang = ' . (int) $idLang . ')
            WHERE v.id_product = ' . (int) $idProduct . ' AND v.id_shop = ' . (int) $idShop
            . ($activeOnly ? ' AND v.active = 1' : '')
            . ' ORDER BY v.position ASC, v.id_cpl_video ASC';

        $rows = Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : array();
    }

    public static function maxPosition($idProduct, $idShop)
    {
        return (int) Db::getInstance()->getValue(
            'SELECT MAX(position) FROM `' . _DB_PREFIX_ . 'cpl_video`
             WHERE id_product = ' . (int) $idProduct . ' AND id_shop = ' . (int) $idShop
        );
    }

    /**
     * Monte ou descend la vidéo d'un cran (échange de positions avec la voisine).
     */
    public static function move($id, $up)
    {
        $video = new self((int) $id);
        if (!Validate::isLoadedObject($video)) {
            return false;
        }
        $op = $up ? '<' : '>';
        $order = $up ? 'DESC' : 'ASC';
        $neighbor = Db::getInstance()->getRow(
            'SELECT id_cpl_video, position FROM `' . _DB_PREFIX_ . 'cpl_video`
             WHERE id_product = ' . (int) $video->id_product . ' AND id_shop = ' . (int) $video->id_shop . '
               AND (position ' . $op . ' ' . (int) $video->position . '
                    OR (position = ' . (int) $video->position . ' AND id_cpl_video ' . $op . ' ' . (int) $video->id . '))
             ORDER BY position ' . $order . ', id_cpl_video ' . $order
        );
        if (!$neighbor) {
            return true; // déjà en bout de liste
        }
        Db::getInstance()->update('cpl_video', array('position' => (int) $neighbor['position']), 'id_cpl_video = ' . (int) $video->id);
        Db::getInstance()->update('cpl_video', array('position' => (int) $video->position), 'id_cpl_video = ' . (int) $neighbor['id_cpl_video']);

        return true;
    }

    public static function deleteByProduct($idProduct)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT id_cpl_video FROM `' . _DB_PREFIX_ . 'cpl_video` WHERE id_product = ' . (int) $idProduct
        );
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $video = new self((int) $row['id_cpl_video']);
                if (Validate::isLoadedObject($video)) {
                    $video->delete();
                }
            }
        }

        return true;
    }

    /**
     * Supprime aussi les fichiers locaux (miniature + vidéo hébergée).
     */
    public function delete()
    {
        if ($this->thumb !== '') {
            @unlink(self::thumbsDir() . basename($this->thumb));
        }
        if ($this->type === self::TYPE_FILE && $this->video_ref !== '') {
            @unlink(self::uploadsDir() . basename($this->video_ref));
        }

        return parent::delete();
    }

    /* ----------------------------------------------------------------------
     * YouTube
     * -------------------------------------------------------------------- */

    /**
     * Extrait l'ID vidéo (11 caractères) depuis toutes les formes d'URL
     * YouTube courantes, ou depuis un ID brut.
     *
     * @return string '' si non reconnu
     */
    public static function parseYoutubeId($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }
        if (preg_match('~^[A-Za-z0-9_-]{11}$~', $url)) {
            return $url;
        }
        if (preg_match('~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return $m[1];
        }

        return '';
    }

    /**
     * Récupère la miniature YouTube côté serveur et la stocke en local :
     * la fiche produit ne fait ainsi aucune requête vers YouTube avant le clic
     * (performance + RGPD). maxresdefault d'abord, hqdefault en repli.
     *
     * @return string nom de fichier local, ou '' (le front repliera sur i.ytimg.com)
     */
    public static function fetchYoutubeThumb($ytId)
    {
        foreach (array('maxresdefault', 'hqdefault') as $quality) {
            $data = self::httpGet('https://i.ytimg.com/vi/' . rawurlencode($ytId) . '/' . $quality . '.jpg');
            // Signature JPEG + taille plancher (élimine les placeholders/erreurs).
            if ($data !== '' && strncmp($data, "\xFF\xD8", 2) === 0 && strlen($data) > 1000) {
                self::ensureDirs();
                $name = 'yt_' . preg_replace('/[^A-Za-z0-9_-]/', '', $ytId) . '_' . Tools::passwdGen(6) . '.jpg';
                if (@file_put_contents(self::thumbsDir() . $name, $data)) {
                    return $name;
                }
                return '';
            }
        }

        return '';
    }

    private static function httpGet($url)
    {
        if (!function_exists('curl_init')) {
            $body = @Tools::file_get_contents($url, false, null, 5);
            return is_string($body) ? $body : '';
        }
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
        ));
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return (is_string($body) && $code >= 200 && $code < 300) ? $body : '';
    }

    /* ----------------------------------------------------------------------
     * Fichiers locaux
     * -------------------------------------------------------------------- */

    public static function uploadsDir()
    {
        return _PS_MODULE_DIR_ . 'coolplay/uploads/';
    }

    public static function thumbsDir()
    {
        return _PS_MODULE_DIR_ . 'coolplay/uploads/thumbs/';
    }

    public static function ensureDirs()
    {
        foreach (array(self::uploadsDir(), self::thumbsDir()) as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            if (!file_exists($dir . 'index.php')) {
                @file_put_contents($dir . 'index.php', "<?php header('Location: ../');\n");
            }
        }
    }

    /**
     * Vide uploads/ et uploads/thumbs/ (désinstallation).
     */
    public static function removeAllFiles()
    {
        foreach (array(self::thumbsDir(), self::uploadsDir()) as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            foreach ((array) @scandir($dir) as $f) {
                if (is_string($f) && preg_match('/\.(jpe?g|png|webp|mp4|webm)$/i', $f)) {
                    @unlink($dir . $f);
                }
            }
        }
    }

    /**
     * URL (relative à la racine boutique) d'un fichier de uploads/.
     */
    public static function uploadUrl($file, $sub = '')
    {
        return __PS_BASE_URI__ . 'modules/coolplay/uploads/' . $sub . basename((string) $file);
    }

    /**
     * URL de miniature d'une ligne DB : locale si disponible, sinon repli
     * i.ytimg.com pour YouTube (cas rare : fetch serveur échoué à l'ajout).
     */
    public static function thumbUrl(array $row)
    {
        if (!empty($row['thumb'])) {
            return self::uploadUrl($row['thumb'], 'thumbs/');
        }
        if ($row['type'] === self::TYPE_YOUTUBE) {
            return 'https://i.ytimg.com/vi/' . $row['video_ref'] . '/hqdefault.jpg';
        }

        return '';
    }
}
