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

namespace BiuradPHP\FileManager\Interfaces;

use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface as LeagueFilesystemInterface;
use SplFileInfo;

/**
 * This provides convenience wrappers around league flysystem queries.
 *
 * @method void flushCache()
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
interface FlysystemInterface extends LeagueFilesystemInterface
{
    /**
     * Prepend to a file.
     *
     * @param  string $path
     * @param  string $data
     * @param  string $separator
     * @return bool
     */
    public function prepend($path, $data, $separator = \PHP_EOL): bool;

    /**
     * Append to a file.
     *
     * @param  string $path
     * @param  string $data
     * @param  string $separator
     * @return bool
     */
    public function append($path, $data, $separator = \PHP_EOL): bool;

    /**
     * Move file from one location to another. Location must exist.
     *
     * @param string $filename
     * @param string $destination
     *
     * @throws FileNotFoundException
     * @return bool
     */
    public function move(string $filename, string $destination): bool;

    /**
     * Store the psr-7 uploaded file on the disk with a given name.
     *
     * @param string      $path
     * @param SplFileInfo $file
     * @param string      $name
     * @param array       $options
     *
     * @return false|string
     */
    public function putFile(string $path, SplFileInfo $file, string $name, array $options = []);

    /**
     * Get contents of a file with shared access.
     *
     * @param string $path
     *
     * @return string
     */
    public function sharedGet(string $path): string;

    /**
     * Touch file to update it's timeUpdated value or create new file. Location must exist.
     *
     * @param string $filename
     * @param int    $mode     when NULL class can pick default mode
     */
    public function touch(string $filename, int $mode = null);

    /**
     * Returns the checksum of the specified file's content.
     *
     * @param string $filename
     *
     * @throws FileNotFoundException
     * @return string
     */
    public function checksum(string $filename): string;

    /**
     * @param string $filename
     *
     * @return bool
     */
    public function isDirectory(string $filename): bool;

    /**
     * @param string $filename
     *
     * @return bool
     */
    public function isFile(string $filename): bool;

    /**
     * Current file permissions (if exists).
     *
     * @param string $filename
     *
     * @throws FileNotFoundException
     * @return bool|int
     */
    public function getPermissions(string $filename): int;

    /**
     * Update file permissions.
     *
     * @param string $filename
     * @param int    $mode
     *
     * @throws FileNotFoundException
     */
    public function setPermissions(string $filename, int $mode);

    /**
     * Get the full path for the file at the given "short" path.
     *
     * @param string $path
     *
     * @return string
     */
    public function path(string $path): string;

    /**
     * Create a symlink to the target file or directory. On Windows, a hard link is created if the target is a file.
     *
     * @param string $target
     * @param string $link
     *
     * @return mixed
     */
    public function createSymlink(string $target, string $link);

    /**
     * Get an array of all files in a directory.
     *
     * @param null|string $directory
     * @param bool        $recursive
     *
     * @return array
     */
    public function getFiles(string $directory = null, $recursive = false): array;

    /**
     * Get all of the directories within a given directory.
     *
     * @param null|string $directory
     * @param bool        $recursive
     *
     * @return array
     */
    public function getDirectories(string $directory = null, $recursive = false): array;
}