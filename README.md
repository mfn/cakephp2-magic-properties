# Parse CakePHP2 controllers source files and add magic property PHPDOC [ ![Travis Build Status](https://travis-ci.org/mfn/cakephp2-magic-properties.svg?branch=master)](https://travis-ci.org/mfn/cakephp2-magic-properties)

Homepage: https://github.com/mfn/cakephp2-magic-properties

# Blurb

When working with [CakePHP2](http://cakephp.org/), dependency injection happens via magic properties like `uses`, `helpers` and `components`.

Unfortunately not many editors/IDEs are capable of understanding this special syntax and thus almost none provide autocompletion for the resulting magic properties.

By running this script against your CakePHP2 sources, PHPDOC `@property` is addded to the class definitions which aids into autocompletion the propert types.

Just pass any files/directories to the script and they will be parsed and PHPDOC properties added. Note that every class will be resolved via it's parents to figure out if it's a Controller or Helper, thus the script must be able to find all relevant parent classes to properly resolve them. Usually, just passing your Controller/ and/or Helper/ direcotries should do fine.

This code uses the excellent [PhpParser library](https://github.com/nikic/PHP-Parser) by Nikita Popov.

# Usage

`cakephp2_magic_properties app/Controller/`
or
`cakephp2_magic_properties app/View/Helper/`
or just your whole app (note that it will recursively parse all *.php files):
`cakephp2_magic_properties ../path/to/your/app/`

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

# Install

Because it's using `nikic/php-parser` which as of yet has no stable release, that package has to be installed manually:
```
composer.phar require nikic/php-parser 1.0.0beta1@dev
composer.phar require mfn/cakephp2-magic-properties 0.0.5
```

# TODOs / Ideas
- Sync properties. I.e. if a '@property' exists in PHPDOC but is not present anymore in the class, remove it.<br>Needs to be considered carefully, because there may be other magic properties documented with reasons the script wouldn't know about.
- Support for Tests?
- Ensure we're working with multi-line php doc comment, otherwise treat as there is none

© Markus Fischer <markus@fischer.name>
