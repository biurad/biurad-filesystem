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
use League\Flysystem\AdapterInterface;

/**
 * This is the adapter connection factory class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class ConnectionFactory
{
    private static $adapters = [];

    /**
     * Establish an adapter connection.
     *
     * @param array $config
     *
     * @return AdapterInterface
     */
    public static function makeAdapter(array $config): AdapterInterface
    {
        $name = $config['default'] ?? 'local';

        return self::createConnector($name)->connect($config['connections'][$name] ?? []);
    }

    /**
     * Create a connector instance based on the configuration.
     *
     * @param string $config
     *
     * @return ConnectorInterface
     * @throws InvalidArgumentException
     */
    public static function createConnector(?string $config): ConnectorInterface
    {
        // Custom Adapters...
        if (null !== $config && isset(self::$adapters[$config])) {
            return self::$adapters[$config];
        }

        switch ($config) {
            case 'awss3':
                return new AwsS3Connector();
            case 'azure':
                return new AzureConnector();
            case 'dropbox':
                return new DropboxConnector();
            case 'ftp':
                return new FtpConnector();
            case 'gcs':
                return new GoogleCloudStorageConnector();
            case 'gridfs':
                return new GridFSConnector();
            case 'local':
                return new LocalConnector();
            case 'array':
            case 'null':
                return new NullConnector();
            case 'rackspace':
                return new RackspaceConnector();
            case 'sftp':
                return new SftpConnector();
            case 'webdav':
                return new WebDavConnector();
            case 'zip':
                return new ZipConnector();
        }

        throw new InvalidArgumentException("Unsupported driver [{$config}].");
    }

    /**
     * Add a new adapter to FIlemanager
     *
     * @param string $name
     * @param ConnectorInterface $adapter
     * @return void
     */
    public function addAdapter(string $name, ConnectorInterface $adapter): void
    {
        self::$adapters[$name] = $adapter;
    }
}
