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

/**
 * This is the adapter connection factory class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class ConnectionFactory
{
    /**
     * Establish an adapter connection.
     *
     * @param array $config
     *
     * @return \League\Flysystem\AdapterInterface
     */
    public static function make(array $config)
    {
        $name = isset($config['default']) ? $config['default'] : 'local';

        return self::createConnector($name)->connect($config['connections'][$name] ?? []);
    }

    /**
     * Create a connector instance based on the configuration.
     *
     * @param string $config
     *
     * @throws \InvalidArgumentException
     *
     * @return ConnectorInterface
     */
    public static function createConnector(?string $config)
    {
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

        throw new \InvalidArgumentException("Unsupported driver [{$config}].");
    }
}
