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
use InvalidArgumentException;
use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

/**
 * This is the azure connector class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class AzureConnector implements ConnectorInterface
{
    /**
     * Establish an adapter connection.
     *
     * @param string[] $config
     *
     * @return AzureBlobStorageAdapter
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
     * @throws InvalidArgumentException
     *
     * @return string[]
     */
    protected function getAuth(array $config)
    {
        if (!array_key_exists('account-name', $config) || !array_key_exists('api-key', $config)) {
            throw new InvalidArgumentException('The azure connector requires authentication.');
        }

        return array_intersect_key($config, array_flip(['account-name', 'api-key']));
    }

    /**
     * Get the azure client.
     *
     * @param string[] $auth
     *
     * @return BlobRestProxy
     */
    protected function getClient(array $auth)
    {
        $endpoint = sprintf('DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s', $auth['account-name'], $auth['api-key']);

        return BlobRestProxy::createBlobService($endpoint);
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
        if (!array_key_exists('container', $config)) {
            throw new InvalidArgumentException('The azure connector requires container configuration.');
        }

        return array_intersect_key($config, array_flip(['container']));
    }

    /**
     * Get the container adapter.
     *
     * @param BlobRestProxy $client
     * @param string[] $config
     *
     * @return AzureBlobStorageAdapter
     */
    protected function getAdapter(BlobRestProxy $client, array $config)
    {
        return new AzureBlobStorageAdapter($client, $config['container']);
    }
}
