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
use Alinex\Util;

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
 *
 * @pattern{Chaining} For the addition of Provider and Filter.
 */
abstract class Handler implements Util\EventObserver
{
    /**
     * Adds a log record to this handler.
     *
     * @param  Message  $message Log message object
     * @return Util\Event Whether the record has been processed
     */
    public function update(Util\Event $message)
    {
        // only message event allowed
        assert($message instanceof Message);

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
     * Set the formatter to use
     * @param \Alinex\Logger\Formatter $formatter Formatter instance to use
     */
    public function setFormatter(Formatter $formatter)
    {
        $this->_formatter = $formatter;
    }

    /**
     * Stack of filters to use.
     * @var array
     */
    private $_filter = array();

    /**
     * Add filter onto stack.
     * @param string|Filter $class class name or Filter instance
     * @return Handler
     */
    public function addFilter($class)
    {
        // $class have to be a Filter object or existing callable
        assert(
            $class instanceof Filter
            || $class = Validator\Code::phpClass(
                $class, 'filter', array('relative' => '\Alinex\Logger\Filter')
            )
        );

        if (is_string($class)) {
            // create new filter
            $filter = new $class();
            // given class name has to be a Filter
            assert($filter instanceof Filter);
        } else {
            // use Filter object
            $filter = $class;
            $class = get_class($filter);
        }
        // check if filter already set
        if (isset($this->_filter[$class]))
            return $this->_filter[$class];
        // set filter
        $this->_filter[$class] = $filter;
        // also add needed providers
        foreach($filter::$needProvider as $provider)
            $this->addProvider($provider);
        // return handler
        return $this;
    }

    /**
     * Get the filter instance.
     * @param string|Filter $class class name or Filter instance
     * @return Filter instance or null if not set
     */
    public function getFilter($class)
    {
        // $class have to be a Filter object or existing callable
        assert(
            $class instanceof Filter
            || $class = Validator\Code::phpClass(
                $class, 'filter', array('relative' => '\Alinex\Logger\Filter')
            )
        );

        if (!is_string($class))
            // get class name from filter
            $class = get_class($class);
        return isset($this->_filter[$class]) ? $this->_filter[$class] : null;
    }

    /**
     * Remove filter from stack.
     * @param string|Filter $class class name or Filter instance
     * @return Handler
     */
    public function removeFilter($class)
    {
        // $class have to be a Filter object or existing callable
        assert(
            $class instanceof Filter
            || $class = Validator\Code::phpClass(
                $class, 'filter', array('relative' => '\Alinex\Logger\Filter')
            )
        );

        if (!is_string($class))
            // get class name from filter
            $class = get_class($class);
        unset($this->_filter[$class]);
        return $this;
    }

    /**
     * Stack of filters to use.
     * @var array
     */
    private $_provider = array();

    /**
     * Add provider onto stack.
     * @param Provider|string $class Provider object or class name
     * @return Handler
     */
    public function addProvider($class)
    {
        // $class has to be a Provider object or an existing class
        assert(
            $class instanceof Provider
            || $class = Validator\Code::phpClass(
                $class, 'provider', array(
                    'relative' => '\Alinex\Logger\Provider'
                )
            )
        );

        if (is_string($class)) {
            // create new provider
            $provider = new $class();
            // given class name has to be a Provider
            assert($provider instanceof Provider);
        } else {
            // use Provider object
            $provider = $class;
            $class = get_class($provider);
        }
        // check if provider already set
        if (isset($this->_provider[$class]))
            return $this->_provider[$class];
        // set provider
        $this->_provider[$class] = $provider;
        // return provider
        return $this;
    }

    /**
     * Get the provider if set.
     * @param Provider|string $class Provider object or class name
     * @return Provider instance or null if not set
     */
    public function getProvider($class)
    {
        // $class has to be a Provider object or an existing class
        assert(
            $class instanceof Provider
            || $class = Validator\Code::phpClass(
                $class, 'provider', array(
                    'relative' => '\Alinex\Logger\Provider'
                )
            )
        );

        if (!is_string($class))
            // get class name from filter
            $class = get_class($class);
        return isset($this->_provider[$class])
            ? $this->_provider[$class] : null;
    }

    /**
     * Remove the provider.
     * @param Provider|string $class Provider object or class name
     * @return Handler
     */
    public function removeProvider($class)
    {
        // $class has to be a Provider object or an existing class
        assert(
            $class instanceof Provider
            || $class = Validator\Code::phpClass(
                $class, 'provider', array(
                    'relative' => '\Alinex\Logger\Provider'
                )
            )
        );

        if (!is_string($class))
            // get class name from filter
            $class = get_class($class);
        unset($this->_provider[$class]);
        return $this;
    }

    /**
     * Write the log message down.
     * @param  Message  $message Log message object
     */
    abstract protected function write(Message $message);
}