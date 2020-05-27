<?php
/** @noinspection StaticClosureCanBeUsedInspection */

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

namespace BiuradPHP\FileManager\Plugin;

use League\Flysystem\Plugin\AbstractPlugin;

class ListDirectories extends AbstractPlugin
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return 'listDirectories';
    }

    /**
     * List all files in the directory.
     *
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array
     */
    public function handle($directory = '', $recursive = false): array
    {
        $contents = $this->filesystem->listContents($directory, $recursive);

        $filter = function ($object) {
            return $object['type'] === 'dir';
        };

        return array_values(array_filter($contents, $filter));
    }
}
