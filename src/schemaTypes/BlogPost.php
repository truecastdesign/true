<?php

namespace True\schemaTypes;

class BlogPost
{
	private $structure = [];

	/**
	 * Get values with an value object
	 * 
	 * https://jsonld.com/blog-post/
	 * 
	 * title: The title of the post
	 * alternativeHeadline: Extra headline of the post
	 * datePublished: yyyy-mm-dd
	 * dateModified: yyyy-mm-dd
	 * image: https://www.example.com/images/photo.jpg
	 * author: Joe Smith
	 * accountablePerson: Joe Smith
	 * accountablePersonUrl: https://www.example.com/
	 * creator: Joe Smith
	 * creatorUrl: https://www.example.com/
	 * inLanguage: en-US
	 *
	 * @param object $info
	 * @return void
	 */
	public function set(object $info)
	{
		$data = [
			"@context" => "http://schema.org",
			"@type"=>'BlogPosting'
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

		if (isset($info->author) and !empty($info->author)) {
			$data['author'] = ["@type"=>"Person", "name"=>$info->author];
		}

		if (isset($info->accountablePerson) and !empty($info->accountablePerson)) {
			$data['accountablePerson'] = ["@type"=>"Person", "name"=>$info->author, "url"=>$info->accountablePersonUrl];
		}
		
		if (isset($info->creator) and !empty($info->creator)) {
			$data['creator'] = ["@type"=>"Person", "name"=>$info->creator, "url"=>$info->creatorUrl];
		}

		if (isset($info->publisher) and !empty($info->publisher)) {
			$data['publisher'] = ["@type"=>"Organization", 
			"name"=>$info->publisher, "url"=>$info->publisherUrl, "logo"=>[
				"@type"=>"ImageObject", "url"=>$info->publisherLogo, 
				"width"=>$info->publisherLogoW, "height"=>$info->publisherLogoH
			]];
		}

		if (isset($info->sponsor) and !empty($info->sponsor)) {
			$data['publisher'] = ["@type"=>"Organization", 
			"name"=>$info->sponsor, "url"=>$info->sponsorUrl, "logo"=>[
				"@type"=>"ImageObject", "url"=>$info->sponsorLogo, 
				"width"=>$info->sponsorLogoW, "height"=>$info->sponsorLogoH
			]];
		}

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
			
		if (isset($info->publisher) and !empty($info->publisher))
			$data['publisher'] = $info->publisher;

		if (isset($info->url) and !empty($info->url))
			$data['url'] = $info->url;

		if (isset($info->description) and !empty($info->description))
			$data['description'] = $info->description;

		if (isset($info->articleBody) and !empty($info->articleBody))
			$data['articleBody'] = $info->articleBody;
			
		if (isset($info->articleSection) and !empty($info->articleSection))
			$data['articleSection'] = $info->articleSection;

		if (isset($info->inLanguage) and !empty($info->inLanguage))
			$data['inLanguage'] = $info->inLanguage;

		if (isset($info->isFamilyFriendly) and !empty($info->isFamilyFriendly))
			$data['isFamilyFriendly'] = $info->isFamilyFriendly;

		if (isset($info->copyrightYear) and !empty($info->copyrightYear))
			$data['copyrightYear'] = $info->copyrightYear;

		if (isset($info->copyrightHolder) and !empty($info->copyrightHolder))
			$data['copyrightHolder'] = $info->copyrightHolder;

		if (isset($info->contentLocation) and !empty($info->contentLocation)) {
			$data['contentLocation'] = ["@type"=>"Place", "name"=>$info->contentLocation];
		}

		$this->structure = $data;
	}

	public function get()
	{
		return $this->structure;
	}
}