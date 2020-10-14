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

use Biurad\FileManager\Interfaces\StreamableInterface;
use Biurad\FileManager\Interfaces\StreamInterface;
use Biurad\FileManager\Streams\FlyStreamBuffer;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Plugin\AbstractPlugin;

class CreateStream extends AbstractPlugin
{
    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return 'createStream';
    }

    /**
     * Create a new stream from path.
     *
     * @param string $key — the path to use for stream
     *
     * @return StreamInterface
     */
    public function handle(string $key): StreamInterface
    {
        $adapter = $this->filesystem->getAdapter();

        if ($adapter instanceof CachedAdapter) {
            $adapter = $adapter->getAdapter();
        }

        if ($adapter instanceof StreamableInterface) {
            return $adapter->createStream($key);
        }

        return new FlyStreamBuffer($this->filesystem, $key);
    }
}
