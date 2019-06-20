<?php
namespace True;

/**
 * PHP template system
 *
 *
 * @package True 6 framework
 * @author Daniel Baldwin
 * @version 5.2.16
 */
class PhpView
{
	public static $version = "5.2.16";

	
	# used keys: js, css, head, body, footer_controls, admin
	private $vars = [];
	private $metaData = ['_metaTitle'=>'', '_metaDescription'=>'', '_metaLinkText'=>'', '_metaNoCache'=>'', '_js'=>'', '_css'=>''];

	# in the route or controller, set meta data using $App->view->meta_name. 
	# meta_names are: title, description, css, js, no_cache, sort, not_live, label, not_in_nav
	# They work the same as the inline meta data in the view file
	# These inline meta data in the view file will override these if they are set.

	public function __construct($args = null)
	{
		$this->vars['base_path'] = (isset($args['base_path'])? $args['base_path']:BP.'/app/views/'); # from root; end with /; ex: BP.'/app/views/'
		$this->vars['assets_path'] = (isset($args['assets_path'])? $args['assets_path']:'/assets/'); # from root; end with /; ex: '/assets/'
		$this->vars['base_assets_path'] = (isset($args['assets_path'])? $args['assets_path']:BP.'/public_html/assets/'); # from root; end with /; ex: BP.'/public_html/assets/'
		$this->vars['layout'] = (isset($args['layout'])? $args['layout']:BP.'/app/views/_layouts/base.phtml'); # from root; end with /; ex: BP.'/app/views/_layouts/base.phtml'
		$this->vars['404'] = (isset($args['404'])? $args['404']:'404-error.phtml'); # put in base_path dir; ex: 404-error.phtml
		$this->vars['401'] = (isset($args['401'])? $args['401']:'401-error.phtml'); # put in base_path dir; ex: 401-error.phtml
		$this->vars['403'] = (isset($args['403'])? $args['403']:'403-error.phtml'); # put in base_path dir; ex: 403-error.phtml
	}

	/**
	 * Used to add global css and js files
	 * ex: $App->view->css = 'assets/css/global.css';
	 *
	 * @return void
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public function __set($key, $value)
	{
		$this->vars[$key] = $value;
	}

	/**
	 * Render views. Use .phtml file
	 * Format files with meta data at the top with {endmeta} before the html starts
	 * Meta data example (use ini format): 
	 * title="The text that goes in the title tag" -> access using $_metaTitle
	 * description="The text that goes in the meta description tag" -> access using $_metaDescription
	 * css="/assets/css/site.css, /vendor/company/project/assets/css/style.css, /app/assets/css/style2.css" -> access using $_css
	 * js="/assets/js/site.js, https://cdn.domain.com/script.js, /vendor/company/project/assets/js/file.js, /app/assets/js/file.js"  -> access using $_js
	 * no_cache # use no_cache for pages you don't want the browser to cache
	 * sort=1 # sort the pages so they display in a navigation in a certain order
	 * not_live # not live to the public
	 * label="A short label to identify the page"
	 * not_in_nav # remove it from nav but not from public view
	 * limit_access # restrict access to only users who are part of the provided group or groups
	 * groups[] = members # groups that are allowed to access this page
	 * head_html="<script type="module" src="path/to/file.js"></script>" -> access using $_headHTML
	 *
	 * @param String $taView - path and filename.phtml to render
	 * @param Array $variables - variables to pass to view file
	 * @param Bool $fullPath - true if path to file name is from server root
	 * @return void
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public function render($taView, $variables = [], $fullPath = false)
	{
		$outputFiles = [];
		$searchFiles = [];
		$replaceTags = [];
		$searchTags = [];

		if (empty($taView) or $taView == '.phtml') {
			$taView = 'index.phtml';
		}

		header('X-Powered-By: True 6');
				
		if (isset($this->vars['base_path']) and !$fullPath) {
			$taView = $this->vars['base_path'].$taView;
		}
		
		if (file_exists($taView) === false) {
			header("HTTP/1.1 404 Not Found");
			$this->metaData['_metaTitle'] = "File Not Found";
			$taView = $this->vars['base_path'].$this->vars['404'];
		}

		ob_start(); 
			extract($variables);
			include $taView;
		$fileContents = ob_get_clean();

		# insert template into page if needed
		preg_match_all("/\{partial:(.*)}/", $fileContents, $outputArray);
		
		if (is_array($outputArray[1])) {
			foreach ($outputArray[1] as $partial)
			{
				ob_start();
					include BP.'/app/views/_partials/'.$partial;
				$replaceTags[] = ob_get_clean();
				$searchTags[] = "{partial:".$partial."}";
			}
		}
		
		if (is_array($outputArray[0])) {
			foreach ($outputArray[0] as $tag) {
				$replaceTags[] = '';
				$searchTags[] = $tag;
			}
		}		

		# find the break point for the meta data
		$fileParts = explode("{endmeta}", $fileContents, 2);
		
		# if no {endmeta}, just use the file contents
		if (count($fileParts) == 1) {
			$fileParts[1] = $fileContents;
			unset($fileParts[0]);
		}

		# find and replace special tags
		if (isset($fileParts[1])) {
			$fileParts[1] = str_replace($searchTags, $replaceTags, $fileParts[1]);
		}

		# override for file based meta data with $App->view->meta_name
		$this->processMetaData($this->vars);

		if(isset($fileParts[0]) and isset($fileParts[1])) {
			$this->processMetaData( parse_ini_string($fileParts[0]) );
		}

		if (!$this->metaData['_metaNoCache']) {
			header('Expires: '.gmdate("D, d M Y H:i:s", strtotime("-4 hours")).' GMT');
			header_remove("Pragma");
			header("Pragma: no-cache");
			header_remove("Cache-Control");
			header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
			header("Cache-Control: post-check=0, pre-check=0", false);
		}		

		if (isset($fileParts[1])) {
			$this->metaData['_html'] = $fileParts[1];
		}
		elseif (isset($fileParts[0])) {
			$this->metaData['_html'] = $fileParts[0];
		}
		else {
			$this->metaData['_html'] = '';
		}
		
		if (isset($this->vars['layout'])) {
			extract($this->metaData);
			extract($variables);
			global $App;
			require_once $this->vars['layout'];
		}	
		else {
			extract($variables);
			echo $this->metaData['_html'];
		}
	}

	private function processMetaData($metaData = null)
	{	
		# add title and description to globals array
		if($metaData == null)
			$metaData = [];
		
		if(isset($metaData['title'])) {
			$this->metaData['_metaTitle'] = trim($metaData['title']);
		}			
		
		if(isset($metaData['description'])) {
			$this->metaData['_metaDescription'] = trim($metaData['description']);
		}
		
		if(isset($metaData['linkText'])) {
			$this->metaData['_metaLinkText'] = trim($metaData['linkText']);
		}

		if(isset($metaData['head_html'])) {
			$this->metaData['_headHTML'] = trim($metaData['head_html']);
		}
		else {
			$this->metaData['_headHTML'] = '';
		}

		$this->metaData['_metaNoCache'] = (array_key_exists('no_cache', $metaData)? true:false);

		$this->addInGlobalAssets($metaData, 'css');
		$this->addInGlobalAssets($metaData, 'js');	 
	}

	/**
	 * add in global assets like js and css files
	 *
	 * @param array $metaData
	 * @param string $type css, js, etc
	 * @return array of type $metaData
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	private function addInGlobalAssets($metaData = [], $type = '')
	{
		if(array_key_exists($type,$this->vars) and array_key_exists($type,$metaData))
		{
			$metaData[$type] = trim($this->vars[$type]). ','. $metaData[$type];
		}	
		elseif(array_key_exists($type,$this->vars))
		{
			$metaData[$type] = trim($this->vars[$type]);
		}

		if(array_key_exists($type,$metaData))
		{
			$assetListTmp = explode(',',$metaData[$type]);
			foreach($assetListTmp as $value)
			{
				$value = trim($value);

				if(strtok($value, '/') == 'vendor' OR strtok($value, '/') == 'app')
				{
					$assetList[] = BP.'/'.rtrim($value, '/');
				}
				elseif( strpos($value, '://') === false and !empty($value) and strpos($value, '*') === false)
				{
					$assetList[] = $_SERVER['DOCUMENT_ROOT'].$value;
				}
				elseif(!empty($value))
				{
					$assetList[] = $value;
				}
			}
			
			# build js files
			if($type == 'js')
				$this->metaData['_js'] = $this->buildJSFile($assetList);
			elseif($type == 'css')
				$this->metaData['_css'] = $this->buildCSSFile($assetList);
		}
		else
		{
			$this->metaData['_'.$type] = '';
		}	

		return $metaData;
	}

	/**
	 * process css files and return html to include them
	 *
	 * @param array $cssList list of css files with paths
	 * @return string html
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	private function buildCSSFile(array $cssList)
	{
		$firstPartFilename = $this->generateFileHash($cssList);
	
		$cssCachePath = $this->vars['assets_path'].'css/cache/';
		$cssCacheRootPath = $this->vars['base_assets_path'].'css/cache/';
				
		if(!empty($firstPartFilename))
		{
			$cacheFilename = $firstPartFilename.'.css';
		
			if(file_exists($cssCachePath.$cacheFilename))
			{
				return '<link rel="stylesheet" href="'.$cssCachePath.$cacheFilename.'">'."\n";
			}
			else
			{
				# make one instance of SCSS parser
				if(in_array('.scss', $cssList) !== false)
					$TAscss = new \True\SCSS;
			
				$cachedStr = '';
			
				foreach($cssList as $file)
				{
					# check to make sure it is a css file
					if(substr($file, strrpos($file, '.') + 1) == 'css')
					{ 
						if(file_exists($file))
							$cachedStr .= file_get_contents($file);
					}
			
					elseif(substr($file, strrpos($file, '.') + 1) == 'scss')
					{
						if(file_exists($file))
						{
							 $cachedStr .= $TAscss->compile( file_get_contents($file) );
						}	
					}
				} # end foreach
			
				# minify css string
				$cachedStrMin = \True\CSSMini::process($cachedStr);
				
				# put contents in file with hashed filename
				file_put_contents($cssCacheRootPath.$cacheFilename, $cachedStrMin);
			
				return '<link rel="stylesheet" href="'.$cssCachePath.$cacheFilename.'">'."\n";
			}
		}	
	}

	/**
	 * build js compressed file
	 *
	 * @param array $jsFiles list of js files
	 * @return string html for including js files
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	private function buildJSFile($jsFiles)
	{
		if(!is_array($jsFiles)) 
			return false;

		$cacheFilename = $this->generateFileHash($jsFiles);

		$jsCachePath = $this->vars['assets_path'].'js/cache/'.$cacheFilename.'.js';
		$jsCacheRootPath = $this->vars['base_assets_path'].'js/cache/'.$cacheFilename.'.js';
		
		$jsScripts = '';
		$cachedJSStr = '';

		# check if only non local files or combined files have already been cached
		if($cacheFilename === false OR file_exists($jsCacheRootPath))
		{
			if(is_array($jsFiles))
			foreach($jsFiles as $file)
			{
				if(strpos($file, '://') !== false OR strpos($file, '*') !== false)
				{
					$file = str_replace('*', '', $file);

					$jsScripts .= '<script src="'.$file.'"></script>'."\n";
				}	
			}

			if($cacheFilename !== false)
				$jsScripts .= '<script src="'.$jsCachePath.'"></script>'."\n";
			
			return $jsScripts;
		}
		# generate new js files and include them
		else
		{
			foreach($jsFiles as $file)
			{
			
				# check to make sure it is a js file
				if(substr($file, strrpos($file, '.') + 1) == 'js')
				{
					if(strpos($file, '://') !== false OR strpos($file, '*') !== false)
					{
						$cdnFiles[] = $file;
					}
					else
					{
						if(file_exists($file))
							$cachedJSStr .= file_get_contents($file);
					}
				}
			}
			
			# check and add in external or non cached js files
			
			if(isset($cdnFiles))
			foreach($cdnFiles as $file)
			{
				$file = str_replace('*', '', $file);

				$jsScripts .= '<script src="'.$file.'"></script>'."\n";
			}
			
			# check to make sure there is some js code to put in file, else there probably was noting but CDN files.
			if(!empty($cachedJSStr))
			{
				# minify js string
				$cachedJSStrMin = \True\JSMin::process($cachedJSStr);
			
				# check for failed minity
				if(empty($cachedJSStrMin) and !empty($cachedJSStr))
					$cachedJSStrMin = $cachedJSStr;
			
				# put contents in file with hashed filename
				file_put_contents($jsCacheRootPath, $cachedJSStrMin);
				
				# add to site header
				$jsScripts .= '<script src="'.$jsCachePath.'"></script>'."\n";
			}
		} 
		return $jsScripts;
	}
	
	/**
	 * get the contents of the files, combine them, and return the hash
	 *
	 * @param array $files - ['file/path/filename.ext', 'file/path/filename.ext']
	 * @return string - file contacts hash
	 * @author Daniel Baldwin
	 **/
	private function generateFileHash($files)
	{
		$content = '';

		foreach($files as $file)
		{
			if(strpos($file, '://') === false)
				$content .= file_get_contents($file);
		}
		
		if(empty($content))
			return false;
		else
			return md5($content);
	}
	
	
	
	/**
	 * create bread crumbs for site
	 *
	 * @param string $sep the separator between pages
	 * @return string html
	 * @author Daniel Baldwin
	 */
	public static function getBreadcrumbs($sep='&#x279D;', $checkLinkText=false)
	{
		$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

		global $TAConfig;

		#echo ' -- '.$Config->controlling_module.' -- ';
		#echo $_SERVER["SCRIPT_FILENAME"]."\n";
		
		$allParts = explode('/',$path);
		
		foreach($allParts as $key=>$value)
		{
			if($value) $parts[] = $value;
		}
		
		$c = count($parts);
		for($i=0; $i < $c; $i++)
		{ 
			if($i+1 == $c) # last one
			{
				$fileParts = explode('.',$parts[$i]);

				if($checkLinkText) # get the linkText meta item from the file for the page title rather than the filename
				{
					$fullFilePath = $_SERVER["DOCUMENT_ROOT"].'/modules/'.$TAConfig->controlling_module.'/views'.$path.'.html'; 
										
					$lines = file($fullFilePath);

					$j = 0;
		
					foreach($lines as $line)
					{ 
						if(strpos($line,'?>') !== false)
							break;
						
						if(strpos($line,'#linkText:') !== false)
						{
							$viewParts = explode(':',$line,2);
							$pageLinkText = trim($viewParts[1]);
						}

						if($j > 12) break;
						$j++;
					}
				}

				if(isset($pageLinkText))
					$linkText = $pageLinkText;
				else
					$linkText = self::ucwordss(str_replace('-',' ',$fileParts[0]), ["is", "to", "the", "for"]);
				
				$str .= ' '.$sep.' '.$linkText;
			}
			else
			{
				$url .= '/'.$parts[$i];

				if($checkLinkText) # get the linkText meta item from the file for the page title rather than the filename
				{
					$fullFilePath = $_SERVER["DOCUMENT_ROOT"].'/modules/'.$TAConfig->controlling_module.'/views'.$url.'.html'; 
					
					$lines = file($fullFilePath);

					$j = 0;
		
					foreach($lines as $line)
					{ 
						if(strpos($line,'?>') !== false)
							break;
						
						if(strpos($line,'#linkText:') !== false)
						{
							$viewParts = explode(':',$line,2);
							$pageLinkText = trim($viewParts[1]);
						}

						if($j > 12) break;
						$j++;
					}
				}

				if(!empty($parts[$i]))
				{
					if(isset($pageLinkText))
						$linkText = $pageLinkText;
					else
						$linkText = self::ucwordss(str_replace('-',' ',$parts[$i]), ["is", "to", "the", "for"]);

					$str .= ' '.$sep.' <a href="'.$url.'">'.$linkText.'</a>';
				}

				unset($linkText, $pageLinkText);
			}
				
		}
		
		return '<a href="/">Home</a>'.$str;
	}
	
	/**
	 * Build Navigation
	 *
	 * @param json string $propsStr {"ul_class":"className", "ul_id":"ulId", "select_class":"sel", "wrapper":"none|ul"}
	 * @return string
	 * @author Daniel Baldwin
	 */
	public static function nav($propsStr = null)
	{
		if($propsStr != null)
			$props = json_decode($propsStr, true);
		
		if(!isset($props['wrapper'])) 
			$props['wrapper'] = 'ul';
		
		$html = '';
		
		$viewsPath = rtrim($GLOBALS['controller'], 'controller.php').'views';
		
		# get list of files in view dir
		$list = array_diff(scandir($viewsPath), array('..', '.', '_parts'));

		# sort files if needed
		if(isset($props['sort']))
		{
			$listSorted = explode(',', $props['sort']);
			array_walk($listSorted, function(&$value) { $value .= '.html'; });
			$list = $listSorted;
		}
		
		# check if wrapper property is set
		if($props['wrapper'] == 'ul')
		{
			$html = '<ul';
			if(isset($props['ul_class'])) $html .= ' class="'.$props['ul_class'].'"';
			if(isset($props['ul_id'])) $html .= ' id="'.$props['ul_id'].'"';
			$html .= '>';
		}
				
		foreach($list as $file)
		{
			if(strpos($file, '.html'))
			{
				# remove .html from file name to get path
				$path = str_replace('.html', '', $file);
				
				# read the file into lines
				if(file_exists($viewsPath.'/'.$file))
					$lines = file($viewsPath.'/'.$file);
										
				$j = 0;
				# loop and get meta data needed
				if(isset($lines))
				foreach($lines as $line)
				{
					if(strpos($line,'#not_in_nav:') !== false)
					{
						$meta['not_in_nav'] = true;
					}
					
					if(strpos($line,'#link_text:') !== false)
					{
						$viewParts = explode(':',$line,2);
						$meta['link_text'] = trim($viewParts[1]);
					}
					
					if($j > 9) break;
					$j++;
				}
				
				# build link is not in nav is not set
				if(!isset($meta['not_in_nav'])) 
				{
					if($props['wrapper'] == 'ul')
						$html .= '<li>';
					
					$html .= '<a href="/'.$path.'"';
					
					if(isset($props['select_class']))
					{
						if($_SERVER["REQUEST_URI"] == '/'.$path)
							$html .= ' class="'.$props['select_class'].'"';
					}
					
					if(isset($meta['link_text']))
						$html .= '>'.$meta['link_text'].'</a>';	
					else
						$html .= '>'.ucwords($path).'</a>';
					
					if($props['wrapper'] == 'ul')
						$html .= '</li>';
				}	
			} 
			unset($meta, $lines);
		}
		if($props['wrapper'] == 'ul')
		{
			$html .= '</ul>';
		}
		
		return $html;
	}
	
	# $additionalString should start with an '&' like &key=value
	/**
	 * A pagination method for displaying page numbers for a list of records.
	 *
	 * @param int $rowCount, total records available
	 * @param int $perPage, how many records per page
	 * @param int $pageRange, number of pages to display at a time
	 * @param int $rangeLimit, not used anymore
	 * @param int $page, the current page to be displayed
	 * @param bool $return, true|false if to return the html or echo it
	 * @param string $additionalString, additional text on end of query string of page links.
	 * @return string HTML with links to all the page numbers needed.
	 * @author Dan Baldwin
	 */
	public function pageNumbers($rowCount, $perPage, $pageRange, $rangeLimit, $page, $return=false, $additionalString='')
	{
		if($page == null OR $page == 0) $page = 1;
		if(!$perPage) $perPage = 100;
		if($page == 1) $pageRange = $pageRange + 2;
		$totalPages = ceil($rowCount/$perPage);
		$rangeLimit = ceil($pageRange/2);

		$output .= '
		<div class="Pages">
			<div class="Paginator"> <b>Pages</b>
			';

		if($page > $rangeLimit)
			$output .= "<a href='$PHP_SELF?page=1".$additionalString."' class='Prev'>&lt; First</a>
			<span class='break'>...</span>
			"; 
			
			

		for($pageCounter=1; $pageCounter <= $totalPages; $pageCounter++)
		{
			if($pageCounter > $page - $rangeLimit AND $pageCounter < $page + $rangeLimit)
			{
				if($page == $pageCounter)
					$output .= "<span class='this-page'>$pageCounter</span>
						";
				else
				{
					if($totalPages != 1)
						$output .= "<a href='$PHP_SELF?page=$pageCounter".$additionalString."'>$pageCounter</a>
						";
				}
			}
		}

		if($totalPages > $page + $pageRange)
			$output .= "<span class='break'>...</span>
			"; 

		if($page < $totalPages - $rangeLimit AND $totalPages > $rangeLimit)
			$output .= "<a href='$PHP_SELF?page=$totalPages".$additionalString."' class='Next'>Last &gt;</a>
			"; 
		//status=$status&amp; <- removed 2007-03-26

		$output .= "
			</div>
		</div>
		";
		if($rowCount>1)
		{
			if($return) return $output;
			else echo $output;
		}
	}
	
	/**
	 * sideSelect - used by the sidebar.tpl in an <a> tag so the link will be highlighted when the user is on that page.
	 * Example: <a href=""<?=$TA->sideSelect('articles')?>>Link</a>
	 *
	 * @param string $item should match the string in the $subpg variable on the page.
	 * @return string class to apply selected style.
	 * @author Dan Baldwin
	 */
	static function sideSelect($item)
	{
		return ($GLOBALS['subpg']==$item? ' class="sideCurr"':'');
	}
	
	/**
	 * sidebarItem - 
	 *
	 * @param string $title Title of link
	 * @param string $url Link
	 * @param string $subPage Subpage tag
	 * @param string $img Image for link located in the local images folder for the module
	 * @param string $permission Permission tag if it needs to have restricted access.
	 * @return void Echos out the results.
	 * @author Dan Baldwin
	 */
	public static function sidebarItem($title, $url, $img, $subPage=null, $permission=null)
	{
		if(!strstr($img,'/')) $img = 'views/images/'.$img;
		if($permission)
		{
			if($this->getPermission($permission)) {
				echo '<a href="'.$url.'"'.$this->sideSelect($subPage).'><img src="'.$img.'" width="16" height="16"/> '.$title.'</a>';
			}
		}
		else echo '<a href="'.$url.'"'.$this->sideSelect($subPage).'><img src="'.$img.'" width="16" height="16"/> '.$title.'</a>';
	}

	/**
	 * add section action to menu
	 *
	 * @param array $args ['title'=>'', 'icon'=>'just the file name', 'onclick'=>'']
	 * @return void
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public function addSectionAction($values)
	{
		$this->vars['section_action'][] = (object) $values;
	}

	public function getSectionActions()
	{
		global $TAConfig;
		
		if(is_object($this->vars['section_action'][0]))
		foreach($this->vars['section_action'] as $action)
		{
			$str .= '<li'.(isset($action->onclick)? ' onclick="'.$action->onclick.'"':'').'><a href="javascript:;">'.(isset($action->icon)? '<img src="/modules/'.$TAConfig->controlling_module.'/assets/images/'.$action->icon.'">':'').$action->title.'</a></li>';
		}
		return $str;
	}

	public function hasActions()
	{
		#print_r($this->vars['section_action']);
		if(is_object($this->vars['section_action'][0]))
			return true;
		else
			return false;
	}
	
	/**
	 * version - Check to see if the version required is available. The var $str should contain needed version. Will return true or false if sent a version else it will return current version.
	 *
	 * @param string $str 
	 * @return string|bool version number of TrueAdmin if you don't send it a version or true|false if the version sent is >= to TrueAdmin version.
	 * @author Dan Baldwin
	 */
	public function version($str=null)
	{
		if($str)
		{
			if($str >= self::$version) return true;
			else return false;
		}
		else return self::$version;
	}

	/**
	 * uppercase words with ignore list
	 *
	 * @param string - words you want changed
	 * @param array - list of words you want ignored
	 * @return void
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	private static function ucwordss($str, $exceptions)
	{
		$out = "";
		foreach (explode(" ", $str) as $word) {
			$out .= (!in_array($word, $exceptions)) ? strtoupper($word{0}) . substr($word, 1) . " " : $word . " ";
		}
		return rtrim($out);
	}
}
?>