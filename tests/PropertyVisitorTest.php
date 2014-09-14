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
class PropertyVisitorTest extends \PHPUnit_Framework_TestCase {
  /** @var NodeTraverser $traverser */
  private $traverser = NULL;
  /** @var PropertyVisitor $visitor */
  private $visitor = NULL;
  /** @var Parser $parser */
  private $parser = NULL;

  public function testWriterUses() {
    $sourceIn = [
      '<?php' . PHP_EOL,
      'class Foo {' . PHP_EOL,
      '  var $uses = ["Foo", "Bar"];' . PHP_EOL,
      '}' . PHP_EOL,
    ];
    $sourceOutExpected = [
      '<?php' . PHP_EOL,
      '/**' . PHP_EOL,
      ' * @property Bar $Bar' . PHP_EOL,
      ' * @property Foo $Foo' . PHP_EOL,
      ' */' . PHP_EOL,
      'class Foo {' . PHP_EOL,
      '  var $uses = ["Foo", "Bar"];' . PHP_EOL,
      '}' . PHP_EOL,
    ];
    $tree = $this->parser->parse(join('', $sourceIn));
    $this->traverser->traverse($tree);
    $sourceOutActual = ClassTransformer::apply(
      $sourceIn,
      $this->visitor->getClasses()
    );
    $this->assertSame($sourceOutExpected, $sourceOutActual);
  }

  public function testWriterHelper() {
    $sourceIn = [
      '<?php' . PHP_EOL,
      'class Foo {' . PHP_EOL,
      '  var $helpers = ["Foo", "Bar"];' . PHP_EOL,
      '}' . PHP_EOL,
    ];
    $sourceOutExpected = [
      '<?php' . PHP_EOL,
      '/**' . PHP_EOL,
      ' * @property BarHelper $Bar' . PHP_EOL,
      ' * @property FooHelper $Foo' . PHP_EOL,
      ' */' . PHP_EOL,
      'class Foo {' . PHP_EOL,
      '  var $helpers = ["Foo", "Bar"];' . PHP_EOL,
      '}' . PHP_EOL,
    ];
    $tree = $this->parser->parse(join('', $sourceIn));
    $this->traverser->traverse($tree);
    $sourceOutActual = ClassTransformer::apply(
      $sourceIn,
      $this->visitor->getClasses()
    );
    $this->assertSame($sourceOutExpected, $sourceOutActual);
  }

  public function testWriterComponents() {
    $sourceIn = [
      '<?php' . PHP_EOL,
      'class Foo {' . PHP_EOL,
      '  var $components = ["Foo", "Bar"];' . PHP_EOL,
      '}' . PHP_EOL,
    ];
    $sourceOutExpected = [
      '<?php' . PHP_EOL,
      '/**' . PHP_EOL,
      ' * @property BarComponent $Bar' . PHP_EOL,
      ' * @property FooComponent $Foo' . PHP_EOL,
      ' */' . PHP_EOL,
      'class Foo {' . PHP_EOL,
      '  var $components = ["Foo", "Bar"];' . PHP_EOL,
      '}' . PHP_EOL,
    ];
    $tree = $this->parser->parse(join('', $sourceIn));
    $this->traverser->traverse($tree);
    $sourceOutActual = ClassTransformer::apply(
      $sourceIn,
      $this->visitor->getClasses()
    );
    $this->assertSame($sourceOutExpected, $sourceOutActual);
  }

  public function testWriterSelectedProperties() {
    $sourceIn = [
      '<?php' . PHP_EOL,
      'class Foo {' . PHP_EOL,
      '  var $uses = ["Ranger"];' . PHP_EOL,
      '  var $helpers = ["Rick"];' . PHP_EOL,
      '  var $components = ["Kansas"];' . PHP_EOL,
      '}' . PHP_EOL,
    ];
    $sourceOutExpected = [
      '<?php' . PHP_EOL,
      '/**' . PHP_EOL,
      ' * @property RickHelper $Rick' . PHP_EOL,
      ' */' . PHP_EOL,
      'class Foo {' . PHP_EOL,
      '  var $uses = ["Ranger"];' . PHP_EOL,
      '  var $helpers = ["Rick"];' . PHP_EOL,
      '  var $components = ["Kansas"];' . PHP_EOL,
      '}' . PHP_EOL,
    ];
    $tree = $this->parser->parse(join('', $sourceIn));
    $this->traverser->traverse($tree);
    $sourceOutActual = ClassTransformer::apply(
      $sourceIn,
      $this->visitor->getClasses(),
      ['helpers']
    );
    $this->assertSame($sourceOutExpected, $sourceOutActual);
  }

  public function testWriterComponentsWithConfig() {
    $sourceIn = [
      '<?php' . PHP_EOL,
      'class Foo {' . PHP_EOL,
      '  var $components = ["Foo", "Bar" => ["Baz"] ];' . PHP_EOL,
      '}' . PHP_EOL,
    ];
    $sourceOutExpected = [
      '<?php' . PHP_EOL,
      '/**' . PHP_EOL,
      ' * @property BarComponent $Bar' . PHP_EOL,
      ' * @property FooComponent $Foo' . PHP_EOL,
      ' */' . PHP_EOL,
      'class Foo {' . PHP_EOL,
      '  var $components = ["Foo", "Bar" => ["Baz"] ];' . PHP_EOL,
      '}' . PHP_EOL,
    ];
    $tree = $this->parser->parse(join('', $sourceIn));
    $this->traverser->traverse($tree);
    $sourceOutActual = ClassTransformer::apply(
      $sourceIn,
      $this->visitor->getClasses()
    );
    $this->assertSame($sourceOutExpected, $sourceOutActual);
  }

  public function testTransformerRemove() {
    $sourceIn = [
      '<?php' . PHP_EOL,
      '/**' . PHP_EOL,
      ' * @property Bar $Bar' . PHP_EOL,
      ' * @property Foo $Foo' . PHP_EOL,
      ' */' . PHP_EOL,
      'class Foo {' . PHP_EOL,
      '  var $uses = ["Bar"];' . PHP_EOL,
      '}' . PHP_EOL,
    ];
    $sourceOutExpected = [
      '<?php' . PHP_EOL,
      '/**' . PHP_EOL,
      ' * @property Bar $Bar' . PHP_EOL,
      ' */' . PHP_EOL,
      'class Foo {' . PHP_EOL,
      '  var $uses = ["Bar"];' . PHP_EOL,
      '}' . PHP_EOL,
    ];
    $tree = $this->parser->parse(join('', $sourceIn));
    $this->traverser->traverse($tree);
    $sourceOutActual = ClassTransformer::apply(
      $sourceIn,
      $this->visitor->getClasses(),
      [],
      true
    );
    $this->assertSame($sourceOutExpected, $sourceOutActual);
  }

  public function testTransformerReorderWithRemove() {
    $sourceIn = [
      '<?php' . PHP_EOL,
      '/**' . PHP_EOL,
      ' * @property Foo $Foo' . PHP_EOL,
      ' * @property Bar $Bar' . PHP_EOL,
      ' */' . PHP_EOL,
      'class Foo {' . PHP_EOL,
      '  var $uses = ["Foo", "Bar"];' . PHP_EOL,
      '}' . PHP_EOL,
    ];
    $sourceOutExpected = [
      '<?php' . PHP_EOL,
      '/**' . PHP_EOL,
      ' * @property Bar $Bar' . PHP_EOL,
      ' * @property Foo $Foo' . PHP_EOL,
      ' */' . PHP_EOL,
      'class Foo {' . PHP_EOL,
      '  var $uses = ["Foo", "Bar"];' . PHP_EOL,
      '}' . PHP_EOL,
    ];
    $tree = $this->parser->parse(join('', $sourceIn));
    $this->traverser->traverse($tree);
    $sourceOutActual = ClassTransformer::apply(
      $sourceIn,
      $this->visitor->getClasses(),
      [],
      true
    );
    $this->assertSame($sourceOutExpected, $sourceOutActual);
  }

  protected function setUp() {
    $this->traverser = new NodeTraverser();
    $this->visitor = new PropertyVisitor();
    $this->traverser->addVisitor($this->visitor);
    $this->parser = new Parser(new Lexer());
  }
}
