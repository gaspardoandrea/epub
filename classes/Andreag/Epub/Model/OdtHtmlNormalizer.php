<?php
/**
 * Copyright (C) 2020 Webformat S.r.l.
 * http://www.webformat.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Andreag\Epub\Model;

use DOMDocument;
use DOMElement;
use SimpleXMLElement;
use function basename;
use function dom_import_simplexml;
use function preg_replace;
use function simplexml_import_dom;
use function trim;

/**
 * Class OdtHtmlNormalizer
 */
class OdtHtmlNormalizer extends HtmlNormalizer
{
    /**
     * Normalize.
     *
     * @return int
     */
    public function normalize()
    {
        $doc = new DOMDocument();
        $doc->loadHTMLFile($this->inputFile);

        $html = simplexml_import_dom($doc);
        $this->removeStyle($html);
        $this->updateTitle($html, basename($this->inputFile));
        $this->removeMeta($html);
        $this->removeStyles($html);
        $this->removeSpans($html);
        $this->removeFonts($html);
        $this->removeEmptyParagraphs($html);
        $this->removeEmptyLinks($html);
        $this->footnotes($html);
        $this->replaceItalic($html);
        $this->replaceBold($html);
        $this->replaceLiDivs($html);
        $this->savePrettyPrint($html);

        return 0;
    }

    /**
     * updateTitle.
     *
     * @param SimpleXMLElement $html
     * @param string $basename
     */
    private function updateTitle(SimpleXMLElement $html, string $basename)
    {
        $html->head->title = preg_replace('|\..*|', '', $basename);
    }

    private function removeMeta(SimpleXMLElement $html)
    {
        $dom = dom_import_simplexml($html);
        /** @var DOMElement $body */
        $body = $dom->getElementsByTagName('body')->item(0);
        $body->removeAttribute('text');
        $body->removeAttribute('link');
        $body->removeAttribute('vlink');
        $body->removeAttribute('dir');
        $body->removeAttribute('style');
        while (true) {
            $found = false;
            foreach ($dom->getElementsByTagName('meta') as $meta) {
                /** @var DOMElement $meta */
                $meta->parentNode->removeChild($meta);
                $found = true;
            }
            if (!$found) {
                break;
            }
        }
        while (true) {
            $found = false;
            foreach ($dom->getElementsByTagName('link') as $meta) {
                /** @var DOMElement $meta */
                $meta->parentNode->removeChild($meta);
                $found = true;
            }
            if (!$found) {
                break;
            }
        }
    }

    private function removeStyles(SimpleXMLElement $html)
    {
        $dom = dom_import_simplexml($html);
        foreach ($dom->getElementsByTagName('p') as $p) {
            /** @var DOMElement $p */
            $this->removeAttrs($p);
        }
        foreach ($dom->getElementsByTagName('h2') as $p) {
            /** @var DOMElement $p */
            $this->removeAttrs($p);
        }
        foreach ($dom->getElementsByTagName('h1') as $p) {
            /** @var DOMElement $p */
            $this->removeAttrs($p);
        }
        foreach ($dom->getElementsByTagName('h3') as $p) {
            /** @var DOMElement $p */
            $this->removeAttrs($p);
        }
        foreach ($dom->getElementsByTagName('h4') as $p) {
            /** @var DOMElement $p */
            $this->removeAttrs($p);
        }
        foreach ($dom->getElementsByTagName('blockquote') as $p) {
            /** @var DOMElement $p */
            $this->removeAttrs($p);
        }
    }

    /**
     * removeAttrs.
     *
     * @param DOMElement $p
     */
    private function removeAttrs(DOMElement $p): void
    {
        $p->removeAttribute('style');
        $p->removeAttribute('align');
        $p->removeAttribute('class');
    }

    /**
     * removeSpans.
     *
     * @param SimpleXMLElement $html
     */
    private function removeSpans(SimpleXMLElement $html)
    {
        $dom = dom_import_simplexml($html);
        foreach ($dom->getElementsByTagName('span') as $span) {
            /** @var $span DOMElement */
            foreach ($span->childNodes as $childNode) {
                $span->parentNode->insertBefore(clone $childNode);
            }
        }
        while (true) {
            $found = false;
            foreach ($dom->getElementsByTagName('span') as $meta) {
                /** @var DOMElement $meta */
                $meta->parentNode->removeChild($meta);
                $found = true;
            }
            if (!$found) {
                break;
            }
        }
    }

    /**
     * removeSpans.
     *
     * @param SimpleXMLElement $html
     */
    private function removeFonts(SimpleXMLElement $html)
    {
        $dom = dom_import_simplexml($html);
        foreach ($dom->getElementsByTagName('font') as $span) {
            /** @var $span DOMElement */
            foreach ($span->childNodes as $childNode) {
                $span->parentNode->insertBefore(clone $childNode);
            }
        }
        while (true) {
            $found = false;
            foreach ($dom->getElementsByTagName('font') as $meta) {
                /** @var DOMElement $meta */
                $meta->parentNode->removeChild($meta);
                $found = true;
            }
            if (!$found) {
                break;
            }
        }
    }

    private function removeEmptyParagraphs(SimpleXMLElement $html)
    {
        $dom = dom_import_simplexml($html);
        while (true) {
            $found = false;
            foreach ($dom->getElementsByTagName('p') as $p) {
                /** @var DOMElement $p */
                if (trim($p->textContent) === '') {
                    $p->parentNode->removeChild($p);
                    $found = true;
                }
            }
            if (!$found) {
                break;
            }
        }
    }

    private function removeEmptyLinks(SimpleXMLElement $html)
    {
        $dom = dom_import_simplexml($html);
        while (true) {
            $found = false;
            foreach ($dom->getElementsByTagName('a') as $p) {
                /** @var DOMElement $p */
                if (trim($p->textContent) === '') {
                    $p->parentNode->removeChild($p);
                    $found = true;
                }
            }
            if (!$found) {
                break;
            }
        }
    }

    private function footnotes(SimpleXMLElement $html)
    {
        $dom = dom_import_simplexml($html);
        $dom->removeAttribute('xmlns:epub');
        $dom->setAttribute('xmlns:epub', 'http://www.idpf.org/2007/ops');
        $dom->removeAttribute('xmlns');
        $dom->setAttribute('xmlns', 'http://www.w3.org/1999/xhtml');
        /** @var DOMElement $head */
        $head = $dom->getElementsByTagName('head')->item(0);
        $head->getElementsByTagName('title')->item(0)->removeAttribute(' xml:lang');
        $link = $dom->ownerDocument->createElement('link');
        $link->setAttribute('rel', 'stylesheet');
        $link->setAttribute('href', '../Styles/Style0001.css');
        $link->setAttribute('type', 'text/css');
        $head->appendChild($link);
        foreach ($dom->getElementsByTagName('a') as $a) {
            /** @var DOMElement $a */
            if ($a->getAttribute('class') === 'sdfootnoteanc') {
                $a->removeAttribute('class');
                $a->removeAttribute('name');
                foreach ($dom->getElementsByTagName('a') as $footA) {
                    /** @var DOMElement $footA */
                    if ('#' . $footA->getAttribute('name') === $a->getAttribute('href')) {
                        $p = $footA->parentNode;
                        $div = $p->parentNode;
                        $aside = $div->ownerDocument->createElement('aside');
                        $aside->setAttribute('epub:type', 'footnote');
                        $aside->setAttribute('id', $footA->getAttribute('name'));
                        $div->parentNode->insertBefore($aside, $div);
                        $footA->parentNode->removeChild($footA);
                        foreach ($div->childNodes as $node) {
                            $aside->appendChild(clone $node);
                        }
                        $div->parentNode->removeChild($div);
                    }
                }
            }
        }
    }

    private function replaceLiDivs(SimpleXMLElement $html)
    {

    }
}
