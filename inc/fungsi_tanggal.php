<?php
function jin_date_sql($date){
	$exp = explode('-',$date);
	if(count($exp) == 3) {
		$date = $exp[2].'-'.$exp[1].'-'.$exp[0];
	}
	return $date;
}
 
function jin_date_str($date){
	$exp = explode('-',$date);
	if(count($exp) == 3) {
		$date = $exp[2].'-'.$exp[1].'-'.$exp[0];
	}
	return $date;
}

function ind_datetime_str($date){
	$bag_tgl = substr($date,0,10);
	$bag_jam = substr($date,11,8);
	
	$exp = explode('-',$bag_tgl);
	$exp1 = explode(':',$bag_jam);
	if(count($exp) == 3) {
		$date = $exp[2].'-'.$exp[1].'-'.$exp[0];
	}
	if(count($exp1) == 3) {
		$jam = $exp1[0].':'.$exp1[1].':'.$exp1[2];
	}
	return $date.' '.$jam;
}
?>