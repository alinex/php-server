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
                    "Relative path {value} is not allowed",
                    array('value' => String::dump($value))
                ), $value, $name, __METHOD__, $options
            );
        // check for absolute paths
        if ($value[0] == '/'
            && isset($options['disallowAbsolute'])
            && $options['disallowAbsolute'] === true)
            throw new Exception(
                tr(
                    "Absolute path {value} is not allowed",
                    array('value' => String::dump($value))
                ), $value, $name, __METHOD__, $options
            );
        // check for backreferences
        if ((preg_match('#^\.\./#', $value) || preg_match('#/\.\./#', $value))
            && (!isset($options['allowBackreferences'])
            || $options['allowBackreferences'] === false))
            throw new Exception(
                tr(
                    "Backreferences in path {value} are not allowed",
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
                    "The path {value} didn't exist",
                    array('value' => String::dump($value))
                ), $value, $name, __METHOD__, $options
            );
        if (isset($options['writable']) && $options['writable'] === true
            && !is_writable(dirname($realpath)) && !is_writable($realpath))
            throw new Exception(
                tr(
                    "The path {value} should be writable",
                    array('value' => String::dump($value))
                ), $value, $name, __METHOD__, $options
            );
        if (isset($options['readable']) && $options['readable'] === true
            && !is_readable($realpath))
            throw new Exception(
                tr(
                    "The path {value} should be readable",
                    array('value' => String::dump($value))
                ), $value, $name, __METHOD__, $options
            );
        if (isset($options['parentExists']) && $options['parentExists'] === true
            && !file_exists(dirname($realpath)))
            throw new Exception(
                tr(
                    "The parent directory for {value} didn't exist",
                    array('value' => String::dump($value))
                ), $value, $name, __METHOD__, $options
            );
        // check filetype
        if (isset($options['filetype'])) {
            if ($options['filetype'] == 'file'
                && !is_file($realpath))
                throw new Exception(
                    tr(
                        "The path {value} should be a file",
                        array('value' => String::dump($value))
                    ), $value, $name, __METHOD__, $options
                );
            else if ($options['filetype'] == 'dir'
                && !is_dir($realpath))
                throw new Exception(
                    tr(
                        "The path {value} should be a directory",
                        array('value' => String::dump($value))
                    ), $value, $name, __METHOD__, $options
                );
            else if ($options['filetype'] == 'link'
                && !is_link($realpath))
                throw new Exception(
                    tr(
                        "The path {value} should be a softlink",
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
                    "The file's mimetype {type} is not allowed",
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
                $desc = tr("The value has to be a file reference.");
            else if ($options['filetype'] == 'file')
                $desc = tr("The value has to be a directory reference.");
            else
                $desc = tr("The value has to be a reference to a softlink.");
        } else {
            $desc = tr("The value has to be a filesystem path.");
        }
        if (isset($options['mimetype']))
            $desc .= ' '.tr(
                "The file have to be one of the following mimetypes: {list}",
                array('list' => String::dump($options['mimetype']))
            );
        if (!isset($options['disallowAbsolute'])
            || $options['disallowAbsolute'] === true)
            $desc .= ' '.tr(
                "An absolute path starting with '/' is bot allowed."
            );

        if (!isset($options['disallowAbsolute'])
            || $options['disallowAbsolute'] === true)
            $desc .= ' '.tr(
                "An absolute path starting with '/' is bot allowed."
            );
        if (!isset($options['disallowRelative'])
            || $options['disallowRelative'] === true)
            $desc .= ' '.tr(
                "Only an absolute path starting with '/' is allowed."
            );
        if (!isset($options['allowBackreferences'])
            || $options['allowBackreferences'] === false)
            $desc .= ' '.tr("Backreferences in the path are not allowed.");
        if (isset($options['base']) && $options['base'] !== false)
            $desc .= ' '.tr(
                "Relative paths will be based at {base}.",
                array('base' => $options['base'])
            );
        if (isset($options['makeAbsolute'])
            && $options['makeAbsolute'] === true)
            $desc .= ' '.tr(
                "Paths will be rewritten as absolute path."
            );
        if (isset($options['resolve']) && $options['resolve'] === true)
            $desc .= ' '.tr(
                "Backreferences in the path will be resolved."
            );
        if (isset($options['exists']) && $options['exists'] === true)
            $desc .= ' '.tr("The path should point to an existing path entry.");
        if (isset($options['readable']) && $options['readable'] === true)
            $desc .= ' '.tr("The path should be readable.");
        if (isset($options['writable']) && $options['writable'] === true)
            $desc .= ' '.tr(
                "The path or the parent directory should be writable."
            );
        if (isset($options['parentExists'])
            && $options['parentExists'] === true)
            $desc .= ' '.tr(
                "The parent directory in the path have to be existing."
            );
        // return description
        return $desc;
    }
}
