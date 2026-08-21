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

$sql = array();

// Vidéos attachées aux produits (YouTube ou fichier hébergé).
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'cpl_video` (
    `id_cpl_video` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_product` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
    `type` VARCHAR(16) NOT NULL DEFAULT \'youtube\',
    `video_ref` VARCHAR(255) NOT NULL DEFAULT \'\',
    `thumb` VARCHAR(255) NOT NULL DEFAULT \'\',
    `position` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_cpl_video`),
    KEY `product_shop` (`id_product`, `id_shop`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

// Titres localisés (utilisés pour l\'accessibilité et le JSON-LD).
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'cpl_video_lang` (
    `id_cpl_video` INT(11) UNSIGNED NOT NULL,
    `id_lang` INT(11) UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL DEFAULT \'\',
    PRIMARY KEY (`id_cpl_video`, `id_lang`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

return $sql;
