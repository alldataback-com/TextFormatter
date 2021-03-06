<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\XSLT;

use s9e\TextFormatter\Configurator\TemplateNormalizer;

class Optimizer
{
	/**
	* @var TemplateNormalizer
	*/
	public $normalizer;

	/**
	* Constructor
	*/
	public function __construct()
	{
		$this->normalizer = new TemplateNormalizer([
			'MergeConsecutiveCopyOf',
			'MergeIdenticalConditionalBranches',
			'OptimizeNestedConditionals',
			'RemoveLivePreviewAttributes'
		]);
	}

	/**
	* Optimize a single template
	*
	* @param  string $template Original template
	* @return string           Optimized template
	*/
	public function optimizeTemplate($template)
	{
		return $this->normalizer->normalizeTemplate($template);
	}
}