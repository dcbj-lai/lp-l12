<?php

namespace App\Services;

use Mews\Purifier\Facades\Purifier;

class HtmlSanitizer
{

    public function clean(string $html): string
    {
        // ❗ Remove <style> tags completely (prevents purifier crash)
        $html = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $html);

        return Purifier::clean($html, [
            'HTML.Allowed' => implode(',', [
                'div',
                'span',
                'p',
                'br',
                'b',
                'strong',
                'i',
                'ul',
                'ol',
                'li',
                'table',
                'thead',
                'tbody',
                'tr',
                'td',
                'th',
                'img[src|alt|width|height|style]',
                'a[href|target|style]',
                'h1',
                'h2',
                'h3',
                'h4',
                'h5',
                'h6',
            ]),
            'CSS.AllowedProperties' => [
                'color',
                'background',
                'background-color',
                'font',
                'font-size',
                'font-weight',
                'font-style',
                'text-align',
                'padding',
                'margin',
                'border',
                'width',
                'height'
            ],
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => false,
        ]);
    }
}
