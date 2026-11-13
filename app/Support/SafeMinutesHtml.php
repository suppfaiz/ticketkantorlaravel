<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

class SafeMinutesHtml
{
    private const TAGS = [
        'p', 'br', 'div', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del',
        'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'a', 'blockquote',
        'code', 'pre', 'hr', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'span',
    ];

    public static function render(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $source = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        try {
            $source->loadHTML('<?xml encoding="UTF-8"><div id="minutes-root">'.$html.'</div>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $root = $source->getElementById('minutes-root');
        if (! $root) {
            return e($html);
        }

        $safe = new DOMDocument('1.0', 'UTF-8');
        $container = $safe->createElement('div');
        $safe->appendChild($container);

        foreach ($root->childNodes as $child) {
            self::copySafeNode($child, $container, $safe);
        }

        $result = '';
        foreach ($container->childNodes as $child) {
            $result .= $safe->saveHTML($child);
        }

        return $result;
    }

    private static function copySafeNode(DOMNode $node, DOMNode $parent, DOMDocument $document): void
    {
        if ($node instanceof DOMText) {
            $parent->appendChild($document->createTextNode($node->nodeValue));

            return;
        }

        if (! $node instanceof DOMElement) {
            return;
        }

        $tag = strtolower($node->tagName);
        if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math'], true)) {
            return;
        }

        $target = $parent;
        if (in_array($tag, self::TAGS, true)) {
            $target = $document->createElement($tag);

            if ($tag === 'a') {
                $href = trim($node->getAttribute('href'));
                if (preg_match('~\Ahttps?://~i', $href) && filter_var($href, FILTER_VALIDATE_URL)) {
                    $target->setAttribute('href', $href);
                    $target->setAttribute('rel', 'noopener noreferrer');
                }
            }

            $parent->appendChild($target);
        }

        foreach ($node->childNodes as $child) {
            self::copySafeNode($child, $target, $document);
        }
    }
}
