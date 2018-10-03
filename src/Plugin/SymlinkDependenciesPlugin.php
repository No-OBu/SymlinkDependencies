<?php

namespace No_OBu\Plugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginEvents;
use Composer\EventDispatcher\Event;
use Composer\Plugin\PluginInterface;
use Composer\Installer\InstallationManager;
use Composer\EventDispatcher\EventSubscriberInterface;

use No_OBu\Installer\SymlinkInstaller;

/**
 * SymlinkDependenciesPlugin Class.
 */
class SymlinkDependenciesPlugin implements PluginInterface, EventSubscriberInterface
{
    const BUNDLE_MAP_PATH = '%s/../bundle.map.php';
    const APP_BUNDLE_MAP_PATH = '%s/../bundles/bundle.map.php';

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * __construct method : SymlinkDependenciesPlugin constructor.
     *
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * getSubscribedEvents method : Required method for composer plugin, hooked the init event
     */
    public static function getSubscribedEvents()
    {
        return array(
            'init' => array('overrideInstallationManager', 100)
        );
    }

    /**
     * overrideInstallationManager method : This method is call on composer init to override the default installer.
     *
     * @param Event $event.
     */
    public function overrideInstallationManager(Event $event)
    {
        // Load the bundle list for symlink installation
        $bundleMap = sprintf(self::BUNDLE_MAP_PATH, getcwd());
        $bundleMapApp = sprintf(self::APP_BUNDLE_MAP_PATH, getcwd());
        if (file_exists($bundleMap)) {
            $bundles = include($bundleMap);
        } elseif (file_exists($bundleMapApp)) {
            $bundles = include($bundleMapApp);
        } else {
            $bundles = [];
        }

        // if the bundles list is empty, we have noting to do.
        if (empty($bundles)) {
            return;
        }

        // Override the default installer manager for composer with the SymlinkInstaller on it
        $symlinkInstaller = new SymlinkInstaller($this->composer, $this->io);
        $symlinkInstaller->setDefaultInstallationManager($this->composer->getInstallationManager());
        $symlinkInstaller->setMappedBundle($bundles);
        $newInstallationManager = new InstallationManager;
        $newInstallationManager->addInstaller($symlinkInstaller);
        $this->composer->setInstallationManager($newInstallationManager);
    }
    
    /**
     * log method : Simple log method for debugging.
     *
     * @param string $message
     *
     * @return $this
     */
    protected function log($message)
    {
        if ($this->io) {
            $this->io->write($message);
        }

        return $this;
    }
}
