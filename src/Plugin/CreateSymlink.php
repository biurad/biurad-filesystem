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

namespace Biurad\FileManager\Plugin;

use League\Flysystem\Plugin\AbstractPlugin;

class CreateSymlink extends AbstractPlugin
{
    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return 'createSymlink';
    }

    /**
     * Create a symlink to the target file or directory.
     * On Windows, a hard link is created if the target is a file.
     *
     * @param string $target
     * @param string $link
     *
     * @return bool|string
     */
    public function handle(string $target, string $link)
    {
        if (\PHP_OS_FAMILY !== 'Windows') {
            return \symlink($target, $link);
        }
        $mode = $this->filesystem->isDirectory($target) ? 'J' : 'H';

        return \exec("mklink /{$mode} " . \escapeshellarg($link) . ' ' . \escapeshellarg($target));
    }
}
