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
 *
 * Each handler may process logs. The Logger will call all appended handlers
 * which will use the Filter, Provider and Formatter associated to process
 * the log messages.
 *
 * With Filter you may exclude some log messages under different circumstances,
 * the Provider retrieves additional information and the formatter will create
 * the message out of the collected data. The result will be published from the
 * handler directly.
 */
abstract class Handler
{
    /**
     * Adds a log record to this handler.
     *
     * @param  Message  $message Log message object
     * @return Boolean Whether the record has been processed
     */
    public function log(Message $message)
    {
        // call prefilter
        foreach($this->_filter as $filter)
            if (!$filter->hasBuffer()
                && !$filter->check($message))
                return false;
        // evaluate providers
        foreach($this->_provider as $provider)
            $provider->addTo($message);
        // format message
        $this->_formatter->format($message);
        // rotate through buffer filters
        foreach($this->_filter as $filter)
            if ($filter->hasBuffer()
                && !$filter->check($message))
                return false;
        // write messages
        $this->write($message->formatted);
        return true;
    }

    /**
     * Formatter for this type
     * @var Formatter\Formatter
     */
    protected $_formatter = null;

    /**
     * Get the formatter to configure it.
     * @return \Alinex\Logger\Formatter
     */
    public function getFormatter()
    {
        return $this->_formatter;
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
    public function filterPush(Filter $filter)
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
    public function filterUnshift(Filter $filter)
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
     * @param Provider $provider
     * @return int number of filters in list
     */
    public function providerPush(Provider $provider)
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
     * @param Provider $provider
     * @return int number of filters in list
     */
    public function providerUnshift(Provider $provider)
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