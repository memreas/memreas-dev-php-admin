 var UI ={
    'current_tab':'',
    'url':'',
		'tab0':'/index/user',
		'tab1':'/index/admin',
    'tab2':'/index/feedback',
    'tab3':'/index/event',
    'tab4':'/index/account-summary',
    'tab5':'/index/account-usage',
    'tab6':'/index/order-history',
    'tab7':'/index/account',
    'tab8':'/index/payout',
    
    
    
};
//Notify functions
function jsuccess (str_msg){ 
    jSuccess(
    str_msg,
    {
      autoHide : true, // added in v2.0
      clickOverlay : false, // added in v2.0
      MinWidth : 250,
      TimeShown : 3000,
      ShowTimeEffect : 200,
      HideTimeEffect : 200,
      LongTrip :20,
      HorizontalPosition : 'center',
      VerticalPosition : 'top',
      ShowOverlay : true,
      ColorOverlay : '#FFF',
      OpacityOverlay : 0.3,
      onClosed : function(){},
      onCompleted : function(){}
    });
}
function jerror (str_msg){
    jError(
    str_msg,
    {
      autoHide : true, // added in v2.0
      clickOverlay : false, // added in v2.0
      MinWidth : 250,
      TimeShown : 3000,
      ShowTimeEffect : 200,
      HideTimeEffect : 200,
      LongTrip :20,
      HorizontalPosition : 'center',
      VerticalPosition : 'top',
      ShowOverlay : true,
      ColorOverlay : '#FFF',
      OpacityOverlay : 0.3,
      onClosed : function(){},
      onCompleted : function(){}
    });
}

function jsuccessAndRefresh(str_msg){
$mymodal.modal("hide");
 
  jsuccess(str_msg);  
  $('#'+UI.current_tab).load(UI[UI.current_tab]);

}

//need to clear popup data on hide .
$('body').on('hidden.bs.modal', '.modal', function () {
 $(this).removeData('bs.modal').find(".modal-content").empty();
});

var $mymodal = $('#popup');
function useractive (user_id) {
 var url = "/index/userActive/"+user_id;
        
     $mymodal.find(".modal-content").load(url, function() { 
           $mymodal.modal(); 
    });
  
}
 
function userdisable (user_id) {
  var url = "/index/userDeactive/"+user_id;
  $mymodal.find(".modal-content").load(url, function() { 
           $mymodal.modal(); 
  }); 
}
/*$(function() {
$( "#datepicker" ).datepicker();
$( "#datepicker-1" ).datepicker();
$( "#datepicker-2" ).datepicker();
$( "#datepicker-3" ).datepicker();
});
*/
		(function($){ 
      
			$(window).load(function(){
$(document).on("click" , "a.paginate_click", function(e){
          e.preventDefault();
          var $this=$(this);
            UI.url=$this.attr("href");
            var target = $(this).parents('div .resp-tab-content-active');
            $(target).load( UI.url );
 
 
} );
$(document).on("click" , "a.listpopup", function(e){
          e.preventDefault();
          var $this=$(this);
            UI.url=$this.attr("href");
            var target = $(this).parents('div .modal-content'); 
            $(target).load( UI.url );
 
 
} );
/*$(document).on("click" , "a.intab", function(e){
          e.preventDefault();
          var $this=$(this),
          url=$this.attr("href");
          var target = $('#tabone');
		  $(target).empty();
          $(target).load( url );
                  popup('forgot');

         //$("#tab-content div.hideCls").hide(); //Hide all content
          //$(target).show();
 
} );*/
 
			
		/*	$("ul.scrollClass").mCustomScrollbar({
					scrollButtons:{
						enable:true
					}
				});
				
				
				
				
			$("#tab-content div.hideCls").hide(); // Initially hide all content
			$("#tabs li:first").attr("id","current"); // Activate first tab
			$("#tab-content div:first").fadeIn(); // Show first tab content
			
			$('#tabs a').click(function(e) {
 				e.preventDefault();        
				$("#tab-content div.hideCls").hide(); //Hide all content
				$("#tabs li").attr("id",""); //Reset id's
				$(this).parent().attr("id","current"); // Activate this
                                UI.current_tab = $(this).attr('title');
				$('#' + $(this).attr('title')).fadeIn(); // Show content for current tab
				
			});

		
				
				
				//ajax demo fn
				$("a[rel='load-content']").click(function(e){
					
					e.preventDefault();
					var $this=$(this),
						url=$this.attr("href");
					$this.addClass("loading");
					$.get(url,function(data){
						$this.removeClass("loading");
						$("ul.scrollClass .mCSB_container").html(data); //load new content inside .mCSB_container
						$("ul.scrollClass").mCustomScrollbar("update"); //update scrollbar according to newly loaded content
						$("ul.scrollClass").mCustomScrollbar("scrollTo","top",{scrollInertia:200}); //scroll to top
					});
				});
				$("a[rel='append-content']").click(function(e){
					e.preventDefault();
					var $this=$(this),
						url=$this.attr("href");
					$this.addClass("loading");
					$.get(url,function(data){
						$this.removeClass("loading");
						$("ul.scrollClass .mCSB_container").append(data); //append new content inside .mCSB_container
						$("ul.scrollClass").mCustomScrollbar("update"); //update scrollbar according to newly appended content
						$("ul.scrollClass").mCustomScrollbar("scrollTo","h2:last",{scrollInertia:2500,scrollEasing:"easeInOutQuad"}); //scroll to appended content
					});
				});*/
			});
		})(jQuery);
  

   $('#tab0').load(UI.tab0);
    $('#tab1').load(UI.tab1);
    $('#tab2').load(UI.tab2);
    $('#tab3').load(UI.tab3);
    $('#tab4').load(UI.tab4);
	    $('#tab5').load(UI.tab5);

    $('#tab6').load(UI.tab6);
   $('#tab7').load(UI.tab7);
      $('#tab8').load(UI.tab8);


$(document)  
  .on('show.bs.modal', '.modal', function(event) {
    $(this).appendTo($('body'));
  })
  .on('shown.bs.modal', '.modal.in', function(event) {
    setModalsAndBackdropsOrder();
  })
  .on('hidden.bs.modal', '.modal', function(event) {
    setModalsAndBackdropsOrder();
  });

function setModalsAndBackdropsOrder() {  
  var modalZIndex = 1040;
  $('.modal.in').each(function(index) {
    var $modal = $(this);
    modalZIndex++;
    $modal.css('zIndex', modalZIndex);
    $modal.next('.modal-backdrop.in').addClass('hidden').css('zIndex', modalZIndex - 1);
});
  $('.modal.in:visible:last').focus().next('.modal-backdrop.in').removeClass('hidden');
}

function doinitFrom(){
  $uniformed = $(".formstyle").find("input.unistyle, textarea, select, a.uniformTest");
      $uniformed.uniform();
}
function getRadioValue () {
    if( $('input[name="adminid"]:radio:checked').length > 0 ) {
        return $('input[name="adminid"]:radio:checked').val();
    }
    else {
        return 0;
    }
}