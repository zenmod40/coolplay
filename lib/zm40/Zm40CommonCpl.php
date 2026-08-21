<?php
/**
 * ZM40 Common — brique partagée à tous les modules ZM40.
 *
 * 3 fonctions, toutes fail-silent et anonymes (aucune donnée boutique envoyée) :
 *   - footer()             : footer d'attribution + lien funnel zm40.com (UTM)
 *   - checkUpdate()        : notify-only via l'API publique GitHub Releases (cache 24h)
 *   - modulesFeed()        : bloc « autres modules ZM40 » depuis zm40.com (cache 24h)
 *
 * Principes : aucun phone-home vers un serveur ZM40 pour la version (GitHub public),
 * aucune télémétrie, opt-out global via ZM40_NET_ENABLED, timeout court, cache.
 * Compatible PrestaShop 1.7 → 9.
 *
 * Composant versionné : voir Zm40Common.version. Toute amélioration = bump + re-déploiement
 * identique dans tous les modules (un seul endroit de vérité).
 *
 * @author    ZM40 — Nicolas Michaud (Magic Garden)
 * @copyright 2026 Nicolas Michaud — ZM40 / Magic Garden
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License version 3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

if (class_exists('Zm40CommonCpl', false)) {
    return;
}

class Zm40CommonCpl
{
    const VERSION   = '1.3';
    const SITE      = 'https://zm40.com';
    const FEED_URL  = 'https://zm40.com/feed/modules-v2.json'; // v2 : OS + Pro avec metadata enrichi
    const GH_ORG    = 'zenmod40';
    const HTTP_TIMEOUT = 3;
    const CACHE_TTL = 86400; // 24 h
    const USER_AGENT = 'ZM40-Module-UpdateCheck';

    /**
     * Interrupteur global (partagé par tous les modules ZM40). Activé par défaut.
     * Si OFF → aucun appel réseau (footer seul reste, il n'appelle rien).
     */
    public static function isNetEnabled()
    {
        $v = Configuration::get('ZM40_NET_ENABLED');
        // Non défini = activé par défaut (l'install pose 1 explicitement).
        if ($v === false || $v === null || $v === '') {
            return true;
        }
        return (bool) (int) $v;
    }

    /**
     * URL funnel zm40.com avec UTM (mesure côté ZM40, pas de tracking utilisateur).
     */
    public static function siteUrl($slug, $medium = 'footer', $path = '/')
    {
        $path = (string) $path;
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . $path;
        }
        $sep = (strpos($path, '?') !== false) ? '&' : '?';
        return self::SITE . $path . $sep . 'utm_source=module&utm_medium=' . rawurlencode($medium)
            . '&utm_campaign=' . rawurlencode($slug);
    }

    /**
     * URL du dépôt GitHub public du module (org zenmod40).
     */
    public static function githubUrl($repo)
    {
        return 'https://github.com/' . self::GH_ORG . '/' . $repo;
    }

    /**
     * Ajoute les paramètres UTM à une URL arbitraire (gère un éventuel ? existant).
     */
    public static function withUtm($url, $campaign, $medium = 'ecosystem')
    {
        if ($url === '') {
            return '';
        }
        $q = 'utm_source=module&utm_medium=' . rawurlencode($medium) . '&utm_campaign=' . rawurlencode($campaign);
        $sep = (strpos($url, '?') !== false) ? '&' : '?';
        return $url . $sep . $q;
    }

    /**
     * Footer d'attribution : « {NomModule} v{version} · ZM40 » (ZM40 = lien funnel).
     *
     * @return string HTML
     */
    public static function footer($moduleName, $version, $slug)
    {
        $url = htmlspecialchars(self::siteUrl($slug, 'footer'), ENT_QUOTES, 'UTF-8');
        $a   = '<a href="' . $url . '" target="_blank" rel="noopener">';
        return '<div class="zm40-footer">'
            . htmlspecialchars($moduleName, ENT_QUOTES, 'UTF-8')
            . ' v' . htmlspecialchars($version, ENT_QUOTES, 'UTF-8')
            . ' · ' . $a . 'ZM40</a>'
            . ' · ' . $a . 'découvrir nos modules</a>'
            . '</div>';
    }

    /**
     * Vérificateur de mise à jour (notify-only) via l'API publique GitHub Releases.
     * Cache 24 h (timestamp + dernière version connue en Configuration). Fail-silent.
     *
     * @param string $repo            Nom du repo GitHub (= slug), ex: 'coolstats'
     * @param string $currentVersion  Version installée (ex: '1.0.2')
     * @return array{available:bool,latest:string,url:string}|null  null si désactivé / inconnu
     */
    public static function checkUpdate($repo, $currentVersion)
    {
        if (!self::isNetEnabled()) {
            return null;
        }

        $key      = strtoupper(preg_replace('/[^a-z0-9]/i', '', $repo));
        $lastKey  = 'ZM40_LASTCHECK_' . $key;
        $latestKey = 'ZM40_LATEST_' . $key;

        $now    = time();
        $last   = (int) Configuration::get($lastKey);
        $latest = (string) Configuration::get($latestKey);

        // Hors cache → on interroge GitHub (throttle même en cas d'échec).
        if (($now - $last) >= self::CACHE_TTL || $latest === '') {
            $url  = 'https://api.github.com/repos/' . self::GH_ORG . '/' . rawurlencode($repo) . '/releases/latest';
            $body = self::httpGet($url, array('Accept: application/vnd.github+json'));
            if ($body !== '') {
                $json = json_decode($body, true);
                if (is_array($json) && !empty($json['tag_name'])) {
                    $latest = (string) $json['tag_name'];
                    Configuration::updateValue($latestKey, $latest);
                }
            }
            Configuration::updateValue($lastKey, $now); // throttle quoi qu'il arrive
        }

        if ($latest === '') {
            return null;
        }

        $cmp = version_compare(ltrim($latest, 'vV'), ltrim((string) $currentVersion, 'vV'));
        return array(
            'available' => $cmp > 0,
            'latest'    => $latest,
            'url'       => 'https://github.com/' . self::GH_ORG . '/' . $repo . '/releases/latest',
        );
    }

    /**
     * Force un rafraîchissement du feed au prochain appel (refetch), sans
     * supprimer le cache existant (qui reste un fallback si le refetch échoue).
     * À appeler p.ex. à la sauvegarde de la config = levier manuel de refresh.
     */
    public static function clearFeedCache()
    {
        Configuration::updateValue('ZM40_FEED_CACHE_TS', 0);
    }

    /**
     * Bloc « Autres modules ZM40 » depuis le JSON curé sur zm40.com. Cache 24 h.
     * Exclut le module courant. Fail-silent (retourne [] si injoignable).
     *
     * @param string $excludeSlug
     * @return array  liste de modules [{slug,name,tagline,url,icon,github}, ...]
     */
    public static function modulesFeed($excludeSlug)
    {
        if (!self::isNetEnabled()) {
            return array();
        }

        $now   = time();
        $ts    = (int) Configuration::get('ZM40_FEED_CACHE_TS');
        $cache = (string) Configuration::get('ZM40_FEED_CACHE');

        if (($now - $ts) >= self::CACHE_TTL || $cache === '') {
            $body = self::httpGet(self::FEED_URL);
            if ($body !== '') {
                $cache = $body;
                Configuration::updateValue('ZM40_FEED_CACHE', $cache);
            }
            Configuration::updateValue('ZM40_FEED_CACHE_TS', $now); // throttle
        }

        $data = json_decode($cache, true);
        if (!is_array($data) || empty($data['modules']) || !is_array($data['modules'])) {
            return array();
        }

        $out = array();
        foreach ($data['modules'] as $m) {
            // Slug : tolère 'slug' (spec) ET 'module' (clé utilisée par le feed live).
            $slug = '';
            if (isset($m['slug']) && $m['slug'] !== '') {
                $slug = (string) $m['slug'];
            } elseif (isset($m['module']) && $m['module'] !== '') {
                $slug = (string) $m['module'];
            }
            if ($slug === '' || $slug === $excludeSlug) {
                continue;
            }
            // v1 rétro-compat : 'url'. v2 : 'landing_url' (canonique). Privilégie v2.
            $url    = isset($m['landing_url']) ? (string) $m['landing_url']
                    : (isset($m['url']) ? (string) $m['url'] : '');
            $github = isset($m['github']) && $m['github'] !== null ? (string) $m['github'] : '';

            // Détection module installé : on cherche par technical_name (champ 'module'
            // dans le feed v2), et on fallback sur le slug pour v1.
            $techName = isset($m['module']) ? (string) $m['module'] : $slug;
            $installed = class_exists('Module') ? (bool) Module::isInstalled($techName) : false;

            // Si installé, on construit le lien direct vers sa page de config
            // dans le BO → l'utilisateur peut y aller en 1 clic depuis cet écran
            // (au lieu du bouton "Acheter" qui n'a plus de sens).
            $configureUrl = '';
            if ($installed && class_exists('Context')) {
                try {
                    $ctx = Context::getContext();
                    if ($ctx && isset($ctx->link)) {
                        $configureUrl = $ctx->link->getAdminLink('AdminModules', true, array(), array(
                            'configure' => $techName,
                        ));
                    }
                } catch (Exception $e) {
                    $configureUrl = '';
                }
            }

            // Champs v2 enrichis (avec fallbacks sécurisés pour rétro-compat v1)
            $type          = isset($m['type'])          ? (string) $m['type']          : 'open_source';
            $pricing_model = isset($m['pricing_model']) ? (string) $m['pricing_model'] : 'free';
            $price_from_ht = isset($m['price_from_ht']) ? (int)    $m['price_from_ht'] : null;
            $license       = isset($m['license'])       ? (string) $m['license']       : 'GPL v3';
            $badge         = isset($m['badge'])         ? (string) $m['badge']         : 'Open source';
            $purchase_url  = isset($m['purchase_url']) && $m['purchase_url'] !== null
                           ? self::withUtm((string) $m['purchase_url'], $excludeSlug, 'ecosystem')
                           : null;

            $out[] = array(
                'slug'          => $slug,
                'module'        => $techName,
                'name'          => isset($m['name']) ? (string) $m['name'] : $slug,
                'tagline'       => isset($m['tagline']) ? (string) $m['tagline'] : '',
                'url'           => self::withUtm($url, $excludeSlug, 'ecosystem'),
                'icon'          => isset($m['icon']) ? (string) $m['icon'] : '',
                'github'        => $github !== '' ? self::withUtm($github, $excludeSlug, 'ecosystem') : '',
                'installed'     => $installed,
                'configure_url' => $configureUrl,
                // === v2 ===
                'type'          => $type,
                'pricing_model' => $pricing_model,
                'price_from_ht' => $price_from_ht,
                'license'       => $license,
                'badge'         => $badge,
                'purchase_url'  => $purchase_url,
                'is_os'         => ($type === 'open_source'),
                'is_pro'        => ($type === 'pro'),
                'is_sub'        => ($pricing_model === 'subscription'),
            );
        }
        return $out;
    }

    /**
     * GET HTTP anonyme, timeout court, fail-silent. cURL puis fallback stream.
     * Retourne le corps (string) ou '' en cas d'échec.
     */
    private static function httpGet($url, array $extraHeaders = array())
    {
        $headers = array_merge(array('User-Agent: ' . self::USER_AGENT), $extraHeaders);

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_TIMEOUT        => self::HTTP_TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => self::HTTP_TIMEOUT,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_USERAGENT      => self::USER_AGENT,
            ));
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (is_string($body) && $body !== '' && $code >= 200 && $code < 300) {
                return $body;
            }
            return '';
        }

        // Fallback sans cURL
        $ctx = stream_context_create(array(
            'http' => array(
                'method'        => 'GET',
                'timeout'       => self::HTTP_TIMEOUT,
                'header'        => implode("\r\n", $headers),
                'ignore_errors' => true,
            ),
            'ssl' => array('verify_peer' => true, 'verify_peer_name' => true),
        ));
        $body = @Tools::file_get_contents($url, false, $ctx);
        return is_string($body) ? $body : '';
    }
}
