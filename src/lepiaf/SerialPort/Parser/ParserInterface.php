<?php

namespace lepiaf\SerialPort\Parser;

interface ParserInterface
{
    /** Basic command parser, use with read(); */

    /**
     * Parse data right after read from serial connection
     *
     * @param array $chars array of chars
     *
     * @return mixed
     */
    public function parse(array $chars);

    /**
     * Separator use to split data
     *
     * @return string
     */
    public function getSeparator();

    /** Advanced function parser, use with read_function(); */

    /**
     * Sets up function parser for usage
     */
    public function setup();

    /**
     * Add character to receive buffer
     */
    public function append($char);

    /**
     * Returns true if all data for message have been received
     *
     * @return bool
     */
    public function is_finished();

    /**
     * Returns parsed data
     *
     * @return string
     */
    public function parsed();
}
