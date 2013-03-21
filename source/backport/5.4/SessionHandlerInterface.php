<?php
/**
 * @file
 * Backport  file to implement functionality from newer PHP versions.
 *
 * This file is used for the initialization of the base plattform settings
 * like autoloading, translation system...
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref License.
 * @see       http://alinex.de
 */

/**
 * SessionHandlerInterface
 * 
 * SessionHandlerInterface is an interface which defines a prototype for 
 * creating a custom session handler. In order to pass a custom session handler 
 * to session_set_save_handler() using its OOP invocation, the class must 
 * implement this interface.
 * 
 * @see http://php.net/sessionhandlerinterface
 * @see http://php.net/session.customhandler
 * @see http://php.net/session-set-save-handler
 */
interface SessionHandlerInterface
{
     
    /**
     * Initialize session
     * 
     * Re-initialize existing session, or creates a new one. Called when a
     * session starts or when session_start() is invoked.
     *  
     * @param string $savePath The path where to store/retrieve the session.
     * @param string $sessionName The session name.
     *
     * @return bool The return value (usually TRUE on success, FALSE on 
     * failure). Note this value is returned internally to PHP for processing. 
     * @throws \RuntimeException If something goes wrong starting the session.
     * 
     * @see http://php.net/sessionhandlerinterface.open
     */
    public function open($savePath, $sessionName);

    /**
     * Close the session.
     *
     * Closes the current session. This function is automatically executed when
     * closing the session, or explicitly via session_write_close(). 
     * 
     * @return bool The return value (usually TRUE on success, FALSE on 
     * failure). Note this value is returned internally to PHP for processing. 
     * 
     * @see http://php.net/sessionhandlerinterface.close
     */
    public function close();

    /**
     * Read session data.
     *
     *  Reads the session data from the session storage, and returns the 
     * results. Called right after the session starts or when session_start() 
     * is called. Please note that before this method is called 
     * SessionHandlerInterface::open() is invoked.
     * 
     * This method is called by PHP itself when the session is started. This 
     * method should retrieve the session data from storage by the session ID 
     * provided. The string returned by this method must be in the same 
     * serialized format as when originally passed to the 
     * SessionHandlerInterface::write() If the record was not found, return an 
     * empty string.
     * 
     * The data returned by this method will be decoded internally by PHP using 
     * the unserialization method specified in session.serialize_handler. The 
     * resultig data will be used to populate the $_SESSION superglobal.
     * 
     * @note
     * Note that the serialization scheme is not the same as unserialize() and 
     * can be accessed by session_decode(). 
     * 
     * @param string $sessionId The session id
     * @return bool Returns an encoded string of the read data. If nothing was 
     * read, it must return an empty string. Note this value is returned 
     * internally to PHP for processing. 
     *
     * @see http://php.net/sessionhandlerinterface.read
     */
    public function read($sessionId);

    /**
     * Write session data to storage.
     *
     * Writes the session data to the session storage. Called by 
     * session_write_close(), when session_register_shutdown() fails, or 
     * during a normal shutdown. Note: SessionHandlerInterface::close() is 
     * called immediately after this function.
     * 
     * PHP will call this method when the session is ready to be saved and 
     * closed. It encodes the session data from the $_SESSION superglobal to a 
     * serialized string and passes this along with the session ID to this 
     * method for storage. The serialization method used is specified in the 
     * session.serialize_handler setting.
     * 
     * @note
     * Note this method is normally called by PHP after the output buffers have 
     * been closed unless explicitly called by session_write_close() 
     * 
     * @param string $sessionId Session id.
     * @param string $data The encoded session data. This data is the result of
     * the PHP internally encoding the $_SESSION superglobal to a serialized 
     * string and passing it as this parameter. Please note sessions use an 
     * alternative serialization method. 
     *
     * @return bool The return value (usually TRUE on success, FALSE on 
     * failure). Note this value is returned internally to PHP for processing. 
     * 
     * @see http://php.net/sessionhandlerinterface.write
     */
    public function write($sessionId, $data);

    /**
     * Destroys the session.
     *
     * Destroys a session. Called by session_regenerate_id() (with $destroy = 
     * TRUE), session_destroy() and when session_decode() fails. 
     * 
     * @param string $sessionId The session ID being destroyed. 
     * @return bool The return value (usually TRUE on success, FALSE on 
     * failure). Note this value is returned internally to PHP for processing. 
     * 
     * @see http://php.net/sessionhandlerinterface.destroy
     */
    public function destroy($sessionId);

    /**
     * Cleanup old sessions.
     *
     * Cleans up expired sessions. Called by session_start(), based on 
     * session.gc_divisor, session.gc_probability and session.gc_lifetime 
     * settings. 
     * 
     * @param integer $lifetime Sessions that have not updated for the last 
     * maxlifetime seconds will be removed. 
     * @return bool  The return value (usually TRUE on success, FALSE on 
     * failure). Note this value is returned internally to PHP for processing.
     * 
     * @see http://php.net/sessionhandlerinterface.gc
     */
    public function gc($lifetime);
}
