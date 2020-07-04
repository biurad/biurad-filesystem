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

namespace BiuradPHP\FileManager\Adapters;

use BiuradPHP\FileManager\Interfaces\FlyAdapterInterface;
use InvalidArgumentException;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\GridFS\GridFSAdapter;
use MongoClient;

/**
 * This is the gridfs connector class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class GridFSConnector implements FlyAdapterInterface
{
    /**
     * {@inheritdoc}
     *
     * @return GridFSAdapter
     */
    public function connect(Config $config): AdapterInterface
    {
        $client =  new MongoClient($this->get($config, 'server'));

        return new GridFSAdapter($client->selectDB($this->get($config, 'database'))->getGridFS());
    }

    /**
     * @param Config $config
     * @param string $key
     *
     * @throws InvalidArgumentException
     * @return mixed
     */
    private function get(Config $config, string $key)
    {
        if (!$config->has($key)) {
            throw new InvalidArgumentException(\sprintf('The gridfs connector requires "%s" configuration.', $key));
        }

        return $config->get($key);
    }
}
