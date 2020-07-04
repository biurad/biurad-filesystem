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
use League\Flysystem\AdapterInterface;
use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use League\Flysystem\Config;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

/**
 * This is the azure connector class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class AzureConnector implements FlyAdapterInterface
{
    /**
     * {@inheritdoc}
     *
     * @return AzureBlobStorageAdapter
     */
    public function connect(Config $config): AdapterInterface
    {
        return new AzureBlobStorageAdapter($this->getClient($config), $this->get($config, 'container'));
    }

    /**
     * Get the azure client.
     *
     * @param Config $auth
     *
     * @return BlobRestProxy
     */
    protected function getClient(Config $config): BlobRestProxy
    {
        $endpoint = \sprintf(
            'DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s',
            $this->get($config, 'account-name'),
            $this->get($config, 'api-key')
        );

        return BlobRestProxy::createBlobService($endpoint);
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
            throw new InvalidArgumentException(\sprintf('The azure connector requires "%s" configuration.', $key));
        }

        return $config->get($key);
    }
}
