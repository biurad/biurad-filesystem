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

namespace BiuradPHP\FileManager\Adapters;

use BiuradPHP\FileManager\Interfaces\FlyAdapterInterface;
use InvalidArgumentException;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

/**
 * This is the local connector class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class LocalConnector implements FlyAdapterInterface
{
    /**
     * {@inheritdoc}
     *
     * @return Local
     */
    public function connect(Config $config): AdapterInterface
    {
        if (!$config->has('path')) {
            throw new InvalidArgumentException('The local connector requires "path" configuration.');
        }

        // Pull parameters from config and set defaults for optional values
        $path         = $config->get('path');
        $writeFlags   = $config->get('write_flags', \LOCK_EX);
        $linkHandling = $config->get('link_handling', Local::DISALLOW_LINKS);
        $permissions  = $config->get('permissions', []);

        return new Local($path, $writeFlags, $linkHandling, $permissions);
    }
}
