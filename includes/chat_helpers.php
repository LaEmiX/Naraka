<?php

declare(strict_types=1);

if (!function_exists('format_chat_text')) {
    function format_chat_text(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        $text = preg_replace('/\[(.*?)\]/', '&lt;$1&gt;', $text);
        $text = preg_replace('/\&lt;(.*?)\&gt;/', '<span class="chat-action-text">&lt;$1&gt;</span>', $text);

        return nl2br((string) $text);
    }
}

if (!function_exists('render_chat_bbcode')) {
    function render_chat_bbcode(string $value): string
    {
        $text = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        $text = preg_replace('/\[b\](.*?)\[\/b\]/is', '<strong>$1</strong>', $text);
        $text = preg_replace('/\[u\](.*?)\[\/u\]/is', '<u>$1</u>', $text);
        $text = preg_replace('/\[i\](.*?)\[\/i\]/is', '<em>$1</em>', $text);
        $text = preg_replace('/\[center\](.*?)\[\/center\]/is', '<div style="text-align:center;">$1</div>', $text);
        $text = preg_replace('/\[left\](.*?)\[\/left\]/is', '<div style="text-align:left;">$1</div>', $text);
        $text = preg_replace('/\[right\](.*?)\[\/right\]/is', '<div style="text-align:right;">$1</div>', $text);
        $text = preg_replace('/\[justify\](.*?)\[\/justify\]/is', '<div style="text-align:justify;">$1</div>', $text);
        $text = preg_replace('/\[color=([#a-zA-Z0-9]+)\](.*?)\[\/color\]/is', '<span style="color:$1;">$2</span>', $text);

        return nl2br((string) $text);
    }
}

// by LaEmiX