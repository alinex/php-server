<?php
/**
 * @file
 * IO validators.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de
 */

namespace Alinex\Validator;
use Alinex\Util\String;

/**
 * IO validators.
 *
 * @see Alinex\Validator for general info
 */
class IO
{
    /**
     * Check for path
     *
     * <b>Stringcheck Options:</b>
     * - \c disallowRelative - disallow relative paths
     * - \c disallowAbsolute - disallow absolute paths
     * - \c allowBackreferences - allow backreferences in relative file names
     *
     * <b>Resolve Options:</b>
     * - \c base - base path for relative paths (also used for filechecks)
     * - \c makeAbsolute - allow backreferences in relative file names
     * - \c resolve - resolve the file path (removes backreferences)
     *
     * <b>Filecheck Options:</b>
     * - \c exists - check that the file or directory exists
     * - \c readable - check that the file is readable
     * - \c writable - check that the file is writable or can be created
     * - \c parentExists - check for existing parent directory
     * - \c filetype - should be existing 'file', 'dir' or 'link'
     * - \c mimetype - existing file with specified mimetype (list possible)
     *
     * If relative or absolute paths are allowed this may be risky because that
     * may give access to everywhere in the system, if the value can be user
     * specified.
     *
     * @param mixed   $value    value to be checked
     * @param string  $name     readable origin identification
     * @param array   $options  specific settings
     *
     * @return string path to use
     * @throws Exception if not valid
     */
    static function path($value, $name, array $options = null)
    {
        // name of origin have to be a string
        assert(is_string($name));

        $options = self::pathOptions($options);
        try {
            $value = Type::string(
                $value, $name,
                array(
                )
            );
        } catch (Exception $ex) {
            throw $ex->createOuter(__METHOD__, $options);
        }
        // replace directory separator with / if system uses another one
        // this may occure on windows but a / will be perfect for all
        if (DIRECTORY_SEPARATOR != '/')
            $value = str_replace(DIRECTORY_SEPARATOR, '/', $value);

        // check for relative paths
        if ($value[0] != '/'
            && isset($options['disallowRelative'])
            && $options['disallowRelative'] === true)
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'Relative path {value} is not allowed',
                    array('value' => String::dump($value))
                ), $value, $name, __METHOD__, $options
            );
        // check for absolute paths
        if ($value[0] == '/'
            && isset($options['disallowAbsolute'])
            && $options['disallowAbsolute'] === true)
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'Absolute path {value} is not allowed',
                    array('value' => String::dump($value))
                ), $value, $name, __METHOD__, $options
            );
        // check for backreferences
        if ((preg_match('#^\.\./#', $value) || preg_match('#/\.\./#', $value))
            && (!isset($options['allowBackreferences'])
            || $options['allowBackreferences'] === false))
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'Backreferences in path {value} are not allowed',
                    array('value' => String::dump($value))
                ), $value, $name, __METHOD__, $options
            );
        // support base path
        if (isset($options['base']) && $value[0] !== '/')
            $realpath = $options['base'].$value;
        else
            $realpath = $value;
        // calculate realfile
        if ((!isset($options['disallowRelative'])
                || $options['disallowRelative'] === false)
            && ((isset($options['makeAbsolute'])
                    && $options['makeAbsolute'] === true)
                || (isset($options['exists'])
                    && $options['exists'] === true)
                || (isset($options['readable'])
                    && $options['readable'] === true)
                || (isset($options['writable'])
                    && $options['writable'] === true)
                || (isset($options['parentExists'])
                    && $options['parentExists'] === true)) ) {
            // change value if requested
            if ((isset($options['makeAbsolute'])
                    && $options['makeAbsolute'] === true))
                $value = $realpath;
        }
        // resolve if set
        if (isset($options['resolve']) && $options['resolve'] === true) {
            /* replace '//' or '/./' or '/foo/../' with '/' */
            $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
            for ($n=1; $n>0; $value=preg_replace($re, '/', $value, -1, $n)) {
            }
            // special replace for first level in relative paths
            $value = preg_replace('#^(\.?/+)+#', '', $value);
            $value = preg_replace('#^(?!\.\.)[^/]+/\.\./#', '', $value);
        }
        // check realfile
        if (isset($options['exists']) && $options['exists'] === true
            && !file_exists($realpath))
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'The path {value} didn\'t exist',
                    array('value' => String::dump($value))
                ), $value, $name, __METHOD__, $options
            );
        if (isset($options['writable']) && $options['writable'] === true
            && !is_writable(dirname($realpath)) && !is_writable($realpath))
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'The path {value} should be writable',
                    array('value' => String::dump($value))
                ), $value, $name, __METHOD__, $options
            );
        if (isset($options['readable']) && $options['readable'] === true
            && !is_readable($realpath))
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'The path {value} should be readable',
                    array('value' => String::dump($value))
                ), $value, $name, __METHOD__, $options
            );
        if (isset($options['parentExists']) && $options['parentExists'] === true
            && !file_exists(dirname($realpath)))
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'The parent directory for {value} didn\'t exist',
                    array('value' => String::dump($value))
                ), $value, $name, __METHOD__, $options
            );
        // check filetype
        if (isset($options['filetype'])) {
            if ($options['filetype'] == 'file'
                && !is_file($realpath))
                throw new Exception(
                    tr(
                        __NAMESPACE__,
                        'The path {value} should be a file',
                        array('value' => String::dump($value))
                    ), $value, $name, __METHOD__, $options
                );
            else if ($options['filetype'] == 'dir'
                && !is_dir($realpath))
                throw new Exception(
                    tr(
                        __NAMESPACE__,
                        'The path {value} should be a directory',
                        array('value' => String::dump($value))
                    ), $value, $name, __METHOD__, $options
                );
            else if ($options['filetype'] == 'link'
                && !is_link($realpath))
                throw new Exception(
                    tr(
                        __NAMESPACE__,
                        'The path {value} should be a softlink',
                        array('value' => String::dump($value))
                    ), $value, $name, __METHOD__, $options
                );
        }
        if (isset($options['mimetype'])) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimetype = $finfo->file($realpath);
            if (!in_array($mimetype, $options['mimetype']))
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'The file\'s mimetype {type} is not allowed',
                    array('type' => String::dump($mimetype))
                ), $value, $name, __METHOD__, $options
            );
        }
        // return value
        return $value;
    }

    /**
     * Optimize the options.
     *
     * @param array $options specific settings
     * @return array optimized options
     */
    private static function pathOptions(array $options = null)
    {
        if (!isset($options))
            $options = array();

        // options have to be an array
        assert(is_array($options));
        // check for invalid options
        assert(
            count(
                array_diff(
                    array_keys($options),
                    array(
                        'description',
                        'disallowRelative',
                        'disallowAbsolute',
                        'allowBackreferences',
                        'base',
                        'makeAbsolute',
                        'resolve',
                        'exists',
                        'readable',
                        'writable',
                        'parentExists',
                        'filetype',
                        'mimetype'
                    )
                )
            ) == 0
        );
        // check options format
        assert(
            !isset($options['disallowRelative'])
            || is_bool($options['disallowRelative'])
        );
        assert(
            !isset($options['disallowAbsolute'])
            || is_bool($options['disallowAbsolute'])
        );
        assert(
            !isset($options['allowBackreferences'])
            || is_bool($options['allowBackreferences'])
        );
        assert(
            !isset($options['base'])
            || is_string($options['base'])
        );
        assert(
            !isset($options['makeAbsolute'])
            || is_bool($options['makeAbsolute'])
        );
        assert(
            !isset($options['exists'])
            || is_bool($options['exists'])
        );
        assert(
            !isset($options['readable'])
            || is_bool($options['readable'])
        );
        assert(
            !isset($options['writable'])
            || is_bool($options['writable'])
        );
        assert(
            !isset($options['parentExists'])
            || is_bool($options['parentExists'])
        );
        // at least one path alternative should be allowed
        assert(
            !(
                isset($options['disallowAbsolute'])
                && isset($options['disallowRelative'])
            )
        );
        assert(
            !isset($options['filetype'])
            || in_array(
                $options['filetype'],
                array('file', 'dir', 'link')
            )
        );
        assert(
            !isset($options['mimetype'])
            || is_string($options['mimetype'])
            || is_array($options['mimetype'])
        );

        // append slash to base dir if missing
        if (isset($options['base']) && $options['base'][strlen($options['base'])-1] != '/')
            $options['base'] .= '/';
        if (isset($options['resolve']) && $options['resolve'] === true)
            $options['allowBackreferences'] = true;
        // filetype and mimetype
        if (isset($options['filetype']))
            $options['exists'] = true;
        if (isset($options['mimetype'])) {
            $options['exists'] = true;
            $options['filetype'] = 'file';
            if (is_string($options['mimetype']))
                $options['mimetype'] = array($options['mimetype']);
        }
        // return optimized array
        return $options;
    }

    /**
     * Get a human readable description for validity.
     *
     * @param array   $options  options from check
     * @return string explaining message
     */
    static function pathDescription(array $options = null)
    {
        $options = self::pathOptions($options);
        // create message
        if (isset($options['filetype'])) {
            if ($options['filetype'] == 'file')
                $desc = tr(
                    __NAMESPACE__,
                    'The value has to be a file reference.'
                );
            else if ($options['filetype'] == 'file')
                $desc = tr(
                    __NAMESPACE__,
                    'The value has to be a directory reference.'
                );
            else
                $desc = tr(
                    __NAMESPACE__,
                    'The value has to be a reference to a softlink.'
                );
        } else {
            $desc = tr(
                __NAMESPACE__,
                'The value has to be a filesystem path.'
            );
        }
        if (isset($options['mimetype']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The file have to be one of the following mimetypes: {list}',
                array('list' => String::dump($options['mimetype']))
            );
        if (!isset($options['disallowAbsolute'])
            || $options['disallowAbsolute'] === true)
            $desc .= ' '.tr(
                __NAMESPACE__,
                'An absolute path starting with \'/\' is not allowed.'
            );
        if (!isset($options['disallowRelative'])
            || $options['disallowRelative'] === true)
            $desc .= ' '.tr(
                __NAMESPACE__,
                'Only an absolute path starting with \'/\' is allowed.'
            );
        if (!isset($options['allowBackreferences'])
            || $options['allowBackreferences'] === false)
            $desc .= ' '.tr(
                __NAMESPACE__,
                'Backreferences in the path are not allowed.'
            );
        if (isset($options['base']) && $options['base'] !== false)
            $desc .= ' '.tr(
                __NAMESPACE__,
                'Relative paths will be based at {base}.',
                array('base' => $options['base'])
            );
        if (isset($options['makeAbsolute'])
            && $options['makeAbsolute'] === true)
            $desc .= ' '.tr(
                __NAMESPACE__,
                'Paths will be rewritten as absolute path.'
            );
        if (isset($options['resolve']) && $options['resolve'] === true)
            $desc .= ' '.tr(
                __NAMESPACE__,
                'Backreferences in the path will be resolved.'
            );
        if (isset($options['exists']) && $options['exists'] === true)
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The path should point to an existing path entry.'
            );
        if (isset($options['readable']) && $options['readable'] === true)
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The path should be readable.'
            );
        if (isset($options['writable']) && $options['writable'] === true)
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The path or the parent directory should be writable.'
            );
        if (isset($options['parentExists'])
            && $options['parentExists'] === true)
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The parent directory in the path have to be existing.'
            );
        // return description
        return $desc;
    }

    /**
     * Check for active stream
     *
     * <b>Options:</b>
     * - \c readable - stream has to be at least readable
     * - \c writable - stream has to be at least writable
     * - \c local - true = only local, false = no local streams are allowed
     *
     * @param mixed   $value    value to be checked
     * @param string  $name     readable origin identification
     * @param array   $options  specific settings
     *
     * @return string path to use
     * @throws Exception if not valid
     */
    static function stream($value, $name, array $options = null)
    {
        // name of origin have to be a string
        assert(is_string($name));

        $options = self::streamOptions($options);
        // check for stream resource
        if (!is_resource($value)
            || get_resource_type($value) !== 'stream')
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'The value has to be a stream resource.'
                )
            );
        if ($options[]
            && feof($value))
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'The value has to be a stream resource.'
                )
            );
        if ((isset($options['readable']) && $options['readable'] === true)
            || (isset($options['writable']) && $options['writable'] === true)) {
            $meta = stream_get_meta_data($value);
            if (isset($options['readable']) && $options['readable'] === true
                && !preg_match('#r|.\+#', $meta['mode'])) // only writing
                throw new Exception(
                    tr(
                        __NAMESPACE__,
                        'The stream has to be readable'
                    ), $value, $name, __METHOD__, $options
                );
            if (isset($options['writable']) && $options['writable'] === true
                && $meta['mode'] == 'r') // no writing possible
                throw new Exception(
                    tr(
                        __NAMESPACE__,
                        'The stream has to be writable'
                    ), $value, $name, __METHOD__, $options
                );
        }
        if (isset($options['local'])) {
            if ($options['local'] && !stream_is_local($value))
                throw new Exception(
                    tr(
                        __NAMESPACE__,
                        'Only local streams are allowed here'
                    ), $value, $name, __METHOD__, $options
                );
            else if (!$options['local'] && stream_is_local($value))
                throw new Exception(
                    tr(
                        __NAMESPACE__,
                        'Local streams are not allowed here'
                    ), $value, $name, __METHOD__, $options
                );
        }
        // return value
        return $value;
    }

    /**
     * Optimize the options.
     *
     * @param array $options specific settings
     * @return array optimized options
     */
    private static function streamOptions(array $options = null)
    {
        if (!isset($options))
            $options = array();

        // options have to be an array
        assert(is_array($options));
        // check for invalid options
        assert(
            count(
                array_diff(
                    array_keys($options),
                    array(
                        'readable',
                        'writable',
                        'local'
                    )
                )
            ) == 0
        );
        assert(
            !isset($options['readable'])
            || is_bool($options['readable'])
        );
        assert(
            !isset($options['writable'])
            || is_bool($options['writable'])
        );
        assert(
            !isset($options['local'])
            || is_bool($options['local'])
        );
        // return optimized array
        return $options;
    }

    /**
     * Get a human readable description for validity.
     *
     * @param array   $options  options from check
     * @return string explaining message
     */
    static function streamDescription(array $options = null)
    {
        $options = self::streamOptions($options);
        if (isset($options['readable']) && $options['readable'] === true)
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The stream should be readable.'
            );
        if (isset($options['writable']) && $options['writable'] === true)
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The stream should be writable.'
            );
        if (isset($options['local'])) {
            if ($options['local'])
                $desc .= ' '.tr(
                    __NAMESPACE__,
                    'Only local streams are possible.'
                );
            else
                $desc .= ' '.tr(
                    __NAMESPACE__,
                    'No local streams are allowed.'
                );
        }
        // return description
        return $desc;
    }

    /**
     * Check stream configuration structure
     *
     * The value has to be a structure with:
     * - uri - resource identification or file reference
     * - protocol options
     * 
     * The following protocols are possible in \c URI:
     * - file:// — Accessing local filesystem
     * (http://www.php.net/manual/en/wrappers.file.php)
     * - http:// or https:// — Accessing HTTP(s) URLs
     * (http://www.php.net/manual/en/wrappers.http.php)
     * - ftp:// or ftps:// — Accessing FTP(s) URLs
     * (http://www.php.net/manual/en/wrappers.ftp.php)
     * - php:// — Accessing various I/O streams
     * (http://www.php.net/manual/en/wrappers.php.php)
     * - zlib:// — Compression Streams
     * (http://www.php.net/manual/en/wrappers.compression.php)
     * - data:// — Data (RFC 2397)
     * (http://www.php.net/manual/en/wrappers.data.php)
     * - glob:// — Find pathnames matching pattern
     * (http://www.php.net/manual/en/wrappers.glob.php)
     * - phar:// — PHP Archive
     * (http://www.php.net/manual/en/wrappers.phar.php)
     * - ssh2:// — Secure Shell 2
     * (http://www.php.net/manual/en/wrappers.ssh2.php)
     * - rar:// — RAR
     * (http://www.php.net/manual/en/wrappers.rar.php)
     * - ogg:// — Audio streams
     * (http://www.php.net/manual/en/wrappers.audio.php)
     * - expect:// — Process Interaction Streams
     * (http://www.php.net/manual/en/wrappers.expect.php)
     *
     * @param mixed   $value    value to be checked
     * @param string  $name     readable origin identification
     * @param array   $options  specific settings
     *
     * @return string path to use
     * @throws Exception if not valid
     */
    static function streamconfig($value, $name, array $options = null)
    {
        // name of origin have to be a string
        assert(is_string($name));

        $options = self::pathOptions($options);
        try {
            $value = Type::arraylist($value, $name, self::$_streamconfig);
        } catch (Exception $ex) {
            throw $ex->createOuter(__METHOD__, $options);
        }
        // optimize values
        if (isset($value['http']['follow_location']))
            $value['http']['follow_location'] =
                $value['http']['follow_location'] ? 1 : 0;
        if (isset($value['https']['follow_location']))
            $value['https']['follow_location'] =
                $value['https']['follow_location'] ? 1 : 0;
        // return value
        return $value;
    }

    /**
     * Configuration of streamconfig check.
     * @var array
     */
    private static $_streamconfig = null;

    /**
     * Optimize the options.
     *
     * @param array $options specific settings
     * @return array optimized options
     */
    private static function streamconfigOptions(array $options = null)
    {
        if (isset(self::$_streamconfig))
            return $options;
        // create the protocol structures
        $httpConfig = array(
            'allowedKeys' => array(
                'method', 'header', 'user_agent', 'content',
                'proxy', 'request_fulluri',
                'follow_location', 'max_redirects',
                'protocol_version', 'timeout',
                'ignore_errors'
            ),
            'keySpec' => array(
                'method' => array(
                    'Type::string',
                    array(
                        'values' => array(
                            'POST', 'GET', 'HEAD', 'PUT',
                            'DELETE', 'OPTIONS', 'TRACE',
                            'CONNECT'
                        ),
                        'description' => tr(
                            __NAMESPACE__,
                            'HTTP method supported by the remote server.'
                        )
                    )
                ),
                'header' => array(
                    'Type::string',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'Additional headers to be sent during request.'
                        )
                    )
                ),
                'user_agent' => array(
                    'Type::string',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'User-agent to send if not specified in header.'
                        )
                    )
                ),
                'content' => array(
                    'Type::string',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'Additional data to be sent after the headers.'
                        )
                    )
                ),
                'proxy' => array(
                    'Type::string',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'URI specifying address of proxy server.'
                        )
                    )
                ),
                'request_fulluri' => array(
                    'Type::boolean',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'Send the non-standard full URI in request like required for some proxy servers.'
                        )
                    )
                ),
                'follow_location' => array(
                    'Type::boolean',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'Follow Location header redirects.'
                        )
                    )
                ),
                'max_redirects' => array(
                    'Type::integer',
                    array(
                        'unsigned' => true,
                        type => 8,
                        'description' => tr(
                            __NAMESPACE__,
                            'The max number of redirects to follow. Value 1 or less means that no redirects are followed.'
                        )
                    )
                ),
                'protocol_version' => array(
                    'Type::float',
                    array(
                        'minRange' => 1.0,
                        'maxRange' => 1.1,
                        'round' => 1,
                        'description' => tr(
                            __NAMESPACE__,
                            'HTTP protocol version.'
                        )
                    )
                ),
                'timeout' => array(
                    'Type::float',
                    array(
                        'unsigned' => true,
                        'description' => tr(
                            __NAMESPACE__,
                            'Read timeout in seconds.'
                        )
                    )
                ),
                'ignore_errors' => array(
                    'Type::boolean',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'Fetch the content even on failure status codes.'
                        )
                    )
                )
            )
        );
        $ftpConfig = array(
            'allowedKeys' => array(
                'overwrite',
                'resume_pos',
                'proxy'
            ),
            'keySpec' => array(
                'overwrite' => array(
                    'Type::boolean',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'Allow overwriting of already existing files on remote server.'
                        )
                    )
                ),
                'rseume_pos' => array(
                    'Type::integer',
                    array(
                        'unsigned' => true,
                        'description' => tr(
                            __NAMESPACE__,
                            'File offset at which to begin transfer (download only).'
                        )
                    )
                ),
                'proxy' => array(
                    'Type::string',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'Proxy FTP request via http proxy server.'
                        )
                    )
                ),
            )
        );
        $sslConfig = array(
            'allowedKeys' => array(
                'verify_peer',
                'allow_self_signed',
                'cafile',
                'capath',
                'local_cert',
                'passphrase',
                'CN_match',
                'verify_depth',
                'ciphers',
                'capture_peer_cert',
                'capture_peer_cert_chain',
                'SNI_enabled',
                'SNI_server_name'
            ),
            'keySpec' => array(
                'verify_peer' => array(
                    'Type::boolean',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'Require verification of SSL certificate used.'
                        )
                    )
                ),
                'allow_self_signed' => array(
                    'Type::boolean',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'Allow self-signed certificates.'
                        )
                    )
                ),
                'cafile' => array(
                    'IO::path',
                    array(
                        'filetype' => 'file',
                        'readable' => true,
                        'description' => tr(
                            __NAMESPACE__,
                            'Location of Certificate Authority file on local filesystem which should be used with the verify_peer context option to authenticate the identity of the remote peer.'
                        )
                    )
                ),
                'capath' => array(
                    'IO::path',
                    array(
                        'filetype' => 'dir',
                        'readable' => true,
                        'description' => tr(
                            __NAMESPACE__,
                            'If cafile is not specified or if the certificate is not found there, the directory pointed to by capath is searched for a suitable certificate.'
                        )
                    )
                ),
                'local_cert' => array(
                    'IO::path',
                    array(
                        'filetype' => 'file',
                        'readable' => true,
                        'description' => tr(
                            __NAMESPACE__,
                            'Path to local certificate file on filesystem. It must be a PEM encoded file which contains your certificate and private key.'
                        )
                    )
                ),
                'passphrase' => array(
                    'Type::string',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'Passphrase with which your local_cert file was encoded.'
                        )
                    )
                ),
                'CN_match' => array(
                    'Type::string',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'Common Name we are expecting.'
                        )
                    )
                ),
                'verify_depth' => array(
                    'Type::integer',
                    array(
                        'unsigned' => true,
                        'description' => tr(
                            __NAMESPACE__,
                            'Abort if the certificate chain is too deep.'
                        )
                    )
                ),
                'ciphers' => array(
                    'Type::string',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'Sets the list of available ciphers.'
                        )
                    )
                ),
                'capture_peer_cert' => array(
                    'Type::boolean',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'If set to TRUE a peer_certificate context option will be created containing the peer certificate.'
                        )
                    )
                ),
                'capture_peer_cert_chain' => array(
                    'Type::boolean',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'If set to TRUE a peer_certificate_chain context option will be created containing the certificate chain.'
                        )
                    )
                ),
                'SNI_enabled' => array(
                    'Type::boolean',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'If set to TRUE server name indication will be enabled. Enabling SNI allows multiple certificates on the same IP address.'
                        )
                    )
                ),
                'SNI_server_name' => array(
                    'Type::string',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'If set, then this value will be used as server name for server name indication.'
                        )
                    )
                )
            )
        );
        $curlConfig = array(
            'allowedKeys' => array(
                'method', 'header', 'user_agent', 'content',
                'proxy', 'max_redirects',
                'curl_verify_ssl_host', 'curl_verify_ssl_peer'
            ),
            'keySpec' => array(
                'method' => array(
                    'Type::string',
                    array(
                        'values' => array(
                            'POST', 'GET', 'HEAD', 'PUT',
                            'DELETE', 'OPTIONS', 'TRACE',
                            'CONNECT'
                        ),
                        'description' => tr(
                            __NAMESPACE__,
                            'HTTP method supported by the remote server.'
                        )
                    )
                ),
                'header' => array(
                    'Type::string',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'Additional headers to be sent during request.'
                        )
                    )
                ),
                'user_agent' => array(
                    'Type::string',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'User-agent to send if not specified in header.'
                        )
                    )
                ),
                'content' => array(
                    'Type::string',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'Additional data to be sent after the headers.'
                        )
                    )
                ),
                'proxy' => array(
                    'Type::string',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'URI specifying address of proxy server.'
                        )
                    )
                ),
                'max_redirects' => array(
                    'Type::integer',
                    array(
                        'unsigned' => true,
                        type => 8,
                        'description' => tr(
                            __NAMESPACE__,
                            'The max number of redirects to follow. Value 1 or less means that no redirects are followed.'
                        )
                    )
                ),
                'curl_verify_ssl_host' => array(
                    'Type::boolean',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'Verify the host.'
                        )
                    )
                ),
                'curl_verify_ssl_peer' => array(
                    'Type::boolean',
                    array(
                        'description' => tr(
                            __NAMESPACE__,
                            'Require verification of SSL certificate used.'
                        )
                    )
                )
            )
        );
        $pharConfig = array(
            'allowedKeys' => array(
                'compress', 'metadata'
            ),
            'keySpec' => array(
                'compress' => array(
                    'Type::integer',
                    array(
                        'unsigned' => true,
                        'description' => tr(
                            __NAMESPACE__,
                            'One of Phar compression constants.'
                        )
                    )
                )
            )
        );
        // put everythinbg together
        self::$_streamconfig = array(
            'notEmpty' => true,
            'mandatoryKeys' => array('uri'),
            'allowedKeys' => array(
                'http', 'https', 'ftp', 'ftps', 'ssl', 'tls', 'curl', 'phar'),
            'keySpec' => array(
                'uri' => array('type::string'),
                'http' => array('Type::arrayList', $httpConfig),
                'https' => array('Type::arrayList', $httpConfig),
                'ftp' => array('Type::arrayList', $ftpConfig),
                'ftps' => array('Type::arrayList', $ftpConfig),
                'ssl' => array('Type::arrayList', $sslConfig),
                'tls' => array('Type::arrayList', $sslConfig),
                'curl' => array('Type::arrayList', $curlConfig),
                'phar' => array('Type::arrayList', $pharConfig),
            )
        );
        return $options;
    }
    /**
     * Get a human readable description for validity.
     *
     * @param array   $options  options from check
     * @return string explaining message
     */
    static function streamDescription(array $options = null)
    {
        $options = self::streamOptions($options);
        $desc = tr(
            __NAMESPACE__,
            'The value has to be a stream configuration structure valid for php to create a stream.'
        );
        $desc .= ' '.Type::arraylistDescription(self::$_streamconfig);
        return $desc;
    }
}
