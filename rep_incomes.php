<!DOCTYPE HTML>
<?PHP
	include 'functions.php';
	check_logon();
	check_report();
	connect();
	
	//Variables $year and $month provide the pre-set values for input fields
	$year = date("Y",time()); 
	$month = date("m",time());
?>
<html>
	<?PHP htmlHead('Incomes Report',1); ?>
	
	<body>
		
		<!-- MENU HEADER & TABS -->
		<?PHP 
		include 'menu_header.php';
		menu_Tabs(5);
		?>
		
		<!-- MENU MAIN -->
		<div id="menu_main">
			<a href="rep_incomes.php" id="item_selected">Incomes Report</a>
			<a href="rep_expenditures.php">Expenditures Report</a>
			<a href="rep_loans.php">Loans Report</a>
			<a href="rep_capital.php">Capital Report</a>
			<a href="rep_monthly.php">Monthly Report</a>
			<a href="rep_annual.php">Annual Report</a>
		</div>
			
		<!-- MENU: Selection Bar -->
		<div id="menu_selection">
			<form action="rep_incomes.php" method="post">
				<input type="number" min="2006" max="2206" name="rep_year" style="width:100px;" value="<?PHP if ($month == 01) echo $year-1; else echo $year; ?>" placeholder="Year" />
				<select name="rep_month" style="height:24px">
					<option value="01" <?PHP if ($month == 02) echo 'selected="selected"' ?> >January</option>
					<option value="02" <?PHP if ($month == 03) echo 'selected="selected"' ?> >February</option>
					<option value="03" <?PHP if ($month == 04) echo 'selected="selected"' ?> >March</option>
					<option value="04" <?PHP if ($month == 05) echo 'selected="selected"' ?> >April</option>
					<option value="05" <?PHP if ($month == 06) echo 'selected="selected"' ?> >May</option>
					<option value="06" <?PHP if ($month == 07) echo 'selected="selected"' ?> >June</option>
					<option value="07" <?PHP if ($month == 08) echo 'selected="selected"' ?> >July</option>
					<option value="08" <?PHP if ($month == 09) echo 'selected="selected"' ?> >August</option>
					<option value="09" <?PHP if ($month == 10) echo 'selected="selected"' ?> >September</option>
					<option value="10" <?PHP if ($month == 11) echo 'selected="selected"' ?> >October</option>
					<option value="11" <?PHP if ($month == 12) echo 'selected="selected"' ?> >November</option>
					<option value="12" <?PHP if ($month == 01) echo 'selected="selected"' ?> >December</option>
				</select>
				<select name="rep_form" style="height:24px;">
					<option value="d" selected="selected">Detailed Rep.</option>
					<option value="a">Aggregated Rep.</option>
				</select>
				<input type="submit" name="select" value="Select Report" />
			</form>
		</div>
		
		<?PHP
		if(isset($_POST['select'])){
			
			//Sanitize user input
			$rep_month = sanitize($_POST['rep_month']);
			$rep_year = sanitize($_POST['rep_year']);
			
			//Calculate UNIX TIMESTAMP for first and last day of selected month
			$firstDay = mktime(0, 0, 0, $rep_month, 1, $rep_year);
			$lastDay = mktime(0, 0, 0, ($rep_month+1), 0, $rep_year);
			
			//Make array for exporting data
			$_SESSION['rep_export'] = array();
			$_SESSION['rep_exp_title'] = $rep_year.'-'.$rep_month.'_incomes_'.$_POST['rep_form'];
			
			/*** CASE 1: Aggregated Report ***/
			if ($_POST['rep_form'] == 'a'){
				
				//Selection from INCOMES and INCTYPE
				$sql_incomes = "SELECT * FROM incomes WHERE inc_date BETWEEN $firstDay AND $lastDay";
				$query_incomes = mysql_query($sql_incomes);
				if (!$query_incomes) die('SELECT failed: ' . mysql_error());
				
				$sql_inctype = "SELECT * FROM inctype";
				$query_inctype = mysql_query($sql_inctype);
				if (!$query_inctype) die('SELECT failed: ' . mysql_error());
				?>
				
				<!-- Export Button -->					
				<form class="export" action="rep_export.php" method="post">
					<input type="submit" name="export_rep" value="Export Report" />
				</form>
				
				<!-- TABLE: Results -->
				<table id="tb_table" style="width:50%">
					<colspan>
						<col width="50%">
						<col width="50%">
					</colspan>
					<tr>
						<th class="title" colspan="2">Aggregated Incomes Report for <?PHP echo $rep_month.'/'.$rep_year; ?></th>
					</tr>
					<tr>
						<th>Type</th>
						<th>Amount</th>
					</tr>
					<?PHP
					//Make array for income types
					$inctype = array();
					while($row_inctype = mysql_fetch_assoc($query_inctype)){
						$inctype[] = $row_inctype;
					}
					
					//Make array for all incomes for selected month
					$incomes = array();
					while($row_incomes = mysql_fetch_assoc($query_incomes)){
						$incomes[] = $row_incomes;
					}
					
					//Iterate over income types and add matching incomes to $total
					$total_inc = 0;
					foreach ($inctype as $it){
						$total_row = 0;
						foreach ($incomes as $ic) if ($ic['inctype_id'] == $it['inctype_id']) $total_row = $total_row + $ic['inc_amount'];
						tr_colored($color);	//Function for alternating Row Colors
						echo '	<td>'.$it['inctype_type'].'</td>
										<td>'.number_format($total_row).' UGX</td>
									</tr>';	
						$total_inc = $total_inc + $total_row;	
						
						//Prepare data for export to Excel file
						array_push($_SESSION['rep_export'], array("Type" => $it['inctype_type'], "Amount" => $total_row));
					}
					echo '	<tr class="balance">
										<td>Total Incomes:</td>
										<td>'.number_format($total_inc).' UGX</td>
									</tr>';
			}
			
			/* CASE 2: Detailed Report */
			else{
				$sql_incomes = "SELECT * FROM incomes, inctype, customer WHERE incomes.cust_id = customer.cust_id AND incomes.inctype_id = inctype.inctype_id AND inc_date BETWEEN $firstDay AND $lastDay ORDER BY inc_date, inc_receipt";
				$query_incomes = mysql_query($sql_incomes);
				if (!$query_incomes) die ('SELECT failed: '.mysql_error());
				?>
				
				<!-- Export Button -->					
				<form class="export" action="rep_export.php" method="post">
					<input type="submit" name="export_rep" value="Export Report" />
				</form>
				
				<!-- TABLE: Results -->
				<table id="tb_table">
					<colspan>
						<col width="15%">
						<col width="20%">
						<col width="20%">
						<col width="30%">
						<col width="15%">
					</colspan>
					<tr>
						<th class="title" colspan="5">Detailed Incomes Report for <?PHP echo $rep_month.'/'.$rep_year; ?></th>
					</tr>
					<tr>
						<th>Date</th>
						<th>Amount</th>
						<th>Type</th>
						<th>From</th>
						<th>Receipt No.</th>
					</tr>
					<?PHP
					$total_inc = 0;
					while($row_incomes = mysql_fetch_assoc($query_incomes)){
						tr_colored($color);	//Function for alternating Row Colors
						echo '	<td>'.date("d.m.Y",$row_incomes['inc_date']).'</td>
										<td>'.number_format($row_incomes['inc_amount']).' UGX</td>
										<td>'.$row_incomes['inctype_type'].'</td>
										<td>'.$row_incomes['cust_name'].'</td>
										<td>'.$row_incomes['inc_receipt'].'</td>
									</tr>';
						$total_inc = $total_inc + $row_incomes['inc_amount'];
						
						//Prepare data for export to Excel file
						array_push($_SESSION['rep_export'], array("Date" => date("d.m.Y",$row_incomes['inc_date']), "Amount" => $row_incomes['inc_amount'], "Type" => $row_incomes['inctype_type'], "From" => $row_incomes['cust_name'], "Receipt No" => $row_incomes['inc_receipt']));
					}
					echo '<tr class="balance">
									<td colspan="5">Total Incomes: '.number_format($total_inc).' UGX</td>
								</tr>';
			}
		}
		?>
		</table>
	</body>
</html>