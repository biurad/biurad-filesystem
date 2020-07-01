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

namespace BiuradPHP\FileManager;

use BiuradPHP\FileManager\Interfaces\FlysystemInterface;
use BiuradPHP\FileManager\Interfaces\StreamableInterface;
use BiuradPHP\FileManager\Interfaces\StreamInterface as FlyStreamInterface;
use BiuradPHP\FileManager\Plugin\ListDirectories;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Config as FileConfig;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem as LeagueFilesystem;
use League\Flysystem\Plugin;
use League\Flysystem\Util;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use SplFileInfo;

/**
 * Default abstraction for file management operations.
 *
 * @author  Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class FileManager extends LeagueFilesystem implements FlysystemInterface, StreamableInterface
{
    /**
     * Default file mode for this manager.
     */
    public const DEFAULT_FILE_MODE = 0664;

    /**
     * Constructor.
     *
     * @param AdapterInterface $adapter
     * @param array|FileConfig $config
     */
    public function __construct(AdapterInterface $adapter, $config = null)
    {
        parent::__construct($adapter, $config);
        $this->addDefaultPlugins();
    }

    /**
     * {@inheritdoc}
     */
    public function createStream(string $key): FlyStreamInterface
    {
        if (($adapter = $this->getAdapter()) instanceof CachedAdapter) {
            $adapter = $adapter->getAdapter();
        }

        if ($adapter instanceof StreamableInterface) {
            return $this->adapter->createStream($key);
        }

        return new Streams\FlyStreamBuffer($this, $key);
    }

    /**
     * {@inheritdoc}
     *
     * @throws FileNotFoundException
     */
    public function prepend($path, $data, $separator = \PHP_EOL): bool
    {
        if ($this->has($path)) {
            return $this->put($path, $data . $separator . $this->read($path));
        }

        return $this->put($path, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @throws FileNotFoundException
     */
    public function append($path, $data, $separator = \PHP_EOL): bool
    {
        if ($this->has($path)) {
            return $this->put($path, $this->read($path) . $separator . $data);
        }

        return $this->put($path, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @throws FileNotFoundException
     */
    public function sharedGet(string $path): string
    {
        $contents = '';
        $file     = $this->path($path);

        if (!$this->isLocalAdapter()) {
            return (string) $this->get($path)->getContents();
        }

        if ($handle = \fopen($file, 'rb')) {
            try {
                if (\flock($handle, \LOCK_SH)) {
                    \clearstatcache(true, $file);

                    $contents = \fread($handle, $this->getSize($path) ?: 1);

                    \flock($handle, \LOCK_UN);
                }
            } finally {
                \fclose($handle);
            }
        }

        return $contents;
    }

    /**
     * {@inheritdoc}
     */
    public function move(string $filename, string $destination): bool
    {
        return $this->adapter->rename($filename, $destination);
    }

    /**
     * {@inheritdoc}
     *
     * @throws FileNotFoundException
     */
    public function touch(string $filename, int $mode = null): bool
    {
        if ($this->isLocalAdapter() && !\touch($this->path($filename))) {
            return false;
        }

        return $this->setPermissions($filename, $mode ?? self::DEFAULT_FILE_MODE);
    }

    /**
     * {@inheritdoc}
     */
    public function checksum(string $filename): string
    {
        if ($this->has($filename)) {
            throw new FileNotFoundException($filename);
        }

        return \md5($this->read($filename));
    }

    /**
     * {@inheritdoc}
     */
    public function path(string $path = ''): string
    {
        if (($prefix = $this->getAdapter()) instanceof CachedAdapter) {
            $prefix = $prefix->getAdapter();
        }

        return Util::normalizePath($prefix->getPathPrefix() . $path);
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory(string $filename): bool
    {
        if (false !== $path = $this->getMetadata($filename)) {
            return 'dir' === $path['type'];
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isFile(string $filename): bool
    {
        if (false !== $path = $this->getMetadata($filename)) {
            return 'file' === $path['type'];
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(string $filename): int
    {
        if (!$this->has($filename)) {
            throw new FileNotFoundException($filename);
        }

        if ($this->isLocalAdapter()) {
            return \fileperms($this->path($filename)) ?? 0777;
        }

        return 33204;
    }

    /**
     * {@inheritdoc}
     */
    public function setPermissions(string $filename, int $mode)
    {
        if ($this->isDirectory($filename)) {
            //Directories must always be executable (i.e. 664 for dir => 775)
            $mode |= 16893;
        } elseif (!$this->isLocalAdapter()) {
            return false;
        }

        return $this->getPermissions($filename) === $mode || \chmod($this->path($filename), $mode);
    }

    /**
     * {@inheritdoc}
     */
    public function put($path, $contents, array $config = [])
    {
        if ($contents instanceof UploadedFileInterface) {
            $contents = $contents->getStream();
        }

        if ($contents instanceof StreamInterface) {
            return $this->putStream($path, $contents->detach(), $config);
        }

        return \is_resource($contents)
                ? $this->putStream($path, $contents, $config)
                : parent::put($path, $contents, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function putFile(string $path, SplFileInfo $file, string $name, array $options = [])
    {
        $stream = \fopen($file->getRealPath(), 'rb');

        // Next, we will format the path of the file and store the file using a stream since
        // they provide better performance than alternatives. Once we write the file this
        // stream will get closed automatically by us so the developer doesn't have to.
        $result = $this->put($path = \trim($path . '/' . $name, '/'), $stream, $options);

        if (\is_resource($stream)) {
            \fclose($stream);
        }

        return $result ? $path : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getFiles(string $directory = null, $recursive = false): array
    {
        $contents = $this->listFiles($directory, $recursive);

        return $this->filterContentsByType($contents);
    }

    /**
     * {@inheritdoc}
     */
    public function getDirectories(string $directory = null, $recursive = false): array
    {
        $contents = $this->listDirectories($directory, $recursive);

        return $this->filterContentsByType($contents);
    }

    /**
     * Flush the Flysystem cache.
     */
    public function flushCache(): void
    {
        $adapter = $this->getAdapter();

        if ($adapter instanceof CachedAdapter) {
            $adapter->getCache()->flush();
        }
    }

    /**
     * Create a symlink to the target file or directory. On Windows, a hard link is created if the target is a file.
     *
     * @param string $target
     * @param string $link
     *
     * @return mixed
     */
    public function createSymlink(string $target, string $link)
    {
        if (\PHP_OS_FAMILY !== 'Windows') {
            return \symlink($this->path($target), $link);
        }
        $mode = $this->isDirectory($target) ? 'J' : 'H';

        return \exec("mklink /{$mode} " . \escapeshellarg($link) . ' ' . \escapeshellarg($this->path($target)));
    }

    /**
     * @return bool
     */
    protected function isLocalAdapter(): bool
    {
        if (($adapter = $this->getAdapter()) instanceof CachedAdapter) {
            $adapter = $adapter->getAdapter();
        }

        return $adapter instanceof Local;
    }

    private function addDefaultPlugins(): void
    {
        $plugins = [
            new ListDirectories(),
            new Plugin\ForcedCopy(),
            new Plugin\ListFiles(),
            new Plugin\EmptyDir(),
            new Plugin\ListWith(),
            new Plugin\ListPaths(),
            new Plugin\ForcedRename(),
            new Plugin\GetWithMetadata(),
        ];

        \array_walk($plugins, [$this, 'addPlugin']);
    }

    /**
     * Filter directory contents by type.
     *
     * @param array $contents
     *
     * @return array
     */
    private function filterContentsByType($contents): array
    {
        $result = [];

        foreach ($contents as $files) {
            $result[] = $files['path'];
        }

        return $result;
    }
}
