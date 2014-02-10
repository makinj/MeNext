<?php
  require_once 'Zend/Loader.php'; // the Zend dir must be in your include_path
  Zend_Loader::loadClass('Zend_Gdata_YouTube');
  $yt = new Zend_Gdata_YouTube();

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
    printVideoFeed($videoFeed, 'Search results for: ' . $searchTerms);
  }
  function printVideoFeed($videoFeed)
  {
    $count = 1;
    foreach ($videoFeed as $videoEntry) {
      echo "Entry # " . $count . "<br>";
      printVideoEntry($videoEntry);
      echo "<br>";
      $count++;
    }
  }
  function printVideoEntry($videoEntry) 
  {
    // the videoEntry object contains many helper functions
    // that access the underlying mediaGroup object
    echo 'Video: ' . $videoEntry->getVideoTitle() . "<br>";
    echo "<object width='425' height='350' data='http://www.youtube.com/v/".$videoEntry->getVideoId()."' type='application/x-shockwave-flash'><param name='src' value='http://www.youtube.com/v/' /></object>";
    echo 'Video ID: ' . $videoEntry->getVideoId() . "<br>";
    echo 'Updated: ' . $videoEntry->getUpdated() . "<br>";
    echo 'Description: ' . $videoEntry->getVideoDescription() . "<br>";
    echo 'Category: ' . $videoEntry->getVideoCategory() . "<br>";
    echo 'Tags: ' . implode(", ", $videoEntry->getVideoTags()) . "<br>";
    echo 'Watch page: ' . $videoEntry->getVideoWatchPageUrl() . "<br>";
    echo 'Flash Player Url: ' . $videoEntry->getFlashPlayerUrl() . "<br>";
    echo 'Duration: ' . $videoEntry->getVideoDuration() . "<br>";
    echo 'View count: ' . $videoEntry->getVideoViewCount() . "<br>";
    echo 'Rating: ' . $videoEntry->getVideoRatingInfo() . "<br>";
    echo 'Recorded on: ' . $videoEntry->getVideoRecorded() . "<br>";
    
    // see the paragraph above this function for more information on the 
    // 'mediaGroup' object. in the following code, we use the mediaGroup
    // object directly to retrieve its 'Mobile RSTP link' child
    foreach ($videoEntry->mediaGroup->content as $content) {
      if ($content->type === "video/3gpp") {
        echo 'Mobile RTSP link: ' . $content->url . "<br>";
      }
    }
    
    echo "Thumbnails:<br>";
    $videoThumbnails = $videoEntry->getVideoThumbnails();

    foreach($videoThumbnails as $videoThumbnail) {
      echo $videoThumbnail['time'] . ' - ' . $videoThumbnail['url'];
      echo ' height=' . $videoThumbnail['height'];
      echo ' width=' . $videoThumbnail['width'] . "<br>";
    }
  }
?>