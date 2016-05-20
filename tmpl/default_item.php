<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_articles_categories
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
$item_html = array();
		$item_html[] = '			<li>';
				$item_html[] = '				<a href="'.$v->urlLink.'" target="'.$v->target.'" >';
				$captionOutput = '';
				if (!empty ($v->caption)) {
					$captionOutput = 'title="'.$v->caption.'"';
					$captionOutput .= ' data-original-title="'.$v->caption.'"';
				}
				$item_html[] = '					<img src="'.$v->slideshow_image.'" alt="'.htmlspecialchars($v->title).'" '.$captionOutput.' />';
			$item_html[] = '				</a>';
		$item_html[] = '			</li>';
