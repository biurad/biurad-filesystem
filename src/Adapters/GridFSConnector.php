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
use League\Flysystem\GridFS\GridFSAdapter;
use MongoClient;

/**
 * This is the gridfs connector class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class GridFSConnector implements ConnectorInterface
{
    /**
     * Establish an adapter connection.
     *
     * @param string[] $config
     *
     * @return \League\Flysystem\GridFS\GridFSAdapter
     */
    public function connect(array $config)
    {
        $auth = $this->getAuth($config);
        $client = $this->getClient($auth);
        $config = $this->getConfig($config);

        return $this->getAdapter($client, $config);
    }

    /**
     * Get the authentication data.
     *
     * @param string[] $config
     *
     * @throws \InvalidArgumentException
     *
     * @return string[]
     */
    protected function getAuth(array $config)
    {
        if (!array_key_exists('server', $config)) {
            throw new \InvalidArgumentException('The gridfs connector requires server configuration.');
        }

        return Arr::only($config, ['server']);
    }

    /**
     * Get the gridfs client.
     *
     * @param string[] $auth
     *
     * @return \MongoClient
     */
    protected function getClient(array $auth)
    {
        return new MongoClient($auth['server']);
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
        if (!array_key_exists('database', $config)) {
            throw new \InvalidArgumentException('The gridfs connector requires database configuration.');
        }

        return Arr::only($config, ['database']);
    }

    /**
     * Get the gridfs adapter.
     *
     * @param \MongoClient $client
     * @param string[]     $config
     *
     * @return \League\Flysystem\GridFS\GridFSAdapter
     */
    protected function getAdapter(MongoClient $client, array $config)
    {
        $fs = $client->selectDB($config['database'])->getGridFS();

        return new GridFSAdapter($fs);
    }
}
