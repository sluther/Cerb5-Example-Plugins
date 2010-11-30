<?php 
class ExReportGroup extends Extension_ReportGroup {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
};
class ExReport extends Extension_Report {
	
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render() {
		$db = DevblocksPlatform::getDatabaseService();
		$tpl = DevblocksPlatform::getTemplateService();

		@$filter_group_ids = DevblocksPlatform::importGPC($_REQUEST['group_id'],'array',array());
		$tpl->assign('filter_group_ids', $filter_group_ids);
		
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','-15 days');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','now');

		// Start + End
		@$start_time = strtotime($start);
		@$end_time = strtotime($end);
		$tpl->assign('start', $start);
		$tpl->assign('end', $end);
		$tpl->assign('age_dur', abs(floor(($start_time - $end_time)/86400)));
		
		

		// Calculate the # of ticks between the dates (and the scale -- day, month, etc)
		$range = $end_time - $start_time;
		$range_days = $range/86400;
		$plots = $range/15;
		
		$ticks = array();
		
		@$report_date_grouping = DevblocksPlatform::importGPC($_REQUEST['report_date_grouping'],'string','');
		$date_group = '';
		$date_increment = '';
		
		// Did the user choose a specific grouping?
		switch($report_date_grouping) {
			case 'year':
				$date_group = '%Y';
				$date_increment = 'year';
				break;
			case 'month':
				$date_group = '%Y-%m';
				$date_increment = 'month';
				break;
			case 'day':
				$date_group = '%Y-%m-%d';
				$date_increment = 'day';
				break;
		}
		
		// Fallback to automatic grouping
		if(empty($date_group) || empty($date_increment)) {
			if($range_days > 365) {
				$date_group = '%Y';
				$date_increment = 'year';
			} elseif($range_days > 32) {
				$date_group = '%Y-%m';
				$date_increment = 'month';
			} elseif($range_days > 1) {
				$date_group = '%Y-%m-%d';
				$date_increment = 'day';
			} else {
				$date_group = '%Y-%m-%d %H';
				$date_increment = 'hour';
			}
		}
		
		$tpl->assign('report_date_grouping', $date_increment);
		
		// Find unique values
		$time = strtotime(sprintf("-1 %s", $date_increment), $start_time);
		while($time < $end_time) {
			$time = strtotime(sprintf("+1 %s", $date_increment), $time);
			if($time <= $end_time)
				$ticks[strftime($date_group, $time)] = 0;
		}
		
		// you can run any custom sql queries you like to get the data you wish to display
		// however, it is important to make sure that the "date_plot" is kept intact, as this
		// is used to determine which dates have information to display in the graph
		// Chart
		$sql = sprintf("SELECT t.team_id AS group_id, DATE_FORMAT(FROM_UNIXTIME(t.created_date),'%s') AS date_plot, ".
			"COUNT(*) AS hits ".
			"FROM ticket t ".
			"WHERE t.created_date >= %d ".
			"AND t.created_date <= %d ".
			"%s ".
			"AND t.is_deleted = 0 ".
			"AND t.spam_score < 0.9000 ".
			"AND t.spam_training != 'S' ".
			"GROUP BY group_id, date_plot ",
			$date_group,
			$start_time,
			$end_time,
			(is_array($filter_group_ids) && !empty($filter_group_ids) ? sprintf("AND t.team_id IN (%s)", implode(',', $filter_group_ids)) : "")
		);
		$rs = $db->Execute($sql);
		
		$data = array();
		// $group_id is the numeric id of the group
		// $date_plot is the date in YYYY-MM-DD format
		// $ticks is an array containing all of the date_plots for this timespan
		// "hits" is the number of results for the current date_plot
		while($row = mysql_fetch_assoc($rs)) {
			$group_id = intval($row['group_id']);
			$date_plot = $row['date_plot'];
			
			if(!isset($data[$group_id]))
				$data[$group_id] = $ticks;
			// associate the date for each group with the number of results
			$data[$group_id][$date_plot] = intval($row['hits']);
		}
		
		// Sort the data in descending order
		uasort($data, array('ChReportSorters','sortDataDesc'));
		
		$tpl->assign('xaxis_ticks', array_keys($ticks));
		$tpl->assign('data', $data);
		
		mysql_free_result($rs);
		
		$tpl->display('devblocks:example.report::index.tpl');
	}
};