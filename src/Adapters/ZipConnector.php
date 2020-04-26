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

namespace BiuradPHP\FileManager\Adapters;

use BiuradPHP\FileManager\Interfaces\ConnectorInterface;
use Illuminate\Support\Arr;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;

/**
 * This is the zip connector class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class ZipConnector implements ConnectorInterface
{
    /**
     * Establish an adapter connection.
     *
     * @param string[] $config
     *
     * @return \League\Flysystem\ZipArchive\ZipArchiveAdapter
     */
    public function connect(array $config)
    {
        $config = $this->getConfig($config);

        return $this->getAdapter($config);
    }

    /**
     * Get the configuration.
     *
     * @param string[] $config
     *
     * @throws \InvalidArgumentException
     *
     * @return string[]
     */
    protected function getConfig(array $config)
    {
        if (!array_key_exists('path', $config)) {
            throw new \InvalidArgumentException('The zip connector requires path configuration.');
        }

        return Arr::only($config, ['path']);
    }

    /**
     * Get the zip adapter.
     *
     * @param string[] $config
     *
     * @return \League\Flysystem\ZipArchive\ZipArchiveAdapter
     */
    protected function getAdapter(array $config)
    {
        return new ZipArchiveAdapter($config['path']);
    }
}
