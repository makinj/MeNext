function searchVideo(){
  /*
  Joshua Makinen
  Calls AJAX to retreive search result
  */
  var API_KEY = 'AIzaSyCIDavzeJA_rfK90XD3O2o5JRyIMOFyUvM';
  var search_term=document.getElementById("search").value;
  if (search_term.length==0){
    document.getElementById("list").innerHTML="<h5>Enter video to search</h5>";
    return false;
  }else{
    var xmlhttp=new XMLHttpRequest();
    xmlhttp.onreadystatechange=function(){
      if (xmlhttp.readyState==4 && xmlhttp.status==200){
        document.getElementById("list").innerHTML="";
        alert(xmlhttp.responseText);
        
        var videos= JSON.parse(xmlhttp.responseText).items;
        
        for (var i=0;i<videos.length;i++){
          //var txt = document.createTextNode("<a href='https://www.youtube.com/watch?v="+videos[i][1]+"'><li>"+i+"<img src='"+videos[i][2]+"'/>"+videos[i][0]+"</li></a>");
          
          var link=document.createElement("LI");
          //var position=i+1;
          link.innerHTML=(i+1).toString()+"<a href='https://www.youtube.com/watch?v="+videos[i].id.videoId+"'>"+"<img src='"+videos[i].snippet.thumbnails.default.url+"'/>"+videos[i].snippet.title+"</a>";
//document.body.appendChild(link);


          //document.getElementById("list").innerHTML="good";
          document.getElementById('list').appendChild(link); 
          //document.write(videos[i] + "<br>");
        }
        //document.getElementById("list").innerHTML=xmlhttp.responseText;
        
        //document.getElementById("list").innerHTML="done";

      }else{
        document.getElementById("list").innerHTML="Loading...";
      }
    }
    xmlhttp.open("GET","https://www.googleapis.com/youtube/v3/search?part=snippet&order=relevance&maxResults=25&q="+search_term+"&key="+API_KEY,true);

    //xmlhttp.open("GET","includes/ytfunctions.php?action=search&search="+search_term,true);
    xmlhttp.send();
  }
  return false;
}