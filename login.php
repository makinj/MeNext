<?php
  $title="Login | Register";//to be displayed in tab
  include("header.php");//open html bar
?>
<table class="loginTable">
  <tr>
    <td class="registerLoginContainer">
      <h1 class="loginTitle">Login</h1>
      <div id="problem"></div>
      <div class="formContainer">
        <form id="login">
          <div class="formItem">
            <span class="loginText">Username: </span>
            <input type="text" name="username" id="name">
          </div>

          <div class="formItem">
            <span class="loginText">Password: </span>
            <input type="password" name="password" id="password">
          </div>

          <div class="formItem">
            <input type="submit" name="submit" value="Submit" class="submit">
          </div>

          <input type="hidden" name="action" value='login'>
        </form>
      </div>
    </td>

    <td class="registerLoginContainer">
      <h2 class="loginTitle">Register</h2>
      <div class="formContainer">
        <form id="register">
          <div class="formItem">
            <span class="loginText">Username: </span>
            <input type="text" name="username" id="name">
          </div>

          <div class="formItem">
            <span class="loginText">Password: </span>
            <input type="password" name="password" id="password">
          </div>

          <div class="formItem">
            <input type="submit" name="submit" value="Submit" class="submit">
          </div>

          <input type="hidden" name="action" value='register'>
        </form>
      </div>
    </td>
  </tr>
</table>
<?php
  require_once("footer.php");//closes html
?>
