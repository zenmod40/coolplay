<?php
/**
 * CoolPlay - Vidéos produits sans impact sur la vitesse, pour PrestaShop
 *
 * @author    ZM40 — Nicolas Michaud (Magic Garden)
 * @copyright 2026 Nicolas Michaud — ZM40 / Magic Garden
 * @license   GPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
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
