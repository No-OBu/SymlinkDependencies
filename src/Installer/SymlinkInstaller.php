<?php

namespace No_OBu\Installer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Composer\Package\PackageInterface;
use Composer\Installer\InstallerInterface;
use Composer\Installer\InstallationManager;
use Composer\Installer\BinaryPresenceInterface;
use Composer\Installer\BinaryInstaller;
use Composer\Repository\InstalledRepositoryInterface;

/**
 * SymlinkInstaller class
 */
class SymlinkInstaller implements InstallerInterface, BinaryPresenceInterface
{

    /**
     * @var InstallationManager
     */
    protected $defaultInstallationManager;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var BinaryInstaller
     */
    protected $binaryInstaller;

    /**
     * @var array
     */
    protected $bundles = [];

    /**
     * @var string
     */
    protected $vendorDir;

    /**
     * __construct method : SymlinkInstaller constructor.
     *
     * @param Composer $composer
     * @param IOInterface $io
     * @param Filesystem $filesystem
     * @param BinaryInstaller $binaryInstaller
     */
    public function __construct(Composer $composer, IOInterface $io, Filesystem $filesystem = null, BinaryInstaller $binaryInstaller = null)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->fs = $filesystem ?? new Filesystem();
        $this->binaryInstaller = $binaryInstaller ?? new BinaryInstaller($this->io, rtrim($composer->getConfig()->get('bin-dir'), '/'), $composer->getConfig()->get('bin-compat'), $this->fs);
        $this->vendorDir = rtrim($composer->getConfig()->get('vendor-dir'), '/');
    }

    /**
     * setDefaultInstallationManager method : Set defaultInstallationManager for fallback to real installationManager for bundle not installed with symlink.
     * @param InstallationManager $defaultInstallationManager
     *
     * @return $this
     */
    public function setDefaultInstallationManager(InstallationManager $defaultInstallationManager)
    {
        // use the defaultInstallationManager to fallback for "no-symlinkable" package
        $this->defaultInstallationManager = $defaultInstallationManager;

        return $this;
    }

    /**
     * setMappedBundle method : Set the bundle mapping for symlink installation.
     *
     * @var array $bundleMap
     *
     * @return $this
     */
    public function setMappedBundle(array $bundleMap)
    {
        $this->bundles = $bundleMap;

        return $this;
    }

    /**
     * supports method : Return if this installer support this package (always true for overriding).
     *
     * @param string $packageType
     */
    public function supports($packageType)
    {
        // Support all file and fallback to the default installer if not symlinks
        return true;
    }

    /**
     * isInstalled method : Return if a package is already installed.
     *
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface $package
     *
     * @return bool
     */
    public function isInstalled(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        if (!$repo->hasPackage($package)) {
            return false;
        }

        return $this->defaultInstallationManager->getInstaller($package->getType())->isInstalled($repo, $package);
    }

    /**
     * install method : Install source for given package.
     *
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface $package
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        if (!$this->isSymlinkBundle($package)) {
            return $this->defaultInstallationManager->getInstaller($package->getType())->install($repo, $package);
        }
        $this->initVendorDir();
        $bundlePath = $this->bundles[$package->getPrettyName()];
        $installPath = $this->getInstallPath($package);
        $parentInstallPath = dirname($installPath);
        !file_exists($parentInstallPath) && mkdir($parentInstallPath, 0755, true);
        symlink($bundlePath, $installPath);
        $this->binaryInstaller->installBinaries($package, $installPath);
        if (!$repo->hasPackage($package)) {
            $repo->addPackage(clone $package);
        }
    }

    /**
     * uninstall method : Delete source for given package.
     *
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface $package
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        if (!$this->isSymlinkBundle($package)) {
            return $this->defaultInstallationManager->getInstaller($package->getType())->uninstall($repo, $package);
        }
        $installPath = $this->getInstallPath($package);
        unlink($installPath);
        $this->binaryInstaller->removeBinaries($package);
        $repo->removePackage($package);
    }

    /**
     * update method : Update source for given package.
     *
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface $initial
     * @param PackageInterface $target
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        if (!$this->isSymlinkBundle($target)) {
            return $this->defaultInstallationManager->getInstaller($target->getType())->update($repo, $initial, $target);
        }
        $this->initVendorDir();
        $bundlePath = $this->bundles[$target->getPrettyName()];
        $installPath = $this->getInstallPath($target);
        $parentInstallPath = dirname($installPath);
        !file_exists($parentInstallPath) && mkdir($parentInstallPath, 0755, true);
        unlink($installPath);
        symlink($bundlePath, $installPath);
        $this->binaryInstaller->installBinaries($target, $installPath);
        if (!$repo->hasPackage($target)) {
            $repo->addPackage(clone $target);
        }
    }

    /**
     * getInstallPath method : Return the install path for a given package (using default installer).
     *
     * @param PackageInterface $package
     *
     * @return string
     */
    public function getInstallPath(PackageInterface $package)
    {
        return $this->defaultInstallationManager->getInstaller($package->getType())->getInstallPath($package);
    }

    /**
     * ensureBinariesPresence method : Use default installer to check if binary must be installed for a given package.
     *
     * @param PackageInterface $package
     */
    public function ensureBinariesPresence(PackageInterface $package)
    {
        $defaultInstaller =  $this->defaultInstallationManager->getInstaller($package->getType());

        if ($defaultInstaller instanceof BinaryPresenceInterface) {
            $defaultInstaller->ensureBinariesPresence($package);
        }
    }

    /**
     * isSymlinkBundle method : Return if a given package must be installed with symlink.
     *
     * @param PackageInterface $package
     *
     * @return bool
     */
    protected function isSymlinkBundle(PackageInterface $package)
    {
        return array_key_exists($package->getPrettyName(), $this->bundles);
    }

    /**
     * initVendorDir method : Create vendor dir if not exists.
     */
    protected function initVendorDir()
    {
        $this->fs->ensureDirectoryExists($this->vendorDir);
        $this->vendorDir = realpath($this->vendorDir);
    }
}
