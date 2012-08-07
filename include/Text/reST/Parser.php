<?php
/**
 * The Text_reST_Parser:: class implements a parser for reStructuredText
 * documents.
 *
 * $Horde: framework/Text_reST/reST/Parser.php,v 1.17 2006/12/30 20:24:32 jan Exp $
 *
 * Copyright 2003-2007 Jason M. Felice <jfelice@cronosys.com>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jason M. Felice <jfelice@cronosys.com>
 * @package Text_reST
 */
class Text_reST_Parser {

    /**
     * The parse tree.
     *
     * @var Text_reST
     */
    var $_document;

    /**
     * A hash of adornment levels.
     *
     * The keys are one-character or two-character strings.  The one-character
     * strings represent underline adornments of the specified character, and
     * the double-character keys are the underline-and-overline styles.  The
     * values associated with the keys are integers representing the
     * adornment's heading level.
     *
     * @var array
     */
    var $_adornmentLevels = array();

    /**
     * Constructor.
     */
    function Text_reST_Parser()
    {
    }

    /**
     * Returns a parse tree representing a document.
     *
     * @param string $text  This is the text of the document to parse.
     *
     * @return Text_reST  The parse tree.
     */
    function &parse($text)
    {
        $this->_text = $text;

        require_once dirname(__FILE__) . '/../reST.php';

        $this->_document = new Text_reST('Document');
        $this->_pushState($this->_document, 'Section', 0);

        while ($this->_next()) {

            //
            // Parse a `..' directive.  We rewrite an `__' directive to a
            // `.. __: ' directive here.
            //
            if (preg_match('/^(\.\.|__)\s+(.*?)\s*$/',
                           $this->_lineBuffer[0], $m)) {
                if ($m[1] == '__') {
                    $text = '__: ' . $m[2];
                } else {
                    $text = $m[2];
                }
                while ($this->_ensureLines(2) &&
                       preg_match('/^  ([^\s].*?)\s*$/',
                                  $this->_lineBuffer[1], $m)) {
                    $text .= ' '.$m[1];
                    $this->_next();
                }
                $this->_parseDirective($text);
                continue;
            }

            //
            // Look for overline-and-underline headings.
            //
            if ($this->_ensureLines(3) &&
                preg_match('/^(.+?)\s*$/', $this->_lineBuffer[1], $lineMatch) &&
                $this->_checkAdornment(array(0, 2), strlen($lineMatch[1]))) {

                $adornmentType = $this->_lineBuffer[0][0];
                $adornmentType .= $adornmentType;

                $this->_next();
                $this->_next();

                if (isset($this->_adornmentLevels[$adornmentType])) {
                    $newLevel = $this->_adornmentLevels[$adornmentType];
                    $this->_popToLevel('Section', $newLevel - 1);
                } else {
                    $newLevel = $this->_getStateLevel('Section') + 1;
                    $this->_adornmentLevels[$adornmentType] = $newLevel;
                }
                $node = &$this->_makeNode($this->_currentNode, 'Section',
                                          array('level' => $newLevel));
                $this->_pushState($node, 'Section', $newLevel);
                preg_match('/^\s*(.*?)\s*$/', $lineMatch[1], $lineMatch);
                $this->_makeNode($this->_currentNode, 'Heading',
                                 array('level' => $newLevel),
                                 $lineMatch[1]);
                continue;
            }

            //
            // Look for underline headings.
            //
            if ($this->_ensureLines(2) &&
                preg_match('/^([^\s].*?)\s*$/',
                           $this->_lineBuffer[0], $lineMatch) &&
                $this->_checkAdornment(array(1), strlen($lineMatch[1]))) {

                $adornmentType = $this->_lineBuffer[1][0];
                $this->_next();
                if (isset($this->_adornmentLevels[$adornmentType])) {
                    $newLevel = $this->_adornmentLevels[$adornmentType];
                    $this->_popToLevel('Section', $newLevel - 1);
                } else {
                    $newLevel = $this->_getStateLevel('Section') + 1;
                    $this->_adornmentLevels[$adornmentType] = $newLevel;
                }
                $node = &$this->_makeNode($this->_currentNode, 'Section',
                                          array('level' => $newLevel));
                $this->_pushState($node, 'Section', $newLevel);
                $this->_makeNode($this->_currentNode, 'Heading',
                                 array('level' => $newLevel),
                                 $lineMatch[1]);
                continue;
            }

            //
            // Parse a `::' paragraph.
            //
            if (preg_match('/^\s*::\s*$/', $this->_lineBuffer[0])) {
                $this->_next();
                $this->_parseLiteralBlock();
                continue;
            }

            //
            // Parse a paragraph.  We end the paragraph when we return to
            // a lower indentation level or encounter a blank line.
            //
            if (preg_match('/^(\s*)([^\s].*?)\s*$/',
                           $this->_lineBuffer[0], $m)) {
                $text = $m[2];
                $level = strlen($m[1]);
                while ($this->_ensureLines(2) &&
                       preg_match('/^(\s*)([^\s].*?)\s*$/',
                                  $this->_lineBuffer[1], $m)) {
                    if (strlen($m[1]) < $level) {
                        break;
                    }
                    $text .= ' ' . $m[2];
                    $this->_next();
                }

                $trailingLiteral = false;
                if (preg_match('/^(.*[^\s]:):\s*$/', $text, $m)) {
                    $text = $m[1];
                    $trailingLiteral = true;
                } elseif (preg_match('/^(.*?)\s*::\s*$/', $text, $m)) {
                    $text = $m[1];
                    $trailingLiteral = true;
                }

                $this->_makeNode($this->_currentNode, 'Paragraph', array(),
                                 $text);

                if ($trailingLiteral) {
                    $this->_next();
                    $this->_parseLiteralBlock();
                }
                continue;
            }

            // XXX: Handle garbage line.

        };

        return $this->_document;
    }

    function &_makeNode(&$parent, $type, $props = array(), $childText = null)
    {
        $node = new Text_reST($type);
        foreach ($props as $name => $value) {
            $node->setProperty($name, $value);
        }
        if (!is_null($parent)) {
            $parent->appendChild($node);
        }
        if (!is_null($childText)) {
            $this->_parseInline($node, $childText);
        }
        return $node;
    }

    /**
     * Checks multiple adornemnt lines in the line buffer and makes sure they
     * are adornments and that all are identical adornments.
     *
     * @access private
     *
     * @param array $lines        An array of line numbers to check if they are
     *                            adornments.
     * @param integer $minLength  The minimum length for this adornment.  The
     *                            default is 1.
     *
     * @return boolean  Whether this line is an adornment which matches the
     *                  above criteria.
     */
    function _checkAdornment($lines = array(0), $minLength = 1)
    {
        $chr = null;
        foreach ($lines as $i) {
            if (!preg_match('/^([^a-zA-Z0-9\x7f-\xff\s]+)\s*$/',
                            $this->_lineBuffer[$i], $m)) {
                return false;
            }
            if (is_null($chr)) {
                if (strlen($m[1]) < $minLength) {
                    return false;
                }
                $chr = $m[1][0];
            } else {
                if (strlen($m[1]) != $minLength) {
                    return false;
                }
            }
            $minLength = strlen($m[1]);
            for ($j = 0; $j < strlen($m[1]); $j++) {
                if ($m[1][$j] != $chr) {
                    return false;
                }
            }
        }
        return true;
    }

    function &_parseInline(&$node, $text)
    {
        static $aliases = array('sup' => 'superscript',
                                'sub' => 'subscript');
        static $schemas = array('http',
                                'https',
                                'ftp',
                                'irc',
                                'telnet',
                                'news');

        while (strlen($text) > 0) {
            if (preg_match('/^\*\*((?:\\\\.|[^\\\\])*?)\*\*(.*)$/', $text, $m)) {
                $this->_makeNode($node, 'Interpreted-Text',
                                 array('role' => 'strong'),
                                 $m[1]);
                $text = $m[2];
            } elseif (preg_match('/^\*((?:\\\\.|[^\\\\])*?)\*(.*)$/', $text, $m)) {
                $this->_makeNode($node, 'Interpreted-Text',
                                 array('role' => 'emphasis'),
                                 $m[1]);
                $text = $m[2];
            } elseif (preg_match('/^``(.*?)``(.*)$/', $text, $m)) {
                $sub = &$this->_makeNode($node, 'Interpreted-Text',
                                         array('role' => 'literal'));
                $sub->appendChild($m[1]);
                $text = $m[2];
            } elseif (preg_match('/^:([a-z-]+):`((?:\\\\.|[^\\\\])*?)`(.*)$/',
                                 $text, $m)) {
                $role = $m[1];
                if (isset($aliases[$m[1]])) {
                    $role = $aliases[$m[1]];
                }
                $sub = &$this->_makeNode($node, 'Interpreted-Text',
                                         array('role' => $role));
                if ($role == 'literal') {
                    $sub->appendChild($m[2]);
                } else {
                    $this->_parseInline($sub, $m[2]);
                }
                $text = $m[3];
            } elseif (preg_match('/^`((?:\\\\.|[^\\\\])*?)`:([a-z-]+):(.*)$/',
                                 $text, $m)) {
                $role = $m[2];
                if (isset($aliases[$m[2]])) {
                    $role = $aliases[$m[2]];
                }
                $sub = &$this->_makeNode($node, 'Interpreted-Text',
                                         array('role' => $role));
                if ($role == 'literal') {
                    $sub->appendChild($m[1]);
                } else {
                    $this->_parseInline($sub, $m[1]);
                }
                $text = $m[3];
            } elseif (preg_match('/^`((?:\\\\.|[^\\\\])*?)`__(.*)$/',
                                 $text, $m)) {
                $this->_parseLink($node, $m[1], true);
                $text = $m[2];
            } elseif (preg_match('/^`((?:\\\\.|[^\\\\])*?)`_(.*)$/',
                                 $text, $m)) {
                $this->_parseLink($node, $m[1], false);
                $text = $m[2];
            } elseif (preg_match('/^`((?:\\\\.|[^\\\\])*?)`(.*)$/',
                                 $text, $m)) {
                $this->_makeNode($node, 'Interpreted-Text',
                                 array('role' => 'title-reference'),
                                 $m[1]);
                $text = $m[2];
            } elseif (preg_match('/^((?:' . join('|', $schemas) .  '):\/\/[-0-9a-z#%&+.\/:;?_\\~]+[-0-9a-z#%&+\/_\\~])(.*)$/i', $text, $m)) {
                $sub = &$this->_makeNode($node, 'Link', array('href' => $m[1]));
                $sub->appendChild($m[1]);
                $text = $m[2];
            } elseif (preg_match('/^([a-z0-9-]+@[a-z0-9-\.]+\.[a-z0-9-]+)(.*)$/i',
                                 $text, $m)) {
                $sub = &$this->_makeNode($node, 'Link',
                                         array('href' => 'mailto:' . $m[1]));
                $sub->appendChild($m[1]);
                $text = $m[2];
            } elseif (preg_match('/^(\w+)_\b(.*)$/', $text, $m)) {
                $this->_parseLink($node, $m[1], false);
                $text = $m[2];
            } elseif (preg_match('/^\\\\\s(.*)$/', $text, $m)) {
                // Backslash-escaped whitespace characters are removed from
                // the document.
                $text = $m[1];
            } elseif (preg_match('/^\\\\(.)(.*)$/', $text, $m)) {
                $c = $m[1];
                $text = $m[2];
                $node->appendChild($c);
            } else {
                // XXX: We should try to use a regexp to grab as much text as
                // possible, then fall through to the single-character case
                // if we can't get anything.

                $c = substr($text, 0, 1);
                $text = substr($text, 1);
                $node->appendChild($c);
            }
        }

        return $body;
    }

    /**
     * Parses an anonymous or named link.
     *
     * @access private
     *
     * @param Text_reST           The parent node for the link.
     * @param string $text        The text to parse.
     * @param boolean $anonymous  Whether this is an anonymous link.
     *
     * @return Text_reST  The new link node.
     */
    function &_parseLink(&$node, $text, $anonymous = false)
    {
        $link = &$this->_makeNode($node, 'Link');

        if (preg_match('/<(.*)>/', $text, $m)) {
            $link->setProperty('href', $m[1]);
            if (preg_match('/^([^<]+?)\s*</', $text, $m)) {
                $link->appendChild($m[1]);
                if (!$anonymous) {
                    $link->setProperty('name', $this->_normalizeName($m[1]));
                }
            }
        } else {
            if (!$anonymous) {
                $link->setProperty('name', $this->_normalizeName($text));
            }
            $link->appendChild($text);
        }

        if ($anonymous && is_null($link->getProperty('href'))) {
            $this->_queueAnonymousReference($link, 'link');
        } elseif (!$anonymous && !is_null($link->getProperty('name'))) {
            $this->_putNamedReference($link, 'link');
        }

        return $link;
    }

    /**
     * Normalizes an object name.
     * This means that we lowercase it and normalize any whitespace in it.
     *
     * @param string $name  A name to normalize.
     *
     * @return string  The normalized name.
     */
    function _normalizeName($name)
    {
        return preg_replace('/\s+/', ' ', strtolower($name));
    }

    /**
     * Parses and executes a `..' directive.
     *
     * @access private
     *
     * @param string $text  A directive to execute, less the leading `.. '.
     */
    function _parseDirective($text)
    {
        if (preg_match('/^__:\s*(.*?)\s*$/', $text, $m)) {
            //
            // Anonymous link definition
            //
            $defn = new Text_reST('Link');
            if (preg_match('/^[a-z0-9-]+@[a-z0-9-\.]+\.[a-z0-9-]+$/i', $m[1])) {
                $m[1] = 'mailto:' . $m[1];
            }
            $defn->setProperty('href', $m[1]);
            $this->_queueAnonymousDefinition($defn, 'link');
        } elseif (preg_match('/^\s*_(.*?):\s*(.*?)\s*$/', $text, $m)) {
            //
            // Named link definition
            //
            $defn = new Text_reST('Link');
            $defn->setProperty('name', $this->_normalizeName($m[1]));
            if (preg_match('/^[a-z0-9-]+@[a-z0-9-\.]+\.[a-z0-9-]+$/i', $m[2])) {
                $m[2] = 'mailto:' . $m[2];
            }
            $defn->setProperty('href', $m[2]);
            $this->_putNamedDefinition($defn, 'link');
        }
    }

    /**
     * Skips blank lines until we find one we can get the indentation level
     * from, then, gathers lines until we have a different level.
     */
    function _parseLiteralBlock()
    {
        if (!$this->_ensureLines(1)) {
            return false;
        }

        while (preg_match('/^\s*$/', $this->_lineBuffer[0])) {
            if (!$this->_next()) {
                return false;
            }
        }

        if (!preg_match('/^(\s+)(.*?)\s*$/', $this->_lineBuffer[0], $m)) {
            return false;
        }
        $level = strlen($m[1]);
        $text = $m[2];

        if ($this->_next()) {
            $re = '/^(?: {' . $level . '}(.*?)|())\s*$/';
            while (preg_match($re, $this->_lineBuffer[0], $m)) {
                $text .= "\n" . $m[1];
                if (!$this->_next()) {
                    break;
                }
            }
        }

        $l = &$this->_makeNode($this->_currentNode, 'Literal-Block', array());
        $stripped_text = preg_replace('/\s+$/s', '', $text);
        $l->appendChild($stripped_text);

        // XXX: Dirty hack!
        array_unshift($this->_lineBuffer, '');
    }

    //----
    // Line-reading members
    //----

    /**
     * The remainder of the text we are parsing, being modified by _getLine()
     * and _next().
     *
     * @access private
     * @var string
     */
    var $_text;

    /**
     * An array of the lines we have peeked at.
     *
     * The first element is the line we are currently working with and so on.
     *
     * @access private
     * @var array
     */
    var $_lineBuffer = array();

    /**
     * Retrieves the next line from a block of text.
     *
     * We replace tabs with 8 spaces.
     *
     * @access private
     */
    function _getLine()
    {
        if (strlen($this->_text) == 0) {
            return null;
        }
        $i = strpos($this->_text, "\n");
        if ($i !== false) {
            $line = substr($this->_text, 0, $i);
            $this->_text = substr($this->_text, $i + 1);
        } else {
            $line = $this->_text;
            $this->_text = '';
        }
        return preg_replace('/\t/', '        ', $line);
    }

    /**
     * Bumps to the next line in the input.
     *
     * @access private
     */
    function _next()
    {
        // Special case the first time 'round.
        if (count($this->_lineBuffer) == 0) {
            return $this->_ensureLines(1);
        }

        if (!$this->_ensureLines(2)) {
            return false;
        }
        array_shift($this->_lineBuffer);
        return true;
    }

    /**
     * Makes sure there is a certain number of lines at minimum in the line
     * buffer.
     *
     * @access private
     *
     * @param integer $count  This is the number of lines which must be in the
     *                        buffer.
     *
     * @return boolean  Whether or not we succeeded.  We can fail at
     *                  end-of-file.
     */
    function _ensureLines($count = 1)
    {
        while (count($this->_lineBuffer) < $count) {
            $line = $this->_getLine();
            if (is_null($line)) {
                return false;
            }
            $this->_lineBuffer[] = $line;
        }
        return true;
    }

    //----
    // Anonymous references and definitions
    //----

    var $_anonymousReferences = array();
    var $_anonymousDefinitions = array();

    /**
     * Since anonymous references and definitions (e.g. footnotes, links) do
     * not need to be defined "in lockstep" according to the spec, we create
     * the partial parse node in both places and use this nifty system to
     * queue or merge in each place.  Note that the reference is the "master"
     * node.  The definition gets thrown away since it really isn't in the
     * parse tree anyway.
     *
     * @access private
     *
     * @param object &$node  The node to queue or merge to.
     * @param string $type   The type of anonymous object.
     */
    function _queueAnonymousReference(&$node, $type)
    {
        if (!array_key_exists($type, $this->_anonymousDefinitions)) {
            $this->_anonymousDefinitions[$type] = array();
        }
        if (count($this->_anonymousDefinitions[$type]) > 0) {
            $defn = &$this->_anonymousDefinitions[$type][0];
            array_shift($this->_anonymousDefinitions[$type]);
            $this->_mergeNodeProperties($node, $defn);
        } else {
            $this->_anonymousReferences[$type][] = &$node;
        }
    }

    /**
     * Handles an anonymous definition.
     *
     * @access private
     *
     * @param object &$node  The node to queue or merge from.
     * @param string $type   The type of anonymous object.
     */
    function _queueAnonymousDefinition(&$node, $type)
    {
        if (!array_key_exists($type, $this->_anonymousReferences)) {
            $this->_anonymousReferences[$type] = array();
        }
        if (count($this->_anonymousReferences[$type]) > 0) {
            $ref = &$this->_anonymousReferences[$type][0];
            array_shift($this->_anonymousReferences[$type]);
            $this->_mergeNodeProperties($ref, $node);
        } else {
            $this->_anonymousDefinitions[$type][] = &$node;
        }
    }

    /**
     * Merges the properties from each node into the other node.
     *
     * The node type is not changed (for the case where we have a footnote
     * reference and a footnote definition), but both nodes will have all
     * properties.
     *
     * @access private
     *
     * @param object &$node  The reference node.
     * @param object &$defn  The definition node.
     */
    function _mergeNodeProperties(&$node, &$defn)
    {
        // XXX: We should make sure there is no collision.
        foreach ($defn->_properties as $name => $value) {
            $node->setProperty($name, $value);
        }
        foreach ($node->_properties as $name => $value) {
            $defn->setProperty($name, $value);
        }
    }

    //----
    // Named references and definitions
    //----

    var $_namedReferences = array();
    var $_namedDefinitions = array();

    /**
     * Stores a named reference parse node in a hash so we can later merge
     * properties with a definition.  If we already have a definition, do
     * the merge now.
     *
     * @access private
     *
     * @param Text_reST &$node  The parse tree node.
     * @param string $type      The type of named reference.
     *
     * @return boolean  Whether or not we successfully added the reference.
     */
    function _putNamedReference(&$node, $type)
    {
        $name = $node->getProperty('name');
        if (isset($this->_namedReferences[$type][$name])) {
            return false;
        }
        $this->_namedReferences[$type][$name] = &$node;
        if (isset($this->_namedDefinitions[$type][$name])) {
            $defn = &$this->_namedDefinitions[$type][$name];
            $this->_mergeNodeProperties($node, $defn);
        }
        return true;
    }

    /**
     * The inverse of {@link _putNamedReference()}.
     *
     * @access private
     */
    function _putNamedDefinition(&$node, $type)
    {
        $name = $node->getProperty('name');
        if (isset($this->_namedDefinitions[$type][$name])) {
            return false;
        }
        $this->_namedDefinitions[$type][$name] = &$node;
        if (isset($this->_namedReferences[$type][$name])) {
            $ref = &$this->_namedReferences[$type][$name];
            $this->_mergeNodeProperties($ref, $node);
        }
        return true;
    }

    //----
    // State stack management
    //----

    /**
     * The state stack.
     *
     * It is used to keep track of nested body-level elements and how they
     * might end.
     *
     * @var array
     */
    var $_stateStack = array();

    var $_currentNode;

    function _pushState(&$node, $stateType, $level)
    {
        $state = new Text_reST_Parser_state($node, $stateType, $level);
        $this->_stateStack[] = &$state;
        $this->_currentNode = &$node;
    }

    function _getStateLevel($stateType)
    {
        for ($i = count($this->_stateStack) - 1; $i >= 0; $i--) {
            if ($this->_stateStack[$i]->stateType == $stateType) {
                return $this->_stateStack[$i]->level;
            }
        }
        return 0;
    }

    function _popToLevel($stateType, $level)
    {
        while ($this->_getStateLevel($stateType) > $level) {
            $this->_pop();
        }
    }

    function _pop()
    {
        array_pop($this->_stateStack);
        if (count($this->_stateStack)) {
            $state = &$this->_stateStack[count($this->_stateStack) - 1];
            $this->_currentNode = &$state->node;
        }
    }

}

/**
 * This class represents a node on the parser's state stack.
 *
 * @package Text_reST
 */
class Text_reST_Parser_state {

    var $node;
    var $stateType;
    var $level;

    /**
     * Constructor.
     *
     * @param object &$node      This is the parse node associated with this
     *                           state.  Block-level elements parsed in this
     *                           state will be children of this node.
     * @param string $stateType  Currently only 'Section'.
     * @param mixed $level       This is the nesting level of this state type.
     */
    function Text_reST_Parser_state(&$node, $stateType, $level)
    {
        $this->node = &$node;
        $this->stateType = $stateType;
        $this->level = $level;
    }

}
