<?php

namespace App\Services;

class HtmlPersonalizer
{
    public function process(string $html, array $data): string
    {
        foreach ($data as $key => $value) {
            $html = str_replace('{{' . $key . '}}', $value, $html);
        }

        return $html;
    }
}
