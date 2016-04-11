<!--
Project : UiTM Timetable Generator
Description : Fetch and generate timetable from iCress
Created by : Afif Zafri
Credit : Mohd Shahril (regex code)
Created Date : 24/1/16
UPDATE 10/4/16
Add Timetable.js javascript plugin to create responsive timetable.
-->
<html>
<head>
<link href="uitmlogo.png" rel="shortcut icon">
<link rel="stylesheet" type="text/css" href="./styles/design.css">
<link rel="stylesheet" href="./styles/timetablejs.css">
<title>UiTM Timetable Generator</title>
</head>
<body>
<center>

<div class='noprint'>
<table>
<tr>
<td><img src="uitmlogo.png" width="80px"/></td><td><h1>UiTM Timetable<br>Generator</h1></td>
</tr>
</table>
<br><br>

<form action="index.php" method="get">
<font size='4'><b>Number of subjects : </b></font>&nbsp;
<label><select name="numsub">
<?php
$sub = range(1,10);
foreach($sub as $sub)
{
	if($sub == $_GET['numsub'])
	{
		echo "<option value='$sub' selected>{$sub}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>";
	}
	else
	{
		echo "<option value='$sub'>{$sub}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>";
	}
	
}
 ?>
 </select></label>
&nbsp; &nbsp;
<input type="submit" name="submit" class="myButton"><br><br>
</form>
<?php

if(isset($_GET['submit']))
{
	$numsub = $_GET['numsub'];
	
	echo "
	
	<table>
		<tr>
			<td><font color='red'><b>Note : </b></font></td>
			<td>&#9679; Enter your subject code under the Subject column</td>
		</tr>
		<tr>
			<td></td>
			<td>&#9679; Enter your group under the Group column.</td>
		</tr>
	</table>
	
	<br>
	
	<form action='index.php?numsub=$numsub&submit=Submit' method='post'>
	
	<table class='newtable'>
	<tr>
	<th>#</th>
	<th>Subject</th>
	<th>Group</th>
	</tr>
	";
	

	for($i=0;$i<$numsub;$i++)
	{
		$num = $i+1;
		echo "
		<tr>
		<td>$num.</td>
		<td><input type='text' name='sub$i' value='".(isset($_POST["sub$i"]) ? $_POST["sub$i"] : null)."'></td>
		<td><input type='text' name='group$i' value='".(isset($_POST["group$i"]) ? $_POST["group$i"] : null)."'></td>
		</tr>
		";
	}
	
	//get campus list
	$getcon = file_get_contents("http://icress.uitm.edu.my/jadual/jadual/jadual.asp");
	preg_match_all('#<option(.*?)<\/option>#',$getcon,$fcamp);
	
	echo "
	</table>
	<br><br>
	<label>
	<select name='fakulti'>
	<option value=''>---SELECT FACULTY---</option>
	";
	
	//display fetched campus list
	foreach($fcamp[0] as $fcamp[0])
	{
		echo $fcamp[0];
	}

	echo "
	 </select></label>
	 &nbsp; &nbsp;
	<input type='submit' name='submit2' class='myButton'>
	</form>
	<br>
	</div>
	";

	if(isset($_POST['submit2']))
	{
		$subs = "";
		$sub = "";
		$group = "";
		$fakulti = substr($_POST['fakulti'],0,2);

		for($i=0;$i<$numsub;$i++)
		{
			$sub = $_POST["sub$i"];
			$group = $_POST["group$i"];
			
			//start fetch icress data - credit : Shahril96
			$jadual = file_get_contents("http://icress.uitm.edu.my/jadual/{$fakulti}/{$sub}.html");
			$jadual = str_replace(array("\r", "\n"), '', $jadual);
			preg_match_all('#<td>(.*?)</td>#i', $jadual, $outs);

			$splits = array_chunk(array_splice($outs[1], 7), 7);

			$new = array();

			foreach($splits as $split) {
				$new[$split[0]][] = $split;

				foreach($new[$split[0]] as &$each) {
					unset($each[0]);
				}
			}
			//end fetch icress data
			
			//get array size of group list
			$size = count($new["$group"]);
			//fetch all details from array
			for($j=0;$j<$size;$j++)
			{	
				$s = $new["$group"][$j][1];
				$e = $new["$group"][$j][2];
				
				//change 12 hour format to 24 hour format
				$s2  = date("H:i", strtotime($s));
				$e2  = date("H:i", strtotime($e));
				
				//replace : to ,
				$start_time = str_replace(":" , "," , $s2); 
				$end_time = str_replace(":" , "," , $e2);
				
				$class = $new["$group"][$j][6];
				$day = $new["$group"][$j][3];
				
				//insert data into Timetable.js format, and store into a variable
				$subs .= " timetable.addEvent('". $sub ." - ". $class . "', '". $day ."', new Date(0,0,0,".$start_time."), new Date(0,0,0,".$end_time."), '#'); ";
				
				
			}
			 
			
			
			
		}
		
		
		?>

			<!--Start Generate Timetable -->
			<div class='timetable'></div>
			    
			    <script src='./scripts/timetable.min.js'></script>
			
					<script>
					  var timetable = new Timetable();
			
					  timetable.setScope(8,0)
			
					 timetable.addLocations(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
			
					<?php
					echo $subs;
					?>
			
				  var renderer = new Timetable.Renderer(timetable);
					  renderer.draw('.timetable');
					</script>
					
			<!--End Generate Timetable -->
			
			<!--print button-->
			<br><br>
			<div class='noprint'>
				<a href='javascript:window.print()'><button class='myButton'>Print</button></a>
			</div>
			<br><br>
			
		<?php
		
	}
}


?>
		
<br><br><br><br>
<div class="noprint">
Afif Zafri &copy; 2016
</div>
</center>
</body>
</html>
