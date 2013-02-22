<?php
/**
 * @file
 * Abstract handler for log managing.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Logger;

/**
 * Abstract handler for log managing.
 */
abstract class Handler
{
    /**
     * Adds a log record to this handler.
     *
     * @param  mixed   $level   The log level
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function log($level, $message, array $context = array())
    {
        // evaluate providers
        $data = array();
        foreach($this->_provider as $provider)
            $data = array_merge($data, $provider->getData());
        // rotate through filters
        foreach($this->_filter as $filter)
            if (!$filter->check($level, $message, $context, $data))
                return false;
        // format message
        $formatted = $this->_formatter->format($level, $message, $context, $data);
        // write message
        $this->write($formatted);
        return true;
    }
    
    /**
     * Formatter for this type
     * @var Formatter\Formatter 
     */
    protected $_formatter = null;
    
    /**
     * Set a formatter for the message.
     * @param \Alinex\Logger\Formatter $formatter
     */
    function setFormatter(Formatter $formatter)
    {
        $this->_formatter = $formatter;
    }
    
    /**
     * Stack of filters to use.
     * @var array
     */
    private $_filter = array();
    
    /**
     * Add filter to the end of the list
     * @param Filter $filter
     * @return int number of filters in list
     */
    public function filterPush($filter)
    {
        return array_push($this->_filter, $filter);
    }
    
    /**
     * Remove filter from the end of the list
     * @return Filter last filter of stack
     */
    public function filterPop()
    {
        return array_pop($this->_filter);
    }
    
    /**
     * Add filter to the start of the list
     * @param Filter $filter
     * @return int number of filters in list
     */
    public function filterUnshift($filter)
    {
        return array_unshift($this->_filter, $filter);
    }
    
    /**
     * Rdmove first filter from list
     * @return Filter first filter of stack
     */
    public function filterShift()
    {
        return array_shift($this->_filter);
    }

    /**
     * Stack of filters to use.
     * @var array
     */
    private $_provider = array();
    
    /**
     * Push provider onto stack.
     * @param Provider $filter
     * @return int number of filters in list
     */
    public function providerPush($provider)
    {
        array_push($this->_provider, $provider);
    }
    
    /**
     * Pop provider from the stack.
     * @return Provider last provider of stack
     */
    public function providerPop()
    {
        return array_pop($this->_provider);
    }
    
    /**
     * Push provider to the start of the stack.
     * @param Provider $filter
     * @return int number of filters in list
     */
    public function providerUnshift($provider)
    {
        array_unshift($this->_provider, $provider);
    }
    
    /**
     * Pop provider from the stack.
     * @return Provider first provider of stack
     */
    public function providerShift()
    {
        return array_shift($this->_provider);
    }
    
    /**
     * Write the log message down.
     * @param mixed $format formatted log message
     */
    abstract protected function write($format);
}