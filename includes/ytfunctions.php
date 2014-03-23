<?php
  require_once 'Zend/Loader.php'; // the Zend dir must be in your include_path
  Zend_Loader::loadClass('Zend_Gdata_YouTube');
  if (isset($_GET['action'])){
    if($_GET['action']=="search"){
      searchAndPrint($_GET['search']);
    }
  }
  function searchAndPrint($searchTerms){
    $yt = new Zend_Gdata_YouTube();
    $yt->setMajorProtocolVersion(2);
    $query = $yt->newVideoQuery();
    $query->setOrderBy('relevance');
    $query->setSafeSearch('moderate');
    $query->setVideoQuery($searchTerms);

    // Note that we need to pass the version number to the query URL function
    // to ensure backward compatibility with version 1 of the API.
    $videoFeed = $yt->getVideoFeed($query->getQueryUrl(2));
    $result = array();
    /*
    result is an array of arrays with the values:
    1. Video Title
    2. Video ID ie.https://www.youtube.com/watch?v=WSMNbbg0X7Q
                                                   ^^^^^^^^^^^
                                                  this is the ID
    3. Top Video Thumbnail URL
    */
    foreach ($videoFeed as $videoEntry) {//fill up array to return to JS
      $videoThumbnails = $videoEntry->getVideoThumbnails();
      $videotmp=array($videoEntry->getVideoTitle(), $videoEntry->getVideoId(), $videoThumbnails[1]['url']);
      array_push($result, $videotmp);
    }
    echo json_encode($result);
  }  
?>