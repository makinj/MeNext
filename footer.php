    <script type="text/javascript">
      var partyId = 1;
      var API_KEY =
      <?php 
        require_once("includes/constants.php");
        echo"'".$API_CLIENT_KEY."';\n";
        if (isset($_GET['q'])){
          echo "$('#searchText').val('".addslashes($_GET['q'])."');\n";
        }
      ?>
      searchYouTube();
    </script>

    </div> <!-- .container -->
  </body>
</html>