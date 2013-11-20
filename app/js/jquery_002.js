/**
 * Menu - plugin for creating DHTML menus
 *
 * Author: 	Igor Finchuk
 *			i.finchuk@gmail.com
 *
 * Version: 0.07 (08/03/2008)
 *
 * + select fixes with iframe
 *
 * Requires: jQuery 1.1+
 */
(function($){
	
	var Menu = function( el, settings ){
		
		var $menu = $(el);
		var $container = $( settings.container );
		var menuLeftOffset = $menu.offset().left;
		var menuTopOffset = $menu.offset().top;
		
		// add hover classes
		$('li:not(:has(ul))', $menu).hover(function(){ $(this).addClass('hover') },function(){ $(this).removeClass('hover') });
		$menu.children().hover(function(){ $(this).addClass('hover') },function(){ $(this).removeClass('hover') });
		
		// fix bottom li
		$('ul', $menu).each(function(){ 
			$(this).children('li:last').addClass('last');
		});
		
		var showInner = function(){
			
			var self = this;
			var $parent = $(this).parent();
			var $inner = $(this).children('ul');
			
			this.position = function(){
				var deltaX = settings.relatived ? -($parent.offset().left) : 0;
				var deltaY = settings.relatived ? 0 : $(self).offset().top;
				
				var vertical = false;
				for( var i = 0; i < settings.vertical.length; i++ ){
					if( $inner.is( settings.vertical[i] ) ){
						vertical = true;
						
						if( ($inner.width() + $(self).offset().left) > $container.width() + $container.offset().left )
							$inner.css({ left: $(self).offset().left + deltaX - $inner.width() + $(self).width(), top: $(self).height() + deltaY });
						else
							$inner.css({ left: $(self).offset().left + deltaX, top: $(self).height() + deltaY });
					}
				}
				
				if( ! vertical ){
					for( var i = 0; i < settings.horizontal.length; i++ ){
						if( $inner.is( settings.horizontal[i] ) ){
							if( ($inner.width() + $(self).width() + $(self).offset().left) > $container.width() + $container.offset().left )
								$inner.css({ left: -( $inner.width() + settings.rightOffset + 1 ), top: $(self).offset().top - $parent.offset().top });
							else
								$inner.css({ left: $parent.width() + settings.leftOffset , top: $(self).offset().top - $parent.offset().top });
						}
					}
				}
				
				if( settings.iframe )
					$inner.bgiframe();
			};
			
			if(typeof this.timer != 'undefined' && typeof this.$inner != 'undefined'){
				clearTimeout( this.timer );
			}else{
				self.$inner = $inner;
				
				this.timer = false;
				this.resized = false;
				
				var width = self.$inner.width();		
				
				$inner.children('li').each(function(){
					$(this).width( width );
				});
				
				this.position();
				
				$inner.mousemove( function(){ showInner.apply( self ) } );
			}
			
			if( typeof this.resized == 'undefined' || this.resized ){
				this.resized = false;
				this.position();
			}
			
			$(self).addClass('hover');
			if( settings.showAnimation )
				this.$inner.fadeIn( settings.showAnimation );
			else
				this.$inner.show();
		};
		
		var hideInner = function( ){
			var self = this;
			var $inner = this.$inner;
			if( ! $inner )
				return;
			
			var hide = function(){ 
					if( settings.hideAnimation && ! $.browser.msie )
						$inner.fadeOut( settings.hideAnimation );
					else
						$inner.hide();
					$(self).removeClass('hover'); 
			};
			this.timer = setTimeout( hide, settings.hideTimeout );
		};
		
		// show inner on hover
		$('li:has(ul)', $menu).mousemove( showInner ).mouseout( hideInner );
		
		$(window).resize(function(){
			$('li:has(ul)', $menu).each(function(){
				this.resized = true;
			});
		});
	};
	
	$.extend($.fn, {
		menu: function( settings ){
			var settings = $.extend({
				showAnimation: false,
				hideAnimation: 100,
				hideTimeout: 100,
				container: 'body',
				horizontal: [],
				vertical: [],
				relatived: true,
				rightOffset: 1,
				leftOffset: 0,
				iframe: false
			}, settings);
			
			return $(this).each(function(){ new Menu( this, settings); });
		}
	});
	
	// provide backwards compability
	$.fn.Menu = $.fn.menu;
	
})(jQuery);