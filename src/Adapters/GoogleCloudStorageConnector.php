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
use Google\Cloud\Storage\StorageClient;
use InvalidArgumentException;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

/**
 * This is the gcs connector class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 * @author Nir Radian <nirradi@gmail.com>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class GoogleCloudStorageConnector implements FlyAdapterInterface
{
    /**
     * {@inheritdoc}
     *
     * @return GoogleStorageAdapter
     */
    public function connect(Config $config): AdapterInterface
    {
        $client = $this->getClient($config);

        $bucket = $client->bucket($this->get($config, 'bucket'));
        $adapter = new GoogleStorageAdapter($client, $bucket);

        if ($config->has('prefix')) {
            $adapter->setPathPrefix($config->get('prefix'));
        }

        if ($config->has('apiUri')) {
            $adapter->setStorageApiUri($config->get('apiUri'));
        }

        return $adapter;
    }

    /**
     * Get the gcs client.
     *
     * @param Config $config
     *
     * @return StorageClient
     */
    protected function getClient(Config $config): StorageClient
    {
        $auth = [
            'projectId' => $this->get($config, 'projectId'),
        ];

        if ($config->has('keyFile')) {
            $auth['keyFilePath'] = $config->get('keyFile');
        }

        return new StorageClient($auth);
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
            throw new InvalidArgumentException(\sprintf('The gcs connector requires "%s" configuration.', $key));
        }

        return $config->get($key);
    }
}
