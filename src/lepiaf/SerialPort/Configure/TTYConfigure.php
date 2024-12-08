<?php

namespace lepiaf\SerialPort\Configure;

/**
 * Configuration for linux/windows
 */
class TTYConfigure implements ConfigureInterface
{
    /**
     * Default configuration, suitable for Arduino serial connection
     *
     * @var array
     */
    private $options = [
        'baud' => 115200,
//        'parity' => 'none',
//        'flow_control' => 'off',
        'data_bits' => 8,
//        'stop_bits' => 1,
        'dio' => false
    ];

    /**
     * {@inheritdoc}
     */
    public function configure($device)
    {
        if (PHP_OS_FAMILY == 'Windows')
        {
            exec(sprintf('mode %s BAUD=%d DATA=%d PARITY=n STOP=1 xon=off rts=off', $device, $this->options['baud'], $this->options['data_bits']));
        }
        else
        {
            exec(sprintf('stty -F %s %d cs%d -brkint -icrnl -imaxbel -opost -onlcr -isig -icanon -iexten -echo -echoe -echok -echoctl -echoke -ixon -crtscts ignbrk noflsh', $device, $this->options['baud'], $this->options['data_bits']));
        }
    }

    /**
     * @param string $name
     * @param int    $value
     */
    public function setOption($name, $value): bool
    {
        if (!isset($this->options[$name]))
        {
            return false;
        }

        $this->options[$name] = $value;
        return true;
    }

    /**
     * @param string $name
     */
    public function getOption($name): mixed
    {
        if (!isset($this->options[$name]))
        {
            return NULL;
        }

        return $this->options[$name];
    }
}
