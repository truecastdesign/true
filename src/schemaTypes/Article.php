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
			
		if (isset($info->alternativeHeadline) and !empty($info->alternativeHeadline))
			$data['alternativeHeadline'] = $info->alternativeHeadline;

		if (isset($info->image) and !empty($info->image))
			$data['image'] = $info->image;

		if (isset($info->author) and !empty($info->author))
			$data['author'] = $info->author;

		if (isset($info->award) and !empty($info->award))
			$data['award'] = $info->award;

		if (isset($info->editor) and !empty($info->editor))
			$data['editor'] = $info->editor;

		if (isset($info->genre) and !empty($info->genre))
			$data['genre'] = $info->genre;

		if (isset($info->keywords) and !empty($info->keywords))
			$data['keywords'] = $info->keywords;

		if (isset($info->wordcount) and !empty($info->wordcount))
			$data['wordcount'] = $info->wordcount;

		if (isset($info->url) and !empty($info->url))
			$data['url'] = $info->url;

		if (isset($info->description) and !empty($info->description))
			$data['description'] = $info->description;

		if (isset($info->articleBody) and !empty($info->articleBody))
			$data['articleBody'] = $info->articleBody;

		$this->structure = $data;
	}

	public function get()
	{
		return $this->structure;
	}
}