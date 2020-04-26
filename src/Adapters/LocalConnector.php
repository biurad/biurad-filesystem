<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  FileManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/filemanager
 * @since     Version 0.1
 */

namespace BiuradPHP\FileManager\Adapters;

use BiuradPHP\FileManager\Interfaces\ConnectorInterface;
use Illuminate\Support\Arr;
use League\Flysystem\Adapter\Local;

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
     * @return \League\Flysystem\Adapter\Local
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
     * @throws \InvalidArgumentException
     *
     * @return string[]
     */
    protected function getConfig(array $config)
    {
        if (!array_key_exists('path', $config)) {
            throw new \InvalidArgumentException('The local connector requires path configuration.');
        }

        return Arr::only($config, ['path', 'write_flags', 'link_handling', 'permissions']);
    }

    /**
     * Get the local adapter.
     *
     * @param string[] $config
     *
     * @return \League\Flysystem\Adapter\Local
     */
    protected function getAdapter(array $config)
    {
        // Pull parameters from config and set defaults for optional values
        $path = $config['path'];
        $writeFlags = Arr::get($config, 'write_flags', LOCK_EX);
        $linkHandling = Arr::get($config, 'link_handling', Local::DISALLOW_LINKS);
        $permissions = Arr::get($config, 'permissions', []);

        return new Local($path, $writeFlags, $linkHandling, $permissions);
    }
}
