<?php

declare(strict_types=1);

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

namespace BiuradPHP\FileManager;

use BiuradPHP\FileManager\Config\FileConfig;
use League\Flysystem\AdapterInterface;
use BiuradPHP\FileManager\Interfaces\FileManagerInterface;
use BiuradPHP\FileManager\Interfaces\StreamableInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use League\Flysystem\Cached\CachedAdapter;
use Psr\Http\Message\StreamInterface;
use League\Flysystem\Adapter\Local;
use BiuradPHP\FileManager\Interfaces\StreamInterface as FlyStreamInterface;
use League\Flysystem\{FileNotFoundException, Filesystem as LeagueFilesystem, Util};

/**
 * Default abstraction for file management operations.
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @license   BSD-3-Clause
 */
class FileManager extends LeagueFilesystem implements FileManagerInterface, StreamableInterface
{
    /**
     * Default file mode for this manager.
     */
    const DEFAULT_FILE_MODE = 0664;

    /** @var FileConfig */
    private $fileConfig;

    /**
     * Constructor.
     *
     * @param AdapterInterface $adapter
     * @param FileConfig|null    $config
     */
    public function __construct(AdapterInterface $adapter, ?FileConfig $config = null)
    {
        $this->fileConfig = $config;
        register_shutdown_function([$this, '__destruct']);

        return parent::__construct($adapter, is_null($config) ? null : $config->getOptions());
    }

    /**
     * Get a connection instance.
     *
     * @param string|null $name
     *
     * @return object|FileManagerInterface
     */
    public function createConnection(string $name = FileConfig::DEFAULT_DRIVER): FileManagerInterface
    {
        $newFly = clone $this->fileConfig;
        return $newFly->makeConnection($name);
    }

    /**
     * {@inheritdoc}
     */
    public function createStream($key): FlyStreamInterface
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
     */
    public function prepend($path, $data, $separator = PHP_EOL): bool
    {
        if ($this->has($path)) {
            return $this->put($path, $data . $separator . $this->read($path));
        }

        return $this->put($path, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function append($path, $data, $separator = PHP_EOL): bool
    {
        if ($this->has($path)) {
            return $this->put($path, $this->read($path) . $separator . $data);
        }

        return $this->put($path, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function sharedGet($path)
    {
        $contents = '';
        $file = $this->path($path);

        if (!$this->isLocalAdapter()) {
            return $this->get($path)->getContents();
        }

        if ($handle = fopen($file, 'rb')) {
            try {
                if (flock($handle, LOCK_SH)) {
                    clearstatcache(true, $file);

                    $contents = fread($handle, $this->getSize($path) ?: 1);

                    flock($handle, LOCK_UN);
                }
            } finally {
                fclose($handle);
            }
        }

        return $contents;
    }

    /**
     * {@inheritdoc}
     */
    public function localFilename(string $filename): string
    {
        if (!$this->has($filename)) {
            throw new FileNotFoundException($filename);
        }

        //Since default implementation is local we are allowed to do that
        return $this->path($filename);
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
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function touch(string $filename, int $mode = null): bool
    {
        if ($this->isLocalAdapter() && !touch($this->path($filename))) {
            return false;
        }

        return $this->setPermissions($filename, $mode ?? self::DEFAULT_FILE_MODE);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function extension(string $filename): string
    {
        return strtolower(pathinfo($this->path($filename), PATHINFO_EXTENSION));
    }

    /**
     * {@inheritdoc}
     */
    public function checksum(string $filename): string
    {
        if ($this->has($filename)) {
            throw new FileNotFoundException($filename);
        }

        return md5($this->read($filename));
    }

    /**
     * {@inheritdoc}
     */
    public function path(string $path = '')
    {
        if (($prefix = $this->getAdapter()) instanceof CachedAdapter) {
            $prefix = $prefix->getAdapter();
        }

        return Util::normalizePath($prefix->getPathPrefix() . $path);
    }

    /**
     * Create the most normalized version for path to file or location.
     *
     * @param string $path        File or location path.
     *
     * @return string
     *
     * @throws \LogicException
     */
    public function normalizePath(string $path): string
    {
        return Util::normalizePath($path);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
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
     *
     * @throws \Exception
     */
    public function isFile(string $filename): bool
    {
        if (false !== $path = $this->getMetadata($filename)) {
            return 'file' === $path['type'];
        }

        return false;
    }

    /**
     * Find path names matching a given pattern.
     *
     * @param  string  $pattern
     * @param  int     $flags
     * @return array
     */
    public function glob(string $pattern, int $flags = 0)
    {
        return glob($pattern, $flags);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function getPermissions(string $filename): int
    {
        if (!$this->has($filename)) {
            throw new FileNotFoundException($filename);
        }

        if ($this->isLocalAdapter()) {
            return fileperms($this->path($filename)) ?? 0777;
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

        return $this->getPermissions($filename) == $mode || chmod($this->path($filename), $mode);
    }

    /**
     * {@inheritdoc}
     *
     * @see http://stackoverflow.com/questions/2637945/getting-relative-path-from-absolute-path-in-php
     */
    public function relativePath(string $path, string $from): string
    {
        $path = Util::normalizePath($path);
        $from = Util::normalizePath($from);

        $from = explode('/', $from);
        $path = explode('/', $path);
        $relative = $path;

        foreach ($from as $depth => $dir) {
            //Find first non-matching dir
            if ($dir === $path[$depth]) {
                //Ignore this directory
                array_shift($relative);
            } else {
                //Get number of remaining dirs to $from
                $remaining = count($from) - $depth;
                if ($remaining > 1) {
                    //Add traversals up to first matching directory
                    $padLength = (count($relative) + $remaining - 1) * -1;
                    $relative = array_pad($relative, $padLength, '..');
                    break;
                } else {
                    $relative[0] = './' . $relative[0];
                }
            }
        }

        return implode('/', $relative);
    }

    /**
     * {@inheritDoc}
     */
    public function put($path, $contents, array $config = [])
    {
        $options = is_string($config)
            ? ['visibility' => $config]
            : (array) $config;

        // If the given contents is actually a file or uploaded file instance than we will
        // automatically store the file using a stream. This provides a convenient path
        // for the developer to store streams without managing them manually in code.
        if ($contents instanceof UploadedFile) {
            return $this->putFileAs($path, $contents, $contents->hashName(), $options);
        }

        if ($contents instanceof StreamInterface) {
            return $this->putStream($path, $contents->detach(), $options);
        }

        return is_resource($contents)
                ? $this->putStream($path, $contents, $options)
                : parent::put($path, $contents, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function putFileAs($path, $file, $name, $options = [])
    {
        $stream = fopen($file->getRealPath(), 'r');

        // Next, we will format the path of the file and store the file using a stream since
        // they provide better performance than alternatives. Once we write the file this
        // stream will get closed automatically by us so the developer doesn't have to.
        $result = $this->put($path = trim($path . '/' . $name, '/'), $stream, $options);

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $result ? $path : false;
    }

    /**
     * Filter directory contents by type.
     *
     * @param  array  $contents
     * @return array
     */
    private function filterContentsByType($contents)
    {
        $result = [];

        foreach($contents as $files) {
            $result[] = $files['path'];
        }

        return $result;
    }

     /**
     * Get an array of all files in a directory.
     *
     * @param  string|null  $directory
     * @param  bool  $recursive
     *
     * @return array
     */
    public function getFiles($directory = null, $recursive = false)
    {
        $contents = $this->listFiles($directory, $recursive);

        return $this->filterContentsByType($contents);
    }

    /**
     * Get all of the directories within a given directory.
     *
     * @param  string|null  $directory
     * @param  bool  $recursive
     *
     * @return array
     */
    public function getDirectories($directory = null, $recursive = false)
    {
        $contents = $this->listDirectories($directory, $recursive);

        return $this->filterContentsByType($contents);
    }

    /**
     * Flush the Flysystem cache.
     *
     * @return void
     */
    public function flushCache()
    {
        $adapter = $this->getAdapter();

        if ($adapter instanceof CachedAdapter) {
            $adapter->getCache()->flush();
        }
    }

    /**
     * Get the returned value of a file.
     *
     * @param  string  $path
     * @return mixed
     *
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function getRequire($path)
    {
        if ($this->isFile($path)) {
            return require $this->path($path);
        }

        throw new FileNotFoundException("File does not exist at path {$path}");
    }

    /**
     * Require the given file once.
     *
     * @param  string  $file
     * @return mixed
     */
    public function requireOnce($file)
    {
        require_once $this->path($file);
    }

    /**
     * Create a symlink to the target file or directory. On Windows, a hard link is created if the target is a file.
     *
     * @param  string  $target
     * @param  string  $link
     * @return void
     */
    public function createSymlink(string $target, string $link)
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return symlink($this->path($target), $link);
        }

        $mode = $this->isDirectory($target) ? 'J' : 'H';

        exec("mklink /{$mode} ".escapeshellarg($link).' '.escapeshellarg($this->path($target)));
    }

    /**
     * @return bool
     */
    protected function isLocalAdapter()
    {
        if (($adapter = $this->getAdapter()) instanceof CachedAdapter) {
            $adapter = $adapter->getAdapter();
        }

        if ($adapter instanceof Local) {
            return true;
        }

        return false;
    }
}
