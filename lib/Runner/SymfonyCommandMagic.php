<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2014 Markus Fischer <markus@fischer.name>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Mfn\CakePHP2\MagicProperty\Runner;

use Mfn\CakePHP2\MagicProperty\Logger\Logger;
use Mfn\CakePHP2\MagicProperty\Logger\SymfonyConsoleOutput;
use Mfn\CakePHP2\MagicProperty\MagicProperty;
use PhpParser\Lexer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SymfonyCommandMagic extends Command {

  protected function configure() {
    $this
      ->setName('magic')
      ->setDescription('Run the CakePHP2 magic property documentor')
      ->addOption('dry-run', 'd', InputOption::VALUE_NONE,
        'Run without actually modifying files')
      ->addOption('remove', NULL, InputOption::VALUE_NONE,
        'Remove non-existent properties. Warning: this also removes unknown properties!')
      ->addOption('config', NULL, InputOption::VALUE_REQUIRED,
        'Use alternative configuration')
      ->addArgument('sources', InputArgument::IS_ARRAY | InputArgument::REQUIRED,
        'Files/directories to scan');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    $logger = new SymfonyConsoleOutput($output);
    $logger->setReportingLevel(Logger::INFO);
    if ($input->getOption('quiet')) {
      $logger->setReportingLevel(Logger::ERROR);
    }

    $project = new MagicProperty($logger);
    $project->setRemoveUnknownProperties($input->getOption('remove'));
    $project->setDryRun($input->getOption('dry-run'));

    if (NULL !== $config = $input->getOption('config')) {
      $project->setConfigurationFromFile($config);
    }

    foreach ($input->getArgument('sources') as $source) {
      $project->addSource($source);
    }

    $sourcesChanged = $project->applyMagic();

    return $sourcesChanged === 0 ? 0 : 1;
  }
}
