<?php

namespace App\Support;

/**
 * Lightweight user-agent parsing for visitor analytics: bot detection plus
 * device type, browser and OS families. Not exhaustive by design.
 */
final class UserAgent
{
    private const BOT_PATTERN = '/bot\b|bot\/|crawl|spider|slurp|archiver|facebookexternalhit|facebookcatalog|meta-externalagent'
        . '|embedly|preview|headless|phantomjs|lighthouse|pagespeed|gtmetrix|pingdom|uptime|statuscake|monitor'
        . '|curl\/|wget|python-|httpclient|go-http-client|java\/|okhttp|axios|node-fetch|guzzlehttp|scrapy'
        . '|semrush|ahrefs|mj12|dotbot|petalbot|bytespider|gptbot|claudebot|perplexitybot|ccbot|applebot'
        . '|yandex(bot|images)|baiduspider|google-inspectiontool|whatsapp\/|telegrambot|discordbot|skypeuripreview/i';

    public static function isBot(?string $ua): bool
    {
        $ua = trim((string) $ua);

        return strlen($ua) < 10 || preg_match(self::BOT_PATTERN, $ua) === 1;
    }

    /** @return array{device_type: string, browser: string, os: string} */
    public static function parse(?string $ua): array
    {
        $ua = (string) $ua;

        $device = match (true) {
            (bool) preg_match('/ipad|tablet|kindle|silk|playbook/i', $ua),
            preg_match('/android/i', $ua) && !preg_match('/mobile/i', $ua) => 'tablet',
            (bool) preg_match('/mobi|iphone|ipod|android|windows phone|opera mini/i', $ua) => 'mobile',
            default => 'desktop',
        };

        $browser = match (true) {
            (bool) preg_match('/FBAN|FBAV|FB_IAB/', $ua) => 'Facebook app',
            (bool) preg_match('/Instagram/', $ua) => 'Instagram app',
            (bool) preg_match('/Edg(e|A|iOS)?\//', $ua) => 'Edge',
            (bool) preg_match('/OPR\/|Opera/', $ua) => 'Opera',
            (bool) preg_match('/SamsungBrowser/', $ua) => 'Samsung Internet',
            (bool) preg_match('/UCBrowser/', $ua) => 'UC Browser',
            (bool) preg_match('/YaBrowser/', $ua) => 'Yandex',
            (bool) preg_match('/Firefox|FxiOS/', $ua) => 'Firefox',
            (bool) preg_match('/; wv\)/', $ua) => 'Android WebView',
            (bool) preg_match('/Chrome|CriOS/', $ua) => 'Chrome',
            (bool) preg_match('/Safari/', $ua) => 'Safari',
            default => 'Other',
        };

        $os = match (true) {
            (bool) preg_match('/Windows/', $ua) => 'Windows',
            (bool) preg_match('/Android/', $ua) => 'Android',
            (bool) preg_match('/iPhone|iPad|iPod/', $ua) => 'iOS',
            (bool) preg_match('/Mac OS X|Macintosh/', $ua) => 'macOS',
            (bool) preg_match('/CrOS/', $ua) => 'ChromeOS',
            (bool) preg_match('/Linux/', $ua) => 'Linux',
            default => 'Other',
        };

        return ['device_type' => $device, 'browser' => $browser, 'os' => $os];
    }
}
