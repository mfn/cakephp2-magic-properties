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
namespace Mfn\CakePHP2\MagicProperty\Runner;

use Mfn\CakePHP2\MagicProperty\Logger\Phing;
use Mfn\CakePHP2\MagicProperty\MagicProperty;

/**
 * Provide a phing task.
 *
 * How to use in phing:
 *
 *   <taskdef name="cakephp2-magic-properties" classname="Mfn\CakePHP2\MagicProperty\Runner\PhingTask"/>
 *
 *   <target name="generate">
 *     <cakephp2-magic-properties>
 *     <fileset dir="/dir/which/contains/your/cake/app/">
 *       <exclude name="Vendir/**"/>
 *     </fileset>
 *   </cakephp2-magic-properties>
 *   </target>
 *
 * Make sure that composer is properly set up, otherwise phing will not be able
 * to properly resolve the namespaces phing task.
 *
 * @author Markus Fischer <markus@fischer.name>
 */
class PhingTask extends \Task {

  /** @var \FileSet[] */
  private $filesets;
  /** @var \PhingFile */
  private $configFile = NULL;

  public function addFileSet(\FileSet $fs) {
    $this->filesets[] = $fs;
  }

  public function main() {
    if (NULL === $this->filesets) {
      throw new \BuildException('No fileset provided');
    }

    $project = new MagicProperty(new Phing($this));

    if (NULL !== $this->getConfigFile()) {
      $project->setConfigurationFromFile($this->getConfigFile());
    }

    # Add files
    foreach ($this->filesets as $fs) {
      $ds = $fs->getDirectoryScanner($this->project);
      /** @var \PhingFile $fromDir */
      $fromDir = $fs->getDir($this->project);
      /** @var  $files */
      $files = $ds->getIncludedFiles();
      foreach ($files as $file) {
        $fileName = $fromDir->getAbsolutePath() . DIRECTORY_SEPARATOR . $file;
        $this->log('Adding file ' . $fileName, \Project::MSG_VERBOSE);
        $project->addSource($fileName);
      }
    }

    $project->applyMagic();
  }

  /**
   * @return \PhingFile
   */
  public function getConfigFile() {
    return $this->configFile;
  }

  /**
   * @param \PhingFile $configFile
   * @return $this
   */
  public function setConfigFile($configFile) {
    $this->configFile = $configFile;
    return $this;
  }
}
