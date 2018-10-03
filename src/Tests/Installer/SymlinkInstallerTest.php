<?php

namespace No_OBu\Tests\Installer;

use PHPUnit\Framework\TestCase;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Composer\Installer\BinaryInstaller;
use Composer\Config;
use Composer\Installer\InstallationManager;

use No_OBu\Installer\SymlinkInstaller;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Package\PackageInterface;
use Composer\Installer\InstallerInterface;

class SymlinkInstallerTest extends TestCase
{
    protected $symlinkInstaller;
    protected $fs;

    public function testTooFewArgumentSetDefaultInstallerManager()
    {
        $this->expectException(\ArgumentCountError::class);
        $this->symlinkInstaller->setDefaultInstallationManager();
    }

    public function testTypeErrorArgumentSetDefaultInstallerManager()
    {
        $this->expectException(\TypeError::class);
        $this->symlinkInstaller->setDefaultInstallationManager(new \StdClass);
    }

    public function testSetDefaultInstallerManager()
    {
        $this->assertEquals(
            $this->symlinkInstaller,
            $this->symlinkInstaller->setDefaultInstallationManager(
                $this->createMock(InstallationManager::class)
            )
        );
    }

    public function testTooFewArgumentSupports()
    {
        $this->expectException(\ArgumentCountError::class);
        $this->symlinkInstaller->supports();
    }

    public function testSupports()
    {
        $this->assertTrue($this->symlinkInstaller->supports('library'));
    }

    public function testTooFewArgumentSetMappedBundle()
    {
        $this->expectException(\ArgumentCountError::class);
        $this->symlinkInstaller->setMappedBundle();
    }

    public function testTypeErrorArgumentSetMappedBundle()
    {
        $this->expectException(\TypeError::class);
        $this->symlinkInstaller->setMappedBundle(42);
    }

    public function testSetMappedBundle()
    {
        $this->assertEquals(
            $this->symlinkInstaller,
            $this->symlinkInstaller->setMappedBundle([])
        );
    }

    public function testTooFewArgumentIsInstalled()
    {
        $this->expectException(\ArgumentCountError::class);
        $this->symlinkInstaller->isInstalled();
    }

    public function testTooFewArgumentIsInstalledBis()
    {
        $this->expectException(\ArgumentCountError::class);
        $this->symlinkInstaller->isInstalled(
            $this->createMock(InstalledRepositoryInterface::class)
        );
    }

    public function testTypeErrorArgumentIsInstalled()
    {
        $this->expectException(\TypeError::class);
        $this->symlinkInstaller->isInstalled(new \stdClass, new \stdClass);
    }

    public function testIsInstalledTrue()
    {
        list($repo, $package) = $this->prepareIsInstalledAssertion(true);
        $this->assertTrue(
            $this->symlinkInstaller->isInstalled($repo, $package)
        );
    }

    public function testIsInstalledFalse()
    {
        list($repo, $package) = $this->prepareIsInstalledAssertion(false);
        $this->assertFalse(
            $this->symlinkInstaller->isInstalled($repo, $package)
        );
    }

    public function testIsInstalledFalseBis()
    {
        $package = $this->createMock(PackageInterface::class);
        $repo = $this->createMock(InstalledRepositoryInterface::class);
        $repo->method('hasPackage')
            ->will($this->returnCallback(function ($var) use ($package) {
                return $package !== $var;
            }));

        $this->assertFalse(
            $this->symlinkInstaller->isInstalled($repo, $package)
        );
    }

    public function testTooFewArgumentInstall()
    {
        $this->expectException(\ArgumentCountError::class);
        $this->symlinkInstaller->install();
    }

    public function testTooFewArgumentInstallBis()
    {
        $this->expectException(\ArgumentCountError::class);
        $this->symlinkInstaller->install(
            $this->createMock(InstalledRepositoryInterface::class)
        );
    }

    public function testTypeErrorInstall()
    {
        $this->expectException(\TypeError::class);
        $this->symlinkInstaller->install(new \StdClass, new \stdClass);
    }

    public function testDefaultInstallTrue()
    {
        list($repo, $package) = $this->prepareInstallerdAssertion('install', true);
        $this->symlinkInstaller->setMappedBundle(['budle1' => 'path/to/bundle']);
        $this->assertTrue(
            $this->symlinkInstaller->install($repo, $package)
        );
    }

    public function testDefaultInstallBis()
    {
        $realFs = new Filesystem();
        $this->fs
            ->expects($this->once())
            ->method('ensureDirectoryExists')
            ->will($this->returnCallback(function ($var) use ($realFs) {
                return $realFs->ensureDirectoryExists($var);
            }));
        list($repo, $package) = $this->prepareInstallerdPathAssertion('install', true);
        $package
            ->expects($this->exactly(2))
            ->method('getPrettyName')
            ->willReturn('bundle2');
        $repo
            ->expects($this->once())
            ->method('hasPackage')
            ->with($package)
            ->willReturn(false);
        $repo
            ->expects($this->once())
            ->method('addPackage')
            ->with($this->equalTo($package));
        $this->symlinkInstaller->setMappedBundle(['bundle2' => __DIR__.'/../../../.test/path/to/bundle']);
        $this->symlinkInstaller->install($repo, $package);
        $this->assertTrue(
            file_exists($bundleDir = realpath(__DIR__.'/../../../').'/.test/_vendor/path/to/bundle')
        );
        unlink($bundleDir);
    }

    public function testTooFewArgumentGetInstallPath()
    {
        $this->expectException(\ArgumentCountError::class);
        $this->symlinkInstaller->install();
    }

    public function testTypeErrorGetInstallPath()
    {
        $this->expectException(\TypeError::class);
        $this->symlinkInstaller->install(new \StdClass);
    }

    public function testGetInstallPath()
    {
        $package = $this->prepareInstallPathAssertion($path = 'path/to/package');
        $this->assertEquals(
            $path,
            $this->symlinkInstaller->getInstallPath($package)
        );
    }

    public function testUninstallFallback()
    {
        $this->markTestIncomplete();
    }

    public function testUninstall()
    {
        $this->markTestIncomplete();
    }

    public function testUpdateFallback()
    {
        $this->markTestIncomplete();
    }

    public function testUpdate()
    {
        $this->markTestIncomplete();
    }

    public function testEnsureBinariesPresence()
    {
        $this->markTestIncomplete();
    }

    public function setUp()
    {
        !file_exists(realpath(__DIR__.'/../../../').'/.test/') && mkdir(realpath(__DIR__.'/../../../').'/.test/', 0777);
        !file_exists(realpath(__DIR__.'/../../../').'/.test/path/to/bundle') && mkdir(realpath(__DIR__.'/../../../').'/.test/path/to/bundle', 0777, true);
        $config = $this->createMock(Config::class);
        $config
            ->expects($this->once())
            ->method('get')
            ->will($this->returnCallback(function ($var) {
                if ('bin-dir' === $var) {
                    return realpath(__DIR__.'/../../../').'/.test/_vendor/bin';
                } elseif ('vendor-dir' === $var) {
                    return realpath(__DIR__.'/../../../').'/.test/_vendor';
                } elseif ('bin-compar' === $var) {
                    return 'auto';
                }

                return 'default';
            }));
        $composer = $this->createMock(Composer::class);
        $composer
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);
        $io = $this->io = $this->createMock(IOInterface::class);
        $this->fs = $this->createMock(Filesystem::class);
        $binary = $this->createMock(BinaryInstaller::class);
        $this->symlinkInstaller = new SymlinkInstaller(
            $composer,
            $io,
            $this->fs,
            $binary
        );
    }

    public function tearDown()
    {
        unset($this->symlinkInstaller);
        if (file_exists($bundleDir = realpath(__DIR__.'/../../../').'/.test/_vendor/path/to/bundle')) {
            unlink($bundleDir);
        }
        $this->clearDir(realpath(__DIR__.'/../../../').'/.test');
    }

    protected function prepareIsInstalledAssertion($returnValue)
    {
        list($repo, $package) = $this->prepareInstallerdAssertion('isInstalled', $returnValue);
        $repo
            ->expects($this->once())
            ->method('hasPackage')
            ->will($this->returnCallback(function ($var) use ($package) {
                return $package === $var;
            }));

        return [$repo, $package];
    }

    protected function prepareInstallerdAssertion($method, $returnValue)
    {
        $package = $this->createMock(PackageInterface::class);
        $package
            ->expects($this->once())
            ->method('getType')
            ->willReturn('library');
        $repo = $this->createMock(InstalledRepositoryInterface::class);
        
        $installer = $this->createMock(InstallerInterface::class);
        $installer
            ->expects($this->once())
            ->method($method)
            ->willReturn($returnValue);
        $installationManager = $this->createMock(InstallationManager::class);
        $installationManager
            ->expects($this->once())
            ->method('getInstaller')
            ->will($this->returnCallback(function ($var) use ($installer) {
                if ($var !== 'library') {
                    throw new Exception('Assert Failed');
                }

                return $installer;
            }));
        $this->symlinkInstaller->setDefaultInstallationManager($installationManager);

        return [$repo, $package];
    }

    protected function prepareInstallerdPathAssertion($method, $returnValue)
    {
        $package = $this->createMock(PackageInterface::class);
        $package
            ->expects($this->once())
            ->method('getType')
            ->willReturn('library');
        $repo = $this->createMock(InstalledRepositoryInterface::class);
        
        $installer = $this->createMock(InstallerInterface::class);
        $installer
            ->expects($this->once())
            ->method('getInstallPath')
            ->will($this->returnCallback(function ($var) use ($package) {
                if ($var !== $package) {
                    throw new Exception('Assert Failed');
                }

                return __DIR__."/../../../.test/_vendor/path/to/bundle";
            }));
        $installer
            ->expects($this->never())
            ->method($method)
            ->willReturn($returnValue);
        $installationManager = $this->createMock(InstallationManager::class);
        $installationManager
            ->expects($this->once())
            ->method('getInstaller')
            ->will($this->returnCallback(function ($var) use ($installer) {
                if ($var !== 'library') {
                    throw new Exception('Assert Failed');
                }

                return $installer;
            }));
        $this->symlinkInstaller->setDefaultInstallationManager($installationManager);

        return [$repo, $package];
    }

    protected function prepareInstallPathAssertion($path)
    {
        $package = $this->createMock(PackageInterface::class);
        $package
            ->expects($this->once())
            ->method('getType')
            ->willReturn('library');
        
        $installer = $this->createMock(InstallerInterface::class);
        $installer
            ->expects($this->once())
            ->method('getInstallPath')
            ->will($this->returnCallback(function ($var) use ($package, $path) {
                if ($var !== $package) {
                    throw new Exception('Assert Failed');
                }

                return $path;
            }));
        $installationManager = $this->createMock(InstallationManager::class);
        $installationManager
            ->expects($this->once())
            ->method('getInstaller')
            ->will($this->returnCallback(function ($var) use ($installer) {
                if ($var !== 'library') {
                    throw new Exception('Assert Failed');
                }

                return $installer;
            }));
        $this->symlinkInstaller->setDefaultInstallationManager($installationManager);

        return $package;
    }

    protected function clearDir($dir)
    {
        if (file_exists($dir) && is_dir($dir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $dir,
                    \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS
                ),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $file) {
                if (!$file->getRealPath()) {
                    continue;
                }
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
        }
    }
}
