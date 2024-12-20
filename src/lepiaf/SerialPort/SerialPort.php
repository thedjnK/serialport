<?php

namespace lepiaf\SerialPort;

use lepiaf\SerialPort\Configure\ConfigureInterface;
use lepiaf\SerialPort\Configure\TTYConfigure;
use lepiaf\SerialPort\Exception\DeviceNotAvailable;
use lepiaf\SerialPort\Exception\DeviceNotFound;
use lepiaf\SerialPort\Exception\DeviceNotOpened;
use lepiaf\SerialPort\Exception\WriteNotAllowed;
use lepiaf\SerialPort\Parser\ParserInterface;
use lepiaf\SerialPort\Parser\SeparatorParser;

/**
 * SerialPort to handle serial connection easily with PHP
 * Suitable for Arduino communication
 *
 * @author Thierry Thuon <lepiaf@users.noreply.github.com>
 * @copyright MIT
 */
class SerialPort
{
    /**
     * File descriptor
     *
     * @var resource
     */
    private $fd = false;

    /**
     * @var ParserInterface
     */
    private $parser;

    /**
     * @var ConfigureInterface
     */
    private $configure;

    /**
     * @var dio
     */
    private $dio;

    /**
     * @param ParserInterface|null    $parser
     * @param ConfigureInterface|null $configure
     */
    public function __construct(ParserInterface $parser = null, ConfigureInterface $configure = null)
    {
        $this->parser = $parser;
        $this->configure = $configure;
    }

    /**
     * Open serial connection
     *
     * @param string $device path to device
     * @param string $mode fopen mode
     *
     * @return bool
     *
     * @throws DeviceNotAvailable|DeviceNotFound
     */
    public function open($device, $mode = "r+b")
    {
        if (false === file_exists($device)) {
            throw new DeviceNotFound();
        }

        $this->getConfigure()->configure($device);
        $this->dio = $this->getConfigure()->getOption('dio');

        if (!$this->dio)
        {
            $this->fd = fopen($device, $mode);
        }
        else
        {
            $this->fd = dio_open($device, O_RDWR);
        }

        if (false !== $this->fd) {
            if (!$this->dio)
            {
                stream_set_blocking($this->fd, false);
            }
            return true;
        }

        unset($this->fd);
        throw new DeviceNotAvailable($device);
    }

    /**
     * Write data into serial port line
     *
     * @param string $data
     *
     * @return int length of byte written
     *
     * @throws WriteNotAllowed|DeviceNotOpened
     */
    public function write($data)
    {
        $this->ensureDeviceOpen();

        if (!$this->dio)
        {
            $dataWritten = fwrite($this->fd, $data);
        }
        else
        {
            $dataWritten = dio_write($this->fd, $data);
        }

        if (false !== $dataWritten) {
            if (!$this->dio)
            {
                fflush($this->fd);
            }
            return $dataWritten;
        }

        throw new WriteNotAllowed();
    }

    /**
     * Read data byte per byte until separator found
     *
     * @return string
     */
    public function read($timeout = 0)
    {
        $last_char_time = time();
        $this->ensureDeviceOpen();

        $chars = [];

        do {
            $char = fread($this->fd, 1);

            if ($char === '') {
                if ($timeout > 0 && (time() - $last_char_time) > $timeout) {
                    break;
                }

                continue;
            } else {
                $last_char = time();
            }
            $chars[] = $char;
        } while ($char !== $this->getParser()->getSeparator());

        return $this->getParser()->parse($chars);
    }

    /**
     * Read data byte per byte until function indicates success
     *
     * @param int $timeout
     * @param int $read_size
     * @param int $sleep_period
     *
     * @return string
     */
    public function read_function($timeout = 0, $read_size = 256, $sleep_period = 0)
    {
        $last_char_time = time();
        $this->ensureDeviceOpen();
        $this->getParser()->setup();

        do {
            if (!$this->dio)
            {
                $char = fread($this->fd, $read_size);
            }
            else
            {
                $char = dio_read($this->fd, $read_size);
            }

            if ($char === '') {
                if ($timeout > 0 && (time() - $last_char_time) > $timeout) {
                    break;
                }

		if ($sleep_period > 0) {
			usleep($sleep_period);
		}

                continue;
            } else {
                $last_char = time();
            }

            $this->getParser()->append($char);
        } while (!$this->getParser()->is_finished());

        return $this->getParser()->parsed();
    }

    /**
     * Close serial connection
     *
     * @return bool return true on success
     *
     * @throws DeviceNotOpened
     */
    public function close()
    {
        $this->ensureDeviceOpen();

        $hasCloseFd = fclose($this->fd);
        $this->fd = false;

        return $hasCloseFd;
    }

    /**
     * configure serial line
     *
     * @return ConfigureInterface
     */
    private function getConfigure()
    {
        if (null === $this->configure) {
            $this->configure = new TTYConfigure();
        }

        return $this->configure;
    }

    /**
     * Get parser, if not defined, return new line parser by default
     *
     * @return ParserInterface
     */
    private function getParser()
    {
        if (null === $this->parser) {
            $this->parser = new SeparatorParser();
        }

        return $this->parser;
    }

    /**
     * @throws DeviceNotOpened
     */
    public function ensureDeviceOpen()
    {
        if (!$this->fd) {
            throw new DeviceNotOpened();
        }
    }
}
