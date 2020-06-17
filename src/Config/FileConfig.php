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

namespace BiuradPHP\FileManager\Config;

use BiuradPHP\FileManager\Adapters\ConnectionFactory;
use BiuradPHP\FileManager\FileManager;
use BiuradPHP\FileManager\Interfaces\CloudConnectionInterface;
use BiuradPHP\FileManager\Interfaces\FileManagerInterface;
use BiuradPHP\FileManager\Plugin\ListDirectories;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\CacheInterface;
use League\Flysystem\Plugin\EmptyDir;
use League\Flysystem\Plugin\ForcedCopy;
use League\Flysystem\Plugin\ForcedRename;
use League\Flysystem\Plugin\GetWithMetadata;
use League\Flysystem\Plugin\ListFiles;
use League\Flysystem\Plugin\ListPaths;
use League\Flysystem\Plugin\ListWith;
use UnexpectedValueException;

use function BiuradPHP\Support\array_get;

final class FileConfig implements CloudConnectionInterface
{
    public const DEFAULT_DRIVER = 'local';

    private $cache;

    private $factory;

    /**
     * @internal
     *
     * @var array
     */
    private $config;

    /**
     * At this moment on array based configs can be supported.
     *
     * @param array               $config
     * @param null|CacheInterface $cache
     */
    public function __construct(array $config = [], ?CacheInterface $cache = null)
    {
        $this->config  = $config;
        $this->cache   = $cache;
        $this->factory = new ConnectionFactory();

        foreach ($config['adapters'] ?? [] as $name => $adapter) {
            $this->factory->addAdapter($name, $adapter);
        }
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
     * @return FileManager[]
     */
    public function getConnections(): array
    {
        $result = [];

        foreach (\array_keys($this->config['connections'] ?? $this->config['drivers'] ?? []) as $driver) {
            $result[$driver] = $this->makeConnection($driver);
        }

        return $result;
    }

    /**
     * Get the flysystem options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        $options = [];

        if ($visibility = array_get($this->config, 'visibility')) {
            $options['visibility'] = $visibility;
        }

        if ($pirate = array_get($this->config, 'pirate')) {
            $options['disable_asserts'] = $pirate;
        }

        return $options;
    }

    /**
     * The defaut's flysystem plugins
     *
     * @return array
     */
    public function defaultPlugins(): array
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
     * @throws UnexpectedValueException
     * @return AdapterInterface
     */
    public function getFileAdapter(string $driver = self::DEFAULT_DRIVER): AdapterInterface
    {
        $config = $this->config;

        if (!$this->hasDriver($driver)) {
            throw new UnexpectedValueException("Undefined flysystem adapter `{$driver}`.");
        }
        // Set the custom driver.
        $config['default'] = $driver;

        $newDriver =  $this->factory::makeAdapter($config);

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
     * @return FileManagerInterface
     */
    public function makeConnection(string $driver = self::DEFAULT_CLOUD): FileManagerInterface
    {
        $new = clone new FileManager($this->getFileAdapter($driver), $this);

        foreach ($this->defaultPlugins() as $plugin) {
            $new->addPlugin($plugin);
        }

        return $new;
    }

    /**
     * @param string $driver
     *
     * @return bool
     */
    public function hasDriver(string $driver): bool
    {
        return isset($this->config['connections'][$driver])
            || \in_array($driver, ['array', 'null'], true)
            || isset($this->config['drivers'][$driver]);
    }

    /**
     * Get the stream wrapper protocol.
     *
     * @return null|string
     */
    public function getStreamProtocol(): ?string
    {
        return $this->config['stream_protocol'];
    }
}
