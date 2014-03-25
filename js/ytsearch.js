function searchVideo(){
  /*
  Joshua Makinen
  Calls AJAX to retreive search result
  */
  var search_term=document.getElementById("search").value;
  if (search_term.length==0){
    document.getElementById("list").innerHTML="<h5>Enter video to search</h5>";
    return false;
  }else{
    var xmlhttp=new XMLHttpRequest();
    xmlhttp.onreadystatechange=function(){
      if (xmlhttp.readyState==4 && xmlhttp.status==200){
        document.getElementById("list").innerHTML="";       
        var videos= JSON.parse(xmlhttp.responseText).items;
        for (var i=0;i<videos.length;i++){
          var link=document.createElement("LI");
          link.innerHTML=(i+1).toString()+"<a href='https://www.youtube.com/watch?v="+videos[i].id.videoId+"'>"+"<img src='"+videos[i].snippet.thumbnails.default.url+"'/>"+videos[i].snippet.title+"</a>";
          document.getElementById('list').appendChild(link); 
        }
      }else{
        document.getElementById("list").innerHTML="Loading...";
      }
    }
    xmlhttp.open("GET","https://www.googleapis.com/youtube/v3/search?part=snippet&order=relevance&maxResults=25&q="+search_term+"&key="+API_KEY,true);

    xmlhttp.send();
  }
  return false;
}