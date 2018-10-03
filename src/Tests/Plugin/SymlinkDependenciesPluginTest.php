<?php

namespace No_OBu\Tests\Plugin;

use Composer\Config;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\EventDispatcher\Event;
use Composer\Installer\InstallationManager;
use PHPUnit\Framework\TestCase;

use No_OBu\Plugin\SymlinkDependenciesPlugin;

class SymlinkDependenciesPluginTest extends TestCase
{
    const TEST_DIR = __DIR__."/../../../.test";
    const TEST_DIR_APP = self::TEST_DIR."/lambdaApp";
    const TEST_DIR_BUNDLES = self::TEST_DIR."/bundles";
    const TEST_DIR_BUNDLE = self::TEST_DIR_BUNDLES."/lambdaBundle";
    const TEST_FILE = self::TEST_DIR_BUNDLES.'/bundle.map.php';

    const TEMPLATE_EMPTY_MAP =  '<?php return [];';
    const TEMPLATE_MAP = '<?php return ["key_bundle_1" => "bundle1", "key_bundle_2" => "bundle2", "key_bundle_3" => "bundle3"];';

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var string
     */
    protected $currentDir;

    public function __construct()
    {
        parent::__construct();
        $this->currentDir = getcwd();
    }

    public function testNoMap()
    {
        $this->composer
            ->expects($this->never())
            ->method('setInstallationManager');

        $symPlugin = new SymlinkDependenciesPlugin();
        $symPlugin->activate($this->composer, $this->io);
        $symPlugin->overrideInstallationManager($this->event);
    }

    public function testAppMap()
    {
        $this->composer
            ->expects($this->once())
            ->method('setInstallationManager')
            ->with($this->isInstanceOf(InstallationManager::class));

        if (!file_exists(self::TEST_DIR_APP)) {
            throw new RuntimeException('');
        }

        chdir(self::TEST_DIR_APP);
        file_put_contents(self::TEST_FILE, self::TEMPLATE_MAP);

        $symPlugin = new SymlinkDependenciesPlugin();
        $symPlugin->activate($this->composer, $this->io);
        $symPlugin->overrideInstallationManager($this->event);
    }

    public function testAppEmptyMap()
    {
        $this->composer
            ->expects($this->never())
            ->method('setInstallationManager')
            ->with($this->isInstanceOf(InstallationManager::class));

        if (!file_exists(self::TEST_DIR_APP)) {
            throw new RuntimeException('');
        }

        chdir(self::TEST_DIR_APP);
        file_put_contents(self::TEST_FILE, self::TEMPLATE_EMPTY_MAP);

        $symPlugin = new SymlinkDependenciesPlugin();
        $symPlugin->activate($this->composer, $this->io);
        $symPlugin->overrideInstallationManager($this->event);
    }

    public function testBundleMap()
    {
        $this->composer
            ->expects($this->once())
            ->method('setInstallationManager')
            ->with($this->isInstanceOf(InstallationManager::class));

        if (!file_exists(self::TEST_DIR_BUNDLE)) {
            throw new RuntimeException('This bundle directoru does not exist.');
        }

        chdir(self::TEST_DIR_BUNDLE);
        file_put_contents(self::TEST_FILE, self::TEMPLATE_MAP);

        $symPlugin = new SymlinkDependenciesPlugin();
        $symPlugin->activate($this->composer, $this->io);
        $symPlugin->overrideInstallationManager($this->event);
    }

    public function testBundleEmptyMap()
    {
        $this->composer
            ->expects($this->never())
            ->method('setInstallationManager')
            ->with($this->isInstanceOf(InstallationManager::class));

        if (!file_exists(self::TEST_DIR_BUNDLE)) {
            throw new RuntimeException('This bundle directoru does not exist.');
        }

        chdir(self::TEST_DIR_BUNDLE);
        file_put_contents(self::TEST_FILE, self::TEMPLATE_EMPTY_MAP);

        $symPlugin = new SymlinkDependenciesPlugin();
        $symPlugin->activate($this->composer, $this->io);
        $symPlugin->overrideInstallationManager($this->event);
    }

    public function setUp()
    {
        $config = $this->createMock(Config::class);
        $config->method('get')
            ->will($this->returnCallback(function ($var) {
                if ('bin-dir' === $var) {
                    return 'vendor/bin';
                } elseif ('vendor-dir' === $var) {
                    return 'vendor';
                } elseif ('bin-compar' === $var) {
                    return 'auto';
                }

                return 'default';
            }));
        $installalionManager = $this->createMock(InstallationManager::class);
        $this->composer = $this->createMock(Composer::class);
        $this->composer
            ->method('getConfig')
            ->willReturn($config);
        $this->composer
            ->method('getInstallationManager')
            ->willReturn($installalionManager);
        $this->io = $this->createMock(IOInterface::class);
        $this->event = $this->createMock(Event::class);
        $this->createTestDir();
    }

    public function tearDown()
    {
        unset(
            $this->composer,
            $this->io,
            $this->event
        );
        $this->cleanTestDir();
    }

    protected function createTestDir()
    {
        if (file_exists(self::TEST_DIR)) {
            $this->cleanTestFile();
        } elseif (!@mkdir(self::TEST_DIR)) {
            throw new RuntimeException('Impossible to create test directory.');
        }

        mkdir(self::TEST_DIR_APP);
        mkdir(self::TEST_DIR_BUNDLES);
        mkdir(self::TEST_DIR_BUNDLE);
    }

    protected function cleanTestDir()
    {
        if (!file_exists(self::TEST_DIR)) {
            return;
        }
        chdir($this->currentDir);
        $this->cleanTestFile();
        rmdir(self::TEST_DIR);
    }

    private function cleanTestFile()
    {
        if (!file_exists(self::TEST_DIR)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                self::TEST_DIR,
                \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                rmdir($fileinfo->getRealPath());
            } elseif (file_exists($fileinfo->getRealPath())) {
                unlink($fileinfo->getRealPath());
            }
        }
    }
}
