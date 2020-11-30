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

/**
 * Class QuestionsNormalizer
 *
 * @package Andreag\Epub\Model
 */
class QuestionsNormalizer
{
    /** @var string */
    private string $inputFile;

    /**
     * QuestionsNormalizer constructor.
     *
     * @param string $inputFile
     */
    public function __construct(string $inputFile)
    {
        $this->inputFile = $inputFile;
    }

    /**
     * @param string $inputFile
     *
     * @return QuestionsNormalizer
     */
    public static function fromInputFile(string $inputFile): QuestionsNormalizer
    {
        return new self($inputFile);
    }

    /**
     * Normalize.
     *
     * @return string
     */
    public function normalize(): string
    {
        $html = file_get_contents($this->inputFile);

        $html = str_replace('<ol>', '', $html);
        $html = str_replace('</ol>', '', $html);
        $html = str_replace('  <li>', '<li>', $html);
        $html = str_replace('<li>', '<p class="question">', $html);
        $html = str_replace('<br/><i>', "</p>\n  <p class=\"answer\">", $html);
        $html = str_replace('</i></li>', "</p>", $html);

        return $html;
    }
}