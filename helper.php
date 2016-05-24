<?php
/* @package Joomla
 * @copyright Copyright (C) Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @extension Phoca Extension
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

defined('_JEXEC') or die;


if (! class_exists('PhocaGalleryLoader')) {
	 include( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_phocagallery'.DS.'libraries'.DS.'loader.php');
}
$ph_router = JPATH_ADMINISTRATOR.'/components/com_phocaphoto/helpers/route.php';
if (! class_exists('PhocaPhotoRoute') && file_exists($ph_router)) {
	 include( $ph_router);
}


phocagalleryimport('phocagallery.path.path');
phocagalleryimport('phocagallery.path.route');
phocagalleryimport('phocagallery.file.file');
phocagalleryimport('phocagallery.file.filethumbnail');
phocagalleryimport('phocagallery.text.text');
phocagalleryimport('phocagallery.ordering.ordering');

class modBXSlidAryHelper {
	function __construct($params) {
		$this->params = $params;
		$s['params']	= $this->paramGet( 'slideshow_params' );
		if (is_object($s['params'])) { $s['params'] = (array)$s['params']; }
		if (is_string($s['params'] )) {
			$temp = array_map('trim',explode (',',$s['params']));
			$s['params'] = array();
			foreach ($temp as $k=>$v) {
				$v = array_map('trim',explode (':',$v));
				if (empty($v[0]) || empty($v[1]) ) {
					continue;
				}
				if (is_numeric($v[1])) {
					$v[1] = (int) $v[1];
				}
				$s['params'][$v[0]] = $v[1];
			}
		}
		$this->pagerIsSet = false;
		if (isset($s['params']['pager']) && $s['params']['pager'] != 'false' && 	$s['params']['pager'] != '0' ) {
			$this->pagerIsSet = true;
		}
		$this->maxSlidesIsSet = false;
		if (isset($s['params']['maxSlides']) && $s['params']['maxSlides'] != 'false' && 	$s['params']['maxSlides'] != '0' ) {
			$this->maxSlidesIsSet = true;
		}
		//~ if ($this->pagerIsSet && $this->maxSlidesIsSet) {
			//~ JFactory::getApplication()->enqueueMessage(get_class($this).': '.JText::_('MOD_BXSLIDARY_CANNOT_USE_PAGER_AND_MAXSLIDE'), 'error');
		//~ }
		if ($this->pagerIsSet) {
			$s['params']['moveSlides'] = 1;
		}
		$this->params->set( 'slideshow_params',$s['params'] );

	}

	function getItemsFromDB ($options) {
		$db 		= JFactory::getDBO();
		$document	= JFactory::getDocument();
		$catidSQL = '';

		$user = JFactory::getUser();
		$accessLevels = JAccess::getAuthorisedViewLevels($user->id);

		$query = $db->getQuery(true);
		$query->select(array('a.id', 'a.alias', 'a.title', 'a.description', 'a.filename', 'a.extl', 'a.extlink1', 'a.extlink2', 'cc.id as categoryid', 'cc.alias as categoryalias'));
		$query->from('#__phocagallery AS a');
		$query->leftJoin('#__phocagallery_categories AS cc ON a.catid = cc.id');
		$query->where($db->qn('a.published') .'= 1');
		$query->where($db->qn('cc.published') .'= 1');
		$query->where($db->qn('a.approved') .'= 1');
		$query->where($db->qn('cc.approved') .'= 1');
		$query->where($db->qn('cc.access') .' IN (' . implode(',',$accessLevels).' )' );
		$query->where( '
				(
					(cc.accessuserid != 0 AND (
							cc.accessuserid = '.$user->id.'
								OR
								cc.accessuserid LIKE \''.$user->id.',%\'
								OR
								cc.accessuserid LIKE \'%,'.$user->id.'\'
								OR
							cc.accessuserid LIKE \'%,'.$user->id.',%\'
						)
				  )
					OR  cc.accessuserid = 0
				)
			');
		if (!empty($options['category_id']) && !in_array('all',$options['category_id'])) {
			$query->where($db->qn('a.catid') .' IN (' . implode(',',$options['category_id']).' ) ' );
		}
		if (!empty($options['exclude_ids'])) {
			$query->where($db->qn('a.id') .' NOT IN (' . implode(',',$options['exclude_ids']).' ) ' );
		}
		if ($options['image_ordering'] == 9) {
			$imageOrdering = ' ORDER BY RAND()';
		} else {
			$iOA = PhocaGalleryOrdering::getOrderingString($options['image_ordering']);
			$imageOrdering = $iOA['output'];
		}
		$imageOrdering = str_replace('ORDER BY ',' ',$imageOrdering);
		$query->order($imageOrdering);
		$db->setQuery($query,0,$options['count_images']);
		$items =  $db->loadObjectList('id');

		if (count($items) < $options['count_images'] && !empty($options['exclude_ids'])){
			$options['exclude_ids'] = array();
			$result = $this->getItemsFromDB($options);
			$result['flushExcludeIds'] = true;
			return  $result;
		}
		return  array('items'=>$items,'flushExcludeIds'=>false);
	}
	function prepareCSSandJS ($exclude_ids) {

		$load_bxslider_css			= $this->paramGet( 'load_bxslider_css' );


		JHTML::stylesheet('modules/mod_bxslidary/css/style.css' );
		//$document->addScript(JURI::base(true).'/components/com_phocagallery/assets/jquery/jquery-1.6.4.min.js');
		JHtml::_('jquery.framework', false);
		JHtml::_('jquery.ui');

		$module_id			= $this->paramGet( 'module_id' );
		$s['params'] 					= $this->paramGet( 'slideshow_params' );

		if ($this->pagerIsSet) {
			$s['params']['pagerCustom'] = '.moduleid_'.$module_id.' .pgbx-bx-pager';
		}
		$num = 1;
		if (!empty($s['params']['moveSlides']) ) {
			$num = $s['params']['moveSlides'];
		} else if (!empty($s['params']['maxSlides'])) {
			$num = $s['params']['maxSlides'];
		}

		$document = JFactory::getDocument();
		$path_to_assets = JURI::base().str_replace(JPATH_SITE,'',dirname(__FILE__));

		$ajax_buttons = (array)$this->paramGet('ajax_buttons');

		$js = '';
		if (!in_array('disabled',$ajax_buttons)){
			$document->addScript($path_to_assets.'/js/ajax.js?h='.md5_file(dirname(__FILE__).'/js/ajax.js')); // ?h='.md5(dirname(__FILE__).'/js/ajax.js') makes sure that the JS is reloaded. After a plugin update Joomla may use browser cached JS or CSS.

			$this->params->set('count_images',1);
			$this->params->set('exclude_ids',$exclude_ids);
			$json_code = json_encode($this->params);

			$options = '{
				sliderParams: '.json_encode($s['params']).',
				moduleParams: '.$json_code.',
				moduleId: '.$module_id.',
			}';

			$js .= '
				jQuery(document).ready(function($){
					new pgBXJQModule($).init('.$options.');
				});
				'.PHP_EOL;

			$js .= ' var MOD_BXSLIDARY_ALL_IMAGES_LOADED = "'.JText::_('MOD_BXSLIDARY_ALL_IMAGES_LOADED').'";'.PHP_EOL;
			$js .= ' var MOD_BXSLIDARY_RELOAD_FROM_THE_BEGINNING = "'.JText::_('MOD_BXSLIDARY_RELOAD_FROM_THE_BEGINNING').'";'.PHP_EOL;
		}
		else { // If no ajax needed, then don't load the full js file, but inject only a small snippet needed to handle captions
			if (!empty($s['params']['captions']) && $s['params']['captions'] == 'true') {
				$s['params']['onSliderLoad'] = ' function() {
					$(".moduleid_'.$module_id.' .pgbx-bxslider .bx-caption span").each(function()  {
						el = $(this).parent();
						var height;
						el.css(\'height\',\'auto\');
						height = el.height();
						el.css(\'height\',\'\');

						el.mouseover(function() {
								 $(this).stop(true, false).animate({ height: height },500);
							}).mouseout(function() {
								 $(this).stop(true, false).animate({ height: \'\' },250);
						});
					});
				}
				';

			}
			$optobj = array();
			foreach ($s['params'] as $k=>$v) {
				$line = $k.':';
				if (is_numeric($v) || $k == 'onSliderLoad') {
					$line .= $v;
				} else {
					$line .= '"'.$v.'"';
				}
				$optobj[] = $line;
			}
			$optobj = '{'.implode(','.PHP_EOL,$optobj).'}';
			$js .= '
				jQuery(document).ready(function($){
					$(".moduleid_'.$module_id.' .pgbx-bxslider").show().bxSlider('.$optobj.');
				});
				'.PHP_EOL;
		}


		$document->addScriptDeclaration($js);


		//~ $use_joomla_tooltip	= $this->paramGet( 'use_joomla_tooltip' );
		//~ if ($use_joomla_tooltip) {
			//~ //JHtml::_('bootstrap.tooltip');
			//~ JHtml::_('bootstrap.popover');
		//~ }
		 //~ JHtml::_('behavior.tooltip'); / Just for tests. Latest bx slide kills the web-site when mootools is loaded.

		// Load older slider if mootools if found in the head section
		$scripts = $document->getHeadData();
		$scripts = implode(',',array_keys($scripts['scripts']));
		if (strpos($scripts,'media/system/js/mootools-core.js') === false ) {
			$subpath = $path_to_assets .'/js/bxslider';
			$document->addScript($subpath.'/vendor/jquery.easing.1.3.js');
			$document->addScript($subpath.'/vendor/jquery.fitvids.js');
			$document->addScript($subpath.'/jquery.bxslider.js');
			if ($load_bxslider_css == 1) {
				JHTML::stylesheet($subpath.'/jquery.bxslider.css' );
			}
		}
		else {
			$subpath = $path_to_assets .'/js/bxslider4.1.1';
			$document->addScript($subpath.'/plugins/jquery.easing.1.3.js');
			$document->addScript($subpath.'/plugins/jquery.fitvids.js');
			$document->addScript($subpath.'/jquery.bxslider.js');
			if ($load_bxslider_css == 1) {
				JHTML::stylesheet($subpath.'/jquery.bxslider.css' );
			}
		}
	}
	public static function getAjax() {
		$input  = JFactory::getApplication()->input;
		// Prepare standard parameters stdClass to a joomla parameter obect JParameter {
		$parameter = $input->get('data',null,'raw');
//~ dump ($parameter,'$parameter');
		$params = new JRegistry;
		$params->loadArray($parameter);
//~ dump ($parameter,'$parameter');
//~ dump ($params,'$params');
		// Load items from DB
		$helper = new modBXSlidAryHelper($params);
		$helper->params = $params;
		$result = $helper->getItems();
//~ dump ($result,'$result');
		$items = $result['items'];

		// Add each item an attribute containg a prepared HTML line with image, title and so on
		$index = 0;
		foreach ($items as $k=>&$v) {
			require JModuleHelper::getLayoutPath('mod_bxslidary', $params->get('layout', 'default').'_item');
			$v->output_slider = implode(PHP_EOL,$item_html);
			if ($helper->pagerIsSet) {
				require JModuleHelper::getLayoutPath('mod_bxslidary', $params->get('layout', 'default').'_pager');
				$v->output_pager = implode(PHP_EOL,$pager_html);
				$index++;
			}
		}

		$ret = array();
		$ret['items'] = $items;
		$ret['end_reached'] = false;
		if ($result['flushExcludeIds']) {
			$ret['exclude_ids'] = array_keys($items);
			$ret['end_reached'] = true;
		} else {
			$ret['exclude_ids'] = array_merge(array_map('intval',$params->get('exclude_ids')),array_keys($items));
			$ret['exclude_ids'] = array_unique($ret['exclude_ids']);
		}
		return $ret;
	}

	function paramGet($name,$default=null) {
		$hash = get_class();
		$session = JFactory::getSession();
		$params = $session->get('DefaultParams',false,$hash); // Get cached parameteres
		if (empty($params) || empty($params[$name])) {
			$xmlfile = dirname(__FILE__).'/mod_bxslidary.xml';
			$xml = simplexml_load_file($xmlfile);
			//unset ($xml->scriptfile);
			$field = 'field';
			$xpath = 'config/fields/fieldset';

			foreach ($xml->xpath('//'.$xpath.'/'.$field) as $f) {
				if (isset($f['default']) ) {
					if (preg_match('~[0-9]+,[0-9]*~',(string)$f['default'])) {
						$params[(string)$f['name']] = explode (',',(string)$f['default']);
					}
					else {
						$params[(string)$f['name']] = (string)$f['default'];
					}
				}
			}
			$session->set('DefaultParams',$params,$hash);
		}
		if (!isset ($params[$name])) {
			$params[$name] = $default;
		}
		return $this->params->get($name,$params[$name]);
	}

	private function addTrimmingDots($text) {
		// Add ... to the end of the article if the last character is a letter or a number.
		if ($this->paramGet ('add_trimming_dots') == 2) {
			if (preg_match('/\w/ui', JString::substr($text, -1))) { $text = trim($text) . $this->trimming_dots; }
		}
		else {
			$text = trim($text) . $this->trimming_dots;
		}
		return $text;
	}


	private function _trimText ($text) {
		$limittype = $this->paramGet('limittype');

		if ($limittype == -1) {
			if ($this->paramGet('Strip_Formatting') == 1) {
				$text = strip_tags($text);
			}
			return $text;
		}

		$this->trimming_dots = '';
		if ($this->paramGet ('add_trimming_dots') != 0) {
			$this->trimming_dots = $this->paramGet ('trimming_dots');
		}

		$maxLimit = $this->paramGet('maxLimit');

		if ($limittype == 0) {// Limit by chars
			if (JString::strlen(strip_tags($text)) > $maxLimit) {
				if ($this->paramGet('Strip_Formatting') == 1) {
					// First, remove all new lines
					$text = preg_replace("/\r\n|\r|\n/", "", $text);
					// Next, replace <br /> tags with \n
					$text = preg_replace("/<BR[^>]*>/i", "\n", $text);
					// Replace <p> tags with \n\n
					$text = preg_replace("/<P[^>]*>/i", "\n\n", $text);
					// Strip all tags
					$text = strip_tags($text);
					// Truncate
					$text = JString::substr($text, 0, $maxLimit);
					//$text = String::truncate($text, $maxLimit, '...', true);
					// Pop off the last word in case it got cut in the middle
					$text = preg_replace("/[.,!?:;]? [^ ]*$/", "", $text);
					// Add ... to the end of the article.
					$text = trim($text) . $this->trimming_dots ;
					// Replace \n with <br />
					$text = str_replace("\n", "<br />", $text);
				} else {
					// Truncate
					//$text = JString::substr($text, 0, $maxLimit);
					if (!class_exists('AutoReadMoreString')) { require_once (dirname(__FILE__).'/helpers/AutoReadMoreString.php'); }

					$text = AutoReadMoreString::truncate($text, $maxLimit, '&hellip;', true);

					// Pop off the last word in case it got cut in the middle
					$text = preg_replace("/[.,!?:;]? [^ ]*$/", "", $text);
					// Pop off the last tag, if it got cut in the middle.
					$text = preg_replace('/<[^>]*$/', '', $text);

					$text = $this->addTrimmingDots($text);
					// Use Tidy to repair any bad XHTML (unclosed tags etc)
					$text = AutoReadMoreString::cleanUpHTML($text);

				}
			}
		}
		else 		if ($limittype == 1) {//Limit by words

			if (!class_exists('AutoReadMoreString')) { require_once (dirname(__FILE__).'/helpers/AutoReadMoreString.php'); }
			$text = AutoReadMoreString::truncateByWords($text,$maxLimit);

			$text = $this->addTrimmingDots($text);
			$text = AutoReadMoreString::cleanUpHTML($text);

		}
		else if ($limittype == 2) {// Limit by paragraphs
			$paragraphs = explode ('</p>',$text);
			if(count($paragraphs)<=$maxLimit+1) {
				// do nothing, as we have $maxLimit paragraphs
			}
			else {
				$text = array();
				for ($i = 0; $i <$maxLimit ; $i++) {
					$text[] = $paragraphs[$i];
				}
				unset ($paragraphs);
				$text = implode('</p>',$text);
			}
		}
		if ($this->paramGet('Strip_Formatting') == 1) {
			$text = strip_tags($text);
		}
		return $text;
	}
	function getItems ($options = null) {
		if (empty($options)) {
			$options = array();
			$options['category_id'] = $this->paramGet( 'category_id' );
			$options['count_images'] = $this->paramGet( 'count_images' );
			$options['image_ordering'] = $this->paramGet( 'image_ordering' );
			$options['exclude_ids'] = $this->paramGet( 'exclude_ids',null );
		}
		$result = $this->getItemsFromDB($options);
//~ dump ($result,'$result');
		$items = $result['items'];


		if (count($items) <1 ) { return $result; }
		$url_link 						= $this->paramGet( 'url_link' );
		$single_link	 				= $this->paramGet( 'single_link' );
		$target							= $this->paramGet( 'target' );
		//~ $use_joomla_tooltip			= $this->paramGet( 'use_joomla_tooltip' );
		$si			= $this->paramGet( 'slideshow_image_source' );
		$pi			= $this->paramGet( 'thumbnails_image_source' );

		$trimming_dots = '';
		if ($this->paramGet ('add_trimming_dots') != 0) {
			$trimming_dots = $this->paramGet ('trimming_dots');
		}
//~ dump ($items,'$items111');
		foreach ($items as $k => &$v) {
			$v->description_trimmed = $this->_trimText($v->description);

			$v->urlLink 	= '';
			$v->target 	= '';
			if (!class_exists('PhocaPhotoRoute') && $url_link == 4)  { $url_link = 3; }
			if (!class_exists('PhocaPhotoRoute') && $url_link == 6)  { $url_link = 5; }
			switch ($url_link) {
				case 0:
					break;
				case 1:
					if (isset($v->extlink1)) {
						$v->extlink1	= explode("|", $v->extlink1, 4);
						if (isset($v->extlink1[0]) && $v->extlink1[0] != '' && isset($v->extlink1[1])) {
							$v->urlLink = 'http://'.$v->extlink1[0];
							if (!isset($v->extlink1[2])) {
								$v->target = '_self';
							} else {
								$v->target = $v->extlink1[2];
							}
						}
					}
					break;
				case 2:
					if (isset($v->extlink2)) {
						$v->extlink2	= explode("|", $v->extlink2, 4);
						if (isset($v->extlink2[0]) && $v->extlink2[0] != '' && isset($v->extlink2[1])) {
							$v->urlLink =  'http://'.$v->extlink2[0];
							if (!isset($v->extlink2[2])) {
								$v->target = '_self';
							} else {
								$v->target = $v->extlink2[2];
							}
						}
					}
					break;

				case 3:
					$v->urlLink =  PhocaGalleryRoute::getCategoryRoute($v->categoryid, $v->categoryalias);
					break;

				case 4:
					$v->urlLink =  PhocaPhotoRoute::getCategoryRoute($v->categoryid, $v->categoryalias);
					break;

				case 5:
					$v->urlLink =  PhocaGalleryRoute::getImageRoute($v->id, $v->categoryid, $v->alias, $v->categoryalias);
					break;

				case 6:
					$v->urlLink =  PhocaPhotoRoute::getImageRoute($v->id, $v->categoryid, $v->alias, $v->categoryalias);
					break;

				default :
					if ($single_link != '') {
						$v->urlLink 	= 'http://'.$single_link;
						$v->target		= '_self';
					}
					break;
			}

			//~ $tooltip  = '';
			$caption = '';
			if ($v->title != '') {
				$caption .= $v->title;
				//$tooltip .= $v->title.'::';
				if (trim ($v->description_trimmed) != '' && trim (strip_tags($v->description_trimmed)) != '') {
					if (strip_tags($v->description_trimmed) == $v->description_trimmed) { // If text has no tags
						$caption .= ' - '. $v->description_trimmed;
					}
					$caption .= $v->description_trimmed;
					//~ $tooltip .= $v->description_trimmed;
				}
			} else if ($v->description_trimmed != '') {
				$caption .= $v->description_trimmed;
				//~ $tooltip .= '::'.$v->description_trimmed;
				//~ $tooltip .= $v->description_trimmed;
			}
			//~ if (!$use_joomla_tooltip)  {
				//~ $tooltip 		= '';
			//~ }
			$v->caption = htmlspecialchars($caption);
			//~ $v->tooltip = htmlspecialchars($tooltip);

			//~ if ($use_joomla_tooltip && !empty($v->title)) {
				//~ $v->class = 'class="hasTooltipBxSlider"';
			//~ }

			// Slideshow image
			if (isset($v->{'ext'.$si[0]}) &&  $v->{'ext'.$si[0]} != '') {
				$v->slideshow_image = PhocaGalleryText::strTrimAll($v->{'ext'.$si[0]});
			} else {
				$thumbLink	= PhocaGalleryFileThumbnail::getThumbnailName($v->filename, $si);
				$v->slideshow_image = JURI::base(true).'/'.$thumbLink->rel;
			}
			// Pager image
			if (isset($v->{'ext'.$pi[0]}) &&  $v->{'ext'.$pi[0]} != '') {
				$v->pager_image = PhocaGalleryText::strTrimAll($v->{'ext'.$pi[0]});
			} else {
				$thumbLink	= PhocaGalleryFileThumbnail::getThumbnailName($v->filename, $pi);
				$v->pager_image = JURI::base(true).'/'.$thumbLink->rel;
			}

		}
		$result ['items'] = $items;
		return $result;
	}


}
