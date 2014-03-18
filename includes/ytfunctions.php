<?php
  require_once 'Zend/Loader.php'; // the Zend dir must be in your include_path
  Zend_Loader::loadClass('Zend_Gdata_YouTube');
  if (isset($_GET['action'])){
    if($_GET['action']=="search"){
      searchAndPrint($_GET['search']);
    }
  }
  function searchAndPrint($searchTerms)
  {
    $yt = new Zend_Gdata_YouTube();
    $yt->setMajorProtocolVersion(2);
    $query = $yt->newVideoQuery();
    $query->setOrderBy('viewCount');
    $query->setSafeSearch('none');
    $query->setVideoQuery($searchTerms);

    // Note that we need to pass the version number to the query URL function
    // to ensure backward compatibility with version 1 of the API.
    $videoFeed = $yt->getVideoFeed($query->getQueryUrl(2));
    $count = 1;
    foreach ($videoFeed as $videoEntry) {
      $videoThumbnails = $videoEntry->getVideoThumbnails();
      echo"<li>".$count.". <img src='".$videoThumbnails[1]['url']."'/> ". $videoEntry->getVideoTitle()."</li>";
      $count++;
    }
  }  
?>