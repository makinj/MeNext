    <script type="text/javascript">
      var API_KEY =
<<<<<<< HEAD
      <?php
        require("includes/constants.php");
=======
      <?php 
        require_once("includes/constants.php");
>>>>>>> 0332500f9fd25d2862ed33f524b994e14a9a7279
        echo"'".$API_CLIENT_KEY."';\n";
        echo "var partyId='";
        if (isset($_GET['partyId'])){
          echo $_GET['partyId'];
        }else{
          echo -1;
        }
        echo "';\n";
      ?>
      searchYouTube();
    </script>

    </div> <!-- .container -->
  </body>
</html>