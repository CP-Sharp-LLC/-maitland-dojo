(function() {
	"use strict";
	jQuery('.team-item:has(.team.template-9)').addClass('collapse-paddings');
	jQuery('.team-listing:has(.team.template-10)').addClass('colored-blocks');
	jQuery('.team-listing:has(.team.template-12)').addClass('colored-overflow-blocks');
	jQuery('.team-listing:has(.team.template-13)').addClass('posts-with-accordion');

	jQuery('.team.template-13 .cherry-spoiler-title a').contents().unwrap();

	jQuery('.team-listing.posts-with-accordion .team-item:not(:first-child) .cherry-spoiler').addClass('cherry-spoiler-closed');
}());