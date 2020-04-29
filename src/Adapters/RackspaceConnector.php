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
use League\Flysystem\Rackspace\RackspaceAdapter;
use OpenCloud\ObjectStore\Resource\Container;
use OpenCloud\Rackspace as OpenStackRackspace;

use function BiuradPHP\Support\array_get;

/**
 * This is the rackspace connector class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class RackspaceConnector implements ConnectorInterface
{
    /**
     * Establish an adapter connection.
     *
     * @codeCoverageIgnore
     *
     * @param string[] $config
     *
     * @return \League\Flysystem\Rackspace\RackspaceAdapter
     */
    public function connect(array $config)
    {
        $auth = $this->getAuth($config);
        $client = $this->getClient($auth);

        return $this->getAdapter($client);
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
        if (!array_key_exists('username', $config) || !array_key_exists('apiKey', $config)) {
            throw new \InvalidArgumentException('The rackspace connector requires authentication.');
        }

        if (!array_key_exists('endpoint', $config)) {
            throw new \InvalidArgumentException('The rackspace connector requires endpoint configuration.');
        }

        if (!array_key_exists('region', $config)) {
            throw new \InvalidArgumentException('The rackspace connector requires region configuration.');
        }

        if (!array_key_exists('container', $config)) {
            throw new \InvalidArgumentException('The rackspace connector requires container configuration.');
        }

        return array_intersect_key($config, array_flip(['username', 'apiKey', 'endpoint', 'region', 'container', 'internal']));
    }

    /**
     * Get the rackspace client.
     *
     * @param string[] $auth
     *
     * @return Container
     */
    protected function getClient(array $auth)
    {
        $client = new OpenStackRackspace($auth['endpoint'], [
            'username' => $auth['username'],
            'apiKey'   => $auth['apiKey'],
        ]);

        $urlType = array_get($auth, 'internal', false) ? 'internalURL' : 'publicURL';

        return $client->objectStoreService('cloudFiles', $auth['region'], $urlType)->getContainer($auth['container']);
    }

    /**
     * Get the rackspace adapter.
     *
     * @codeCoverageIgnore
     *
     * @param Container $client
     *
     * @return RackspaceAdapter
     */
    protected function getAdapter(Container $client)
    {
        return new RackspaceAdapter($client);
    }
}
