function getWindowWidth() {
	return Math.max( $(window).width(), window.innerWidth);
}

jQuery(document).ready(function($) {
	
	var windowWidth = getWindowWidth();
	$(window).on('resize', function () {
		var windowWidth = getWindowWidth();
	});

	var backToTop = $('.backToTop');
	if(!$('body').hasClass('mobile')) {
		$(window).scroll(function(){
			var scroll = $(this).scrollTop();
			
			$(window).on('resize', function () {
				var scroll = $(this).scrollTop();
			});				
					
			if (scroll > 370) {
				backToTop.addClass('fadein');
			} else {
				backToTop.removeClass('fadein');
			}
		});
	}
	
	// Smooth Scroll
	$('a.scrollto').off('click');
	$('a.scrollto').on('click', function() {
		var sScroll = $(this),
			sScroll_offset = sScroll.data('offset'),
			sScroll_easing = sScroll.data('easing'),
			sScroll_speed = sScroll.data('speed'),
			sScroll_target = sScroll.attr('href');
			
		if(sScroll_offset == null){ sScroll_offset = 0; }
		if(sScroll_easing == null){ sScroll_easing = 'swing'; }
		if(sScroll_speed == null){ sScroll_speed = 800; }
		if(sScroll_target == null){ sScroll_target = '#'; }
		
		$.smoothScroll({
			offset: sScroll_offset,
			easing: ''+ sScroll_easing +'',
			speed: sScroll_speed,
			scrollTarget: sScroll_target
		});
		return false;
	});

});