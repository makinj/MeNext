function register(){
  $.post("handler.php", $("#register").serialize(),
    function(data){
      var result= JSON.parse(data);
      if(result['token']!=-1){
        window.location.href = "/";
      }else{
        if(result['registerStat']=="alreadyExists"){
          $("#problem").html("username already in use");
        }else{
          $("#problem").html("unable to register user");
        }
      }
    }
  );
  return false;
}

function login(){
  $.post("handler.php", $("#login").serialize(),
    function(data){
      var result= JSON.parse(data);
      if(result['token']!=-1){
        window.location.href = "/";
      }else{
        $("#problem").html("unable to log in");
      }
    }
  );
  return false;
}

function searchYouTube(){//searches youtube to get a list of
  if($("#searchText").val()!=""){
    $.get("https://www.googleapis.com/youtube/v3/search"+
      "?part=snippet"+//don't question this part
      "&order=relevance"+//sort by relevance
      "&type=video"+//only show videos(not channels or playlists)
      "&maxResults=25"+//up to 25 results
      "&q="+$("#searchText").val()+//query with search term
      "&key="+API_KEY,//Client API key

      function(data,status){
        if (status=="success"){
          listSearchResults(data);
        }else{
          $("#searchResults").html("Failed :(");
        }
        $('button.addVideo').click(function(){
          $(this).attr('disabled',1);
          submitVideo($(this).val());
          $(this).attr('class',"button button-success");
          $(this).html("<span class='glyphicon glyphicon-ok'/>");
          listQueue();
        });
    });
  }else{
    $("#searchResults").html("Enter Search term");

  }
  //event.preventDefault();
  return 0;
}

function listSearchResults(data){
  $("#searchResults").html("");

  var videos= data.items;
  for (var i=0;i<videos.length;i++){
    $('#searchResults').append("<tr>"+
      "<td>"+(i+1).toString()+"</td>"+
      "<td><button class='addVideo button' value='"+videos[i].id.videoId+"'><span class='glyphicon glyphicon-plus'/></button></td>"+
      "<td><img src='"+videos[i].snippet.thumbnails.default.url+"'/></td>"+
      "<td>"+videos[i].snippet.title+"</td>"+
      "<td>"+videos[i].snippet.description+"</td>"+
      "</tr>");
  }
}

function createParty(){
  $.post("handler.php", $("#createPartyForm").serialize(),
    function(data){
      listParties();
    }
  );
  return false;
}

function listQueue(){
  $(document).ready(function(){
    $.get("handler.php?action=listVideos&partyId="+partyId,
      function(data,status){
        if (status=="success"){
          var videos= JSON.parse(data);
          //var users=data;
          $("#queueList").html("");
          for (var i=0;i<videos.length;i++){
            var queueRow="<tr><td>"+(i+1).toString()+"</td><td>"+videos[i].title+"</td><td>"+videos[i].username+"</td>";
            if(typeof isAdmin != 'undefined' &&isAdmin==1){
              queueRow=queueRow+"<td>"+
                "<button "+
                  "class='removeVideo button' "+
                  "value='"+videos[i].submissionId+"'>"+
                    "<span class='glyphicon glyphicon-remove'/>"+
                "</button>"+
              "</td>";

            }
            queueRow=queueRow+"</tr>"
            $('#queueList').append(queueRow);
          }
          $('button.removeVideo').click(function(){
            $(this).attr('disabled',1);
            removeVideo($(this).val());
            listQueue();
            if($(this).val()==currentSubmissionId){
              player.stopVideo();
              player.clearVideo();
              loadCurrentVideo();
            }
          });

        }else{
          $("#queueList").html("Failed :(");
        }
      }
    );
  });
}

function loadCurrentVideo(){
  if(typeof loadVideoTimer != 'undefined'){
    clearInterval(loadVideoTimer);
  }
  $(document).ready(function(){
    $.get("handler.php?action=getCurrentVideo&partyId="+partyId,
      function(data,status){
        if (status=="success"){
          var video= JSON.parse(data);
          if(video!=-1){
            $("#youtubePlayerParent").show();
            currentSubmissionId=video.submissionId;
            player.loadVideoById(video.youtubeId, 0);
          }else{
            $("#youtubePlayerParent").hide();
            //swfobject.removeSWF("youtubePlayerParent");

            loadVideoTimer=setTimeout(loadCurrentVideo, 5000);
          }
        }
      }
    );
  });
}

function markVideoWatched(){
  $.post("handler.php", {'action':'markVideoWatched', 'submissionId':currentSubmissionId}, function(data){});
}

function removeVideo(submissionId){
  $.post("handler.php", {'action':'removeVideo', 'submissionId':submissionId}, function(data){});
}

function upVote(submissionId){
  $.post("handler.php", {'action':'vote', 'direction':1, 'submissionId':submissionId}, function(data){});
}

function downVote(submissionId){
  $.post("handler.php", {'action':'vote', 'direction':-1, 'submissionId':submissionId}, function(data){});
}

function unVote(submissionId){
  $.post("handler.php", {'action':'vote', 'direction':0, 'submissionId':submissionId}, function(data){});
}

function submitVideo(youtubeId){
  $.post("handler.php", {'action':'addVideo', 'partyId':partyId, 'youtubeId':youtubeId}, function(data){});
}

function listParties(){
  if($("#joinedList").length >0){
    $.get("handler.php?action=listJoinedParties",
      function(data,status){
        if (status=="success"){
          var parties= JSON.parse(data);
          $("#joinedList").html("");
          for (var i=0;i<parties.length;i++){
            var row="<tr><td>"+parties[i].partyId+"</td><td><a href='/party.php?partyId="+parties[i].partyId+"'>"+parties[i].name+"</a></td><td>"+parties[i].username+"</td>";
            row=row+"</tr>"
            $('#joinedList').append(row);
          }

        }else{
          $("#joinedList").html("Failed :(");
        }
      }
    );
  }
  if($("#unjoinedList").length >0){
    $.get("handler.php?action=listUnjoinedParties",
      function(data,status){
        if (status=="success"){
          var parties= JSON.parse(data);
          $("#unjoinedList").html("");
          for (var i=0;i<parties.length;i++){
            var row="<tr><td>"+parties[i].partyId+"</td><td><a href='/party.php?partyId="+parties[i].partyId+"'>"+parties[i].name+"</a></td><td>"+parties[i].username+"</td>";
            row=row+"</tr>"
            $('#unjoinedList').append(row);
          }

        }else{
          $("#unjoinedList").html("Failed :(");
        }
      }
    );
  }
}

/**@license
This function uses Google Suggest for jQuery plugin (licensed under GPLv3) by Haochi Chen ( http://ihaochi.com )
 */
$.fn.googleSuggest = function(opts){
  opts = $.extend({service: 'web', secure: false}, opts);

  var services = {
    youtube: { client: 'youtube', ds: 'yt' },
    books: { client: 'books', ds: 'bo' },
    products: { client: 'products-cc', ds: 'sh' },
    news: { client: 'news-cc', ds: 'n' },
    images: { client: 'img', ds: 'i' },
    web: { client: 'psy', ds: '' },
    recipes: { client: 'psy', ds: 'r' }
  }, service = services[opts.service];

  opts.source = function(request, response){
    $.ajax({
      url: 'http'+(opts.secure?'s':'')+'://clients1.google.com/complete/search',
      dataType: 'jsonp',
      data: {
        q: request.term,
        nolabels: 't',
        client: service.client,
        ds: service.ds
      },
      success: function(data) {
        response($.map(data[1], function(item){
          return { value: $("<span>").html(item[0]).text() };
        }));
      }
    });
  };
  opts.delay = 50;
  opts.autoFocus = true;
  return this.each(function(){
    $(this).autocomplete(opts);
  });
}

function onYouTubePlayerReady(playerId){
  player = document.getElementById("youtubePlayer");
  player.addEventListener("onStateChange", "playerStateHandler");
  //$("#youtubePlayerParent").hide();
}

function playPause() {
  //Make Sure Player Is Initialized
  if (player) {
    if (player.getPlayerState() == 1){//Playing
      player.pauseVideo();
    }else if (player.getPlayerState() == 0||player.getPlayerState() == 2){//Paused
      player.playVideo();
    }
  }
}

function playerStateHandler(state){
  if (state==-1) {//unstarted
    loadCurrentVideo();
  }else if (state==0){//ended
    markVideoWatched();
    listQueue();
    loadCurrentVideo();
  }else if (state==1){//playing
    $("#playPause").html("<span class='glyphicon glyphicon-pause'></span>");
  }else if(state==2){//paused
    $("#playPause").html("<span class='glyphicon glyphicon-play'></span>");
  }else if(state==3){//buffering
  }else if(state==5){//video cued
  }
}

function setupYouTube(){
  var params = { allowScriptAccess: "always" , allowFullscreen: "true"};
  var atts = { id: "youtubePlayer" };
  //"Chromeless" Player
  //swfobject.embedSWF("http://www.youtube.com/apiplayer/?enablejsapi=1&version=3&playerapiid=youtubePlayerParent",

  swfobject.embedSWF("http://www.youtube.com/v/00000000000?version=3&enablejsapi=1&iv_load_policy=3&autohide=1&showinfo=0",
  "youtubePlayerParent", "100%", "645", "8", null, null, params, atts);

  //add "&modestbranding=1&autohide=1&showinfo=0&controls=0" to remove youtube bars and controls

  /* -- youtube flash object url addition explanations --
     "&iv_load_policy=3" disables annotations
     "&modestbranding=1" makes youtube logo smaller
     "&autohide=1" makes the top and bottom bars go away faster
     "&showinfo=0" removes the top bar
     "&controls=0" removes the bottom bar
  */

}

function fullScreenChangeHandler() {
  var elem = document.getElementById("youtubePlayer");
  if (elem && // if there is a youtube player and the page is in fullscreen mode
      (document.fullScreen == false) ||
      (document.msFullscreenEnabled == true) ||
      (document.mozFullScreen == false) ||
      (document.webkitIsFullScreen == false)
  ) {
    // IE 11 checks
    if (document.msFullscreenElement != null) {
      elem.style.width = screen.width + "px";
      elem.style.height = screen.height + "px";
    }
    else {
      elem.style.width = "100%";
      elem.style.height = "645px";
    }

    // removes listeners
    document.removeEventListener("fullscreenchange", fullScreenChangeHandler, false);
    //document.removeEventListener("MSFullscreenChange", fullScreenChangeHandler, false);
    document.removeEventListener("mozfullscreenchange", fullScreenChangeHandler, false);
    document.removeEventListener("webkitfullscreenchange", fullScreenChangeHandler, false);
  }
}

function fullScreen() {
  var elem = document.getElementById("youtubePlayer");
  var fullScreenPossible = false;
  var fullScreenSupported = true;
  var ieEventListenerExists;

  // Disables external fullscreen button for operating systems on which firefox doesn't work

  if( window.mozInnerScreenX != null &&
      (
        navigator.appVersion.indexOf("Linux") != -1 ||
        navigator.appVersion.indexOf("X11") != -1 ||
        (navigator.appVersion.indexOf("Windows") != -1 && navigator.appVersion.indexOf("Windows") > 5) // Windows 8
      )
  ) {
    fullScreenSupported = false;
    var fullScreenButton = document.getElementById("fullScreen");
    fullScreenButton.className += " disabled";
  }

  if (fullScreenSupported) {
    if (elem.requestFullscreen) {
      elem.requestFullscreen();
      fullScreenPossible = true;
    }
    else if (elem.msRequestFullscreen) {
      elem.msRequestFullscreen();
      fullScreenPossible = true;
    }
    else if (elem.mozRequestFullScreen) {
      elem.mozRequestFullScreen();
      fullScreenPossible = true;
    }
    else if (elem.webkitRequestFullscreen) {
      elem.webkitRequestFullscreen();
      fullScreenPossible = true;
    }

    if (fullScreenPossible) {
      // makes the youtube player the screen's size
      elem.style.width = screen.width + "px";
      elem.style.height = screen.height + "px";

      document.addEventListener("fullscreenchange", fullScreenChangeHandler, false);
      if (ieEventListenerExists != true) {
        document.addEventListener("MSFullscreenChange", fullScreenChangeHandler, false);
        ieEventListenerExists = true;
      }
      document.addEventListener("mozfullscreenchange", fullScreenChangeHandler, false);
      document.addEventListener("webkitfullscreenchange", fullScreenChangeHandler, false);
    }
  }

  else {
    // adds notification that browser/OS combo is not supported
    document.getElementById("disabledFullScreen").style.display = "block";
    $("#disabledFullScreen").children().click(function () { $("#disabledFullScreen").hide(); })
  }
  /* external fullscreen is still partially broken in older versions
     of IE and Safari but should now work in firefox, chrome, IE 11, and Safari 7+.
     because of this leave the normal youtube controls on for now
  */
}

function submitContentToggle() {
  if ($('.submitContent').is(':visible')) {
    $('.submitContent').hide();
    $('.container').css('margin-left', 'auto');
    $('.container').css('width', '100%');
  }
  else {
    $('.submitContent').show();
    $('.container').css('margin-left', 0);
    $('.container').css('width', '50%');
  }
}

$(document).ready(function(){
  $("#searchText").googleSuggest({ service: "youtube" });
  $('#register').submit(register);
  $('#login').submit(login);
  $('#createPartyForm').submit(createParty);
  $('#submitContentToggle').click(submitContentToggle);
  //$("#searchForm").submit(searchYouTube);
  listQueue();
  listParties();
  var listQueueTimer=window.setInterval(listQueue, 5000);
  var currentSubmissionId;
  var loadVideoTimer;
  if ($("#youtubePlayerParent").length > 0){
    setupYouTube();
    $("#playPause").click(playPause);
    $("#fullScreen").click(fullScreen);
  }
});