# CakePHP2 magic properties CHANGELOG

### 0.1.1 - 28 October 2014
- Added
  - Use changed source files as optional control for shell/phing
  - Added `dryRun` to phing task
- Internal
  - Removed getters on phing task (not needed ATM)
  - Relax composer version restriction in favour of semver

### 0.1.0 - 26 October 2014
- Added
  - phing task supports the `removeUnknownProperties` attribute now

### 0.0.9 - 25 October 2014
- Added
  - Support for special properties on `Component` and `Model`
  - Support for running as phing task
  - Support single-line doc comments; they will turned into multi-line
- Internal
  - Switched to symfony/console
  - Rewrote core mechanics to be used as library; evident now as we've a console
    as well as a phing task runner.

### 0.0.8 - 23th October 2014
- Fixed locating composer autoload

### 0.0.7 - 14th September 2014
- Added
  - Support for models in shells

### 0.0.6 - 14th September 2014
- Changed
  - Changed namespace and shuffled file layout to reduce unneccesary directory layers
  - Rewrote algorithm to automatically detect Controller/Helper classes
  - Upgraded php-parser (stable version) and getopt-php (custom banner support)
- Added
  - Flag `--remove` which removes all existing `@property` before syncing with special properties

### 0.0.5 - 31th August 2014
- Added
  - Support components with configurations

### 0.0.4 - 31th August 2014
- Fixed
  - Don't treat all classes equally. A flag must now be specified which triggers which properties are considered for PHPDOC

### 0.0.3 - 30th August 2014
- Trying to figure out problems with packagist, still

### 0.0.2 - 30th August 2014
- Trying to figure out problems with packagist

### 0.0.1 - 30th August 2014
- Initial release
