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
use League\Flysystem\Adapter\Ftp as FtpAdapter;

/**
 * This is the ftp connector class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class FtpConnector implements ConnectorInterface
{
    /**
     * Establish an adapter connection.
     *
     * @param string[] $config
     *
     * @return FtpAdapter
     */
    public function connect(array $config)
    {
        return $this->getAdapter($config);
    }

    /**
     * Get the ftp adapter.
     *
     * @param string[] $config
     *
     * @return FtpAdapter
     */
    protected function getAdapter(array $config)
    {
        return new FtpAdapter($config);
    }
}
