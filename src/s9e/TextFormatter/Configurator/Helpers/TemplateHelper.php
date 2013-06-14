<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use DOMAttr;
use DOMCharacterData;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMProcessingInstruction;
use DOMXPath;
use RuntimeException;
use s9e\TextFormatter\Configurator\Exceptions\InvalidTemplateException;
use s9e\TextFormatter\Configurator\Exceptions\InvalidXslException;
use s9e\TextFormatter\Configurator\Items\Tag;

abstract class TemplateHelper
{
	/**
	* Normalize a template to a chunk of optimized XSL
	*
	* @param  string $template Original template
	* @return string           Normalized template
	*/
	public static function normalize($template)
	{
		// Load and normalize the template
		$dom = self::loadTemplate($template);
		self::inlineCDATA($dom);
		self::preserveSingleSpaces($dom);
		$template = self::saveTemplate($dom);

		// Optimize the template
		$template = TemplateOptimizer::optimize($template);

		return $template;
	}

	/**
	* Attempt to load a template with DOM, first as XML then as HTML as a fallback
	*
	* NOTE: in order to accomodate templates that don't have one single root node, the DOMDocument
	*       returned by this method has its own root node (with a random name) that acts as a parent
	*       to this template's content
	*
	* @param  string      $template
	* @return DOMDocument
	*/
	public static function loadTemplate($template)
	{
		$dom = new DOMDocument;

		// Generate a random tag name so that the user cannot inject stuff outside of that template.
		// For instance, if the tag was <t>, one could input </t><xsl:evil-stuff/><t>
		$t = 't' . sha1(uniqid(mt_rand(), true));

		// First try as XML
		$xml = '<?xml version="1.0" encoding="utf-8" ?><' . $t . ' xmlns:xsl="http://www.w3.org/1999/XSL/Transform">' . $template . '</' . $t . '>';

		$useErrors = libxml_use_internal_errors(true);
		$success   = $dom->loadXML($xml);
		libxml_use_internal_errors($useErrors);

		if ($success)
		{
			// Success!
			return $dom;
		}

		// Couldn't load it as XML... if the template contains an XSL element, abort now, otherwise
		// we'll reparse it as HTML
		if (strpos($template, '<xsl:') !== false)
		{
			$error = libxml_get_last_error();
			throw new InvalidXslException($error->message);
		}

		// Fall back to loading it inside a div, as HTML
		$html = '<html><body><' . $t . '>' . $template . '</' . $t . '></body></html>';

		$useErrors = libxml_use_internal_errors(true);
		$success   = $dom->loadHTML($html);
		libxml_use_internal_errors($useErrors);

		// @codeCoverageIgnoreStart
		if (!$success)
		{
			$error = libxml_get_last_error();
			throw new InvalidTemplateException('Invalid HTML template - error was: ' . $error->message);
		}
		// @codeCoverageIgnoreEnd

		// Now dump the thing as XML and reload it with the proper namespace declaration
		$xml = self::innerXML($dom->getElementsByTagName($t)->item(0));

		return self::loadTemplate($xml);
	}

	/**
	* Serialize a loaded template back into a string
	*
	* NOTE: removes the root node created by loadTemplate()
	*
	* @param  DOMDocument $dom
	* @return string
	*/
	public static function saveTemplate(DOMDocument $dom)
	{
		return self::innerXML($dom->documentElement);
	}

	/**
	* Get the XML content of a node
	*
	* @param  DOMNode $node
	* @return string
	*/
	protected static function innerXML(DOMNode $node)
	{
		// Serialize the XML then remove the outer node
		$xml = $node->ownerDocument->saveXML($node);

		$pos = 1 + strpos($xml, '>');
		$len = strrpos($xml, '<') - $pos;

		// If the template is empty, return an empty string
		if ($len < 1)
		{
			return '';
		}

		$xml = substr($xml, $pos, $len);

		return $xml;
	}

	/**
	* Replace CDATA sections with text literals
	*
	* @param DOMDocument $dom xsl:template node
	*/
	protected static function inlineCDATA(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);

		foreach ($xpath->query('//text()') as $textNode)
		{
			if ($textNode->nodeType === XML_CDATA_SECTION_NODE)
			{
				$textNode->parentNode->replaceChild(
					$dom->createTextNode($textNode->textContent),
					$textNode
				);
			}
		}
	}

	/**
	* Preserve single space characters by replacing them with a <xsl:text/> node
	*
	* @param DOMDocument $dom xsl:template node
	*/
	protected static function preserveSingleSpaces(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);

		foreach ($xpath->query('//text()[. = " "][not(parent::xsl:text)]') as $textNode)
		{
			$newNode = $dom->createElementNS('http://www.w3.org/1999/XSL/Transform', 'xsl:text');
			$newNode->nodeValue = ' ';

			$textNode->parentNode->replaceChild($newNode, $textNode);
		}
	}

	/**
	* Parse an attribute value template
	*
	* @link http://www.w3.org/TR/xslt#dt-attribute-value-template
	*
	* @param  string $attrValue Attribute value
	* @return array             Array of tokens
	*/
	public static function parseAttributeValueTemplate($attrValue)
	{
		$tokens  = [];
		$attrLen = strlen($attrValue);

		$pos = 0;
		while ($pos < $attrLen)
		{
			// Look for opening brackets
			if ($attrValue[$pos] === '{')
			{
				// Two brackets = one literal bracket
				if (substr($attrValue, $pos, 2) === '{{')
				{
					$tokens[] = ['literal', '{'];
					$pos += 2;

					continue;
				}

				// Move the cursor past the left bracket
				++$pos;

				// We're inside an inline XPath expression. We need to parse it in order to find
				// where it ends
				$expr = '';
				while ($pos < $attrLen)
				{
					// Capture everything up to the next "interesting" char: ', " or }
					$spn = strcspn($attrValue, '\'"}', $pos);
					if ($spn)
					{
						$expr .= substr($attrValue, $pos, $spn);
						$pos += $spn;
					}

					if ($pos >= $attrLen)
					{
						throw new RuntimeException('Unterminated XPath expression');
					}

					// Capture the character then move the cursor
					$c = $attrValue[$pos];
					++$pos;

					if ($c === '}')
					{
						// Done with this expression
						break;
					}

					// Look for the matching quote
					$quotePos = strpos($attrValue, $c, $pos);
					if ($quotePos === false)
					{
						throw new RuntimeException('Unterminated XPath expression');
					}

					// Capture the content of that string then move the cursor past it
					$expr .= $c . substr($attrValue, $pos, $quotePos + 1 - $pos);
					$pos = 1 + $quotePos;
				}

				$tokens[] = ['expression', $expr];
			}

			$spn = strcspn($attrValue, '{', $pos);
			if ($spn)
			{
				// Capture this chunk of attribute value
				$str = substr($attrValue, $pos, $spn);

				// Unescape right brackets
				$str = str_replace('}}', '}', $str);

				// Add the value and move the cursor
				$tokens[] = ['literal', $str];
				$pos += $spn;
			}
		}

		return $tokens;
	}

	/**
	* Return the list of variables used in a given XPath expression
	*
	* @param  string $expr XPath expression
	* @return array        Alphabetically sorted list of unique variable names
	*/
	public static function getVariablesFromXPath($expr)
	{
		// First, remove strings' contents to prevent false-positives
		$expr = preg_replace('/(["\']).*?\\1/s', '$1$1', $expr);

		// Capture all the variable names
		preg_match_all('/\\$(\\w+)/', $expr, $matches);

		// Dedupe and sort names
		$varNames = array_unique($matches[1]);
		sort($varNames);

		return $varNames;
	}

	/**
	* Return a list of parameters in use in given XSL
	*
	* @param  string $xsl XSL source
	* @return array       Alphabetically sorted list of unique parameter names
	*/
	public static function getParametersFromXSL($xsl)
	{
		$paramNames = [];

		// Wrap the XSL in boilerplate code because it might not have a root element
		$xsl = '<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . '<xsl:template>'
		     . $xsl
		     . '</xsl:template>'
		     . '</xsl:stylesheet>';

		$dom = new DOMDocument;
		$dom->loadXML($xsl);

		$xpath = new DOMXPath($dom);

		// Start by collecting XPath expressions in XSL elements
		$query = '//xsl:*/@match | //xsl:*/@select | //xsl:*/@test';
		foreach ($xpath->query($query) as $attribute)
		{
			foreach (self::getVariablesFromXPath($attribute->value) as $varName)
			{
				// Test whether this is the name of a local variable
				$varQuery = 'ancestor-or-self::*/'
				          . 'preceding-sibling::xsl:variable[@name="' . $varName . '"]';

				if (!$xpath->query($varQuery, $attribute)->length)
				{
					$paramNames[] = $varName;
				}
			}
		}

		// Collecting XPath expressions in attribute value templates
		$query = '//*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]'
		       . '/@*[contains(., "{")]';
		foreach ($xpath->query($query) as $attribute)
		{
			$tokens = self::parseAttributeValueTemplate($attribute->value);

			foreach ($tokens as $token)
			{
				if ($token[0] !== 'expression')
				{
					continue;
				}

				foreach (self::getVariablesFromXPath($token[1]) as $varName)
				{
					// Test whether this is the name of a local variable
					$varQuery = 'ancestor-or-self::*/'
					          . 'preceding-sibling::xsl:variable[@name="' . $varName . '"]';

					if (!$xpath->query($varQuery, $attribute)->length)
					{
						$paramNames[] = $varName;
					}
				}
			}
		}

		// Dedupe and sort names
		$paramNames = array_unique($paramNames);
		sort($paramNames);

		return $paramNames;
	}

	/**
	* Return all attributes (literal or generated) that match given regexp
	*
	* @param  DOMDocument $dom Document
	* @return array            Array of DOMNode instances
	*/
	public static function getAttributesByRegexp(DOMDocument $dom, $regexp)
	{
		$xpath = new DOMXPath($dom);
		$nodes = [];

		// Get literal attributes
		foreach ($xpath->query('//@*') as $attribute)
		{
			if (preg_match($regexp, $attribute->name))
			{
				$nodes[] = $attribute;
			}
		}

		// Get generated attributes
		foreach ($xpath->query('//xsl:attribute') as $attribute)
		{
			if (preg_match($regexp, $attribute->getAttribute('name')))
			{
				$nodes[] = $attribute;
			}
		}

		// Get attributes created with <xsl:copy-of/>
		foreach ($xpath->query('//xsl:copy-of') as $node)
		{
			$expr = $node->getAttribute('select');

			if (preg_match('/^@(\\w+)$/', $expr, $m)
			 && preg_match($regexp, $m[1]))
			{
				$nodes[] = $node;
			}
		}

		return $nodes;
	}

	/**
	* Return all elements (literal or generated) that match given regexp
	*
	* @param  DOMDocument $dom Document
	* @return array            Array of DOMNode instances
	*/
	public static function getElementsByRegexp(DOMDocument $dom, $regexp)
	{
		$xpath = new DOMXPath($dom);
		$nodes = [];

		// Get literal attributes
		foreach ($xpath->query('//*') as $element)
		{
			if (preg_match($regexp, $element->localName))
			{
				$nodes[] = $element;
			}
		}

		// Get generated elements
		foreach ($xpath->query('//xsl:element') as $element)
		{
			if (preg_match($regexp, $element->getAttribute('name')))
			{
				$nodes[] = $element;
			}
		}

		// Get elements created with <xsl:copy-of/>
		// NOTE: this method of creating elements is disallowed by default
		foreach ($xpath->query('//xsl:copy-of') as $node)
		{
			$expr = $node->getAttribute('select');

			if (preg_match('/^\\w+$/', $expr)
			 && preg_match($regexp, $expr))
			{
				$nodes[] = $node;
			}
		}

		return $nodes;
	}

	/**
	* Return all elements (literal or generated) that match given regexp
	*
	* Will return all <param/> descendants of <object/> and all attributes of <embed/> whose name
	* matches given regexp. This method will NOT catch <param/> elements whose 'name' attribute is
	* set via an <xsl:attribute/>
	*
	* @param  DOMDocument $dom Document
	* @return array            Array of DOMNode instances
	*/
	public static function getObjectParamsByRegexp(DOMDocument $dom, $regexp)
	{
		$xpath = new DOMXPath($dom);
		$nodes = [];

		// Collect attributes from <embed/> elements
		foreach (self::getAttributesByRegexp($dom, $regexp) as $attribute)
		{
			if ($attribute->nodeType === XML_ATTRIBUTE_NODE)
			{
				if (strtolower($attribute->parentNode->localName) === 'embed')
				{
					$nodes[] = $attribute;
				}
			}
			elseif ($xpath->evaluate('ancestor::embed', $attribute))
			{
				// Assuming <xsl:attribute/> or <xsl:copy-of/>
				$nodes[] = $attribute;
			}
		}

		// Collect <param/> descendants of <object/> elements
		foreach ($dom->getElementsByTagName('object') as $object)
		{
			foreach ($object->getElementsByTagName('param') as $param)
			{
				if (preg_match($regexp, $param->getAttribute('name')))
				{
					$nodes[] = $param;
				}
			}
		}

		return $nodes;
	}

	/**
	* Return all DOMNodes whose content is CSS
	*
	* @param  DOMDocument $dom Document
	* @return array            Array of DOMNode instances
	*/
	public static function getCSSNodes(DOMDocument $dom)
	{
		$regexp = '/^style$/i';
		$nodes  = array_merge(
			self::getAttributesByRegexp($dom, $regexp),
			self::getElementsByRegexp($dom, '/^style$/i')
		);

		return $nodes;
	}

	/**
	* Return all DOMNodes whose content is Javascript
	*
	* @param  DOMDocument $dom Document
	* @return array            Array of DOMNode instances
	*/
	public static function getJSNodes(DOMDocument $dom)
	{
		$regexp = '/^on/i';
		$nodes  = array_merge(
			self::getAttributesByRegexp($dom, $regexp),
			self::getElementsByRegexp($dom, '/^script$/i')
		);

		return $nodes;
	}

	/**
	* Return all DOMNodes whose content is an URL
	*
	* NOTE: it will also return HTML4 nodes whose content is an URI
	*
	* @param  DOMDocument $dom Document
	* @return array            Array of DOMNode instances
	*/
	public static function getURLNodes(DOMDocument $dom)
	{
		$regexp = '/(?:^(?:action|background|c(?:ite|lassid|odebase)|data|formaction|href|icon|longdesc|manifest|p(?:luginspage|oster|rofile)|usemap)|src)$/i';
		$nodes  = self::getAttributesByRegexp($dom, $regexp);
		$xpath  = new DOMXPath($dom);

		/**
		* @link http://helpx.adobe.com/flash/kb/object-tag-syntax-flash-professional.html
		* @link http://www.sitepoint.com/control-internet-explorer/
		*/
		foreach (self::getObjectParamsByRegexp($dom, '/^(?:dataurl|movie)$/i') as $param)
		{
			$node = $param->getAttributeNode('value');
			if ($node)
			{
				$nodes[] = $node;
			}
		}

		return $nodes;
	}

	/**
	* Replace parts of a template that match given regexp
	*
	* Treats attribute values as plain text. Replacements within XPath expression is unsupported.
	* The callback must return an array with two elements. The first must be either of 'expression',
	* 'literal' or 'passthrough', and the second element depends on the first.
	*
	*  - 'expression' indicates that the replacement must be treated as an XPath expression such as
	*    '@foo', which must be passed as the second element.
	*  - 'literal' indicates a literal (plain text) replacement, passed as its second element.
	*  - 'passthrough' indicates that the replacement should the tag's content. It works differently
	*    whether it is inside an attribute's value or a text node. Within an attribute's value, the
	*    replacement will be the text content of the tag and the second element must be a boolean
	*    that indicates whether it should include the start and end tags. Within a text node, the
	*    replacement becomes an <xsl:apply-templates/> node and the second element is ignored.
	*
	* @param  string   $template Original template
	* @param  array    $regexp   Regexp for matching parts that need replacement
	* @param  callback $fn       Callback used to get the replacement
	* @return string             Processed template
	*/
	public static function replaceTokens($template, $regexp, $fn)
	{
		if ($template === '')
		{
			return $template;
		}

		$dom   = self::loadTemplate($template);
		$xpath = new DOMXPath($dom);

		// Replace tokens in attributes
		foreach ($xpath->query('//@*') as $attribute)
		{
			// Generate the new value
			$attrValue = preg_replace_callback(
				$regexp,
				function ($m) use ($fn)
				{
					$replacement = $fn($m);

					if ($replacement[0] === 'expression')
					{
						return '{' . $replacement[1] . '}';
					}
					elseif ($replacement[0] === 'passthrough')
					{
						return ($replacement[1]) ? '{.}' : '{substring(.,1+string-length(st),string-length()-(string-length(st)+string-length(et)))}';
					}
					else
					{
						// Literal replacement
						return $replacement[1];
					}
				},
				$attribute->value
			);

			// Replace the attribute value
			$attribute->value = htmlspecialchars($attrValue, ENT_COMPAT, 'UTF-8');
		}

		// Replace tokens in text nodes
		foreach ($xpath->query('//text()') as $node)
		{
			preg_match_all(
				$regexp,
				$node->textContent,
				$matches,
				PREG_SET_ORDER | PREG_OFFSET_CAPTURE
			);

			if (empty($matches))
			{
				continue;
			}

			// Grab the node's parent so that we can rebuild the text with added variables right
			// before the node, using DOM's insertBefore(). Technically, it would make more sense
			// to create a document fragment, append nodes then replace the node with the fragment
			// but it leads to namespace redeclarations, which looks ugly
			$parentNode = $node->parentNode;

			$lastPos = 0;
			foreach ($matches as $m)
			{
				$pos = $m[0][1];

				// Catch-up to current position
				if ($pos > $lastPos)
				{
					$parentNode->insertBefore(
						$dom->createTextNode(
							substr($node->textContent, $lastPos, $pos - $lastPos)
						),
						$node
					);
				}
				$lastPos = $pos + strlen($m[0][0]);

				// Remove the offset data from the array, keep only the content of captures so that
				// $_m contains the same data that preg_match() or preg_replace() would return
				$_m = [];
				foreach ($m as $capture)
				{
					$_m[] = $capture[0];
				}

				// Get the replacement for this token
				$replacement = $fn($_m);

				if ($replacement[0] === 'expression')
				{
					// Expressions are evaluated in a <xsl:value-of/> node
					$parentNode
						->insertBefore(
							$dom->createElementNS(
								'http://www.w3.org/1999/XSL/Transform',
								'xsl:value-of'
							),
							$node
						)
						->setAttribute('select', $replacement[1]);
				}
				elseif ($replacement[0] === 'passthrough')
				{
					// Passthrough token, replace with <xsl:apply-templates/>
					$parentNode->insertBefore(
						$dom->createElementNS(
							'http://www.w3.org/1999/XSL/Transform',
							'xsl:apply-templates'
						),
						$node
					);
				}
				else
				{
					// Literal replacement
					$parentNode->insertBefore($dom->createTextNode($replacement[1]), $node);
				}
			}

			// Append the rest of the text
			$text = substr($node->textContent, $lastPos);
			if ($text > '')
			{
				$parentNode->insertBefore($dom->createTextNode($text), $node);
			}

			// Now remove the old text node
			$parentNode->removeChild($node);
		}

		return self::saveTemplate($dom);
	}

	/**
	* Highlight the source of a node inside of a template
	*
	* NOTE: this method expects the template to be wrapped in a <t.../> node, which will be omitted
	*
	* @param  DOMNode $node    Node to highlight
	* @param  string  $prepend HTML to prepend
	* @param  string  $append  HTML to append
	* @return string           Template's source, as HTML
	*/
	static public function highlightNode(DOMNode $node, $prepend, $append)
	{
		// Add a unique token to the node
		$uniqid = uniqid('_');
		if ($node instanceof DOMAttr)
		{
			$node->value .= $uniqid;
		}
		elseif ($node instanceof DOMElement)
		{
			$node->setAttribute($uniqid, '');
		}
		elseif ($node instanceof DOMCharacterData
		     || $node instanceof DOMProcessingInstruction)
		{
			$node->data .= $uniqid;
		}

		$dom = $node->ownerDocument;
		$dom->formatOutput = true;

		$docXml = $dom->saveXML($dom->documentElement);
		$docXml = preg_replace('#^<(t\\w*)[^>]*>(.*)</\\1>$#s', '$2', $docXml);
		$docXml = trim(str_replace("\n  ", "\n", $docXml));

		$nodeHtml = htmlspecialchars(trim($dom->saveXML($node)));
		$docHtml  = htmlspecialchars($docXml);

		// Enclose the node's representation in our hilighting HTML
		$html = str_replace($nodeHtml, $prepend . $nodeHtml . $append, $docHtml);

		// Remove the unique token from HTML and from the node
		if ($node instanceof DOMAttr)
		{
			$node->value = substr($node->value, 0, -strlen($uniqid));
			$html = str_replace($uniqid, '', $html);
		}
		elseif ($node instanceof DOMElement)
		{
			$node->removeAttribute($uniqid);
			$html = str_replace(' ' . $uniqid . '=&quot;&quot;', '', $html);
		}
		elseif ($node instanceof DOMCharacterData
		     || $node instanceof DOMProcessingInstruction)
		{
			$node->data .= $uniqid;
			$html = str_replace($uniqid, '', $html);
		}

		return $html;
	}
}