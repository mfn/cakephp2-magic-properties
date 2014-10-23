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
class ClassVisitorTest extends \PHPUnit_Framework_TestCase {

  /** @var NodeTraverser $traverser */
  private $traverser = NULL;
  /** @var ClassVisitor $visitor */
  private $visitor = NULL;
  /** @var Parser $parser */
  private $parser = NULL;

  public function testSingleClass() {
    $source = <<<'PHP'
<?php
class Foo {
  var $uses = ["Foo", "Bar"];
}
PHP;
    $tree = $this->parser->parse($source);
    $this->traverser->traverse($tree);
    $classes = $this->visitor->getClasses();
    $this->assertSame(1, count($classes));
    $this->assertSame('Foo', $classes[0]->name);
  }

  public function testMultiClass() {
    $source = <<<'PHP'
<?php
class Foo {
  var $uses = ["Foo", "Bar"];
}
class Bar {
}
PHP;
    $tree = $this->parser->parse($source);
    $this->traverser->traverse($tree);
    $classes = $this->visitor->getClasses();
    $this->assertSame(2, count($classes));
    $this->assertSame('Foo', $classes[0]->name);
    $this->assertSame('Bar', $classes[1]->name);
  }

  protected function setUp() {
    $this->traverser = new NodeTraverser();
    $this->visitor = new ClassVisitor();
    $this->traverser->addVisitor($this->visitor);
    $this->parser = new Parser(new Lexer());
  }
}
