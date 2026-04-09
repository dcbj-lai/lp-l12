<?php

namespace App\Services;

use OpenAI\Client;
use Mews\Purifier\Facades\Purifier;

class OpenAiHtmlEmailService
{
    public function __construct(
        protected Client $client
    ) {
    }

    public function process(string $html): string
    {
        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');

        $html = $this->stripDocumentTags($html);

        $final = $this->fixItalicsDeterministic($html);

        return Purifier::clean($final, [
            'HTML.Allowed' => implode(',', [
                'p[style]',
                'table[style]',
                'tbody',
                'thead',
                'tr[style]',
                'td[style]',
                'br',
                'strong',
                'b',
                'i',
                'img[src|alt|width|height|style]',
                'a[href|target|style]',
                'h1[style]',
                'h2[style]',
                'h3[style]',
                'h4[style]'
            ]),
            'CSS.AllowedProperties' => [
                'color',
                'background-color',
                'font-size',
                'font-weight',
                'text-align',
                'padding',
                'margin',
                'width',
                'height'
            ],
            'CSS.AllowTricky' => true,
            'CSS.Trusted' => true,
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => false,
        ]);
    }

    /**
     * Deterministic italic fix (NO AI dependency)
     */
    private function fixItalicsDeterministic(string $html): string
    {
        return preg_replace_callback(
            '/<p([^>]*)style="([^"]*)"([^>]*)>(.*?)<\/p>/is',
            function ($matches) {

                $before = $matches[1];
                $style = $matches[2];
                $after = $matches[3];
                $content = $matches[4];

                // remove ONLY font-style: italic
                $cleanStyle = preg_replace('/font-style\s*:\s*italic;?/i', '', $style);

                // normalize spacing
                $cleanStyle = trim(preg_replace('/\s+/', ' ', $cleanStyle));

                $styleAttr = $cleanStyle ? ' style="' . $cleanStyle . '"' : '';

                return "<p{$before}{$styleAttr}{$after}><i>{$content}</i></p>";
            },
            $html
        );
    }
    private function stripDocumentTags(string $html): string
    {
        // Remove DOCTYPE
        $html = preg_replace('/<!DOCTYPE.*?>/i', '', $html);

        // Extract body content if exists
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches)) {
            return $matches[1];
        }

        return $html;
    }
}
