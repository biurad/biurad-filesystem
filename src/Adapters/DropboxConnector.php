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
use Spatie\Dropbox\Client;
use Spatie\FlysystemDropbox\DropboxAdapter;

/**
 * This is the dropbox connector class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class DropboxConnector implements FlyAdapterInterface
{
    /**
     * Establish an adapter connection.
     *
     * @param string[] $config
     *
     * @return DropboxAdapter
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
        if (!\array_key_exists('token', $config)) {
            throw new InvalidArgumentException('The dropbox connector requires authentication.');
        }

        return \array_intersect_key($config, \array_flip(['token']));
    }

    /**
     * Get the dropbox client.
     *
     * @param string[] $auth
     *
     * @return Client
     */
    protected function getClient(array $auth)
    {
        return new Client($auth['token']);
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
     * Get the dropbox adapter.
     *
     * @param Client   $client
     * @param string[] $config
     *
     * @return DropboxAdapter
     */
    protected function getAdapter(Client $client, array $config)
    {
        return new DropboxAdapter($client, (string) $config['prefix']);
    }
}
