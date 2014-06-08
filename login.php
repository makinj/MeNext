<?php
  require_once("class.DB.php");//database funcitonality 
  $title="Login | Register";//to be displayed in tab
  include("header.php");//open html bar
?>
<table class="login-table">
  <tr>
    <td class="reglog-item">
      <h1 class="login-title">Login</h1>
      <div id="problem"></div>
      <form id="login">
        <div class="form-item">
          <span class="login-text">Username: </span><input type="text" name="username" id="name">
        </div>

        <div class="form-item">
          <span class="login-text">Password: </span><input type="password" name="password" id="password">
        </div>

        <input type="submit" name="submit" value="Submit" class="submit">
        
        <input type="hidden" name="action" value='login'>
      </form>
    </td>

    <td class="reglog-item">
      <h2 class="login-title">Register</h2>
      <form id="register">
        <div class="form-item">
          <span class="login-text">Username: </span><input type="text" name="username" id="name">
        </div>

        <div class="form-item">
          <span class="login-text">Password: </span><input type="password" name="password" id="password">
        </div>

        <input type="submit" name="submit" value="Submit" class="submit">

        <input type="hidden" name="action" value='register'>
      </form>
    </td>
  </tr>
</table>
<?php
  require_once("footer.php");//closes html
?>
