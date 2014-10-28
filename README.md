# Automatically add class documentation for CakePHP2 magic properties [ ![Travis Build Status](https://travis-ci.org/mfn/cakephp2-magic-properties.svg?branch=master)](https://travis-ci.org/mfn/cakephp2-magic-properties)

Homepage: https://github.com/mfn/cakephp2-magic-properties

# Blurb

When working with [CakePHP2](http://cakephp.org/), dependency injection happens via magic properties like `uses`, `helpers`, etc.

Unfortunately not many editors/IDEs are capable of understanding this special syntax and thus almost none provide autocompletion for the resulting magic properties.

By running this script against your CakePHP2 sources, PHPDOC `@property` is addded to the class definitions which aids into autocompletion the propert types.

Just pass any files/directories to the script and they will be parsed and PHPDOC properties added. Note that every class will be resolved via it's parents to figure out if it's a Controller or Helper, thus the script must be able to find all relevant parent classes to properly resolve them. Usually, just passing your Controller/ and/or Helper/ direcotries should do fine.

This code uses the excellent [PhpParser library](https://github.com/nikic/PHP-Parser) by Nikita Popov.

# What injected properties are supported?

- `Controller`
  - `components`
  - `uses`
- `Component`
  - `components`
- `Helper`
  - `components`
  - `helpers`
  - `uses`
- `Model`
  - `belingsTo`
  - `hasAndBelongsToMany`
  - `hasOne`
  - `hasMany`
- `Shell`
  - `uses`

The `className` setting is also supported.

For details please see `res/configuration.php`.

# Usage

`cakephp2_magic_properties magic app/Controller/`

or

`cakephp2_magic_properties magic app/View/Helper/`

or just your whole app (note that it will recursively parse all \*.php files):

`cakephp2_magic_properties magic ../path/to/your/app/`

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

By default, existing properties will not be touched but care is taken to not create duplicates. If however you want to remove all existing `@property` documentation, use the `--remove` flag `cakephp2_magic_properties magic --remove ../path/to/your/app/`:
```PHP
/**
 * @property BarComponent $Bar
 * @property Foo $Foo
 */
AppController extends Controller {
  $component = ['Bar'];
}
```
into
```PHP
/**
 * @property BarComponent $Bar
 */
AppController extends Controller {
  $component = ['Bar'];
}
```
Note: it may also cause to re-order your existing properties if none have really changed (because interally first all properties are always removed).

See the `--help` flag for other options. The library is designed to act idempotent.

## Integration with phing

A phing task is also provided for better phing integration. Use `taskdef` to define a new custom task.

```XML
  <taskdef name="cakephp2-magic-properties" classname="Mfn\CakePHP2\MagicProperty\Runner\PhingTask"/>

  <target name="generate">
    <cakephp2-magic-properties>
      <fileset dir="/path/to/your/cake/app/">
        <include name="**/*.php"/>
        <exclude name="Config/**"/>
        <exclude name="Plugin/**"/>
        <exclude name="Vendor/**"/>
      </fileset>
    </cakephp2-magic-properties>
  </target>
```

The following attributes are supported:
- `configFile`<br>Path to configuration file, see next chapter
- `dryRun`<br>Whether to actually write changes to files; defaults to `false` which means *overwrite* the files.
  This is useful in combination with `haltOnSourcesChanged`
- `haltOnSourcesChanged`<br>Whether to abort the build if any source files have been changed.
- `removeUnknownProperties`<br>Whether to remove unknown `@property` declarations

Note: due the use of namespaces this will only work properly if phing is used via composer too.

# Configuration file

In `res/configuration.php` the projects default configuration is provided which all runners allow to override.

This file contains the mapping:

1. from "top level class" to properties
2. and those properties map to a closure which transform the name of a injected virtual property

The closure approach is required because e.g. `helpers` and `components` have different rules how the actual class names have to be called.

# Requirements

- PHP 5.4 (tested with at least 5.4.24)

# Installation

```
composer.phar require mfn/cakephp2-magic-properties 0.1.1
```

# TODOs / Ideas
- Support for Tests?

© Markus Fischer <markus@fischer.name>
