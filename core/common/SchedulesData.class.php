<?php
/**
 * phpVMS - Virtual Airline Administration Software
 * Copyright (c) 2008 Nabeel Shahzad
 * For more information, visit www.phpvms.net
 *	Forums: http://www.phpvms.net/forum
 *	Documentation: http://www.phpvms.net/docs
 *
 * phpVMS is licenced under the following license:
 *   Creative Commons Attribution Non-commercial Share Alike (by-nc-sa)
 *   View license.txt in the root, or visit http://creativecommons.org/licenses/by-nc-sa/3.0/
 *
 * @author Nabeel Shahzad
 * @copyright Copyright (c) 2008, Nabeel Shahzad
 * @link http://www.phpvms.net
 * @license http://creativecommons.org/licenses/by-nc-sa/3.0/
 */
 
class SchedulesData
{

	/**
	 * Return information about a schedule (pass the ID)
	 */
	function GetSchedule($id)
	{
		$id = DB::escape($id);
		
		$sql = 'SELECT * FROM '. TABLE_PREFIX.'schedules WHERE id='.$id;

		return DB::get_row($sql);
	}
	
	/**
	 * Return all the airports by depature, which have a schedule, for
	 *	a certain airline. If the airline
	 * @return object_array
	 */
	function GetDepartureAirports($airlinecode='')
	{
		$airlinecode = DB::escape($airlinecode);
		
		$sql = 'SELECT DISTINCT s.depicao AS icao, a.name
					FROM '.TABLE_PREFIX.'schedules s, '.TABLE_PREFIX.'airports a
					WHERE s.depicao = a.icao ';
					
		if($airlinecode != '')
			$sql .= ' AND s.code=\''.$airlinecode.'\' ';
			
		$sql .= ' ORDER BY depicao ASC';
									
		return DB::get_results($sql);
	}
	
	/**
	 * Get all of the airports which have a schedule, from
	 *	a certain airport, using the airline code. Code
	 *	is optional, otherwise it returns all of the airports.
	 * @return database object
	 */
	function GetArrivalAiports($depicao, $airlinecode='')
	{
		$depicao = DB::escape($depicao);
		
		$sql = 'SELECT DISTINCT s.arricao AS icao, a.name
					FROM '.TABLE_PREFIX.'schedules s, '.TABLE_PREFIX.'airports a
					WHERE s.arricao = a.icao ';

		if($airlinecode != '')
			$sql .= ' AND s.code=\''.$airlinecode.'\' ';
		
		$sql .= ' ORDER BY depicao ASC';
		
		return DB::get_results($sql);
	}
	
	/**
	 * Return all of the routes give the departure airport
	 */
	function GetRoutesWithDeparture($depicao, $limit='')
	{
		$depicao = DB::escape($depicao);
		
		$sql = 'SELECT s.*, dep.name as depname, dep.lat AS deplat, dep.lng AS deplong,
							arr.name as arrname, arr.lat AS arrlat, arr.lng AS arrlong
					FROM '.TABLE_PREFIX.'schedules AS s
						INNER JOIN '.TABLE_PREFIX.'airports AS dep ON dep.icao = s.depicao
						INNER JOIN '.TABLE_PREFIX.'airports AS arr ON arr.icao = s.arricao
					WHERE s.depicao=\''.$depicao.'\'';
		
		return DB::get_results($sql);
	}
	
	function GetRoutesWithArrival($arricao, $limit='')
	{
		$arricao = DB::escape($arricao);
		
		$sql = 'SELECT s.*, dep.name as depname, dep.lat AS deplat, dep.lng AS deplong,
							arr.name as arrname, arr.lat AS arrlat, arr.lng AS arrlong
					FROM phpvms_schedules AS s
						INNER JOIN '.TABLE_PREFIX.'airports AS dep ON dep.icao = s.depicao
						INNER JOIN '.TABLE_PREFIX.'airports AS arr ON arr.icao = s.arricao
					WHERE s.arricao=\''.$arricao.'\'';
		
		return DB::get_results($sql);
	}
	
	function GetSchedulesByDistance($distance, $type, $limit='')
	{
		$distance = DB::escape($distance);
		$limit = DB::escape($limit);
		
		if($type == '')
			$type = '>';
		
		$sql = 'SELECT s.*, dep.name as depname, dep.lat AS deplat, dep.lng AS deplong,
							arr.name as arrname, arr.lat AS arrlat, arr.lng AS arrlong
					FROM phpvms_schedules AS s
						INNER JOIN '.TABLE_PREFIX.'airports AS dep ON dep.icao = s.depicao
						INNER JOIN '.TABLE_PREFIX.'airports AS arr ON arr.icao = s.arricao
					WHERE s.distance '.$type.' '.$distance.'
						ORDER BY s.depicao DESC';
	
		if($limit != '')
			$sql .= ' LIMIT ' . $limit;
		
		return DB::get_results($sql);
	}
	
	/**
	 * Search schedules by the equipment type
	 */
	function GetSchedulesByEquip($ac, $limit='')
	{
		$ac = DB::escape($ac);
		$limit = DB::escape($limit);
		
		$sql = 'SELECT * FROM '.TABLE_PREFIX.'schedules
					WHERE aircraft = \''.$ac.'\'
					ORDER BY depicao DESC';
		
		if($limit != '')
			$sql .= ' LIMIT ' . $limit;
		
		return DB::get_results($sql);
	}
	
	/**
	 * Get all the schedules, $limit is the number to return
	 */
	function GetSchedules($limit='')
	{
		
		$limit = DB::escape($limit);
		
		$sql = 'SELECT s.*, dep.name as depname, dep.lat AS deplat, dep.lng AS deplong,
							arr.name as arrname, arr.lat AS arrlat, arr.lng AS arrlong
					FROM phpvms_schedules AS s
						INNER JOIN '.TABLE_PREFIX.'airports AS dep ON dep.icao = s.depicao
						INNER JOIN '.TABLE_PREFIX.'airports AS arr ON arr.icao = s.arricao
					ORDER BY s.depicao DESC';
		
		if($limit != '')
			$sql .= ' LIMIT ' . $limit;
		
		return DB::get_results($sql);
	}
	
	/**
	 * Add a schedule
	 */
	function AddSchedule($code, $flightnum, $leg, $depicao, $arricao, $route,
		$aircraft, $distance, $deptime, $arrtime, $flighttime)
	{
		$code = DB::escape($code);
		$flightnum = DB::escape($flightnum);
		$leg = DB::escape($leg);
		$depicao = DB::escape($depicao);
		$arricao = DB::escape($arricao);
		$route = DB::escape($route);
		$aircraft = DB::escape($aircraft);
		$distance = DB::escape($distance);
		$deptime = DB::escape($deptime);
		$arrtime = DB::escape($arrtime);
		$flighttime = DB::escape($flighttime);
		
		if($leg == '') $leg = 1;
		$deptime = strtoupper($deptime);
		$arrtime = strtoupper($arrtime);
		
		if($depicao == $arricao) return;
		
		$sql = "INSERT INTO " . TABLE_PREFIX ."schedules
				(code, flightnum, leg, depicao, arricao, route, aircraft, distance, deptime, arrtime, flighttime)
				VALUES ('$code', '$flightnum', '$leg', '$depicao', '$arricao', '$route', '$aircraft', '$distance',
				'$deptime', '$arrtime', '$flighttime')";
		
		$res = DB::query($sql);
		
		if(DB::errno() != 0)
			return false;
			
		return true;
	}

	/**
	 * Edit a schedule
	 */
	function EditSchedule($scheduleid, $code, $flightnum, $leg, $depicao, $arricao, $route,
				$aircraft, $distance, $deptime, $arrtime, $flighttime)
	{

		$scheduleid = DB::escape($scheduleid);
		$code = DB::escape($code);
		$flightnum = DB::escape($flightnum);
		$leg = DB::escape($leg);
		$depicao = DB::escape($depicao);
		$arricao = DB::escape($arricao);
		$route = DB::escape($route);
		$aircraft = DB::escape($aircraft);
		$distance = DB::escape($distance);
		$deptime = DB::escape($deptime);
		$arrtime = DB::escape($arrtime);
		$flighttime = DB::escape($flighttime);
		
		if($leg == '') $leg = 1;
		$deptime = strtoupper($deptime);
		$arrtime = strtoupper($arrtime);

		$sql = "UPDATE " . TABLE_PREFIX ."schedules SET code='$code', flightnum='$flightnum', leg='$leg',
						depicao='$depicao', arricao='$arricao',
						route='$route', aircraft='$aircraft', distance='$distance', deptime='$deptime',
						arrtime='$arrtime', flighttime='$flighttime'
					WHERE id=$scheduleid";

		$res = DB::query($sql);
		
		if(DB::errno() != 0)
			return false;
			
		return true;
	}

	/**
	 * Delete a schedule
	 */
	function DeleteSchedule($scheduleid)
	{
		$scheduleid = DB::escape($scheduleid);
		$sql = 'DELETE FROM ' .TABLE_PREFIX.'schedules WHERE id='.$scheduleid;

		$res = DB::query($sql);
		
		if(DB::errno() != 0)
			return false;
			
		return true;
	}
	
	
	/**
	 * Get a specific bid with route information
	 *
	 * @param unknown_type $bidid
	 * @return unknown
	 */
	function GetBid($bidid)
	{
		$bidid = DB::escape($bidid);
		$sql = 'SELECT s.*, b.bidid
					FROM '.TABLE_PREFIX.'schedules s, '.TABLE_PREFIX.'bids b
					WHERE b.routeid = s.id AND b.bidid='.$bidid;
		
		return DB::get_row($sql);
	}
	
	/**
	 * Get all of the bids for a pilot
	 *
	 * @param unknown_type $pilotid
	 * @return unknown
	 */
	function GetBids($pilotid)
	{
		$pilotid = DB::escape($pilotid);
		$sql = 'SELECT s.*, b.bidid
					FROM '.TABLE_PREFIX.'schedules s, '.TABLE_PREFIX.'bids b
					WHERE b.routeid = s.id AND b.pilotid='.$pilotid;
		
		return DB::get_results($sql);
	}
		
	function AddBid($pilotid, $routeid)
	{
		$pilotid = DB::escape($pilotid);
		$routeid = DB::escape($routeid);
		
		if(DB::get_row('SELECT bidid FROM '.TABLE_PREFIX.'bids
				WHERE pilotid='.$pilotid.' AND routeid='.$routeid))
		{
			return;
		}
			
		$pilotid = DB::escape($pilotid);
		$routeid = DB::escape($routeid);
		
		$sql = 'INSERT INTO '.TABLE_PREFIX.'bids (pilotid, routeid)
					VALUES ('.$pilotid.', '.$routeid.')';
		
		DB::query($sql);
		
		if(DB::errno() != 0)
			return false;
			
		return true;
	}
	
	function RemoveBid($bidid)
	{
		$bidid = DB::escape($bidid);
		
		$sql = 'DELETE FROM '.TABLE_PREFIX.'bids WHERE bidid='.$bidid;
		
		DB::query($sql);
		
		if(DB::errno() != 0)
			return false;
			
		return true;
	}
}

?>