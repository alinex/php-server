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

use Alinex\Logger\Message;
use Alinex\Validator;

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
            if (!$filter::IS_POSTFILTER
                && !$filter->check($message))
                return false;
        // evaluate providers
        foreach($this->_provider as $provider)
            $provider->addTo($message);
        // format message
        $this->_formatter->format($message);
        // rotate through buffer filters
        foreach($this->_filter as $filter)
            if ($filter::IS_POSTFILTER
                && !$filter->check($message))
                return false;
        // write messages
        $this->write($message);
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
     * Add filter onto stack.
     * @param string $class class name
     * @return Filter new filter instance
     */
    public function addFilter($class)
    {
        $class = Validator\Code::phpClass(
            $class, 'filter', array('relative' => '\Alinex\Logger\Filter')
        );
        // check if filter already set
        if (isset($this->_filter[$class]))
            return $this->_filter[$class];
        // create new filter
        $filter = new $class;
        $this->_filter[$class] = $filter;
        // also add needed providers
        foreach($filter::$needProvider as $provider)
            $this->addProvider ($provider);
        // return filter
        return $filter;
    }

    /**
     * Get the filter instance.
     * @param string $class class name
     * @return Filter instance or null if not set
     */
    public function getFilter($class)
    {
        $class = Validator\Code::phpClass(
            $class, 'filter', array('relative' => '\Alinex\Logger\Filter')
        );
        return isset($this->_filter[$class]) ? $this->_filter[$class] : null;
    }

    /**
     * Remove filter from stack.
     * @param string $class class name
     */
    public function removeFilter($class)
    {
        $class = Validator\Code::phpClass(
            $class, 'filter', array('relative' => '\Alinex\Logger\Filter')
        );
        unset($this->_filter[$class]);
    }

    /**
     * Stack of filters to use.
     * @var array
     */
    private $_provider = array();

    /**
     * Add provider onto stack.
     * @param string $class class name
     * @return Provider instance of this provider
     */
    public function addProvider($class)
    {
        $class = Validator\Code::phpClass(
            $class, 'provider', array('relative' => '\Alinex\Logger\Provider')
        );
        // check if provider already set
        if (isset($this->_provider[$class]))
            return $this->_provider[$class];
        // create new provider
        $provider = new $class;
        $this->_provider[$class] = $provider;
        // return provider
        return $provider;
    }

    /**
     * Get the provider if set.
     * @param string $class provider class name
     * @return Provider instance or null if not set
     */
    public function getProvider($class)
    {
        $class = Validator\Code::phpClass(
            $class, 'provider', array('relative' => '\Alinex\Logger\Provider')
        );
        return isset($this->_provider[$class])
            ? $this->_provider[$class] : null;
    }

    /**
     * Remove the provider.
     * @param string $class provider class name
     */
    public function removeProvider($class)
    {
        $class = Validator\Code::phpClass(
            $class, 'provider', array('relative' => '\Alinex\Logger\Provider')
        );
        unset($this->_provider[$class]);
    }

    /**
     * Write the log message down.
     * @param  Message  $message Log message object
     */
    abstract protected function write(Message $message);
}