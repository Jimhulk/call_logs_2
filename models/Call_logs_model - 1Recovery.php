<?php defined('BASEPATH') or exit;

class Call_logs_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Fetch DataTable JSON, scoped to a single client if $client_id provided.
     *
     * @param int|null $client_id
     */
    public function get_table_data($client_id = null, $lead_id = null)
    {
        $columns = [
			'call_time', 'call_id', 'cost', 'from', 'to',
			'direction', 'status', 'ringing', 'talking',
			'call_activity_details'
		];

		// ******************************* CONNECT TO EXTERNAL DATABASE
		$draw        = intval($_POST['draw']    ?? 1);
		$start       = intval($_POST['start']   ?? 0);
		$length      = intval($_POST['length']  ?? 10);
		$searchValue = trim($_POST['search']['value'] ?? '');
		$order       = $_POST['order']          ?? [];

		// 2) define which columns you allow ordering/searching on
		$columns = [
			'cdr_started_at',                      // col 0
			'',                                     // col 1: no ordering/search
			'',                                     // col 2: no ordering/search
			'source_participant_phone_number',     // col 3
			// if you want to search/dorder by destination_dn_name or phone,
			// you can split them out, otherwise leave blank
			'destination_dn_name',                 // col 4
			'source_entity_type',                  // col 5
			'termination_reason',                  // col 6
			// col 7/8 are computed in PHP, so you can’t order/search them in SQL
			'', // col 7
			'', // col 8
			'', // col 9
			'', // col 10
			'', // col 11
		];
		
		$searchColumns = [
			'cdr_started_at',                      
			'source_dn_name',                                                                       
			'source_participant_phone_number',     
			'destination_dn_name',   
			'destination_dn_number',  
			'destination_participant_phone_number',
			'source_entity_type',                  
			'termination_reason',                  
			'',
			'',
			'', 
			'', 
		];

		// 3) connect
		$dbconn = pg_connect("host=135.181.232.87 port=5432 dbname=3cx user=call_logs password=4z7a?z9T1");

		// 4) build the fixed “last 30 days” WHERE clause
		$whereClauses = [ "cdr_started_at >= CURRENT_DATE - INTERVAL '2 days'" ];

		// 5) add global search filter if provided
		if ($searchValue !== '') {
			$sv = pg_escape_string($dbconn, $searchValue);
			$searchBits = [];
			foreach ($searchColumns as $col) {
				if ($col !== '') {
					if ($col === 'cdr_started_at') {
						$searchBits[] = "CAST({$col} AS TEXT) ILIKE '%{$sv}%'";
					} else {
						$searchBits[] = "{$col} ILIKE '%{$sv}%'";
					}
					//$searchBits[] = "{$col} ILIKE '%{$sv}%'";
				}
			}
			if (count($searchBits)) {
				$whereClauses[] = '(' . implode(' OR ', $searchBits) . ')';
			}
		}

		// join all WHERE pieces
		$where = 'WHERE ' . implode(' AND ', $whereClauses);

		// 6) figure out ORDER BY
		$orderBy = '';
		if (!empty($order)) {
			$colIdx = intval($order[0]['column']);
			$dir    = strtoupper($order[0]['dir']) === 'ASC' ? 'ASC' : 'DESC';
			// only use columns that are non-empty in the map
			if (! empty($columns[$colIdx])) {
				$orderBy = "ORDER BY {$columns[$colIdx]} {$dir}";
			}
		}
		
		// extra filtro gia tin periptwsi pou koitame sugkekrimeno client i lead
		$extraFilter = "";
		$uuidPattern = '/^[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-'
             . '[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-'
             . '[0-9a-fA-F]{12}$/';
		if (is_numeric($client_id)) {
			$this->db->select('call_id')
					 ->from('tblcall_logs')
					 ->where('client_id', (int)$client_id);
			$ids = array_column($this->db->get()->result_array(), 'call_id');
			
			$validIds = array_filter($ids, function($u) use ($uuidPattern) {
				return preg_match($uuidPattern, $u);
			});

			if (empty($validIds)) {
				echo json_encode([
				  'draw'            => $draw,
				  'recordsTotal'    => 0,
				  'recordsFiltered' => 0,
				  'data'            => [],
				]);
				exit;
			}

			$escaped = array_map(function($u) use ($dbconn) {
				return pg_escape_literal($dbconn, $u);
			}, $validIds);
			$inList = implode(',', $escaped);
			$extraFilter = "AND call_history_id IN ({$inList})";
		}
		elseif (is_numeric($lead_id)) {
			$this->db->select('call_id')
					 ->from('tblcall_logs')
					 ->where('lead_id', (int)$lead_id);
			$ids = array_column($this->db->get()->result_array(), 'call_id');
			
			$validIds = array_filter($ids, function($u) use ($uuidPattern) {
				return preg_match($uuidPattern, $u);
			});

			if (empty($validIds)) {
				echo json_encode([
				  'draw'            => $draw,
				  'recordsTotal'    => 0,
				  'recordsFiltered' => 0,
				  'data'            => [],
				]);
				exit;
			}

			$escaped = array_map(function($u) use ($dbconn) {
				return pg_escape_literal($dbconn, $u);
			}, $validIds);
			$inList = implode(',', $escaped);
			$extraFilter = "AND call_history_id IN ({$inList})";
		}
		
		

		// 7) get totalRecords (just the date filter, no search)
		$resTotal = pg_query($dbconn, "
			SELECT COUNT(*) AS cnt
			  FROM cdroutput
			 WHERE cdr_started_at >= CURRENT_DATE - INTERVAL '30 days'
			 AND  NOT (char_length(source_dn_number)=3 AND char_length(destination_dn_number)=3)
			 {$extraFilter}
		");
		$totalRecords = (int) pg_fetch_result($resTotal, 0, 'cnt');

		// 8) get recordsFiltered (date + search)
		$resFiltered = pg_query($dbconn, "
			SELECT COUNT(*) AS cnt
			  FROM cdroutput
			{$where}
			AND  NOT (char_length(source_dn_number)=3 AND char_length(destination_dn_number)=3)
			{$extraFilter}
		");
		$recordsFiltered = (int) pg_fetch_result($resFiltered, 0, 'cnt');

		// 9) fetch the actual page of data
		//    note: only apply LIMIT/OFFSET if length > 0
		$limitOffset = $length > 0
			? "LIMIT {$length} OFFSET {$start}"
			: '';

		$resData = pg_query($dbconn, "
			SELECT *
			  FROM cdroutput
			{$where}
			AND  NOT (char_length(source_dn_number)=3 AND char_length(destination_dn_number)=3)
			{$extraFilter}
			{$orderBy}
			{$limitOffset}
		");
		$data = pg_fetch_all($resData) ?: [];

		pg_close($dbconn);

		$json = [
			'draw'            => $draw,
			'recordsTotal'    => $totalRecords,
			'recordsFiltered' => $recordsFiltered,
			'data'            => [],
		];
		
		$buckets = [];
		foreach ($data as $row) {
			$buckets[$row['call_history_id']][] = $row;
		}
		foreach ($buckets as $callId => &$rows) {
			usort($rows, function($a, $b) {
				$ta = new DateTime($a['cdr_started_at']);
				$tb = new DateTime($b['cdr_started_at']);
				return $ta <=> $tb;
			});
		}
		unset($rows);
		
		foreach ($buckets as $call_history_id => $rows) {
			// Copy the full group for the modal payload
			$fullGroup = $rows;

			$last = array_pop($rows); // now $rows has all but the last
			//$last = array_shift($rows);

			// Build the modal payload (all rows, including that last one)
			$payload = [];
			foreach ($fullGroup as $r) {
				
				// Direction
				$translated_direction = '';
				if($r['source_entity_type'] == "extension"){
					$translated_direction = "Εξερχόμενη";
				}else if($r['source_entity_type'] == "external_line"){
					$translated_direction = "Εισερχόμενη";
				}else{
					
				}
				
				// Ringing kai Talking
				if($r['cdr_started_at'] != null){
					$d1 = DateTime::createFromFormat('Y-m-d H:i:s.u O', $r['cdr_started_at']);
				}else{
					$d1 = false;
				}
				if($r['cdr_answered_at'] != null){
					$d2 = DateTime::createFromFormat('Y-m-d H:i:s.u O', $r['cdr_answered_at']);
				}else{
					$d2 = false;
				}
				if($r['cdr_ended_at'] != null){
					$d3 = DateTime::createFromFormat('Y-m-d H:i:s.u O', $r['cdr_ended_at']);
				}else{
					$d3 = false;
				}
				
				if ($d1 && $d2) {
					$interval = $d1->diff($d2);
					$ringing = $interval->format('%H:%I:%S');
				} else if($d1 && $d3){
					$interval = $d1->diff($d3);
					$ringing = $interval->format('%H:%I:%S');
				}else {
					$ringing = '00:00:00';
				}

				if ($d2 && $d3) {
					$interval = $d2->diff($d3);
					$talking = $interval->format('%H:%I:%S');
				} else {
					$talking = '00:00:00';
				}
				
				// From kai To
				$from = '';
				if(strlen($r['source_participant_phone_number']) == 3){
					$from = $r['source_dn_name']." ".$r['source_participant_phone_number'];
				}else{
					$from = $r['source_participant_phone_number'];
				}
				
				$to = '';
				if(strlen($r['destination_dn_number']) == 3){
					$to = $r['destination_dn_name']." ".$r['destination_dn_number'];
				}else if(strlen($r['destination_participant_phone_number']) > 3){
					$to = $r['destination_participant_phone_number'];
				}else{
					$to = $r['destination_dn_name'];
				}
				
				// Details
				$translated_details = '';
				if( ($r['termination_reason'] == "src_participant_terminated") || ($r['termination_reason'] == "cancelled") ){
					$translated_details = 'Έληξε απο '.$from;
				}else if( $r['termination_reason'] == "dst_participant_terminated" ){
					$translated_details = 'Έληξε απο '.$to;
				}else if($r['termination_reason'] == "rejected"){
					$translated_details = 'Απορρίφθηκε απο '.$to;
				}else{
					$translated_details = $r['termination_reason'];
				}
				
				// Status
				$translated_status = $r['termination_reason'];
				if($r['termination_reason'] == "src_participant_terminated"){
					$translated_status = 'Απαντήθηκε';
				}else if($r['termination_reason'] == "dst_participant_terminated"){
					$translated_status = 'Απαντήθηκε';
				}else if($r['termination_reason'] == "cancelled"){
					$translated_status = 'Αναπάντητη';
				}else if($r['termination_reason'] == "rejected"){
					$translated_status = 'Απορρίφθηκε';
				}else if($r['termination_reason'] == "redirected"){
					$translated_status = 'Ανακατευθύνθηκε';
				}else if($r['termination_reason'] == "continued_in"){
					$translated_status = 'Συνεχίστηκε';
				}else{
					$translated_status = $r['termination_reason'];
				}
				
				//cost
				$cost = "0.00";	// peripou 0.017 lepta to deuterolepto
				if($translated_direction == "Εξερχόμενη"){
					list($h, $m, $s) = explode(':', $talking);
					$totalSeconds = ((int)$h * 3600) + ((int)$m * 60) + (int)$s;
					$rawResult = $totalSeconds * 0.017;
					$cost = number_format($rawResult, 2, '.', '');
				}
				
				// Recordings
				$dt = DateTime::createFromFormat('Y-m-d H:i:s.uP', $r['cdr_started_at']);
				$dt->setTimezone(new DateTimeZone('UTC'));
				list($h, $m, $s) = explode(':', $ringing);
				$interval = new DateInterval(sprintf('PT%dH%dM%dS', $h, $m, $s));
				$dt->add($interval);
				$output = $dt->format('Y-m-d H:i:s');
				
				$extension = 0;
				if( strlen(preg_replace('/\D/', '', $r['source_participant_phone_number'])) === 3){
					$extension = $r['source_participant_phone_number'];
					$external = $r['destination_participant_phone_number'];
				}else{
					$extension = $r['destination_dn_number'];
					$external = $r['source_participant_phone_number'];
				}
				
				$staff = $this->db
						->where('extension', $extension)
						->get('tblstaff')
						->row();
				
				$base_string = "Recording not found";
				$recording_ref = "Den uparxei";
				$plus_one_base = "DOG";
				if($staff != null){
					$base_string = "[".$staff->lastname.", ".$staff->firstname."]_".$extension."-".$external."_".str_replace(['-', ' ', ':'], '', $output)."(arithmos).wav";
					
					$recordings_dir = FCPATH . 'recordings/'.$extension;
					$candidates = [];
					if (is_dir($recordings_dir)) {
						$files = scandir($recordings_dir);
						foreach ($files as $file) {
							$candidates[] = $file;
						}
					}
					
					foreach ($candidates as $candidate) {
						$string_parts = explode('(',$candidate);
						
						$dtPlusOne   = clone $dt;
						$dtPlusOne->modify('+1 second');
						$plusOneSec  = $dtPlusOne->format('YmdHis');

						$dtMinusOne  = clone $dt;
						$dtMinusOne->modify('-1 second');
						$minusOneSec = $dtMinusOne->format('YmdHis');
						
						$dtPlusTwo   = clone $dt;
						$dtPlusTwo->modify('+2 seconds');
						$plusTwoSec  = $dtPlusTwo->format('YmdHis');

						$dtMinusTwo  = clone $dt;
						$dtMinusTwo->modify('-2 seconds');
						$minusTwoSec = $dtMinusTwo->format('YmdHis');
						
						$pure_base = substr(explode('(',$base_string)[0], 0, -14);
						$plus_one_base = $pure_base . "" . $plusOneSec;
						$minus_one_base = $pure_base . "" . $minusOneSec;
						$plus_two_base = $pure_base . "" . $plusTwoSec;
						$minus_two_base = $pure_base . "" . $minusTwoSec;
						
						if($string_parts[0] == explode('(',$base_string)[0]){
							$recording_ref = $candidate;
							break;
						}
						
						if($string_parts[0] == $plus_one_base){
							$recording_ref = $candidate;
							break;
						}
						
						if($string_parts[0] == $minus_one_base){
							$recording_ref = $candidate;
							break;
						}
						
						if($string_parts[0] == $plus_two_base){
							$recording_ref = $candidate;
							break;
						}
						
						if($string_parts[0] == $minus_two_base){
							$recording_ref = $candidate;
							break;
						}
					}
				}
				$file_path = FCPATH . "recordings/".$extension."/" . $recording_ref;
				$recording_button = "";
				if (file_exists($file_path)) {
					$recording_url = base_url("recordings/".$extension."/" . $recording_ref);
					//$recording_button = "<a href='$recording_url' download><i class='fas fa-download'></i>$recording_ref</a>";
					$recording_button = "<audio controls style='width: 100%'><source src='$recording_url' type='audio/wav'>Your browser does not support the audio element. /audio>";
				} else {
					$recording_button = '<span class="text-danger">Recording not found</span>';
				}
				
				if (!has_permission('call_logs_recordings','','view')) {
					$recording_button = '<span class="text-danger">You do not have permission</span>';
				}
				
				$dt = DateTime::createFromFormat('Y-m-d H:i:s.uP', $r['cdr_started_at']);
				$dt->modify('+1 hour');	
				$output = $dt->format('Y-m-d H:i:s');
				$payload[] = [
					'call_time'             => $output,//$r['cdr_started_at'],
					'call_id'               => $r['call_history_id'],
					'from'                  => $from,
					'to'                    => $to,
					'direction'             => $translated_direction,
					'status'                => $translated_status,
					'ringing'               => $ringing,
					'talking'               => $talking,
					'cost'                  => $cost,
					'call_activity_details' => $translated_details,
					'recording'             => $recording_button,//"<a id='6' href='#sound'><i class='fas fa-download'></i></a>",
				];
			}
			$jsonPayload = htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8');

			$view_button = "<button class='btn btn-xs btn-info view-group' data-group='{$jsonPayload}' title='Show all'><i class='fas fa-list'></i></button>";
			
			// Ringing and talking
			if($last['cdr_started_at'] != null){
				$d1 = DateTime::createFromFormat('Y-m-d H:i:s.u O', $last['cdr_started_at']);
			}else{
				$d1 = false;
			}
			if($last['cdr_answered_at'] != null){
				$d2 = DateTime::createFromFormat('Y-m-d H:i:s.u O', $last['cdr_answered_at']);
			}else{
				$d2 = false;
			}
			if($last['cdr_ended_at'] != null){
				$d3 = DateTime::createFromFormat('Y-m-d H:i:s.u O', $last['cdr_ended_at']);
			}else{
				$d3 = false;
			}
			
			if ($d1 && $d2) {
				$interval = $d1->diff($d2);
				$ringing = $interval->format('%H:%I:%S');
			} else if($d1 && $d3){
				$interval = $d1->diff($d3);
				$ringing = $interval->format('%H:%I:%S');
			}else{
				$ringing = '00:00:00';
			}

			if ($d2 && $d3) {
				$interval = $d2->diff($d3);
				$talking = $interval->format('%H:%I:%S');
			} else {
				$talking = '00:00:00';
			}

			// Profile and contact link
			$this->db->where('call_id', $last['call_history_id']);
			$query = $this->db->get('tblcall_logs');
			$inTable = $query->result();
			
			$profile_link = "";
			$contact_link = "";
			if($inTable != null){
				if ($inTable[0]->client_id != null) {
					$client = $this->db
						->where('userid', $inTable[0]->client_id)
						->get('tblclients')
						->row();
					$anchor = admin_url('clients/client/' . $client->userid);
					$profile_link = '<a href="' . $anchor . '">' . html_escape($client->company) . '</a>';
					
					$toDigits   = preg_replace('/\D+/', '', $inTable[0]->to);
					$fromDigits = preg_replace('/\D+/', '', $inTable[0]->from);
					$toNorm   = substr($toDigits,   -10);
					$fromNorm = substr($fromDigits, -10);
					
					// 1. Get all contacts for this client
					$contacts = $this->db
						->where('userid', $inTable[0]->client_id)
						->get('tblcontacts')
						->result();
					
					// 2. Get contact custom field IDs
					$slugs = [
						'contacts_til_ergasias',
						'contacts_tilefono_allo',
						'contacts_stathero_tilefono'
					];
					$this->db->select('id');
					$this->db->where_in('slug', $slugs);
					$custom_fields = $this->db->get('tblcustomfields')->result_array();
					$custom_field_ids = array_column($custom_fields, 'id');
					
					// 3. Get custom field values for these contacts
					$custom_phones = [];
					if (!empty($contacts) && !empty($custom_field_ids)) {
						$contact_ids = array_column($contacts, 'id');
						$this->db->select('relid as contact_id, value');
						$this->db->where_in('relid', $contact_ids);
						$this->db->where_in('fieldid', $custom_field_ids);
						$this->db->where('fieldto', 'contacts');
						$custom_phones = $this->db->get('tblcustomfieldsvalues')->result();
					}
					
					// 4. Search for matching contact
					$contact = null;
					foreach ($contacts as $c) {
						// Check main phone number
						$main_phone = preg_replace('/\D+/', '', $c->phonenumber);
						$main_phone = substr($main_phone, -10);
						
						if ($main_phone === $toNorm || $main_phone === $fromNorm) {
							$contact = $c;
							break;
						}
						
						// Check custom fields
						foreach ($custom_phones as $cp) {
							if ($cp->contact_id == $c->id) {
								$custom_phone = preg_replace('/\D+/', '', $cp->value);
								$custom_phone = substr($custom_phone, -10);
								
								if ($custom_phone === $toNorm || $custom_phone === $fromNorm) {
									$contact = $c;
									break 2; // Break both loops
								}
							}
						}
					}
					
					// 5. Create contact link
					$anchorContact = admin_url('clients/client/' . $client->userid . '?group=contacts');
					if ($contact == null) {
						$contactName = "Δεν βρέθηκε επαφή";
					} else {
						$contactName = html_escape($contact->firstname) . " " . html_escape($contact->lastname);
					}
					$contact_link = '<a href="' . $anchorContact . '">' . $contactName . '</a>';
				}else if($inTable[0]->lead_id != null){
					$lead = $this->db
						->where('id', $inTable[0]->lead_id)
						->get('tblleads')
						->row();
					$profile_link = '<a href="'. admin_url("leads") .'/index/'. $inTable[0]->lead_id .'">'. html_escape($lead->name) .' (Lead)</a>';
				}
			}
			
			// To and From
			$from = '';
			if(strlen($last['source_participant_phone_number']) == 3){
				$from = $last['source_dn_name']." ".$last['source_participant_phone_number'];
			}else{
				$from = $last['source_participant_phone_number'];
			}
			
			$to = '';
			if(strlen($last['destination_dn_number']) == 3){
				$to = $last['destination_dn_name']." ".$last['destination_dn_number'];
			}else if(strlen($last['destination_participant_phone_number']) > 3){
				$to = $last['destination_participant_phone_number'];
			}else{
				$to = $last['destination_dn_name'];
			}
			
			// Inbound/Outbound translated
			$translated_source = '';
			if($last['source_entity_type'] == "extension"){
				$translated_source = "Εξερχόμενη";
			}else if($last['source_entity_type'] == "external_line"){
				$translated_source = "Εισερχόμενη";
			}else{
				
			}
			
			// Translated details
			$translated_details = '';
			if( ($last['termination_reason'] == "src_participant_terminated") || ($last['termination_reason'] == "cancelled") ){
				$translated_details = 'Έληξε απο '.$from;
			}else if( $last['termination_reason'] == "dst_participant_terminated" ){
				$translated_details = 'Έληξε απο '.$to;
			}else if($last['termination_reason'] == "rejected"){
				$translated_details = 'Απορρίφθηκε απο '.$to;
			}else{
				$translated_details = $last['termination_reason'];
			}
			
			// Status
			$status = $last['termination_reason'];
			if($last['termination_reason'] == "src_participant_terminated"){
				$status = 'Απαντήθηκε';
			}else if($last['termination_reason'] == "dst_participant_terminated"){
				$status = 'Απαντήθηκε';
			}else if($last['termination_reason'] == "cancelled"){
				$status = 'Αναπάντητη';
			}else if($last['termination_reason'] == "rejected"){
				$status = 'Απορρίφθηκε';
			}else{
				$status = $last['termination_reason'];
			}
			
			
			// Recordings
			$dt = DateTime::createFromFormat('Y-m-d H:i:s.uP', $last['cdr_started_at']);
			$dt->setTimezone(new DateTimeZone('UTC'));
			list($h, $m, $s) = explode(':', $ringing);
			$interval = new DateInterval(sprintf('PT%dH%dM%dS', $h, $m, $s));
			$dt->add($interval);
			$output = $dt->format('Y-m-d H:i:s');
			
			$extension = 0;
			if( strlen(preg_replace('/\D/', '', $last['source_participant_phone_number'])) === 3){
				$extension = $last['source_participant_phone_number'];
				$external = $last['destination_participant_phone_number'];
			}else{
				$extension = $last['destination_dn_number'];
				$external = $last['source_participant_phone_number'];
			}
			
			$staff = $this->db
					->where('extension', $extension)
					->get('tblstaff')
					->row();
			
			$base_string = "Recording not found";
			$recording_ref = "Den uparxei";
			if($staff != null){
				$base_string = "[".$staff->lastname.", ".$staff->firstname."]_".$extension."-".$external."_".str_replace(['-', ' ', ':'], '', $output)."(arithmos).wav";
				
				$recordings_dir = FCPATH . 'recordings/'.$extension;
				$candidates = [];
				if (is_dir($recordings_dir)) {
					$files = scandir($recordings_dir);
					foreach ($files as $file) {
						$candidates[] = $file;
					}
				}
				
				foreach ($candidates as $candidate) {
					$string_parts = explode('(',$candidate);
					
					$dtPlusOne   = clone $dt;
					$dtPlusOne->modify('+1 second');
					$plusOneSec  = $dtPlusOne->format('YmdHis');

					$dtMinusOne  = clone $dt;
					$dtMinusOne->modify('-1 second');
					$minusOneSec = $dtMinusOne->format('YmdHis');
					
					$dtPlusTwo   = clone $dt;
					$dtPlusTwo->modify('+2 seconds');
					$plusTwoSec  = $dtPlusTwo->format('YmdHis');

					$dtMinusTwo  = clone $dt;
					$dtMinusTwo->modify('-2 seconds');
					$minusTwoSec = $dtMinusTwo->format('YmdHis');
					
					$pure_base = substr(explode('(',$base_string)[0], 0, -14);
					$plus_one_base = $pure_base . "" . $plusOneSec;
					$minus_one_base = $pure_base . "" . $minusOneSec;
					$plus_two_base = $pure_base . "" . $plusTwoSec;
					$minus_two_base = $pure_base . "" . $minusTwoSec;
					
					if($string_parts[0] == explode('(',$base_string)[0]){
						$recording_ref = $candidate;
						break;
					}
					
					if($string_parts[0] == $plus_one_base){
						$recording_ref = $candidate;
						break;
					}
					
					if($string_parts[0] == $minus_one_base){
						$recording_ref = $candidate;
						break;
					}
					
					if($string_parts[0] == $plus_two_base){
						$recording_ref = $candidate;
						break;
					}
					
					if($string_parts[0] == $minus_two_base){
						$recording_ref = $candidate;
						break;
					}
				}
			}
			
			$file_path = FCPATH . "recordings/".$extension."/" . $recording_ref;
			$recording_button = "";
			if (file_exists($file_path)) {
				$recording_url = base_url("recordings/".$extension."/" . $recording_ref);
				//$recording_button = "<a href='$recording_url' download><i class='fas fa-download'></i>$recording_ref</a>";
				$recording_button = "<audio controls style='width: 100%'><source src='$recording_url' type='audio/wav'>Your browser does not support the audio element. /audio>";
			} else {
				$recording_button = '<span class="text-danger">Recording not found</span>';
			}
			
			if (!has_permission('call_logs_recordings','','view')) {
				$recording_button = '<span class="text-danger">You do not have permission</span>';
			}
			
			// Call time
			$dt = DateTime::createFromFormat('Y-m-d H:i:s.uP', $last['cdr_started_at']);
			$dt->modify('+1 hour');						// MWLIS FUGEI TO KALOKAIRI KAI ALLAKSOUEM SE XEIMERINI WRA TO VAZOUME SE SXOLIA
			$output = $dt->format('Y-m-d H:i:s');
			// Final data to send
			$json['data'][] = [
				$output,//$last['cdr_started_at'],
				$profile_link,
				$contact_link,
				$from,
				$to,
				$translated_source,//$last['source_entity_type'],
				$status,//$last['termination_reason'],
				$ringing,
				$talking,
				$translated_details,
				$recording_button,
				$view_button,
			];
		}
		// *******************************


        echo json_encode($json);
        exit;
    }
}