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

        $draw        = intval($this->input->post('draw'));
        $start       = intval($this->input->post('start'));
        $length      = intval($this->input->post('length'));
        $searchValue = $this->input->post('search')['value'] ?? '';
        $order       = $this->input->post('order');

        $totalRecords = $this->db
            ->where('client_id', (int)$client_id)
            ->count_all_results('tblcall_logs');

        $this->db->from('tblcall_logs');
        if (is_numeric($client_id)) {
            $this->db->where('client_id', (int)$client_id);
        }else if(is_numeric($lead_id)){
			$this->db->where('lead_id', (int)$lead_id);
		}

        if (!empty($searchValue)) {
            $this->db->group_start();
            foreach ($columns as $col) {
                $this->db->or_like($col, $searchValue);
            }
            $this->db->group_end();
        }

        $recordsFiltered = $this->db->count_all_results('', false);

        if (!empty($order)) {
            $colIndex    = intval($order[0]['column']);
            $dir         = ($order[0]['dir'] === 'asc') ? 'ASC' : 'DESC';
            $orderColumn = $columns[$colIndex] ?? $columns[0];
            $this->db->order_by($orderColumn, $dir);
        }

        $this->db->limit($length, $start);

        $query = $this->db->get();
        $data  = $query->result();

        $json = [
            'draw'            => $draw,
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $recordsFiltered,
            'data'            => []
        ];
		
		// 1) Bucket all rows by call_id
		$buckets = [];
		foreach ($data as $row) {
			$buckets[$row->call_id][] = $row;
		}

		// 2) Emit one table-row per call_id, using the *last* element as the main row
		$json['data'] = [];
		foreach ($buckets as $call_id => $rows) {
			// Copy the full group for the modal payload
			$fullGroup = $rows;

			// Grab the *last* instance for the main table
			$last = array_pop($rows); // now $rows has all but the last

			// Build the modal payload (all rows, including that last one)
			$payload = [];
			foreach ($fullGroup as $r) {
				
				$translated_direction = $r->direction;
				if($r->direction == "Inbound"){
					$translated_direction = "Εισερχόμενη";
				}else if($r->direction == "Outbound"){
					$translated_direction = "Εξερχόμενη";
				}
				
				$translated_status = $r->status;
				if($r->status == "Answered"){
					$translated_status = "Απαντήθηκε";
				}else if($r->status == "Unanswered"){
					$translated_status = "Αναπάντητη";
				}else if($r->status == "Redirected"){
					$translated_status = "Ανακατευθύνθηκε";
				}
				
				$payload[] = [
					'call_time'             => _dt($r->call_time),
					'call_id'               => $r->call_id,
					'from'                  => $r->from,
					'to'                    => $r->to,
					'direction'             => $translated_direction,//$r->direction,
					'status'                => $translated_status,//$r->status,
					'ringing'               => $r->ringing,
					'talking'               => $r->talking,
					'cost'                  => $r->cost,
					'call_activity_details' => $r->call_activity_details,
					'recording'             => "<a id='{$r->id}' href='#sound'><i class='fas fa-download'></i></a>",
				];
			}
			$jsonPayload = htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8');

			// Only show the button if there’s more than one row
			/*$view_button = count($fullGroup) > 1
				? "<button class='btn btn-xs btn-info view-group' data-group='{$jsonPayload}' title='Show all'><i class='fas fa-list'></i></button>"
				: '';*/
			$view_button = "<button class='btn btn-xs btn-info view-group' data-group='{$jsonPayload}' title='Show all'><i class='fas fa-list'></i></button>";

			$profile_link = null;
			$contact_link = null;
			if ($last->client_id != null) {
    // Get client
    $client = $this->db
        ->where('userid', $last->client_id)
        ->get('tblclients')
        ->row();
    $anchor = admin_url('clients/client/' . $client->userid);
    $profile_link = '<a href="' . $anchor . '">' . html_escape($client->company) . '</a>';
    
    // Normalize phone numbers
    $toDigits   = preg_replace('/\D+/', '', $last->to);
    $fromDigits = preg_replace('/\D+/', '', $last->from);
    $toNorm   = substr($toDigits,   -10);
    $fromNorm = substr($fromDigits, -10);
    
    // 1. Get all contacts for this client
    $contacts = $this->db
        ->where('userid', $last->client_id)
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
}else if($last->lead_id != null){
				$lead = $this->db
					->where('id', $last->lead_id)
					->get('tblleads')
					->row();
				$profile_link = '<a href="'. admin_url("leads") .'/index/'. $last->lead_id .'">'. html_escape($lead->name) .' (Lead)</a>';
			}
			
			$parts = explode('->', $last->call_activity_details);
			$nice_details = trim(array_pop($parts));
			$translated_details = '';
			if (strpos($nice_details, 'Ended by ') === 0) {
				$who = substr($nice_details, strlen('Ended by '));
				$translated_details = 'Έληξε ο ' . $who;
			} elseif ($nice_details === 'Declined') {
				$translated_details = 'Απορρίφθηκε';
			} else {
				$translated_details = $nice_details;
			}
			
			$translated_direction = $last->direction;
			if($last->direction == "Inbound"){
				$translated_direction = "Εισερχόμενη";
			}else if($last->direction == "Outbound"){
				$translated_direction = "Εξερχόμενη";
			}
			
			$translated_status = $last->status;
			if($last->status == "Answered"){
				$translated_status = "Απαντήθηκε";
			}else if($last->status == "Unanswered"){
				$translated_status = "Αναπάντητη";
			}else if($last->status == "Redirected"){
				$translated_status = "Ανακατευθύνθηκε";
			}
			// Emit the *last* row’s cells + our button
			$json['data'][] = [
				_dt($last->call_time),
				$profile_link,//$last->call_id,
				$contact_link,//$last->cost,
				$last->from,
				$last->to,
				$translated_direction,//$last->direction,
				$translated_status,//$last->status,
				$last->ringing,
				$last->talking,
				
				$translated_details,//$nice_details,//$last->call_activity_details,
				"<a id='{$last->id}' href='#sound'><i class='fas fa-download'></i></a>",
				$view_button,
			];
		}


        echo json_encode($json);
        exit;
    }
}