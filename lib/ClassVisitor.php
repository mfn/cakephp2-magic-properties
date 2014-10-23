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

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;

/**
 * A visitor for PhpParser\NodeTraverser
 *
 * Extracts all classes when running through \PhpParser\NodeTraverser .
 *
 * Use getClasses() to retrieve them. Call reset() to re-use the visitor.
 *
 * @author Markus Fischer <markus@fischer.name>
 */
class ClassVisitor extends NodeVisitorAbstract {

  /**
   * Classes found
   * @type Class_
   */
  private $classes = [];

  public function __construct() {
    $this->reset();
  }

  /**
   * Resets the internal state
   */
  public function reset() {
    $this->classes = [];
  }

  public function enterNode(Node $node) {
    if (!($node instanceof Class_)) {
      return;
    }
    $this->classes[] = $node;
  }

  /**
   * @return Class_[]
   */
  public function getClasses() {
    return $this->classes;
  }
}
