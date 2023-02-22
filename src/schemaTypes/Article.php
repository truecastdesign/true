<?php

namespace True\schemaTypes;

/**
 * Schema for articles
 * 
 * @version 1.0.1
 */
class Article
{
	private $structure = [];

	/**
	 * Get values with an value object
	 * 
	 * title: The title of the article
	 * datePublished: yyyy-mm-dd
	 * dateModified: yyyy-mm-dd
	 * alternativeHeadline: A subtitle of the article or opening line.
	 * image: An image of the item. This can be a URL or a fully described ImageObject. https://schema.org/ImageObject
	 * ImageObject: ['contentUrl'=>'http', 'width'=>234, 'height'=>456, 'caption'=>'text', 'author'=>'Name', 'contentLocation'=>'City, State, Country', 'datePublished'=>'2012-01-01', 'description'=>'What the photo is about.', 'name'=>'Title of image']
	 * 
	 * author: Organization ['type'=>'Organization', 'name'=>'Org Name', 'address'=>'123 Main St.', 'logo'=>'url', 'telephone'=>'541-', 'slogan'=>'text']  or Person ['type'=>'Person', 'name'=>'text', 'email'=>'text', 'image'=>'url'] objects
	 * 
	 * award: An award won by or for this item.
	 * editor: Person ['type'=>'Person', 'name'=>'text', 'email'=>'text', 'telephone'=>'541-', 'image'=>'url'] object
	 * 
	 * publisher: Organization or Person object ['type'=>'Organization', 'name'=>'Org Name', 'address'=>'123 Main St.', 'logo'=>'url', 'telephone'=>'541-', 'slogan'=>'text']
	 * genre: text or url, Genre of the creative work, broadcast channel or group. https://vocab.getty.edu/ example: http://vocab.getty.edu/aat/300021143
	 * keywords: keywords list are typically delimited by commas
	 * wordcount: 56 - Integer - The number of words in the text of the Article.
	 * url: http://
	 * description: text
	 * articleBody: full text
	 * pageType: AmpStory, ArchiveComponent, Article, Atlas, Blog, Book, Chapter, Claim, Clip, Collection, ComicStory, Comment, Conversation, Course, CreativeWorkSeason, CreativeWorkSeries, DataCatalog, Dataset, DefinedTermSet, Diet, DigitalDocument, Drawing, EducationalOccupationalCredential, Episode, ExercisePlan, Game, Guide, HowTo, HowToDirection, HowToSection, HowToStep, HowToTip, HyperToc, HyperTocEntry, LearningResource, Legislation, Manuscript, Map, MathSolver, MediaObject, MediaReviewItem, Menu, MenuSection, Message, Movie, MusicComposition, MusicPlaylist, MusicRecording, Painting, Photograph, Play, Poster, PublicationIssue, PublicationVolume, Quotation, Review, Sculpture, SheetMusic, ShortStory, SoftwareApplication, SoftwareSourceCode, SpecialAnnouncement, Statement, TVSeason, TVSeries, Thesis, VisualArtwork, WebContent, WebPage, WebPageElement, WebSite
	 * 
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