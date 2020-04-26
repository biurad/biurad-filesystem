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

namespace BiuradPHP\FileManager\Config;

use BiuradPHP\FileManager\Adapters\ConnectionFactory;
use BiuradPHP\FileManager\FileManager;
use BiuradPHP\FileManager\Interfaces\CloudConnectionInterface;
use BiuradPHP\FileManager\Plugin\ListDirectories;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\CacheInterface;
use League\Flysystem\Plugin\ForcedCopy;
use League\Flysystem\Plugin\ForcedRename;
use League\Flysystem\Plugin\GetWithMetadata;
use League\Flysystem\Plugin\ListFiles;
use League\Flysystem\Plugin\ListPaths;
use League\Flysystem\Plugin\ListWith;
use Illuminate\Support\Arr;
use League\Flysystem\Plugin\EmptyDir;

final class FileConfig implements CloudConnectionInterface
{
    public const DEFAULT_DRIVER = 'local';

    private $cache;

    /**
     * @internal
     *
     * @var array
     */
    private $config = [
        'default' => self::DEFAULT_DRIVER,
        'aliases' => [],
        'caching' => [
            'enable' => false,
            'ttl' => null,
        ],
        'stream_protocol' => null,
        'resources' => [],
        'connections' => [
            'awss3' => [
                'key'             => 'your-key',
                'secret'          => 'your-secret',
                'bucket'          => 'your-bucket',
                'region'          => 'your-region',
                'version'         => 'latest',
                // 'bucket_endpoint' => false,
                // 'calculate_md5'   => true,
                // 'scheme'          => 'https',
                // 'endpoint'        => 'your-url',
                // 'prefix'          => 'your-prefix',
                // 'visibility'      => 'public',
                // 'pirate'          => false,
                // 'eventable'       => true,
                // 'cache'           => 'foo'
            ],

            'azure' => [
                'account-name' => 'your-account-name',
                'api-key'      => 'your-api-key',
                'container'    => 'your-container',
                // 'visibility'   => 'public',
                // 'pirate'       => false,
                // 'eventable'    => true,
                // 'cache'        => 'foo'
            ],

            'dropbox' => [
                'token'      => 'your-token',
                // 'prefix'     => 'your-prefix',
                // 'visibility' => 'public',
                // 'pirate'     => false,
                // 'eventable'  => true,
                // 'cache'      => 'foo'
            ],

            'ftp' => [
                'host'       => 'ftp.example.com',
                'port'       => 21,
                'username'   => 'your-username',
                'password'   => 'your-password',
                // 'root'       => '/path/to/root',
                // 'passive'    => true,
                // 'ssl'        => true,
                // 'timeout'    => 20,
                // 'visibility' => 'public',
                // 'pirate'     => false,
                // 'eventable'  => true,
                // 'cache'      => 'foo'
            ],

            'gcs' => [
                'projectId' => 'your-project-id',
                'keyFile'   => 'your-key-file',
                'bucket'    => 'your-bucket',
                // 'prefix'    => 'your-prefix',
                // 'apiUri'    => 'http://your-domain.com',
            ],

            'gridfs' => [
                'server'     => 'mongodb://localhost:27017',
                'database'   => 'your-database',
                // 'visibility' => 'public',
                // 'pirate'     => false,
                // 'eventable'  => true,
                // 'cache'      => 'foo'
            ],

            'local' => [
                'path'       => null,
                // 'visibility' => 'public',
                // 'pirate'     => false,
                // 'eventable'  => true,
                // 'cache'      => 'foo'
            ],

            'rackspace' => [
                'endpoint'   => 'your-endpoint',
                'region'     => 'your-region',
                'username'   => 'your-username',
                'apiKey'     => 'your-api-key',
                'container'  => 'your-container',
                // 'internal'   => false,
                // 'visibility' => 'public',
                // 'pirate'     => false,
                // 'eventable'  => true,
                // 'cache'      => 'foo'
            ],

            'sftp' => [
                'host'       => 'sftp.example.com',
                'port'       => 22,
                'username'   => 'your-username',
                'password'   => 'your-password',
                // 'privateKey' => 'path/to/or/contents/of/privatekey',
                // 'root'       => '/path/to/root',
                // 'timeout'    => 20,
                // 'visibility' => 'public',
                // 'pirate'     => false,
                // 'eventable'  => true,
                // 'cache'      => 'foo'
            ],

            'webdav' => [
                'baseUri'    => 'http://example.org/dav/',
                'userName'   => 'your-username',
                'password'   => 'your-password',
                // 'visibility' => 'public',
                // 'pirate'     => false,
                // 'eventable'  => true,
                // 'cache'      => 'foo'
            ],

            'zip' => [
                'path'       => '/files.zip',
                // 'visibility' => 'public',
                // 'pirate'     => false,
                // 'eventable'  => true,
                // 'cache'      => 'foo'
            ],
        ]
    ];

    /**
     * At this moment on array based configs can be supported.
     *
     * @param array $config
     * @param CacheInterface|null $cache
     */
    public function __construct(array $config = [], ?CacheInterface $cache = null)
    {
        $this->config = $config;
        $this->cache = $cache;
    }

    /**
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->config['default'] ?? self::DEFAULT_DRIVER;
    }

    /**
     * Get named list of all driver connections.
     *
     * @return \BiuradPHP\FileManager\FileManager[]
     */
    public function getConnections(): array
    {
        $result = [];
        foreach (array_keys($this->config['connections'] ?? $this->config['drivers'] ?? []) as $driver) {
            $result[$driver] = $this->makeConnection($driver);
        }

        return $result;
    }

    /**
     * Get the flysystem options.
     *
     * @return array|null
     */
    public function getOptions()
    {
        $options = [];

        if ($visibility = Arr::get($this->config, 'visibility')) {
            $options['visibility'] = $visibility;
        }

        if ($pirate = Arr::get($this->config, 'pirate')) {
            $options['disable_asserts'] = $pirate;
        }

        return $options;
    }

    /**
     * The defaut's flysystem plugins
     *
     * @return array
     */
    public function defaultPlugins()
    {
        return [
            new ListDirectories(),
            new ForcedCopy(),
            new ListFiles(),
            new EmptyDir(),
            new ListWith(),
            new ListPaths(),
            new ForcedRename(),
            new GetWithMetadata(),
        ];
    }

    /**
     * @param string $driver
     *
     * @return AdapterInterface
     *
     * @throws \UnexpectedValueException
     */
    public function getFileAdapter(string $driver = self::DEFAULT_DRIVER): AdapterInterface
    {
        $config = $this->config;
        if (!$this->hasDriver($driver)) {
            throw new \UnexpectedValueException("Undefined flysystem adapter `{$driver}`.");
        }
        // Set the custom driver.
        $config['default'] = $driver;

        $newDriver =  ConnectionFactory::make($config);
        if (null !== $this->cache && $config['caching']['enable']) {
            return new CachedAdapter($newDriver, $this->cache);
        }

        return $newDriver;
    }

    /**
     * Make a new flysystem instance.
     *
     * @param string $driver
     *
     * @return \BiuradPHP\FileManager\FileManager
     */
    public function makeConnection(string $driver = self::DEFAULT_CLOUD)
    {
        $new = clone new FileManager($this->getFileAdapter($driver), $this);

        foreach ($this->defaultPlugins() as $plugin) {
            $new->addPlugin($plugin);
        };

        return $new;
    }

    /**
     * @param string $driver
     *
     * @return bool
     */
    public function hasDriver(string $driver): bool
    {
        return isset($this->config['connections'][$driver]) || in_array($driver, ['array', 'null'], true) || isset($this->config['drivers'][$driver]);
    }

    /**
     * Get the stream wrapper protocol.
     *
     * @return string|null
     */
    public function getStreamProtocol(): ?string
    {
        return $this->config['stream_protocol'];
    }

    /**
     * @param string $alias
     *
     * @return string
     */
    public function resolveAlias(string $alias): string
    {
        while (is_string($alias) && isset($this->config) && isset($this->config['aliases'][$alias])) {
            $alias = $this->config['aliases'][$alias];
        }

        return $alias;
    }
}
