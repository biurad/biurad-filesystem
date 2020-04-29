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

use BiuradPHP\FileManager\Interfaces\CloudConnectionInterface;
use BiuradPHP\FileManager\Streams\StreamMode;
use BiuradPHP\Loader\Interfaces\ResourceLocatorInterface;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Cached\CachedAdapter;

/**
 * Stream wrapper class for the Gaufrette filesystems.
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
class StreamWrapper
{
    private static $filesystemMap;

    private $stream;
    private $dirResource;
    private $tempFilesystem;

    /**
     * Defines the filesystem map.
     *
     * @param CloudConnectionInterface $map
     * @param ResourceLocatorInterface|null $resource
     */
    public static function setFilesystemMap(CloudConnectionInterface $map)
    {
        static::$filesystemMap = $map;

        return new self;
    }

    /**
     * Returns the filesystem map.
     *
     * @return CloudConnectionInterface $map
     */
    public static function getFilesystemMap()
    {
        return static::$filesystemMap;
    }

    /**
     * Registers the stream wrapper to handle the specified scheme.
     *
     * @param string $scheme Default is flysystem
     */
    public static function register($scheme = 'flysystem')
    {
        $scheme = self::$filesystemMap->getStreamProtocol() ?? $scheme;
        static::streamWrapperUnregister($scheme);

        if (!static::streamWrapperRegister($scheme, __CLASS__)) {
            throw new \RuntimeException(sprintf('Could not register stream wrapper class %s for scheme %s.',
                __CLASS__, $scheme
            ));
        }
    }

    /**
     * @param string $scheme - protocol scheme
     */
    protected static function streamWrapperUnregister($scheme)
    {
        if (in_array($scheme, stream_get_wrappers())) {
            return stream_wrapper_unregister($scheme);
        }
    }

    /**
     * @param string $scheme    - protocol scheme
     * @param string $className
     *
     * @return bool
     */
    protected static function streamWrapperRegister($scheme, $className)
    {
        return stream_wrapper_register($scheme, $className);
    }

    public function stream_open($path, $mode)
    {
        $this->stream = $this->createStream($path);

        return $this->stream->open($this->createStreamMode($mode));
    }

    /**
     * @param int $bytes
     *
     * @return mixed
     */
    public function stream_read($bytes)
    {
        if ($this->stream) {
            return $this->stream->read($bytes);
        }

        return false;
    }

    /**
     * @param string $data
     *
     * @return int
     */
    public function stream_write($data)
    {
        if ($this->stream) {
            return $this->stream->write($data);
        }

        return 0;
    }

    public function stream_close()
    {
        if ($this->stream) {
            $this->stream->close();
        }
    }

    /**
     * @return bool
     */
    public function stream_flush()
    {
        if ($this->stream) {
            return $this->stream->flush();
        }

        return false;
    }

    /**
     * @param int $offset
     * @param int $whence - one of values [SEEK_SET, SEEK_CUR, SEEK_END]
     *
     * @return bool
     */
    public function stream_seek($offset, $whence = SEEK_SET)
    {
        if ($this->stream) {
            return $this->stream->seek($offset, $whence);
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function stream_tell()
    {
        if ($this->stream) {
            return $this->stream->tell();
        }

        return false;
    }

    /**
     * @return bool
     */
    public function stream_eof()
    {
        if ($this->stream) {
            return $this->stream->eof();
        }

        return true;
    }

    /**
     * @return mixed
     */
    public function stream_stat()
    {
        if ($this->stream) {
            return $this->stream->stat();
        }

        return false;
    }

    /**
     * @return bool
     */
    public function stream_lock($operation)
    {
        return flock($this->stream, $operation);
    }

    /**
     * @param string $path
     * @param int    $flags
     *
     * @return mixed
     *
     * @todo handle $flags parameter
     */
    public function url_stat($path, $flags)
    {
        $stream = $this->createStream($path);

        try {
            $stream->open($this->createStreamMode('r+'));
        } catch (\RuntimeException $e) {
        }

        return $stream->stat();
    }

    /**
     * @return mixed
     */
    public function dir_opendir($path, $context = null)
    {
        $this->tempFilesystem = $this->createStream($path, true);
        return $this->dirResource = $this->createStream($path)->opendir($path);
    }

    /**
     * @return mixed
     */
    public function dir_readdir()
    {
        if ($this->isLocalAdapter($this->tempFilesystem)) {
            return readdir($this->dirResource);
        }

        return $this->createStream($this->dirResource)->readdir();
    }

    /**
     * @return mixed
     */
    public function mkdir($path, $mode, $options)
    {
        return $this->createStream($path)->mkdir();
    }

    /**
     * @return mixed
     */
    public function rmdir($dirname, $context = null)
    {
        return $this->createStream($dirname)->rmdir();
    }

    /**
     * @param string $path
     *
     * @return mixed
     */
    public function unlink($path)
    {
        $stream = $this->createStream($path);

        try {
            $stream->open($this->createStreamMode('w+'));
        } catch (\RuntimeException $e) {
            return false;
        }

        return $stream->unlink();
    }

    /**
     * @return mixed
     */
    public function stream_cast($castAs)
    {
        if ($this->stream) {
            return $this->stream->cast($castAs);
        }

        return false;
    }

    protected function createStream($path, $stream = false)
    {
        $parts = array_merge(
            array(
                'scheme' => null,
                'host' => null,
                'path' => null,
                'query' => null,
                'fragment' => null,
            ),
            parse_url($path) ?: array()
        );

        $domain = $parts['host'];
        $key = (empty($parts['path']) || '/' === $parts['path']) ? '/' : substr($parts['path'], 1);

        if (null !== $parts['query']) {
            $key .= '?'.$parts['query'];
        }

        if (null !== $parts['fragment']) {
            $key .= '#'.$parts['fragment'];
        }

        if (empty($domain) || empty($key)) {
            throw new \InvalidArgumentException(sprintf('The specified path (%s) is invalid.', $path));
        }

        $filesystem = static::getFilesystemMap()->makeConnection($domain);

        if (false !== $stream) {
            return $filesystem;
        }

        return $filesystem->createStream($key);
    }

    protected function createStreamMode($mode)
    {
        return new StreamMode($mode);
    }

    /**
     * @return bool
     */
    protected function isLocalAdapter($filesystem)
    {
        if (($adapter = $filesystem->getAdapter()) instanceof CachedAdapter) {
            $adapter = $adapter->getAdapter();
        }

        if ($adapter instanceof Local) {
            return true;
        }

        return false;
    }
}
