(function() {
	"use strict";
	var postsItem = jQuery('.cherry-posts-item');
	postsItem.find('.template-7').parents('.cherry-posts-list').addClass('template-7');
	postsItem.find('.template-11').parents('.cherry-posts-list').addClass('template-11');
	postsItem.find('.template-9').parents('.cherry-posts-list').addClass('odd-background fixed-width');
	postsItem.find('.clients').parents('.cherry-posts-list').addClass('clients');
	jQuery('.template-8.cherry-accordion').parents('.cherry-posts-list').addClass('posts-with-accordion');
	jQuery('.template-8.cherry-accordion').each(function(){
		jQuery(this).find('.cherry-spoiler-title a').contents().unwrap();
	});
	jQuery('.posts-with-accordion').find('.cherry-posts-item').each(function(){
		jQuery(this).not(':first-child').find('.cherry-spoiler').addClass('cherry-spoiler-closed');
	});
}());