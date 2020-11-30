<?php /*
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
 */ /*
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
/** @noinspection PhpUndefinedFieldInspection */

namespace Andreag\Epub\Model;

use DOMElement;
use DOMText;
use DOMXPath;
use SimpleXMLElement;
use tidy;
use function count;
use function dom_import_simplexml;
use function simplexml_import_dom;
use function simplexml_load_file;
use function str_replace;
use function trim;

/**
 * Class HtmlNormalizer
 */
class HtmlNormalizer
{
    /** @var string */
    protected string $inputFile;

    /** @var string */
    protected string $outputFile;

    /**
     * HtmlNormalizer constructor.
     *
     * @param string $inputFile
     * @param string $outputFile
     */
    public function __construct(string $inputFile, string $outputFile)
    {
        $this->inputFile = $inputFile;
        $this->outputFile = $outputFile;
    }

    /**
     * From input file.
     *
     * @param string $inputFile
     * @param string $outputFile
     *
     * @return HtmlNormalizer
     */
    public static function fromInputOutputFile(string $inputFile, string $outputFile): HtmlNormalizer
    {
        return new static($inputFile, $outputFile);
    }

    /**
     * Normalize.
     *
     * @return int
     */
    public function normalize(): int
    {
        $html = simplexml_load_file($this->inputFile);
        $this->removeBaseFont($html);
        $this->removeStyle($html);
        $this->removeHeadings($html);
        $this->removeBaseDiv($html);
        $this->tableToH1($html);
        $this->simplifyH1Divs($html);
        $html = $this->simplifyDivs($html);
        $html = $this->removeEmptyDiv($html);
        $html = $this->replaceItalic($html);
        $html = $this->replaceBold($html);
        $html = $this->replaceJustify($html);
        $html = $this->specialChars($html);

        $this->savePrettyPrint($html);

        return 0;
    }

    /**
     * Remove base font.
     *
     * @param SimpleXMLElement $html
     */
    private function removeBaseFont(SimpleXMLElement $html)
    {
        unset($html->head->basefont);
    }

    /**
     * RemoveStyle.
     *
     * @param SimpleXMLElement $html
     */
    protected function removeStyle(SimpleXMLElement $html)
    {
        unset($html->head->style);
    }

    /**
     * removeHeadings.
     *
     * @param SimpleXMLElement $html
     */
    private function removeHeadings(SimpleXMLElement $html)
    {
        unset($html->body->a[0]);
        unset($html->body->h1[0]);
        unset($html->body->div[0]);
        unset($html->body->br);
    }

    /**
     * Remove empty div span.
     *
     * @param SimpleXMLElement $html
     */
    private function removeBaseDiv(SimpleXMLElement $html)
    {
        unset($html->body->div[0]->span[0]->div->br);
        $allDivs = $html->body->div[0]->span[0]->div;
        $toDom = dom_import_simplexml($html->body);
        foreach ($allDivs[0] as $el) {
            $elNode = dom_import_simplexml(clone $el);
            $toDom->appendChild($elNode);
        }
        unset($html->body->div[0]);
    }

    /**
     * Save pretty print.
     *
     * @param SimpleXMLElement $html
     */
    protected function savePrettyPrint(SimpleXMLElement $html): void
    {
        $tidy = new tidy();
        $config = [
            'indent' => true,
            'doctype' => 'html5',
            'wrap' => 200
        ];
        $tidy->parseString($html->asXML(), $config, 'utf8');

        $tidy = $this->postProcess((string)$tidy);

        file_put_contents($this->outputFile, (string)$tidy);
    }

    /**
     * Table to h1.
     *
     * @param SimpleXMLElement $html
     */
    private function tableToH1(SimpleXMLElement $html)
    {
        $allDivs = $html->body->div;
        foreach ($allDivs as $div) {
            if (!count($div->table)) {
                continue;
            }
            if ($div->table['bgcolor']) {
                $title = $div->table->tr->td->h1;
                $h1 = $div->addChild('h1');
                $h1[0] = $title;
                unset($div->table);
            }
        }
    }

    /**
     * Simplify divs.
     *
     * @param SimpleXMLElement $html
     */
    private function simplifyH1Divs(SimpleXMLElement $html)
    {
        while (true) {
            $changed = false;
            foreach ($html->body->div as $div) {
                if (count($div->children()) === 1 && count($div->h1) === 1) {
                    $dom = dom_import_simplexml($div);
                    $h1 = dom_import_simplexml($div->h1);
                    $dom->parentNode->replaceChild($h1, $dom);
                    $changed = true;
                }
            }
            if (!$changed) {
                break;
            }
        }
    }

    /**
     * Simplify divs.
     *
     * @param SimpleXMLElement $html
     *
     * @return SimpleXMLElement
     */
    private function simplifyDivs(SimpleXMLElement $html)
    {
        $dom = dom_import_simplexml($html);
        /** @var DOMElement $body */
        $body = $dom->getElementsByTagName('body')->item(0);
        foreach ($body->childNodes as $divEl) {
            /** @var DOMElement $divEl */
            if (!($divEl instanceof DOMElement) || $divEl->nodeName !== 'div') {
                continue;
            }
            if (!$divEl->childNodes) {
                $divEl->parentNode->removeChild($divEl);
            }
            foreach ($divEl->childNodes as $divChild) {
                $body->insertBefore(clone $divChild, $divEl);
            }
            $divEl->parentNode->removeChild($divEl);
        }

        return simplexml_import_dom($dom);
    }

    /**
     * removeEmptyDiv.
     *
     * @param SimpleXMLElement $html
     *
     * @return SimpleXMLElement
     */
    private function removeEmptyDiv(SimpleXMLElement $html): SimpleXMLElement
    {
        $dom = dom_import_simplexml($html);
        $divs = $dom->getElementsByTagName('div');

        foreach ($divs as $div) {
            /** @var DOMElement $div */
            if (trim($div->textContent) === '') {
                $div->parentNode->removeChild($div);
            }
        }
        while (true) {
            $found = false;
            foreach ($divs as $div) {
                /** @var DOMElement $div */
                $first = $div->childNodes[0];
                if ((($first instanceof DOMText) && trim($first->textContent)) ||
                    (($first instanceof DOMElement) && $first->nodeName === 'span')) {
                    if ($div->parentNode->nodeName === 'li') {
                        foreach ($div->childNodes as $c) {
                            $div->parentNode->appendChild(clone $c);
                        }
                        $div->parentNode->removeChild($div);
                    } else {
                        $p = $div->ownerDocument->createElement('p');
                        foreach ($div->childNodes as $c) {
                            $p->appendChild(clone $c);
                        }

                        $div->parentNode->insertBefore($p, $div);
                        $div->parentNode->removeChild($div);
                    }
                    $found = true;
                }
            }
            if (!$found) {
                break;
            }
        }

        return simplexml_import_dom($dom);
    }

    /**
     * Special Chars.
     *
     * @param SimpleXMLElement $html
     *
     * @return SimpleXMLElement
     */
    private function specialChars(SimpleXMLElement $html): SimpleXMLElement
    {
        $dom = dom_import_simplexml($html);
        $xpath = new DOMXPath($dom->ownerDocument);
        $textNodes = $xpath->query('//text()');
        foreach ($textNodes as $textNode) {
            /** @var DOMText $textNode */
            $textNode->textContent = str_replace('ù', '§', $textNode->textContent);
        }

        return simplexml_import_dom($dom);
    }

    /**
     * Replace italic.
     *
     * @param SimpleXMLElement $html
     *
     * @return SimpleXMLElement
     */
    protected function replaceItalic(SimpleXMLElement $html): SimpleXMLElement
    {
        return $this->tagToEl('span', $html, 'font-style: italic;', 'em');
    }

    /**
     * Replace bold.
     *
     * @param SimpleXMLElement $html
     *
     * @return SimpleXMLElement
     */
    protected function replaceBold(SimpleXMLElement $html): SimpleXMLElement
    {
        $rv = $this->tagToEl('span', $html, 'font-weight: bold;', 'strong');
        $rv = $this->tagToEl('b', $rv, null, 'strong');

        return $rv;
    }

    /**
     * @param SimpleXMLElement $html
     *
     * @return SimpleXMLElement
     */
    private function replaceJustify(SimpleXMLElement $html): SimpleXMLElement
    {
        return $this->tagToEl('div', $html, 'text-align:justify;', 'p');
    }

    /**
     * spanToEl1.
     *
     * @param string $originTagName
     * @param SimpleXMLElement $html
     * @param string|null $style
     * @param string $targetElementName
     *
     * @return SimpleXMLElement
     */
    private function tagToEl(
        string $originTagName,
        SimpleXMLElement $html,
        ?string $style,
        string $targetElementName
    ): SimpleXMLElement {
        $dom = dom_import_simplexml($html);
        while (true) {
            $nodeList = $dom->getElementsByTagName($originTagName);
            $found = false;
            foreach ($nodeList as $span) {
                /** @var DOMElement $span */
                if ($style === null || ($span->hasAttribute('style') && $span->getAttribute('style') === $style)) {
                    $em = $span->ownerDocument->createElement($targetElementName);
                    foreach ($span->childNodes as $c) {
                        $em->appendChild(clone $c);
                    }

                    $span->parentNode->insertBefore($em, $span);
                    $span->parentNode->removeChild($span);
                    $found = true;
                }
            }
            if (!$found) {
                break;
            }
        }

        return simplexml_import_dom($dom);
    }

    /**
     * Reduce li.
     *
     * @param array $liHtml
     *
     * @return string
     */
    public function reduceLi(array $liHtml): string
    {
        return sprintf("\n      <li>%s</li>", ucfirst(trim($liHtml[1])));
    }

    /**
     * Post process.
     *
     * @param string $html
     *
     * @return string
     */
    private function postProcess(string $html): string
    {
        return preg_replace_callback('|\s*<li>\s*\n\s*<p>\n(.*)\n\s*</p>\n\s*</li>|', [$this, 'reduceLi'], $html);
    }
}
