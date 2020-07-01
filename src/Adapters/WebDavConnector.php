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
use League\Flysystem\WebDAV\WebDAVAdapter;
use Sabre\DAV\Client;

/**
 * This is the webdav connector class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class WebDavConnector implements FlyAdapterInterface
{
    /**
     * Establish an adapter connection.
     *
     * @param string[] $config
     *
     * @return \League\Flysystem\WebDAV\WebDAVAdapter
     */
    public function connect(array $config)
    {
        $client = $this->getClient($config);
        $config = $this->getConfig($config);

        return $this->getAdapter($client, $config);
    }

    /**
     * Get the webdav client.
     *
     * @param string[] $config
     *
     * @return \Sabre\DAV\Client
     */
    protected function getClient(array $config)
    {
        return new Client($config);
    }

    /**
     * Get the configuration.
     *
     * @param string[] $config
     *
     * @return string[]
     */
    protected function getConfig(array $config)
    {
        if (!\array_key_exists('prefix', $config)) {
            $config['prefix'] = null;
        }

        return \array_intersect_key($config, \array_flip(['prefix']));
    }

    /**
     * Get the webdav adapter.
     *
     * @param Client   $client
     * @param string[] $config
     *
     * @return WebDAVAdapter
     */
    protected function getAdapter(Client $client, array $config)
    {
        return new WebDAVAdapter($client, $config['prefix']);
    }
}
