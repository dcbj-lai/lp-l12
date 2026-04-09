<?php

namespace App\Services;

use OpenAI\Client;
use Mews\Purifier\Facades\Purifier;

class OpenAiHtmlEmailService
{

    protected string $backgroundColor = '#ffffff';
    public function __construct(
        protected Client $client
    ) {
    }

    public function setBackgroundColor(string $color): void
    {
        $this->backgroundColor = $color;
    }

    public function process(string $html): string
    {
        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');
        $html = $this->stripBackgroundColors($html);
        $html = $this->stripDocumentTags($html);

        $final = $this->fixItalicsDeterministic($html);
        $final = $this->normalizeParagraphSpacing($final);
        $final = $this->wrapEmailLayout($final);

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

    private function wrapEmailLayout(string $html): string
    {
        $bg = $this->backgroundColor;

        return "
<table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color:{$bg}; padding:20px 0;'>
    <tr>
        <td align='center'>
            <table width='600' cellpadding='0' cellspacing='0' border='0' style='background:#F5F1EC; padding:30px; font-family:Arial, sans-serif; color:#333333;'>
                <tr>
                    <td>
                        {$html}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
";
    }
    private function normalizeParagraphSpacing(string $html): string
    {
        return preg_replace_callback(
            '/<p([^>]*)>/i',
            function ($matches) {

                $attrs = $matches[1] ?? '';

                // Extract existing style if present
                if (preg_match('/style="([^"]*)"/i', $attrs, $styleMatch)) {
                    $style = $styleMatch[1];

                    // Ensure margin
                    if (!str_contains($style, 'margin')) {
                        $style .= '; margin: 0 0 16px 0;';
                    }

                    // Ensure font-size
                    if (!str_contains($style, 'font-size')) {
                        $style .= '; font-size:14px;';
                    }

                    // Ensure line-height
                    if (!str_contains($style, 'line-height')) {
                        $style .= '; line-height:1.6;';
                    }

                    // Replace existing style attribute
                    $attrs = preg_replace(
                        '/style="([^"]*)"/i',
                        'style="' . trim($style) . '"',
                        $attrs
                    );
                } else {
                    // No style at all → add it
                    $attrs .= ' style="margin: 0 0 16px 0; font-size:14px; line-height:1.6;"';
                }

                return "<p{$attrs}>";
            },
            $html
        );
    }

    private function stripBackgroundColors(string $html): string
    {
        // Remove background-color styles
        $html = preg_replace('/background-color\s*:\s*[^;"]+;?/i', '', $html);

        // Remove deprecated bgcolor attributes
        $html = preg_replace('/bgcolor="[^"]*"/i', '', $html);

        return $html;
    }

}
