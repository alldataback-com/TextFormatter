<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use ReflectionClass;
use RuntimeException;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\Regexp as RegexpObject;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;
use s9e\TextFormatter\Configurator\JavaScript\Minifiers\Noop;
use s9e\TextFormatter\Configurator\JavaScript\RegExp;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;
use s9e\TextFormatter\Configurator\RendererGenerators\XSLT;

class JavaScript
{
	protected $callbacks;

	protected $config;

	protected $configurator;

	public $exportMethods = array(
		'disablePlugin',
		'disableTag',
		'enablePlugin',
		'enableTag',
		'getLogger',
		'parse',
		'preview',
		'setNestingLimit',
		'setParameter',
		'setTagLimit'
	);

	protected $hints;

	protected $minifier;

	protected $xsl;

	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
	}

	public function getMinifier()
	{
		if (!isset($this->minifier))
			$this->minifier = new Noop;

		return $this->minifier;
	}

	public function getParser(array $config = \null)
	{
		$this->config = (isset($config)) ? $config : $this->configurator->asConfig();
		ConfigHelper::filterVariants($this->config, 'JS');

		$src = $this->getSource();

		$this->injectConfig($src);

		if (!empty($this->exportMethods))
		{
			$methods = array();
			foreach ($this->exportMethods as $method)
				$methods[] = "'" . $method . "':" . $method;

			$src .= "window['s9e'] = { 'TextFormatter': {" . \implode(',', $methods) . "} }\n";
		}

		$src = $this->getMinifier()->get($src);

		return $src;
	}

	public function setMinifier($minifier)
	{
		if (\is_string($minifier))
		{
			$className = __NAMESPACE__ . '\\JavaScript\\Minifiers\\' . $minifier;

			$args = \array_slice(\func_get_args(), 1);
			if (!empty($args))
			{
				$reflection = new ReflectionClass($className);
				$minifier   = $reflection->newInstanceArgs($args);
			}
			else
				$minifier = new $className;
		}

		$this->minifier = $minifier;

		return $minifier;
	}

	protected static function convertBitfield($bitfield)
	{
		$hex = array();

		foreach (\str_split($bitfield, 4) as $quad)
		{
			$v = '';
			foreach (\str_split($quad, 1) as $c)
				$v = \sprintf('%02X', \ord($c)) . $v;

			$hex[] = '0x' . $v;
		}

		$code = new Code('[' . \implode(',', $hex) . ']');

		return $code;
	}

	protected function getHints()
	{
		$this->hints = array(
			'attributeGenerator'      => 0,
			'attributeDefaultValue'   => 0,
			'closeAncestor'           => 0,
			'closeParent'             => 0,
			'fosterParent'            => 0,
			'postProcessing'          => 1,
			'regexpLimitActionAbort'  => 0,
			'regexpLimitActionIgnore' => 0,
			'regexpLimitActionWarn'   => 0,
			'requireAncestor'         => 0
		);

		$this->setPluginHints();
		$this->setRenderingHints();
		$this->setRulesHints();
		$this->setTagsHints();

		$js = "/** @const */ var HINT={};\n";
		foreach ($this->hints as $hintName => $hintValue)
			$js .= '/** @const */ HINT.' . $hintName . '=' . self::encode($hintValue) . ";\n";

		return $js;
	}

	protected function getPluginsConfig()
	{
		$plugins = new Dictionary;

		foreach ($this->config['plugins'] as $pluginName => $pluginConfig)
		{
			if (!isset($pluginConfig['parser']))
				continue;

			unset($pluginConfig['className']);

			if (isset($pluginConfig['quickMatch']))
			{
				$valid = array(
					'[[:ascii:]]',
					'[\\xC0-\\xDF][\\x80-\\xBF]',
					'[\\xE0-\\xEF][\\x80-\\xBF]{2}',
					'[\\xF0-\\xF7][\\x80-\\xBF]{3}'
				);

				$regexp = '#(?>' . \implode('|', $valid) . ')+#';

				if (\preg_match($regexp, $pluginConfig['quickMatch'], $m))
					$pluginConfig['quickMatch'] = $m[0];
				else
					unset($pluginConfig['quickMatch']);
			}

			$globalKeys = array(
				'parser'            => 1,
				'quickMatch'        => 1,
				'regexp'            => 1,
				'regexpLimit'       => 1,
				'regexpLimitAction' => 1
			);

			$globalConfig = \array_intersect_key($pluginConfig, $globalKeys);
			$localConfig  = \array_diff_key($pluginConfig, $globalKeys);

			if (isset($globalConfig['regexp'])
			 && !($globalConfig['regexp'] instanceof RegExp))
			{
				$regexp = RegexpConvertor::toJS($globalConfig['regexp']);
				$regexp->flags .= 'g';

				$globalConfig['regexp'] = $regexp;
			}

			$globalConfig['parser'] = new Code(
				'/**
				* @param {!string} text
				* @param {!Array.<Array>} matches
				*/
				function(text, matches)
				{
					/** @const */
					var config=' . self::encode($localConfig) . ';
					' . $globalConfig['parser'] . '
				}'
			);

			$plugins[$pluginName] = $globalConfig;
		}

		$code = new Code(self::encode($plugins));

		return $code;
	}

	protected function getRegisteredVarsConfig()
	{
		$registeredVars = $this->config['registeredVars'];

		unset($registeredVars['cacheDir']);

		return new Code(self::encode(new Dictionary($registeredVars)));
	}

	protected function getRootContext()
	{
		$rootContext = $this->config['rootContext'];

		$rootContext['allowedChildren']
			= self::convertBitfield($rootContext['allowedChildren']);
		$rootContext['allowedDescendants']
			= self::convertBitfield($rootContext['allowedDescendants']);

		$code = new Code(self::encode($rootContext));

		return $code;
	}

	protected function getSource()
	{
		$files = array(
			'Parser/utils.js',
			'Parser/BuiltInFilters.js',
			'Parser/' . (\in_array('getLogger', $this->exportMethods) ? '' : 'Null') . 'Logger.js',
			'Parser/Tag.js',
			'Parser.js'
		);

		if (\in_array('preview', $this->exportMethods, \true))
			$files[] = 'render.js';

		$rendererGenerator = new XSLT;
		$this->xsl = $rendererGenerator->getXSL($this->configurator->rendering);

		$src = $this->getHints();

		foreach ($files as $filename)
		{
			if ($filename === 'render.js')
				$src .= '/** @const */ var xsl=' . \json_encode($this->xsl) . ";\n";

			$filepath = __DIR__ . '/../' . $filename;
			$src .= \file_get_contents($filepath) . "\n";
		}

		return $src;
	}

	protected function getTagsConfig()
	{
		$this->replaceCallbacks();

		$tags = new Dictionary;
		foreach ($this->config['tags'] as $tagName => $tagConfig)
		{
			if (isset($tagConfig['attributes']))
				$tagConfig['attributes'] = new Dictionary($tagConfig['attributes']);

			$tagConfig['allowedChildren']
				= self::convertBitfield($tagConfig['allowedChildren']);
			$tagConfig['allowedDescendants']
				= self::convertBitfield($tagConfig['allowedDescendants']);

			$tags[$tagName] = $tagConfig;
		}

		$code = new Code(self::encode($tags));

		return $code;
	}

	public static function encode($value)
	{
		if (\is_scalar($value))
		{
			if (\is_bool($value))
				return ($value) ? '!0' : '!1';

			return \json_encode($value);
		}

		if ($value instanceof RegexpObject)
			$value = $value->toJS();

		if ($value instanceof RegExp
		 || $value instanceof Code)
			return (string) $value;

		if (!\is_array($value) && !($value instanceof Dictionary))
			throw new RuntimeException('Cannot encode non-scalar value');

		if ($value instanceof Dictionary)
		{
			$value = $value->getArrayCopy();
			$preserveKeys = \true;
		}
		else
			$preserveKeys = \false;

		$isArray = (!$preserveKeys && \array_keys($value) === \range(0, \count($value) - 1));

		$src = ($isArray) ? '[' : '{';
		$sep = '';

		foreach ($value as $k => $v)
		{
			$src .= $sep;

			if (!$isArray)
				$src .= (($preserveKeys || !self::isLegalProp($k)) ? \json_encode($k) : $k) . ':';

			$src .= self::encode($v);
			$sep = ',';
		}

		$src .= ($isArray) ? ']' : '}';

		return $src;
	}

	protected function injectConfig(&$src)
	{
		$this->callbacks = array();

		$config = array(
			'plugins'        => $this->getPluginsConfig(),
			'registeredVars' => $this->getRegisteredVarsConfig(),
			'rootContext'    => $this->getRootContext(),
			'tagsConfig'     => $this->getTagsConfig()
		);
		$src = \preg_replace_callback(
			'/(\\nvar (' . \implode('|', \array_keys($config)) . '))(;)/',
			function ($m) use ($config)
			{
				return $m[1] . '=' . $config[$m[2]] . $m[3];
			},
			$src
		);

		$src .= "\n" . \implode("\n", $this->callbacks) . "\n";
	}

	public static function isLegalProp($name)
	{
		$reserved = array('abstract', 'boolean', 'break', 'byte', 'case', 'catch', 'char', 'class', 'const', 'continue', 'debugger', 'default', 'delete', 'do', 'double', 'else', 'enum', 'export', 'extends', 'false', 'final', 'finally', 'float', 'for', 'function', 'goto', 'if', 'implements', 'import', 'in', 'instanceof', 'int', 'interface', 'let', 'long', 'native', 'new', 'null', 'package', 'private', 'protected', 'public', 'return', 'short', 'static', 'super', 'switch', 'synchronized', 'this', 'throw', 'throws', 'transient', 'true', 'try', 'typeof', 'var', 'void', 'volatile', 'while', 'with');

		if (\in_array($name, $reserved, \true))
			return \false;

		return (bool) \preg_match('#^[$_\\pL][$_\\pL\\pNl]+$#Du', $name);
	}

	protected function replaceCallbacks()
	{
		foreach ($this->config['tags'] as &$tagConfig)
		{
			if (isset($tagConfig['filterChain']))
			{
				foreach ($tagConfig['filterChain'] as &$filter)
					$filter = $this->convertCallback('tagFilter', $filter);
				unset($filter);
			}

			if (isset($tagConfig['attributes']))
			{
				foreach ($tagConfig['attributes'] as &$attrConfig)
				{
					if (isset($attrConfig['filterChain']))
					{
						foreach ($attrConfig['filterChain'] as &$filter)
							$filter = $this->convertCallback('attributeFilter', $filter);
						unset($filter);
					}

					if (isset($attrConfig['generator']))
						$attrConfig['generator'] = $this->convertCallback(
							'attributeGenerator',
							$attrConfig['generator']
						);
				}
				unset($attrConfig);
			}
		}
	}

	protected function convertCallback($callbackType, array $callbackConfig)
	{
		$callback = $callbackConfig['callback'];
		$params   = (isset($callbackConfig['params'])) ? $callbackConfig['params'] : array();

		if (isset($callbackConfig['js']))
			$jsCallback = '(' . $callbackConfig['js'] . ')';
		elseif (\is_string($callback))
			if (\substr($callback, 0, 41) === 's9e\\TextFormatter\\Parser\\BuiltInFilters::')
				$jsCallback = 'BuiltInFilters.' . \substr($callback, 41);
			elseif (\substr($callback, 0, 26) === 's9e\\TextFormatter\\Parser::')
				$jsCallback = \substr($callback, 26);

		if (!isset($jsCallback))
			return new Code('returnFalse');

		$arguments = array(
			'attributeFilter' => array(
				'attrValue' => '*',
				'attrName'  => '!string'
			),
			'attributeGenerator' => array(
				'attrName'  => '!string'
			),
			'tagFilter' => array(
				'tag'       => '!Tag',
				'tagConfig' => '!Object'
			)
		);

		$js = '(' . \implode(',', \array_keys($arguments[$callbackType])) . '){return ' . $jsCallback . '(';

		$sep = '';
		foreach ($params as $k => $v)
		{
			$js .= $sep;
			$sep = ',';

			if (isset($v))
				$js .= self::encode($v);
			else
			{
				if (!isset($arguments[$callbackType][$k])
				 && $k !== 'logger'
				 && $k !== 'openTags'
				 && $k !== 'registeredVars')
					$k = 'registeredVars[' . \json_encode($k) . ']';

				$js .= $k;
			}
		}

		$js .= ');}';

		$header = "/**\n";
		foreach ($arguments[$callbackType] as $paramName => $paramType)
			$header .= '* @param {' . $paramType . '} ' . $paramName . "\n";
		$header .= "*/\n";

		$funcName = \sprintf('c%08X', \crc32($js));

		$js = $header . 'function ' . $funcName . $js;

		$this->callbacks[$funcName] = $js;

		return new Code($funcName);
	}

	protected function setPluginHints()
	{
		foreach ($this->config['plugins'] as $pluginConfig)
			if (isset($pluginConfig['regexpLimitAction']))
			{
				$hintName = 'regexpLimitAction' . \ucfirst($pluginConfig['regexpLimitAction']);
				if (isset($this->hints[$hintName]))
					$this->hints[$hintName] = 1;
			}
	}

	protected function setRulesHints()
	{
		$flags = 0;
		foreach ($this->config['tags'] as $tagConfig)
		{
			foreach (\array_intersect_key($tagConfig['rules'], $this->hints) as $k => $v)
				$this->hints[$k] = 1;
			$flags |= $tagConfig['rules']['flags'];
		}
		$flags |= $this->config['rootContext']['flags'];

		$parser = new ReflectionClass('s9e\\TextFormatter\\Parser');
		foreach ($parser->getConstants() as $constName => $constValue)
			if (\substr($constName, 0, 5) === 'RULE_')
				$this->hints[$constName] = ($flags & $constValue) ? 1 : 0;
	}

	protected function setTagsHints()
	{
		foreach ($this->config['tags'] as $tagConfig)
			if (!empty($tagConfig['attributes']))
				foreach ($tagConfig['attributes'] as $attrConfig)
				{
					if (isset($attrConfig['generator']))
						$this->hints['attributeGenerator'] = 1;

					if (isset($attrConfig['defaultValue']))
						$this->hints['attributeDefaultValue'] = 1;
				}
	}

	protected function setRenderingHints()
	{
		if (\strpos($this->xsl, 'data-s9e-livepreview-postprocess') === \false)
			$this->hints['postProcessing'] = 0;
	}
}