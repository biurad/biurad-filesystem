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

class FlushCache extends AbstractPlugin
{
    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return 'flushCache';
    }

    /**
     * Flush the Flysystem cache.
     */
    public function handle(): void
    {
        $adapter = $this->filesystem->getAdapter();

        if ($adapter instanceof CachedAdapter) {
            $adapter->getCache()->flush();
        }
    }
}
