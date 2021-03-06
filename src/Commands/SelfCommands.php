<?php

namespace drunomics\Phapp\Commands;

use drunomics\Phapp\Exception\LogicException;
use drunomics\Phapp\PhappCommandBase;
use Humbug\SelfUpdate\Strategy\GithubStrategy;
use Humbug\SelfUpdate\Updater;
use Robo\Robo;

/**
 * Supports self-updating phars.
 */
class SelfCommands extends PhappCommandBase {

  /**
   * {@inheritdoc}
   */
  protected $requiresPhappManifest = FALSE;

  /**
   * Updates the installed phar.
   *
   * @option bool $unstable Allows updating to unstable releases.
   *
   * @command self:update
   */
  public function selfUpdate($options = ['unstable' => FALSE]) {
    $updater = new Updater(NULL, FALSE);
    $updater->setStrategy(Updater::STRATEGY_GITHUB);
    $strategy = $updater->getStrategy();
    /** @var \Humbug\SelfUpdate\Strategy\GithubStrategy $strategy */
    $strategy->setPackageName('drunomics/phapp-cli');
    $strategy->setPharName('phapp.phar');
    $strategy->setCurrentLocalVersion(Robo::application()->getVersion());
    $strategy->setStability($options['unstable'] ? GithubStrategy::ANY : GithubStrategy::STABLE);

    if (!\Phar::running()) {
      throw new LogicException("Unable to self-update if the application is not installed as phar.");
    }

    if ($updater->update()) {
      $this->say("Application updated to version " . $updater->getNewVersion());
      // Phar cannot load more classes after the update has occurred. So to
      // avoid errors from classes loaded after this (e.g.
      // ConsoleTerminateEvent), we exit directly now.
      exit(0);
    }
    else {
      $version = $updater->getNewVersion();
      $this->say("Most recent version $version installed.");
    }
  }

  /**
   * Rolls back to the previous version after a self-update.
   *
   * @command self:rollback
   */
  public function rollback() {
    $updater = new Updater();
    if ($result = $updater->rollback()) {
      // Phar cannot load more classes after the update has occurred. So to
      // avoid errors from classes loaded after this (e.g.
      // ConsoleTerminateEvent), we exit directly now.
      $this->say("Application rolled back to version " . $updater->getOldVersion());
      exit(0);
    }
  }
}
