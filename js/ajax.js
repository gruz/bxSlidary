function pgBXJQModule ($) {
	var self = this;
	var options;
	var baseSelector;
 	//~ var removeItems;
	var items;
	var sliderReloadedCaller

	this.init = function (opts) {
		options = opts;
//~ console.log (options);
		baseSelector = '.moduleid_'+options.moduleId+' ';
		//~ if(options.moduleParams.use_joomla_tooltip == '1') {		}
		options.sliderParams.onSliderLoad = function() {
			self.makeCaptionsAnimation();
			self.unBlockElement(sliderReloadedCaller); // Cannot unblock the button till he slider is reloaded.
			if (typeof(sliderReloadedCaller) == 'undefined' || sliderReloadedCaller === null) { return; }
			sliderReloadedCaller.removeClass('fa-spin');
			sliderReloadedCaller = null;
			return true;
		};

		options.moduleParams.count_images = 1;
		if (typeof(options.sliderParams.moveSlides) != 'undefined' && options.sliderParams.moveSlides !=0  ) {
			options.moduleParams.count_images = options.sliderParams.moveSlides;
		} else {
			options.moduleParams.count_images = options.sliderParams.maxSlides;
		}

		if(jQuery.inArray('load_next_slides',options.moduleParams.ajax_buttons) !== -1) {

			/*
			 * Function to be loaded when last slide Next button is clicked
			 *
			 *
			 */
			options.moduleParams.nextload_count_images = options.moduleParams.count_images;
			var loadNextItems = function (e){
				e.stopPropagation();
				// Prepare some values
				var countOfSlides = $(baseSelector).find(' .bx-pager .bx-pager-item').length;
				options.moduleParams.count_images = options.moduleParams.nextload_count_images; // set to load all images number, as set up in the module settings
//~ console.log ('reload fired','count images'+options.moduleParams.count_images);

				self.blockElement(this);
				sliderReloadedCaller = $(this); // Prepare to block "next" button while reloading
				$.when( self.loadItems() ).done( function() {
//~ console.log ('loaded',items);
					self.removeItems(options.moduleParams.nextload_count_images);
					$.when( self.appendItems() ).done( function() {
						$(baseSelector+' .bxslider li img').each(function()  {
							var title = $(this).attr('data-original-title');
							$(this).attr('title',title);
						});
						options.sliderParams.startSlide = countOfSlides-2;
//~ console.log('loadNextItems: startSlide',options.sliderParams.startSlide);

						$.when( slider.reloadSlider(options.sliderParams)).done( function(w) {
//~ console.log ('Load finished',slider);
							//$('.moduleid_'+options.moduleId+' .bx-controls-direction .bx-next').unbind ('click.loadNextItems');
							//$('.moduleid_'+options.moduleId+' .bx-controls-direction .bx-next').click(loadNextItems);
							$('.moduleid_'+options.moduleId+' .bx-controls-direction').append('<i class="fa fa-rotate-right fa-special-css"></i>');
							$('.moduleid_'+options.moduleId+' .fa-special-css').click(loadNextItems);
//alert();
							setTimeout(
							  function()
							  {
								 slider.goToNextSlide();
							  }, 100);

							options.sliderParams.startSlide = 0;
						});
					});
				});
			}

			/*
			 * Before slide is loaded
			 * */
			options.sliderParams.onSlideBefore = function() {
				var countOfSlides = $(baseSelector).find(' .bx-pager .bx-pager-item').length;
//~ console.log ('countOfSlides',countOfSlides);
				var currentSlide = this.getCurrentSlide();
				if (countOfSlides-1 == currentSlide) { // If is last slide
					options.moduleParams.isLast = true;
					$('.moduleid_'+options.moduleId+' .bx-controls-direction').append('<i class="fa fa-rotate-right fa-special-css"></i>');
					$('.moduleid_'+options.moduleId+' .fa-special-css').click(loadNextItems);
					//$('.moduleid_'+options.moduleId+' .bx-controls-direction .bx-next').click(loadNextItems);
				}
				else { // If is NOT last slide
					options.moduleParams.isLast = false;
					$('.moduleid_'+options.moduleId+' .bx-controls-direction .fa-special-css').remove();
//~ console.log ('unbind in onSlideBefore');
					//$('.moduleid_'+options.moduleId+' .bx-controls-direction .bx-next').click(originalClick);
				}
				return true;
			}
		}

		var slider = $('.moduleid_'+options.moduleId+' .pgbx-bxslider').show().bxSlider(options.sliderParams);



		if (options.moduleParams.debug == 1) {
			$(baseSelector+ ' .ajax_loader.remove').click(function(e){
				self.blockElement(this);
				self.removeItems();
				self.unBlockElement(this);
		  });
			$(baseSelector+ ' .ajax_loader.load').click(function(e){
				self.loadItems($(this));
		  });
			$(baseSelector+ ' .ajax_loader.append').click(function(e){
				self.blockElement(this);
				self.appendItems();
				self.unBlockElement(this);
		  });
		}
		$(baseSelector+ ' .ajax_loader.rebuild').click(function(e){
			e.preventDefault();
			self.blockElement(this);

			$(baseSelector+' .bxslider li.bx-clone').remove();
			$(baseSelector+' .bxslider li img').each(function()  {
				var title = $(this).attr('data-original-title');
				$(this).attr('title',title);
			});
			var startSlide = slider.getCurrentSlide();
			options.sliderParams.startSlide = startSlide;
			if (options.sliderParams.startSlide<0) { options.sliderParams.startSlide = 0; }

			sliderReloadedCaller = $(this);
			slider.reloadSlider(options.sliderParams);

			slider.goToNextSlide();
			self.unBlockElement(this);

	  });

		// Button loads next images and reload slider
		$(baseSelector+ ' .ajax_loader.load_next_reload').click(function(e){
			self.blockElement(this);
			$(baseSelector+ ' .alert').hide().queue(function(){$(this).remove();});
//~ console.log ($(baseSelector+ ' .alert'));
			$(this).addClass('fa-spin');
//~ console.log (options.moduleParams.global_count_images);
			self.removeItems(options.moduleParams.global_count_images); // remove all old images
			options.moduleParams.count_images = options.moduleParams.global_count_images; // set to load all images number, as set up in the module settings
			//~ self.loadItems(); // loaded items are in 'items' var
			//~ self.appendItems();
			sliderReloadedCaller = $(this);
			$.when( self.loadItems() ).done( function() {
				$.when( self.appendItems() ).done( function() {
					slider.reloadSlider(options.sliderParams);
				});
			});
	  });

	return;

	}

	this.loadItems = function (btn)  {
//~ console.log ('loadItems exclude_ids = ',options.moduleParams.exclude_ids);
		var end_reached = false;
		var request = {
					'option' : 'com_ajax',
					'module' : 'bxslidary',
					'data'   : options.moduleParams,
					'format' : 'json'
				};
		return $.ajax({
			type   : 'POST',
			data   : request,
			async: true,
			success: function (response) {
				options.moduleParams.exclude_ids = response.data.exclude_ids;
				items = response.data.items;
				end_reached = response.data.end_reached;
//~ console.log ('response.data',response.data);
//~ console.log ('items',items);
			},
			error: function(response) {
				// alert('could not load images');
//~ console.log(response, 'response.responseText = ' +response.responseText);
				var data = '',
					obj = $.parseJSON(response.responseText);
				for(key in obj){
					data = data + ' ' + obj[key] + '<br/>';
				}
				$('.status').html(data);
			},
			beforeSend: function() {
				if (typeof(btn) == 'undefined') { return; }
				btn.addClass('fa-spin');
				self.blockElement(btn);
			},
			complete: function(response) {
				if (end_reached && options.moduleParams.show_all_images_loaded_message == 1) {
					if ($(baseSelector+ ' .alert').length < 1) {
						// Since the delay option there is not sense to keep the close button. It would make users click when it hides - miss clicke at images
						// $(baseSelector).append('<div class="alert alert-info fade in"><button data-dismiss="alert" class="close" type="button">×</button><h4>'+MOD_PHOCAGALLERY_SLIDESHOW_BXSLIDER_ALL_IMAGES_LOADED+'</h4>'+MOD_PHOCAGALLERY_SLIDESHOW_BXSLIDER_RELOAD_FROM_THE_BEGINNING+'</div>').find('.alert').delay(1000).hide(3000).queue(function(){$(this).remove();});
						$(baseSelector).append('<div class="alert alert-info fade in"><h4>'+MOD_BXSLIDARY_ALL_IMAGES_LOADED+'</h4>'+MOD_BXSLIDARY_RELOAD_FROM_THE_BEGINNING+'</div>').find('.alert').stop(true,true).delay(1000).hide(3000).queue(function(){$(this).remove();});
					}
				}
				if (typeof(btn) == 'undefined') { return; }
				btn.removeClass('fa-spin');
				self.unBlockElement(btn);
			}
		});
	}
	this.blockElement = function (el) {
		el = $(el);
		el.css('pointer-events','none');
		el.css('opacity','0.4');
		el.button('loading');

		return;
	}
	this.unBlockElement = function (el) {
		el = $(el);
		el.css('pointer-events','auto');
		el.css('opacity','1');
		el.button('reset');

		return;
	}
	this.removeItems = function (num)  {
		if(typeof(num) == 'undefined' || num == 0) {
			num = options.moduleParams.count_images;
		}
		if (options.moduleParams.ajax_remove_on_append == 1) {
			$(baseSelector+' .bxslider li').not('.bx-clone').slice(0,num).remove();
			$(baseSelector+' .bx-pager a').slice(0,num).remove();
		}
	}

	this.appendItems = function () {
//~ console.log ('appendItems',items);
		if (typeof(items) === 'undefined' ) { return; }
		var counter = 0;
		$.each(items, function (index, value) {
			var pager = $(value.output_pager);
			pager.attr('data-slide-index',counter);
			counter++;
//~ console.log (pager);
			$(baseSelector +' .bxslider').append(value.output_slider);
			$(baseSelector +' .bx-pager').append(pager);
		});
		return true;
	}

	this.makeCaptionsAnimation = function ()  {
			$(baseSelector+' .bx-caption span ').each(function()  {
				el = $(this).parent();
				var height;
				el.css('height','auto');
				height = el.height();
				el.css('height','');

				el.mouseover(function() {
						 $(this).stop(true, false).animate({ height: height },500);
					}).mouseout(function() {
						 $(this).stop(true, false).animate({ height: '' },250);
				});
			});
			$(baseSelector+' .bxslider li img').attr('title','');

	}


} // top func

