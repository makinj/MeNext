    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="js/bootstrap.min.js"></script>

    <script src="http://code.jquery.com/jquery-1.11.0.min.js"></script>
    <script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1/jquery-ui.min.js" type="text/javascript"></script>
    <script src="/js/common.js" type="text/javascript"></script>
    <script type="text/javascript">
      var API_KEY =
      <?php
        require("includes/constants.php");
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
    </div>
  </body>
</html>