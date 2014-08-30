<?php
namespace Mfn\CakePHP2;

use Mfn\Util\SimpleOrderedMap;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeVisitorAbstract;

/**
 * A visitor for PhpParser\NodeTraverser
 *
 * Visits every class and collects all properties documented in
 * in self::$specialProperties . It expects those properties to be array of
 * strings.
 *
 * @author Markus Fischer <markus@fischer.name>
 */
class MagicPropertyVisitor extends NodeVisitorAbstract {
  /**
   * Special properties used by CakePHP2
   * @var string[]
   */
  private static $specialProperties = [
    'components',
    'helpers',
    'uses',
  ];
  /**
   * Maps classes to their matched properties
   * @type SimpleOrderedMap
   */
  private $classes = NULL;

  public function __construct() {
    $this->reset();
  }

  /**
   * Resets the internal state of found matches so the visitor can be reused.
   */
  public function reset() {
    $this->classes = new SimpleOrderedMap();
  }

  public function enterNode(Node $node) {
    if (!($node instanceof Class_)) {
      return;
    }
    if ($this->classes->exists($node)) {
      throw new MagicPropertyException(
        "Class {$node->name} already encountered");
    }
    $properties = new SimpleOrderedMap;
    foreach ($node->stmts as $stmt) {
      if (!($stmt instanceof Property)) {
        continue;
      }
      foreach ($stmt->props as $prop) {
        if (!in_array($prop->name, self::$specialProperties, true)) {
          continue;
        }
        if ($properties->exists($prop)) {
          throw new MagicPropertyException(
            "Property {$prop->name} already exists");
        }
        if (!($prop->default instanceof Array_)) {
          continue;
        }
        $extracted = self::arrayExtractItems($prop->default);
        if (empty($extracted)) {
          continue;
        }
        sort($extracted);
        $properties->add($prop, $extracted);
      }
    }
    $this->classes->add($node, $properties);
  }

  static private function arrayExtractItems(Array_ $expr) {
    $extracted = [];
    foreach ($expr->items as $item) {
      if (!($item->value instanceof String)) {
        continue;
      }
      $extracted[] = $item->value->value;
    }
    return $extracted;
  }

  /**
   * @return SimpleOrderedMap
   */
  public function getClasses() {
    return $this->classes;
  }
}
