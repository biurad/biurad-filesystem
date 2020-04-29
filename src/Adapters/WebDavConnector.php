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
use League\Flysystem\WebDAV\WebDAVAdapter;
use Sabre\DAV\Client;

/**
 * This is the webdav connector class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class WebDavConnector implements ConnectorInterface
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
        if (!array_key_exists('prefix', $config)) {
            $config['prefix'] = null;
        }

        return Arr::only($config, ['prefix']);
    }

    /**
     * Get the webdav adapter.
     *
     * @param \Sabre\DAV\Client $client
     * @param string[]          $config
     *
     * @return \League\Flysystem\WebDAV\WebDAVAdapter
     */
    protected function getAdapter(Client $client, array $config)
    {
        return new WebDAVAdapter($client, $config['prefix']);
    }
}
