$(document).ready(function(){
  $('#register').submit(function(){
    $.post("handler.php", $("#register").serialize(),
      function(data){
        var result= JSON.parse(data);
        if(result['token']!=0){
          window.location.href = "/";
        }else{
          if(result['reg']=="alreadyExists"){
            $("#problem").html("username already in use");
          }else{
            $("#problem").html("unable to register user");
          }
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
        }else{
          $("#problem").html("unable to sign in");
        }
      }
    );
    return false;
  });



});