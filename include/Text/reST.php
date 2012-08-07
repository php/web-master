<?php
/**
 * The Text_reST:: class represents a parse node of a reStructuredText
 * document and provides an API for parsing reStructuredText documents.
 *
 * $Horde: framework/Text_reST/reST.php,v 1.12 2006/12/30 20:24:32 jan Exp $
 *
 * Copyright 2003-2007 Jason M. Felice <jfelice@cronosys.com>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jason M. Felice <jfelice@cronosys.com>
 * @package Text_reST
 */
class Text_reST {

    /**
     * The parse node type.
     *
     * @access private
     * @var string
     */
    var $_type;

    /**
     * An array of the parse node's children.
     *
     * @access private
     * @var string
     */
    var $_children = array();

    /**
     * A hash of parse node properties.
     *
     * @access private
     * @var array
     */
    var $_properties = array();

    /**
     * Constructor.
     *
     * @param string $type  This is the node type.  The default is 'Document'.
     */
    function Text_reST($type = 'Document')
    {
        $this->_type = $type;
    }

    /**
     * Appends a child parse node to this parse node.
     *
     * @param string|Text_reST &$child  This is the string or object child
     *                                  to append to this parse node.
     */
    function appendChild(&$child)
    {
        $n = count($this->_children);
        if (is_string($child) && $n > 0 && is_string($this->_children[$n - 1])) {
            $this->_children[$n - 1] .= $child;
        } elseif (is_string($child)) {
            $this->_children[] = $child;
        } else {
            $this->_children[] = &$child;
        }
    }

    /**
     * Sets the value of a parse node property.
     *
     * @param string $name   The property's name.
     * @param string $value  The property's value.
     */
    function setProperty($name, $value)
    {
        $this->_properties[$name] = $value;
    }

    /**
     * Retrieves the value of a parse node property.
     *
     * @param string $name  The property's name.
     *
     * @return string  The property's value.
     */
    function getProperty($name)
    {
        if (!array_key_exists($name, $this->_properties)) {
            return null;
        }
        return $this->_properties[$name];
    }

    /**
     * Dumps this parse node and its children.
     * This method is for debugging purposes.
     *
     * @param integer $level  This is the indent level of this parse node.
     */
    function dump($level = 0)
    {
        for ($i = 0; $i < $level; $i++) {
            echo '  ';
        }
        echo $this->_type, '::';
        ksort($this->_properties);
        foreach ($this->_properties as $name => $value) {
            echo ' ', $name, '="', preg_replace('/["\\\\]/', '\\$1', $value),
                '"';
        }
        echo "\n";
        foreach ($this->_children as $child) {
            if (is_string($child)) {
                for ($i = 0; $i < ($level + 1); $i++) {
                    echo '  ';
                }
                echo '"', preg_replace('/["\\\\]/', '\\$1', $child), "\"\n";
            } else {
                $child->dump($level + 1);
            }
        }
    }

    /**
     * Parses a reStructuredText document.
     *
     * @static
     *
     * @param string $text  This is the text of the document we want to parse.
     *
     * @return Text_reST  The parsed document or PEAR_Error on failure.
     */
    static function &parse($text)
    {
        require_once dirname(__FILE__) . '/reST/Parser.php';
        $parser = new Text_reST_Parser();
        return $parser->parse($text);
    }

}
