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

use Mfn\Util\SimpleOrderedMap;
use Mfn\Util\SimpleOrderedMapException;
use PhpParser\Comment\Doc;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;

/**
 * @see MagicPropertyWriter::apply()
 * @author Markus Fischer <markus@fischer.name>
 */
class Writer {
  /**
   * Includes extra empty line for space -> deliberately!
   */
  const DEFAULT_PHPDOC = <<<EOF
/**
 */

EOF;
  /** Used to match the indentation of a line */
  const RE_INDENT = '/^(?<indent>\s*)/';

  /**
   * Rewrite the PHPDOC of a class to contain the documentation of the
   * special CakePHP2 magic properties.
   *
   * $classes maps parsed PHP classes to properties and each property contains
   * the list of strings contained in it.
   *
   * This nodes/statement from PhpParser also contain additional information
   * about the PHPDOC and in which lines in the source those elements are.
   * This information is used to either create a new PHPDOC or change an
   * existing one to contain the magic properties.
   *
   * @param array $code The code of the PHP file as returned by the file()
   *                    command, i.e. every line includes the EOL.
   * @param SimpleOrderedMap $classes Mapping of classes to the collected
   *                                  property information
   * @param string[] $properties Optional: Name the properties to write to the
   *  PHPDOC in in this class; see Visitor::$specialProperties
   *  for available names. By default all recognized properties will be
   *  processed. This may not be always desirable. Controllers e.g. don't
   *  have the $helpers property injected into themselves.
   * @throws Exception
   * @throws SimpleOrderedMapException
   * @return array Returns the possible modified PHP source code.
   */
  static public function apply(array $code, SimpleOrderedMap $classes,
                               array $properties = []) {
    if (empty($code)) {
      return $code; # nothing to do
    }
    if (empty($properties)) {
      $properties = Visitor::$specialProperties;
    }
    $insertions = []; # record at which line what kind of insertions will happen
    /** @var Class_ $class */
    foreach ($classes->keys() as $class) {
      $currentInsertions = 0; # keep count how many properties == lines we've added
      # Detect existing or create new PhpDoc and figure out indentation
      $phpDoc = self::getLastPhpDocComment($class);
      $phpDocNumLinesInSource = 0;
      $docIndent = '';
      # TODO: ensure we've a multi-line php doc comment, otherwise treat as there is none
      if ($phpDoc instanceof Doc) {
        # get indentation from existing phpdoc
        $text = $phpDoc->getText();
        if (preg_match(self::RE_INDENT, $text, $m)) {
          $docIndent = $m['indent'];
        }
        $phpDocNumLinesInSource = count(self::splitStringIntoLines($text));
        assert(0 !== $phpDocNumLinesInSource);
      } else {
        $default = self::DEFAULT_PHPDOC;
        # indent default phpdoc by current class indentation
        $classLine = $code[ $class->getAttribute('startLine') - 1];
        if (preg_match(self::RE_INDENT, $classLine, $m)) {
          $docIndent = $m['indent'];
          # now prepend the indent before each line
          $lines = preg_split("/(\R)/", $default, -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
          for ($lineNr = 0; $lineNr < count($lines) >> 1; $lineNr++) {
            # accommodate for extra EOL matches
            $lines[$lineNr << 1] = $docIndent . $lines[$lineNr << 1];
          }
          $default = join('', $lines);
          unset($lines);
        }
        $phpDoc = new Doc($default,
          $class->getAttribute('startLine'));
        unset($default);
      }
      $docIndent .= ' '; # extra space for slash
      # Go through the parsed properties and add them to the phpdoc if they
      # can't be found
      $currentProperties = $classes->get($class);
      /** @var Property $property */
      foreach ($currentProperties->keys() as $property) {
        $symbols = $currentProperties->get($property);
        if (!in_array($property->name, $properties)) {
          continue;
        }
        switch ($property->name) {
          case 'components':
            foreach ($symbols as $symbol) {
              # Extract only class name part if contains
              if (preg_match('/\.(?<symbol>[^\.]+)$/', $symbol, $m)) {
                $symbol = $m['symbol'];
              }
              if (true === self::addPropertyIfNotExists($phpDoc,
                  $symbol . 'Component', $symbol, $docIndent)) {
                $currentInsertions++;
              }
            }
            break;
          case 'helpers':
            foreach ($symbols as $symbol) {
              # Extract only class name part if contains
              if (preg_match('/\.(?<symbol>[^\.]+)$/', $symbol, $m)) {
                $symbol = $m['symbol'];
              }
              if (true === self::addPropertyIfNotExists($phpDoc,
                  $symbol . 'Helper', $symbol, $docIndent)) {
                $currentInsertions++;
              }
            }
            break;
          case 'uses':
            foreach ($symbols as $symbol) {
              # Extract only class name part if contains
              if (preg_match('/\.(?<symbol>[^\.]+)$/', $symbol, $m)) {
                $symbol = $m['symbol'];
              }
              if (true ===self::addPropertyIfNotExists(
                  $phpDoc, $symbol, $symbol, $docIndent)) {
                $currentInsertions++;
              }
            }
            break;
          default:
            throw new Exception(
              "Unknown special propert '{$property->name}'");
        }
      }
      if ($currentInsertions === 0) {
        # no lines added, nothing to do
        continue;
      }
      # Accumulate sum of previously replaced lines to get actual line number
      $alreadyAddedLines = array_reduce($insertions, function($carry, $item) {
        return $carry + $item['replaceNumLines'];
      }, 0);
      $insertions[$phpDoc->getLine() -1 + $alreadyAddedLines] = [
        'doc' => $phpDoc,
        'replaceNumLines' => $phpDocNumLinesInSource
      ];
    }
    foreach ($insertions as $lineNr => $data) {
      /** @var Doc $phpDoc */
      $phpDoc = $data['doc'];
      $text = $phpDoc->getText();
      $lines = self::splitStringIntoLines($text);
      array_splice($code, $lineNr, $data['replaceNumLines'], $lines);
    }
    return $code;
  }

  /**
   * Find the last/nearest PHPDOC of the class.
   * @param Class_ $class
   * @return null|Doc
   */
  static private function getLastPhpDocComment(Class_ $class) {
    $comments = $class->getAttribute('comments');
    if (NULL === $comments) {
      return NULL;
    }
    $lastPhpdocComment = NULL;
    foreach ($comments as $comment) {
      if ($comment instanceof Doc) {
        $lastPhpdocComment = $comment;
      }
    }
    return $lastPhpdocComment;
  }

  /**
   * Adds the type/symbol to PHPDOC if not already present.
   * @param Doc $phpDoc
   * @param string $type E.g. 'Html' or 'HtmlHelper'
   * @param string $symbol E.g. 'Html'
   * @param string $indent  The indentation so when adding properties it matches
   *                        general PHPDOC indentation
   * @return boolean True if property was added, false if not
   */
  static private function addPropertyIfNotExists(Doc $phpDoc,
                                                 $type, $symbol, $indent) {
    $text = $phpDoc->getText();
    # split into lines but ensure we keep the existing line ending format
    $lines = self::splitStringIntoLines($text);
    # try to find the symbol we're going to add
    $reSymMatch = '/\*\s*@property.*' .
      preg_quote($type, '/') . '.*' .
      preg_quote($symbol, '/') . '/i';
    foreach ($lines as $line) {
      if (preg_match($reSymMatch, $line)) {
        return false;
      }
    }
    # We haven't found it, so add it at the end before the comment ends
    $addedLine = $indent .'* @property ' . $type . ' $' . $symbol;
    array_splice($lines, count($lines) - 1, 0,
      [ $addedLine . self::extractEol($text) ]);
    $phpDoc->setText(join('', $lines));
    return true;
  }

  /**
   * Takes a string a converts it into an array where each line also contains
   * the EOL; similar like file() behaves.
   *
   * Additional work is done to ensure there's finished EOL.
   *
   * @param $str
   * @throws Exception
   * @return array
   */
  static public function splitStringIntoLines($str) {
    $lines = preg_split("/(\R)/", $str, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (empty($lines)) {
      throw new Exception(
        'Expected at least two lines, none found');
    }
    if (count($lines) === 1 && reset($lines) === '') {
      throw new Exception(
        'Expected at least two lines, none found');
    }
    if ('' === end($lines)) {
      # remove last element if empty string; reason is input like "foo\n"
      # will produce ["foo", "\n", ""]
      array_pop($lines);
    }
    # The splits will look like:
    # "" => [""]
    # "one" => ["one"]
    # "one\n" => ["one", "\n"]
    # "one\ntwo" => ["one", "\n", "two"]
    # "one\ntwo\n" => ["one", "\n", "two", "\n"]
    if (count($lines) < 3) {
      throw new Exception(
        'Expected at least two lines, only one found');
    }
    if (count($lines) % 2 !== 0) {
      $eol = self::extractEol($str);
      $lines[] = $eol;
    }
    $result = [];
    for ($lineNr = 0; $lineNr < count($lines) >> 1; $lineNr++) {
      $result[] = $lines[$lineNr << 1] . $lines[($lineNr << 1) + 1];
    }
    return $result;
  }

  /**
   * Extract the first EOL from str
   * @param string $str
   * @throws Exception
   * @return
   */
  static private function extractEol($str) {
    if (!preg_match('/\R/', $str, $m)) {
      throw new Exception('Unable to extract EOL');
    }
    return $m[0];
  }
}
