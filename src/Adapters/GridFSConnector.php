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
use League\Flysystem\GridFS\GridFSAdapter;
use MongoClient;

/**
 * This is the gridfs connector class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class GridFSConnector implements FlyAdapterInterface
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
        $auth   = $this->getAuth($config);
        $client = $this->getClient($auth);
        $config = $this->getConfig($config);

        return $this->getAdapter($client, $config);
    }

    /**
     * Get the authentication data.
     *
     * @param string[] $config
     *
     * @throws InvalidArgumentException
     * @return string[]
     */
    protected function getAuth(array $config)
    {
        if (!\array_key_exists('server', $config)) {
            throw new InvalidArgumentException('The gridfs connector requires server configuration.');
        }

        return \array_intersect_key($config, \array_flip(['server']));
    }

    /**
     * Get the gridfs client.
     *
     * @param string[] $auth
     *
     * @return MongoClient
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
        if (!\array_key_exists('database', $config)) {
            throw new InvalidArgumentException('The gridfs connector requires database configuration.');
        }

        return \array_intersect_key($config, \array_flip(['database']));
    }

    /**
     * Get the gridfs adapter.
     *
     * @param MongoClient $client
     * @param string[]    $config
     *
     * @return GridFSAdapter
     */
    protected function getAdapter(MongoClient $client, array $config)
    {
        $fs = $client->selectDB($config['database'])->getGridFS();

        return new GridFSAdapter($fs);
    }
}
