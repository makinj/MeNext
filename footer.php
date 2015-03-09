<?php
  require_once("includes/constants.php");
  require_once("includes/functions.php");
?>
    <script type="text/javascript">
      var YT_API_KEY =
      <?php
        echo"'".YT_API_CLIENT_KEY."';\n";
      ?>
      var WSDOMAIN =
      <?php
        echo"'".WSDOMAIN."';\n";
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
        echo"'".$user->userId."';\n";
      ?>
    </script>

    </div> <!-- .container -->

  </body>
</html>