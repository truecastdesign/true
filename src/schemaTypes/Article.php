<?php

namespace True\schemaTypes;

/**
 * Schema for articles
 * 
 * @version 1.0.3
 */
class Article
{
	private $structure = [];

	/**
	 * Get values with an value object
	 * 
	 * Google Approved Set
	 * 
	 * author: ['@type'=>'Organization', 'name'=>'Org Name', 'url'=>'url'] or Person ['@type'=>'Person', 'name'=>'text', 'url'=>'url', 'jobTitle'=>'']
	 * 
	 * datePublished: yyyy-mm-dd
	 * dateModified: yyyy-mm-dd
	 * 
	 * headline: ''
	 * 
	 * image: 'url' or ['url1', 'url2']
	 * 
	 * All
	 * 
	 * title: The title of the article
	 * dateCreated: yyyy-mm-dd
	 * datePublished: yyyy-mm-dd
	 * dateModified: yyyy-mm-dd
	 * alternativeHeadline: A subtitle of the article or opening line.
	 * image: An image of the item. This can be a URL or a fully described ImageObject. https://schema.org/ImageObject
	 * ImageObject: ['type'=>'ImageObject', 'contentUrl'=>'http', 'width'=>234, 'height'=>456, 'caption'=>'text', 'author'=>'Name', 'contentLocation'=>'City, State, Country', 'datePublished'=>'2012-01-01', 'description'=>'What the photo is about.', 'name'=>'Title of image']
	 * 
	 * author: Organization ['type'=>'Organization', 'name'=>'Org Name', 'address'=>'123 Main St.', 'logo'=>'url', 'telephone'=>'541-', 'slogan'=>'text']  or Person ['type'=>'Person', 'name'=>'text', 'email'=>'text', 'image'=>'url'] objects
	 * 
	 * award: An award won by or for this item.
	 * editor: Person ['type'=>'Person', 'name'=>'text', 'email'=>'text', 'telephone'=>'541-', 'image'=>'url'] object
	 * 
	 * publisher: Organization or Person object ['type'=>'Organization', 'name'=>'Org Name', 'address'=>'123 Main St.', 'logo'=>'url', 'telephone'=>'541-', 'slogan'=>'text']
	 * 
	 * genre: text or url, Genre of the creative work, broadcast channel or group. https://vocab.getty.edu/ example: http://vocab.getty.edu/aat/300021143
	 * 
	 * keywords: keywords list are typically delimited by commas
	 * 
	 * wordcount: 56 - Integer - The number of words in the text of the Article.
	 * 
	 * url: http://
	 * 
	 * description: text
	 * 
	 * articleBody: full text
	 * 
	 * inLanguage: en
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

		$info = (array) $info;

		foreach ($info as $key=>$value) {
			if (empty($value))
				continue;

			switch ($key) {
				case 'title':
					$data['headline'] = $value;
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