<?php
$db_host = 'localhost';
$db_user = 'root';
$db_pwd = 'gehein';

$database6 = 'drupal6';
$database7 = 'drupal7';
//the table on which to operate field_revision_body and field_data_body
//$table = 'field_revision_body';
$table = 'field_data_body';

$db7 = new mysqli($db_host, $db_user, $db_pwd, $database7);
if ($db7->connect_error) {
	die("Can't connect to database");
}	

$db6 = new mysqli($db_host, $db_user, $db_pwd, $database6);
if ($db6->connect_error) {
	die("Can't connect to database");
}

// sending query
//$result = mysql_query("SELECT * FROM {$table}");

$articles = $db7->query("SELECT * FROM {$table} where bundle = 'article' ");

if (!$articles) {
	die("Query to show fields from table failed");
}

while($article = $articles->fetch_array())	{

		$body_value = $article["body_value"];
		$pos_start = strpos($body_value, '[[wysiwyg_imageupload:');
		if ($pos_start === false) {
			continue;
		}
		echo $article["entity_id"], "\n";
		$pos_end = strpos($body_value, ':]]');
		//$text = substr($body_value, $pos_start, $pos_end - $pos_start -1);
		$start = $pos_start + strlen('[[wysiwyg_imageupload:');
		$laenge = $pos_end - $start; 
		$number = substr($body_value, $start, $laenge );
		$front = substr($body_value, 0, $pos_start);
		$back = substr($body_value, $pos_end + strlen(':]]'), strlen($body_value) );
		//get the file name from the old number in d6
		$image_files = mysqli_query($db6, "select ii.fid, ii.iid, f.filename, f.filepath from wysiwyg_imageupload_entity ii inner join files f on ii.fid = f.fid where ii.iid = {$number};");				
		if (!$image_files) {
			echo "iid not found!";
			continue;
		}
		$image_file = mysqli_fetch_array($image_files);
// 		print_r ($image_file); 
		$file_name = $image_file["filename"];
		//having the filename from d6 get the new number from d7
		$files_managed = mysqli_query($db7, "select * from file_managed where filename='{$file_name}';");
		//$result = mysqli_query($db7, "select * from file_managed where filename='yongnuo-rf603-300x300.jpg';");
		$file_managed = mysqli_fetch_array($files_managed);
		$new_number = $file_managed["fid"];
// 		print_r ($file_managed);
		$new_body_value = $front . '[[{"type":"media","view_mode":"media_large","fid":"' . $new_number . '","attributes":{"alt":"","class":"media-image","height":"480","typeof":"foaf:Image","width":"480"}}]]' . $back;
// 		echo " $new_body_value \n";
// 		echo " $body_value \n";
		if ($stmt = $db7->prepare("update {$table} set body_value = ? where entity_id = ? and revision_id = ? and language = ?;")) {
			$stmt->bind_param("siis", $new_body_value, $article["entity_id"], $article["revision_id"], $article["language"]);
			if (!$stmt->execute()) {
				echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
			}
			$stmt->close();
		}
		//print_r($article);
		//echo "update {$table} set body_value = {$new_body_value} where entity_id = {$file_managed["entity_id"]} and revision_id = {$file_managed["revision_id"]} and language = {$file_managed["language"]};";
		//$result = mysqli_query($db7, "update {$table} set body_value = {$new_body_value} where entity_id = {$file_managed["entity_id"]} and revision_id = {$file_managed["revision_id"]} and language = {$file_managed["language"]};");
}

//$articles->close;
$db7->close();
$db6->close();
?>
