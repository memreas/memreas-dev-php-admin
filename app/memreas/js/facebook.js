/////////////////////////////////
// Author: David Kang
// Copyright memreas llc 2013
/////////////////////////////////

var FACEBOOK_APPID 			= '642983449085789';
var FACEBOOK_SECRETCODE 	= '47bfc45d191ef7dda0e2ebbf43b70a64';
var FACEBOOK_FRIENDSLIMIT 	= 500;

var fb_accountInfo = null;
var fb_friendsInfo = null;


sortMethod = function(a, b) {
	var x = a.name.toLowerCase();
	var y = b.name.toLowerCase();
	return ((x < y) ? -1 : ((x > y) ? 1 : 0));
}

facebook_getFriendList = function() {
	$('#loadingpopup').show();
	FB.init({ appId: FACEBOOK_APPID,
		status: true, 
		cookie: true,
		xfbml: true,
		oauth: true
	});

	function getFacebookInfo(response) {
		var button = document.getElementById('fb-auth');

		if (response.authResponse) { // in case if we are logged in
			var userInfo = document.getElementById('user-info');
			FB.api('/me', function(response) {
				fb_accountInfo = {
					'id': 		response.id,
					'name': 	response.name,
					'photo': 	'https://graph.facebook.com/' + response.id + '/picture'
				};
				$('#profile_picture').prop('src', fb_accountInfo.photo);
			});

			// get friends
			FB.api('/me/friends?limit=' + FACEBOOK_FRIENDSLIMIT, function(response) {
				var i = 0, info = response.data.sort(sortMethod);
				fb_friendsInfo = [];
				
				for (i = 0; i < info.length; i++) {
					fb_friendsInfo[i] = {
						'id': 		info[i].id,
						'div_id':	'fb_' + i,
						'name': 	info[i].name,
						'photo': 	'https://graph.facebook.com/' + info[i].id + '/picture',
						'selected':	false
					}
				}
				
				share_addFriends(fb_friendsInfo);
				$('#loadingpopup').hide();
			});
		}
		else {
			$('#loadingpopup').hide();
		}
	}
	
	// run once with current status and whenever the status changes
	FB.getLoginStatus(getFacebookInfo);
	
	FB.login(function(response) {
		if (response.authResponse) {
		}
		else {
			$('#loadingpopup').hide();
		}
	}, {scope:'email'});
}