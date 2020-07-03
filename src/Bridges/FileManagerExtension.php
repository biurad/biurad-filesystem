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
use Psr\Cache\CacheItemInterface;

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

        $builder->addDefinition($this->prefix('map'))
            ->setFactory(FlysystemMap::class);

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

        $config      = \array_intersect_key($this->config, \array_flip(['default', 'connections']));
        $default     = new Statement([ConnectionFactory::class, 'makeAdapter'], [$config]);
        $adapters    = [];

        foreach ($builder->findByTag(ConnectionFactory::FlY_ADAPTER_TAG) as $id => $name) {
            $adapter = $builder->getDefinition($id)->getFactory();
            $builder->removeDefinition($name);

            $adapters[$name] = $connection = new Statement(
                [ConnectionFactory::class, 'makeAdapter'],
                [$this->createFlyConfig($name, $adapter)]
            );
            $filesystemMap->addSetup(
                'set',
                new Statement(FileManager::class, [$connection, $this->getFlyConfig($name)])
            );
        }

        $adapter = $adapters[$this->config['default']] ?: $default;
        $cache   = $this->config['caching'];

        if ($cache['enable'] && \class_exists(Psr6Cache::class) && $builder->getByType(CacheItemInterface::class)) {
            $adapter = new Statement(
                CachedAdapter::class,
                [$adapter, new Statement(Psr6Cache::class, [1 => $cache['key'], 2 => $cache['ttl']])]
            );
        }

        $builder->getDefinition($this->prefix('manager'))
            ->setArgument('config', $adapter);
    }

    /**
     * @param string    $name
     * @param Statement $adapter
     *
     * @return array
     */
    private function createFlyConfig(string $name, Statement $adapter): array
    {
        return [
            'default'     => $adapter,
            'connections' => [$name => $this->config['connections'][$name]],
        ];
    }

    /**
     * @return array
     */
    private function getFlyConfig(string $name): array
    {
        $options = [];
        $config  = $this->config['connection'][$name] ?: [];

        if (isset($config['visibility'])) {
            $options['visibility'] = $config['visibility'];
        }

        if (isset($config['pirate'])) {
            $options['disable_asserts'] = $config['private'];
        }

        return $options;
    }
}
