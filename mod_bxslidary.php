<?php
/* @package Joomla
 * @copyright Copyright (C) Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @extension Phoca Extension
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

defined('_JEXEC') or die('Restricted access');


if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
if (!JComponentHelper::isEnabled('com_phocagallery', true)) {
	return JError::raiseError(JText::_('Phoca Gallery Error'), JText::_('Phoca Gallery is not installed on your system'));
}


// Include the syndicate functions only once
if (!class_exists('modBXSlidAryHelper')) { include __DIR__ . '/helper.php'; }

//~ $s['params'] = '
	 //~ slideWidth: 550,
//~ p1ager: true,
	 //~ maxSlides: 3,
	 //~ slideMargin: 10,
	 //~ captions: true,
	 //~ ';
//~ $params->set('slideshow_params',$s['params']);
$params->set('module_id',$module->id);
$helper = new modBXSlidAryHelper($params);
$result = $helper->getItems();
$items = $result['items'];
if (count($items) < 1)  { return; }
$params->set('global_count_images',count($items)); //Use for buttons later
$helper->prepareCSSandJS(array_keys($items));

//~ $base      = ModMenuHelper::getBase($params);
//~ $active    = ModMenuHelper::getActive($params);
//~ $active_id = $active->id;
//~ $path      = $base->tree;
//~ $showAll   = $params->get('showAllChildren');
$class_sfx = htmlspecialchars($params->get('class_sfx'));



require JModuleHelper::getLayoutPath('mod_bxslidary', $params->get('layout', 'default'));

if ($params->get('debug')) :
?>
<a class="button button-4 ajax_loader remove" >Remove images</a>
<a
data-loading-text="<i aria-hidden='true' class='fa fa-coffee fa-1'></i> Load images"
class="button button-4 ajax_loader load" >
<i aria-hidden='true' class='fa fa-image fa-1'></i>
Load images </a>
<a class="button button-4 ajax_loader append hasTooltip" title="aaaa">Append images </a>

<a class="button button-4 ajax_loader rebuild hasPopover" title="aaasa" data-content="dsfdsfdsgfd fds fdsf gfds" >Rebuild Slider </a>
<a class="button button-4 ajax_loader load_next_append " >Load next <?php echo count($items) ?> images</a>
<?php endif; ?>
