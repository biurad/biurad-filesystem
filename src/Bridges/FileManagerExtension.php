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

use BiuradPHP;
use BiuradPHP\FileManager\Config\FileConfig;
use BiuradPHP\FileManager\FileCache;
use BiuradPHP\FileManager\Interfaces\ConnectorInterface;
use Nette;
use Nette\DI\Definitions\Reference;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;

class FileManagerExtension extends Nette\DI\CompilerExtension
{
    /**
     * {@inheritDoc}
     */
    public function getConfigSchema(): Nette\Schema\Schema
    {
        return Nette\Schema\Expect::structure([
            'default'           => Nette\Schema\Expect::string()->default(FileConfig::DEFAULT_DRIVER),
            'stream_protocol'   => Nette\Schema\Expect::string('flysystem'),
            'caching'           => Nette\Schema\Expect::array()->default([]),
            'adapters'          => Nette\Schema\Expect::arrayOf(Expect::string())->nullable(),
            'connections'       => Nette\Schema\Expect::arrayOf('array')->required(),
        ])->castTo('array');
    }

    /**
     * {@inheritDoc}
     */
    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();

        foreach ($this->config['adapters'] ?? [] as $key => $adapter) {
            if ($builder->hasDefinition($adapter)) {
                $adapter = new Reference($adapter);
            } elseif (\is_subclass_of($adapter, ConnectorInterface::class)) {
                $adapter = new Statement($adapter);
            }

            $this->config['adapters'] = \array_replace(
                [$this->config['adapters'][$key] => $adapter],
                [$key, $adapter]
            );
        }

        $builder->addDefinition($this->prefix('cache'))
            ->setFactory(FileCache::class)
            ->setArgument('config', $this->config['caching'])
        ;

        $builder->addDefinition($this->prefix('config'))
            ->setFactory(FileConfig::class, [$this->config])
        ;

        if (!$this->config['caching']['enable']) {
            $builder->removeDefinition($this->prefix('cache'));
        }

        $builder->addDefinition($this->prefix('manager'))
            ->setFactory(BiuradPHP\FileManager\FileManager::class)
            ->setArgument(0, new Statement(
                [new Reference($this->prefix('config')), 'getFileAdapter'],
                [$this->config['default']]
            ))
            ->addSetup(
                'foreach (?->defaultPlugins() as $plugin) { ?->addPlugin($plugin); }',
                [new Reference($this->prefix('config')), '@self']
            );

        $builder->addAlias('flysystem', $this->prefix('manager'));
    }
}
