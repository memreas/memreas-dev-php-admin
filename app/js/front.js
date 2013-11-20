function fixHeaders(){
	jQuery('.inner-content > h1').each(
		function(){
			if(! jQuery(this).text().length)
				return;
			$(this).hide();
			var bgcolor = '';
			font = 'myriadpro';
			size = '18';
			color = '007ECB';
			bgcolor = 'FFFFFF';
			width = jQuery(this).width() + 20;

			var src = '/imagetext.php?title='+encodeURIComponent(jQuery(this).text())+'&font='+font+'&size='+size+'&width='+(jQuery(this).width() + 30)+'&height='+jQuery(this).height()+'&color='+color+'&bgcolor='+bgcolor+'&top=top&foreg=EAF7FF';
			img = '<img src="'+src+'" title="'+jQuery(this).text()+'" alt="'+jQuery(this).text()+'" width="'+(jQuery(this).width() + 30)+'px" height="'+jQuery(this).height()+'px" border="0" />';
			jQuery(this).html( img ).css({paddingBottom: 8 });
			$(this).show();
		}
	);

	jQuery('.thematic-block-left h2, .thematic-block h2').each(
		function(){
			if(! jQuery(this).text().length)
				return;
			$(this).hide();
			var bgcolor = '';
			font = 'myriadpro';
			size = '15';
			color = '006EB1';
			bgcolor = 'FFFFFF';
			width = jQuery(this).width() + 20;

			var src = '/imagetext.php?title='+encodeURIComponent(jQuery(this).text())+'&font='+font+'&size='+size+'&width='+width+'&height='+jQuery(this).height()+'&color='+color+'&bgcolor='+bgcolor+'&top=top&foreg=EAF7FF';
			img = '<img src="'+src+'" title="'+jQuery(this).text()+'" alt="'+jQuery(this).text()+'" width="'+width+'px" height="'+jQuery(this).height()+'px" border="0" />';
			jQuery(this).html( img ).css({paddingBottom: 0 });
			$(this).show();
		}
	);

	jQuery('.submenu h2').each(
		function(){
			if(! jQuery(this).text().length)
				return;
			$(this).hide();
			var bgcolor = '';
			font = 'myriadpro';
			size = '15';
			color = '006EB1';
			bgcolor = 'FFFFFF';
			width = jQuery(this).width() + 20;

			var src = '/imagetext.php?title='+encodeURIComponent(jQuery(this).text())+'&font='+font+'&size='+size+'&width='+width+'&height='+jQuery(this).height()+'&color='+color+'&bgcolor='+bgcolor+'&top=top&foreg=EAF7FF';
			img = '<img src="'+src+'" title="'+jQuery(this).text()+'" alt="'+jQuery(this).text()+'" width="'+width+'px" height="'+jQuery(this).height()+'px" border="0" />';
			jQuery(this).css({paddingBottom: 0 }).html('');
			$(img).insertBefore(this);
			$(this).show();
		}
	);

	jQuery('div.portfolio-project-list h2, div.portfolio-info-list h2').each(
		function(){
			if(! jQuery(this).text().length)
				return;
			$(this).hide();
			var bgcolor = '';
			font = 'myriadpro';
			size = '15';
			color = '007ECB';
			bgcolor = 'FFFFFF';
			width = jQuery(this).width() + 20;

			var src = '/imagetext.php?title='+encodeURIComponent(jQuery(this).text())+'&font='+font+'&size='+size+'&width='+width+'&height='+jQuery(this).height()+'&color='+color+'&bgcolor='+bgcolor+'&top=top&foreg=EAF7FF';
			img = '<img src="'+src+'" title="'+jQuery(this).text()+'" alt="'+jQuery(this).text()+'" width="'+width+'px" height="'+jQuery(this).height()+'px" border="0" />';
			jQuery(this).html( img ).css({paddingBottom: 0 });
			$(this).show();
		}
	);
}

function loadUI( container, callfunc ) {
	var contain = jQuery(typeof(container) == 'undefined' ? 'body' : container );

	var loader = $('<img src="'+img_dir+'loadingAnimation.gif" />')
		.addClass('ajax-loader')
		.css({display: 'none', position: 'absolute', left: parseInt($(window).width() / 2 - 27), top: parseInt($(window).height() / 2 - 27), zIndex: 9500 })
		.prependTo(contain);

	loader.fadeIn('fast', function(){
		if(typeof( callfunc ) == 'function') {
			callfunc();
		}
	});
}

function unloadUI( container ) {
	var contain = jQuery(typeof(container) == 'undefined' ? 'body' : container );

	contain.children('.ajax-loader').fadeOut('fast', function(){ $(this).remove(); });
}
/**
 * Add mask to user interface
 */
function blockUI( container ) {

 var contain = jQuery(typeof(container) == 'undefined' ? 'body' : container );
 var blockdiv = '<div class="x-mask" style="display: block" />';
 var mask = contain.prepend(blockdiv).children('.x-mask');

 contain.addClass('x-masked');

 //mask.fadeIn('slow');
}
/**
* Remove mask
*/
function unblockUI() {
	jQuery('.x-masked').removeClass('x-masked').children('.x-mask').remove();
}

function blockedUI() {
	return jQuery('body').is('.x-masked');
}

function responceFormSubmit( data ){

	if(typeof(data) != 'string') {
		data = data.toSource();
	}

	if(! data || data == "<div class=\"message\"><table border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tbody><tr><td><span>Your contact message has been successfully accepted.</span></td></tr></tbody></table></div>\n"){
		data = '<script>setTimeout(function(){ location.href = "/"; }, 2000)</script><div  class="message"><table cellpadding="0" cellspacing="0" border="0"><tr><td><span>Your contact message has been successfully accepted.</span></td></tr></table></div>';
	}

	if( $.browser.msie ){
		var script = $(data).filter('script').html();
		if( typeof script == 'string' && script.length )
			eval( script );
	}

	$(data).filter('div').appendTo('body').wrap('<div class="modal-dialog"></div>');

	var dlg = $('.modal-dialog');
	var script = '';

	if($(data).filter('script').length){
		script = $(data).filter('script').text();
	}

	unloadUI();
	dlg.css({top: parseInt($(window).height() / 2) - parseInt(dlg.height() / 2) - 40, left: parseInt($(window).width() / 2) - parseInt(dlg.width() / 2)});

	if(dlg.children('.error').length){
		dlg.addClass('modal-has-errors');
		$('.x-mask, .modal-dialog').prepend( $('<div />').addClass('close') );

		$('.x-mask, .modal-dialog').one('click', function(){
			unblockUI();
			$(".modal-dialog").fadeOut(function(){$(this).remove();});
		});

		eval(script);
	}else
		setTimeout('unblockUI(); $(".modal-dialog").fadeOut("1000", function(){$(this).remove();}); ' + script, 3000);
}

function submitForm() {
	if($(this).is('input')){

		if(blockedUI())
			return false;

		if($(this).is('[name="cancel"]')){
			$('.right-column').slideUp('slow', function(){$(this).html('');});
			return false;
		}

		var form = $(this).parents('form');
		if($(this).is(':button'))
			$('[name="_ia"]', form).val($(this).attr('name'));

		var field_values = form.serializeArray();

		if(typeof(field_values) == 'object'){
			var targ = typeof(form.attr('target')) == 'string' ? form.attr('target') : (typeof(form.attr('action')) == 'string' ? form.attr('action') : document.location.href);
			targ = targ.replace(/^.*\?/, '?');
			if(targ.charAt(0) != '?')
				targ = '?action=submit';

			$.scrollTo(0);

			blockUI();
			loadUI('body', function(){
				var options = {
					url: targ + '&ajaxed=true',
					success: responceFormSubmit,
					dataType: null
				}

				$(form).ajaxSubmit(options);
			});
		}

		return false;
	}
}

function ajaxedForms() {

	$('form.ajaxed input:button, form.ajaxed input:submit').click(submitForm).each(
	function(){
		var onclick = $(this).attr('onclick');
		if(typeof(onclick) == 'string' && onclick.length > 0)
			$(this).attr('onclick', 'if(submitForm.apply(this)){'+onclick+'} else { return false; }');
		else
			$(this).attr('onclick', 'return submitForm();');
	});

	$('form.ajaxed').submit(function(){
		return submitForm.apply($('input[type=submit], input[type=image]', $(this)));
	}).removeClass('ajaxed');
}

function ajaxedLinks(){
	$('a.ajaxed').removeClass('ajaxed').click(function(){
		var self = this;
		var $cont = $('.ajax-content');
		$cont.fadeOut(function(){
			var cont  = this;
			$.scrollTo(0);
			$cont.load( $(self).attr('href') + 'ajax/', function(){
				ajaxedLinks();
				$cont.fadeIn();
			});
		});
		return false;
	});
}

var tabAnimation = false;

function initTabs() {
	$('#tabs .navi a').each(function(ind){

		$(this).click(function(){
			if($(this).parent('li').is('.here') || tabAnimation)
				return false;

			tabAnimation = true;

			//activate tab
			$('#tabs .navi li').removeClass('here');
			$(this).parent('li').addClass('here');

			//hide container
			$('#tabs .tab-content:visible').fadeOut(function(){
				//show container
				$('#tabs .tab-content:eq('+ind+')').fadeIn(function(){ tabAnimation = false; });
			});

			return false;
		});
	});
}

function fixLeftColumn(){
	if( ! $('.left-column:eq(0)').html() ){
		$('.left-column:eq(0)').remove();
		$('.center-over').removeClass('center-over').addClass('center-over-big');
		$('.center-column').removeClass('center-column').addClass('center-column-big');
	}
}

function createSelect( id_prefix ){
	var hideQJTimeout = false;

	function hideQuickJump(){
		resetQuickJump();
		hideQJTimeout = setTimeout(function(){ if( ! $('#'+id_prefix+'Options li.select-option-hovered').length ){ $('#'+id_prefix+'Options').fadeOut(); } }, 500);
	}
	function resetQuickJump(){
		clearTimeout( hideQJTimeout );
	}

	$('#'+id_prefix+'Area').click(function(){
		if( $('#'+id_prefix+'Options').is(':hidden') ){
			resetQuickJump();
			if( $('#'+id_prefix+'Options').is('.select-options-invisible') )
				$('#'+id_prefix+'Options').removeClass('select-options-invisible').addClass('select-options-visible').appendTo('body');

			$('#'+id_prefix+'Options').css({ position: 'absolute', top: $(this).offset().top + $(this).height() - 1, left: $(this).offset().left + ($.browser.mozilla ? 1 : 0), width: $(this).width() - 2 }).show();

			var offset = $('#'+id_prefix+'Options').offset().top + $('#'+id_prefix+'Options').height();
			offset -= $(window).height();

			var ScrollTop = document.body.scrollTop;
			if (ScrollTop == 0)	{
			    if (window.pageYOffset)
			        ScrollTop = window.pageYOffset;
			    else
			        ScrollTop = (document.body.parentElement) ? document.body.parentElement.scrollTop : 0;
			}

			offset -= ScrollTop;

			if( offset > 0 ){

				if( $(this).offset().top - $('#'+id_prefix+'Options').height() > ScrollTop ){
					$('#'+id_prefix+'Options').css({ position: 'absolute', 'top': $(this).offset().top - $('#'+id_prefix+'Options').height() - 2, left: $(this).offset().left + ($.browser.mozilla ? 1 : 0), width: $(this).width() - 2 });
				}else{
					$.scrollTo('+=' + (offset + 10) + 'px', { duration:500, easing:'swing' });
				}
			}

		}else{
			$('#'+id_prefix+'Options').hide();
		}
	}).mouseover( resetQuickJump ).mouseout( hideQuickJump )
	.children('#'+id_prefix+'Options').hover( resetQuickJump, hideQuickJump )
	.children('li').hover(function(){
		resetQuickJump();
		$(this).addClass('select-option-hovered');
	},function(){
		$(this).removeClass('select-option-hovered');
	}).click(function(){
		$('#'+id_prefix+'Text').text( $(this).text() );
		document.location = $(this).attr('rel');
	});
}


jQuery(document).ready(function() {

	$('div.menu > ul').menu({
		//container: '#all',
	showAnimation: 100,
	hideAnimation: 100,
	horizontal: ['.inner-menu3'],
	vertical: ['.inner-menu']
	});


	fixLeftColumn();
	fixHeaders();
	initTabs();
	ajaxedForms();

	ajaxedLinks();

	createSelect('quickJumpSelect');
});

(function( $ ){

	var $scrollTo = $.scrollTo = function( target, duration, settings ){
		$scrollTo.window().scrollTo( target, duration, settings );
	};

	$scrollTo.defaults = {
		axis:'y',
		duration:1
	};

	//returns the element that needs to be animated to scroll the window
	$scrollTo.window = function(){
		return $( $.browser.safari ? 'body' : 'html' );
	};

	$.fn.scrollTo = function( target, duration, settings ){
		if( typeof duration == 'object' ){
			settings = duration;
			duration = 0;
		}
		settings = $.extend( {}, $scrollTo.defaults, settings );
		duration = duration || settings.speed || settings.duration;//speed is still recognized for backwards compatibility
		settings.queue = settings.queue && settings.axis.length > 1;//make sure the settings are given right
		if( settings.queue )
			duration /= 2;//let's keep the overall speed, the same.
		settings.offset = both( settings.offset );
		settings.over = both( settings.over );

		return this.each(function(){
			var elem = this, $elem = $(elem),
				t = target, toff, attr = {},
				win = $elem.is('html,body');
			switch( typeof t ){
				case 'number'://will pass the regex
				case 'string':
					if( /^([+-]=)?\d+(px)?$/.test(t) ){
						t = both( t );
						break;//we are done
					}
					t = $(t,this);// relative selector, no break!
				case 'object':
					if( t.is || t.style )//DOM/jQuery
						toff = (t = $(t)).offset();//get the real position of the target
			}
			$.each( settings.axis.split(''), function( i, axis ){
				var Pos	= axis == 'x' ? 'Left' : 'Top',
					pos = Pos.toLowerCase(),
					key = 'scroll' + Pos,
					act = elem[key],
					Dim = axis == 'x' ? 'Width' : 'Height',
					dim = Dim.toLowerCase();

				if( toff ){//jQuery/DOM
					attr[key] = toff[pos] + ( win ? 0 : act - $elem.offset()[pos] );

					if( settings.margin ){//if it's a dom element, reduce the margin
						attr[key] -= parseInt(t.css('margin'+Pos)) || 0;
						attr[key] -= parseInt(t.css('border'+Pos+'Width')) || 0;
					}

					attr[key] += settings.offset[pos] || 0;//add/deduct the offset

					if( settings.over[pos] )//scroll to a fraction of its width/height
						attr[key] += t[dim]() * settings.over[pos];
				}else
					attr[key] = t[pos];//remove the unnecesary 'px'

				if( /^\d+$/.test(attr[key]) )//number or 'number'
					attr[key] = attr[key] <= 0 ? 0 : Math.min( attr[key], max(Dim) );//check the limits

				if( !i && settings.queue ){//queueing each axis is required
					if( act != attr[key] )//don't waste time animating, if there's no need.
						animate( settings.onAfterFirst );//intermediate animation
					delete attr[key];//don't animate this axis again in the next iteration.
				}
			});
			animate( settings.onAfter );

			function animate( callback ){
				$elem.animate( attr, duration, settings.easing, callback && function(){
					callback.call(this, target);
				});
			};
			function max( Dim ){
				var el = win ? $.browser.opera ? document.body : document.documentElement : elem;
				return el['scroll'+Dim] - el['client'+Dim];
			};
		});
	};

	function both( val ){
		return typeof val == 'object' ? val : { top:val, left:val };
	};

})( jQuery );