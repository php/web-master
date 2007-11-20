<?php

require_once dirname(__FILE__) . '/../Formatter.php';

/**
 * The Text_reST_Formatter_html:: class is the HTML formatter.
 *
 * $Horde: framework/Text_reST/reST/Formatter/html.php,v 1.13 2006/12/30 20:24:32 jan Exp $
 *
 * Copyright 2003-2007 Jason M. Felice <jfelice@cronosys.com>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jason M. Felice <jfelice@cronosys.com>
 * @package Text_reST
 */
class Text_reST_Formatter_html extends Text_reST_Formatter {

    /**
     * The current output charset.
     *
     * @var string
     */
    var $_charset = null;

    function format(&$node, $charset = null)
    {
        if (!isset($charset)) {
            $charset = $this->_charset;
        }

        if (is_string($node)) {
            if (isset($charset)) {
                return @htmlspecialchars($node, ENT_QUOTES, $charset);
            } else {
                return htmlspecialchars($node, ENT_QUOTES);
            }
        }

        switch ($node->_type) {
        case 'Document':
            return $this->_children($node);

        case 'Heading':
            $level = $node->getProperty('level');
            return '<h' . $level . '>' . $this->_children($node) . '</h' .
                $level . '>';

        case 'Link':
            return '<a href="' .
                (isset($charset) ?
                 @htmlspecialchars($node->getProperty('href'), ENT_QUOTES, $charset) :
                 htmlspecialchars($node->getProperty('href'), ENT_QUOTES)) .
                '">' .
                $this->_children($node) . '</a>';

        case 'Literal-Block':
            list($text) = $node->_children;
            return '<pre>' . (isset($charset) ?
                              @htmlspecialchars($text, ENT_QUOTES, $charset) :
                              htmlspecialchars($text, ENT_QUOTES)) . '</pre>';

        case 'Paragraph':
            return "<p>" . $this->_children($node) . "</p>";

        case 'Interpreted-Text':
            switch ($node->getProperty('role')) {
            case 'emphasis':
                return '<em>' . $this->_children($node) . '</em>';

            case 'literal':
                return '<tt>' . $this->_children($node) . '</tt>';

            case 'strong':
                return '<strong>' . $this->_children($node) . '</strong>';

            case 'superscript':
                return '<sup>' . $this->_children($node) . '</sup>';

            case 'subscript':
                return '<sub>' . $this->_children($node) . '</sub>';

            case 'title-reference':
                return '<cite>' . $this->_children($node) . '</cite>';

            default:
                // XXX: Issue a warning.
                return $this->_children($node);
            }

        case 'Section':
            return $this->_children($node);
        }
    }

    function _children(&$node)
    {
        $result = '';
        foreach ($node->_children as $child) {
            $result .= $this->format($child);
        }
        return $result;
    }

}
