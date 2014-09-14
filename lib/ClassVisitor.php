<?php
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
