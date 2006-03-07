<?
//====================================================================================
// OCS INVENTORY REPORTS
// Copyleft Pierre LEMMET 2005
// Web: http://ocsinventory.sourceforge.net
//
// This code is open source and may be copied and modified as long as the source
// code is always made freely available.
// Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
//====================================================================================
//Modified on 11/25/2005
set_time_limit(0); 
error_reporting(E_ALL & ~E_NOTICE);
?>
<html>
<head>
<TITLE>OCS Inventory Installation</TITLE>
<LINK REL='StyleSheet' TYPE='text/css' HREF='css/ocsreports.css'>
</head><body>

<?
printEnTeteInstall("OCS Inventory Installation");

if( isset($fromAuto) && $fromAuto==true)
echo "<center><br><font color='green'><b>Current installed version ".$valUpd["tvalue"]." is lower than this version (".GUI_VER.") automatic install launched</b></red><br></center>";


if(!isset($_POST["name"])) {
	if( $hnd = @fopen("dbconfig.inc.php", "r") ) {
		fclose($hnd);
		include("dbconfig.inc.php");
		$_POST["name"] = $_SESSION["COMPTE_BASE"];
		$_POST["pass"] = $_SESSION["PSWD_BASE"];
		$_POST["host"] = $_SESSION["SERVEUR_SQL"];
	}
	else {
		$_POST["name"] = "root";
		$_POST["pass"] = "";
		$_POST["host"] = "localhost";
	}
	$firstAttempt=true;
}

if(!function_exists('session_start')) {	
	echo "<br><center><font color=red><b>ERROR: Sessions for PHP is not properly installed.<br>Try installing the php4-session package.</b></font></center>";
	die();
}

if(!function_exists('xml_parser_create')) {	
	echo "<br><center><font color=orange><b>WARNING: XML for PHP is not properly installed, you will not be able to use ipdiscover-util.</b></font></center>";
}

if(!function_exists('mysql_connect')) {	
	echo "<br><center><font color=red><b>ERROR: MySql for PHP is not properly installed.<br>Try installing mysql for php package (Debian: php4-mysql)</b></font></center>";
	die();
}

if(!function_exists('zip_read')) {	
	echo "<br><center><font color=orange><b>WARNING: Zip for PHP is not properly installed.<br>You will not be able to upload windows agents<br>Try uncommenting \";extension=php_zip.dll\" (windows) by removing the semicolon in file php.ini, or try installing the php4-zip package (Linux).</b></font></center>";
}

include ('fichierConf.class.php');
$l = new FichierConf("english"); // on cr�e l'instance pour avoir les mots dans la langue choisie
if( (!$link=@mysql_connect($_POST["host"],$_POST["name"],$_POST["pass"]))) {
$firstAttempt=false;
echo "<br><center><font color=red><b>ERROR: ".$l->g(249)." (host=".$_POST["host"]." name=".$_POST["name"]." pass=".$_POST["pass"].")<br>
	Mysql error: ".mysql_error()."</b></font></center>
	<br>
	<form name='fsub' action='install.php' method='POST'><table width='100%'>
	<tr>
		<td align='right' width='50%'>
			<font face='Verdana' size='-1'>".$l->g(247)." :&nbsp;&nbsp;&nbsp;&nbsp;</font>
		</td>
		<td width='50%' align='left'><input size=40 name='name'>
		</td>
	</tr>
	<tr>
		<td align='right' width='50%'>
			<font face='Verdana' size='-1'>".$l->g(248)." :&nbsp;&nbsp;&nbsp;&nbsp;</font>
		</td>
		<td width='50%' align='left'><input size=40 type='password' name='pass'>
		</td>
	</tr>
	<tr>
		<td align='right' width='50%'>
			<font face='Verdana' size='-1'>".$l->g(250)." :&nbsp;&nbsp;&nbsp;&nbsp;</font>
		</td>
		<td width='50%' align='left'><input size=40 name='host'>
		</td>
	</tr>
	<tr><td>&nbsp;</td></tr>
		<tr>
		<td colspan='2' align='center'>
			<input class='bouton' name='enre' type='submit' value=".$l->g(13)."> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		</td>
	</tr>
	
	</table></form>";
	die();
}

if($firstAttempt==true && $_POST["pass"] == "") {
	echo "<br><center><font color=orange><b>WARNING: your the default root password is set on your mysql server. Change it asap. (using root password=blank)</b></font></center>";
}

if(!mysql_query("set global max_allowed_packet=2097152;")) {
	echo "<br><center><font color=orange><b>WARNING: The user you typed does not seem to be root<br>If you encounter any problem with files insertion, try setting the global max_allowed_packet mysql value to at least 2M in your server config file.</font></center>";
}

mysql_select_db("ocsweb"); 

if(isset($_POST["label"])) {
	
	if($_POST["label"]!="") {
		@mysql_query( "DELETE FROM deploy WHERE NAME='label'");
		$query = "INSERT INTO deploy VALUES('label','".$_POST["label"]."');";
		mysql_query($query) or die(mysql_error());
		echo "<br><center><font color=green><b>Label added</b></font></center>";
	}
	else {
		echo "<br><center><font color=green><b>Label NOT added (not tag will be asked on client launch)</b></font></center>";
	}
}

if($_POST["fin"]=="fin") {
	if(!@mysql_connect($_POST["host"],"ocs","ocs")) {
		if(mysql_errno()==0) {
			echo "<br><center><font color=red><b>ERROR: MySql authentication problem. You must add the 'old-passwords' in your mysql configuration file (my.ini). Then restart mysql, and relaunch install.php</b><br></font></center>";
			die();
		}
		else
			echo "<br><center><font color=red><b>ERROR: MySql authentication problem. (using host=".$_POST["host"]." login=ocs pass=ocs).</b><br></font></center>";
		
		echo "<br><center><font color=red><b>ERROR: The installer ended unsuccessfully, rerun install.php once problems are corrected</b></font></center>";
		unlink("dbconfig.inc.php");
	}
	else {
		echo "<br><center><font color=green><b>Installation finished you can log in index.php with login=admin and pass=admin</b><br><br><b><a href='index.php'>Click here to enter OCS-NG GUI</a></b></font></center>";
	}	
	die();
}


if(!$ch = @fopen("dbconfig.inc.php","w")) {
	echo "<br><center><font color=red><b>ERROR: can't write in directory (on dbconfig.inc.php), please set the required rights in order to install ocsinventory (you should remove the write mode after the installation is successfull)</b></font></center>";
	die();
}

fwrite($ch,"<?\n\$_SESSION[\"SERVEUR_SQL\"]=\"".$_POST["host"]."\";\n\$_SESSION[\"COMPTE_BASE\"]=\"ocs\";\n\$_SESSION[\"PSWD_BASE\"]=\"ocs\";\n?>");
fclose($ch);

echo "<br><center><font color=green><b>MySql config file successfully written</b></font></center>";
	
$db_file = "ocsbase.sql";
if($dbf_handle = @fopen($db_file, "r")) {
	$sql_query = fread($dbf_handle, filesize($db_file));
	fclose($dbf_handle);
	$dejaLance=0;
	foreach ( explode(";", "$sql_query") as $sql_line) {
		if(!mysql_query($sql_line)) {
			if(  mysql_errno()==1062 || mysql_errno()==1061 || mysql_errno()==1044 || mysql_errno()==1065 || mysql_errno()==1060) 
				continue;			
			
			if(mysql_errno()==1007 || mysql_errno()==1050) {
				$dejaLance = 1;
				continue;
			}
			
			echo "<br><center><font color=red><b>ERROR: query failed</b><br>";
			echo "<b>mysql error: ".mysql_error()." (err:".mysql_errno().")</b></font></center>";
			$nberr++;
		}
	}
	if(!$nberr&&!$dejaLance)
		echo "<br><center><font color=green><b>Database successfully generated</b></font></center>";
}
else
	echo "<br><center><font color=red><b>ERROR: $db_file needed</b></font></center>";

if($dejaLance>0)	
	echo "<br><center><font color=green><b>Updating existing database</b></font></center>";
	
if($nberr) {
	echo "<br><center><font color=red><b>ERROR: The installer ended unsuccessfully, rerun install.php once problems are corrected</b></font></center>";
	unlink("dbconfig.inc.php");
	die();
}
$nberr=0;
$dir = "files";
$filenames = Array("ocsagent.exe");
$dejaLance=0;
$filMin = "";
mysql_query("DELETE FROM deploy");
mysql_select_db("ocsweb"); 
foreach($filenames as $fil) {
	$filMin = $fil;
	if ( $ledir = @opendir("files")) {
		while($filename = readdir($ledir)) {
			if(strcasecmp($filename,$fil)==0 && strcmp($filename,$fil)!=0  ) {
				//echo "<br><center><font color=green><b>$fil case is '$filename'</b></font></center>";
				$fil = $filename;
			}
		}
		closedir($ledir);
	}
	else {
		echo "<br><center><font color=orange><b>WARNING: 'files' directory missing, can't import $fil from it</b></font></center>";
	}
	
	if($fd = @fopen($dir."/".$fil, "r")) {
		$contents = fread($fd, filesize ($dir."/".$fil));
		fclose($fd);	
		$binary = addslashes($contents);	
		$query = "INSERT INTO deploy VALUES('$filMin','$binary');";
		
		if(!mysql_query($query)) {			
			if(mysql_errno()==1007 || mysql_errno()==1050 || mysql_errno()==1062) {
					$dejaLance++;
					continue;
			}
			if(mysql_errno()==2006) {
				echo "<br><center><font color=red><b>ERROR: $fil was not inserted. You need to set the max_allowed_packet mysql value to at least 2M</b></font></center>";
				echo "<br><center><font color=red><b>ERROR: The installer ended unsuccessfully, rerun install.php once problems are corrected</b></font></center>";
				unlink("dbconfig.inc.php");
				die();
			} 
			echo "<br><center><font color=red><b>ERROR: $fil not inserted</b><br>";
			echo "<b>mysql error: ".mysql_error()."</b></font></center>";		
			$nberr++;
		}
	}
	else {
		echo "<br><center><font color=orange><b>WARNING: ".$dir."/".$fil." missing, if you do not reinstall the DEPLOY feature won't be available</b></font></center>";
		$errNorm = true;
	}
}

if($dejaLance>0)	
	echo "<br><center><font color=orange><b>WARNING: One or more files were already inserted</b></font></center>";

if(!$nberr&&!$dejaLance&&!$errNorm)
	echo "<br><center><font color=green><b>Deploy files successfully inserted</b></font></center>";
	
mysql_query("DELETE FROM files");
$nbDeleted = mysql_affected_rows();
if( $nbDeleted > 0)
	echo "<br><center><font color=green><b>Table 'files' truncated</b></font></center>";
else
	echo "<br><center><font color=green><b>Table 'files' was empty</b></font></center>";
	
if($nberr) {
	echo "<br><center><font color=red><b>ERROR: The installer ended unsuccessfully, rerun install.php once problems are corrected</b></font></center>";
	unlink("dbconfig.inc.php");
	die();
}

$row = 1;
$handle = @fopen("subnet.csv", "r");

if( ! $handle ) {
	echo "<br><center><font color=green><b>No subnet.csv file to import</b></font></center>";
}
else {
	$errSub = 0;
	$resSub = 0;
	$dejSub = 0;
	echo "<hr><br><center><font color=green><b>Inserting subnet.csv networks</b></font></center>";
	while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
	
		$ipValide = "(([0-9]{1,3}\.){3}[0-9]{1,3})";
		$masqueEntier = "([0-9]{1,3})";
		$masqueValide = "(($ipValide|$masqueEntier)[ ]*$)";
		$exp = $ipValide."[ ]*/[ ]*".$masqueValide;

		if( ereg($exp,$data[2],$res) ) {
			
			if( @mysql_query("INSERT INTO subnet(netid, name, id, mask) 
			VALUES ('".$res[1]."','".$data[0]."','".$data[1]."','".$res[4]."')") ) {
				$resSub++;
				//echo "<br><center><font color=green><b>
				//Network => name: ".$data[0]." ip: ".$res[1]." mask: ".$res[4]." id: ".$data[1]." successfully inserted</b></font></center>";
			}
			else {
				if( mysql_errno() != 1062) {
					$errSub++;
					echo "<br><center><font color=red><b>ERROR: Could not insert network ".$data[0]." in the subnet table, error ".mysql_errno().": ".mysql_error()."</b></font></center>";
				}
				else
					$dejSub++;
			}
		}
		else {
			$errSub++;
			echo "<br><center><font color=orange><b>WARNING: Network ".$data[0]." was not inserted (invalid ip or mask: ".$data[2].")</b></font></center>";
		}
	}
	fclose($handle);
	echo "<br><center><font color=green><b>Subnet was imported=> $resSub successful, <font color=orange>$dejSub were already imported</font>, <font color=red>$errSub failed</font></b></font></center><hr>";
	
}


echo "<br><center><font color=green><b>Network netid computing. Please wait...</b></font></center>";
flush();

$reqDej = "SELECT COUNT(id) as nbid FROM networks WHERE ipsubnet IS NOT NULL";
$resDej = mysql_query($reqDej) or die(mysql_error());
$valDej = mysql_fetch_array($resDej);
$errNet = 0;
$sucNet = 0;
$dejNet = $valDej["nbid"];

$reqNet = "SELECT deviceid, id, ipaddress, ipmask FROM networks WHERE ipsubnet='' OR ipsubnet IS NULL";
$resNet = mysql_query($reqNet) or die(mysql_error());
while ($valNet = mysql_fetch_array($resNet) ) {
	$netid = getNetFromIpMask( $valNet["ipaddress"], $valNet["ipmask"] );
	if( !$netid || $valNet["ipaddress"]=="" || $valNet["ipmask"]=="" ) {
		$errNet++;
	}
	else {
		mysql_query("UPDATE networks SET ipsubnet='$netid' WHERE deviceid='".$valNet["deviceid"]."' AND id='".$valNet["id"]."'");
		if( mysql_errno() != "") {
			$errNet++;
			echo "<br><center><font color=red><b>ERROR: Could not update netid to $netid, error ".mysql_errno().": ".mysql_error()."</b></font></center>";
		}
		else {
			$sucNet++;
		}
	}	
}
echo "<br><center><font color=green><b>Network netid was computed=> $sucNet successful, <font color=orange>$dejNet were already computed</font>, <font color=red>$errNet were not computable</font></b></font></center>";

echo "<br><center><font color=green><b>Netmap netid computing. Please wait...</b></font></center>";
flush();

$reqDej = "SELECT COUNT(mac) as nbid FROM netmap WHERE netid IS NOT NULL";
$resDej = mysql_query($reqDej) or die(mysql_error());
$valDej = mysql_fetch_array($resDej);
$errNet = 0;
$sucNet = 0;
$dejNet = $valDej["nbid"];

$reqNet = "SELECT mac, ip, mask FROM netmap WHERE netid='' OR netid IS NULL";
$resNet = mysql_query($reqNet) or die(mysql_error());
while ($valNet = mysql_fetch_array($resNet) ) {
	$netid = getNetFromIpMask( $valNet["ip"], $valNet["mask"] );
	if( !$netid || $valNet["ip"]=="" || $valNet["mask"]=="" ) {
		$errNet++;
	}
	else {
		mysql_query("UPDATE netmap SET netid='$netid' WHERE mac='".$valNet["mac"]."' AND ip='".$valNet["ip"]."'");
		if( mysql_errno() != "") {
			$errNet++;
			echo "<br><center><font color=red><b>ERROR: Could not update netid to $netid, error ".mysql_errno().": ".mysql_error()."</b></font></center>";
		}
		else {
			$sucNet++;
		}
	}	
}
echo "<br><center><font color=green><b>Netmap netid was computed=> $sucNet successful, <font color=orange>$dejNet were already computed</font>, <font color=red>$errNet were not computable</font></b></font></center>";


function printEnTeteInstall($ent) {
	echo "<br><table border=1 class= \"Fenetre\" WIDTH = '62%' ALIGN = 'Center' CELLPADDING='5'>
	<th height=40px class=\"Fenetre\" colspan=2><b>".$ent."</b></th></table>";
}

?><br>
<center>
<form name='taginput' action='install.php' method='post'><b>
<font color='black'>Please enter the label of the windows client tag input box:<br>
(Leave empty if you don't want a popup to be shown on each agent launch).</font></b><br><br>
	<input name='label' size='40'>
	<input type='hidden' name='fin' value='fin'>
	<input type='hidden' name='name' value='<?=$_POST["name"];?>'>
	<input type='hidden' name='pass' value='<?=$_POST["pass"];?>'>
	<input type='hidden' name='host' value='<?=$_POST["host"];?>'>
	<input type=submit>
	
</form></center>
<?

function getNetFromIpMask($ip, $mask) {	
	return (  long2ip(ip2long($ip)&ip2long($mask)) ); 
}

?>





