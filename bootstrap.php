<?php
# Try to locate composer autoloader
$foundAutoload = false;
foreach (['/../../autoload.php', '/vendor/autoload.php'] as $relDir) {
  if (file_exists(__DIR__ . $relDir)) {
    require __DIR__ . $relDir;
    $foundAutoload = true;
    break;
  }
}

if (!$foundAutoload) {
  echo 'Unable to find composer infrastructure, please see' . PHP_EOL;
  echo 'https://github.com/mfn/cakephp2-magic-properties for installation.' . PHP_EOL;
  exit(1);
}
