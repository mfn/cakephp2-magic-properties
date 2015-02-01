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

use Mfn\Util\Map\SimpleOrderedMap;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeVisitorAbstract;

/**
 * A visitor for PhpParser\NodeTraverser
 *
 * After instantiation, use setProperties() to set which ones to collect during
 * traversal.
 *
 * Extracts the requested properties from all classes from \PhpParser\NodeTraverser .
 *
 * Use getClasses() to retrieve a mapping of class to property matching.
 *
 * @author Markus Fischer <markus@fischer.name>
 */
class PropertyVisitor extends NodeVisitorAbstract {

  /** @var string[] */
  private $properties = [];

  /**
   * Maps classes to their matched properties to their matches symbols
   * @type SimpleOrderedMap
   */
  private $classes = NULL;

  public function beforeTraverse(array $nodes) {
    $this->classes = new SimpleOrderedMap();
  }

  public function enterNode(Node $node) {
    if (!($node instanceof Class_)) {
      return;
    }
    if ($this->classes->exists($node)) {
      throw new Exception(
        "Class {$node->name} already encountered");
    }
    $properties = new SimpleOrderedMap;
    foreach ($node->stmts as $stmt) {
      if (!($stmt instanceof Property)) {
        continue;
      }
      foreach ($stmt->props as $prop) {
        if (!in_array($prop->name, $this->properties, true)) {
          continue;
        }
        if ($properties->exists($prop)) {
          throw new Exception("Property {$prop->name} already exists");
        }
        if (!($prop->default instanceof Array_)) {
          continue;
        }
        $symbols = self::arrayExtractItems($prop->default);
        if (empty($symbols)) {
          continue;
        }
        asort($symbols);
        $properties->add($prop, $symbols);
      }
    }
    $this->classes->add($node, $properties);
  }

  /**
   * Returns a mapping of Cakes injected properties and their types.
   *
   * @param Array_ $expr
   * @return string[] Mapping of symbols to their types
   */
  static private function arrayExtractItems(Array_ $expr) {
    $symbols = [];
    foreach ($expr->items as $item) {

      # Symbols can either be listed by their names (have a value in the hash)
      if (NULL === $item->key && $item->value instanceof String) {
        $symbols[$item->value->value] = $item->value->value;
        continue;
      }

      # or can have configuration by which the name is in the key and the
      # value is a configuration
      if ($item->key instanceof String && $item->value instanceof Array_) {
        # If the array has a className property, derive class name from it
        $className = self::extractClassNameValue($item->value->items);
        if (NULL === $className) {
          $className = $item->key->value;
        }
        $symbols[$item->key->value] = $className;
        continue;
      }
    }
    return $symbols;
  }

  /**
   * @param array $items
   * @return null|string
   */
  static private function extractClassNameValue(array $items) {
    foreach ($items as $item) {
      if (!isset($item->key)) {
        continue;
      }
      if ($item->key->value !== 'className') {
        continue;
      }
      if (!($item->value instanceof String)) {
        continue;
      }
      return $item->value->value;
    }
    return NULL;
  }

  /**
   * @return SimpleOrderedMap
   */
  public function getClasses() {
    return $this->classes;
  }

  /**
   * @return \string[]
   */
  public function getProperties() {
    return $this->properties;
  }

  /**
   * @param \string[] $properties
   * @return $this
   */
  public function setProperties($properties) {
    $this->properties = $properties;
    return $this;
  }
}
