<?php
namespace True;

/**
 * @version 1.3.7
 */
class SEO
{
	/**
	 * Generate JSON+LD script
	 *
	 * @param string $type
	 * @param array|object $info
	 * @return void
	 */
	public function jsonLD(string $type, $info)
	{
		if (is_array($info))
			$info = (object) $info;
		
		switch (strtolower($type)) {
			case 'recipe':
				$Schema = new \True\schemaTypes\Recipe;
			break;
			case 'article':
				$Schema = new \True\schemaTypes\Article;
			break;
			case 'organization':
				$Schema = new \True\schemaTypes\Organization;
			break;
			case 'website':
				$Schema = new \True\schemaTypes\WebSite;
			break;
			case 'webpage':
				$Schema = new \True\schemaTypes\WebPage;
			break;
			case 'blogpost':
				$Schema = new \True\schemaTypes\BlogPost;
			break;
			case 'breadcrumbs':
				$Schema = new \True\schemaTypes\BreadcrumbList;
			break;
			case 'store':
				$Schema = new \True\schemaTypes\Store;
			break;
			case 'homeandconstructionbusiness':
				$Schema = new \True\schemaTypes\HomeAndConstructionBusiness;
			break;
			case 'product':
				$Schema = new \True\schemaTypes\Products;
			break;
		}

		if (!is_object($Schema))
			throw new \Exception("Invalid schema type $type");

		$Schema->set($info);
		return $this->addHTML($Schema->get());
	}

	private function addHTML($schema)
	{
		$html = '<script type="application/ld+json">'."\n";
		$html .= json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
		$html .= "\n".'</script>'."\n";

		return $html;
	}

	/**
	 * generate an array of path and names for the BreadcrumbList schema
	 *
	 * @param string $lookupFile full file path to an ini file for custom page name lookups.
	 * example: / = "Company Name"
	 * 			/contact.html = "Contact Us"
	 * @return array ['Books'=>"https://example.com/books", "Science Fiction"=>"https://example.com/books/sciencefiction", "Award Winners"=>null]
	 */
	public function generateBreadcrumbs($lookupFile = null)
	{
		$path = strtok(filter_var($_SERVER["REQUEST_URI"], FILTER_SANITIZE_URL), '?');

		$https = array_key_exists('HTTPS', $_SERVER) ? ($_SERVER['HTTPS'] == 'on' ? true:false):false;
		$protocol = ($https ? 'https':'http').'://';

		$patternElements = explode('/', ltrim($path, '/'));
		
		end($patternElements);
		$lastPath = current($patternElements);
		
		if (!strstr($lastPath, '.html'))
			array_pop($patternElements);
		
		$lookup = (array) parse_ini_file($lookupFile);

		// if homepage
		$homePath = ($path == '/')? null:$protocol.$_SERVER['HTTP_HOST'].'/';

		$list = (!empty($lookup['/']))? [$lookup['/']=>$homePath]:['Home'=>$homePath];

		$pathBuilt = '/';
		foreach ($patternElements as $part) {
			$pathBuilt .= (strstr($part, '.html'))? $part:$part."/";
			
			$title = (!empty($lookup[$pathBuilt]))? $lookup[$pathBuilt]:$this->formatURLPath($part);

			if (!empty($title))
				$list[$title] = $protocol.$_SERVER['HTTP_HOST'].$pathBuilt;

		}

		end($list);
		$lastKey = key($list);
		$list[$lastKey] = null;

		return $list;
	}

	public function formatURLPath($part)
	{
		return ucwords(str_replace(['-','.html'],[' ',''],trim($part))); 
	}

	/**
	 * Used by Google
	 *
	 * @param object $info {"url", "title", "description", "site_logo_url", "site_logo_width", "site_logo_height", "site_logo_caption", "datePublished", "dateModified", "social_media"=>{"facebook", "twitter", "youtube", "instagram"}, "breadcrumbs"=>[{"name"=>"Books", "url"=>"/books/"},{"name"=>"Science Fiction", "url"=>"/books/sciencefiction/"}]}
	 * @return void
	 */
	public function schemaGraph(object $info)
	{
		$info->url = rtrim($info->url, '/');
		
		$sameAs = [];
		
		if (is_object($info->social_media)) {
			$socialMedia = (array) $info->social_media;
			foreach ($socialMedia as $site)
				$sameAs[] = $site;
		}

		$itemListElements = [];
		if (is_array($info->breadcrumbs)) {
			$position = 1;
			foreach ($info->breadcrumbs as $crumb) {
				$itemListElements[] = (object)[
					"@type"=>"ListItem",
					"position"=>$position,
					"name"=>$crumb['name'],
					"item"=>$crumb['url']
				];
				$position++;
			}

			$itemListElements[] = (object)[
				"@type"=>"ListItem",
				"position"=>$position,
				"name"=>$info->title
			];
		}

		/*if (is_array($info->breadcrumbs)) {
			$position = 1;
			foreach ($info->breadcrumbs as $crumb) {
				$itemListElements[] = (object)[
					"@type"=>"ListItem",
					"position"=>$position,
					"item"=>[
						"@type"=>"WebSite",
						"@id"=>$info->base_url.$crumb['url'],
						"name"=>$crumb['name']
					]
				];
				$position++;
			}
		}*/
		
		$data = [
			"@context" => "http://schema.org",
			"@graph" => [
				0=>[
					"@type"=> "Organization",
					"@id"=> $info->url."#organization",
					"name"=> $info->title,
					"url"=> $info->url,
					"sameAs"=> $sameAs,
					"logo"=> [
						"@type"=> "ImageObject",
						"@id"=> $info->url."#logo",
						"inLanguage"=> "en-GB",
						"url"=> $info->site_logo_url,
						"width"=> $info->site_logo_width,
						"height"=> $info->site_logo_height,
						"caption"=> $info->site_logo_caption
					],
					"image"=> [
						"@id"=> $info->url."#logo"
					]
				],
				1=>[
					"@type"=> "WebSite",
					"@id"=> $info->url."#website",
					"url"=> $info->url,
					"name"=> $info->title,
					"description"=> $info->description,
					"publisher"=> [
						"@id"=> $info->url."#organization"
					],
					"inLanguage"=> "en-GB"
				],
				2=>[
					"@type"=> "WebPage",
					"@id"=> $info->url."#webpage",
					"url"=> $info->url,
					"name"=> $info->title,
					"isPartOf"=> [
						"@id"=> $info->url."#website"
					],
					"about"=> [
						"@id"=> $info->url."#organization"
					],
					"datePublished"=> $info->datePublished,
					"dateModified"=> $info->dateModified,
					"description"=> $info->description,
					"inLanguage"=> "en-GB",
					"potentialAction"=> [
						0=>[
							"@type"=> "ReadAction",
							"target"=> [
								$info->url
							]
						]
					]
				]
				
			],
		];
		
		$html = '<script type="application/ld+json">'."\n";
		$html .= json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$html .= "\n".'</script>'."\n";

		return $html;
	}
}