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
namespace Mfn\CakePHP2\MagicProperty;

use Mfn\CakePHP2\MagicProperty\Logger\Logger;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\Parser;

/**
 * Add at-property documentation to CakePHP classes based on the special
 * dependency injection properties of the framework.
 *
 * Example, for:
 * class MyModel extends Model {
 *   public $belingsTo = ['Foo'];
 *
 * the following PHPDOC will be added
 * /**
 *  * @ property  Foo $Foo
 *  * /
 * class MyModel extends Model {
 *   public $belingsTo = ['Foo'];
 *
 * The file res/configuration.php contains the mapping which classes support
 * which special properties and how to properly transform them.
 *
 * How to use this class:
 * - use addSource() for files/dirs to scan
 * - run applyMagic()
 *   Note: files will be overwritten in-place!
 *
 * @author Markus Fischer <markus@fischer.name>
 */
class MagicProperty {

  /**
   * This list of files will be parsed
   * @var \SplFileInfo[]
   */
  private $files = [];
  /** @var  Logger */
  private $logger;
  /** @var bool */
  private $removeUnknownProperties = false;
  /**
   * If true, don't write any changes back to the file system
   * @var bool
   */
  private $dryRun = false;
  /** @var array */
  private $classToPropertySymbolTransform = [];

  public function __construct(Logger $logger) {
    $this->logger = $logger;

    # load default configuration
    $this->setConfigurationFromFile(__DIR__ . '/../res/configuration.php');
  }

  /**
   * Load configuration from file
   *
   * A configuration usually contains closures to perform the string
   * transformations. If these closures will be 'require'd in a dynamic method
   * they will bind to $this in the calling class which is undesired. No
   * binding will happen in static method calls.
   *
   * @param string $filename
   * @return array
   */
  static private function loadConfiguration($filename) {
    return require $filename;
  }

  /**
   * Adds a source to parse; directories will be scanned for *.php files
   *
   * @param string $source
   * @throws Exception
   */
  public function addSource($source) {
    if (is_file($source)) {
      $this->files[] = new \SplFileInfo($source);
    } elseif (is_dir($source)) {
      $this->files = array_merge($this->files, self::scanDir($source));
    } else {
      throw new Exception(
        "Given source '$source' is neither file or directory'"
      );
    }
  }

  /**
   * @param string $dir
   * @return \SplFileInfo[]
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
      $files[] = $file;
    };
    return $files;
  }

  /**
   * Perform the analysis for special properties and write them to the files.
   *
   * Use addSource() before calling this.
   *
   * @return int  Number of source files changed (does not necessarily mean they
   *              have been actually modified in case dryRun if true)
   */
  public function applyMagic() {

    $sourcesChanged = 0;

    # start processing
    $traverser = new NodeTraverser();
    $classVisitor = new ClassVisitor();
    $traverser->addVisitor($classVisitor);

    # Store which class is in which file and whose parent it has
    $classes = [];
    foreach ($this->files as $file) {
      $this->logger->info('Parsing ' . $file->getRealPath());
      $code = file($file);
      $parser = new Parser(new Lexer());
      $tree = $parser->parse(join('', $code));
      $traverser->traverse($tree);
      $classesInFile = $classVisitor->getClasses();
      foreach ($classesInFile as $class) {

        # A class without an parent cannot be any CakePHP managed class
        if (!isset($class->extends)) {
          continue;
        }

        if (isset($classes[$class->name])) {
          $this->logger->error(
            sprintf(
              'Ignoring class definition of %s found in file %s:%d, first'
              . ' found in %s:%d',
              $class->name,
              $file,
              $class->getAttribute('startLine'),
              $classes[$class->name]['file'],
              $classes[$class->name]['class']->getAttribute('startLine')
            )
          );
          continue;
        }

        # Remember for next pass
        $classes[$class->name] = [
          'class' => $class,
          'file'  => $file->getRealPath(),
          'code'  => $code,
        ];
      }
    }

    $traverser->removeVisitor($classVisitor);
    $propertyVisitor = new PropertyVisitor();
    $propertyVisitor->setProperties($this->getProperties());
    $traverser->addVisitor($propertyVisitor);

    if (empty($classes)) {
      $this->logger->warning('No classes found');
      return $sourcesChanged;
    }

    $fnFindTopAncestor = function ($className) use ($classes) {
      while (isset($classes[$className])) {
        $className = $classes[$className]['class']->extends->toString();
      }
      return $className;
    };

    foreach ($classes as $className => $classData) {

      $fileName = $classData['file'];
      $transformedSource = NULL;

      $topAncestor = $fnFindTopAncestor($className);

      if (!isset($this->classToPropertySymbolTransform[$topAncestor])) {
        $this->logger->info("Ignoring $fileName , not a recognized class");
        continue;
      }
      $traverser->traverse([$classData['class']]);
      $transformedSource = ClassTransformer::apply(
        $classData['code'],
        $propertyVisitor->getClasses(),
        $this->classToPropertySymbolTransform[$topAncestor],
        $this->removeUnknownProperties
      );

      if ($classData['code'] === $transformedSource) {
        continue;
      }

      $sourcesChanged++;

      if ($this->dryRun) {
        $this->logger->info('Dry-run, not writing changes to ' . $fileName);
        continue;
      }

      $this->logger->info('Writing changes to ' . $fileName);

      file_put_contents($fileName, $transformedSource);
    }

    return $sourcesChanged;
  }

  /**
   * A configuration contains class->property->callback mapping; this will
   * method only retrieves all properties.
   *
   * @return string[]
   */
  private function getProperties() {
    $properties = [];
    foreach ($this->classToPropertySymbolTransform as $className => $propertyTransformer) {
      $properties = array_merge(
        $properties,
        array_keys($propertyTransformer)
      );
    }
    return array_unique($properties);
  }

  /**
   * @return boolean
   */
  public function isRemoveUnknownProperties() {
    return $this->removeUnknownProperties;
  }

  /**
   * @param boolean $remoteUnknownProperties
   * @return $this
   */
  public function setRemoveUnknownProperties($remoteUnknownProperties) {
    $this->removeUnknownProperties = $remoteUnknownProperties;
    return $this;
  }

  /**
   * @return boolean
   */
  public function isDryRun() {
    return $this->dryRun;
  }

  /**
   * @param boolean $dryRun
   * @return $this
   */
  public function setDryRun($dryRun) {
    $this->dryRun = $dryRun;
    return $this;
  }

  /**
   * @param array $config
   * @return $this
   */
  public function setConfiguration(array $config) {
    $this->classToPropertySymbolTransform = $config;
    return $this;
  }

  /**
   * @param string $filename
   * @return $this
   */
  public function setConfigurationFromFile($filename) {
    $this->classToPropertySymbolTransform = self::loadConfiguration($filename);
    return $this;
  }
}
