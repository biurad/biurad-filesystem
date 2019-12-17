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

namespace BiuradPHP\FileManager\Bridges;

use Nette, BiuradPHP;
use Nette\DI\ContainerBuilder;
use League\Flysystem\AdapterInterface;
use Nette\DI\Definitions\ServiceDefinition;
use League\Flysystem\Cached\Storage\Memory as MemoryStore;
use BiuradPHP\FileManager\Interfaces\CloudManagerInterface;

class FileManagerExtension extends BiuradPHP\DependencyInjection\CompilerExtension
{
    /**
     * {@inheritDoc}
     */
    public function getConfigSchema(): Nette\Schema\Schema
    {
        return Nette\Schema\Expect::structure([
            'caching' => Nette\Schema\Expect::bool()->default(false),
            'disk' => Nette\Schema\Expect::string()->default('local'),
            'cloud' => Nette\Schema\Expect::string(),
            'pools' => Nette\Schema\Expect::arrayOf('string|array'),
        ])->otherItems('mixed');
    }

    /**
     * {@inheritDoc}
     */
    public function loadConfiguration()
    {
        $builder = $this->getContainerBuilder();
        $config = $this->config;

        $local = $builder->addDefinition('filemanager')
                ->setFactory(BiuradPHP\FileManager\FileManager::class);
        $this->createDefault($local, $builder, $config->caching);

        $cloud = $builder->addDefinition('filemanager.cloud')
                ->setFactory('BiuradPHP\FileManager\Bridges\FileManagerExtension::cloudStorage');
        $this->createCloud($cloud, $builder, $config->caching);

        foreach (FileManagerBridge::defaultPlugins() as $plugin) {
            $local->addSetup('addPlugin', [$plugin]);
            $cloud->addSetup('addPlugin', [$plugin]);
        }
    }

    protected function createCloud(
        ServiceDefinition $definition, ContainerBuilder $builder, bool $caching
    ): void {
        FileManagerBridge::of($this)
            ->setConfig($this->config)
            ->withDefault($this->config->cloud)
            ->getDefinition('file.adapter.cloud');

        if (false !== $caching) {
            $builder->addDefinition('filesystem.cloud')
                ->setFactory(\League\Flysystem\Cached\CachedAdapter::class)
                ->setArguments(['@file.adapter.cloud', new MemoryStore]);
        }

        $cloud = '@file.adapter.cloud';
        if (isset($caching) && $builder->hasDefinition('filesystem.cloud')) {
            $cloud = '@filesystem.cloud';
        }

        $definition->setArguments([$cloud]);
    }

    protected function createDefault(
        ServiceDefinition $definition, ContainerBuilder $builder, bool $caching
    ): void {
        FileManagerBridge::of($this)
            ->setConfig($this->config)
            ->withDefault($this->config->disk)
            ->getDefinition('file.adapter.local');

        if (false !== $caching) {
            $builder->addDefinition('filesystem.local')
                ->setFactory(\League\Flysystem\Cached\CachedAdapter::class)
                ->setArguments(['@file.adapter.local', new MemoryStore]);
        }

        $local = '@file.adapter.local';
        if (isset($caching) && $builder->hasDefinition('filesystem.local')) {
            $local = '@filesystem.local';
        }

        $definition->setArguments([$local]);
    }


    public static function cloudStorage(AdapterInterface $adapter, $config = null): CloudManagerInterface
    {
        return new class($adapter, $config) extends BiuradPHP\FileManager\FileManager implements CloudManagerInterface {};
    }
}
