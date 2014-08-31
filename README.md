# Parse CakePHP2 controllers source files and add magic property PHPDOC [ ![Travis Build Status](https://travis-ci.org/mfn/cakephp2-magic-properties.svg?branch=master)](https://travis-ci.org/mfn/cakephp2-magic-properties)

Homepage: https://github.com/mfn/cakephp2-magic-properties

# Blurb

When working with [CakePHP2](http://cakephp.org/), dependency injection happens via some magic properties like `uses`, `helpers` and `components`.

Unfortunately not many editors/IDEs are capable of understanding this special syntax and provide autocompletion for the resulting magic properties.

By running this script against controller sources, PHPDOC `@property` is addded to the class definitions which aids into autocompletion the propert types.

This code uses the excellent [PhpParser library](https://github.com/nikic/PHP-Parser) by Nikita Popov.

# Usage

`cakephp2_magic_properties app/Controller/AppController.php`

It will convert:
```PHP
AppController extends Controller {
  $uses = ['Foo'];
  $component = ['Bar'];
}
```
into
```PHP
/**
 * @property BarComponent $Bar
 * @property Foo $Foo
 */
AppController extends Controller {
  $uses = ['Foo'];
  $component = ['Bar'];
}
```

Note: no checks are done whether the class is actually a CakePHP2 controller or not.

# Install

Using composer: `composer.phar require mfn/cakephp2-magic-properties 0.0.3`

© Markus Fischer <markus@fischer.name>
