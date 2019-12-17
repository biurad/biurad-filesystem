<?php

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

use BiuradPHP\FileManager\FileManager;
use BiuradPHP\FileManager\Interfaces\FileManagerInterface;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\Memory;
use PHPUnit\Framework\TestCase;

/**
 * @requires PHP 7.1.30
 * @requires PHPUnit 7.5
 */
class FilesystemTestCase extends TestCase
{
    private $umask;

    protected $longPathNamesWindows = [];

    /**
     * @var \Closure|\BiuradPHP\FileManager\FileManager
     */
    protected $filesystem = null;

    /**
     * @var string
     */
    protected $workspace = null;

    protected function setUp(): void
    {
        $this->umask = umask(0);
        $this->workspace = __DIR__.\DIRECTORY_SEPARATOR.'Fixtures';

        $this->filesystem = function (AdapterInterface & $driver) {
            return new FileManager($driver);
        };
    }

    protected function tearDown(): void
    {
        $this->filesystem->delete($this->workspace);
        umask($this->umask);
    }

    protected function getFlysystem(): FileManagerInterface
    {
        $this->driver = new Local($this->workspace);

        return $this->filesystem->call(null, $this->driver);
    }

    protected function getFlysystemCache(): FileManagerInterface
    {
        $driver = new CachedAdapter($this->driver, new Memory);

        return $this->filesystem->call(null, $driver);
    }
}
