/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

/*
 * STARTUP SCRIPTS
 */

// Initialize on document ready.
jQuery(function() {
	var $ = jQuery;

	function setAudioSource(audioElement) {
		let url = '/wp-admin/admin-ajax.php?action=audio_bible_chapter';
		url += '&bible-id=' + encodeURIComponent(audioElement.dataset.bibleId);
		url += '&chapter=' + encodeURIComponent(audioElement.dataset.chapterId);
		$.ajax({
			dataType: "json",
			url: url,
			success: function(response, status, jqxhr) {
				if (response !== null && response !== '' && response.data !== null) {
					let data = response.data;
					let expirationString = new Date(data.expiresAt * 1000).toUTCString();
					let sourceElement = document.createElement('source');
					sourceElement.id = 'bible-audio-source';
					sourceElement.src = data.resourceUrl;
					sourceElement.type = 'audio/mpeg';
					sourceElement.dataset.expiration = data.expiresAt;
					sourceElement.dataset.expirationString = expirationString;
					audioElement.appendChild(sourceElement);
					audioElement.dataset.fumsToken = response.meta.fumsToken;
					console.log('Audio source element created.');
					enableMediaElementPlayer(audioElement);
				} else {
					console.log('Ajax request succeeded. jQuery status: "' + status + '", Url: "' + url + '"');
					console.log('Audio source element not created.')
				}
			},
			error: function(jqxhr, status, error) {
				console.log('Ajax request failed. jQuery status: "' + status +
					'", HTTP error: "' + error +
					'", Url: "' + url + '"');
				console.log('Audio source element not created.')
			}
		});
	}

	function enableMediaElementPlayer(audioElement) {
		$(audioElement).mediaelementplayer({startVolume: 0.8, success: function(mediaElement,domObject) {
				// GA event tracking
				mediaElement.addEventListener('play', function() {
					if($('#online-bible').size()) {
						var wrapper = $(domObject).parents('.version');
						var label = document.title;
						if(wrapper.data('book') && wrapper.data('chapter') && wrapper.data('translation')) {
							label = wrapper.data('book') + ' ' + wrapper.data('chapter') + ' ' + wrapper.data('translation');
						}
						try	{
							ga('send', 'event', 'Audio - Bible', 'Audio - play', label);
						} catch(err){}

						if (domObject.dataset.fumsToken) {
							fums('trackListen', domObject.dataset.fumsToken);
							console.log('fums("trackListen", "' + domObject.dataset.fumsToken + '")');
							domObject.removeAttribute('data-fums-token');
						}
					}

					if($('.m-dailymanna-full').size()) {
						try	{
							ga('send', 'event', 'Audio - Daily Manna', 'Audio - play');
						} catch(err){}
					}

				}, false);
			}
		});
	}

	// Setup Bible footnote icons and popups
	$('.f').each(function() {
		var $footnote = $(this);
		var $placeholder = $('<span class="footnote-icon"></span>');
		$footnote.replaceWith($placeholder);

		var tooltip = new jBox('Tooltip', {
			attach: $placeholder,
			trigger: 'click',
			content: $footnote,
            closeOnClick: 'body'
		});
	});

	// Activate toggle links
	$('.toggle-link').each(function() {
		var t = $(this);
		var targetHash = '#'+t.attr('href').split('#')[1];
		var toggleTarget = $(targetHash);
		if(!toggleTarget.hasClass('toggled') && document.location.hash !== targetHash) {
			toggleTarget.hide();
		} else {
			t.addClass('toggled');
		}
		t.on('click touchstart', function(e) {
			e.stopPropagation();
			e.preventDefault();
			var t = $(this);
			var target = $(targetHash);
			target.slideToggle(100).toggleClass('toggled');
			t.toggleClass('toggled');
			audioElements = target.find('audio.bible-audio-player');
			if(t.hasClass('toggled') && audioElements.size()) {
				audioElements.each(function() {
					if ($(this).find('source').size() < 1) {
						setAudioSource(this);
					} else {
						enableMediaElementPlayer(this);
					}
				});
			}
		});
	});

	// TODO: keep?
	if (location.hash === '#listen') {
		$('.listen .toggle-link').trigger('click');
	}

	// TODO: keep?
	// Activate auto submit form widgets
	$('.auto-submit').change(function() {
		$(this).parents('form').submit();
	});

	$('div.select-link select').change(function() {
		window.location = $(this).val();
	});

	$('.bible-widget-form form.read-form').each(function() {
		var $readForm = $(this);
		var $refreshContainer = $readForm.find('.auto-refresh');

		$readForm.on('submit', function(event) {
            event.preventDefault();
			let newLocation = '';
			let urlType = $readForm.data('url-type');
			if (urlType === 'seo') {
				newLocation = $readForm.data('base-url') +
					$readForm.find('#widget-read-translation option:selected').data('abbreviation').toLowerCase() + '/' +
					$readForm.find('#book').val() + '/' +
					$readForm.find('#chapter').val() + '/';
			} else {
				newLocation = $readForm.data('base-url') +
					'?translationid=' + $readForm.find('#widget-read-translation').val() +
					'&book=' + $readForm.find('#book').val() +
					'&chapter=' + $readForm.find('#chapter').val();
			}
			window.location = newLocation;

			return false;
		});

		var refreshUrl = $refreshContainer.data('refresh-url');
		$refreshContainer.on('change', '.refresh-trigger', function () {
			$refreshContainer.addClass('refreshing');
			var data = $readForm.serialize();

			$refreshContainer.load(refreshUrl, data, function (responseText, textStatus, jqXHR) {
				$refreshContainer.removeClass('refreshing');
				let $selectedOption = $('#widget-read-translation option:selected');
				let newTranslationId = $selectedOption.val();
				let newAbbreviation = $selectedOption.data('abbreviation')
				$('#widget-search-translation').val(newTranslationId);
				$('#widget-search-keywords').prop('placeholder', 'Search the ' + newAbbreviation + ' Bible');
				$refreshContainer.find('select.form-control').selectric({
					nativeOnMobile: false
				});
			});
		});
	});

	$(function() {
		ResponsiveHelper.addRange({
			'..991': {
				on: function() {
					if ($('#online-bible').size()) {
						// Handle tabs in Online Bible
						$('.bible-reader .version-secondary').each(function() {
							var sec = $(this);
							var prim = $('.bible-reader .version-primary');

							sec.find('select.form-control').add(prim.find('select.form-control')).each(function() {
								var select = jQuery(this);

								if (select.data('selectric')) {
									select.data('selectric').destroy()
								}
							});

							var primtab = prim.find('.version-selector').clone();
							var sectab = sec.find('.version-selector').clone();
							var tabbar = $('<div class="header tab-header clearfix"><div class="tab-primary"></div><div class="tab-secondary"></div>');
							sec.hide();
							tabbar.find('.tab-primary').append(primtab);
							tabbar.find('.tab-secondary').append(sectab);
							$('.bible-reader .version .version-selector .btn-group-close').hide();

							primtab.find('.select').addClass('tab-active');

							$('.bible-reader').prepend(tabbar);

							primtab.find('.select').on('click.select', function() {
								if (!prim.is(':visible')) {
									sec.hide();
									prim.show();
									sectab.find('.select').removeClass('tab-active');
									jQuery(this).addClass('tab-active');

									setTimeout(() => {
										initClipText();
									}, 0)

									primtab.find('select.form-control').selectric('close');
								} else {
									if (primtab.find('.selectric-open').length) {
										primtab.find('select.form-control').selectric('open');
									}
								}
							});

							sectab.find('.select').on('click.select', function() {
								if (!sec.is(':visible')) {
									prim.hide();
									sec.show();
									primtab.find('.select').removeClass('tab-active');
									jQuery(this).addClass('tab-active');

									setTimeout(() => {
										initClipText();
									}, 0);

									sectab.find('select.form-control').selectric('close');
								} else {
									if (sectab.find('.selectric-open').length) {
										sectab.find('select.form-control').selectric('open');
									}
								}
							});

							sectab.find('select.form-control').add(primtab.find('select.form-control')).selectric({
								nativeOnMobile: false,
								stopPropagation: false
							});
						});
					}
				},
				off: function() {
					if ($('#online-bible').size()) {
						// Handle tabs in Online Bible
						$('.bible-reader .version-secondary').each(function() {
							var sec = $(this);
							var prim = $('.bible-reader .version-primary');
							var primtab = prim.find('.version-selector');
							var sectab = sec.find('.version-selector');

							$('.bible-reader .version').show();
							$('.bible-reader .version-selector .btn-group-close').show();
							$('.bible-reader .tab-header').remove();

							primtab.find('.select').off('click.select');
							sectab.find('.select').off('click.select');

							prim.find('select.form-control').add(sec.find('select.form-control')).selectric({
								nativeOnMobile: false,
								stopPropagation: false
							});
						});
					}
				},
			},
		});

		$('select.form-control').selectric({
			nativeOnMobile: false,
			stopPropagation: true,
			preventWindowScroll: false,
			onOpen: function() {
				$(".selectric-scroll").niceScroll({
					cursorwidth: "8px",
					cursorcolor: "#cccccc",
					zindex: 1,
					railpadding: {
				top: 5,
				right: 5,
				left: 0,
				bottom: 0
			  },
			  autohidemode: false,
				}).show();
				  $('.selectric-scroll').getNiceScroll().resize();
			},
		});
	});

	// TODO: keep?
	// Activate Bible reading navigation and tools
	$('#online-bible .version').each(function() {
		new biblica.onlineBibleVersion(this);
	});

	// TODO: keep?
	// Activate datepickers
	$('input.datepicker').each(function() {
		var t = $(this);
		if(t.hasClass('date-selector-datepicker')) {
			t.change(function() {
				$(this).closest('form').submit();
			});
		}
		t.datepicker({
			firstDay: 0,
			dateFormat: 'yy-mm-dd',
			dayNames: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
			dayNamesMin: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
			onUpdateDatepicker: function (obj) {
				jQuery(obj.input).closest('.btn-group').addClass('open');
			},
			onClose: function (_, obj) {
				jQuery(obj.input).closest('.btn-group').removeClass('open');
			},
			beforeShow: function (obj, ins) {
				var instHeight = 0;

				setTimeout(function () {
					instHeight = ins.dpDiv.height();
					var inputPos = $(window).height() - jQuery(obj).closest('.btn-group')[0].getBoundingClientRect().bottom;

					if (ins.dpDiv.hasClass('ui-top') || ins.dpDiv.hasClass('ui-bottom')) {
						return;
					}
					if (inputPos < instHeight) {
						ins.dpDiv
							.removeClass('ui-top')
							.addClass('ui-bottom')
							.position({
								my: 'left bottom',
								at: 'left top-50',
								collision: 'none',
								of: ".btn-group.open input[id='date']",
							})
					} else {
						ins.dpDiv
							.removeClass('ui-bottom')
							.addClass('ui-top')
							.position({
								my: 'left top',
								at: 'left bottom+5',
								collision: 'none',
								of: ".btn-group.open input[id='date']",
							})
					}
				}, 0);
				ins.dpDiv.css({
					transform: "translateY(15px)",
				})
			}
		});
	});
	$('.toggle-datepicker').click(function (e) {
		e.preventDefault();
		$(this).next().find('.datepicker').toggleClass("opened");
		if($(this).next().find('.datepicker').hasClass('opened')) {
			$(this).next().find('.datepicker').focus();
		} else {
			$("#ui-datepicker-div").removeClass('ui-top')
			$("#ui-datepicker-div").removeClass('ui-bottom')
			$(this).next().find('.datepicker').blur();
			$(this).blur();
		}
	});

	
	$(window).click(function (e) {
		if($(e.target).hasClass("toggle-datepicker")) {
			return
		} else {
			$('.datepicker.opened').removeClass('opened');
			$(".btn-group.open .toggle-datepicker").blur();
			$("#ui-datepicker-div").removeClass('ui-top')
			$("#ui-datepicker-div").removeClass('ui-bottom')
		}

	})

	$(window).resize(function () {
		if ($(".btn-group.open input[id='date']").length > 0) {
			if ($("#ui-datepicker-div").hasClass("ui-bottom")) {
				$("#ui-datepicker-div").position({
					my: 'left bottom',
					at: 'left top-50',
					collision: 'none',
					of: ".btn-group.open input[id='date']",
				});
			} else if ($("#ui-datepicker-div").hasClass("ui-top")) {
				$("#ui-datepicker-div").position({
					my: 'left top',
					at: 'left bottom+5',
					collision: 'none',
					of: ".btn-group.open input[id='date']",
				});
			} else {
				return;
			}
		} else {

		}
	});

	// inits
	initTooltip();
	initOpenClose();
	initTabs();
	initStickyScrollBlock();
	initClipText();
	initPaginationScroll();

	jQuery(function() {
		initSetHeight();
	});

	// hover tooltip init
	function initTooltip() {
		jQuery('.tools a, .tools button, .reviewsTools a').hoverTooltip({
			tooltipStructure: '<div class="hover-tooltip"><div class="tooltip-text"></div></div>',
		});
	}

	function initSetHeight() {
		var win = jQuery(window);

		jQuery('.bible-reader-compared').each(function() {
			var holder = jQuery(this);
			var items = holder.find('.version-selector');
			var maxHeight = 0;

			function resizeHandler() {
				items.removeAttr('style');

				items.each(function() {
					var item = jQuery(this);
					var itemHeight = item.outerHeight();

					if (itemHeight > maxHeight) {
						maxHeight = itemHeight;
					}
				});

				items.css({
					height: maxHeight
				});
			}

			resizeHandler();

			win.on('resize orientationchange', resizeHandler);
		});
	}

	// open-close init
	function initOpenClose() {
		jQuery('.btn-group:not(.date-selector)').openClose({
			activeClass: 'open',
			opener: '.dropdown-toggle',
			slider: '.dropdown-menu',
			animSpeed: 300,
			hideOnClickOutside: true,
			animStart: function (isOpen) {
				jQuery(this.opener).attr('aria-expanded', isOpen);
			},
		});
	}

	// content tabs init
	function initTabs() {
		jQuery('.nav-tabs').tabset({
			tabLinks: 'a',
			addToParent: true,
			defaultTab: true,
		});
	}

	// initialize fixed blocks on scroll
	function initStickyScrollBlock() {
		ResponsiveHelper.addRange({
			'768..': {
				on: function () {
					jQuery('.bible-api .version.col-md-12 .header .next').stickyScrollBlock({
						setBoxHeight: false,
						activeClass: 'fixed-position',
						container: '.version',
						positionType: 'fixed',
						extraTop: 150,
						extraBottom: 150,
					});
					jQuery('.bible-api .version.col-md-12 .header .prev').stickyScrollBlock({
						setBoxHeight: false,
						activeClass: 'fixed-position',
						container: '.version',
						positionType: 'fixed',
						extraTop: 150,
						extraBottom: 150,
					});
					jQuery('.reading-plans-header .next').stickyScrollBlock({
						setBoxHeight: false,
						activeClass: 'fixed-position',
						container: '.m-reading-plans-full',
						positionType: 'fixed',
						extraTop: 150,
						extraBottom: 150,
					});
					jQuery('.reading-plans-header .prev').stickyScrollBlock({
						setBoxHeight: false,
						activeClass: 'fixed-position',
						container: '.m-reading-plans-full',
						positionType: 'fixed',
						extraTop: 150,
						extraBottom: 150,
					});
				},
				off: function () {
					jQuery('.bible-api .version.col-md-12 .header .next').stickyScrollBlock('destroy');
					jQuery('.bible-api .version.col-md-12 .header .prev').stickyScrollBlock('destroy');
					jQuery('.reading-plans-header .next').stickyScrollBlock('destroy');
					jQuery('.reading-plans-header .prev').stickyScrollBlock('destroy');
				},
			},
		});
	}

	// clipping text with gradient
	function initClipText() {
		jQuery('.bible-reader .header h2, #daily-manna .controls h2').each(function () {
			const self = jQuery(this);

			clipText();

			jQuery(window).on('resize', clipText);

			function clipText() {
				if (self.get(0).scrollWidth > self.get(0).offsetWidth) {
					self.addClass('clip-text');
				} else {
					self.removeClass('clip-text');
				}
			}
		});

		jQuery('.dropdown-toggle').each(function () {
			const self = jQuery(this);

			clipText();

			jQuery(window).on('resize', clipText);

			function clipText() {
				if (self.find('.dropdown-toggle-text').width() + 72 > self.get(0).offsetWidth) {
					self.addClass('clip-text');
				} else {
					self.removeClass('clip-text');
				}
			}
		});

		jQuery('.select').each(function () {
			const self = jQuery(this);
			const select = self.find('.form-control');
			const html = `<div class="select-option-text sr-only"></div>`;

			self.append(html);
			clipText();

			jQuery(window).on('resize', clipText);
			select.on('change', clipText);

			select.on('click', function () {
				select.addClass('open');
			});

			select.on('blur', function () {
				select.removeClass('open');
			});

			jQuery(document).on('keyup', function (e) {
				if (e.keyCode == 27) {
					select.removeClass('open');
				}
			});

			function clipText() {
				if (select.length) {
					self.find('.select-option-text').css('width', select.get(0).offsetWidth).text(select.find('option:selected').text());
				}

				if (self.find('.select-option-text').get(0).scrollWidth > self.find('.select-option-text').get(0).offsetWidth) {
					self.addClass('clip-text');
				} else {
					self.removeClass('clip-text');
				}
			}
		});
	}

	// pagination scroll position
	function initPaginationScroll() {
		jQuery('.pagination').each(function () {
			const self = jQuery(this);
			const activeItem = self.find('li.active');

			setPos();

			jQuery(window).on('resize orientationChange', setPos);

			function setPos() {
				const parentOffsetLeft = self.offset().left;
				const activeItemOffsetLeft = activeItem.offset().left;

				if (activeItemOffsetLeft - parentOffsetLeft + activeItem.width() > self.width()) {
					self.scrollLeft(activeItemOffsetLeft - parentOffsetLeft - 1);
				}
			}
		});
	}
});

// TODO: keep?

// jQuery(window).bind('load resize orientationchange', function() {
// 	var $ = jQuery;
// 	// Check if any tables are wider than their parent.
// 	// If they are, wrap them in containers to allow for horizontal scrolling on small screens.
// 	// Requires CSS rules in global.css (.scroll-table, .scroll-table:after .scroll-table > .scroll-table-inner).
// 	$('table').each(function() {
// 		var table = $(this);
// 		if (table.outerWidth() > table.parent().outerWidth()) {
// 			console.log('Wide table: startup.js, line 333');
// 			alert('Wide table: startup.js, line 333');
// 			if (!table.hasClass('scrollable')) {
// 				table.wrap('<div class="scroll-table"><div class="scroll-table-inner"></div></div>').addClass('scrollable');
// 			}
// 		} else if (table.hasClass('scrollable')) {
// 			table.removeClass('scrollable').unwrap().unwrap();
// 		}
// 	});
//
// });

/*
 * jQuery Open/Close plugin
 */
;(function($) {
	function OpenClose(options) {
		this.options = $.extend({
			addClassBeforeAnimation: true,
			hideOnClickOutside: false,
			activeClass: 'active',
			opener: '.opener',
			slider: '.slide',
			animSpeed: 400,
			effect: 'fade',
			event: 'click'
		}, options);
		this.init();
	}
	OpenClose.prototype = {
		init: function() {
			if (this.options.holder) {
				this.findElements();
				this.attachEvents();
				this.makeCallback('onInit', this);
			}
		},
		findElements: function() {
			this.holder = $(this.options.holder);
			this.opener = this.holder.find(this.options.opener);
			this.slider = this.holder.find(this.options.slider);
		},
		attachEvents: function() {
			// add handler
			var self = this;
			this.eventHandler = function(e) {
				e.preventDefault();
				if (self.slider.hasClass(slideHiddenClass)) {
					self.showSlide();
				} else {
					self.hideSlide();
				}
			};
      self.slider.attr('aria-hidden', 'true');
      self.opener.attr('aria-expanded', 'false');
			self.opener.on(self.options.event, this.eventHandler);

			// hover mode handler
			if (self.options.event === 'hover') {
				self.opener.on('mouseenter', function() {
					if (!self.holder.hasClass(self.options.activeClass)) {
						self.showSlide();
					}
				});
				self.holder.on('mouseleave', function() {
					self.hideSlide();
				});
			}

			// outside click handler
			self.outsideClickHandler = function(e) {
				if (self.options.hideOnClickOutside) {
					var target = $(e.target);
					if (!target.is(self.holder) && !target.closest(self.holder).length) {
						self.hideSlide();
					}
				}
			};

			// set initial styles
			if (this.holder.hasClass(this.options.activeClass)) {
				$(document).on('click touchstart', self.outsideClickHandler);
			} else {
				this.slider.addClass(slideHiddenClass);
			}
		},
		showSlide: function() {
			var self = this;
			if (self.options.addClassBeforeAnimation) {
				self.holder.addClass(self.options.activeClass);
			}
      self.slider.attr('aria-hidden', 'false');
      self.opener.attr('aria-expanded', 'true');
			self.slider.removeClass(slideHiddenClass);
			$(document).on('click touchstart', self.outsideClickHandler);

			self.makeCallback('animStart', true);
			toggleEffects[self.options.effect].show({
				box: self.slider,
				speed: self.options.animSpeed,
				complete: function() {
					if (!self.options.addClassBeforeAnimation) {
						self.holder.addClass(self.options.activeClass);
					}
					self.makeCallback('animEnd', true);
				}
			});
		},
		hideSlide: function() {
			var self = this;
			if (self.options.addClassBeforeAnimation) {
				self.holder.removeClass(self.options.activeClass);
			}
			$(document).off('click touchstart', self.outsideClickHandler);

			self.makeCallback('animStart', false);
			toggleEffects[self.options.effect].hide({
				box: self.slider,
				speed: self.options.animSpeed,
				complete: function() {
					if (!self.options.addClassBeforeAnimation) {
						self.holder.removeClass(self.options.activeClass);
					}
					self.slider.addClass(slideHiddenClass);
          self.slider.attr('aria-hidden', 'true');
          self.opener.attr('aria-expanded', 'false');
					self.makeCallback('animEnd', false);
				}
			});
		},
		destroy: function() {
			this.slider.removeClass(slideHiddenClass).css({
				display: ''
			});
			this.opener.off(this.options.event, this.eventHandler);
			this.holder.removeClass(this.options.activeClass).removeData('OpenClose');
			$(document).off('click touchstart', this.outsideClickHandler);
		},
		makeCallback: function(name) {
			if (typeof this.options[name] === 'function') {
				var args = Array.prototype.slice.call(arguments);
				args.shift();
				this.options[name].apply(this, args);
			}
		}
	};

	// add stylesheet for slide on DOMReady
	var slideHiddenClass = 'js-slide-hidden';
	(function() {
		var tabStyleSheet = $('<style type="text/css">')[0];
		var tabStyleRule = '.' + slideHiddenClass;
		tabStyleRule += '{position:absolute !important;left:-9999px !important;top:-9999px !important;display:block !important}';
		if (tabStyleSheet.styleSheet) {
			tabStyleSheet.styleSheet.cssText = tabStyleRule;
		} else {
			tabStyleSheet.appendChild(document.createTextNode(tabStyleRule));
		}
		$('head').append(tabStyleSheet);
	}());

	// animation effects
	var toggleEffects = {
		slide: {
			show: function(o) {
				o.box.stop(true).hide().slideDown(o.speed, o.complete);
			},
			hide: function(o) {
				o.box.stop(true).slideUp(o.speed, o.complete);
			}
		},
		fade: {
			show: function(o) {
				o.box.stop(true).hide().fadeIn(o.speed, o.complete);
			},
			hide: function(o) {
				o.box.stop(true).fadeOut(o.speed, o.complete);
			}
		},
		none: {
			show: function(o) {
				o.box.hide().show(0, o.complete);
			},
			hide: function(o) {
				o.box.hide(0, o.complete);
			}
		}
	};

	// jQuery plugin interface
	$.fn.openClose = function(opt) {
		var args = Array.prototype.slice.call(arguments);
		var method = args[0];

		return this.each(function() {
			var $holder = jQuery(this);
			var instance = $holder.data('OpenClose');

			if (typeof opt === 'object' || typeof opt === 'undefined') {
				$holder.data('OpenClose', new OpenClose($.extend({
					holder: this
				}, opt)));
			} else if (typeof method === 'string' && instance) {
				if (typeof instance[method] === 'function') {
					args.shift();
					instance[method].apply(instance, args);
				}
			}
		});
	};
}(jQuery));

/*
 * jQuery Tooltip plugin
 */
(function ($) {
	$.fn.hoverTooltip = function (o) {
		var options = $.extend(
			{
				tooltipStructure: '<div class="hover-tooltip"><div class="tooltip-text"></div></div>',
				tooltipSelector: '.tooltip-text',
				positionTypeX: 'left',
				positionTypeY: 'top',
				attribute: 'title',
				extraOffsetX: 10,
				extraOffsetY: 10,
				showOnTouchDevice: true,
			},
			o
		);

		// create tooltip
		var tooltip = $('<div>').html(options.tooltipStructure).children().css({ position: 'absolute' });
		var tooltipTextBox = tooltip.find(options.tooltipSelector);
		var tooltipWidth, tooltipHeight;

		// tooltip logic
		function initTooltip(item) {
			var tooltipText = item.attr(options.attribute);
			item.removeAttr(options.attribute);
			if (!tooltipText) return;

			if (isTouchDevice) {
				if (options.showOnTouchDevice) {
					item.bind('touchstart', function (e) {
						showTooltip(item, tooltipText, getEvent(e));
						jQuery(document).one('touchend', hideTooltip);
					});
				}
			} else {
				item
					.bind('mouseenter', function (e) {
						showTooltip(item, tooltipText, e);
					})
					.bind('mouseleave', hideTooltip)
					.bind('mousemove', moveTooltip);
			}
		}
		function showTooltip(item, text, e) {
			tooltipTextBox.html(text);
			tooltip.appendTo(document.body).show();
			tooltipWidth = tooltip.outerWidth(true);
			tooltipHeight = tooltip.outerHeight(true);
			moveTooltip(e, item);
		}
		function hideTooltip() {
			tooltip.remove();
		}
		function moveTooltip(e) {
			var top,
				left,
				x = e.pageX,
				y = e.pageY;

			switch (options.positionTypeY) {
				case 'top':
					top = y - tooltipHeight - options.extraOffsetY;
					break;
				case 'center':
					top = y - tooltipHeight / 2;
					break;
				case 'bottom':
					top = y + options.extraOffsetY;
					break;
			}

			switch (options.positionTypeX) {
				case 'left':
					left = x - tooltipWidth - options.extraOffsetX;
					break;
				case 'center':
					left = x - tooltipWidth / 2;
					break;
				case 'right':
					left = x + options.extraOffsetX;
					break;
			}

			tooltip.css({
				top: top,
				left: left,
			});
		}

		// add handlers
		return this.each(function () {
			initTooltip($(this));
		});
	};

	// parse event
	function getEvent(e) {
		return e.originalEvent.changedTouches ? e.originalEvent.changedTouches[0] : e;
	}

	// detect device type
	var isTouchDevice = (function () {
		try {
			return 'ontouchstart' in window || (window.DocumentTouch && document instanceof DocumentTouch);
		} catch (e) {
			return false;
		}
	})();
})(jQuery);

/*
 * jQuery Tabs plugin
 */
(function ($, $win) {
	'use strict';

	function Tabset($holder, options) {
		this.$holder = $holder;
		this.options = options;

		this.init();
	}

	Tabset.prototype = {
		init: function () {
			this.$tabLinks = this.$holder.find(this.options.tabLinks);

			this.setStartActiveIndex();
			this.setActiveTab();

			if (this.options.autoHeight) {
				this.$tabHolder = $(this.$tabLinks.eq(0).attr(this.options.attrib)).parent();
			}

			this.makeCallback('onInit', this);
		},

		setStartActiveIndex: function () {
			var $classTargets = this.getClassTarget(this.$tabLinks);
			var $activeLink = $classTargets.filter('.' + this.options.activeClass);
			var $hashLink = this.$tabLinks.filter('[' + this.options.attrib + '="' + location.hash + '"]');
			var activeIndex;

			if (this.options.checkHash && $hashLink.length) {
				$activeLink = $hashLink;
			}

			activeIndex = $classTargets.index($activeLink);

			this.activeTabIndex = this.prevTabIndex = activeIndex === -1 ? (this.options.defaultTab ? 0 : null) : activeIndex;
		},

		setActiveTab: function () {
			var self = this;

			this.$tabLinks.each(function (i, link) {
				var $link = $(link);
				var $classTarget = self.getClassTarget($link);
				var $tab = $($link.attr(self.options.attrib));

				if (i !== self.activeTabIndex) {
					$classTarget.removeClass(self.options.activeClass);
					$tab.addClass(self.options.tabHiddenClass).removeClass(self.options.activeClass);
				} else {
					$classTarget.addClass(self.options.activeClass);
					$tab.removeClass(self.options.tabHiddenClass).addClass(self.options.activeClass);
				}

				self.attachTabLink($link, i);
			});
		},

		attachTabLink: function ($link, i) {
			var self = this;

			$link.on(this.options.event + '.tabset', function (e) {
				e.preventDefault();

				if (self.activeTabIndex === self.prevTabIndex && self.activeTabIndex !== i) {
					self.activeTabIndex = i;
					self.switchTabs();
				}
				if (self.options.checkHash) {
					location.hash = jQuery(this).attr('href').split('#')[1];
				}
			});
		},

		resizeHolder: function (height) {
			var self = this;

			if (height) {
				this.$tabHolder.height(height);
				setTimeout(function () {
					self.$tabHolder.addClass('transition');
				}, 10);
			} else {
				self.$tabHolder.removeClass('transition').height('');
			}
		},

		switchTabs: function () {
			var self = this;

			var $prevLink = this.$tabLinks.eq(this.prevTabIndex);
			var $nextLink = this.$tabLinks.eq(this.activeTabIndex);

			var $prevTab = this.getTab($prevLink);
			var $nextTab = this.getTab($nextLink);

			$prevTab.removeClass(this.options.activeClass);

			if (self.haveTabHolder()) {
				this.resizeHolder($prevTab.outerHeight());
			}

			setTimeout(
				function () {
					self.getClassTarget($prevLink).removeClass(self.options.activeClass);

					$prevTab.addClass(self.options.tabHiddenClass);
					$nextTab.removeClass(self.options.tabHiddenClass).addClass(self.options.activeClass);

					self.getClassTarget($nextLink).addClass(self.options.activeClass);

					if (self.haveTabHolder()) {
						self.resizeHolder($nextTab.outerHeight());

						setTimeout(function () {
							self.resizeHolder();
							self.prevTabIndex = self.activeTabIndex;
							self.makeCallback('onChange', self);
						}, self.options.animSpeed);
					} else {
						self.prevTabIndex = self.activeTabIndex;
					}
				},
				this.options.autoHeight ? this.options.animSpeed : 1
			);
		},

		getClassTarget: function ($link) {
			return this.options.addToParent ? $link.parent() : $link;
		},

		getActiveTab: function () {
			return this.getTab(this.$tabLinks.eq(this.activeTabIndex));
		},

		getTab: function ($link) {
			return $($link.attr(this.options.attrib));
		},

		haveTabHolder: function () {
			return this.$tabHolder && this.$tabHolder.length;
		},

		destroy: function () {
			var self = this;

			this.$tabLinks.off('.tabset').each(function () {
				var $link = $(this);

				self.getClassTarget($link).removeClass(self.options.activeClass);
				$($link.attr(self.options.attrib)).removeClass(self.options.activeClass + ' ' + self.options.tabHiddenClass);
			});

			this.$holder.removeData('Tabset');
		},

		makeCallback: function (name) {
			if (typeof this.options[name] === 'function') {
				var args = Array.prototype.slice.call(arguments);
				args.shift();
				this.options[name].apply(this, args);
			}
		},
	};

	$.fn.tabset = function (opt) {
		var args = Array.prototype.slice.call(arguments);
		var method = args[0];

		var options = $.extend(
			{
				activeClass: 'active',
				addToParent: false,
				autoHeight: false,
				checkHash: false,
				defaultTab: true,
				animSpeed: 500,
				tabLinks: 'a',
				attrib: 'href',
				event: 'click',
				tabHiddenClass: 'js-tab-hidden',
			},
			opt
		);
		options.autoHeight = options.autoHeight;

		return this.each(function () {
			var $holder = jQuery(this);
			var instance = $holder.data('Tabset');

			if (typeof opt === 'object' || typeof opt === 'undefined') {
				$holder.data('Tabset', new Tabset($holder, options));
			} else if (typeof method === 'string' && instance) {
				if (typeof instance[method] === 'function') {
					args.shift();
					instance[method].apply(instance, args);
				}
			}
		});
	};
})(jQuery, jQuery(window));

/*
 * jQuery sticky box plugin
 */
(function ($, $win) {
	'use strict';

	function StickyScrollBlock($stickyBox, options) {
		this.options = options;
		this.$stickyBox = $stickyBox;
		this.init();
	}

	var StickyScrollBlockPrototype = {
		init: function () {
			this.findElements();
			this.attachEvents();
			this.makeCallback('onInit');
		},

		findElements: function () {
			// find parent container in which will be box move
			this.$container = this.$stickyBox.closest(this.options.container);
			// define box wrap flag
			this.isWrap = this.options.positionType === 'fixed' && this.options.setBoxHeight;
			// define box move flag
			this.moveInContainer = !!this.$container.length;
			// wrapping box to set place in content
			if (this.isWrap) {
				this.$stickyBoxWrap = this.$stickyBox.wrap('<div class="' + this.getWrapClass() + '"/>').parent();
			}
			//define block to add active class
			this.parentForActive = this.getParentForActive();
			this.isInit = true;
		},

		attachEvents: function () {
			var self = this;

			// bind events
			this.onResize = function () {
				if (!self.isInit) return;
				self.resetState();
				self.recalculateOffsets();
				self.checkStickyPermission();
				self.scrollHandler();
			};

			this.onScroll = function () {
				self.scrollHandler();
			};

			// initial handler call
			this.onResize();

			// handle events
			$win.on('load resize orientationchange', this.onResize).on('scroll', this.onScroll);
		},

		defineExtraTop: function () {
			// define box's extra top dimension
			var extraTop;

			if (typeof this.options.extraTop === 'number') {
				extraTop = this.options.extraTop;
			} else if (typeof this.options.extraTop === 'function') {
				extraTop = this.options.extraTop();
			}

			this.extraTop = this.options.positionType === 'absolute' ? extraTop : Math.min(this.winParams.height - this.data.boxFullHeight, extraTop);
		},

		checkStickyPermission: function () {
			// check the permission to set sticky
			this.isStickyEnabled = this.moveInContainer
				? this.data.containerOffsetTop + this.data.containerHeight > this.data.boxFullHeight + this.data.boxOffsetTop + this.options.extraBottom
				: true;
		},

		getParentForActive: function () {
			if (this.isWrap) {
				return this.$stickyBoxWrap;
			}

			if (this.$container.length) {
				return this.$container;
			}

			return this.$stickyBox;
		},

		getWrapClass: function () {
			// get set of container classes
			try {
				return this.$stickyBox
					.attr('class')
					.split(' ')
					.map(function (name) {
						return 'sticky-wrap-' + name;
					})
					.join(' ');
			} catch (err) {
				return 'sticky-wrap';
			}
		},

		resetState: function () {
			// reset dimensions and state
			this.stickyFlag = false;
			this.$stickyBox
				.css({
					'-webkit-transition': '',
					'-webkit-transform': '',
					transition: '',
					transform: '',
					position: '',
					width: '',
					left: '',
					top: '',
				})
				.removeClass(this.options.activeClass);

			if (this.isWrap) {
				this.$stickyBoxWrap.removeClass(this.options.activeClass).removeAttr('style');
			}

			if (this.moveInContainer) {
				this.$container.removeClass(this.options.activeClass);
			}
		},

		recalculateOffsets: function () {
			// define box and container dimensions
			this.winParams = this.getWindowParams();

			this.data = $.extend(this.getBoxOffsets(), this.getContainerOffsets());

			this.defineExtraTop();
		},

		getBoxOffsets: function () {
			function offetTop(obj) {
				obj.top = 0;
				return obj;
			}
			var boxOffset = this.$stickyBox.css('position') === 'fixed' ? offetTop(this.$stickyBox.offset()) : this.$stickyBox.offset();
			var boxPosition = this.$stickyBox.position();

			return {
				// sticky box offsets
				boxOffsetLeft: boxOffset.left,
				boxOffsetTop: boxOffset.top,
				// sticky box positions
				boxTopPosition: boxPosition.top,
				boxLeftPosition: boxPosition.left,
				// sticky box width/height
				boxFullHeight: this.$stickyBox.outerHeight(true),
				boxHeight: this.$stickyBox.outerHeight(),
				boxWidth: this.$stickyBox.outerWidth(),
			};
		},

		getContainerOffsets: function () {
			var containerOffset = this.moveInContainer ? this.$container.offset() : null;

			return containerOffset
				? {
					// container offsets
					containerOffsetLeft: containerOffset.left,
					containerOffsetTop: containerOffset.top,
					// container height
					containerHeight: this.$container.outerHeight(),
				}
				: {};
		},

		getWindowParams: function () {
			return {
				height: window.innerHeight || document.documentElement.clientHeight,
			};
		},

		makeCallback: function (name) {
			if (typeof this.options[name] === 'function') {
				var args = Array.prototype.slice.call(arguments);
				args.shift();
				this.options[name].apply(this, args);
			}
		},

		destroy: function () {
			this.isInit = false;
			// remove event handlers and styles
			$win.off('load resize orientationchange', this.onResize).off('scroll', this.onScroll);
			this.resetState();
			this.$stickyBox.removeData('StickyScrollBlock');
			if (this.isWrap) {
				this.$stickyBox.unwrap();
			}
			this.makeCallback('onDestroy');
		},
	};

	var stickyMethods = {
		fixed: {
			scrollHandler: function () {
				this.winScrollTop = $win.scrollTop();
				var isActiveSticky =
					this.winScrollTop - (this.options.showAfterScrolled ? this.extraTop : 0) - (this.options.showAfterScrolled ? this.data.boxHeight + this.extraTop : 0) >
					this.data.boxOffsetTop - this.extraTop;

				if (isActiveSticky) {
					this.isStickyEnabled && this.stickyOn();
				} else {
					this.stickyOff();
				}
			},

			stickyOn: function () {
				if (!this.stickyFlag) {
					this.stickyFlag = true;
					this.parentForActive.addClass(this.options.activeClass);
					this.$stickyBox.css({
						width: this.data.boxWidth,
						position: this.options.positionType,
					});
					if (this.isWrap) {
						this.$stickyBoxWrap.css({
							height: this.data.boxFullHeight,
						});
					}
					this.makeCallback('fixedOn');
				}
				this.setDynamicPosition();
			},

			stickyOff: function () {
				if (this.stickyFlag) {
					this.stickyFlag = false;
					this.resetState();
					this.makeCallback('fixedOff');
				}
			},

			setDynamicPosition: function () {
				this.$stickyBox.css({
					top: this.getTopPosition(),
					left: this.data.boxOffsetLeft - $win.scrollLeft(),
				});
			},

			getTopPosition: function () {
				if (this.moveInContainer) {
					var currScrollTop = this.winScrollTop + this.data.boxHeight + this.options.extraBottom;

					return Math.min(this.extraTop, this.data.containerHeight + this.data.containerOffsetTop - currScrollTop);
				} else {
					return this.extraTop;
				}
			},
		},
		absolute: {
			scrollHandler: function () {
				this.winScrollTop = $win.scrollTop();
				var isActiveSticky = this.winScrollTop > this.data.boxOffsetTop - this.extraTop;

				if (isActiveSticky) {
					this.isStickyEnabled && this.stickyOn();
				} else {
					this.stickyOff();
				}
			},

			stickyOn: function () {
				if (!this.stickyFlag) {
					this.stickyFlag = true;
					this.parentForActive.addClass(this.options.activeClass);
					this.$stickyBox.css({
						width: this.data.boxWidth,
						transition: 'transform ' + this.options.animSpeed + 's ease',
						'-webkit-transition': 'transform ' + this.options.animSpeed + 's ease',
					});

					if (this.isWrap) {
						this.$stickyBoxWrap.css({
							height: this.data.boxFullHeight,
						});
					}

					this.makeCallback('fixedOn');
				}

				this.clearTimer();
				this.timer = setTimeout(
					function () {
						this.setDynamicPosition();
					}.bind(this),
					this.options.animDelay * 1000
				);
			},

			stickyOff: function () {
				if (this.stickyFlag) {
					this.clearTimer();
					this.stickyFlag = false;

					this.timer = setTimeout(
						function () {
							this.setDynamicPosition();
							setTimeout(
								function () {
									this.resetState();
								}.bind(this),
								this.options.animSpeed * 1000
							);
						}.bind(this),
						this.options.animDelay * 1000
					);
					this.makeCallback('fixedOff');
				}
			},

			clearTimer: function () {
				clearTimeout(this.timer);
			},

			setDynamicPosition: function () {
				var topPosition = Math.max(0, this.getTopPosition());

				this.$stickyBox.css({
					transform: 'translateY(' + topPosition + 'px)',
					'-webkit-transform': 'translateY(' + topPosition + 'px)',
				});
			},

			getTopPosition: function () {
				var currTopPosition = this.winScrollTop - this.data.boxOffsetTop + this.extraTop;

				if (this.moveInContainer) {
					var currScrollTop = this.winScrollTop + this.data.boxHeight + this.options.extraBottom;
					var diffOffset = Math.abs(Math.min(0, this.data.containerHeight + this.data.containerOffsetTop - currScrollTop - this.extraTop));

					return currTopPosition - diffOffset;
				} else {
					return currTopPosition;
				}
			},
		},
	};

	// jQuery plugin interface
	$.fn.stickyScrollBlock = function (opt) {
		var args = Array.prototype.slice.call(arguments);
		var method = args[0];

		var options = $.extend(
			{
				container: null,
				positionType: 'fixed', // 'fixed' or 'absolute'
				activeClass: 'fixed-position',
				setBoxHeight: true,
				showAfterScrolled: false,
				extraTop: 0,
				extraBottom: 0,
				animDelay: 0.1,
				animSpeed: 0.2,
			},
			opt
		);

		return this.each(function () {
			var $stickyBox = jQuery(this);
			var instance = $stickyBox.data('StickyScrollBlock');

			if (typeof opt === 'object' || typeof opt === 'undefined') {
				StickyScrollBlock.prototype = $.extend(stickyMethods[options.positionType], StickyScrollBlockPrototype);
				$stickyBox.data('StickyScrollBlock', new StickyScrollBlock($stickyBox, options));
			} else if (typeof method === 'string' && instance) {
				if (typeof instance[method] === 'function') {
					args.shift();
					instance[method].apply(instance, args);
				}
			}
		});
	};

	// module exports
	window.StickyScrollBlock = StickyScrollBlock;
})(jQuery, jQuery(window));

/*
 * Responsive Layout helper
 */
window.ResponsiveHelper = (function ($) {
	// init variables
	var handlers = [],
		prevWinWidth,
		win = $(window),
		nativeMatchMedia = false;

	// detect match media support
	if (window.matchMedia) {
		if (window.Window && window.matchMedia === Window.prototype.matchMedia) {
			nativeMatchMedia = true;
		} else if (window.matchMedia.toString().indexOf('native') > -1) {
			nativeMatchMedia = true;
		}
	}

	// prepare resize handler
	function resizeHandler() {
		var winWidth = win.width();
		if (winWidth !== prevWinWidth) {
			prevWinWidth = winWidth;

			// loop through range groups
			$.each(handlers, function (index, rangeObject) {
				// disable current active area if needed
				$.each(rangeObject.data, function (property, item) {
					if (item.currentActive && !matchRange(item.range[0], item.range[1])) {
						item.currentActive = false;
						if (typeof item.disableCallback === 'function') {
							item.disableCallback();
						}
					}
				});

				// enable areas that match current width
				$.each(rangeObject.data, function (property, item) {
					if (!item.currentActive && matchRange(item.range[0], item.range[1])) {
						// make callback
						item.currentActive = true;
						if (typeof item.enableCallback === 'function') {
							item.enableCallback();
						}
					}
				});
			});
		}
	}
	win.bind('load resize orientationchange', resizeHandler);

	// test range
	function matchRange(r1, r2) {
		var mediaQueryString = '';
		if (r1 > 0) {
			mediaQueryString += '(min-width: ' + r1 + 'px)';
		}
		if (r2 < Infinity) {
			mediaQueryString += (mediaQueryString ? ' and ' : '') + '(max-width: ' + r2 + 'px)';
		}
		return matchQuery(mediaQueryString, r1, r2);
	}

	// media query function
	function matchQuery(query, r1, r2) {
		if (window.matchMedia && nativeMatchMedia) {
			return matchMedia(query).matches;
		} else if (window.styleMedia) {
			return styleMedia.matchMedium(query);
		} else if (window.media) {
			return media.matchMedium(query);
		} else {
			return prevWinWidth >= r1 && prevWinWidth <= r2;
		}
	}

	// range parser
	function parseRange(rangeStr) {
		var rangeData = rangeStr.split('..');
		var x1 = parseInt(rangeData[0], 10) || -Infinity;
		var x2 = parseInt(rangeData[1], 10) || Infinity;
		return [x1, x2].sort(function (a, b) {
			return a - b;
		});
	}

	// export public functions
	return {
		addRange: function (ranges) {
			// parse data and add items to collection
			var result = { data: {} };
			$.each(ranges, function (property, data) {
				result.data[property] = {
					range: parseRange(property),
					enableCallback: data.on,
					disableCallback: data.off,
				};
			});
			handlers.push(result);

			// call resizeHandler to recalculate all events
			prevWinWidth = null;
			resizeHandler();
		},
	};
})(jQuery);
