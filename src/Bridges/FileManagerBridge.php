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

use Nette\DI\ContainerBuilder;
use League, InvalidArgumentException;
use Nette\DI\Definitions\ServiceDefinition;
use BiuradPHP\FileManager\Plugin\ListDirectories;
use League\Flysystem\Adapter\Local as LocalAdapter;
use BiuradPHP\DependencyInjection\CompilerExtension;
use BiuradPHP\DependencyInjection\Interfaces\BridgeInterface;
use League\Flysystem\Plugin\{ListFiles, ListPaths, ListWith};
use League\Flysystem\Plugin\{ForcedCopy, ForcedRename, GetWithMetadata};

class FileManagerBridge implements BridgeInterface
{
    private const DRIVERS = [
        'aws'         => League\Flysystem\AwsS3v3\AwsS3Adapter::class,
		'local'       => League\Flysystem\Adapter\Local::class,
        'sftp'        => League\Flysystem\Sftp\SftpAdapter::class,
        'ftp'         => League\Flysystem\Adapter\Ftp::class,
    ];

    /** @var CompilerExtension */
	private $extension, $config;

	/** @var string */
	private $default = 'local';

	private function __construct(CompilerExtension $extension)
	{
		$this->extension = $extension;
    }

    public static function of(CompilerExtension $extension): BridgeInterface
	{
		return new self($extension);
    }

	public function setConfig($config): self
	{
		$this->config = $config;

		return $this;
	}

	public function withDefault(string $driver): BridgeInterface
	{
		if (!isset(self::DRIVERS[$driver])) {
			throw new InvalidArgumentException(sprintf('Unsupported default filemanager driver "%s"', $driver));
		}

		$this->default = $driver;

		return $this;
	}

    /**
     * @param string $service
     *
     * @return ServiceDefinition
     */
	public function getDefinition(string $service)
	{
        $builder = $this->extension->getContainerBuilder();

		$def = $builder->addDefinition($service)
			->setFactory(self::DRIVERS[$this->default])
            ->setAutowired(true);

        // First, we will determine if a custom driver creator exists for the given driver and
        // if it does not we will check for a creator method for the driver. Custom creator
        // callbacks allow developers to build their own "drivers" easily using Closures.
		if (isset($this->default)) {
            $method = 'create'.ucfirst($this->default);
            $pools = $this->config->pools;

            if (method_exists($this, $method)) {
                $this->$method($def, $builder, $pools);
            }
		}

		return $def;
    }

    /**
     * The defaut's flysystem plugins
     *
     * @return array
     */
    public static function defaultPlugins()
    {
        return [
            new ListDirectories(),
            new ListFiles(),
            new ListPaths(),
            new ListWith(),
            new ForcedCopy(),
            new ForcedRename(),
            new GetWithMetadata(),
        ];
    }

    /**
     * Format the given S3 configuration with the default options.
     *
     * @param  array  $config
     *
     * @return array
     */
    private function formatS3Config(array $config)
    {
        $config += ['version' => 'latest'];

        if (! empty($config['key']) && ! empty($config['secret'])) {
            $config['credentials'] = array_intersect_key($config, array_flip(['key', 'secret', 'token']));
        }

        return $config;
    }

    /**
     * Create the driver.
     *
     * @param ServiceDefinition $definition
     * @param ContainerBuilder $builder
     * @param array $pools
     *
     * @return void
     */
    protected function createLocal(
        ServiceDefinition $definition,
        ContainerBuilder $builder, array $pools
    ): void {
        $local = $pools['local'];
        $path = $builder->parameters['path__ROOT'];

        $permissions = $this->config->permissions ?? [];
        $links = ($this->config->links ?? null) === 'skip'
            ? LocalAdapter::SKIP_LINKS
            : LocalAdapter::DISALLOW_LINKS;

        $definition->setArguments([
            $path, $this->config->lock ?? LOCK_EX,
            $links, $permissions
        ]);
    }

    /**
     * Create the driver.
     *
     * @param ServiceDefinition $definition
     * @param ContainerBuilder $builder
     * @param array $pools
     *
     * @return void
     */
    protected function createFtp(
        ServiceDefinition $definition,
        ContainerBuilder $builder, array $pools
    ): void {
        $ftp = $pools['ftp'];
        $definition->setArguments([$ftp]);
    }

    /**
     * Create the driver.
     *
     * @param ServiceDefinition $definition
     * @param ContainerBuilder $builder
     * @param array $pools
     *
     * @return void
     */
    protected function createSftp(
        ServiceDefinition $definition,
        ContainerBuilder $builder, array $pools
    ): void {
        $sftp = $pools['ftp'];
        $definition->setArguments([$sftp]);
    }

    /**
     * Create the driver.
     *
     * @param ServiceDefinition $definition
     * @param ContainerBuilder $builder
     * @param array $pools
     *
     * @return void
     */
    protected function createAws(
        ServiceDefinition $definition,
        ContainerBuilder $builder, array $pools
    ): void {
        $aws = $pools['aws'];
        $s3Config = $this->formatS3Config($aws);

        $root = $s3Config['root'] ?? null;
        $options = $this->config->options ?? [];

        $builder->addDefinition('filemanager.connection')
            ->setFactory(\Aws\S3\S3Client::class)
            ->setArguments([$s3Config]);

        $definition->setArguments([
            '@filemanager.connection', $s3Config['bucket'],
            $root, $options
        ]);
    }
}
