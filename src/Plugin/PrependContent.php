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

class PrependContent extends AbstractPlugin
{
    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return 'prepend';
    }

    /**
     * Prepend contents to a file if exists
     *
     * @param string $path
     * @param string $contents
     * @param string $separator
     *
     * @return bool
     */
    public function handle(string $path, string $contents, string $separator = \PHP_EOL): bool
    {
        if (!$this->filesystem->has($path)) {
            return false;
        }

        return $this->filesystem->put(
            $path,
            $contents . $separator . $this->filesystem->read($path)
        );
    }
}
