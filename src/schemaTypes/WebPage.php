<?php

namespace True\schemaTypes;

/**
 * Schema for WebPage
 * 
 * @version 1.0.2
 */
class WebPage
{
	private $structure = [];

	/**
	 * Get values with an value object
	 * 
	 * url: https://www.domain.com
	 * name: Website name
	 * description: A description of the site
	 * 
	 * searchAction: ['url'=>'https://umbraco.com/search/?q={search_term_string}', 'input'=>'required name=search_term_string']
	 * 
	 * inLanguage: en - language codes from the IETF BCP 47 standard https://en.wikipedia.org/wiki/IETF_language_tag
	 * audience: text example: Small businesses
	 * author: Organization or Person
	 * 
	 * genre: text or url, Genre of the creative work, broadcast channel or group. https://vocab.getty.edu/ example: http://vocab.getty.edu/aat/300021143
	 * 
	 * keywords: keyword1, keyword2
	 * 
	 * publisher: Organization or Person ['type'=>'Organization', 'name'=>'Org Name', 'address'=>'123 Main St.', 'logo'=>'url', 'telephone'=>'541-', 'slogan'=>'text'] OR ['type'=>'Person', 'name'=>'text', 'email'=>'text', 'image'=>'url']
	 * 
	 * review: 	Review object, ['type'=>'Review', 'reviewRating'=>['type'=>'Rating', 'ratingValue'=>5, 'bestRating'=>5, 'worstRating'=>0], 'author'=>['type'=>'Person', 'name'=>'John Doe'], 'publisher'=>['type'=>'Organization', 'name'=>'Business Name'], 'datePublished'=>'2023-01-01', 'reviewBody'=>'Test of the review']
	 * 
	 * aggregateRating: ['type'=>'AggregateRating', 'ratingValue'=>5, 'bestRating'=>5, 'worstRating'=>0, 'ratingCount'=>10]
	 * 
	 * @param object $info
	 * @return void
	 */
	public function set(object $info)
	{
		$data = [
			"@context" => "http://schema.org",
			"@type"=>'WebSite'
		];

		$info = (array) $info;

		foreach ($info as $key=>$value) {
			if (empty($value))
				continue;

			switch ($key) {
				case 'searchAction':
					$data['potentialAction'] = ['@type'=>'SearchAction', 'target'=>$value['url'], 'query-input'=>$value['input']];
				break;

				case 'audience':
					$data['audience'] = ['@type'=>'Audience', 'name'=>$value];
				break;

				default:
					$data[$key] = $value;
			}
		}

		
			
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

		if (isset($info->pageType) and !empty($info->pageType) and isset($info->url) and !empty($info->url))
			$data['mainEntityOfPage'] = ['@type'=>$info->pageType, '@id'=>$info->url, 'url'=>$info->url];

		$this->structure = $data;
	}

	public function get()
	{
		return $this->structure;
	}
}