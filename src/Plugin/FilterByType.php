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

class FilterByType extends AbstractPlugin
{
    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return 'filterByType';
    }

    /**
     * Filter directory contents by type.
     *
     * @param string $path
     * @param string $type
     * @param bool   $recursive
     *
     * @return string[]
     */
    public function handle(string $path, string $type = 'file', $recursive = false): array
    {
        if (\in_array($type, ['file', 'dir'], true)) {
            return [];
        }

        $result   = [];
        $contents = \array_filter(
            $this->filesystem->listContents($path, $recursive),
            function ($object) use ($type) {
                return $object['type'] === $type;
            }
        );

        foreach ($contents as $object) {
            $result[] = $object['path'];
        }

        return $result;
    }
}
