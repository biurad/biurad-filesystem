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

namespace BiuradPHP\FileManager\Interfaces;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

/**
 * This is the connector interface.
 *
 * @author Graham Campbell <graham@alt-three.com>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
interface FlyAdapterInterface
{
    /**
     * Establish an adapter connection.
     *
     * @param Config $config
     *
     * @return AdapterInterface
     */
    public function connect(Config $config): AdapterInterface;
}
