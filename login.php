<?php
$title = "Login | Register"; //to be displayed in tab
include("header.php"); //open html bar
?>
<div class="jumbotron">
    <div class="row">
        <div class="col-md-6">
            <h2>Register</h2>

            <div id="register_problem"></div>
            <form role="form" id="register">
                <div class="form-group">
                    <input type="email" class="form-control" name="email" id="email" placeholder="Email Address">
                </div>

                <div class="form-group">
                    <input type="text" class="form-control" id="name" name="username" placeholder="Username">
                </div>

                <div class="form-group">
                    <input type="password" class="form-control" name="password" id="password" placeholder="Password">
                </div>

                <div class="form-group">
                    <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="Confirm Password">
                </div>

                <button type="submit" name="submit" class="btn btn-primary pull-right">Create Account</button>

                <input type="hidden" name="action" value='register'>
            </form>
        </div>
        <div class="col-md-6">
            <h2>Login</h2>
            <div id="login_problem"></div>
            <form role="form" id="login">
                <div class="form-group">
                    <input type="text" class="form-control" id="name" name="username" placeholder="Username">
                </div>

                <div class="form-group">
                    <input type="password" class="form-control" name="password" id="password" placeholder="Password">
                </div>

                <button type="submit" name="submit" class="btn btn-primary pull-right">Login</button>

                <input type="hidden" name="action" value='login'>
            </form>
        </div>
    </div>
</div>
<?php
require_once("footer.php"); //closes html
?>
