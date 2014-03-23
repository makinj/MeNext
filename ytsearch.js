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
        var videos= JSON.parse(xmlhttp.responseText)
        for (var i=0;i<videos.length;i++){
          //var txt = document.createTextNode("<a href='https://www.youtube.com/watch?v="+videos[i][1]+"'><li>"+i+"<img src='"+videos[i][2]+"'/>"+videos[i][0]+"</li></a>");
          
          var link=document.createElement("LI");
          //var position=i+1;
          link.innerHTML=i+"<a href='https://www.youtube.com/watch?v="+videos[i][1]+"'>"+"<img src='"+videos[i][2]+"'/>"+videos[i][0]+"</a>";
//document.body.appendChild(link);


          //document.getElementById("list").innerHTML="good";
          document.getElementById('list').appendChild(link); 
          //document.write(videos[i] + "<br>");
        }
        //document.getElementById("list").innerHTML=xmlhttp.responseText;
      }else{
        document.getElementById("list").innerHTML="Loading...";
      }
    }
    xmlhttp.open("GET","includes/ytfunctions.php?action=search&search="+search_term,true);
    xmlhttp.send();
  }
  return false;
}