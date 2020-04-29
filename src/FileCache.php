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

namespace BiuradPHP\FileManager;

use League\Flysystem\Cached\Storage\AbstractCache;
use Psr\SimpleCache\CacheInterface;

class FileCache extends AbstractCache
{
    /**
     * The cache repository implementation.
     *
     * @var \Psr\SimpleCache\CacheInterface
     */
    protected $repository;

    /**
     * The cache key.
     *
     * @var string
     */
    protected $key;

    /**
     * The cache expiration time in seconds.
     *
     * @var int|null
     */
    protected $expire;

    /**
     * Create a new cache instance.
     *
     * @param  \Psr\SimpleCache\CacheInterface $repository
     * @param  array $config
     * @return void
     */
    public function __construct(CacheInterface $repository, array $config = [])
    {
        $this->key = isset($config['key']) ? $config['key'] : 'flysystem';
        $this->expire = isset($config['ttl']) ? $config['ttl'] : null;
        $this->repository = $repository;
    }

    /**
     * Load the cache.
     *
     * @return void
     */
    public function load()
    {
        $contents = $this->repository->get($this->key);

        if (! is_null($contents)) {
            $this->setFromStorage($contents);
        }
    }

    /**
     * Persist the cache.
     *
     * @return void
     */
    public function save()
    {
        $contents = $this->getForStorage();

        $this->repository->set($this->key, $contents, $this->expire);
    }
}
