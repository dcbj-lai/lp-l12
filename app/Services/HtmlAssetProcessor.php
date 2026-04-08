<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;

class HtmlAssetProcessor
{
    public function process(string $html, array $assetMap): string
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();

        $dom->loadHTML(
            mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'),
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $xpath = new DOMXPath($dom);

        // Normalize asset map (case-insensitive)
        $normalizedMap = [];
        foreach ($assetMap as $key => $value) {
            $normalizedMap[strtolower($key)] = $value;
        }

        /*
        |--------------------------------------------------------------------------
        | Handle src attributes
        |--------------------------------------------------------------------------
        */
        foreach ($xpath->query('//*[@src]') as $node) {
            $src = $node->getAttribute('src');

            // Skip already processed URLs
            if ($this->isExternal($src)) {
                continue;
            }

            $filename = strtolower($this->extractFilename($src));

            if (isset($normalizedMap[$filename])) {
                $node->setAttribute('src', $normalizedMap[$filename]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Handle href attributes
        |--------------------------------------------------------------------------
        */
        foreach ($xpath->query('//*[@href]') as $node) {
            $href = $node->getAttribute('href');

            if ($this->isExternal($href)) {
                continue;
            }

            $filename = strtolower($this->extractFilename($href));

            if (isset($normalizedMap[$filename])) {
                $node->setAttribute('href', $normalizedMap[$filename]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Handle inline styles
        |--------------------------------------------------------------------------
        */
        foreach ($xpath->query('//*[@style]') as $node) {
            $style = $node->getAttribute('style');

            $updatedStyle = preg_replace_callback(
                '/url\([\'"]?(.*?)[\'"]?\)/i',
                function ($matches) use ($normalizedMap) {

                    $path = $matches[1];

                    if ($this->isExternal($path)) {
                        return $matches[0];
                    }

                    $filename = strtolower($this->extractFilename($path));

                    if (isset($normalizedMap[$filename])) {
                        return 'url(' . $normalizedMap[$filename] . ')';
                    }

                    return $matches[0];
                },
                $style
            );

            $node->setAttribute('style', $updatedStyle);
        }

        return $dom->saveHTML();
    }

    /**
     * Extract filename from path or URL
     */
    private function extractFilename(string $path): string
    {
        return basename(parse_url($path, PHP_URL_PATH) ?? $path);
    }

    /**
     * Detect external URLs (already processed)
     */
    private function isExternal(string $path): bool
    {
        return str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
            || str_starts_with($path, '//');
    }
}
