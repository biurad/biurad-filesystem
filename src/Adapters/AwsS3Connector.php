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

use Aws\S3\S3Client;
use BiuradPHP\FileManager\Interfaces\FlyAdapterInterface;
use InvalidArgumentException;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Config;

/**
 * This is the awss3 connector class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 * @author Raul Ruiz <publiux@gmail.com>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class AwsS3Connector implements FlyAdapterInterface
{
    /**
     * {@inheritdoc}
     *
     * @return AwsS3Adapter
     */
    public function connect(Config $config): AdapterInterface
    {
        $client = new S3Client($this->getAuth($config));

        return new AwsS3Adapter($client, $this->get($config, 'bucket'), $config->get('prefix'));
    }

    /**
     * Get the authentication data.
     *
     * @param Config $config
     *
     * @throws InvalidArgumentException
     * @return array
     */
    protected function getAuth(Config $config): array
    {
        $auth = [
            'region'  => $this->get($config, 'region'),
            'version' => $this->get($config, 'version'),
        ];

        if ($config->has('key')) {
            if (!$config->has('secret')) {
                throw new InvalidArgumentException('The awss3 connector requires authentication.');
            }
            $auth['credentials'] = ['key' => $config->get('key'), 'secret' => $config->get('secret')];
        }

        if ($config->has('bucket_endpoint')) {
            $auth['bucket_endpoint'] = $config->get('bucket_endpoint');
        }

        if ($config->has('calculate_md5')) {
            $auth['calculate_md5'] = $config->get('calculate_md5');
        }

        if ($config->has('scheme')) {
            $auth['scheme'] = $config->get('scheme');
        }

        if ($config->has('endpoint')) {
            $auth['endpoint'] = $config->get('endpoint');
        }

        return $auth;
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
            throw new InvalidArgumentException(\sprintf('The awss3 connector requires "%s" configuration.', $key));
        }

        return $config->get($key);
    }
}
