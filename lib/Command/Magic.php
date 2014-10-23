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
namespace Mfn\CakePHP2\MagicProperty\Command;

use Mfn\CakePHP2\MagicProperty\ClassTransformer;
use Mfn\CakePHP2\MagicProperty\ClassVisitor;
use Mfn\CakePHP2\MagicProperty\PropertyVisitor;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Magic extends Command {

  protected function configure() {
    $this
      ->setName('magic')
      ->setDescription('Run the CakePHP2 magic property documentor')
      ->addOption('dry-run', 'd', InputOption::VALUE_NONE,
        'Run without actually modifying file')
      ->addOption('remove', NULL, InputOption::VALUE_NONE,
        'Remove non-existent properties. Warning: this also removes unknown properties!')
      ->addArgument('sources', InputArgument::IS_ARRAY,
        'Files/directories to scan');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    # Gather files
    $files = [];
    foreach ($input->getArgument('sources') as $source) {
      if (is_file($source)) {
        $files[] = $source;
        continue;
      }
      if (is_dir($source)) {
        $files = array_merge($files, self::scanDir($source));
      }
    }
    $files = array_unique($files);

    # start processing
    $traverser = new NodeTraverser();
    $classVisitor = new ClassVisitor();
    $traverser->addVisitor($classVisitor);

    # Store which class is in which file and whose parent it has
    $quiet = $input->getOption('quiet');
    $classes = [];
    foreach ($files as $file) {
      if (!$quiet) {
        $output->writeln("Parsing $file ...");
      }
      $code = file($file);
      $parser = new Parser(new Lexer());
      $tree = $parser->parse(join('', $code));
      $traverser->traverse($tree);
      $classesInFile = $classVisitor->getClasses();
      foreach ($classesInFile as $class) {
        if (!isset($class->extends)) {
          continue;
        }
        if (isset($classes[$class->name])) {
          if (!$quiet) {
            $output->writeln('<error>Ignoring class definition of %s found in file %s:%d, first'
              . ' found in %s:%d</error>',
              $class->name,
              $file,
              $class->getAttribute('startLine'),
              $classes[$class->name]['file'],
              $classes[$class->name]['class']->getAttribute('startLine')
            );
          }
          continue;
        }
        # Remember for next pass
        $classes[$class->name] = [
          'class' => $class,
          'file'  => $file,
          'code'  => $code,
        ];
      }
    }

    $traverser->removeVisitor($classVisitor);
    $propertyVisitor = new PropertyVisitor();
    $traverser->addVisitor($propertyVisitor);

    if (empty($classes)) {
      $output->writeln('<error>No classes found</error>');
      return 1;
    }

    $fnFindTopAncestor = function ($className) use ($classes) {
      while (isset($classes[$className])) {
        $className = $classes[$className]['class']->extends->toString();
      }
      return $className;
    };

    $removeProperties = $input->getOption('remove');

    foreach ($classes as $className => $classData) {

      $fileName = $classData['file'];
      $transformedSource = NULL;

      switch ($fnFindTopAncestor($className)) {

        case 'Controller':
          $propertyVisitor->reset();
          $traverser->traverse([$classData['class']]);
          $transformedSource = ClassTransformer::apply(
            $classData['code'],
            $propertyVisitor->getClasses(),
            ['components', 'uses'],
            $removeProperties
          );
          break;

        case 'Helper':
          $propertyVisitor->reset();
          $traverser->traverse([$classData['class']]);
          $transformedSource = ClassTransformer::apply(
            $classData['code'],
            $propertyVisitor->getClasses(),
            [],
            $removeProperties
          );
          break;

        case 'Shell':
          $propertyVisitor->reset();
          $traverser->traverse([$classData['class']]);
          $transformedSource = ClassTransformer::apply(
            $classData['code'],
            $propertyVisitor->getClasses(),
            ['uses'],
            $removeProperties
          );
          break;

        case 'Model':
          $propertyVisitor->reset();
          $traverser->traverse([$classData['class']]);
          $transformedSource = ClassTransformer::apply(
            $classData['code'],
            $propertyVisitor->getClasses(),
            ['belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany'],
            $removeProperties
          );
          break;

        default:
          # ignored
          if (!$quiet) {
            $output->writeln("<comment>Ignoring $fileName , not a recognized class</comment>");
          }
          goto nextIteration;
      }

      if ($classData['code'] === $transformedSource) {
        continue;
      }

      if ($input->getOption('dry-run')) {
        if (!$quiet) {
          $output->writeln('Dry-run, not writing changes to ' . $fileName);
        }
        continue;
      }

      if (!$quiet) {
        $output->writeln('<info>Writing changes to ' . $fileName);
      }

      file_put_contents($fileName, $transformedSource);

      nextIteration:
    }

    return 0;
  }

  /**
   * @param string $dir
   * @return string[]
   */
  static private function scanDir($dir) {
    $files = [];
    $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
    /** @var $file \SplFileInfo */
    foreach ($iter as $file) {
      if (!$file->isFile()) {
        continue;
      }
      if ($file->getExtension() !== 'php') {
        continue;
      }
      $files[] = $file->getRealPath();
    };
    return $files;
  }
}
