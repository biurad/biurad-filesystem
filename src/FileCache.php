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

namespace BiuradPHP\FileManager;

use League\Flysystem\Cached\Storage\AbstractCache;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class FileCache extends AbstractCache
{
    /**
     * The cache repository implementation.
     *
     * @var CacheInterface
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
     * @var null|int
     */
    protected $expire;

    /**
     * Create a new cache instance.
     *
     * @param CacheInterface $repository
     * @param array          $config
     */
    public function __construct(CacheInterface $repository, array $config = [])
    {
        $this->key        = $config['key'] ?? 'flysystem';
        $this->expire     = $config['ttl'] ?? null;
        $this->repository = $repository;
    }

    /**
     * Load the cache.
     *
     * @throws InvalidArgumentException
     */
    public function load(): void
    {
        $contents = $this->repository->get($this->key);

        if (null !== $contents) {
            $this->setFromStorage($contents);
        }
    }

    /**
     * Persist the cache.
     *
     * @throws InvalidArgumentException
     */
    public function save(): void
    {
        $contents = $this->getForStorage();

        $this->repository->set($this->key, $contents, $this->expire);
    }
}
