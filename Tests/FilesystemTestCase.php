<?php
/** @noinspection StaticClosureCanBeUsedInspection */

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  FileManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/filemanager
 * @since     Version 0.1
 */

namespace BiuradPHP\FileManager\Tests;

use BiuradPHP\FileManager\Config\FileConfig;
use BiuradPHP\FileManager\FileManager;
use BiuradPHP\FileManager\Interfaces\FileManagerInterface;
use Closure;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\AbstractCache;
use League\Flysystem\Cached\Storage\Memory;
use PHPUnit\Framework\TestCase;

/**
 * @requires PHP 7.1.30
 * @requires PHPUnit 7.5
 */
abstract class FilesystemTestCase extends TestCase
{
    /**
     * @var Closure|FileManager
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $workspace;

    protected function setUp(): void
    {
        $this->workspace = __DIR__. DIRECTORY_SEPARATOR.'Fixtures';

        $this->filesystem = function (FileConfig $config, AdapterInterface & $driver) {
            return new FileManager($driver, $config);
        };
    }

    abstract protected function getConfig(): FileConfig;

    protected function getAdapter(): AdapterInterface
    {
        return new Local($this->workspace);
    }

    protected function getFlysystem(): FileManagerInterface
    {
        return ($this->filesystem)($this->getConfig(), $this->getAdapter());
    }

    protected function getFlysystemCache(AbstractCache $cache = null): FileManagerInterface
    {
        $driver = new CachedAdapter($this->getAdapter(), $cache ?? new Memory);

        return ($this->filesystem)($this->getConfig(), $driver);
    }
}
