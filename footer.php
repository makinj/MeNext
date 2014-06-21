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
    </script>

    </div> <!-- .container -->
  </body>
</html>