$(document).ready(function(){
  $('#register').submit(function(){
    $.post("handler.php", $("#register").serialize(),
      function(data){
        var result= JSON.parse(data);
        if(result['token']!=0){
          window.location.href = "/";
        }
      }
    );
    return false;
  });

  $('#login').submit(function(){
    $.post("handler.php", $("#login").serialize(),
      function(data){
        var result= JSON.parse(data);
        if(result['token']!=0){
          window.location.href = "/";
        }
      }
    );
    return false;
  });



});