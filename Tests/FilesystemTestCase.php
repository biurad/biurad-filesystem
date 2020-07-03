<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
 *
 * PHP version 7.1 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
use BiuradPHP\FileManager\Interfaces\FlysystemInterface;
use Closure;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\AbstractCache;
use League\Flysystem\Cached\Storage\Memory;
use PHPUnit\Framework\TestCase;

abstract class FilesystemTestCase extends TestCase
{
    /** @var Closure<FileManager> */
    protected $filesystem;

    /** @var string */
    protected $workspace;

    protected function setUp(): void
    {
        $this->workspace = __DIR__ . \DIRECTORY_SEPARATOR . 'Fixtures';

        $this->filesystem = function (AdapterInterface $driver) {
            return new FileManager($driver);
        };
    }

    abstract protected function getAdapter(): AdapterInterface;

    protected function getLocalAdapter(): AdapterInterface
    {
        return new Local($this->workspace);
    }

    protected function getFlysystem(): FlysystemInterface
    {
        return ($this->filesystem)($this->getAdapter());
    }

    protected function getFlysystemCache(AbstractCache $cache = null): FlysystemInterface
    {
        $driver = new CachedAdapter($this->getAdapter(), $cache ?? new Memory());

        return ($this->filesystem)($driver);
    }
}
