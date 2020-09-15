<?php
/*
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

use tidy;

/**
 * Class SimpleNormalizer
 *
 * @package Andreag\Epub\Model
 */
class SimpleNormalizer
{
    /** @var string */
    private $inputFile;

    /** @var string */
    private $outputFile;

    /**
     * SimpleNormalizer constructor.
     *
     * @param $inputFile
     * @param $outputFile
     */
    public function __construct($inputFile, $outputFile)
    {
        $this->inputFile = $inputFile;
        $this->outputFile = $outputFile;
    }

    /**
     * @param string|null $inputFile
     * @param string|null $outputFile
     *
     * @return SimpleNormalizer
     */
    public static function fromInputOutputFile(?string $inputFile, ?string $outputFile): SimpleNormalizer
    {
        return new self($inputFile, $outputFile);
    }

    /**
     * Normalize.
     */
    public function normalize(): int
    {
        $html = file_get_contents($this->inputFile);

        $html = str_replace(' style="text-align:justify;"', '', $html);
        $html = str_replace('<li><div>', '<li>', $html);
        $html = preg_replace('|<a name="[^"]+"/>|', '', $html);
        $html = str_replace('</div></li>', '</li>', $html);


        $html = str_replace('<div><span>', '', $html);
        $html = str_replace("</span>\n</div>", '', $html);

        $html = str_replace("<div>\n<span><div>", '', $html);
        $html = str_replace("</div></span>\n</div>", '', $html);

        $html = str_replace("<div><br/></div>", '', $html);
        $html = preg_replace('/<span style="font-weight: bold;">([^<]+)<\/span>/', '<b>$1</b>', $html);
        $html = preg_replace('/<span style="font-style: italic;">([^<]+)<\/span>/', '<i>$1</i>', $html);

        $tidy = new tidy();
        $config = [
            'indent' => true,
            'doctype' => 'html5',
            'wrap' => 90
        ];
        $tidy->parseString($html, $config, 'utf8');

        $html = (string)$tidy;
        $html = preg_replace('|<li>\n\s+|', '<li>', $html);
        $html = preg_replace('|\n\s*</li>|', '</li>', $html);

        $html = preg_replace('|<div>\n\s+|', '<p>', $html);
        $html = preg_replace('|\n\s*</div>|', '</p>', $html);

        $html = preg_replace('|<h(\d)>\n\s+|', '<h$1>', $html);
        $html = preg_replace('|\n\s+</h(\d)>|', '</h$1>', $html);
        $html = preg_replace('|([\w,:)(])&nbsp;([\w,:)(])|', '$1 $2', $html);

        $html = str_replace('&nbsp;=&nbsp;', ' = ', $html);
        $html = str_replace('&nbsp;<', '<', $html);
        $html = str_replace('&nbsp; ', ' ', $html);
        $html = str_replace('>&nbsp;', '>', $html);
        $html = str_replace('> ', '>', $html);
        $html = str_replace('>&nbsp;', '>', $html);
        $html = str_replace('<hr>', '<hr/>', $html);
        $html = str_replace('<h1>', "<hr/>\n<h1>", $html);

        file_put_contents($this->outputFile, $html);

        return 0;
    }
}
