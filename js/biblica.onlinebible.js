/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

/**
@namespace Biblica
*/
biblica = (typeof biblica === 'undefined') ? {} : biblica;

/**
@namespace Online Bible Reading UI constructor
*/
biblica.onlineBibleVersion = function(elm) {
	var $ = jQuery;
	this.elm = $(elm);
	this.header = this.elm.find('.header');
	this.content = this.elm.find('.content');
	this.verseTools = $('<ul class="tools verse-tools" tabindex="0" />');
	this.init();
};

biblica.onlineBibleVersion.sharetemplate = '<div class="addthis_toolbox addthis_default_style addthis_32x32_style" addthis:url="" addthis:title="">'+
									'<a class="addthis_button_preferred_1"></a>'+
									'<a class="addthis_button_preferred_2"></a>'+
									'<a class="addthis_button_preferred_3"></a>'+
									'<a class="addthis_button_preferred_4"></a>'+
									'<a class="addthis_button_compact"></a>'+
									'<a class="addthis_counter addthis_bubble_style"></a>'+
								'</div>';

/** 
@namespace Online Bible Reading UI prototype
*/
biblica.onlineBibleVersion.prototype = {
	init: function() {
		var $ = jQuery;
		var t = this;
	
		// Activate close button
		/*this.header.find('.btn-close').click(function(e) {
			e.stopPropagation();
			e.preventDefault();
			t.close();
		});*/
		
		// Activate single verse view
		var verseView = this.elm.find('.single-verse');
		if(verseView.size()) {
			this.content.hide().next().hide();
			this.elm.find('.single-verse .btn-show').click(function(e) {
				e.stopPropagation();
				e.preventDefault();
				verseView.slideUp(300);
				t.content.fadeIn(300).next().show();
			});
		}
		
		// Activate contextual menus on verse numbers
		this.verseTools.html('<li class="share"><a class="icon-share" href="#share" data-toggle="modal" data-backdrop="false"><span class="fa fa-share" role="img"></span> Share</a></li>');
		this.elm.append(this.verseTools.hide());
		// this.content.find('.versenum').click(function(e) {
		// 	e.stopPropagation();
		// 	e.preventDefault();
		// 	t.toggleContextualMenu($(this));
		// });
		$(document).click(function() {
			t.verseTools.hide();
			t.content.find('.toggled').parent().removeClass('shared');
			t.content.find('.toggled').removeClass('toggled');
		});
		
		$('.header .tools .share a').click(function(e) {
			if(addthis) {
				addthis.update('share', 'url', document.location.href);
				addthis.url = document.location.href;
				addthis.update('share', 'title', document.title);
				addthis.title = document.title;
				addthis.toolbox(".addthis_toolbox");
			}
		});
		
		//Mark selected verse
		if(document.location.hash && document.location.hash.indexOf('verse') > -1) {
			$(document.location.hash).addClass('shared');
		}
		
	},
	toggleContextualMenu: function(clicked) {
		if(clicked.hasClass('toggled')) {
			clicked.removeClass('toggled').focus();
			this.verseTools.hide();
			clicked.parent().removeClass('shared');
		} else {
			this.content.find('.toggled').removeClass('toggled');
			this.verseTools.css({
				top: clicked.position().top + clicked.height(),
				left: clicked.position().left - 1
			});
			clicked.addClass('toggled');
			this.verseTools.show().focus().find('a:first');
			clicked.parent().addClass('shared');
		}
		// Verse sharing
		var verseId = clicked.parent().attr('id');
		var verseContent = clicked.parent().text();
		var shareUrl = document.location.href.split('#')[0]+'#'+verseId;
		
		if(addthis) {
			addthis.update('share', 'url', shareUrl);
			addthis.url = shareUrl;
			addthis.update('share', 'title', verseContent);
			addthis.title = verseContent;
			addthis.toolbox(".addthis_toolbox");
		}
	},
	close: function() {
		var $ = jQuery;
		this.elm.remove();
		$('#online-bible .version').removeClass('version-secondary col-md-6').addClass('version-primary col-md-12');
	}
};
