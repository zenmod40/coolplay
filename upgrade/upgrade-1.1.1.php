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
 * 1.1.1 : colonne `unavailable`, posée par la reconstruction des miniatures sur
 * une vidéo que YouTube déclare introuvable (vidéo désactivée, plus retentée).
 */
function upgrade_module_1_1_1($module)
{
    $db = Db::getInstance();
    $table = _DB_PREFIX_ . 'cpl_video';
    if ($db->executeS('SHOW COLUMNS FROM `' . $table . '` LIKE \'unavailable\'')) {
        return true;
    }

    return (bool) $db->execute('ALTER TABLE `' . $table . '` ADD `unavailable` TINYINT(1) NOT NULL DEFAULT 0 AFTER `active`');
}
