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

namespace Andreag\Epub\Command;

use Andreag\Epub\Model\HtmlNormalizer;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function is_readable;

/**
 * Class NormalizeHtml
 */
class NormalizeHtml extends Command
{
    /** @var string */
    const INPUT = 'input';

    /** @var string */
    const OUTPUT = 'output';

    /** @var string */
    protected $defaultName = 'epub:normalize-html';

    /**
     * New instance.
     *
     * @return NormalizeHtml
     */
    public static function newInstance(): NormalizeHtml
    {
        return new self();
    }

    /**
     * Configure command.
     */
    protected function configure()
    {
        $this
            ->addOption(self::INPUT, 'i', InputOption::VALUE_REQUIRED, "Input html file", '')
            ->addOption(self::OUTPUT, 'o', InputOption::VALUE_REQUIRED, "Output html file", '')
            ->setDescription('Normalize html for epub.')
            ->setHelp('This command normalize html for epub.');
    }

    /**
     * Execute.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputFile = $input->getOption(self::INPUT);
        $outputFile = $input->getOption(self::OUTPUT);
        if (!is_readable($inputFile)) {
            throw new InvalidArgumentException(sprintf("Could not find input file %s", $inputFile));
        }
        $htmlNormalizer = HtmlNormalizer::fromInputOutputFile($inputFile, $outputFile);

        return $htmlNormalizer->normalize();
    }
}
