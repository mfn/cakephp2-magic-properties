# Parse CakePHP2 controllers source files and add magic property PHPDOC [ ![Travis Build Status](https://travis-ci.org/mfn/cakephp2-magic-properties.svg?branch=master)](https://travis-ci.org/mfn/cakephp2-magic-properties)

Homepage: https://github.com/mfn/cakephp2-magic-properties

# Blurb

When working with [CakePHP2](http://cakephp.org/), dependency injection happens via some magic properties like `uses`, `helpers` and `components`.

Unfortunately not many editors/IDEs are capable of understanding this special syntax and provide autocompletion for the resulting magic properties.

By running this script against controller sources, PHPDOC `@property` is addded to the class definitions which aids into autocompletion the propert types.

This code uses the excellent [PhpParser library](https://github.com/nikic/PHP-Parser) by Nikita Popov.

# Usage

`cakephp2_magic_properties --controller app/Controller/*Controller.php`
or
`cakephp2_magic_properties --helper app/View/Helper/*Helper.php`

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

See the `--help` flag for other options. The library is designed to act idempotent.

Be aware that no checks are done whether the class is actually a CakePHP2 controller/helper or not.

# Install

Because it's using `nikic/php-parser` which as of yet has no stable release, that package has to be installed manually:
```
composer.phar require nikic/php-parser 1.0.0beta1@dev
composer.phar require mfn/cakephp2-magic-properties 0.0.5
```

= TODOs / Ideas
- Sync properties. I.e. if a '@property' exists in PHPDOC but is not present anymore in the class, remove it.<br>Needs to be considered carefully, because there may be other magic properties documented with reasons the script wouldn't know about.
- Remove the need to tell if we're processing Controllers, Helpers, etc. The script should be able to figure this out on its own.
- Support for Tests?

© Markus Fischer <markus@fischer.name>
