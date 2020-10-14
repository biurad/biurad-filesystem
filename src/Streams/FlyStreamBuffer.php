<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
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

namespace Biurad\FileManager\Streams;

use Biurad\FileManager\Interfaces\StreamInterface;
use Exception;
use League\Flysystem\Adapter\Local as FlyLocal;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\NotSupportedException;
use League\Flysystem\Util;
use LogicException;
use SplFileObject;

class FlyStreamBuffer implements StreamInterface
{
    /** @var FilesystemInterface */
    private $filesystem;

    /** @var string */
    private $key;

    /** @var StreamMode */
    private $mode;

    /** @var string */
    private $content;

    /** @var int */
    private $numBytes;

    /** @var int */
    private $position;

    /** @var bool */
    private $synchronized;

    /**
     * @param FilesystemInterface $filesystem The filesystem managing the file to stream
     * @param string              $key        The file key
     */
    public function __construct(FilesystemInterface $filesystem, string $key)
    {
        $this->filesystem = $filesystem;
        $this->key        = $key;
    }

    /**
     * {@inheritdoc}
     * @throws FileNotFoundException
     */
    public function open(StreamMode $mode)
    {
        $this->mode = $mode;

        if (true !== $exists = $this->filesystem->has($this->key)) {
            return false;
        }

        if (
            (
                $exists &&
                !$mode->allowsExistingFileOpening()
            ) ||
            (
                !$exists &&
                !$mode->allowsNewFileOpening()
            )
        ) {
            return false;
        }

        if ($mode->impliesExistingContentDeletion()) {
            $this->content = $this->writeContent('');
        } elseif (!$exists && $mode->allowsNewFileOpening()) {
            $this->content = $this->writeContent('');
        } else {
            $this->content = $this->filesystem->isDirectory($this->key) ? '' : $this->filesystem->read($this->key);
        }

        $this->numBytes = Util::contentSize($this->content);
        $this->position = $mode->impliesPositioningCursorAtTheEnd() ? $this->numBytes : 0;

        $this->synchronized = true;

        return true;
    }

    public function read($count)
    {
        if (false === $this->mode->allowsRead()) {
            throw new LogicException('The stream does not allow read.');
        }

        $chunk = \substr($this->content, $this->position, $count);
        $this->position += Util::contentSize($chunk);

        return $chunk;
    }

    public function write($data)
    {
        if (false === $this->mode->allowsWrite()) {
            throw new LogicException('The stream does not allow write.');
        }

        $numWrittenBytes = Util::contentSize($data);

        $newPosition = $this->position + $numWrittenBytes;
        $newNumBytes = $newPosition > $this->numBytes ? $newPosition : $this->numBytes;

        if ($this->eof()) {
            $this->numBytes += $numWrittenBytes;

            if ($this->hasNewContentAtFurtherPosition()) {
                $data = \str_pad($data, $this->position + \strlen($data), ' ', \STR_PAD_LEFT);
            }
            $this->content .= $data;
        } else {
            $before        = \substr($this->content, 0, $this->position);
            $after         = $newNumBytes > $newPosition ? \substr($this->content, $newPosition) : '';
            $this->content = $before . $data . $after;
        }

        $this->position     = $newPosition;
        $this->numBytes     = $newNumBytes;
        $this->synchronized = false;

        return $numWrittenBytes;
    }

    public function close(): void
    {
        if (!$this->synchronized) {
            $this->flush();
        }
    }

    public function seek($offset, $whence = \SEEK_SET)
    {
        switch ($whence) {
            case \SEEK_SET:
                $this->position = $offset;

                break;
            case \SEEK_CUR:
                $this->position += $offset;

                break;
            case \SEEK_END:
                $this->position = $this->numBytes + $offset;

                break;
            default:
                return false;
        }

        return true;
    }

    public function tell()
    {
        return $this->position;
    }

    public function flush()
    {
        if ($this->synchronized) {
            return true;
        }

        try {
            $this->writeContent($this->content);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function eof()
    {
        return $this->position >= $this->numBytes;
    }

    /**
     * {@inheritdoc}
     * @throws FileNotFoundException
     */
    public function stat()
    {
        if ($this->filesystem->has($this->key)) {
            $isDirectory = $this->filesystem->isDirectory($this->key);
            $time        = $this->filesystem->getTimestamp($this->key);
            $path        = $this->filesystem->path($this->key);
            $isLocal     = $this->isLocalAdapter();
            $mode        = !$isDirectory ? (new SplFileObject($path))->fstat()['mode'] : 16893;

            $stats = [
                'dev'     => 1,
                'ino'     => 0,
                'mode'    => !$isLocal ? ($isDirectory ? 16893 : 33204) : $mode,
                'nlink'   => 1,
                'uid'     => 0,
                'gid'     => 0,
                'rdev'    => 0,
                'size'    => $isDirectory ? 0 : $this->filesystem->getSize($this->key),
                'atime'   => !$isLocal ? $time : \fileatime($path),
                'mtime'   => $time,
                'ctime'   => !$isLocal ? $time : \filectime($path),
                'blksize' => -1,
                'blocks'  => -1,
            ];

            return \array_merge(\array_values($stats), $stats);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function cast($castAst)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function unlink()
    {
        if ($this->mode && $this->mode->impliesExistingContentDeletion()) {
            return $this->filesystem->delete($this->key);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function opendir($path)
    {
        if ($this->isLocalAdapter()) {
            return \opendir($this->filesystem->path($this->key));
        }

        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public function readdir()
    {
        try {
            return $this->filesystem->readStream($this->key);
        } catch (Exception $e) {
            throw new NotSupportedException(
                'Sorry, doesn\'t support reading directory on remote connection, use local storage instead.',
                0,
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function mkdir()
    {
        return $this->filesystem->createDir($this->key);
    }

    /**
     * {@inheritdoc}
     */
    public function rmdir()
    {
        return $this->filesystem->deleteDir($this->key);
    }

    /**
     * @return bool
     */
    protected function isLocalAdapter(): bool
    {
        if (($adapter = $this->filesystem->getAdapter()) instanceof CachedAdapter) {
            $adapter = $adapter->getAdapter();
        }

        return $adapter instanceof FlyLocal;
    }

    /**
     * @return bool
     */
    protected function hasNewContentAtFurtherPosition(): bool
    {
        return $this->position > 0 && !$this->content;
    }

    /**
     * @param string $content Empty string by default
     *
     * @return string
     */
    protected function writeContent($content = ''): string
    {
        $this->filesystem->put($this->key, $content);

        return $content;
    }
}
