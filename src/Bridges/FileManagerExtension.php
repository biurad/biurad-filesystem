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

namespace BiuradPHP\FileManager\Bridges;

use BiuradPHP\FileManager\ConnectionFactory;
use BiuradPHP\FileManager\FileManager;
use BiuradPHP\FileManager\FlysystemMap;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\Psr6Cache;
use Nette;
use Nette\DI\Definitions\Statement;
use Psr\Cache\CacheItemPoolInterface;

class FileManagerExtension extends Nette\DI\CompilerExtension
{
    /**
     * {@inheritDoc}
     */
    public function getConfigSchema(): Nette\Schema\Schema
    {
        return Nette\Schema\Expect::structure([
            'default'           => Nette\Schema\Expect::string()->default('array'),
            'caching'           => Nette\Schema\Expect::structure([
                'enable' => Nette\Schema\Expect::bool(false),
                'key'    => Nette\Schema\Expect::string()->nullable(),
                'ttl'    => Nette\Schema\Expect::int()->nullable(),
            ])->castTo('array'),
            'connections'       => Nette\Schema\Expect::arrayOf(
                Nette\Schema\Expect::structure([
                    'visibility' => Nette\Schema\Expect::anyOf('public', 'private')->default('public'),
                    'pirate'     => Nette\Schema\Expect::scalar()->default(false),
                ])->otherItems()->castTo('array')
            ),
        ])->castTo('array');
    }

    /**
     * {@inheritDoc}
     */
    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();

        $filesystems = \array_map(function (string $name) {
            $adapters = ['awss3', 'azure', 'dropbox', 'ftp', 'gcs', 'gridfs', 'local', 'array', 'rackspace', 'sftp', 'webdav', 'zip'];

            if (\in_array($name, $adapters, true)) {
                return new Statement(
                    FileManager::class,
                    [
                        $this->getFlyAdapter($name, $name),
                        $this->getFlyConfig($name),
                    ]
                );
            }
        }, \array_combine(\array_keys($this->config['connections']), \array_keys($this->config['connections'])));

        $builder->addDefinition($this->prefix('map'))
            ->setFactory(FlysystemMap::class)
            ->setArguments([$filesystems]);

        $builder->addDefinition($this->prefix('manager'))
            ->setFactory(FileManager::class)
            ->setArgument(0, new Statement([ConnectionFactory::class, 'makeAdapter'], [[]]));

        $builder->addAlias('flysystem', $this->prefix('manager'));
    }

    /**
     * {@inheritdoc}
     */
    public function beforeCompile(): void
    {
        $builder       = $this->getContainerBuilder();
        $filesystemMap = $builder->getDefinition($this->prefix('map'));

        $default  = $this->config['default'];
        $adapters = [];

        foreach ($builder->findByTag(ConnectionFactory::FlY_ADAPTER_TAG) as $id => $name) {
            $adapter = $builder->getDefinition($id)->getFactory();
            $builder->removeDefinition($name);

            $adapters[$name] = $connection = $this->getFlyAdapter($name, $adapter);
            $filesystemMap->addSetup(
                'set',
                [$name, new Statement(FileManager::class, [$connection, $this->getFlyConfig($name)])]
            );
        }

        $builder->getDefinition($this->prefix('manager'))
            ->setArgument(0, $adapters[$default] ?? $this->getFlyAdapter($default, $default));
    }

    /**
     * @param string           $name
     * @param Statement|string $adapter
     *
     * @return Statement
     */
    private function getFlyAdapter(string $name, $adapter): Statement
    {
        $cache   = $this->config['caching'];
        $builder = $this->getContainerBuilder();
        $adapter =  new Statement([ConnectionFactory::class, 'makeAdapter'], [$this->createFlyConfig($name, $adapter)]);

        if ($cache['enable'] && \class_exists(Psr6Cache::class) && $builder->getByType(CacheItemPoolInterface::class)) {
            $adapter = new Statement(
                CachedAdapter::class,
                [$adapter, new Statement(Psr6Cache::class, [1 => $cache['key'], 2 => $cache['ttl']])]
            );
        }

        return $adapter;
    }

    /**
     * @param string           $name
     * @param Statement|string $adapter
     *
     * @return array
     */
    private function createFlyConfig(string $name, $adapter): array
    {
        $adapterConfig = \array_filter(
            $this->config['connections'][$name],
            function (string $key) {
                return !\in_array($key, ['visibility', 'pirate']);
            },
            \ARRAY_FILTER_USE_KEY
        );

        return [
            'default'     => $adapter,
            'connection'  => $adapterConfig,
        ];
    }

    /**
     * @return array
     */
    private function getFlyConfig(string $name): array
    {
        $options = [];
        $config  = $this->config['connections'][$name] ?? [];

        if (isset($config['visibility'])) {
            $options['visibility'] = $config['visibility'];
        }

        if (isset($config['pirate'])) {
            $options['disable_asserts'] = $config['pirate'];
        }

        return $options;
    }
}
