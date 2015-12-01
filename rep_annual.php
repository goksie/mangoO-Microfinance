<!DOCTYPE HTML>
<?PHP
	include 'functions.php';
	check_logon();
	check_report();
	connect();
	
	//Variable $year provides the pre-set values for input fields
	$year = date("Y",time()); 
?>
<html>
	<?PHP htmlHead('Annual Report',1) ?>	
	<body>
		
		<!-- MENU HEADER & TABS -->
		<?PHP 
		include 'menu_header.php';
		menu_Tabs(5);
		?>
		
		<!-- MENU MAIN -->
		<div id="menu_main">
			<a href="rep_incomes.php">Incomes Report</a>
			<a href="rep_expenditures.php">Expenditures Report</a>
			<a href="rep_loans.php">Loans Report</a>
			<a href="rep_capital.php">Capital Report</a>
			<a href="rep_monthly.php">Monthly Report</a>
			<a href="rep_annual.php" id="item_selected">Annual Report</a>
		</div>
		
		<!-- MENU: Selection Bar -->
		<div id="menu_selection">			
			<form action="rep_annual.php" method="post">
				<input type="number" min="2006" max="2206" name="rep_year" style="width:100px;" value="<?PHP echo $year; ?>" placeholder="Give Year" />
				<input type="submit" name="select" value="Select Report" />
			</form>
		</div>
		
		<?PHP
		if(isset($_POST['select'])){
			
			//Sanitize user input
			$rep_year = sanitize($_POST['rep_year']);
			
			//Calculate UNIX TIMESTAMP for first and last day of selected month
			$firstDay = mktime(0, 0, 0, 1, 1, $rep_year);
			$lastDay = mktime(0, 0, 0, 0, 0, ($rep_year+1));
			
			//Make array for exporting data
			$_SESSION['rep_export'] = array();
			$_SESSION['rep_exp_title'] = $rep_year.'_annual';
			
			
			/**** INCOME RELATED DATA ****/
			
			//Select INCOMES and INCTYPE
			$sql_incomes = "SELECT * FROM incomes WHERE inc_date BETWEEN $firstDay AND $lastDay";
			$query_incomes = mysql_query($sql_incomes);
			if (!$query_incomes) die('SELECT failed: ' . mysql_error());
			
			$sql_inctype = "SELECT * FROM inctype";
			$query_inctype = mysql_query($sql_inctype);
			if (!$query_inctype) die('SELECT failed: ' . mysql_error());
			
			
			/**** EXPENDITURE RELATED DATA ****/
			
			//Select EXPENDITURES and EXPTYPE
			$sql_expendit = "SELECT * FROM expenditures WHERE exp_date BETWEEN $firstDay AND $lastDay ORDER BY exp_date";
			$query_expendit = mysql_query($sql_expendit);
			if (!$query_expendit) die ('SELECT failed: '.mysql_error());
			
			$sql_exptype = "SELECT * FROM exptype";
			$query_exptype = mysql_query($sql_exptype);
			if (!$query_exptype) die ('SELECT failed: '.mysql_error());
			
			
			/**** CAPITAL RELATED DATA ****/
			
			//Select newly bought Shares from SHARES
			$sql_shares = "SELECT * FROM shares WHERE share_date BETWEEN $firstDay AND $lastDay";
			$query_shares = mysql_query($sql_shares);
			check_sql ($query_shares);
			$total_shares = 0;
			while($row_shares = mysql_fetch_assoc($query_shares)){
				$total_shares = $total_shares + ($row_shares['share_amount']*$row_shares['share_value']);
			}
			
			//Select Saving Deposits from SAVINGS
			$sql_savdep = "SELECT * FROM savings WHERE sav_date BETWEEN $firstDay AND $lastDay AND savtype_id = 1";
			$query_savdep = mysql_query($sql_savdep);
			check_sql ($query_savdep);
			$total_savdep = 0;
			while($row_savdep = mysql_fetch_assoc($query_savdep)){
				$total_savdep = $total_savdep + $row_savdep['sav_amount'];
			}
			
			//Select Loan Recoveries from LOANS
			$sql_loanrec = "SELECT * FROM ltrans WHERE ltrans_date BETWEEN $firstDay AND $lastDay";
			$query_loanrec = mysql_query($sql_loanrec);
			check_sql ($query_loanrec);
			$total_loanrec = 0;
			while($row_loanrec = mysql_fetch_assoc($query_loanrec)){
				$total_loanrec = $total_loanrec + $row_loanrec['ltrans_principal'];
			}
			
			//Select Saving Withdrawals from SAVINGS
			$sql_savwithd = "SELECT * FROM savings WHERE sav_date BETWEEN $firstDay AND $lastDay AND savtype_id = 2";
			$query_savwithd = mysql_query($sql_savwithd);
			check_sql ($query_savwithd);
			$total_savwithd = 0;
			while($row_savwithd = mysql_fetch_assoc($query_savwithd)){
				$total_savwithd = $total_savwithd + $row_savwithd['sav_amount'];
			}
			$total_savwithd = $total_savwithd * (-1);
			
			//Select Loans Out from LOANS
			$sql_loanout = "SELECT * FROM loans WHERE loan_dateout BETWEEN $firstDay AND $lastDay";
			$query_loanout = mysql_query($sql_loanout);
			check_sql ($query_loanout);
			$total_loanout = 0;
			while($row_loanout = mysql_fetch_assoc($query_loanout)){
				$total_loanout = $total_loanout + $row_loanout['loan_principal'];
			}

			
			/**** LOAN RELATED DATA ****/
			
			//Select Due Loan Payments from LTRANS
			$sql_loandue = "SELECT * FROM ltrans, loans, loanstatus WHERE ltrans.loan_id = loans.loan_id AND loans.loanstatus_id = loanstatus.loanstatus_id AND ltrans_due BETWEEN $firstDay AND $lastDay AND loans.loanstatus_id IN (2, 4, 5) ORDER BY ltrans_due, loans.cust_id";
			$query_loandue = mysql_query($sql_loandue);
			check_sql ($query_loandue);
			
			//Select Loan Recoveries from LTRANS
			$sql_loanrec = "SELECT * FROM ltrans, loans WHERE ltrans.loan_id = loans.loan_id AND ltrans_date BETWEEN $firstDay AND $lastDay ORDER BY ltrans_date, loans.cust_id";
			$query_loanrec = mysql_query($sql_loanrec);
			check_sql ($query_loanrec);
			
			//Select Loans Out from LOANS
			$sql_loanout = "SELECT * FROM loans, customer WHERE loans.cust_id = customer.cust_id AND loans.loan_dateout BETWEEN $firstDay AND $lastDay ORDER BY loan_dateout, loans.cust_id";
			$query_loanout = mysql_query($sql_loanout);
			check_sql ($query_loanout);
			?>	
									
			<!-- Export Button -->					
			<form class="export" action="rep_export.php" method="post">
				<input type="submit" name="export_rep" value="Export Report" />
			</form>
			

			<!-- INCOMES: Table 1 -->
			<?PHP array_push($_SESSION['rep_export'], array("Type" => "INCOMES", "Amount" => "")); ?>
			<table id="tb_table" style="width:50%">
				<colspan>
					<col width="50%">
					<col width="50%">
				</colspan>
				<tr>
					<th class="title" colspan="2">Incomes Report for <?PHP echo $rep_year; ?></th>
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
					
					//Prepare INCOME data for export to Excel file
					array_push($_SESSION['rep_export'], array("Type" => $it['inctype_type'], "Amount" => $total_row));
				}
				
				//Total Incomes Amount
				echo '	<tr class="balance">
									<td>Total Incomes:</td>
									<td>'.number_format($total_inc).' UGX</td>
								</tr>';
				array_push($_SESSION['rep_export'], array("Type" => "Total Incomes", "Amount" => $total_inc));
				array_push($_SESSION['rep_export'], array("Type" => "", "Amount" => ""));
				?>
			</table>
			
			<!-- EXPENDITURES: Table 1 -->
			<?PHP array_push($_SESSION['rep_export'], array("Type" => "EXPENDITURES", "Amount" => "")); ?>
			<table id="tb_table" style="width:50%">
				<colspan>
					<col width="50%">
					<col width="50%">
				</colspan>
				<tr>
					<th class="title" colspan="2">Expenditures for <?PHP echo $rep_year; ?></th>
				</tr>
				<tr>
					<th>Type</th>
					<th>Amount</th>
				</tr>
				<?PHP
				
				$exptype = array();
				while($row_exptype = mysql_fetch_assoc($query_exptype)){
					$exptype[] = $row_exptype;
				}
				
				$expendit = array();
				while($row_expendit = mysql_fetch_assoc($query_expendit)){
					$expendit[] = $row_expendit;
				}
				
				$total_exp = 0;
				foreach ($exptype as $et){
					$total_row = 0;
					foreach ($expendit as $ex) if ($ex['exptype_id'] == $et['exptype_id']) $total_row = $total_row + $ex['exp_amount'];
					tr_colored($color);	//Function for alternating Row Colors
					echo '	<td>'.$et['exptype_type'].'</td>
									<td>'.number_format($total_row).' UGX</td>
								</tr>';
					$total_exp = $total_exp + $total_row;	
					
					//Prepare EXPENSE data for export to Excel file
					array_push($_SESSION['rep_export'], array("Type" => $et['exptype_type'], "Amount" => $total_row));
				}
				
				//Total Expenditures Amount Line
				echo '<tr class="balance">
								<td>Total Expenditures:</td>
								<td>'.number_format($total_exp).' UGX</td>
							</tr>';
				array_push($_SESSION['rep_export'], array("Type" => "Total Expenditures", "Amount" => $total_exp));
				array_push($_SESSION['rep_export'], array("Type" => "", "Amount" => ""));
				?>
			</table>

			
			<!-- LOANS: Table 1: Due Repayments -->
			<?PHP array_push($_SESSION['rep_export'], array("Type" => "LOAN REPAYMENTS", "Amount" => "")); ?>
			<table id="tb_table" style="width:75%">
				<colgroup>
					<col width="25%"/>
					<col width="25%"/>
					<col width="25%"/>
					<col width="25%"/>
				</colgroup>
				<tr>
					<th class="title" colspan="4">Due Loan Payments for <?PHP echo $rep_year; ?></th>
				</tr>
				<tr>
					<th>Loan No.</th>
					<th>Loan Status</th>
					<th>Due Date</th>
					<th>Due Amount</th>
				</tr>
				<?PHP
				$total_loandue = 0;
				$color = 0;
				while($row_loandue = mysql_fetch_assoc($query_loandue)){
					tr_colored($color);
					echo '	<td><a href="loan.php?lid='.$row_loandue['loan_id'].'">'.$row_loandue['loan_no'].'</a></td>
									<td>'.$row_loandue['loanstatus_status'].'</td>
									<td>'.date("d.m.Y",$row_loandue['ltrans_due']).'</td>
									<td>'.number_format($row_loandue['ltrans_principaldue'] + $row_loandue['ltrans_interestdue']).' UGX</td>										
								</tr>';
					$total_loandue = $total_loandue + $row_loandue['ltrans_principaldue'] + $row_loandue['ltrans_interestdue'];
				}
				echo '<tr class="balance">
								<td colspan="4">Total Due Payments: '.number_format($total_loandue).' UGX</td>
							</tr>';
				
				//Prepare LOAN REPAYMENT data for export to Excel file
				array_push($_SESSION['rep_export'], array("Type" => "Due Loan Payments", "Amount" => $total_loandue));
				?>				
			</table>
			
			<!-- LOANS: Table 2: Loan Recoveries --> 
			<table id="tb_table" style="width:75%">
				<colgroup>
					<col width="25%"/>
					<col width="25%"/>
					<col width="25%"/>
					<col width="25%"/>
				</colgroup>
				<tr>
					<th class="title" colspan="4">Loan Recoveries for <?PHP echo $rep_year; ?></th>
				</tr>
				<tr>
					<th>Loan No.</th>
					<th>Instalment Due</th>
					<th>Recovered</th>
					<th>Date</th>
				</tr>
				<?PHP
				$total_loanrec = 0;
				$color = 0;
				while($row_loanrec = mysql_fetch_assoc($query_loanrec)){
					tr_colored($color);
					echo '	<td><a href="loan.php?lid='.$row_loanrec['loan_id'].'">'.$row_loanrec['loan_no'].'</a></td>
									<td>'.number_format($row_loanrec['ltrans_principaldue'] + $row_loanrec['ltrans_interestdue']).' UGX</td>
									<td>'.number_format($row_loanrec['ltrans_principal'] + $row_loanrec['ltrans_interest']).' UGX</td>
									<td>'.date("d.m.Y",$row_loanrec['ltrans_date']).'</td>
								</tr>';
					$total_loanrec = $total_loanrec + $row_loanrec['ltrans_principal'] + $row_loanrec['ltrans_interest'];
				}
				echo '<tr class="balance">
								<td colspan="4">
									Total Recoveries: '.number_format($total_loanrec).' UGX';
				if ($total_loandue != 0) echo '<br/>Loan Recovery Rate: '.number_format($total_loanrec / $total_loandue * 100).'%';
				echo '	</td>
							</tr>';
							
				//Prepare LOANS RECOVERY data for export to Excel file
				array_push($_SESSION['rep_export'], array("Type" => "Loan Recoveries", "Amount" => $total_loanrec));
				if ($total_loandue != 0) array_push($_SESSION['rep_export'], array("Type" => "Loan Recovery Rate", "Amount" => round(($total_loanrec / $total_loandue * 100),2).'%'));
				array_push($_SESSION['rep_export'], array("Type" => "", "Amount" => ""));
				?>				
			</table>
			
			<!-- LOANS: Table 3: Loans Out -->
			<table id="tb_table" style="width:75%">
				<colgroup>
					<col width="10%"/>
					<col width="30%"/>
					<col width="20%"/>
					<col width="5%"/>
					<col width="5%"/>
					<col width="20%"/>
					<col width="10%"/>
				</colgroup>
				<tr>
					<th class="title" colspan="7">Loans Out for <?PHP echo $rep_year; ?></th>
				</tr>
				<tr>
					<th>Loan No.</th>
					<th>Customer</th>
					<th>Principal</th>
					<th>Interest</th>
					<th>Period</th>
					<th>Repay Total</th>
					<th>Date Out</th>
				</tr>
				<?PHP
				$total_loanout = 0;
				$color = 0;
				while($row_loanout = mysql_fetch_assoc($query_loanout)){
					tr_colored($color);
					echo '	<td><a href="loan.php?lid='.$row_loanout['loan_id'].'">'.$row_loanout['loan_no'].'</a></td>
									<td>'.$row_loanout['cust_name'].' ('.$row_loanout['cust_id'].'/'.date("Y",$row_loanout['cust_since']).')</td>
									<td>'.number_format($row_loanout['loan_principal']).' UGX</td>
									<td>'.$row_loanout['loan_interest'].'%</td>
									<td>'.$row_loanout['loan_period'].'</td>
									<td>'.number_format($row_loanout['loan_repaytotal']).' UGX</td>
									<td>'.date("d.m.Y", $row_loanout['loan_dateout']).'</td>
								</tr>';
					$total_loanout = $total_loanout + $row_loanout['loan_principal'];
				}
				echo '<tr class="balance">
								<td colspan="7">Total Loans Out: '.number_format($total_loanout).' UGX</td>
							</tr>';
				?>
			</table>
			
			
			<!-- CAPITAL: Table 1: Capital Additions -->
			<table id="tb_table" style="width:50%">
				<colgroup>
					<col width="50%"/>
					<col width="50%"/>
				</colgroup>
				<tr>
					<th class="title" colspan="2">Capital Additions for <?PHP echo $rep_year; ?></th>
				</tr>
				<tr>
					<th>Type</th>
					<th>Amount</th>
				</tr>
				<tr>
					<td>Shares</td>
					<td><?PHP echo number_format($total_shares) ?> UGX</td>
				</tr>
				<tr class="alt">
					<td>Saving Deposits</td>
					<td><?PHP echo number_format($total_savdep) ?> UGX</td>
				</tr>
				<tr>
					<td>Loan Recoveries</td>
					<td><?PHP echo number_format($total_loanrec) ?> UGX</td>
				</tr>
				<tr class="balance">
					<td>Total Capital Additions:</td>
					<td><?PHP echo number_format($total_shares + $total_savdep + $total_loanrec) ?> UGX</td>
				</tr>
			</table>
			
			<!-- CAPITAL: Table 2: Capital Deductions -->
			<table id="tb_table" style="width:50%">
				<colgroup>
					<col width="50%"/>
					<col width="50%"/>
				</colgroup>
				<tr>
					<th class="title" colspan="2">Capital Deductions for <?PHP echo $rep_year; ?></th>
				</tr>
				<tr>
					<th>Type</th>
					<th>Amount</th>
				</tr>
				<tr>
					<td>Loans Out</td>
					<td><?PHP echo number_format($total_loanout) ?> UGX</td>
				</tr>
				<tr class="alt">
					<td>Saving Withdrawals</td>
					<td><?PHP echo number_format($total_savwithd) ?> UGX</td>
				</tr>
				<tr class="balance">
					<td>Total Capital Deductions:</td>
					<td><?PHP echo number_format($total_loanout+$total_savwithd) ?> UGX</td>
				</tr>
				
				<?PHP
				//Prepare CAPITAL data for export to Excel file
				array_push($_SESSION['rep_export'], array("Type" => "CAPITAL ADDITIONS", "Amount" => ""));
				array_push($_SESSION['rep_export'], array("Type" => "Shares", "Amount" => $total_shares));
				array_push($_SESSION['rep_export'], array("Type" => "Saving Deposits", "Amount" => $total_savdep));
				array_push($_SESSION['rep_export'], array("Type" => "Loan Recoveries", "Amount" => $total_loanrec));
				array_push($_SESSION['rep_export'], array("Type" => "Total Additions", "Amount" => $total_loanrec+$total_savdep+$total_shares));
				
				array_push($_SESSION['rep_export'], array("Type" => "", "Amount" => ""));
				
				array_push($_SESSION['rep_export'], array("Type" => "CAPITAL DEDUCTIONS", "Amount" => ""));
				array_push($_SESSION['rep_export'], array("Type" => "Saving Withdrawals", "Amount" => $total_savwithd));
				array_push($_SESSION['rep_export'], array("Type" => "Loans Out", "Amount" => $total_loanout));				
				array_push($_SESSION['rep_export'], array("Type" => "Total Deductions", "Amount" => $total_loanout+$total_savwithd));
				?>
			</table>
			
		<?PHP	
		}
		?>
	</body>
</html>