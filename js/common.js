function register(){
  $.post("handler.php", $("#register").serialize(),
    function(data){
      var result= JSON.parse(data);
      if(result['token']!=0){
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
      if(result['token']!=0){
        window.location.href = "/";
      }else{
        $("#problem").html("unable to sign in");
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
          submitVideo($(this).val());
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
      "<td><button class='addVideo' value='"+videos[i].id.videoId+"'>Add</button></td>"+
      "<td><img src='"+videos[i].snippet.thumbnails.default.url+"'/></td>"+
      "<td>"+videos[i].snippet.title+"</td>"+
      "<td>"+videos[i].snippet.description+"</td>"+
      "</tr>"); 
  } 
}

function listQueue(){
  $(document).ready(function(){
    $.get("handler.php?action=listVideos",        
      function(data,status){
        if (status=="success"){
          
          var videos= JSON.parse(data);
          //var users=data;         
          $("#queueList").html("");
          for (var i=0;i<videos.length;i++){
            $('#queueList').append("<tr><td>"+(i+1).toString()+"</td><td>"+videos[i].title+"</td></tr>");
          }
          
        }else{
          $("#queueList").html("Failed :(");
        }
      }
    );
  });
}
      


function submitVideo(youtubeId){
  $.post("handler.php", {'action':'addVideo', 'youtubeId':youtubeId}, function(data){});
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
    player.loadVideoById('FT7MWDoW_rc', 15);
}

$("#searchText").googleSuggest({ service: "youtube" });
$(document).ready(function(){
  $('#register').submit(register);
  $('#login').submit(login);
  //$("#searchForm").submit(searchYouTube);
  listQueue();
  window.setInterval(listQueue, 5000);
  if ($("#youtubePlayer").length > 0){
    var params = { allowScriptAccess: "always" };
    var atts = { id: "youtubePlayer" };

    //"Chromeless" Player
    swfobject.embedSWF("http://www.youtube.com/apiplayer/?enablejsapi=1&version=3&playerapiid=youtubePlayer",
    "youtubePlayer", "560", "315", "8", null, null, params, atts);
  }
});