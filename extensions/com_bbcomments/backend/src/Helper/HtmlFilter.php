<?php
/**
 * @package   BlackBrixComments (BlackBrix development branch)
 * @copyright Copyright (c)2020-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 * @modified  2026-11-13 BlackBrix: native, minimal HTML sanitizer
 */

namespace BlackBrix\Component\Bbcomments\Administrator\Helper;

defined('_JEXEC') or die;

use DOMDocument;
use DOMElement;
use DOMNode;
use Joomla\CMS\Filter\InputFilter;

/**
 * Narrow, context-independent comment HTML filter.
 *
 * Call at input validation AND before output (frontend, admin and emails).
 * No Joomla user group may bypass this allowlist, including Super Users.
 * Does not mutate already stored comments.
 */
final class HtmlFilter
{
    private const TAGS = ['p', 'br', 'b', 'strong', 'i', 'em', 'u', 'a'];

    public static function filterText(?string $text): string
    {
        $text = $text ?? '';

        if ($text === '') {
            return '';
        }

        // Joomla's HTML filter provides a first security boundary.
        $filter = InputFilter::getInstance(
            self::TAGS,
            ['href'],
            InputFilter::ONLY_ALLOW_DEFINED_TAGS,
            InputFilter::ONLY_ALLOW_DEFINED_ATTRIBUTES,
            1
        );
        $filtered = (string) $filter->clean($text, 'html');

        // Never rely on the first pass alone for URL protocols and attributes.
        // If DOM is unavailable, safely display plain text rather than unsafe HTML.
        if (!class_exists(DOMDocument::class)) {
            return htmlspecialchars(strip_tags($filtered), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $oldErrors = libxml_use_internal_errors(true);
        try {
            $ok = $dom->loadHTML(
                '<?xml encoding="UTF-8"?><!DOCTYPE html><html><body><div id="bb-comments-root">'
                . $filtered . '</div></body></html>',
                LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
            );
            if (!$ok) {
                return htmlspecialchars(strip_tags($filtered), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
            // Avoid getElementById(): the HTML parser need not declare id as an ID type.
            $body = $dom->getElementsByTagName('body')->item(0);
            $root = $body instanceof DOMElement ? $body->getElementsByTagName('div')->item(0) : null;
            if ($root && $root->getAttribute('id') !== 'bb-comments-root') {
                $root = null;
            }
            if (!$root) {
                // Defensive fallback if the HTML parser moved the wrapper.
                return htmlspecialchars(strip_tags($filtered), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
            $out = '';
            foreach ($root->childNodes as $child) {
                $out .= self::renderNode($child);
            }
            return $out;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($oldErrors);
        }
    }

    private static function renderNode(DOMNode $node): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return htmlspecialchars($node->nodeValue ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        if ($node->nodeType !== XML_ELEMENT_NODE || !$node instanceof DOMElement) {
            return '';
        }
        $tag = strtolower($node->tagName);
        // Prevent script/style/template content from leaking into the output.
        if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template', 'noscript'], true)) {
            return '';
        }
        $inner = '';
        foreach ($node->childNodes as $child) {
            $inner .= self::renderNode($child);
        }
        if (!in_array($tag, self::TAGS, true)) {
            return $inner;
        }
        if ($tag === 'br') {
            return '<br>';
        }
        if ($tag === 'a') {
            $href = trim($node->getAttribute('href'));
            if (!self::isSafeLink($href)) {
                return $inner;
            }
            return '<a href="' . htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '" rel="nofollow ugc noopener noreferrer">' . $inner . '</a>';
        }
        return '<' . $tag . '>' . $inner . '</' . $tag . '>';
    }

    private static function isSafeLink(string $url): bool
    {
        // Explicit protocol whitelist, reject controls, spaces and backslashes.
        if (!preg_match('~^https?://~i', $url)
            || preg_match('/[\x00-\x20\x7F]/', $url)
            || str_contains($url, '\\')) {
            return false;
        }
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
