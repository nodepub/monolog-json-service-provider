<?php

namespace NodePub\Monolog;

use Monolog\Formatter\FormatterInterface;

/**
 * Encodes whatever record data is passed to it as json,
 * based on the original JsonFormatter in Monolog core
 */
class JsonFormatter implements FormatterInterface
{
    const DATE_FORMAT = 'Y-m-d H:i:s';
    
    protected $filteredKeys = array('extra', 'channel');

    public function format(array $record)
    {
        return json_encode($this->filterRecord($record)).PHP_EOL;
    }

    public function formatBatch(array $records)
    {
        return array_map(array($this, 'format'), $records);
    }

    /**
     * Filters out record keys from Monolog that we don't need
     */
    protected function filterRecord(array $record)
    {
        foreach ($this->filteredKeys as $key) {
            if (isset($record[$key]) {
                unset($record[$key]);
            }
        }
        
        // Convert datetime object to string
        if (! isset($record['datetime'])) {
            $record['datetime'] = new \DateTime("now");
        }
        $record['datetime'] = $record['datetime']->format(self::DATE_FORMAT);

        return $record;
    }
}
