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

use BiuradPHP\FileManager\Interfaces\ConnectorInterface;
use InvalidArgumentException;
use League\Flysystem\Adapter\Local;

use function BiuradPHP\Support\array_get;

/**
 * This is the local connector class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class LocalConnector implements ConnectorInterface
{
    /**
     * Establish an adapter connection.
     *
     * @param string[] $config
     *
     * @return Local
     */
    public function connect(array $config)
    {
        $config = $this->getConfig($config);

        return $this->getAdapter($config);
    }

    /**
     * Get the configuration.
     *
     * @param string[] $config
     *
     * @throws InvalidArgumentException
     *
     * @return string[]
     */
    protected function getConfig(array $config)
    {
        if (!\array_key_exists('path', $config)) {
            throw new InvalidArgumentException('The local connector requires path configuration.');
        }

        return \array_intersect_key($config, \array_flip(['path', 'write_flags', 'link_handling', 'permissions']));
    }

    /**
     * Get the local adapter.
     *
     * @param string[] $config
     *
     * @return Local
     */
    protected function getAdapter(array $config)
    {
        // Pull parameters from config and set defaults for optional values
        $path         = $config['path'];
        $writeFlags   = array_get($config, 'write_flags', \LOCK_EX);
        $linkHandling = array_get($config, 'link_handling', Local::DISALLOW_LINKS);
        $permissions  = array_get($config, 'permissions', []);

        return new Local($path, $writeFlags, $linkHandling, $permissions);
    }
}
