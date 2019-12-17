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

use League\Flysystem\AdapterInterface;
use Nette\Utils\Strings, League\Flysystem\Config;
use BiuradPHP\FileManager\Interfaces\FileManagerInterface;
use Psr\Http\Message\UploadedFileInterface, Zend\Diactoros\UploadedFile;
use League\Flysystem\{FileNotFoundException, Filesystem as LeagueFilesystem};

/**
 * Default abstraction for file management operations.
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @license   BSD-3-Clause
 */
class FileManager extends LeagueFilesystem implements FileManagerInterface
{
    /**
     * Default file mode for this manager.
     */
    const DEFAULT_FILE_MODE = 0664;

    /**
     * Files to be removed when component destructed.
     *
     * @var array
     */
    private $destructFiles = [];

    /**
     * Constructor.
     *
     * @param AdapterInterface $adapter
     * @param Config|array     $config
     */
    public function __construct(AdapterInterface $adapter, $config = null)
    {
        register_shutdown_function([$this, '__destruct']);

        return parent::__construct($adapter, $config);
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
        $path = $this->path($path);

        $handle = fopen($path, 'rb');

        if ($handle) {
            try {
                if (flock($handle, LOCK_SH)) {
                    clearstatcache(true, $path);

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
        return $filename;
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
        if (!touch($filename)) {
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
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * {@inheritdoc}
     */
    public function md5(string $filename): string
    {
        if (!$this->has($filename)) {
            throw new FileNotFoundException($filename);
        }

        return md5_file($filename);
    }

    /**
     * {@inheritdoc}
     */
    public function path($path = '')
    {
        $path = $this->normalizePath($path);

        return $this->getAdapter()->getPathPrefix() . $path;
    }

    /**
     * Convert the string to ASCII characters that are equivalent to the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function fallbackName($name)
    {
        return str_replace('%', '', Strings::toAscii($name));
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function isDirectory(string $filename): bool
    {
        return is_dir($filename);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function isFile(string $filename): bool
    {
        return is_file($filename);
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

        return fileperms($filename) & 0777;
    }

    /**
     * {@inheritdoc}
     */
    public function setPermissions(string $filename, int $mode)
    {
        if (is_dir($filename)) {
            //Directories must always be executable (i.e. 664 for dir => 775)
            $mode |= 0111;
        }

        return $this->getPermissions($filename) == $mode || chmod($filename, $mode);
    }

    /**
     * {@inheritdoc}
     */
    public function tempFilename(string $extension = '', string $location = null): string
    {
        if (empty($location)) {
            $location = sys_get_temp_dir();
        }

        $filename = tempnam($location, 'bp_');

        if (!empty($extension)) {
            //I should find more original way of doing that
            $this->rename($filename, $filename = "{$filename}.{$extension}");
            $this->destructFiles[] = $filename;
        }

        return $filename;
    }

    /**
     * {@inheritDoc}
     */
    public function replace($path, $content)
    {
        // If the path already exists and is a symlink, get the real path...
        clearstatcache(true, $path);

        $path = realpath($path) ?: $path;

        $tempPath = tempnam(dirname($path), basename($path));

        // Fix permissions of tempPath because `tempnam()` creates it with permissions set to 0600...
        chmod($tempPath, 0777 - umask());

        file_put_contents($tempPath, $content);

        $this->rename($tempPath, $path);
    }

    /**
     * {@inheritdoc}
     */
    public function normalizePath(string $path, bool $asDirectory = false): string
    {
        $path = str_replace(['//', '\\'], '/', $path);

        //Potentially open links and ../ type directories?
        return rtrim($path, '/') . ($asDirectory ? '/' : '');
    }

    /**
     * {@inheritdoc}
     *
     * @see http://stackoverflow.com/questions/2637945/getting-relative-path-from-absolute-path-in-php
     */
    public function relativePath(string $path, string $from): string
    {
        $path = $this->normalizePath($path);
        $from = $this->normalizePath($from);

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
        if (
            $contents instanceof UploadedFile ||
            $contents instanceof UploadedFileInterface
        ) {
            return $this->putFileAs($path, $contents, $contents->getClientFilename(), $options);
        }

        return parent::put($path, $contents, $config);
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
        $result = $this->put(
            $path = trim($path . '/' . $name, '/'),
            $stream,
            $options
        );

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
     * Destruct every temporary file.
     *
     * @throws FileNotFoundException
     */
    public function __destruct()
    {
        foreach ($this->destructFiles as $filename) {
            $this->delete($filename);
        }
    }
}
