<?php

namespace App\Support;

/**
 * Works out where a visit came from (channel + referring site) from the
 * Referer header and the landing URL's utm_* / click-id parameters.
 */
final class TrafficSource
{
    /**
     * Host patterns (regex on the bare host) → [canonical host, channel].
     * Order matters: more specific hosts first (gemini.google.com before google).
     */
    private const HOST_RULES = [
        // AI assistants
        '/(^|\.)chatgpt\.com$|(^|\.)openai\.com$/' => ['chatgpt.com', 'ai'],
        '/(^|\.)perplexity\.ai$/' => ['perplexity.ai', 'ai'],
        '/^gemini\.google\.com$|^bard\.google\.com$/' => ['gemini.google.com', 'ai'],
        '/^copilot\.microsoft\.com$/' => ['copilot.microsoft.com', 'ai'],
        '/(^|\.)claude\.ai$/' => ['claude.ai', 'ai'],
        '/(^|\.)deepseek\.com$/' => ['deepseek.com', 'ai'],

        // Webmail
        '/^mail\.google\.com$/' => ['mail.google.com', 'email'],
        '/^outlook\.(live|office|office365)\.com$/' => ['outlook.com', 'email'],
        '/^mail\.yahoo\.com$/' => ['mail.yahoo.com', 'email'],

        // Search engines
        '/(^|\.)google\.[a-z.]+$/' => ['google.com', 'search'],
        '/(^|\.)bing\.com$/' => ['bing.com', 'search'],
        '/(^|\.)duckduckgo\.com$/' => ['duckduckgo.com', 'search'],
        '/(^|\.)yahoo\.[a-z.]+$/' => ['yahoo.com', 'search'],
        '/(^|\.)yandex\.[a-z.]+$|(^|\.)ya\.ru$/' => ['yandex.com', 'search'],
        '/(^|\.)baidu\.com$/' => ['baidu.com', 'search'],
        '/(^|\.)ecosia\.org$/' => ['ecosia.org', 'search'],
        '/^search\.brave\.com$/' => ['search.brave.com', 'search'],
        '/(^|\.)startpage\.com$/' => ['startpage.com', 'search'],
        '/(^|\.)qwant\.com$/' => ['qwant.com', 'search'],
        '/(^|\.)naver\.com$/' => ['naver.com', 'search'],
        '/(^|\.)seznam\.cz$/' => ['seznam.cz', 'search'],
        '/(^|\.)sogou\.com$/' => ['sogou.com', 'search'],

        // Social & messaging
        '/(^|\.)facebook\.com$|(^|\.)fb\.com$|^fb\.me$|(^|\.)messenger\.com$/' => ['facebook.com', 'social'],
        '/(^|\.)instagram\.com$/' => ['instagram.com', 'social'],
        '/^t\.co$|(^|\.)twitter\.com$|(^|\.)x\.com$/' => ['x.com', 'social'],
        '/(^|\.)linkedin\.com$|^lnkd\.in$/' => ['linkedin.com', 'social'],
        '/(^|\.)reddit\.com$|^redd\.it$/' => ['reddit.com', 'social'],
        '/(^|\.)youtube\.com$|^youtu\.be$/' => ['youtube.com', 'social'],
        '/(^|\.)whatsapp\.com$|^wa\.me$/' => ['whatsapp.com', 'social'],
        '/(^|\.)tiktok\.com$/' => ['tiktok.com', 'social'],
        '/(^|\.)pinterest\.[a-z.]+$|^pin\.it$/' => ['pinterest.com', 'social'],
        '/^t\.me$|(^|\.)telegram\.(org|me)$/' => ['telegram.org', 'social'],
        '/(^|\.)quora\.com$/' => ['quora.com', 'social'],
        '/(^|\.)threads\.(net|com)$/' => ['threads.net', 'social'],
        '/(^|\.)snapchat\.com$/' => ['snapchat.com', 'social'],
        '/(^|\.)discord\.(com|gg)$/' => ['discord.com', 'social'],
        '/(^|\.)tumblr\.com$/' => ['tumblr.com', 'social'],
        '/(^|\.)vk\.com$/' => ['vk.com', 'social'],
        '/(^|\.)medium\.com$/' => ['medium.com', 'social'],
    ];

    /** Android apps that send an android-app:// referrer. */
    private const ANDROID_APPS = [
        'com.google.android.gm' => ['mail.google.com', 'email'],
        'com.google.android.googlequicksearchbox' => ['google.com', 'search'],
        'com.facebook.katana' => ['facebook.com', 'social'],
        'com.facebook.orca' => ['facebook.com', 'social'],
        'com.instagram.android' => ['instagram.com', 'social'],
        'com.whatsapp' => ['whatsapp.com', 'social'],
        'org.telegram.messenger' => ['telegram.org', 'social'],
        'com.twitter.android' => ['x.com', 'social'],
        'com.linkedin.android' => ['linkedin.com', 'social'],
        'com.reddit.frontpage' => ['reddit.com', 'social'],
        'com.google.android.youtube' => ['youtube.com', 'social'],
        'com.slack' => ['slack.com', 'social'],
    ];

    /**
     * @param  string|list<?string>|null  $ownHosts  this site's host(s); referrers from them are internal
     * @param  array<string, mixed>  $query  landing URL query parameters
     * @return array{channel: string, referrer_host: ?string, referrer_url: ?string, utm_source: ?string, utm_medium: ?string, utm_campaign: ?string, key: string}
     */
    public static function classify(?string $referrer, string|array|null $ownHosts, array $query = []): array
    {
        $utm = [
            'utm_source' => self::clean($query['utm_source'] ?? null),
            'utm_medium' => self::clean($query['utm_medium'] ?? null),
            'utm_campaign' => self::clean($query['utm_campaign'] ?? null),
        ];

        [$host, $url, $channel] = self::fromReferrer($referrer, (array) $ownHosts);

        // In-app browsers (Facebook, Instagram) often drop the Referer but keep click ids.
        if ($host === null) {
            if (!empty($query['fbclid'])) {
                [$host, $channel] = ['facebook.com', 'social'];
            } elseif (!empty($query['gclid']) || !empty($query['gbraid']) || !empty($query['wbraid'])) {
                [$host, $channel] = ['google.com', 'campaign'];
            } elseif (!empty($query['msclkid'])) {
                [$host, $channel] = ['bing.com', 'campaign'];
            }
        }

        if ($utm['utm_source'] !== null || $utm['utm_campaign'] !== null) {
            $medium = strtolower((string) $utm['utm_medium']);
            $channel = match (true) {
                in_array($medium, ['email', 'e-mail', 'newsletter'], true) => 'email',
                in_array($medium, ['social', 'social-media', 'sm'], true) => 'social',
                in_array($medium, ['cpc', 'ppc', 'paid', 'paidsearch', 'display', 'ads', 'banner'], true) => 'campaign',
                $channel !== null => $channel,
                default => 'campaign',
            };
        }

        $channel ??= 'direct';
        $key = $utm['utm_source'] !== null || $utm['utm_campaign'] !== null
            ? 'utm:' . strtolower(($utm['utm_source'] ?? '') . '|' . ($utm['utm_campaign'] ?? ''))
            : $channel . ':' . ($host ?? '');

        return ['channel' => $channel, 'referrer_host' => $host, 'referrer_url' => $url, 'key' => $key] + $utm;
    }

    /** @return array{0: ?string, 1: ?string, 2: ?string} host, url, channel */
    private static function fromReferrer(?string $referrer, array $ownHosts): array
    {
        $referrer = trim((string) $referrer);
        if ($referrer === '') {
            return [null, null, null];
        }

        if (str_starts_with($referrer, 'android-app://')) {
            $package = strtolower(strtok(substr($referrer, strlen('android-app://')), '/') ?: '');
            if ($package === '') {
                return [null, null, null];
            }
            [$host, $channel] = self::ANDROID_APPS[$package] ?? [$package, 'app'];

            return [$host, 'android-app://' . $package, $channel];
        }

        $parts = parse_url($referrer);
        $rawHost = strtolower($parts['host'] ?? '');
        if ($rawHost === '' || !in_array($parts['scheme'] ?? '', ['http', 'https'], true)) {
            return [null, null, null];
        }

        $bare = preg_replace('/^(www\d?|m|l|lm|mobile|amp)\./', '', $rawHost);
        $own = array_map(fn ($h) => preg_replace('/^www\./', '', strtolower((string) $h)), array_filter($ownHosts));
        if (in_array($bare, $own, true)) {
            return [null, null, null]; // internal navigation, not a source
        }

        // Keep scheme, host and path only: query strings can carry personal data.
        $url = mb_substr($parts['scheme'] . '://' . $rawHost . ($parts['path'] ?? ''), 0, 500);

        foreach (self::HOST_RULES as $pattern => [$canonical, $channel]) {
            if (preg_match($pattern, $bare)) {
                return [$canonical, $url, $channel];
            }
        }

        return [mb_substr($bare, 0, 191), $url, 'referral'];
    }

    private static function clean(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $value = trim(strip_tags($value));

        return $value === '' ? null : mb_substr($value, 0, 150);
    }
}
