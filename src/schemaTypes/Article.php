<?php

namespace True\schemaTypes;

class Article
{
	private $structure = [];

	/**
	 * Get values with an value object
	 * 
	 * title: The title of the article
	 * datePublished: yyyy-mm-dd
	 * dateModified: yyyy-mm-dd
	 *
	 * @param object $info
	 * @return void
	 */
	public function set(object $info)
	{
		$data = [
			"@context" => "http://schema.org",
			"@type"=>'Article'
		];

		if (isset($info->title) and !empty($info->title))
			$data['headline'] = $info->title;
			
		if (isset($info->datePublished) and !empty($info->datePublished))
			$data['datePublished'] = $info->datePublished;

		if (isset($info->dateModified) and !empty($info->dateModified))
			$data['dateModified'] = $info->dateModified;

		$this->structure = $data;
	}

	public function get()
	{
		return $this->structure;
	}
}