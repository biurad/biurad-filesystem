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

use InvalidArgumentException;

/**
 * Associates filesystem instances to their names.
 */
interface FlysystemMapInterface
{
    /**
     * Indicates whether there is a filesystem registered for the specified
     * name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * Returns the filesystem registered for the specified name.
     *
     * @param string $name
     *
     * @throws InvalidArgumentException when there is no filesystem registered
     *                                  for the specified name
     * @return FilesystemInterface
     */
    public function get(string $name): FlysystemInterface;
}
