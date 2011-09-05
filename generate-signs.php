<html>
	<head>
	<link rel="stylesheet" href="css/Makers.css" type="text/css" />
	</head>
<body>

<?php

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

	if ($value[18]) { // Got a maker #
        `qrencode -m 0 -s 7 -o figs/QR$value[18].png http://makerfaireri.com/home/makers/?project=$value[18]`;
	}

?>

<div class="maker">
	<div class="container">
		<div class="row">
			<div class="left"><img class="heading-graphic" src="figs/logo_complete.png"/></div>
			<div class="right"><img class="heading-graphic" src="figs/maker.png"/></div>
		</div>
	</div>
	
	
<img src="<?php echo $value[7] ?>"  border="0" alt="" class="makerimg"/>

<div class="title"><?php echo $value[1] ?>: <?php echo $value[4] ?></div>
<div class="description"><?php echo $value[5] ?></div> 
<div class="url"><?php echo $value[8] ?></div>
</div>

<div class="footer-container">
	<div class="row">
		<div class="middle"><img class="heading-graphic" src="<?php echo "figs/QR$value[18].png" ?>"/></div>
		<div class="middle"><img class="heading-graphic" src="figs/RhodeIsland_MMF<?php if ($compact) { echo "_sm"; } ?>.jpg"/></div>
		<div class="right"><img class="heading-graphic" src="figs/WF Square wTEXT<?php if ($compact) { echo "_sm"; } ?>.jpg"/></div>
	</div>
</div>

<?php }
?>
	</div>
	</body></html>
