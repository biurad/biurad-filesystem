<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
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

namespace Biurad\FileManager\Adapters;

use Biurad\FileManager\Interfaces\FlyAdapterInterface;
use InvalidArgumentException;
use League\Flysystem\Rackspace\RackspaceAdapter;
use OpenCloud\ObjectStore\Resource\Container;
use OpenCloud\Rackspace as OpenStackRackspace;

/**
 * This is the rackspace connector class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class RackspaceConnector implements FlyAdapterInterface
{
    /**
     * {@inheritdoc}
     *
     * @return RackspaceAdapter
     */
    public function connect(Config $config): AdapterInterface
    {
        return new RackspaceAdapter($this->getClient($config));
    }

    /**
     * Get the rackspace client.
     *
     * @param Config $config
     *
     * @return Container
     */
    protected function getClient(Config $config)
    {
        $client = new OpenStackRackspace($this->get($config, 'endpoint'), [
            'username' => $this->get($config, 'username'),
            'apiKey'   => $this->get($config, 'apiKey'),
        ]);

        $urlType = $config->has('internal') ? 'internalURL' : 'publicURL';

        return $client->objectStoreService(
            'cloudFiles',
            $this->get($config, 'region'),
            $urlType
        )->getContainer($this->get($config, 'container'));
    }

    /**
     * @param Config $config
     * @param string $key
     *
     * @throws InvalidArgumentException
     * @return mixed
     */
    private function get(Config $config, string $key)
    {
        if (!$config->has($key)) {
            throw new InvalidArgumentException(\sprintf('The rackspace connector requires "%s" configuration.', $key));
        }

        return $config->get($key);
    }
}
