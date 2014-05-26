<html>
	<head>
	<link rel="stylesheet" href="css/Makers.css" type="text/css" />
	<script>
document.onkeydown = function(evt) {
    evt = evt || window.event;
    if (evt.keyCode == 39) {
	projnum = location.href.match(/project=(\d*)/)[1];
	next = parseInt(projnum) + 1;
	window.location = location.href.replace(projnum, next);
    }
    if (evt.keyCode == 37) {
	projnum = location.href.match(/project=(\d*)/)[1];
	prev = parseInt(projnum) - 1;
	window.location = location.href.replace(projnum, prev);
    }
};
</script>

	</head>
<body>

<?php
#ini_set('display_errors', 1);

  function get_bitly_short_url($url) {
          $token = 'b6181508eac928fa86d9db23fb66f0da89dec8a4';
	  $connectURL = 'https://api-ssl.bitly.com/v3/shorten?access_token='.$token.'&longUrl='.urlencode($url).'&format=txt';
	  return file_get_contents($connectURL);
  }

  include "key.php";

  `mkdir -p csvcache`;


  if (!$spreadsheet_key) {
	fprintf(STDERR, "\$spreadsheet_key must be defined in key.php!\n");
	exit;
	
  }
  // add the compact=yes param to use small images
  $compact = $_GET["compact"] == "yes";  

  // add the expire=yes param to clear the cache immediately
  $expire_now = $_GET["expire"] == "yes";  

  // Specify a category with category=Crafts for example
  $chosen_cat = $_GET["category"];

  // Specify a category with project=modkit for example
  $chosen_project = $_GET["project"];

  // Cache the Google Spreadsheet so we're not hitting Google
  // all the time.
  $cachefile = "csvcache/spreadsheet-cached-2010-$chosen_cat$chosen_project.csv";
  $cachetime = 5 * 60; // 5 minutes

  if ($expire_now || 
      !file_exists($cachefile)  ||
      filesize($cachefile) == 0 ||
      time() - $cachetime >= filemtime($cachefile)
     ) 
  {

    $local = fopen($cachefile, 'w');
    $remote = fopen(
      'http://spreadsheets.google.com/pub?key=' . $spreadsheet_key . '&output=csv&gid=0', "r"); 

    // skip first row
    fgets($remote);

    // Copy the CSV spreadsheet to the local file
    //
    while ( ( $data = fgets($remote) ) !== false ) {
      fwrite($local, $data);
    }
    fclose($remote);
    fclose($local);
  } 

  $byproject = array();  // Hash of projects

  // Open the cache file for reading
  $handle = fopen($cachefile, "r");

  // Store each row in the byproject array
  while ( ( $data = fgetcsv($handle) ) !== false ) {

    $projectname = $data[4];
    $approved = $data[15];

    // If a category was specified, only store matching projects.
    //
    $category    = $data[6];

    // If a category was specified, only store matching projects.
    //
    $project_id    = $data[18];

    if ($approved =="Y" && 
        ($chosen_cat == "" || $category == $chosen_cat) &&
        ($chosen_project == "" || $project_id == $chosen_project)) {
      // store the row in the byproject array
      $byproject[$projectname] = $data;
    }  
  }
  fclose($handle); 

  // Sort and display
  //ksort($byproject);
  uksort($byproject, 'strcasecmp');

  foreach($byproject as $key => $value) { 

    $sales = "";
    if ($value[13] == "Yes" && $chosen_project) {
      $sales = "<strong>For sale:</strong> $value[14]<br/>";
    }

	$qr_url = "";
	if ($value[18]) { // Got a maker #
		$qr_url =
			#"http://chart.apis.google.com/chart?cht=qr&chs=220x220&chl=http%3A//makerfaireri.com/mini-maker-faire-ri-2012/2012-makers/%3Fproject%3D$value[18]";
			"http://chart.apis.google.com/chart?cht=qr&chs=220x220&chl=" . urlencode($value[8]);
	}
	
	$maker_title = "";
	if ( $value[1] == $value[4]) {
		$maker_title = $value[1];
	} else {
		$maker_title = $value[1] . ": " . $value[4];
	}

	$desc = $value[5];
	$desc1_style = "description";
	$desc2 = "";

        $desc = preg_replace('/• /', '<ul><li>', $desc, 1);
        $desc = preg_replace('/• /', '<li>', $desc);
	if (strlen($desc) < 220) {
		$desc1_style = "description-padded";
	}
	if (strlen($desc) > 350) {
		$array = explode('.',$desc);
		$desc = array_shift($array) . ".";
		$desc2 = join(".", $array);
        }
	$url = get_bitly_short_url($value[8]);

?>

<div class="maker">
        <div class="flags">&nbsp;</div>
	
<!-- <img src="<?php echo $value[7] ?>"  border="0" alt="" class="makerimg"/> -->

<div class="title"><?php echo $value[4] ?></div>
<div class="<?php echo $desc1_style?>"><?php echo $desc ?></div> 
<div class="description2"><?php echo $desc2 ?></div> 


<div class="footer-container">
	<div class="name"><?php echo $value[1] ?></div>
	<div class="url"><a href="<?php echo $url ?>"><?php echo$url ?></a></div>
        <div class="flags">&nbsp;</div>
	<div class="row">
		<img class="footer-graphic" src="<?php echo $qr_url ?>"/>
		<img class="footer-graphic" src="figs/RhodeIsland_MMF<?php if ($compact) { echo "_sm"; } ?>.png"/>
		<!-- <div class="right"><img class="footer-graphic" src="figs/as220<?php if ($compact) { echo "_sm"; } ?>.png"/></div> -->
	</div>
</div>
</div>

<?php }
?>
	</div>
	</body></html>
