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

namespace BiuradPHP\FileManager\Interfaces;

use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface as LeagueFilesystemInterface;

/**
 * Access to hard drive or local store. Does not provide full filesystem abstractions.
 *
 * @author Divine Niiquaye <hello@biuhub.net>
 * @license MIT
 */
interface FileManagerInterface extends LeagueFilesystemInterface
{
    /**
     * Prepend to a file.
     *
     * @param  string  $path
     * @param  string  $data
     * @param  string  $separator
     * @return bool
     */
    public function prepend($path, $data, $separator = PHP_EOL): bool;

    /**
     * Append to a file.
     *
     * @param  string  $path
     * @param  string  $data
     * @param  string  $separator
     * @return bool
     */
    public function append($path, $data, $separator = PHP_EOL): bool;

    /**
     * Method has to return local uri which can be used in require and include statements.
     * Implementation is allowed to use virtual stream uris if it's not local.
     *
     * @param string $filename
     *
     * @return string
     */
    public function localFilename(string $filename): string;

    /**
     * Move file from one location to another. Location must exist.
     *
     * @param string $filename
     * @param string $destination
     *
     * @return bool
     *
     * @throws FileNotFoundException
     */
    public function move(string $filename, string $destination): bool;

    /**
     * Write the contents of a file, replacing it atomically if it already exists.
     *
     * @param  string  $path
     * @param  string  $content
     *
     * @return void
     */
    public function replace($path, $content);

    /**
     * Get contents of a file with shared access.
     *
     * @param  string  $path
     *
     * @return string
     */
    public function sharedGet($path);

    /**
     * Touch file to update it's timeUpdated value or create new file. Location must exist.
     *
     * @param string $filename
     * @param int    $mode When NULL class can pick default mode.
     */
    public function touch(string $filename, int $mode = null);

    /**
     * Get file extension using it's name. Simple but pretty common method.
     *
     * @param string $filename
     *
     * @return string
     */
    public function extension(string $filename): string;

    /**
     * Get file MD5 hash.
     *
     * @param string $filename
     *
     * @return string
     *
     * @throws FileNotFoundException
     */
    public function md5(string $filename): string;

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
     * @return int|bool
     *
     * @throws FileNotFoundException
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
     * Return unique name of temporary (should be removed when interface implementation destructed)
     * file in desired location.
     *
     * @param string $extension Desired file extension.
     * @param string $location
     *
     * @return string
     */
    public function tempFilename(string $extension = '', string $location = null): string;

    /*
     * Move outside in a future versions.
     */

    /**
     * Create the most normalized version for path to file or location.
     *
     * @param string $path        File or location path.
     * @param bool   $asDirectory Path points to directory.
     *
     * @return string
     */
    public function normalizePath(string $path, bool $asDirectory = false): string;

    /**
     * Get relative location based on absolute path.
     *
     * @param string $path Original file or directory location (to).
     * @param string $from Path will be converted to be relative to this directory (from).
     *
     * @return string
     */
    public function relativePath(string $path, string $from): string;

    /**
     * Get the full path for the file at the given "short" path.
     *
     * @param  string  $path
     *
     * @return string
     */
    public function path($path);

    /**
     * Store the uploaded file on the disk with a given name.
     *
     * @param  string  $path
     * @param  \Psr\Http\Message\UploadedFileInterface  $file
     * @param  string  $name
     * @param  array  $options
     *
     * @return string|false
     */
    public function putFileAs($path, $file, $name, $options = []);
}
