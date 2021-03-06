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
 
class StatsData extends CodonData
{
	public static function getStartDate()
	{
		$start_date = CodonCache::read('start_date');
		
		if($start_date === false)
		{
			$sql = 'SELECT `pirepid`, `submitdate`
					FROM '.TABLE_PREFIX.'pireps
					ORDER BY `submitdate` ASC
					LIMIT 1';
						
			$start_date = DB::get_row($sql);
			CodonCache::write('start_date', $start_date, 'long');
		}
		
		return $start_date;
	}
	
	/**
	 * Get all of the months since the VA started
	 */
	public static function getMonthsSinceStart()
	{
		$months_list = CodonCache::read('months_since_start');
		
		if($months_list === false)
		{
			$date = self::GetStartDate();
			
			if(!$date)
				$startdate = time();
			else
				$startdate = $date->submitdate;
				
			$months_list = self::GetMonthsSinceDate($startdate);
			CodonCache::write('months_since_start', $months_list, 'long');
		}
		
		return $months_list;
	}
	
	/**
	 * Get years since the VA started
	 */
	public static function getYearsSinceStart()
	{
		$key = 'years_since_start';
		$years_start = CodonCache::read($key);
		
		if($years_start === false)
		{
			$date = self::GetStartDate();
			
			if(!$date)
				$startdate = 'Today';
			else
				$startdate = $date->submitdate;
			
			$start = strtotime($startdate);
			$end = date('Y');		
			do
			{
				$year = date('Y', $start);	# Get the months
				$years[$year] = $start;		# Set the timestamp
				$start = strtotime('+1 Year', $start);
				
			} while ( $year < $end ); 
			
			$years_start = array_reverse($years, true);	
			CodonCache::write($key, $years_start, 'long');
		}
		
		return $years_start;
	}
	
	/**
	 * Get all of the months since a certain date
	 */
	public static function getMonthsSinceDate($start)
	{
		$key = 'months_since_'.$start;
		$months = CodonCache::read($key);
		
		if($months === false)
		{
			if(!is_numeric($start))
			{
				$start = strtotime($start);
			}
			
			$end = date('Ym');

			do
			{
				# Get the months
				$month = date('M Y', $start);
				$months[$month] = $start; # Set the timestamp			
				$start = strtotime('+1 month +1 day', strtotime($month));
			
				# Convert to YYYYMM to compare
				$check = intval(date('Ym', $start));
				
			} while ( $check <= $end ); 
			
			CodonCache::write($key, $months, 'long');
		}

		return $months;
	}
	
	/**
	 * Get all the months within a certain range
	 * Pass timestamp, or textual date
	 */
	public static function getMonthsInRange($start, $end)
	{
		$key = "months_in_{$start}_{$end}";
		$months = CodonCache::read($key);
		
		if($months === false)
		{
			if(!is_numeric($start))
			{
				$start = strtotime($start);
			}
			
			if(!is_numeric($end))
			{
				$end = strtotime($end);
			}
			
			$end = intval(date('Ym', $end));
			
			/* Loop through, adding one month to $start each time
			 */		
			do
			{			
				$month = date('M Y', $start);		# Get the month
				$months[$month] = $start;			# Set the timestamp	
				$start = strtotime('+1 month +1 day', strtotime($month));
				//$start += (SECONDS_PER_DAY * 25);	# Move it up a month
				
				$check = intval(date('Ym', $start));
				
			} while ( $check <= $end );
			
			CodonCache::write($key, $months, 'long');
		}
		
		return $months;		
	}
	
	public static function updateTotalHours()
	{
		$sql = 'SELECT SEC_TO_TIME(SUM(p.flighttime)) as totaltime
			FROM '.TABLE_PREFIX.'pireps p WHERE p.accepted=1';

		$total = DB::get_row($sql);
		$totaltime = $total->totaltime;
		
		SettingsData::SaveSetting('TOTAL_HOURS', $totaltime);
		return $totaltime;
	}
	
	/**
	 * Get the total number of hours flown by pilots
	 */
	public static function TotalHours()
	{
		return SettingsData::GetSettingValue('TOTAL_HOURS');
	}
	
	/**
	 * Get the total number of flights flown
	 */
	public static function TotalFlights($airline_code = '')
	{
		$key = 'total_flights';
		if($airline_code != '')
		{
			$key .= '_'.$airline_code;
		}
		
		$total = CodonCache::read($key);
		if($total === false)
		{
			$sql = 'SELECT COUNT(*) AS `total`
					FROM `'.TABLE_PREFIX.'pireps`
					WHERE `accepted`='.PIREP_ACCEPTED;
			
			if($airline_code != '')
			{
				$sql .= " AND `code`='{$airline_code}' GROUP BY `code`";
			}
					
			$result = DB::get_row($sql);
			
			if(!$result)
			{
				return 0;
			}
			
			$total = $result->total;
			CodonCache::write($key, $total, '15minute');
		}
		
		return $total;
	}
	
	/**
	 * Get the top routes
	 */
	public static function TopRoutes($airline_code='')
	{
		$key = 'top_routes';
		if($airline_code != '')
		{
			$key .= '_'.$airline_code;
		}
		
		$top_routes = CodonCache::read($key);
		if($top_routes === false)
		{
			$sql = 'SELECT * 
					FROM `'.TABLE_PREFIX.'schedules`';
					
			if($airline_code != '')
			{
				$sql .= " WHERE `code`='{$airline_code}' GROUP BY `code`";
			}
			
			$sql ='	ORDER BY `timesflown` DESC
					LIMIT 10';
			
			$top_routes = DB::get_results($sql);
			CodonCache::write($key, $top_routes, 'medium');
		}
		
		return $top_routes;
	}
	
	public static function UsersOnline($minutes = '')
	{
		$key = 'users_online';
		$users_online = CodonCache::read($key);
		
		if($users_online === false)
		{
			if($minutes == '')
				$minutes = Config::Get('USERS_ONLINE_TIME');
			
			$sql = "SELECT p.*
					FROM ".TABLE_PREFIX."pilots p, ".TABLE_PREFIX."sessions s
					WHERE s.pilotid = p.pilotid 
					AND DATE_SUB(NOW(), INTERVAL {$minutes} MINUTE) <= s.`logintime`";
			
			$users_online = DB::get_results($sql);
			
			# Check if it's blank, then return an empty array
			if(!$users_online)
				$users_online = array();
				
			CodonCache::write($key, $users_online, 'short');
		}
		
		return $users_online;
	}
	
	public static function GuestsOnline($minutes = '')
	{
		$key = 'guests_online';
		$guests_online = CodonCache::read($key);
		
		if($guests_online === false)
		{
			if($minutes == '')
				$minutes = Config::Get('USERS_ONLINE_TIME');
			
			$sql = "SELECT s.*
					FROM ".TABLE_PREFIX."sessions s
					WHERE s.pilotid = 0
					AND DATE_SUB(NOW(), INTERVAL {$minutes} MINUTE) <= s.`logintime`";
			
			$guests_online = DB::get_results($sql);
			
			if(!$guests_online)
				$guests_online = array();
				
			CodonCache::write($key, $guests_online, 'short');
		}
		
		return $guests_online;
	}
		
	/**
	 * Get the current aircraft usage
	 */
	public static function AircraftUsage()
	{
		$key = 'stats_aircraft_usage';
		
		$aircraft_usage = CodonCache::read($key);
		if($aircraft_usage === false)
		{
			//SEC_TO_TIME(SUM(p.flighttime*60*60)) AS totaltime,
			$sql = 'SELECT a.*, a.name AS aircraft,
						COUNT(p.pirepid) AS routesflown,
						SUM(p.distance) AS distance,
						SEC_TO_TIME(SUM(TIME_TO_SEC(p.flighttime_stamp))) as totaltime,
						AVG(p.distance) AS averagedistance,
						AVG(p.flighttime) as averagetime
					FROM   '.TABLE_PREFIX.'aircraft a
					INNER JOIN '.TABLE_PREFIX.'pireps p ON (p.aircraft = a.id)
					GROUP BY a.registration';
			
			$aircraft_usage = DB::get_results($sql);
			CodonCache::write($key, $aircraft_usage, 'short');
		}
		
		return $aircraft_usage;
	}
	
	/**
	 * Show pie chart for all of the aircraft flown
	 *  by a certain pilot. Outputs image, unless $ret == true,
	 * 	then it returns the URL.
	 */
	public static function AircraftFlownGraph($ret = false)
	{
		//Select aircraft types
		$sql = 'SELECT a.name AS aircraft, COUNT(p.aircraft) AS count
				FROM '.TABLE_PREFIX.'pireps p, '.TABLE_PREFIX.'aircraft a 
				WHERE p.aircraft = a.id
				GROUP BY a.name';
		
		$stats = DB::get_results($sql);
		
		if(!$stats)
		{
			return;
		}
		
		$data = '';
		$labels = '';
		foreach($stats as $stat)
		{
			if($stat->aircraft == '') continue;
			
			$data .= $stat->count . ',';
			$labels .= $stat->aircraft.'|';
		}
		
		// remove that final lone char
		$data = substr($data, 0, strlen($data)-1);
		$labels = substr($labels, 0, strlen($labels)-1);
		
		$chart = new googleChart($data, 'pie');
		$chart->dimensions = '350x200';
		$chart->setLabels($labels);
		
		if($ret == true)
			return $chart->draw(false);
		else
			echo '<img src="'.$chart->draw(false).'" />';
	}
	
	public static function PilotAircraftFlownCounts($pilotid)
	{
		$key = 'ac_flown_counts_'.$pilotid;
		
		$counts = CodonCache::read($key);
		
		if($counts === false)
		{
			//Select aircraft types
			$sql = 'SELECT a.name AS aircraft, COUNT(p.aircraft) AS count, SUM(p.flighttime) AS hours
					FROM '.TABLE_PREFIX.'pireps p, '.TABLE_PREFIX.'aircraft a 
					WHERE p.aircraft = a.id AND p.pilotid='.intval($pilotid).'
					GROUP BY a.name';
			
			$counts = DB::get_results($sql);
			CodonCache::write($key, $counts, 'medium');
		}
		
		return $counts;
	}
	
	/**
	 * Show pie chart for all of the aircraft flown
	 *  by a certain pilot. Outputs image, unless $ret == true,
	 * 	then it returns the URL.
	 */
	public static function PilotAircraftFlownGraph($pilotid, $ret = false)
	{
		$stats = self::PilotAircraftFlownCounts($pilotid);
		
		if(!$stats)
		{
			return;
		}
		
		$data = '';
		$labels = '';
		foreach($stats as $stat)
		{
			if($stat->aircraft == '') continue;

			$data .= $stat->count . ',';
			$labels .= $stat->aircraft.'|';
		}

		// remove that final lone char
		$data = substr($data, 0, strlen($data)-1);
		$labels = substr($labels, 0, strlen($labels)-1);

		$chart = new GoogleChart($data, 'pie');
		$chart->dimensions = '350x200';
		$chart->setLabels($labels);

		
		$url = $chart->draw(false);
		unset($chart);
		
		if($ret == true)
			return $url;
		else
			echo '<img src="'.$url.'" />';
	}
	
	
	/* These contributed by simpilot from phpVMS forums
	 */
	 
	/**
	* Get the total number of pilots
	*/
	public static function PilotCount($airline_code='')
	{
		$key = 'pilot_count';
		if($airline_code != '')
		{
			$key .= '_'.$airline_code;
		}
		
		$total = CodonCache::read($key);
		
		if($total === false)
		{
			$params = array(
				'table' => TABLE_PREFIX.'pilots',
				'fields' => 'COUNT(*) as `total`',
			);
			
			if(!empty($airline_code))
			{
				$params['where']['code'] = $airline_code;
				$params['group'] = 'code';
			}
			
			$sql = DB::build_select($params);
			$results = DB::get_results($sql);
			if(!$results)
			{
				$total = 0;
			}
			else
			{
				$total = $results[0]->total;
			}
			
			CodonCache::write($key, $total, '15minute');
		}
		
		return $total;
	}
	
	
	/**
	 * Return the total number of passengers carried
	 *
	 * @return mixed This is the return value description
	 *
	 */
	public static function TotalPaxCarried($airline_code = '')
	{
		$key = 'total_pax_carried';
		if($airline_code != '')
		{
			$key .= '_'.$airline_code;
		}
		
		$total = CodonCache::read($key);
		
		if($total === false)
		{
			$params = array(
				'table' => TABLE_PREFIX.'pireps',
				'fields' => 'SUM(`load`) as `total`',
				'where' => array(
					'accepted' => PIREP_ACCEPTED,
					'flighttype' => 'P'
				),
			);
			
			if(!empty($airline_code))
			{
				$params['where']['code'] = $airline_code;
				$params['group'] = 'code';
			}
			
			$sql = DB::build_select($params);
			$results = DB::get_results($sql);
			if(!$results)
			{
				$total = 0;
			}
			else
			{
				$total = $results[0]->total;
			}
			
			CodonCache::write($key, $total, '15minute');
		}
		
		return $total;
	}
	
	
	/**
	 * Return the number of flights flown today
	 *
	 * @return int Total number of flights
	 *
	 */
	public static function TotalFlightsToday($airline_code='')
	{
		$key = 'flights_today';
		if($airline_code != '')
		{
			$key .= '_'.$airline_code;
		}
		
		$total = CodonCache::read($key);
		if($total === false)
		{
			$params = array(
				'table' => TABLE_PREFIX.'pireps',
				'fields' => 'COUNT(*) AS `total`',
				'where' => 'DATE(`submitdate`) = CURDATE()',
			);
			
			if(!empty($airline_code))
			{
				$params['where']['code'] = $airline_code;
				$params['group'] = 'code';
			}
			
			$sql = DB::build_select($params);
			$results = DB::get_results($sql);
			if(!$results)
			{
				$total = 0;
			}
			else
			{
				$total = $results[0]->total;
			}
			
			CodonCache::write($key, $total, '15minute');
		}
		
		return $total;
	}
	
	
	/**
	 * Total amount of fuel burned among all accepted PIREPS
	 *
	 * @return float In units specified in config
	 *
	 */
	public static function TotalFuelBurned($airline_code='')
	{
		$key = 'fuel_burned';
		if($airline_code != '')
		{
			$key .= '_'.$airline_code;
		}
		
		$total = CodonCache::read($key);
		if($total === false)
		{
			$params = array(
				'table' => TABLE_PREFIX.'pireps',
				'fields' => 'SUM(`fuelused`) AS `total`',
				'where' => array('accepted' => PIREP_ACCEPTED),
			);
			
			if(!empty($airline_code))
			{
				$params['where']['code'] = $airline_code;
				$params['group'] = 'code';
			}
			
			$sql = DB::build_select($params);
			$results = DB::get_results($sql);
			if(!$results)
			{
				$total = 0;
			}
			else
			{
				$total = $results[0]->total;
			}
			
			CodonCache::write($key, $total, '15minute');
		}
		
		return $total;
	}
	
	
	/**
	 * Get the total miles/km flown
	 *
	 * @return float Total distance flown in units in config
	 *
	 */
	public static function TotalMilesFlown($airline_code='')
	{
		$key = 'miles_flown';
		if($airline_code != '')
		{
			$key .= '_'.$airline_code;
		}
		
		$total = CodonCache::read($key);
		
		if($total === false)
		{
			$params = array(
				'table' => TABLE_PREFIX.'pireps',
				'fields' => 'SUM(`distance`) AS `total`',
				'where' => array('accepted' => PIREP_ACCEPTED),
			);
			
			if(!empty($airline_code))
			{
				$params['where']['code'] = $airline_code;
				$params['group'] = 'code';
			}
			
			$sql = DB::build_select($params);
			$results = DB::get_results($sql);
			if(!$results)
			{
				$total = 0;
			}
			else
			{
				$total = $results[0]->total;
			}
			
			CodonCache::write($key, $total, '15minute');
		}
		
		return $total;
	}
	
	
	/**
	 * Return the total number of aircraft in the fleet
	 *
	 * @return int Total
	 *
	 */
	public static function TotalAircraftInFleet($airline_code='')
	{
		$key = 'aircraft_in_fleet';
		if($airline_code != '')
		{
			$key .= '_'.$airline_code;
		}
		
		$total = CodonCache::read($key);
		if($total === false)
		{
			$params = array(
				'table' => TABLE_PREFIX.'aircraft',
				'fields' => 'COUNT(`id`) as `total`',
			);
			
			if(!empty($airline_code))
			{
				$params['where']['code'] = $airline_code;
				$params['group'] = 'code';
			}
			
			$sql = DB::build_select($params);
			$results = DB::get_results($sql);

			if(!$results)
			{
				$total = 0;
			}
			else
			{
				$total = $results[0]->total;
			}
			
			CodonCache::write($key, $total, '15minute');
		}
		
		return $total;
	}
	
	
	/**
	 * Return the total number of news stories
	 *
	 * @return int Total
	 *
	 */
	public static function TotalNewsItems()
	{
		$key = 'total_news_items';
		if($airline_code != '')
		{
			$key .= '_'.$airline_code;
		}
		
		$total = CodonCache::read($key);
		
		if($total === false)
		{
			$sql = 'SELECT COUNT(`id`) AS `total` 
					FROM '.TABLE_PREFIX.'news';
			
			$result = DB::get_row($sql);
			
			if(!$result)
			{
				$total = 0;
			}
			else
			{
				$total = $result->total;
			}
			
			CodonCache::write($key, $total, '15minute');		
		}
		
		return $total;
	}
	
	
	/**
	 * Return the total number of schedules in the system
	 *
	 * @return int $airline_code Total number
	 *
	 */
	public static function TotalSchedules($airline_code='')
	{
		$key = 'total_schedules';
		if($airline_code != '')
		{
			$key .= '_'.$airline_code;
		}
		
		$total = CodonCache::read($key);
		
		if($total === false)
		{
			$params = array(
				'table' => TABLE_PREFIX.'schedules',
				'fields' => 'COUNT(`id`) as `total`',
			);
			
			if(!empty($airline_code))
			{
				$params['where'] = array('code' => $airline_code);
				$params['group'] = 'code';
			}
			
			$sql = DB::build_select($params);
			$results = DB::get_results($sql);

			if(!$results)
			{
				$total = 0;
			}
			else
			{
				$total = $results[0]->total;
			}
			
			CodonCache::write($key, $total, '15minute');
		}
		
		return $total;
	}
}
