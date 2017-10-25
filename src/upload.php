<?php

/* -----------------------------------------
   Upload a file and/or load geographical GeoJSON data if applicable
----------------------------------------- */
header('Content-type: application/json');
    $lat = '';
    $lng = '';
$map = (isset($_POST['latlng'])) ? $_POST['latlng'] : '';
if (isset($_FILES['files']) && !empty($_FILES['files'])) {
    $no_files = count($_FILES["files"]['name']);
    if (!is_dir("uploads/")) {
    	mkdir("uploads/",0777,true);
    }
    for ($i = 0; $i < $no_files; $i++) {
        if ($_FILES["files"]["error"][$i] > 0) {
            echo "Error: " . $_FILES["files"]["error"][$i] . "<br>";
        } else {
            if (file_exists('uploads/'. $_FILES["files"]["name"][$i])) {
                echo 'File already exists : ../uploads/'. $_FILES["files"]["name"][$i];
            } else {
		            $path_info = pathinfo($_FILES["files"]["name"][$i]);
                if (move_uploaded_file($_FILES["files"]["tmp_name"][$i], 'uploads/'. $_FILES["files"]["name"][$i])) {

                  // Check if the image is geotagged
                  $info = exif_read_data('uploads/'. $_FILES["files"]["name"][$i]);
                    if (isset($info['GPSLatitude']) && isset($info['GPSLongitude']) && isset($info['GPSLatitudeRef']) && isset($info['GPSLongitudeRef']) &&
                  in_array($info['GPSLatitudeRef'], array('E','W','N','S')) && in_array($info['GPSLongitudeRef'], array('E','W','N','S'))) {

                      $GPSLatitudeRef  = strtolower(trim($info['GPSLatitudeRef']));
                      $GPSLongitudeRef = strtolower(trim($info['GPSLongitudeRef']));

                      $lat_degrees_a = explode('/',$info['GPSLatitude'][0]);
                      $lat_minutes_a = explode('/',$info['GPSLatitude'][1]);
                      $lat_seconds_a = explode('/',$info['GPSLatitude'][2]);
                      $lng_degrees_a = explode('/',$info['GPSLongitude'][0]);
                      $lng_minutes_a = explode('/',$info['GPSLongitude'][1]);
                      $lng_seconds_a = explode('/',$info['GPSLongitude'][2]);

                      $lat_degrees = $lat_degrees_a[0] / $lat_degrees_a[1];
                      $lat_minutes = $lat_minutes_a[0] / $lat_minutes_a[1];
                      $lat_seconds = $lat_seconds_a[0] / $lat_seconds_a[1];
                      $lng_degrees = $lng_degrees_a[0] / $lng_degrees_a[1];
                      $lng_minutes = $lng_minutes_a[0] / $lng_minutes_a[1];
                      $lng_seconds = $lng_seconds_a[0] / $lng_seconds_a[1];

                      $lat = (float) $lat_degrees+((($lat_minutes*60)+($lat_seconds))/3600);
                      $lng = (float) $lng_degrees+((($lng_minutes*60)+($lng_seconds))/3600);
                        //    $miss = Mission::search($_GET['mid']);
              //If the latitude is South, make it negative.
              //If the longitude is west, make it negative
                      $GPSLatitudeRef  == 's' ? $lat *= -1 : '';
                      $GPSLongitudeRef == 'w' ? $lng *= -1 : '';
                      if ($lat != '' && $lng != '') {
                            $feature = array(
                                  'type' => 'Feature',
                                  'geometry' => array(
                                  'type' => 'Point',
                                      'coordinates' => array($lng, $lat)
                                   ),
                                  'properties' => array(
                                      'src' => htmlspecialchars_decode($_FILES["files"]["name"][$i])
                                   )
                            );
                            $tempArray = json_decode(htmlspecialchars_decode($map), true);
                            array_push($tempArray['features'], $feature);

                            $map = json_encode($tempArray);



                     }
                 }
		            }
            }
        }
    }

	$file = "uploads/file.zip";
	if(file_exists($file)){
	    unlink($file);
	}
	// Get real path for our folder
	$rootPath = realpath('uploads/');

	// Initialize archive object
	$zip = new ZipArchive();
	$zip->open($rootPath . '/file.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

	// Create recursive directory iterator
	$files = new RecursiveIteratorIterator(
	    new RecursiveDirectoryIterator($rootPath),
	    RecursiveIteratorIterator::LEAVES_ONLY
	);

	foreach ($files as $name => $file) {   // Skip directories (they would be added automatically)
    		if (!$file->isDir())    {        // Get real and relative path for current file
        		$filePath = $file->getRealPath();
        		$relativePath = substr($filePath, strlen($rootPath) + 1);

        		// Add current file to archive
        		$zip->addFile($filePath, $relativePath);
    		}
	}
                            echo $map;
	// Zip archive will be created only after closing object
	$zip->close();
} else {
    echo 'Please choose at least one file';
}
?>
