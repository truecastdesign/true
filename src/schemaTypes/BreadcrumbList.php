<?php

namespace True\schemaTypes;

/**
 * Schema for BreadcrumbList
 * 
 * @version 1.0.2
 */
class BreadcrumbList
{
	private $structure = [];

	/**
	 * Get values with an value object
	 * 
	 * ['Books'=>"https://example.com/books", "Science Fiction"=>"https://example.com/books/sciencefiction", "Award Winners"=>null]
	 * 
	 * @param object $info
	 * @return void
	 */
	public function set(object $info)
	{
		$data = [
			"@context" => "http://schema.org",
			"@type"=>'BreadcrumbList'
		];

		$info = (array) $info;

		$list = [];

		$i = 1;
		foreach ($info as $name=>$item) {
			$page = ['@type'=>'ListItem', 'position'=>$i, 'name'=>$name];
			if (!is_null($item))
				$page['item'] = $item;
			$list[] = (object) $page;
			$i++;
		}

		$data['itemListElement'] = $list;

		$this->structure = $data;
	}

	public function get()
	{
		return $this->structure;
	}
}