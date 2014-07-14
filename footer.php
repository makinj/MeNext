<?php
  require_once("includes/constants.php");
  require_once("includes/functions.php");
  if(session_id() == '') {
    session_start();
  }
?>
    <script type="text/javascript">
      var API_KEY =
      <?php
        echo"'".API_CLIENT_KEY."';\n";
      ?>

      var partyId =
      <?php
        if (isset($_GET['partyId'])){
          echo "'".$_GET['partyId']."'";
        }else{
          echo -1;
        }
        echo ";\n";
      ?>

      var userId =
      <?php
        echo"'".$_SESSION['userId']."';\n";
      ?>
    </script>

    </div> <!-- .container -->
  </body>
</html>