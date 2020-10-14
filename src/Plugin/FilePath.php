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

use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Plugin\AbstractPlugin;
use League\Flysystem\Util;

class FilePath extends AbstractPlugin
{
    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return 'path';
    }

    /**
     * Get the full path for the file at the given "short" path.
     *
     * @param string $path
     *
     * @return string
     */
    public function handle(string $path): string
    {
        $adapter = $this->filesystem->getAdapter();

        if ($adapter instanceof CachedAdapter) {
            $adapter = $adapter->getAdapter();
        }

        return Util::normalizePath($adapter->getPathPrefix() . $path);
    }
}
