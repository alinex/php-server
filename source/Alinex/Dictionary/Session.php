<?php
/**
 * @file
 * Enhanced session management.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Dictionary;

use Alinex\Util\Http;
use Alinex\Template\Simple;

/**
 * Enhanced session management.
 *
 * This class will give more control over the session. This can be done with
 * using a dictionary engine as storage but also without one.
 *
 * <b>control the session</b>
 *
 * - start() Starts the session - do not use session_start().
 * - migrate(): Regenerates the session ID - do not use session_regenerate_id().
 * This method can optionally change the lifetime of the new cookie that will be
 * emitted by calling this method.
 * - invalidate(): Clears all session data and regenerates session ID. Do not
 * use session_destroy().
 * - getId(): Gets the session ID. Do not use session_id().
 * - setId(): Sets the session ID. Do not use session_id().
 * - getName(): Gets the session name. Do not use session_name().
 * - setName(): Sets the session name. Do not use session_name().
 *
 * <b>Session attributes</b>
 *
 * - set(): Sets an attribute by key;
 * - get(): Gets an attribute by key;
 * - all(): Gets all attributes as an array of key => value;
 * - has(): Returns true if the attribute exists;
 * - keys(): Returns an array of stored attribute keys;
 * - replace(): Sets multiple attributes at once: takes a keyed array and sets
 * each key => value pair.
 * - remove(): Deletes an attribute by key;
 * - clear(): Clear all attributes;
 *
 * @code
 * use Alinex\Dictionary\Session;
 *
 * $session = new Session();
 * $session->start();
 *
 * // set and get session attributes
 * $session->set('name', 'Drak');
 * $session->get('name');
 * @endcode
 */
class Session implements SessionHandlerInterface
{
    /**
     * Time period till the session will be declared as inactive.
     *
     * The session will be considered as inactive after this time period
     * and the user may need to login again if he was authenticated.
     * The period is counted from the last access time.
     */
    const DEFAULT_INACTIVETIME = 900;

    /**
     * @copydoc DEFAULT_INACTIVETIME
     * @registry
     */
    const REGISTRY_INACTIVETIME = 'session.inactive_time';

    /**
     * Time period till the login will be declared as outdated.
     *
     * The login will be considered as outdated after this time period
     * and the user may need to login again if he was authenticated.
     * The time measurement starts after each login again.
     */
    const DEFAULT_LOGINTIME = 21600; // 6 hours

    /**
     * @copydoc DEFAULT_LOGINTIME
     * @registry
     */
    const REGISTRY_LOGINTIME = 'session.login_time';

    /**
     * Time period till the session will be declared as outdated.
     *
     * The session will be considered as outdated after this time period
     * and a new empty session will be created.
     * The period is counted from the session start time.
     */
    const DEFAULT_LIFETIME = 86400; // 24 hours

    /**
     * @copydoc DEFAULT_LIFETIME
     * @registry
     */
    const REGISTRY_LIFETIME = 'session.life_time';

    /**
     * Time period for counting session creation per ip..
     *
     * Only a maximum of new sessions will be created in the specific time range
     * per client ip. This prevents from hijacking using bruteforce attacks on
     * the session id.
     */
    const DEFAULT_IPLOCK_TIME = 60; // 1 minute

    /**
     * @copydoc DEFAULT_IPLOCK_TIME
     * @registry
     */
    const REGISTRY_IPLOCK_TIME = 'session.iplock_time';

    /**
     * Maximum number of new sessions to create in time range.
     *
     * Only a maximum of new sessions will be created in the specific time range
     * per client ip. This prevents from hijacking using bruteforce attacks on
     * the session id.
     */
    const DEFAULT_IPLOCK_NUM = 60; // max. 60 new sessions

    /**
     * @copydoc DEFAULT_IPLOCK_NUM
     * @registry
     */
    const REGISTRY_IPLOCK_NUM = 'session.iplock_num';

    /**
     * Dictionary engine definition to use as storage.
     * @registry
     */
    const REGISTRY_ENGINE = 'session.engine';

    /**
     * Prefix for the data storage-
     * @registry
     */
    const DEFAULT_PREFIX = 'ax:ses:';

    /**
     * Flag if session is already fully initiated.
     * @session
     */
    const SESSION_INITIATED = 'session.initiated';

    /**
     * Md5 of user agent string to check for continuous access.
     * @session
     */
    const SESSION_AGENT = 'session.agent';

    /**
     * Timestamp than the session will end it's lifetime.
     * @session
     */
    const SESSION_LIFETIME = 'session.lifetime';

    /**
     * Timestamp till the session will end if inactive.
     * This will be updated on each access.
     * @session
     */
    const SESSION_INACTIVE = 'session.inactive';

    /**
     * Time of the last login.
     * @session
     */
    const SESSION_LOGINTIME = 'login.time';

    /**
     * Login user id, which will be deleted after end of login time.
     * @session
     */
    const SESSION_LOGINID = 'login.id';

    /**
     * The current session object.
     * @var Session
     */
    private static $_instance = null;

    /**
     * Get the session object.
     * @return Session the singleton instance
     */
    static function getInstance()
    {
        if (!isset(self::$_instance))
            self::$_instance = new self();
        return self::$_instance;
    }

    /**
     * Storage engine to use.
     * @var Engine
     */
    private $_engine = null;

    /**
     * @copydoc DEFAULT_INACTIVETIME
     * @var int
     */
    private $_inactivetime = self::DEFAULT_INACTIVETIME;

    /**
     * @copydoc DEFAULT_LOGINTIME
     * @var int
     */
    private $_logintime = self::DEFAULT_LOGINTIME;

    /**
     * @copydoc DEFAULT_LIFETIME
     * @var int
     */
    private $_lifetime = self::DEFAULT_LIFETIME;

    /**
     * @copydoc DEFAULT_IPLOCK_TIME
     * @var int
     */
    private $_iplocktime = self::DEFAULT_IPLOCK_TIME;

    /**
     * @copydoc DEFAULT_IPLOCK_NUM
     * @var int
     */
    private $_iplocknum = self::DEFAULT_IPLOCK_NUM;

    /**
     * Create a new session handler object.
     *
     * This will rerad in the registry settiongs and apply them.
     */
    private function __construct()
    {
        // check for registry settings
        $registry = Registry::getInstance();
        if ($registry) {
            // add validators
            if ($registry->validatorCheck()) {
                if (!$this->validatorHas(self::REGISTRY_ENGINE))
                    $this->validatorSet(
                        self::REGISTRY_ENGINE, 'Dictionary::engine',
                        array('description' => tr(
                            __NAMESPACE__,
                            'Storage engine used for session data.'
                        ))
                    );
                if (!$this->validatorHas(self::REGISTRY_INACTIVETIME))
                    $this->validatorSet(
                        self::REGISTRY_INACTIVETIME, 'Type::integer',
                        array(
                            'unsigned' => true,
                            'description' => tr(
                                __NAMESPACE__,
                                'Time of no access till an session will be declared as inactive.'
                            )
                        )
                    );
                if (!$this->validatorHas(self::REGISTRY_LOGINTIME))
                    $this->validatorSet(
                        self::REGISTRY_LOGINTIME, 'Type::integer',
                        array(
                            'unsigned' => true,
                            'description' => tr(
                                __NAMESPACE__,
                                'Maximum time to keep an user logged in.'
                            )
                        )
                    );
                if (!$this->validatorHas(self::REGISTRY_LIFETIME))
                    $this->validatorSet(
                        self::REGISTRY_LIFETIME, 'Type::integer',
                        array(
                            'unsigned' => true,
                            'description' => tr(
                                __NAMESPACE__,
                                'Maximum time to keep an session active.'
                            )
                        )
                    );
                if (!$this->validatorHas(self::REGISTRY_IPLOCK_TIME))
                    $this->validatorSet(
                        self::REGISTRY_IPLOCK_TIME, 'Type::integer',
                        array(
                            'unsigned' => true,
                            'description' => tr(
                                __NAMESPACE__,
                                'Timerange for calculating iplock accesses.'
                            )
                        )
                    );
                if (!$this->validatorHas(self::REGISTRY_IPLOCK_NUM))
                    $this->validatorSet(
                        self::REGISTRY_IPLOCK_NUM, 'Type::integer',
                        array(
                            'unsigned' => true,
                            'description' => tr(
                                __NAMESPACE__,
                                'Maximum number of session creation per ip in time range.'
                            )
                        )
                    );
            }
            // set engine
            $this->setEngine(
                $registry->has(self::REGISTRY_ENGINE)
                ? Engine::getInstance($registry->get(self::REGISTRY_ENGINE))
                : Engine\ArrayList::getInstance(self::DEFAULT_PREFIX)
            );
            // config times
            if ($registry->has(self::REGISTRY_INACTIVETIME))
                $this->_inactivetime = $registry->get(
                    self::REGISTRY_INACTIVETIME
                );
            if ($registry->has(self::REGISTRY_LOGINTIME))
                $this->_logintime = $registry->get(self::REGISTRY_LOGINTIME);
            if ($registry->has(self::REGISTRY_LIFETIME))
                $this->_lifetime = $registry->get(self::REGISTRY_LIFETIME);
            if ($registry->has(self::REGISTRY_IPLOCK_TIME))
                $this->_iplocktime = $registry->get(self::REGISTRY_IPLOCK_TIME);
            if ($registry->has(self::REGISTRY_IPLOCK_NUM))
                $this->_iplocknum = $registry->get(self::REGISTRY_IPLOCK_NUM);
        }
    }

    /**
     * Set a specific session egine to use.
     * @param Engine $engine storage engine to use
     */
    public function setEngine(Engine $engine)
    {
        if ($engine instanceof Engine\Session)
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'Engine of type \'Session\' is not possible in Session itself.'
                )
            );
        $this->_engine = $engine;
    }

    /**
     * Start the session handling.
     *
     * The session handling will be started with additional security.
     *
     * The security is enhanced because own session ids are prevented and
     * hijacking is made complicate by using the User-Agent as identifier.
     * New sessions and hijacking tries will automatically get a new
     * initialised session with new session id. In the case of hijacking
     * the old session will be kept.
     *
     * The session contains three time periods, after the login timeout or a
     * maximum inactivity time the user have to login again and after the
     * maximum session lifetime a new and empty session will be opened.
     *
     * @param string $sid session id to use (optional) this should be only used
     * in testing
     * @return void
     * @throw \Exception in case of bruteforce attacks
     */
    public function start($sid)
    {
        assert(is_string($sid));

        if (session_id() != "")
            return; // session already started
        if (isset($sid))
            session_id($sid);
        // register the session engine if defined
        $this->register();
        // start session handling
        session_start();
        // prevent user to use his own session id's
        if (!isset($_SESSION[self::SESSION_INITIATED])) {
            // new session
            $this->create(TRUE);
        // prevent session hijacking
        } else if (isset($_SESSION[self::SESSION_AGENT])
            && $_SESSION[self::SESSION_AGENT]
                != md5($_SERVER['HTTP_USER_AGENT'])) {
            Logger::getInstance()->warn(
                'Possible case of session hijacking because user-agent changed'
            );
            // request with new session
            $this->create();
        // check for outdated session
        } else if (isset($_SESSION[self::REGISTRY_LIFETIME])
            && $_SESSION[self::REGISTRY_LIFETIME] < time()) {
            // new session
            Logger::getInstance()->info(
                Simple::run(
                    'Session outdated at {time|date Y-m-d H:i:sO}',
                    array('time' => $_SESSION[self::REGISTRY_LIFETIME])
                )
            );
            $this->create(TRUE);
        // check session timeouts
        } else {
            // check for inactive session or outdated login
            if ((isset($_SESSION[self::SESSION_INACTIVE])
                    && $_SESSION[self::SESSION_INACTIVE] < time())
                || (isset($_SESSION[self::SESSION_LOGINTIME])
                    && $_SESSION[self::SESSION_LOGINTIME] < time())) {
                // remove login data
                unset($_SESSION[self::SESSION_LOGINID]);
            }
            // set new inactive time
            $_SESSION[self::SESSION_INACTIVE] = time() + self::$inactive;
        }
    }

    /**
     * Create a new session.
     *
     * A new session id is generated, while the old is kept or not.
     * All data of the new session is destroyed and the intial
     * session marks are added.
     *
     * @todo check for bruteforce attacks by locking ip for time if more
     * than x different session_id's per minute.
     *
     * @param bool $removeOld should the old session be removed
     * @throw \Exception in case of bruteforce attacks
     */
    private function create($removeOld = FALSE)
    {
        // check against bruteforce attacks
        $cache = Cache::getInstance();
        $ip = Http::determineIP();
        if (isset($ip)) {
            $check = $cache->get(self::CACHE_BRUTEFORCE.$ip);
            $time = time();
            if (isset($check) && $check[0] > $time) {
                if ($check[1] > $this->_iplocknum) {
                    // block the session by using a new one
                    session_regenerate_id();
                    // throw an exception to be handled
                    throw new \Exception(
                        tr(
                            __NAMESPACE__,
                            'Session could not be creatred because of possible bruteforce attack from {ip]',
                            array('ip' => $ip)
                        )
                    );
                }
            } else {
                $cache->set(
                    self::CACHE_BRUTEFORCE.$ip,
                    array($time + $this->_iplocktime, 1),
                    Engine::SCOPE_GLOBAL
                );
            }
        }
        # create the new session
        Logger::getInstance()->info(
            'New session will be created for '.$ip
        );
        session_regenerate_id($removeOld);
        session_unset();
        $_SESSION[self::SESSION_INITIATED] = TRUE;
        $_SESSION[self::SESSION_INACTIVE] = time() + self::$inactive;
        $_SESSION[self::SESSION_LIFETIME] = time() + self::$lifetime;
        if (isset($_SERVER['HTTP_USER_AGENT']))
            $_SESSION[self::SESSION_AGENT] = md5($_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * Registers the handler instance as the current session handler.
     * @return bool true on success, false if default storage will be used.
     */
    private function register()
    {
        if (!isset($this->_engine)) // no engine set
            return false;
        if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
            session_set_save_handler($this, true);
        } else {
            session_set_save_handler(
                array($this, 'open'),
                array($this, 'close'),
                array($this, 'read'),
                array($this, 'write'),
                array($this, 'destroy'),
                array($this, 'gc')
            );
        }
        return true;
    }

    /**
     * Open the session and initialize it.
     *
     * This method will do nothing and return always <i>TRUE</i>.
     *
     * Opening of session is not needed on this platform because the
     * cache system should already be initialized and running.
     *
     * @return bool always <i>TRUE</i>
     * @see http://www.php.net/manual/en/sessionhandlerinterface.open.php
     */
    public static function open()
    {
        return TRUE;
    }

    /**
     * Close the session.
     *
     * This method will do nothing and return always <i>TRUE</i>.
     *
     * Because the cache always stays open and don't need to be closed the
     * method is not realy needed on this platform.
     *
     * @return bool always <i>TRUE</i>
     * @see http://www.php.net/manual/en/sessionhandlerinterface.close.php
     */
    public static function close()
    {
        return TRUE;
    }

    /**
     * Run garbage collector.
     *
     * This method will be called by the PHP session handler to remove sessions
     * which are out of date.
     *
     * This method will do nothing and return always <i>TRUE</i>.
     *
     * Because of the self management of lifetime in the <i>Cache...</i>
     * methods a separate garbage collector isn't neccessary.
     *
     * The probability to be called on each session initialization is defined
     * by the configuration settings <i>session.gc_probability</i> and
     * <i>session.gc_divisor</i>.
     *
     * @param int $ttl life time in seconds
     * @return bool always <i>TRUE</i>
     * @see http://www.php.net/manual/en/sessionhandlerinterface.gc.php
     */
    public static function gc($maxlifetime)
    {
        return true;
    }

    /**
     * Read session data
     *
     * Reads the session data from the session storage, and returns the results.
     * Called right after the session starts or when start() is called. Please
     * note that before this method is called open() is invoked.
     *
     * This method is called by PHP itself when the session is started. This
     * method should retrieve the session data from storage by the session ID
     * provided. The string returned by this method must be in the same
     * serialized format as when originally passed to the write() method. If
     * the record was not found, return an empty string.
     *
     * The data returned by this method will be decoded internally by PHP using
     * the unserialization method specified in session.serialize_handler. The
     * resultig data will be used to populate the $_SESSION superglobal.
     *
     * @param string $id The session id
     * @return string Returns an encoded string of the read data. If nothing
     * was read, it must return an empty string. Note this value is returned
     * internally to PHP for processing.
     *
     * @see http://www.php.net/manual/en/sessionhandlerinterface.read.php
     */
    public static function read($id)
    {
        return $this->_engine->has($id)
            ? $this->_engine->get($id)
            : '';
    }

    /**
     * Write session data
     *
     * Writes the session data to the session storage. Called by
     * session_write_close(), when session_register_shutdown() fails, or during
     * a normal shutdown. Note: close() is called immediately after this
     * function.
     *
     * PHP will call this method when the session is ready to be saved and
     * closed. It encodes the session data from the $_SESSION superglobal to a
     * serialized string and passes this along with the session ID to this
     * method for storage. The serialization method used is specified in the
     * session.serialize_handler setting.
     *
     * Note this method is normally called by PHP after the output buffers have
     * been closed unless explicitly called by close()
     *
     * @param string $id The session id.
     * @param string $data The encoded session data. This data is the result of
     * the PHP internally encoding the $_SESSION superglobal to a serialized
     * string and passing it as this parameter. Please note sessions use an
     * alternative serialization method.
     * @return bool The return value (usually TRUE on success, FALSE on
     * failure). Note this value is returned internally to PHP for processing.
     * @see http://www.php.net/manual/en/sessionhandlerinterface.write.php
     */
    public static function write($id, $data)
    {
        return $this->_engine->set($id, $data) ? true : false;
    }

    /**
     * Destroy a session
     *
     * Destroys a session. Called by session_regenerate_id()
     * (with $destroy = TRUE), session_destroy() and when session_decode()
     * fails.
     *
     * @param string $id The session ID being destroyed.
     * @return bool The return value (usually TRUE on success, FALSE on
     * failure). Note this value is returned internally to PHP for processing.
     * @see http://www.php.net/manual/en/sessionhandlerinterface.destroy.php
     */
    public static function destroy($id)
    {
        return $this->_engine->remove($id);
    }

}
