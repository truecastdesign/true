<?php

namespace True\schemaTypes;

/**
 * Schema for WebPage
 * 
 * @version 1.0.3
 */
class WebPage
{
	private $structure = [];

	/**
	 * Get values with an value object
	 * 
	 * url: https://www.domain.com
	 * name: Title of the web page
	 * description: A description of the site
	 * 
	 * datePublished: yyyy-mm-dd
	 * dateModified: yyyy-mm-dd
	 * 
	 * alternativeHeadline: A subtitle of the article or opening line.
	 * 
	 * primaryImageOfPage: An image of the item. This can be a URL or a fully described ImageObject. https://schema.org/ImageObject
	 * ImageObject: ['type'=>'ImageObject', 'contentUrl'=>'http', 'width'=>234, 'height'=>456, 'caption'=>'text', 'author'=>'Name', 'contentLocation'=>'City, State, Country', 'datePublished'=>'2012-01-01', 'description'=>'What the photo is about.', 'name'=>'Title of image']
	 * 
	 * 
	 * inLanguage: en - language codes from the IETF BCP 47 standard https://en.wikipedia.org/wiki/IETF_language_tag  English: en
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

		$this->structure = $data;
	}

	public function get()
	{
		return $this->structure;
	}
}