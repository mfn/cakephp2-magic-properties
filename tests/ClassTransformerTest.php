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

use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\Parser;

/**
 * @author Markus Fischer <markus@fischer.name>
 */
class ClassTransformerTest extends \PHPUnit_Framework_TestCase {

  /** @var NodeTraverser $traverser */
  private $traverser = NULL;
  /** @var PropertyVisitor $visitor */
  private $visitor = NULL;
  /** @var Parser $parser */
  private $parser = NULL;

  /**
   * @expectedException \Mfn\CakePHP2\MagicProperty\Exception
   * @expectedExceptionMessage Expected at least two lines, none found
   */
  public function testString2LinesEmpty() {
    $actual = ClassTransformer::splitStringIntoLines(
      "");
    $expected = [];
    $this->assertSame($expected, $actual);
  }

  /**
   * @expectedException \Mfn\CakePHP2\MagicProperty\Exception
   * @expectedExceptionMessage Expected at least two lines, only one found
   */
  public function testString2LinesLineNoEnd() {
    $actual = ClassTransformer::splitStringIntoLines(
      "line1");
    $expected = [];
    $this->assertSame($expected, $actual);
  }

  /**
   * @expectedException \Mfn\CakePHP2\MagicProperty\Exception
   * @expectedExceptionMessage Expected at least two lines, only one found
   */
  public function testString2LinesLineWithEnd() {
    $actual = ClassTransformer::splitStringIntoLines(
      "line1\n");
    $expected = [];
    $this->assertSame($expected, $actual);
  }

  public function testString2LinesTwoLinesNoEnd() {
    $actual = ClassTransformer::splitStringIntoLines(
      "line1\nline2");
    $expected = ["line1\n", "line2\n"];
    $this->assertSame($expected, $actual);
  }

  public function testString2LinesTwoLinesWithEnd() {
    $actual = ClassTransformer::splitStringIntoLines(
      "line1\nline2\n");
    $expected = ["line1\n", "line2\n"];
    $this->assertSame($expected, $actual);
  }

  public function testString2LinesEmptyLineMiddle() {
    $actual = ClassTransformer::splitStringIntoLines(
      "line1\n\nline2\n");
    $expected = ["line1\n", "\n", "line2\n"];
    $this->assertSame($expected, $actual);
  }

  protected function setUp() {
    $this->traverser = new NodeTraverser();
    $this->visitor = new ClassVisitor();
    $this->parser = new Parser(new Lexer());
  }
}
