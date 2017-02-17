<?php
$scriptValues = [];
$sql = "CREATE TABLE `additionalactivity` (
  `id` int(11) NOT NULL,
  `activity_date` date NOT NULL,
  `place` varchar(255) CHARACTER SET utf8 NOT NULL,
  `theme` varchar(255) CHARACTER SET utf8 DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

while(preg_match('#`(.*?)`#',$sql,$matches)) {
	$scriptValues[] = $matches[1];
	$sql = str_replace('`'.$matches[1].'`','',$sql);
	$count++;
}
$file = fopen("oui.php","w") or die ("Unable to open file!");
$txt = "<?php";
fwrite($file, $txt);
$txt = "
abstract class ".$scriptValues[0]."Build
{
	
}
?>";
fwrite($file, $txt);
fclose($file);
var_dump($scriptValues);