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

namespace BiuradPHP\FileManager\Streams;

/**
 * Represents a stream mode.
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class StreamMode
{
    private $mode;

    private $base;

    private $plus;

    private $flag;

    /**
     * @param string $mode A stream mode as for the use of fopen()
     */
    public function __construct($mode)
    {
        $this->mode = $mode;

        $mode = \substr($mode, 0, 3);
        $rest = \substr($mode, 1);

        $this->base = \substr($mode, 0, 1);
        $this->plus = false !== \strpos($rest, '+');
        $this->flag = \trim($rest, '+');
    }

    /**
     * Returns the underlying mode.
     *
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Indicates whether the mode allows to read.
     *
     * @return bool
     */
    public function allowsRead(): bool
    {
        if ($this->plus) {
            return true;
        }

        return 'r' === $this->base;
    }

    /**
     * Indicates whether the mode allows to write.
     *
     * @return bool
     */
    public function allowsWrite(): bool
    {
        if ($this->plus) {
            return true;
        }

        return 'r' !== $this->base;
    }

    /**
     * Indicates whether the mode allows to open an existing file.
     *
     * @return bool
     */
    public function allowsExistingFileOpening(): bool
    {
        return 'x' !== $this->base;
    }

    /**
     * Indicates whether the mode allows to create a new file.
     *
     * @return bool
     */
    public function allowsNewFileOpening(): bool
    {
        return 'r' !== $this->base;
    }

    /**
     * Indicates whether the mode implies to delete the existing content of the
     * file when it already exists.
     *
     * @return bool
     */
    public function impliesExistingContentDeletion(): bool
    {
        return 'w' === $this->base;
    }

    /**
     * Indicates whether the mode implies positioning the cursor at the
     * beginning of the file.
     *
     * @return bool
     */
    public function impliesPositioningCursorAtTheBeginning(): bool
    {
        return 'a' !== $this->base;
    }

    /**
     * Indicates whether the mode implies positioning the cursor at the end of
     * the file.
     *
     * @return bool
     */
    public function impliesPositioningCursorAtTheEnd(): bool
    {
        return 'a' === $this->base;
    }

    /**
     * Indicates whether the stream is in binary mode.
     *
     * @return bool
     */
    public function isBinary(): bool
    {
        return 'b' === $this->flag;
    }

    /**
     * Indicates whether the stream is in text mode.
     *
     * @return bool
     */
    public function isText(): bool
    {
        return false === $this->isBinary();
    }
}
