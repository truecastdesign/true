<?php

namespace True\schemaTypes;

/**
 * Schema for WebSite
 * 
 * @version 1.0.2
 */
class WebSite
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
	 * publisher: Organization or Person ['type'=>'Organization', 'name'=>'Org Name', 'address'=>['@type'=>'PostalAddress', 'name'=>'123 Main St.'], 'logo'=>['type'=>'ImageObject', 'url'=>'url', 'width'=>, 'height'=>, 'caption'=>''], 'telephone'=>'541-', 'slogan'=>'text'] OR ['type'=>'Person', 'name'=>'text', 'email'=>'text', 'image'=>['type'=>'ImageObject', 'url'=>'url', 'width'=>, 'height'=>, 'caption'=>''], sameAs=>["https://www.facebook.com/Name", "https://www.youtube.com/user/Name"], 'legalName'=>'text', member=>['type'=>'Organization', ect...]]
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