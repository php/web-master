<?php
/**
 * The Text_reST_Formatter:: class is the framework for rendering
 * reStructuredText documents to different media (e.g. HTML).
 *
 * $Horde: framework/Text_reST/reST/Formatter.php,v 1.14 2006/12/30 20:24:32 jan Exp $
 *
 * Copyright 2003-2007 Jason M. Felice <jfelice@cronosys.com>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jason M. Felice <jfelice@cronosys.com>
 * @package Text_reST
 */
class Text_reST_Formatter {

    /**
     * Array of driver-specific parameters for formatting.
     *
     * @var array
     */
    var $_args;

    /**
     * Construct a new formatter.
     *
     * @access protected
     *
     * @param array $args  Arguments specific to this formatter.
     */
    function Text_reST_Formatter($args = array())
    {
        $this->_args = $args;
    }

    /**
     * Construct a new formatter.
     *
     * @param string $driver  Name of the formatting driver to construct.
     * @param array $args     An array of driver-specific parameters.
     *
     * @return Text_reST_Formatter  The formatter
     */
    static function &factory($driver, $args = array())
    {
        if (is_array($driver)) {
            list($path, $driver) = $driver;
        } else {
            $path = dirname(__FILE__) . '/Formatter/';
        }
        $class = 'Text_reST_Formatter_' . $driver;
        require_once $path . $driver . '.php';
        $formatter = new $class($args);
        return $formatter;
    }

    /**
     * Render the document.
     *
     * @abstract
     *
     * @param Text_reST $document  The document we will render.
     * @param string $charset      The output charset.
     */
    function format(&$document, $charset = null)
    {
    }

}
