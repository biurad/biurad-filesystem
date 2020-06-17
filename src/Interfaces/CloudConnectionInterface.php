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

interface CloudConnectionInterface
{
    public const DEFAULT_CLOUD = 'ftp';

    /**
     * Make a new flysystem instance.
     *
     * @param string $driver
     *
     * @return FileManagerInterface
     */
    public function makeConnection(string $driver = self::DEFAULT_CLOUD): FileManagerInterface;
}
