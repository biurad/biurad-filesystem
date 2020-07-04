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

namespace BiuradPHP\FileManager;

use BiuradPHP\FileManager\Interfaces\FlyAdapterInterface;
use InvalidArgumentException;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

/**
 * This is the adapter connection factory class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class ConnectionFactory
{
    public const FLY_ADAPTER_TAG = 'flysystem.connection';

    /**
     * Establish an adapter connection.
     *
     * @param array $config
     *
     * @return AdapterInterface
     */
    public static function makeAdapter(array $config): AdapterInterface
    {
        $name = $config['default'] ?? 'array';
        $connection = new Config($config['connection'] ?? []);

        if ($name instanceof FlyAdapterInterface) {
            return $name->connect($connection);
        }

        return self::createDefaultConnector($name)->connect($connection);
    }

    /**
     * Create a connector instance based on the configuration.
     *
     * @param string $config
     *
     * @throws InvalidArgumentException
     * @return FlyAdapterInterface
     */
    public static function createDefaultConnector(string $config): FlyAdapterInterface
    {
        switch ($config) {
            case 'awss3':
                return new Adapters\AwsS3Connector();
            case 'azure':
                return new Adapters\AzureConnector();
            case 'dropbox':
                return new Adapters\DropboxConnector();
            case 'ftp':
                return new Adapters\FtpConnector();
            case 'gcs':
                return new Adapters\GoogleCloudStorageConnector();
            case 'gridfs':
                return new Adapters\GridFSConnector();
            case 'local':
                return new Adapters\LocalConnector();
            case 'array':
            case 'null':
                return new Adapters\NullConnector();
            case 'rackspace':
                return new Adapters\RackspaceConnector();
            case 'sftp':
                return new Adapters\SftpConnector();
            case 'webdav':
                return new Adapters\WebDavConnector();
            case 'zip':
                return new Adapters\ZipConnector();
        }

        throw new InvalidArgumentException("Unsupported driver [{$config}].");
    }
}
