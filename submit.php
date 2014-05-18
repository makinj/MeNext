
<?php
  $restricted=true;//only show if logged in
  $title="submit";
  require_once('header.php');//echo top of html
?>

<div class="panel panel-default">
  <div class="panel-heading">Search Results</div>
  <table class="table">
    <thead>
      <tr>
        <th>#</th>
        <th>Add</th>
        <th>Thumbnail</th>
        <th>Title</th>
      </tr>
    </thead>
    <tbody id="searchResults">
    </tbody>
    
  </table>
</div>


<script type="text/javascript" src="/js/common.js"></script>
<?php
  require_once('footer.php');//close up html
?>