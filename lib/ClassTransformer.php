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
 * @see apply()
 * @author Markus Fischer <markus@fischer.name>
 */
class ClassTransformer {

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
   * a mapping of injected property names to their class types
   *
   * This nodes/statement from PhpParser also contain additional information
   * about the PHPDOC and in which lines in the source those elements are.
   * This information is used to either create a new PHPDOC or change an
   * existing one to contain the magic properties.
   *
   * Note: it may seem redundant that the $classes->property->symbol mapping
   * already has all properties and a separate $properties list is required.
   * However during collecting a property we don't know yet which top level
   * class it is in, i.e. which properties apply to the current class.
   * This is solves here insofar the top level class check happened (outside)
   * already and thus we get a specific list of $properties to document.
   *
   * @param array $code The code of the PHP file as returned by the file()
   *                    command, i.e. an every with every line in the file
   *                    including EOL.
   * @param SimpleOrderedMap $classes Mapping of classes to the collected
   *                                  property which maps to a list of symbols.
   * @param string[] $properties Name the properties whose symbols should be
   *                              written to PHPDOC.
   * @param bool $removeUnknownProperties Removes all other properties (first)
   *                                      and then adds the found ones. This may
   *                                      remove properties which have been
   *                                      manually added so use with care.
   * @throws Exception
   * @throws SimpleOrderedMapException
   * @return array Returns the (possible) modified PHP source code.
   */
  static public function apply(array $code, SimpleOrderedMap $classes,
                               array $properties,
                               $removeUnknownProperties = false) {
    if (empty($code)) {
      return $code; # nothing to do
    }

    $insertions = []; # record at which line what kind of insertions will happen

    /** @var Class_ $class */
    foreach ($classes->keys() as $class) {
      $currentInsertions = 0; # keep count how many properties == lines we've added
      # Detect existing or create new PhpDoc and figure out indentation
      $phpDoc = self::getLastPhpDocComment($class);
      $phpDocNumLinesInSource = 0;
      $docIndent = '';
      if ($phpDoc instanceof Doc) {
        # get indentation from existing phpdoc
        $text = $phpDoc->getText();
        if (preg_match(self::RE_INDENT, $text, $m)) {
          $docIndent = $m['indent'];
        }

        # If it's a single line comment, we've to break it up
        if (!preg_match('/\R/', $text)) {

          $textNoEndComment = preg_replace(';\*/\s*;', '', $text);
          # If there was no change to the text, we couldn't remove the end of
          # comment we expected to be there; that's rather unexpected
          if ($text === $textNoEndComment) {
            throw new Exception(
              'Unable to remove end doc comment marker \'*/\' from single line comment');
          }
          # Since we've add a new line we need to know the files EOL
          $eol = self::extractEol(reset($code));
          $text = $textNoEndComment . $eol . $docIndent . ' */';
          $phpDoc->setText($text);

          $lines = self::splitStringIntoLines($text);
          $phpDocNumLinesInSource = 1; # hardcoded because we know

        } else {

          $lines = self::splitStringIntoLines($text);
          $phpDocNumLinesInSource = count($lines);

        }
        if ($removeUnknownProperties) {
          # Remove every line already containing a @property statement
          foreach ($lines as $i => $line) {
            if (false !== strpos($line, '@property')) {
              unset($lines[$i]);
            }
          }
          $lines = array_values($lines); # ensure no gaps in indices
          $phpDoc->setText(join('', $lines));
        }
        assert(0 !== $phpDocNumLinesInSource);
      } else {
        # No PHPDOC found, create a new one
        $default = self::DEFAULT_PHPDOC;
        # indent default phpdoc by current class indentation
        $classLine = $code[$class->getAttribute('startLine') - 1];
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
        if (!in_array($property->name, array_keys($properties))) {
          continue;
        }

        $symbols = $currentProperties->get($property);

        foreach ($symbols as $symbol => $type) {
          # Extract only class name part (i.e. throw away name of plugins)
          $symbol = preg_replace('/.*\.([^\.]+)$/', '$1', $symbol);
          $type = preg_replace('/.*\.([^\.]+)$/', '$1', $type);

          $type = $properties[$property->name]($type);

          if (self::addPropertyIfNotExists($phpDoc, $type, $symbol, $docIndent)) {
            $currentInsertions++;
          }
        }
      }
      if ($currentInsertions === 0) {
        # no lines added, nothing to do
        continue;
      }
      # Accumulate sum of previously replaced lines to get actual line number
      $alreadyAddedLines = array_reduce($insertions, function ($carry, $item) {
        return $carry + $item['replaceNumLines'];
      }, 0);
      $insertions[$phpDoc->getLine() - 1 + $alreadyAddedLines] = [
        'doc'             => $phpDoc,
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
   * Takes a string a converts it into an array where each line also contains
   * the EOL; similar like file() behaves.
   *
   * Additional work is done to ensure there's finished EOL.
   *
   * @param string $str
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

  /**
   * Adds the type/symbol to PHPDOC if not already present.
   * @param Doc $phpDoc
   * @param string $type E.g. 'Html' or 'HtmlHelper'
   * @param string $symbol E.g. 'Html'
   * @param string $indent The indentation so when adding properties it matches
   *                        general PHPDOC indentation
   * @return boolean True if property was added, false if not
   */
  static private function addPropertyIfNotExists(Doc $phpDoc,
                                                 $type, $symbol, $indent) {
    $text = $phpDoc->getText();
    # split into lines but ensure we keep the existing line ending format
    $lines = self::splitStringIntoLines($text);
    # try to find the symbol we're going to add
    $reSymMatch = '/\*\s*@property\s+' .
      preg_quote($type, '/') . '\s+\$' .
      preg_quote($symbol, '/') . '/i';
    foreach ($lines as $line) {
      if (preg_match($reSymMatch, $line)) {
        return false;
      }
    }
    # We haven't found it, so add it at the end before the comment ends
    $addedLine = $indent . '* @property ' . $type . ' $' . $symbol;
    array_splice($lines, count($lines) - 1, 0,
      [$addedLine . self::extractEol($text)]);
    $phpDoc->setText(join('', $lines));
    return true;
  }
}
