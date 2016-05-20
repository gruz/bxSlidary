<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_menu
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

echo '	<div class="pgbx-bxslider-module moduleid_'.$module->id.' clearfix'.$class_sfx.'">'.PHP_EOL;
echo '		<ul class="pgbx-bxslider bxslider">'. "\n";
	foreach ($items as $k => $v) {
		require JModuleHelper::getLayoutPath('mod_bxslidary', $params->get('layout', 'default').'_item');
		echo implode(PHP_EOL,$item_html);
	}
echo '		</ul>'.PHP_EOL;
if ($helper->pagerIsSet) {
	echo '		<div class="pgbx-bx-pager bx-pager">'. "\n";
		foreach ($items as $k => $v) {
			require JModuleHelper::getLayoutPath('mod_bxslidary', $params->get('layout', 'default').'_pager');
			echo implode(PHP_EOL,$pager_html);
		}
	echo '		</div>'.PHP_EOL;
}
?>
		<?php
//~ dump ($params->get('ajax_buttons'),'$params->get(ajax_buttons)');
		if (in_array('load_next_reload',(array)$params->get('ajax_buttons'))) :
//~ dump ($params->get('ajax_buttons'),'$params->get(ajax_buttons)222');
			$txt = JText::sprintf('MOD_BXSLIDARY_NEXT_X_IMAGES',count($items)); ?>
			<div class="button button-4 ajax_loader load_next_reload pull-right" data-loading-text="<i aria-hidden='true' class='fa fa-spinner fa-1'></i> <?php echo $txt; ?>" >
				<i aria-hidden='true' class='fa fa-image fa-1'></i>
				<?php echo $txt; ?>
			</div>
		<?php endif; ?>

	</div>

<?php /*
		$document = JFactory::getDocument();

		$document->addScript('http://pyatka.in.uac/modules/mod_phocagallery_slideshow_bxslider/javascript/vendor/jquery.easing.1.3.js');
		$document->addScript('http://pyatka.in.uac/modules/mod_phocagallery_slideshow_bxslider/javascript/vendor/jquery.fitvids.js');
		$document->addScript('http://pyatka.in.uac/modules/mod_phocagallery_slideshow_bxslider/javascript/jquery.bxslider.js');
		$document->addScriptDeclaration("
jQuery(document).ready(function($){
  $('.bxslider').bxSlider();
});
		");

?>

<ul class="bxslider">
  <li><img src="images/sampledata/parks/landscape/800px_cradle_mountain_seen_from_barn_bluff.jpg" /></li>
  <li><img src="images/sampledata/parks/landscape/800px_cradle_mountain_seen_from_barn_bluff.jpg" /></li>
  <li><img src="images/sampledata/parks/landscape/800px_cradle_mountain_seen_from_barn_bluff.jpg" /></li>
  <li><img src="images/sampledata/parks/landscape/800px_cradle_mountain_seen_from_barn_bluff.jpg" /></li>
  <li><img src="images/sampledata/parks/landscape/800px_cradle_mountain_seen_from_barn_bluff.jpg" /></li>

</ul> <?php
*/
