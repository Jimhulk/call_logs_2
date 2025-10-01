<?php defined('BASEPATH') or exit('No direct script access allowed');

use app\services\proposals\ProposalsPipeline;

class Call_logs extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('call_logs_model');
		$this->load->helper('clients');
		
		$this->load->model('proposals_model');
    }

    public function index()
    {
        if ($this->input->is_ajax_request()) {
            $this->call_logs_model->get_table_data();
        }

		$this->load->model('staff_model');
		$staff_id = $this->session->userdata('staff_user_id');
		$data['staff'] = $this->staff_model->get($staff_id);
		
		$this->load->model('clients_model');
		$data['clients'] = $this->clients_model->get();
		
		$this->load->model('leads_model');
		$data['leads'] = $this->leads_model->get();
		
        $data['title'] = _l('call_logs');
		$this->load->view('main', $data);
    }
	
	public function client($client_id)
    {
        if ($this->input->is_ajax_request()) {
            $this->call_logs_model->get_table_data($client_id, null);
        }
		
		$this->load->helper('clients');
		
		$client = $this->clients_model->get($client_id);
		
		$userid = $client->userid;
		$tabs = [
			'profile'       => ['slug'=>'profile',       'name'=>_l('profile'),                'href'=>admin_url("clients/client/{$userid}?group=profile"),                'icon'=>'fa fa-user-circle'],
			'contacts'      => ['slug'=>'contacts',      'name'=>_l('contacts'),               'href'=>admin_url("clients/client/{$userid}?group=contacts"),              'icon'=>'fa-regular fa-user'],
			'notes'         => ['slug'=>'notes',         'name'=>_l('notes'),                  'href'=>admin_url("clients/client/{$userid}?group=notes"),                 'icon'=>'fa-regular fa-note-sticky'],
			'statements'    => ['slug'=>'statements',    'name'=>_l('statement'),             'href'=>admin_url("clients/client/{$userid}?group=statements"),            'icon'=>'fa fa-area-chart'],
			'call_logs'     => ['slug'=>'call_logs',     'name'=>_l('call_logs'),             'view'=>'call_logs/client_tab',                                           'icon'=>'fa fa-phone'],
			'invoices'      => ['slug'=>'invoices',      'name'=>_l('invoices'),               'href'=>admin_url("clients/client/{$userid}?group=invoices"),              'icon'=>'fa fa-file-text'],
			'payments'      => ['slug'=>'payments',      'name'=>_l('payments'),               'href'=>admin_url("clients/client/{$userid}?group=payments"),              'icon'=>'fa fa-line-chart'],
			'proposals'     => ['slug'=>'proposals',     'name'=>_l('proposals'),              'href'=>admin_url("clients/client/{$userid}?group=proposals"),             'icon'=>'fa-regular fa-file-powerpoint'],
			'credit_notes'  => ['slug'=>'credit_notes',  'name'=>_l('credit_notes'),           'href'=>admin_url("clients/client/{$userid}?group=credit_notes"),          'icon'=>'fa-regular fa-file-lines'],
			'estimates'     => ['slug'=>'estimates',     'name'=>_l('estimates'),              'href'=>admin_url("clients/client/{$userid}?group=estimates"),             'icon'=>'fa-regular fa-file'],
			'subscriptions' => ['slug'=>'subscriptions', 'name'=>_l('subscriptions'),          'href'=>admin_url("clients/client/{$userid}?group=subscriptions"),         'icon'=>'fa fa-sync-alt'],
			'expenses'      => ['slug'=>'expenses',      'name'=>_l('expenses'),               'href'=>admin_url("clients/client/{$userid}?group=expenses"),              'icon'=>'fa-regular fa-file-lines'],
			'contracts'     => ['slug'=>'contracts',     'name'=>_l('contracts'),              'href'=>admin_url("clients/client/{$userid}?group=contracts"),             'icon'=>'fa-regular fa-note-sticky'],
			'projects'      => ['slug'=>'projects',      'name'=>_l('projects'),               'href'=>admin_url("clients/client/{$userid}?group=projects"),              'icon'=>'fa-solid fa-chart-gantt'],
			'tasks'         => ['slug'=>'tasks',         'name'=>_l('tasks'),                  'href'=>admin_url("clients/client/{$userid}?group=tasks"),                 'icon'=>'fa-regular fa-circle-check'],
			'tickets'       => ['slug'=>'tickets',       'name'=>_l('tickets'),                'href'=>admin_url("clients/client/{$userid}?group=tickets"),               'icon'=>'fa-regular fa-life-ring'],
			'files'         => ['slug'=>'files',         'name'=>_l('files'),                  'href'=>admin_url("clients/client/{$userid}?group=files"),                 'icon'=>'fa fa-paperclip'],
			'vault'         => ['slug'=>'vault',         'name'=>_l('vault'),                  'href'=>admin_url("clients/client/{$userid}?group=vault"),                 'icon'=>'fa fa-lock'],
			'reminders'     => ['slug'=>'reminders',     'name'=>_l('reminders'),              'href'=>admin_url("clients/client/{$userid}?group=reminders"),             'icon'=>'fa-regular fa-bell'],
			'map'           => ['slug'=>'map',           'name'=>_l('map'),                    'href'=>admin_url("clients/client/{$userid}?group=map"),                   'icon'=>'fa fa-map-marker-alt'],
		];

		$data = [
			'client'        => $client,
			'title'         => $client->company,
			'customer_tabs' => $tabs,
			'group'         => 'call_logs',
		];
        $this->load->view('client_logs', $data);
    }
	
	public function lead($lead_id)
    {
        if ($this->input->is_ajax_request()) {
            $this->call_logs_model->get_table_data(null, $lead_id);
        }

        //$data['title'] = _l('call_logs');
		//$this->load->view('main', $data);
    }
	
	public function save_call()
	{
		if (! $this->input->is_ajax_request()) {
			show_404();
		}

		try{
			$ids_json = $this->input->post('callDetailsIds');
			$ids = json_decode($ids_json, true);

			if (! is_array($ids) || empty($ids)) {
				throw new Exception('No valid IDs to update');
			}
			
			$client_id = 0;
			$lead_id = 0;
			$allclients_id = 0;
			$client_or_lead = $this->input->post('client_or_lead');
			$typeClient = 0;
			$note = $this->input->post('noteTextarea');
			
			$this->load->library('session');
			$staff_id = $this->session->userdata('staff_user_id');
			
			$response_code = 0;
			
			if($client_or_lead == "client"){
				$client_id = (int)$this->input->post('main_id');
				$lead_id = 0;
				$allclients_id = 0;
				
				if($client_id == -1){
					$to_insert = (int)$this->input->post('allclients_id');
				}else{
					$to_insert = $client_id;
				}
				/*$this->db
					 ->where('rel_id',   $to_insert)
					 ->where('rel_type', 'client');
				$existing = $this->db->get('tblnotes');
				if ($existing->num_rows() > 0) {
					$this->db
						 ->where('rel_id',   $to_insert)
						 ->where('rel_type', 'client')
						 ->update('tblnotes', ["description" => $note, "dateadded" => date('Y-m-d H:i:s')]);
				}else{
					$this->db->insert('tblnotes', ["rel_id" => $to_insert, "rel_type" => "client", "description" => $note, "dateadded" => date('Y-m-d H:i:s')]);
				}*/
				if (trim($note) != '') {
					$this->db->insert('tblnotes', ["rel_id" => $to_insert, "rel_type" => "customer", "description" => $note, "addedfrom" => $staff_id,"dateadded" => date('Y-m-d H:i:s')]);
				}
			}else if($client_or_lead == "lead"){
				$client_id = 0;
				$lead_id = (int)$this->input->post('main_id');
				$allclients_id = 0;
				
				/**************************************CUSTOM FIELDS KAI REMARKS*/
				$katastasi = $this->input->post('katastasi');
				
				$cf_arithmos_epikoinonias = $this->db->select('*')->from('tblcustomfields')->where('fieldto', 'leads')->where('slug', 'leads_arithmos_epikinonias')->get()->row_array();												 
				$cf_voip = 					$this->db->select('*')->from('tblcustomfields')->where('fieldto', 'leads')->where('slug', 'leads_voip')->get()->row_array();												 
				$cf_no_answer = 			$this->db->select('*')->from('tblcustomfields')->where('fieldto', 'leads')->where('slug', 'leads_no_answer')->get()->row_array();				 
				$cf_akiromena_rantevou = 	$this->db->select('*')->from('tblcustomfields')->where('fieldto', 'leads')->where('slug', 'leads_akiromena_rantevou')->get()->row_array();
												 
				$cfv_exists_arithmos = 		$this->db->select('*')->from('tblcustomfieldsvalues')->where('fieldto', 'leads')->where('fieldid', $cf_arithmos_epikoinonias['id'])->where('relid', $lead_id)->get()->row_array();
				$cfv_exists_voip = 			$this->db->select('*')->from('tblcustomfieldsvalues')->where('fieldto', 'leads')->where('fieldid', $cf_voip['id'])->where('relid', $lead_id)->get()->row_array();
				$cfv_exists_no_answer = 	$this->db->select('*')->from('tblcustomfieldsvalues')->where('fieldto', 'leads')->where('fieldid', $cf_no_answer['id'])->where('relid', $lead_id)->get()->row_array();
				$cfv_exists_akiromena_rantevou = 	$this->db->select('*')->from('tblcustomfieldsvalues')->where('fieldto', 'leads')->where('fieldid', $cf_akiromena_rantevou['id'])->where('relid', $lead_id)->get()->row_array();
				
				if($cfv_exists_arithmos == null){
					$data = [
						'relid'   => (int) $lead_id,
						'fieldid' => (int) $cf_arithmos_epikoinonias['id'],
						'fieldto' => 'leads',
						'value'   => '1',
					];
					$this->db->insert('tblcustomfieldsvalues', $data);
				}else{
					$cfv_exists_arithmos['value'] = (int) $cfv_exists_arithmos['value'] + 1;
					$this->db->where('id', $cfv_exists_arithmos['id']);
					$this->db->update('tblcustomfieldsvalues', ['value' => $cfv_exists_arithmos['value']]);
				}
				if($cfv_exists_voip == null){
					$data = [
						'relid'   => (int) $lead_id,
						'fieldid' => (int) $cf_voip['id'],
						'fieldto' => 'leads',
						'value'   => '12',
					];
					$this->db->insert('tblcustomfieldsvalues', $data);
				}else{
					if($katastasi == "no_call"){
						if($cfv_exists_voip['value'] >= "19"){
							$cfv_exists_voip['value'] = '12';
							$this->db->where('id', $cfv_exists_voip['id']);
							$this->db->update('tblcustomfieldsvalues', ['value' => $cfv_exists_voip['value']]);
						}else{
							$cfv_exists_voip['value'] = (int) $cfv_exists_voip['value'] + 1;
							$this->db->where('id', $cfv_exists_voip['id']);
							$this->db->update('tblcustomfieldsvalues', ['value' => $cfv_exists_voip['value']]);
						}
					}
				}
				if($cfv_exists_no_answer == null){
					$data = [
						'relid'   => (int) $lead_id,
						'fieldid' => (int) $cf_no_answer['id'],
						'fieldto' => 'leads',
						'value'   => '0',
					];
					$this->db->insert('tblcustomfieldsvalues', $data);
					$cfv_exists_no_answer = $this->db->select('*')->from('tblcustomfieldsvalues')->where('fieldto', 'leads')->where('fieldid', $cf_no_answer['id'])->where('relid', $lead_id)->get()->row_array();
				}else if($katastasi != "no_call"){
					$cfv_exists_no_answer['value'] = '0';
					$this->db->where('id', $cfv_exists_no_answer['id']);
					$this->db->update('tblcustomfieldsvalues', ['value' => $cfv_exists_no_answer['value']]);
				}
				
				$row_lead = $this->db->select('*')->from('tblleads')->where('id', $lead_id)->get()->row_array();
				
				//$this->db->where('id', $lead_id);
				//$this->db->update('tblleads', ['assigned' => get_staff_user_id()]);
				
				// KATASTASEIS
				if($katastasi == "no_call"){
					$cfv_exists_no_answer['value'] = (int) $cfv_exists_no_answer['value'] + 1;
					$this->db->where('id', $cfv_exists_no_answer['id']);
					$this->db->update('tblcustomfieldsvalues', ['value' => $cfv_exists_no_answer['value']]);
					if($cfv_exists_no_answer['value'] >= '7'){
						$wanted_status = "Last Call";
						$wanted_status = $this->db->select('*')->from('tblleads_status')->where('name', $wanted_status)->get()->row_array();
						
						//$row_lead['status'] = $wanted_status['id'];
						$this->db->where('id', $lead_id);
						$this->db->update('tblleads', ['status' => $wanted_status['id']]);
					}else{
						$wanted_status = "Επανάληψη Επικοινωνίας";
						$wanted_status = $this->db->select('*')->from('tblleads_status')->where('name', $wanted_status)->get()->row_array();
						
						//$row_lead['status'] = $wanted_status['id'];
						$this->db->where('id', $lead_id);
						$this->db->update('tblleads', ['status' => $wanted_status['id']]);
					}
					
					$follow_up = 0;
					if($cfv_exists_no_answer['value'] == '1'){
						$follow_up = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' +1 hour'));
					}else if($cfv_exists_no_answer['value'] == '2'){
						$follow_up = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' +3 hours'));
					}else if($cfv_exists_no_answer['value'] == '3'){
						$follow_up = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' +1 day'));
					}else if($cfv_exists_no_answer['value'] == '4'){
						$follow_up = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' +2 days'));
					}else if($cfv_exists_no_answer['value'] == '5'){
						$follow_up = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' +3 days'));
					}else if($cfv_exists_no_answer['value'] == '6'){
						$follow_up = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' +4 days'));
					}else if($cfv_exists_no_answer['value'] == '7'){
						$follow_up = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' +5 days'));
					}else if($cfv_exists_no_answer['value'] == '8'){
						$follow_up = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' +6 day'));
					}
					if ($follow_up) {
						$dt = new DateTime($follow_up);
						// --- Adjust for weekends ---
						while (in_array($dt->format('N'), [6, 7])) { // 6 = Saturday, 7 = Sunday
							$dt->modify('+1 day')->setTime(9, 0); // push to Monday 09:00
						}
						// --- Adjust for after-hours ---
						$hour = (int)$dt->format('H');
						if ($hour < 9) {
							// too early → set to 09:00
							$dt->setTime(9, 0);
						} elseif ($hour >= 16) {
							// too late → move to next weekday 09:00
							$dt->modify('+1 day')->setTime(9, 0);
							while (in_array($dt->format('N'), [6, 7])) {
								$dt->modify('+1 day')->setTime(9, 0);
							}
						}
						$follow_up = $dt->format('Y-m-d H:i:s');
					}
					$data = [ 'rel_id' => $lead_id, 'remark' => "Δεν απάντησε.", 'rel_type' => 1, 'date' => date('Y-m-d H:i:s'), 'lm_follow_up_date' => $follow_up, 'is_notified' => 0 ];
					$this->db->insert('tbllead_manager_meeting_remark', $data);
					
					$data = [ 'type' => 'remark', 'is_audio_call_recorded' => 0, 'lead_id' => $lead_id, 'date' => date('Y-m-d H:i:s'), 'description' => "Δεν απάντησε.", 'additional_data' => null, 'staff_id' => get_staff_user_id(), 'direction' => 'outgoing', 'call_duration' => null, 'is_client' => 0 ];
					$this->db->insert('tbllead_manager_activity_log', $data);
					
					$this->db->where('id', $lead_id);
					$this->db->update('tblleads', ['lm_follow_up' => 1]);
					
					if($row_lead['status'] != $wanted_status['id']){
						$old_status_row = $this->db->select('name')->from('tblleads_status')->where('id', $row_lead['status'])->get()->row_array();
						$new_status_row = $this->db->select('name')->from('tblleads_status')->where('id', $wanted_status['id'])->get()->row_array();
						$activity_log_description = get_staff_full_name()." updated lead status from ".$old_status_row['name']." to ".$new_status_row['name'];
						$activity_log_data = [
							"leadid" 			=> $lead_id,
							"description" 		=> $activity_log_description,
							"additional_data" 	=> null,
							"date" 				=> date('Y-m-d H:i:s'),
							"staffid" 			=> get_staff_user_id(),
							"full_name" 		=> get_staff_full_name(),
							"custom_activity" 	=> 1
						];
						$this->db->insert('tbllead_activity_log', $activity_log_data);
					}
					
				}else if($katastasi == "closed"){
					$wanted_status = "Επανάληψη Επικοινωνίας";
					$wanted_status = $this->db->select('*')->from('tblleads_status')->where('name', $wanted_status)->get()->row_array();
					
					//$row_lead['status'] = $wanted_status['id'];
					$this->db->where('id', $lead_id);
					$this->db->update('tblleads', ['status' => $wanted_status['id']]);
					
					$follow_up = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' +15 minutes'));
					if ($follow_up) {
						$dt = new DateTime($follow_up);
						// --- Adjust for weekends ---
						while (in_array($dt->format('N'), [6, 7])) { // 6 = Saturday, 7 = Sunday
							$dt->modify('+1 day')->setTime(9, 0); // push to Monday 09:00
						}
						// --- Adjust for after-hours ---
						$hour = (int)$dt->format('H');
						if ($hour < 9) {
							// too early → set to 09:00
							$dt->setTime(9, 0);
						} elseif ($hour >= 16) {
							// too late → move to next weekday 09:00
							$dt->modify('+1 day')->setTime(9, 0);
							while (in_array($dt->format('N'), [6, 7])) {
								$dt->modify('+1 day')->setTime(9, 0);
							}
						}
						$follow_up = $dt->format('Y-m-d H:i:s');
					}
					
					$data = [ 'rel_id' => $lead_id, 'remark' => "Το έκλεισε/Κατειλημμένο.", 'rel_type' => 1, 'date' => date('Y-m-d H:i:s'), 'lm_follow_up_date' => $follow_up, 'is_notified' => 0 ];
					$this->db->insert('tbllead_manager_meeting_remark', $data);
					
					$data = [ 'type' => 'remark', 'is_audio_call_recorded' => 0, 'lead_id' => $lead_id, 'date' => date('Y-m-d H:i:s'), 'description' => "Το έκλεισε/Κατειλημμένο.", 'additional_data' => null, 'staff_id' => get_staff_user_id(), 'direction' => 'outgoing', 'call_duration' => null, 'is_client' => 0 ];
					$this->db->insert('tbllead_manager_activity_log', $data);
					
					//$this->db->where('id', $lead_id);
					//$this->db->update('tblleads', ['assigned' => get_staff_user_id()]);
					
					$this->db->where('id', $lead_id);
					$this->db->update('tblleads', ['lm_follow_up' => 1]);
					
					if($row_lead['status'] != $wanted_status['id']){
						$old_status_row = $this->db->select('name')->from('tblleads_status')->where('id', $row_lead['status'])->get()->row_array();
						$new_status_row = $this->db->select('name')->from('tblleads_status')->where('id', $wanted_status['id'])->get()->row_array();
						$activity_log_description = get_staff_full_name()." updated lead status from ".$old_status_row['name']." to ".$new_status_row['name'];
						$activity_log_data = [
							"leadid" 			=> $lead_id,
							"description" 		=> $activity_log_description,
							"additional_data" 	=> null,
							"date" 				=> date('Y-m-d H:i:s'),
							"staffid" 			=> get_staff_user_id(),
							"full_name" 		=> get_staff_full_name(),
							"custom_activity" 	=> 1
						];
						$this->db->insert('tbllead_activity_log', $activity_log_data);
					}
					
				}else if( ($katastasi == "recall") || ($katastasi == "interest") ){
					$remark_staff_id =  $this->input->post('lm_remark_staff');
					$remark_staff_row = $this->db->select('*')->from('tblstaff')->where('staffid', $remark_staff_id)->get()->row_array();
					/*if(get_staff_user_id() != $remark_staff_row['staffid']){
						$wanted_status = "Ενδιαφέρεται";
						$wanted_status = $this->db->select('*')->from('tblleads_status')->where('name', $wanted_status)->get()->row_array();
					}else{
						$wanted_status = "Επανάληψη Επικοινωνίας";
						$wanted_status = $this->db->select('*')->from('tblleads_status')->where('name', $wanted_status)->get()->row_array();
					}*/
					if($katastasi == "interest"){
						$wanted_status = "Ενδιαφέρεται";
						$wanted_status = $this->db->select('*')->from('tblleads_status')->where('name', $wanted_status)->get()->row_array();
					}else{
						$wanted_status = "Επανάληψη Επικοινωνίας";
						$wanted_status = $this->db->select('*')->from('tblleads_status')->where('name', $wanted_status)->get()->row_array();
					}
					
					//$row_lead['status'] = $wanted_status['id'];
					$this->db->where('id', $lead_id);
					$this->db->update('tblleads', ['status' => $wanted_status['id']]);
					
					$follow_up = $this->input->post('lm_datetime');
					$dt = DateTime::createFromFormat('Y-m-d H:i:s', $follow_up)?: DateTime::createFromFormat('Y-m-d H:i', $follow_up);
					if (!$dt) { $dt = DateTime::createFromFormat('d/m/Y H:i', $follow_up); }
					if (!$dt) { $dt = date_create($follow_up); }
					$follow_up = $dt ? $dt->format('Y-m-d H:i') : null;
					
					$remark_staff_id =  $this->input->post('lm_remark_staff');
					$remark_staff_row = $this->db->select('*')->from('tblstaff')->where('staffid', $remark_staff_id)->get()->row_array();
					$data = [ 'rel_id' => $lead_id, 'remark' => "Επανάληψη επικοινωνίας -> ".$remark_staff_row['firstname']." ".$remark_staff_row['lastname'], 'rel_type' => 1, 'date' => date('Y-m-d H:i:s'), 'lm_follow_up_date' => $follow_up, 'is_notified' => 0 ];
					$this->db->insert('tbllead_manager_meeting_remark', $data);
					
					$data = [ 'type' => 'remark', 'is_audio_call_recorded' => 0, 'lead_id' => $lead_id, 'date' => date('Y-m-d H:i:s'), 'description' => "Επανάληψη επικοινωνίας -> ".$remark_staff_row['firstname']." ".$remark_staff_row['lastname'], 'additional_data' => null, 'staff_id' => get_staff_user_id(), 'direction' => 'outgoing', 'call_duration' => null, 'is_client' => 0 ];
					$this->db->insert('tbllead_manager_activity_log', $data);
					
					$this->db->where('id', $lead_id);
					$this->db->update('tblleads', ['assigned' => get_staff_user_id()]);
					
					$this->db->where('id', $lead_id);
					$this->db->update('tblleads', ['lm_follow_up' => 1]);
					
					$old_status_row = $this->db->select('name')->from('tblleads_status')->where('id', $row_lead['status'])->get()->row_array();
					$new_status_row = $this->db->select('name')->from('tblleads_status')->where('id', $wanted_status['id'])->get()->row_array();
					$activity_log_description = get_staff_full_name()." updated lead status from ".$old_status_row['name']." to ".$new_status_row['name'];
					$activity_log_data = [
						"leadid" 			=> $lead_id,
						"description" 		=> $activity_log_description,
						"additional_data" 	=> null,
						"date" 				=> date('Y-m-d H:i:s'),
						"staffid" 			=> get_staff_user_id(),
						"full_name" 		=> get_staff_full_name(),
						"custom_activity" 	=> 1
					];
					if($row_lead['status'] != $wanted_status['id']){ 
						$this->db->insert('tbllead_activity_log', $activity_log_data); 
					}
					$new_admin_url = admin_url('profile/' . get_staff_user_id());
					$activity_log_data['description'] = "<a href='".$new_admin_url."' target='_blank'>".get_staff_full_name()."</a> was assigned as the administrator of this lead";
					if(get_staff_user_id() != $row_lead['assigned']){
						$this->db->insert('tbllead_activity_log', $activity_log_data);
					}
					
				}else if($katastasi == "not_interested"){
					$wanted_status = "Δεν Ενδιαφέρεται";
					$wanted_status = $this->db->select('*')->from('tblleads_status')->where('name', $wanted_status)->get()->row_array();
					
					//$row_lead['status'] = $wanted_status['id'];
					$this->db->where('id', $lead_id);
					$this->db->update('tblleads', ['status' => $wanted_status['id']]);
					
					$data = [ 'rel_id' => $lead_id, 'remark' => "Δεν ενδιαφέρεται.", 'rel_type' => 1, 'date' => date('Y-m-d H:i:s'), 'lm_follow_up_date' => null, 'is_notified' => 0 ];
					$this->db->insert('tbllead_manager_meeting_remark', $data);
					
					$data = [ 'type' => 'remark', 'is_audio_call_recorded' => 0, 'lead_id' => $lead_id, 'date' => date('Y-m-d H:i:s'), 'description' => "Δεν ενδιαφέρεται.", 'additional_data' => null, 'staff_id' => get_staff_user_id(), 'direction' => 'outgoing', 'call_duration' => null, 'is_client' => 0 ];
					$this->db->insert('tbllead_manager_activity_log', $data);
					
					if($row_lead['status'] != $wanted_status['id']){
						$old_status_row = $this->db->select('name')->from('tblleads_status')->where('id', $row_lead['status'])->get()->row_array();
						$new_status_row = $this->db->select('name')->from('tblleads_status')->where('id', $wanted_status['id'])->get()->row_array();
						$activity_log_description = get_staff_full_name()." updated lead status from ".$old_status_row['name']." to ".$new_status_row['name'];
						$activity_log_data = [
							"leadid" 			=> $lead_id,
							"description" 		=> $activity_log_description,
							"additional_data" 	=> null,
							"date" 				=> date('Y-m-d H:i:s'),
							"staffid" 			=> get_staff_user_id(),
							"full_name" 		=> get_staff_full_name(),
							"custom_activity" 	=> 1
						];
						$this->db->insert('tbllead_activity_log', $activity_log_data);
					}
					
				}else if($katastasi == "ineligible"){
					$wanted_status = "Μη Επιλέξιμος";
					$wanted_status = $this->db->select('*')->from('tblleads_status')->where('name', $wanted_status)->get()->row_array();
					
					//$row_lead['status'] = $wanted_status['id'];
					$this->db->where('id', $lead_id);
					$this->db->update('tblleads', ['status' => $wanted_status['id']]);
					
					$data = [ 'rel_id' => $lead_id, 'remark' => "Μη επιλέξιμος.", 'rel_type' => 1, 'date' => date('Y-m-d H:i:s'), 'lm_follow_up_date' => null, 'is_notified' => 0 ];
					$this->db->insert('tbllead_manager_meeting_remark', $data);
					
					$data = [ 'type' => 'remark', 'is_audio_call_recorded' => 0, 'lead_id' => $lead_id, 'date' => date('Y-m-d H:i:s'), 'description' => "Μη επιλέξιμος.", 'additional_data' => null, 'staff_id' => get_staff_user_id(), 'direction' => 'outgoing', 'call_duration' => null, 'is_client' => 0 ];
					$this->db->insert('tbllead_manager_activity_log', $data);
					
					if($row_lead['status'] != $wanted_status['id']){
						$old_status_row = $this->db->select('name')->from('tblleads_status')->where('id', $row_lead['status'])->get()->row_array();
						$new_status_row = $this->db->select('name')->from('tblleads_status')->where('id', $wanted_status['id'])->get()->row_array();
						$activity_log_description = get_staff_full_name()." updated lead status from ".$old_status_row['name']." to ".$new_status_row['name'];
						$activity_log_data = [
							"leadid" 			=> $lead_id,
							"description" 		=> $activity_log_description,
							"additional_data" 	=> null,
							"date" 				=> date('Y-m-d H:i:s'),
							"staffid" 			=> get_staff_user_id(),
							"full_name" 		=> get_staff_full_name(),
							"custom_activity" 	=> 1
						];
						$this->db->insert('tbllead_activity_log', $activity_log_data);
					}
					
				}else if($katastasi == "appointment"){
					$wanted_status = "Ενδιαφέρεται";
					$wanted_status = $this->db->select('*')->from('tblleads_status')->where('name', $wanted_status)->get()->row_array();
					
					//$row_lead['status'] = $wanted_status['id'];
					$this->db->where('id', $lead_id);
					$this->db->update('tblleads', ['status' => $wanted_status['id']]);
					
					$this->db->where('id', $lead_id);
					$this->db->update('tblleads', ['assigned' => get_staff_user_id()]);
					
					$follow_up = $this->input->post('lm_datetime');
					$dt = DateTime::createFromFormat('Y-m-d H:i:s', $follow_up)?: DateTime::createFromFormat('Y-m-d H:i', $follow_up);
					if (!$dt) { $dt = DateTime::createFromFormat('d/m/Y H:i', $follow_up); }
					if (!$dt) { $dt = date_create($follow_up); }
					$follow_up = $dt ? $dt->format('Y-m-d H:i') : null;
					
					$data = [ 'rel_id' => $lead_id, 'remark' => "Διεκπεραίωση Ραντεβού.", 'rel_type' => 1, 'date' => date('Y-m-d H:i:s'), 'lm_follow_up_date' => $follow_up, 'is_notified' => 0 ];
					$this->db->insert('tbllead_manager_meeting_remark', $data);
					
					$data = [ 'type' => 'remark', 'is_audio_call_recorded' => 0, 'lead_id' => $lead_id, 'date' => date('Y-m-d H:i:s'), 'description' => "Διεκπεραίωση Ραντεβού.", 'additional_data' => null, 'staff_id' => get_staff_user_id(), 'direction' => 'outgoing', 'call_duration' => null, 'is_client' => 0 ];
					$this->db->insert('tbllead_manager_activity_log', $data);
					
					$this->db->where('id', $lead_id);
					$this->db->update('tblleads', ['lm_follow_up' => 1]);
					
					$map_address = [
						"telephone" => "Τηλεφωνικά",
						"conference" => "Τηλεδιάσκεψη",
						"deligianni" => "Δεληγιαννη 8, Τρίπολη 22100",
						"apostolopoulou" => "Αποστολοπούλου 8, Τρίπολη 22100",
						"arapaki" => "Αραπάκη 6, Καλλιθέα 17676",
						"karaoli" => "Καραολή και Δημητρίου 119, Εύοσμος 56224"
					];
					$selected_address = $this->input->post('lm_location');
					$appointly_address = isset($map_address[$selected_address]) ? $map_address[$selected_address] : null;
					
					if($this->input->post('lm_type') == '1'){
						$appointly_subject = "Αξιολόγηση Σύμβασης Αναδιάρθρωσης";
						$appointly_description = "Στο προσεχές μας ραντεβού θα αξιολογήσουμε μαζί τη Σύμβαση Αναδιάρθρωσης που έχει εκδοθεί μέσω του εξωδικαστικού μηχανισμού. <br>Θα εξετάσουμε αναλυτικά τους όρους της σύμβασης, τις προτεινόμενες ρυθμίσεις και το πλάνο αποπληρωμής. <br>Στόχος μας είναι να διασφαλίσουμε ότι η συμφωνία ανταποκρίνεται στις ανάγκες σας και ότι όλες οι πτυχές της είναι ξεκάθαρες και προς όφελός σας.";
					}else if($this->input->post('lm_type') == '2'){
						$appointly_subject = "Παρουσίαση Ανταποδοτικού Συστήματος Affiliate – MECE";
						$appointly_description = "Η συνάντηση αυτή έχει σκοπό να σας παρουσιάσει το νέο Ανταποδοτικό Σύστημα Affiliate της εταιρείας MECE, το οποίο προσφέρει τη δυνατότητα ενίσχυσης των εσόδων μέσω στρατηγικών συνεργασιών. <br><br>Η παρουσίαση θα πραγματοποιηθεί από τον υπεύθυνο του τμήματος ρυθμίσεων, ο οποίος θα σας ενημερώσει αναλυτικά και θα απαντήσει σε τυχόν ερωτήσεις. <br><br>Παρέχουμε λύσεις με επαγγελματισμό και συνέπεια, με γνώμονα την αξιοπιστία και την ουσιαστική υποστήριξη των συνεργασιών μας";
					}else if($this->input->post('lm_type') == '3'){
						$appointly_subject = "Πρόσκληση σε Συνέντευξη Εργασίας";
						$appointly_description = "Στο πλαίσιο της διαδικασίας επιλογής, θα θέλαμε να σας προσκαλέσουμε σε συνέντευξη, με σκοπό να γνωριστούμε καλύτερα και να αξιολογήσουμε την επαγγελματική σας πορεία, τις δεξιότητες και τα προσόντα σας σε σχέση με τις απαιτήσεις της θέσης. <br><br>Παράλληλα, θα έχετε την ευκαιρία να ενημερωθείτε για την εταιρεία μας, τις αξίες που μας διέπουν, την εργασιακή μας κουλτούρα και τους μελλοντικούς μας στόχους.";
					}else if($this->input->post('lm_type') == '4'){
						$appointly_subject = "Συνεδρία για Ρύθμιση και Εξυγίανση Οφειλών";
						$appointly_description = "Θα θέλαμε να σας ενημερώσουμε ότι στο προσεχές μας ραντεβού θα πραγματοποιήσουμε συνεδρία με στόχο τη ρύθμιση και εξυγίανση των οφειλών σας. <br><br>Θα εξετάσουμε αναλυτικά την οικονομική σας κατάσταση και τις διαθέσιμες επιλογές για βιώσιμες λύσεις διαχείρισης των υποχρεώσεών σας. <br><br>Για την ομαλή διεξαγωγή της συνάντησης, παρακαλούμε να έχετε διαθέσιμα όλα τα απαραίτητα οικονομικά στοιχεία.";
					}else if($this->input->post('lm_type') == '5'){
						$appointly_subject = "Συνεδρία για Τραπεζική Διαμεσολάβηση";
						$appointly_description = "Θα θέλαμε να σας ενημερώσουμε ότι στο προσεχές μας ραντεβού θα πραγματοποιήσουμε συνεδρία για τραπεζική διαμεσολάβηση. <br><br>Η συνάντηση αυτή έχει ως σκοπό να συζητήσουμε λεπτομερώς τις επιλογές σας, καθώς και να σας παρέχουμε υποστήριξη στην επίλυση των τραπεζικών ζητημάτων που σας απασχολούν.";
					}else if($this->input->post('lm_type') == '6'){
						$appointly_subject = "Ενημέρωση Αίτησης";
						$appointly_description = "Στο προσεχές μας ραντεβού θα συζητήσουμε την πορεία της αίτησής σας για τη ρύθμιση οφειλών. Θα σας ενημερώσουμε για την τρέχουσα κατάσταση, τα επόμενα βήματα στη διαδικασία και τυχόν απαιτούμενες ενέργειες από την πλευρά σας. <br><br>Στόχος μας είναι να βεβαιωθούμε ότι όλα προχωρούν ομαλά και να επιλύσουμε τυχόν απορίες που μπορεί να έχετε.";
					}else if($this->input->post('lm_type') == '7'){
						$appointly_subject = "Ραντεβού για Ανάλυση & Στρατηγική Διαχείρισης Ακινήτου";
						$appointly_description = "Θα θέλαμε να σας ενημερώσουμε ότι στο προσεχές μας ραντεβού θα πραγματοποιήσουμε συνεδρία με στόχο για τη διαχείριση του/των ακινήτου/ων σας.<br><br>Θα συζητήσουμε λύσεις για μίσθωση, αξιοποίηση ή πλήρη ανάληψη διαχείρισης.<br>Αν υπάρχουν διαθέσιμα έγγραφα (τίτλοι, μισθωτήρια, έξοδα), καλό είναι να τα έχετε μαζί σας. Για οποιαδήποτε αλλαγή, επικοινωνήστε μαζί μας.";
					}else{
						$appointly_subject = " ";
						$appointly_description = " ";
					}
					//$appointly_subject = "Συνεδρία για Ρύθμιση και Εξυγίανση Οφειλών";
					//$appointly_description = "Θα θέλαμε να σας ενημερώσουμε ότι στο προσεχές μας ραντεβού θα πραγματοποιήσουμε μια συνεδρία με σκοπό να συζητήσουμε τη ρύθμιση και την εξυγίανση των οφειλών σας. Στη διάρκεια της συνάντησής μας, θα αναλύσουμε λεπτομερώς την τρέχουσα οικονομική σας κατάσταση και θα εξετάσουμε τις διαθέσιμες επιλογές για βιώσιμες λύσεις, που θα βοηθήσουν στη βελτίωση της διαχείρισης των υποχρεώσεών σας. Η εταιρεία μας διαθέτει εκτεταμένο δίκτυο συνεργατών, με περισσότερους από 150 συνεργάτες σε όλη την Ελλάδα, ενώ έχουμε φυσική παρουσία μέσω των γραφείων μας σε Αθήνα, Θεσσαλονίκη, Καλαμάτα και Τρίπολη. Μέσα από την εμπειρία και τις λύσεις που προσφέρουμε, επιδιώκουμε να ανταποκριθούμε στις ανάγκες σας με επαγγελματισμό και συνέπεια. Για την καλύτερη προετοιμασία και την ομαλή διεξαγωγή της συνάντησής μας, παρακαλούμε να προσκομίσετε όλα τα απαραίτητα οικονομικά στοιχεία.";
					
					
					$appointly_date = $follow_up;			//
					$parts = explode(' ', trim((string)$appointly_date), 2);		//
					$date_only = isset($parts[0]) ? $parts[0] : '';					//
					$time_only = isset($parts[1]) ? $parts[1] : '';					//
					$appointly_attendees = $this->input->post('lm_attendees');
					$appointly_name = $row_lead['name'];
					$appointly_email = $row_lead['email'];
					$appointly_phone = $row_lead['phonenumber'];
					$appointly_data = [
						"subject" 				=> $appointly_subject,
						"description" 			=> $appointly_description,
						"email" 				=> $appointly_email,
						"name" 					=> $appointly_name,
						"phone" 				=> $appointly_phone,
						"address" 				=> $appointly_address,
						"contact_id" 			=> $lead_id,
						"by_sms" 				=> 0,
						"by_email" 				=> 0,
						"hash" 					=> bin2hex(random_bytes(16)),
						"date" 					=> $date_only,
						"start_hour" 			=> $time_only,
						"approved" 				=> 1,
						"created_by" 			=> get_staff_user_id(),
						"reminder_before" 		=> 60,
						"reminder_before_type" 	=> "minutes",
						"source" 				=> "lead_related",
						"repeat_every" 			=> 0,
						"custom_recurring" 		=> 0
					];
					$this->db->insert('tblappointly_appointments', $appointly_data);
					$appointment_id = $this->db->insert_id();
					foreach($appointly_attendees as $staffid){
						$staffid = (int) $staffid;
						$this->db->insert('tblappointly_attendees', [ "staff_id" => $staffid, "appointment_id" => $appointment_id ]);
					}
					
					$old_status_row = $this->db->select('name')->from('tblleads_status')->where('id', $row_lead['status'])->get()->row_array();
					$new_status_row = $this->db->select('name')->from('tblleads_status')->where('id', $wanted_status['id'])->get()->row_array();
					$activity_log_description = get_staff_full_name()." updated lead status from ".$old_status_row['name']." to ".$new_status_row['name'];
					$activity_log_data = [
						"leadid" 			=> $lead_id,
						"description" 		=> $activity_log_description,
						"additional_data" 	=> null,
						"date" 				=> date('Y-m-d H:i:s'),
						"staffid" 			=> get_staff_user_id(),
						"full_name" 		=> get_staff_full_name(),
						"custom_activity" 	=> 1
					];
					if($row_lead['status'] != $wanted_status['id']){
						$this->db->insert('tbllead_activity_log', $activity_log_data);
					}
					$new_admin_url = admin_url('profile/' . get_staff_user_id());
					$activity_log_data['description'] = "<a href='".$new_admin_url."' target='_blank'>".get_staff_full_name()."</a> was assigned as the administrator of this lead";
					if(get_staff_user_id() != $row_lead['assigned']){
						$this->db->insert('tbllead_activity_log', $activity_log_data);
					}
					$new_apointment_url = admin_url('appointly/appointments/view?appointment_id=' . $appointment_id);
					$activity_log_data['description'] = "A new appointment was scheduled. Check it <a href='".$new_apointment_url."' target='_blank'>here</a>";
					$this->db->insert('tbllead_activity_log', $activity_log_data);
					
				}else if($katastasi == "no_call_on_appointment"){
					if($cfv_exists_akiromena_rantevou == null){
						$data = [
							'relid'   => (int) $lead_id,
							'fieldid' => (int) $cf_akiromena_rantevou['id'],
							'fieldto' => 'leads',
							'value'   => '1',
						];
						$this->db->insert('tblcustomfieldsvalues', $data);
					}else{
						$cfv_exists_akiromena_rantevou['value'] = (int) $cfv_exists_akiromena_rantevou['value'] + 1;
						$this->db->where('id', $cfv_exists_akiromena_rantevou['id']);
						$this->db->update('tblcustomfieldsvalues', ['value' => $cfv_exists_akiromena_rantevou['value']]);
					}
					//$wanted_status = "Επαναπρογραμματισμός Ραντεβού"; Δεν απάντησε στο Ραντεβού - Να κλειστεί εκ νέου Ραντεβού.
					$wanted_status = "Επανάληψη Επικοινωνίας";
					$wanted_status = $this->db->select('*')->from('tblleads_status')->where('name', $wanted_status)->get()->row_array();
					
					//$row_lead['status'] = $wanted_status['id'];
					$this->db->where('id', $lead_id);
					$this->db->update('tblleads', ['status' => $wanted_status['id']]);
					
					//$follow_up = $this->input->post('lm_datetime');
					//$data = [ 'rel_id' => $lead_id, 'remark' => "Δεν απάντησε στο Ραντεβού - Να κλειστεί εκ νέου Ραντεβού.", 'rel_type' => 1, 'date' => date('Y-m-d H:i:s'), 'lm_follow_up_date' => $follow_up, 'is_notified' => 0 ];
					$follow_up = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' +1 day'));
					if ($follow_up) {
						$dt = new DateTime($follow_up);
						// --- Adjust for weekends ---
						while (in_array($dt->format('N'), [6, 7])) { // 6 = Saturday, 7 = Sunday
							$dt->modify('+1 day')->setTime(9, 0); // push to Monday 09:00
						}
						// --- Adjust for after-hours ---
						$hour = (int)$dt->format('H');
						if ($hour < 9) {
							// too early → set to 09:00
							$dt->setTime(9, 0);
						} elseif ($hour >= 16) {
							// too late → move to next weekday 09:00
							$dt->modify('+1 day')->setTime(9, 0);
							while (in_array($dt->format('N'), [6, 7])) {
								$dt->modify('+1 day')->setTime(9, 0);
							}
						}
						$follow_up = $dt->format('Y-m-d H:i:s');
					}
					$data = [ 'rel_id' => $lead_id, 'remark' => "Δεν απάντησε στο Ραντεβού - Επανάληψη Επικοινωνίας.", 'rel_type' => 1, 'date' => date('Y-m-d H:i:s'), 'lm_follow_up_date' => $follow_up, 'is_notified' => 0 ];
					$this->db->insert('tbllead_manager_meeting_remark', $data);
					
					$data = [ 'type' => 'remark', 'is_audio_call_recorded' => 0, 'lead_id' => $lead_id, 'date' => date('Y-m-d H:i:s'), 'description' => "Δεν απάντησε στο Ραντεβού - Επανάληψη Επικοινωνίας.", 'additional_data' => null, 'staff_id' => get_staff_user_id(), 'direction' => 'outgoing', 'call_duration' => null, 'is_client' => 0 ];
					$this->db->insert('tbllead_manager_activity_log', $data);
					
					$this->db->where('id', $lead_id);
					$this->db->update('tblleads', ['lm_follow_up' => 1]);
					
					$appointments = $this->db->select('*')->from('tblappointly_appointments')->where('contact_id', $lead_id)->get()->result_array();
					$best = null;
					if (!empty($appointments)) {
						$now = new DateTime('now');
						$best = null;
						$bestDiff = PHP_INT_MAX;
						foreach ($appointments as $r) {
							if (empty($r['date'])) {
								continue;
							}
							
							if($r['cancelled'] == 1){
								continue;
							}

							$raw_date_hour = $r['date']." ".$r['start_hour'];
							$dt = DateTime::createFromFormat('Y-m-d H:i', $raw_date_hour);

							$diff = abs($now->getTimestamp() - $dt->getTimestamp());

							if ($diff < $bestDiff) {
								$bestDiff = $diff;
								$best = $r;
							}
						}
						if($best != null){
							$this->db->where('id', $best['id']);
							$this->db->update('tblappointly_appointments', ['cancelled' => 1]);
						}
					}
					
					$old_status_row = $this->db->select('name')->from('tblleads_status')->where('id', $row_lead['status'])->get()->row_array();
					$new_status_row = $this->db->select('name')->from('tblleads_status')->where('id', $wanted_status['id'])->get()->row_array();
					$activity_log_description = get_staff_full_name()." updated lead status from ".$old_status_row['name']." to ".$new_status_row['name'];
					$activity_log_data = [
						"leadid" 			=> $lead_id,
						"description" 		=> $activity_log_description,
						"additional_data" 	=> null,
						"date" 				=> date('Y-m-d H:i:s'),
						"staffid" 			=> get_staff_user_id(),
						"full_name" 		=> get_staff_full_name(),
						"custom_activity" 	=> 1
					];
					if($row_lead['status'] != $wanted_status['id']){
						$this->db->insert('tbllead_activity_log', $activity_log_data);
					}
					if($best != null){
						$canceled_apointment_url = admin_url('appointly/appointments/view?appointment_id=' . $best['id']);
						$activity_log_data['description'] = "Δεν απάντησε στο <a href='".$canceled_apointment_url."' target='_blank'>Ραντεβού</a>";
						$this->db->insert('tbllead_activity_log', $activity_log_data);
					}
					
				}else if($katastasi == "appointment_anew"){		// DEPRECATED AHH FEATURE	***********************************
					/*$wanted_status = "Επαναπρογραμματισμός Ραντεβού";
					$wanted_status = $this->db->select('*')->from('tblleads_status')->where('name', $wanted_status)->get()->row_array();
					
					$row_lead['status'] = $wanted_status['id'];
					$this->db->where('id', $lead_id);
					$this->db->update('tblleads', ['status' => $wanted_status['id']]);
					
					$follow_up = $this->input->post('lm_datetime');
					$dt = DateTime::createFromFormat('Y-m-d H:i:s', $follow_up)?: DateTime::createFromFormat('Y-m-d H:i', $follow_up);
					if (!$dt) { $dt = DateTime::createFromFormat('d/m/Y H:i', $follow_up); }
					if (!$dt) { $dt = date_create($follow_up); }
					$follow_up = $dt ? $dt->format('Y-m-d H:i') : null;
					
					$data = [ 'rel_id' => $lead_id, 'remark' => "Εκ νέου Ραντεβού", 'rel_type' => 1, 'date' => date('Y-m-d H:i:s'), 'lm_follow_up_date' => $follow_up, 'is_notified' => 0 ];
					$this->db->insert('tbllead_manager_meeting_remark', $data);
					
					$data = [ 'type' => 'remark', 'is_audio_call_recorded' => 0, 'lead_id' => $lead_id, 'date' => date('Y-m-d H:i:s'), 'description' => "Εκ νέου Ραντεβού", 'additional_data' => null, 'staff_id' => get_staff_user_id(), 'direction' => 'outgoing', 'call_duration' => null, 'is_client' => 0 ];
					$this->db->insert('tbllead_manager_activity_log', $data);
					
					$this->db->where('id', $lead_id);
					$this->db->update('tblleads', ['lm_follow_up' => 1]);*/
					
				}else if($katastasi == "send_proposal"){
					$pr_content = '';
					if($this->input->post('pr_type') == 'fusiko'){
						$subject_input = "Προσφορά Οικονομικού Ελέγχου Οφειλέτη - Φυσικού Προσώπου";
						$new_item_description = "Οικονομικός Έλεγχος Φυσικού Προσώπου";
						$pr_content = $this->db->select('*')->from('tbltemplates')->where('name', 'Πρότυπο Οικονομικού Ελέγχου')->where('type', 'proposals')->get()->row_array();
						$pr_content = $pr_content['content'];
					}else if($this->input->post('pr_type') == 'teiresia'){
						$subject_input = "Έλεγχος οφειλέτη στα μητρώα του Τειρεσία";
						$new_item_description = "Έλεγχος οφειλέτη στα μητρώα του Τειρεσία";
						$pr_content = $this->db->select('*')->from('tbltemplates')->where('name', 'Πρότυπο για Τειρεσία')->where('type', 'proposals')->get()->row_array();
						$pr_content = $pr_content['content'];
					}else{
						$subject_input = "Προσφορά Οικονομικού Ελέγχου Οφειλέτη - Νομικού Προσώπου";
						$new_item_description = "Οικονομικός Έλεγχος Νομικού Προσώπου";
						$pr_content = $this->db->select('*')->from('tbltemplates')->where('name', 'Πρότυπο οικονομικού ελέγχου για εταιρεία')->where('type', 'proposals')->get()->row_array();
						$pr_content = $pr_content['content'];
					}
					
					if($pr_content == null){
						$pr_content = '';
					}
					
					$lead_to_input = $this->db->select('*')
											->from('tblleads')
											->where('id', $lead_id)
											->get()
											->row_array();
											
					$pr_cf_arithmos_epikoinwnias = $this->db->select('*')->from('tblcustomfields')->where('fieldto', 'proposal')->where('slug', 'proposal_arithmos_epikinonias')->get()->row_array();
					$pr_cf_voip = $this->db->select('*')->from('tblcustomfields')->where('fieldto', 'proposal')->where('slug', 'proposal_voip')->get()->row_array();
					$pr_no_answer = $this->db->select('*')->from('tblcustomfields')->where('fieldto', 'proposal')->where('slug', 'proposal_no_answer')->get()->row_array();
					
					/*$pr_content = '<p>{proposal_items}</p><p><strong><span style="text-decoration:underline;">&#935;&#961;&#972;&#957;&#959;&#962; &#953;&#963;&#967;&#973;&#959;&#962; &#960;&#961;&#959;&#963;&#966;&#959;&#961;&#945;&#962;</span></strong></p>
					<p>*&#919; &#960;&#961;&#959;&#963;&#966;&#959;&#961;&#940; &#953;&#963;&#967;&#973;&#949;&#953; &#947;&#953;&#945; 15 &#951;&#956;&#941;&#961;&#949;&#962;.<strong><span style="text-decoration:underline;"></span></strong></p>
					<p>&#919; &#954;&#945;&#964;&#945;&#946;&#959;&#955;&#942; &#964;&#959;&#965; &#960;&#959;&#963;&#959;&#973; &#956;&#960;&#959;&#961;&#949;&#943; &#957;&#945; &#960;&#961;&#945;&#947;&#956;&#945;&#964;&#959;&#960;&#959;&#953;&#951;&#952;&#949;&#943; &#963;&#949; &#941;&#957;&#945;&#957; &#945;&#960;&#972; &#964;&#959;&#965;&#962; &#960;&#945;&#961;&#945;&#954;&#940;&#964;&#969; &#964;&#961;&#945;&#960;&#949;&#950;&#953;&#954;&#959;&#973;&#962; &#955;&#959;&#947;&#945;&#961;&#953;&#945;&#963;&#956;&#959;&#973;&#962; &#960;&#959;&#965; &#964;&#951;&#961;&#949;&#943; &#951; &#949;&#964;&#945;&#953;&#961;&#949;&#943;&#945; &#956;&#945;&#962;:</p>
					<p><span><strong>&#932;&#961;&#945;&#960;&#949;&#950;&#953;&#954;&#959;&#943; &#923;&#959;&#947;&#945;&#961;&#953;&#945;&#963;&#956;&#959;&#943;</strong></span></p>
					<p><strong>&#917;&#952;&#957;&#953;&#954;&#942; &#932;&#961;&#940;&#960;&#949;&#950;&#945;</strong><br>IBAN: GR9401104780000047840767454<br>BIC:<span>&#160;</span><span>ETHNGRAA</span><br>&#908;&#957;&#959;&#956;&#945; &#916;&#953;&#954;&#945;&#953;&#959;&#973;&#967;&#959;&#965;: &#932;&#918;&#927;&#929;&#914;&#913;&#931; &#925;&#921;&#922;&#927;&#923;&#913;&#927;&#931; &#915;&#917;&#937;&#929;&#915;&#921;&#927;&#931;</p>
					<p><strong>Alpha &#932;&#961;&#940;&#960;&#949;&#950;&#945;</strong><br>IBAN: GR2201407900790002002009641<br>BIC:<span>&#160;</span><span>CRBAGRAA</span><br>&#908;&#957;&#959;&#956;&#945; &#916;&#953;&#954;&#945;&#953;&#959;&#973;&#967;&#959;&#965;: &#932;&#918;&#927;&#929;&#914;&#913;&#931; &#925;&#921;&#922;&#927;&#923;&#913;&#927;&#931; &#915;&#917;&#937;&#929;&#915;&#921;&#927;&#931;</p>
					<p><strong>VivaWallet</strong><br>IBAN: GR4570100000000287033672185<br>BIC: VPAYGRAA<br>&#908;&#957;&#959;&#956;&#945; &#916;&#953;&#954;&#945;&#953;&#959;&#973;&#967;&#959;&#965;: &#932;&#918;&#927;&#929;&#914;&#913;&#931; &#925;&#921;&#922;&#927;&#923;&#913;&#927;&#931; &#915;&#917;&#937;&#929;&#915;&#921;&#927;&#931;</p>
					<p>&#928;&#945;&#961;&#945;&#954;&#945;&#955;&#959;&#973;&#956;&#949; &#954;&#945;&#964;&#940; &#964;&#951;&#957; &#954;&#945;&#964;&#940;&#952;&#949;&#963;&#942; &#963;&#945;&#962; &#957;&#945; &#963;&#965;&#956;&#960;&#955;&#951;&#961;&#974;&#963;&#949;&#964;&#949; &#969;&#962; &#945;&#953;&#964;&#953;&#959;&#955;&#959;&#947;&#943;&#945; &#964;&#959; &#959;&#957;&#959;&#956;&#945;&#964;&#949;&#960;&#974;&#957;&#965;&#956;&#972; &#963;&#945;&#962;.</p>
					<p><strong><span style="text-decoration:underline;">&#908;&#961;&#959;&#953; &#928;&#961;&#959;&#963;&#966;&#959;&#961;&#940;&#962;</span></strong></p>
					<ul>
					<li>&#919; &#945;&#960;&#959;&#960;&#955;&#951;&#961;&#969;&#956;&#942; &#964;&#951;&#962; &#960;&#961;&#959;&#963;&#966;&#959;&#961;&#940;&#962; &#952;&#945; &#960;&#961;&#941;&#960;&#949;&#953; &#957;&#945; &#941;&#967;&#949;&#953; &#959;&#955;&#959;&#954;&#955;&#951;&#961;&#969;&#952;&#949;&#943; &#947;&#953;&#945; &#957;&#945; &#949;&#954;&#954;&#953;&#957;&#942;&#963;&#949;&#953; &#951; &#948;&#953;&#945;&#948;&#953;&#954;&#945;&#963;&#943;&#945; &#964;&#959;&#965; &#927;&#953;&#954;&#959;&#957;&#959;&#956;&#953;&#954;&#959;&#973; &#917;&#955;&#941;&#947;&#967;&#959;&#965;.</li>
					<li>&#931;&#949; &#960;&#949;&#961;&#943;&#960;&#964;&#969;&#963;&#951; &#960;&#959;&#965; &#959;&#955;&#959;&#954;&#955;&#951;&#961;&#974;&#963;&#959;&#965;&#956;&#949; &#964;&#951; &#948;&#953;&#945;&#948;&#953;&#954;&#945;&#963;&#943;&#945; &#964;&#951;&#962; &#961;&#973;&#952;&#956;&#953;&#963;&#951;&#962;, &#964;&#959; &#954;&#972;&#963;&#964;&#959;&#962; &#947;&#953;&#945; &#964;&#959;&#957; &#959;&#953;&#954;&#959;&#957;&#959;&#956;&#953;&#954;&#972; &#941;&#955;&#949;&#947;&#967;&#959; &#952;&#945; &#963;&#965;&#956;&#960;&#949;&#961;&#953;&#955;&#951;&#966;&#952;&#949;&#943; &#963;&#964;&#951;&#957; &#964;&#953;&#956;&#942; &#964;&#951;&#962; &#960;&#961;&#959;&#963;&#966;&#959;&#961;&#940;&#962; &#964;&#951;&#962; &#961;&#973;&#952;&#956;&#953;&#963;&#951;&#962;.</li>
					<li>&#922;&#945;&#964;&#940; &#964;&#951;&#957; &#949;&#958;&#972;&#966;&#955;&#951;&#963;&#951; &#964;&#951;&#962; &#960;&#961;&#959;&#963;&#966;&#959;&#961;&#940;&#962;, &#952;&#945; &#954;&#959;&#960;&#949;&#943; &#960;&#945;&#961;&#945;&#963;&#964;&#945;&#964;&#953;&#954;&#972; &#972;&#960;&#959;&#965; &#952;&#945; &#960;&#949;&#961;&#953;&#955;&#945;&#956;&#946;&#940;&#957;&#949;&#953; &#934;.&#928;.&#913;., &#965;&#960;&#959;&#955;&#959;&#947;&#943;&#950;&#959;&#957;&#964;&#945;&#962; &#964;&#959; &#963;&#973;&#957;&#959;&#955;&#959; &#964;&#969;&#957; &#949;&#958;&#972;&#948;&#969;&#957; &#960;&#959;&#965; &#949;&#957;&#948;&#949;&#967;&#959;&#956;&#941;&#957;&#969;&#962; &#941;&#967;&#959;&#965;&#957; &#960;&#961;&#959;&#954;&#973;&#968;&#949;&#953;.</li>
					<li><span>&#924;&#949;&#964;&#940; &#964;&#951;&#957; &#960;&#955;&#942;&#961;&#951; &#945;&#960;&#959;&#948;&#959;&#967;&#942; &#954;&#945;&#953; &#949;&#960;&#953;&#964;&#965;&#967;&#942; &#959;&#955;&#959;&#954;&#955;&#942;&#961;&#969;&#963;&#951; &#964;&#951;&#962; &#945;&#947;&#959;&#961;&#940;&#962; &#964;&#951;&#962; &#960;&#961;&#959;&#963;&#966;&#959;&#961;&#940;&#962; &#956;&#945;&#962;, &#952;&#945; &#949;&#954;&#948;&#959;&#952;&#949;&#943; &#964;&#959; &#945;&#960;&#945;&#961;&#945;&#943;&#964;&#951;&#964;&#959; &#960;&#945;&#961;&#945;&#963;&#964;&#945;&#964;&#953;&#954;&#972;, &#964;&#959; &#959;&#960;&#959;&#943;&#959; &#952;&#945; &#960;&#949;&#961;&#953;&#955;&#945;&#956;&#946;&#940;&#957;&#949;&#953; &#972;&#955;&#949;&#962; &#964;&#953;&#962; &#955;&#949;&#960;&#964;&#959;&#956;&#941;&#961;&#949;&#953;&#949;&#962; &#964;&#951;&#962; &#963;&#965;&#957;&#945;&#955;&#955;&#945;&#947;&#942;&#962;. &#932;&#959; &#960;&#945;&#961;&#945;&#963;&#964;&#945;&#964;&#953;&#954;&#972; &#945;&#965;&#964;&#972; &#952;&#945; &#945;&#960;&#959;&#963;&#964;&#945;&#955;&#949;&#943; &#963;&#964;&#959;&#957; &#960;&#949;&#955;&#940;&#964;&#951; &#949;&#943;&#964;&#949; &#963;&#949; &#941;&#957;&#964;&#965;&#960;&#951; &#956;&#959;&#961;&#966;&#942; &#949;&#943;&#964;&#949; &#956;&#941;&#963;&#969; &#951;&#955;&#949;&#954;&#964;&#961;&#959;&#957;&#953;&#954;&#959;&#973; &#964;&#945;&#967;&#965;&#948;&#961;&#959;&#956;&#949;&#943;&#959;&#965;</span></li>
					</ul>
					<p><b>&#931;&#967;&#949;&#964;&#953;&#954;&#940; &#956;&#949; &#964;&#951; &#961;&#973;&#952;&#956;&#953;&#963;&#951; &#960;&#959;&#965; &#945;&#966;&#959;&#961;&#940; &#966;&#965;&#963;&#953;&#954;&#972; &#960;&#961;&#972;&#963;&#969;&#960;&#959;</b><b>&#160;&#952;&#945; &#967;&#961;&#949;&#953;&#945;&#963;&#964;&#959;&#973;&#956;&#949;:</b></p>
					<ul>
					<li>&#922;&#969;&#948;&#953;&#954;&#959;&#943; TAXIS &#945;&#953;&#964;&#959;&#973;&#957;&#964;&#959;&#962;</li>
					<li>&#922;&#969;&#948;&#953;&#954;&#959;&#943; TAXIS &#931;&#965;&#950;&#973;&#947;&#959;&#965;</li>
					<li>&#922;&#969;&#948;&#953;&#954;&#959;&#943; TAXIS &#949;&#958;&#945;&#961;&#964;&#974;&#956;&#949;&#957;&#969;&#957; &#956;&#949;&#955;&#974;&#957;*</li>
					</ul>
					<p>*&#913;&#957; &#964;&#945; &#949;&#958;&#945;&#961;&#964;&#974;&#956;&#949;&#957;&#945; &#956;&#941;&#955;&#951; &#948;&#949;&#957; &#941;&#967;&#959;&#965;&#957; &#946;&#947;&#940;&#955;&#949;&#953; &#913;&#934;&#924; &#967;&#961;&#949;&#953;&#945;&#950;&#972;&#956;&#945;&#963;&#964;&#949; &#964;&#959; &#913;&#924;&#922;&#913; &#964;&#959;&#965;&#962;.</p>
					';*/
					
					
					// AUTO EDW PAEI STO LIVE / HUB2 / TEST
					$raw_pr_datetime = trim((string)$this->input->post('pr_datetime'));
					$final_pr_datetime = '';
					if ($raw_pr_datetime !== '') {
						$dt = false;
						// Try the formats we commonly see (add others if you find different ones)
						$try_formats = [
							'd/m/Y H:i',      // DD/MM/YYYY hh:ii (UI expected on many systems)
							'd/m/Y H:i:s',
							'd/m/Y',          // date only
							'Y-m-d H:i',      // ISO-like you had previously
							'Y-m-d H:i:s',
							'Y-m-d',          // date only ISO
						];
						foreach ($try_formats as $fmt) {
							$parsed = DateTime::createFromFormat($fmt, $raw_pr_datetime);
							if ($parsed !== false) {
								$dt = $parsed;
								break;
							}
						}
						// Last resort, try generic parser
						if ($dt === false) {
							try {
								$dt = new DateTime($raw_pr_datetime);
							} catch (Exception $e) {
								$dt = false;
							}
						}
						if ($dt !== false) {
							// Format to the UI date format (get_current_date_format(true) usually returns e.g. 'd/m/Y H:i')
							$ui_format = get_current_date_format(true);
							// Ensure the UI format contains time when the parsed value has time
							// (get_current_date_format(true) generally includes time if 'true' passed)
							$final_pr_datetime = $dt->format($ui_format);
						} else {
							// Could not parse — log and leave empty to avoid to_sql_date fatal
							log_message('error', '[call_logs] Could not parse pr_datetime: ' . var_export($raw_pr_datetime, true));
							$final_pr_datetime = ''; // safer than leaving a badly formatted string
						}
					}
					//$final_pr_datetime = $this->input->post('pr_datetime');		// AUTO EDW PAEI STO LOCAL MOY
					
					$pr_data = [
						'subject'       => $subject_input,
						'status'        => 6,
						'assigned'      => (int) get_staff_user_id(),
						'rel_type'      => 'lead',
						'rel_id'        => (int) $lead_id,
						'proposal_to'   => $lead_to_input['name'] ?? '',
						'address'       => $lead_to_input['address'] ?? '',
						'date'          => date(get_current_date_format(true)),//date('Y-m-d H:i:s'),
						'open_till'     => $final_pr_datetime,//$this->input->post('pr_datetime'),
						'currency'      => 1,
						'discount_type' => '',
						'city'          => $lead_to_input['city'] ?? '',
						'state'         => $lead_to_input['state'] ?? '',
						'country'       => $lead_to_input['country'] ?? '',
						'zip'           => $lead_to_input['zip'] ?? '',
						'email'         => $lead_to_input['email'] ?? '',
						'phone'         => $lead_to_input['phonenumber'] ?? '',
						'tags'          => '',
						'allow_comments'=> 'on', // model will convert to 1
						'show_quantity_as' => 1,
						'discount_percent' => 0,
						'adjustment'       => 0,
						'total' => $this->input->post('pr_cost') + ($this->input->post('pr_cost') * 0.24),//'372.00',
						'subtotal' => $this->input->post('pr_cost'),//'300.00',
						'content' => $pr_content,
						// IMPORTANT: newitems must be an array, keyed numerically
						'newitems' => [
							1 => [
								'description'     => $new_item_description,
								'long_description'=> '',
								'qty'             => 1,
								'rate'            => $this->input->post('pr_cost'),//'300.00',	/*itemable*/
								'taxname'         => ['Standar Vat|24.00'],
								'order'           => 1,   // ordinal position (integer)
								'unit'            => '',  // unit name (string) or unit id depending on your install
							],
						],
						// custom_fields must be an array structured the way handle_custom_fields_post expects
						// format: ['proposal' => [ custom_field_id => value, ... ]]
						'custom_fields' => [
							'proposal' => [
								(int)$pr_cf_arithmos_epikoinwnias['id'] => '',
								(int)$pr_cf_voip['id']                   => '',
								(int)$pr_no_answer['id']                 => '',
							],
						],
					];
					
					$id = $this->proposals_model->add($pr_data);
					
					$final_pr_datetime_remark= $this->input->post('pr_datetime_remark');
					$dt = DateTime::createFromFormat('Y-m-d H:i:s', $final_pr_datetime_remark)?: DateTime::createFromFormat('Y-m-d H:i', $final_pr_datetime_remark);
					if (!$dt) { $dt = DateTime::createFromFormat('d/m/Y H:i', $final_pr_datetime_remark); }
					if (!$dt) { $dt = date_create($final_pr_datetime_remark); }
					$final_pr_datetime_remark = $dt ? $dt->format('Y-m-d H:i') : null;
					
					if($id){
						$this->db->where('id', $id);
						$this->db->update('tblproposals', ['content' => $pr_content]);
					}
					
					$data_remark = [ 'rel_id' => $lead_id, 'remark' => "Έγινε Επικοινωνία, και δημιουργήθηκε προσφορά", 'rel_type' => 1, 'date' => date('Y-m-d H:i:s'), 'lm_follow_up_date' => $final_pr_datetime_remark, 'is_notified' => 0 ];		//'lm_follow_up_date' => $this->input->post('pr_datetime_remark')
					$this->db->insert('tbllead_manager_meeting_remark', $data_remark);
					
					$data_activity = [ 'type' => 'remark', 'is_audio_call_recorded' => 0, 'lead_id' => $lead_id, 'date' => date('Y-m-d H:i:s'), 'description' => "Έγινε Επικοινωνία, και δημιουργήθηκε προσφορά", 'additional_data' => null, 'staff_id' => get_staff_user_id(), 'direction' => 'outgoing', 'call_duration' => null, 'is_client' => 0 ];
					$this->db->insert('tbllead_manager_activity_log', $data_activity);
					
					$data_reminder = ['description' => 'Υπενθύμιση για τη προσφορά', 'date' => $final_pr_datetime_remark, 'isnotified' => 0, 'rel_id' => $id, 'staff' => get_staff_user_id(), 'rel_type' => 'proposal', 'notify_by_email' => 0, 'creator' => get_staff_user_id()];
					$this->db->insert('tblreminders', $data_reminder);
					
					$data_pr_activity = ['proposal_id' => $id, 'pr_date' => date('Y-m-d H:i:s'), 'pr_description' => 'Έγινε Επικοινωνία, και δημιουργήθηκε προσφορά', 'staff_id' => get_staff_user_id()];
					$this->db->insert('tblpr_manager_activity_log', $data_pr_activity);
					
					$data_pr_remark = ['pr_rel_id' => $id, 'pr_remark' => 'Έγινε Επικοινωνία, και δημιουργήθηκε προσφορά', 'pr_date' => date('Y-m-d H:i:s'), 'pr_follow_up_date' => $final_pr_datetime_remark];
					$this->db->insert('tblpr_manager_meeting_remark', $data_pr_remark);
					
					$wanted_status = "Ανοικτή Προσφορά";
					$wanted_status = $this->db->select('*')->from('tblleads_status')->where('name', $wanted_status)->get()->row_array();
					$this->db->where('id', $lead_id);
					$this->db->update('tblleads', ['status' => $wanted_status['id']]);
					
					$response_code = admin_url('proposals/list_proposals/' . $id . '#' . $id);
					/*if ($id) {
						set_alert('success', _l('added_successfully', _l('proposal')));
						if ($this->set_proposal_pipeline_autoload($id)) {
							redirect(admin_url('proposals'));
						} else {
							redirect(admin_url('proposals/list_proposals/' . $id));
						}
					}*/
					
					/*ISWS META APO EDW VAZOUME REMARKS*/
				}
				
				/**************************************TELOS CUSTOM FIELDS KAI REMARKS*/
				
				/*$this->db
					 ->where('rel_id',   $lead_id)
					 ->where('rel_type', 'lead');
				$existing = $this->db->get('tblnotes');
				if ($existing->num_rows() > 0) {
					$this->db
						 ->where('rel_id',   $lead_id)
						 ->where('rel_type', 'lead')
						 ->update('tblnotes', ["description" => $note, "dateadded" => date('Y-m-d H:i:s')]);
				}else{
					$this->db->insert('tblnotes', ["rel_id" => $lead_id, "rel_type" => "lead", "description" => $note, "dateadded" => date('Y-m-d H:i:s')]);
				}*/
				if (trim($note) != '') {
					$this->db->insert('tblnotes', ["rel_id" => $lead_id, "rel_type" => "lead", "description" => $note, "addedfrom" => $staff_id, "dateadded" => date('Y-m-d H:i:s')]);
				}
			}
			if($client_id == -1){
				$allclients_id =  (int)$this->input->post('allclients_id');
			}

			// Prepare update payload
			$data = [];
			if($allclients_id > 0){
				$data['client_id'] = $allclients_id;
				$data['lead_id']   = null;
				
				$contactId = (int)$this->input->post('contactId');
				$contact = $this->db
					->where('id', $contactId)
					->get('tblcontacts')
					->row_array();
				$new_data = [
					'userid'	  => $allclients_id,
					'is_primary'  => 0,
					'firstname'   => $contact['firstname'],
					'lastname'    => $contact['lastname'],
					'email'       => $contact['email'],
					'phonenumber' => $contact['phonenumber'],
					'title'		  => $contact['title'],
					'datecreated' => date('Y-m-d H:i:s'),
				];
				$this->db->insert('tblcontacts', $new_data);
				$new_id = $this->db->insert_id();
				
				$typeClient = $this->input->post('type_id');
				$customField = $this->db
					->where('slug', 'contacts_idos_epafis')
					->get('tblcustomfields')
					->row();
				$this->db->insert('tblcustomfieldsvalues', ["relid" => $new_id, "fieldid" => $customField->id, "fieldto" => "contacts", "value" => $typeClient]);
			}else if ($client_id > 0) {
				$data['client_id'] = $client_id;
				$data['lead_id']   = null;
			} elseif ($lead_id > 0) {
				$data['lead_id']   = $lead_id;
				$data['client_id'] = null;
			} else {
				$data['client_id'] = null;
				$data['lead_id']   = null;
			}

			// batch update all IDs
			$this->db->where_in('id', $ids)
					->update('tblcall_logs', $data);
					
			echo json_encode([
				'success' => true,
				'message' => _l('call_log_updated'),
				'updated' => count($ids),
				'response_code' => $response_code
			]);
		}catch(Exception $e){
			echo json_encode([
			  'success' => false,
			  'message' => "Who knows"
			]);
		}
		exit;
	}
	
	/*public function get_phone_matches()
	{
		// Only allow AJAX
		if (!$this->input->is_ajax_request()) {
			show_error('No direct access allowed', 403);
		}

		$phoneRaw = $this->input->post('phone');
		// strip non-digits
		$digits = preg_replace('/\D+/', '', $phoneRaw);
		// take last 10 digits (if shorter, use as-is)
		$last10 = substr($digits, -10);

		// helper to build the RIGHT(REPLACE(...),10) SQL fragment
		$cleanSql = "RIGHT(
			REPLACE(
			  REPLACE(
				REPLACE(
				  REPLACE(phonenumber, ' ', ''), 
				'-', ''), 
			  '+', ''), 
			'(', ''), 10)";

		// 1) Search contacts
		$this->db->select('id, userid, firstname, lastname, email, phonenumber');
		$this->db->where("$cleanSql =", $last10);
		$contacts = $this->db->get('tblcontacts')->result_array();
		
		$client_ids = array_column($contacts, 'userid');
		if (! empty($client_ids)) {
			$clients_found = $this->db
				->select('*')
				->where_in('userid', $client_ids)
				->get('tblclients')
				->result_array();
		} else {
			$clients_found = [];
		}

		// 2) Search leads
		$this->db->select('id, name, phonenumber');
		$this->db->where("$cleanSql =", $last10);
		$leads = $this->db->get('tblleads')->result_array();

		// return JSON
		echo json_encode([
			'contacts' 		=> $contacts,
			'leads'    		=> $leads,
			'clients_found' => $clients_found
		]);
	}*/
	public function get_phone_matches()
	{
		// Only allow AJAX
		if (!$this->input->is_ajax_request()) {
			show_error('No direct access allowed', 403);
		}

		$phoneRaw = $this->input->post('phone');
		// Strip non-digits and get last 10 digits
		$digits = preg_replace('/\D+/', '', $phoneRaw);
		$last10 = substr($digits, -10);

		// SQL cleaning expression (reusable)
		$cleanExpr = "REPLACE(REPLACE(REPLACE(REPLACE(%s, ' ', ''), '-', ''), '+', ''), '(', '')";
		$cleanSqlMain = "RIGHT(" . sprintf($cleanExpr, 'phonenumber') . ", 10)";
		$cleanSqlCustom = "RIGHT(" . sprintf($cleanExpr, 'value') . ", 10)";

		// 1. Get custom field IDs
		$slugs = [
			'contacts_til_ergasias',
			'contacts_tilefono_allo',
			'contacts_stathero_tilefono',
			'leads_til_ergasias',
			'leads_tilefono_allo',
			'leads_stathero_tilefono'
		];
		
		$this->db->select('id, slug');
		$this->db->where_in('slug', $slugs);
		$custom_fields = $this->db->get('tblcustomfields')->result_array();

		// Separate contact and lead custom field IDs
		$contact_custom_field_ids = [];
		$lead_custom_field_ids = [];

		foreach ($custom_fields as $field) {
			if (strpos($field['slug'], 'contacts_') === 0) {
				$contact_custom_field_ids[] = $field['id'];
			} elseif (strpos($field['slug'], 'leads_') === 0) {
				$lead_custom_field_ids[] = $field['id'];
			}
		}

		// 2. Search contacts (main + custom fields)
		$contact_ids = [];

		// Main contact numbers
		$this->db->select('id');
		$this->db->where("$cleanSqlMain =", $last10);
		$main_contacts = $this->db->get('tblcontacts')->result_array();
		$contact_ids = array_column($main_contacts, 'id');

		// Contact custom fields
		if (!empty($contact_custom_field_ids)) {
			$this->db->select('relid as contact_id');
			$this->db->where_in('fieldid', $contact_custom_field_ids);
			$this->db->where("$cleanSqlCustom =", $last10);
			$this->db->where('fieldto', 'contacts');
			$custom_contacts = $this->db->get('tblcustomfieldsvalues')->result_array();
			
			foreach ($custom_contacts as $c) {
				$contact_ids[] = $c['contact_id'];
			}
		}
		
		$contact_ids = array_unique($contact_ids);
		$contacts = [];
		
		if (!empty($contact_ids)) {
			$this->db->select('id, userid, firstname, lastname, email, phonenumber');
			$this->db->where_in('id', $contact_ids);
			$contacts = $this->db->get('tblcontacts')->result_array();
		}

		// Get associated clients
		$client_ids = array_column($contacts, 'userid');
		$clients_found = [];
		if (!empty($client_ids)) {
			$this->db->where_in('userid', $client_ids);
			$clients_found = $this->db->get('tblclients')->result_array();
		}

		// 3. Search leads (main + custom fields)
		$lead_ids = [];

		// Main lead numbers
		$this->db->select('id');
		$this->db->where("$cleanSqlMain =", $last10);
		$main_leads = $this->db->get('tblleads')->result_array();
		$lead_ids = array_column($main_leads, 'id');

		// Lead custom fields
		if (!empty($lead_custom_field_ids)) {
			$this->db->select('relid as lead_id');
			$this->db->where_in('fieldid', $lead_custom_field_ids);
			$this->db->where("$cleanSqlCustom =", $last10);
			$this->db->where('fieldto', 'leads');
			$custom_leads = $this->db->get('tblcustomfieldsvalues')->result_array();
			
			foreach ($custom_leads as $l) {
				$lead_ids[] = $l['lead_id'];
			}
		}
		
		$lead_ids = array_unique($lead_ids);
		$leads = [];
		
		if (!empty($lead_ids)) {
			$status_row = $this->db
				->select('id')
				->from('tblleads_status')
				->where('name', 'Διπλοεγγραφή')
				->get()
				->row_array();
			//log_message('debug', 'bad_status_row: ' . print_r($status_row, true));  //application/logs
			$bad_status_id = (int) $status_row['id'];
			
			$this->db->select('id, name, phonenumber');
			$this->db->where_in('id', $lead_ids);
			$this->db->where('status !=', $bad_status_id);
			$leads = $this->db->get('tblleads')->result_array();
		}

		// Return JSON response
		echo json_encode([
			'contacts'      => $contacts,
			'leads'         => $leads,
			'clients_found' => $clients_found,
			'csrf_hash' => $this->security->get_csrf_hash()
		]);
	}
	
	public function add_call_notification() {
		$this->config->set_item('csrf_protection', false);
		
		$data = $this->input->post('data');
		
		$userid = get_staff_user_id();
		$description = "Μη καταχωρημένη κλήση ". $data['phone'];
		
		$link = '#call-modal/'.base64_encode(json_encode($data)); //. base64_encode(json_encode($data));
					
		
		// Check for existing unread notification within last 5 minutes
		$this->db->where('touserid', $userid);
		$this->db->where('description', $description);
		$this->db->where('link', $link);
		//$this->db->where('isread', 0);
		//$this->db->where('date >', date('Y-m-d H:i:s', strtotime('-5 minutes')));
		$existing = $this->db->count_all_results('tblnotifications');

		if ($existing > 0) {
			return false; // Duplicate exists
		}

		$this->db->insert('tblnotifications', [
			'isread' => 0,
			'isread_inline' => 0,
			'date' => date('Y-m-d H:i:s'),
			'description' => $description,
			'fromuserid' => 0,
			'touserid' => $userid,
			'link' => $link,
			//'additional_data' => json_encode($data)
		]);
		
		$id = $this->db->insert_id();
		pusher_trigger_notification([$userid]);
		//return $id;
	}
	
	public function save_call_to_lead(){
		$lead_id = $this->input->post('lead_id');
		
		$call_ids = $this->input->post('call_ids');
		
		$data['client_id'] = null;
		$data['lead_id']   = $lead_id;
		
		$this->db->where_in('id', $call_ids)
					->update('tblcall_logs', $data);
					
		echo json_encode([
			'success' => true,
		]);
	}
	
	public function show_dashboard(){
		$all_leads = $this->db
					->select('*')
					->from('tblleads')
					->get()
					->result_array();
					
		$subquery = $this->db
			->select('MAX(id) as latest_id')
			->from('tbllead_manager_meeting_remark')
			->where('lm_follow_up_date >', date('Y-m-d H:i:s'))
			->group_by('rel_id')
			->get_compiled_select();

		$future_remarks = $this->db
			->select('*')
			->from('tbllead_manager_meeting_remark')
			->where("id IN ($subquery)", NULL, FALSE)
			->order_by('lm_follow_up_date', 'ASC') // or 'DESC' for newest first
			->get()
			->result_array();
		$rel_ids = array_column($future_remarks, 'rel_id');
			
		$leads_from_remarks = $this->db
			->select('*')
			->from('tblleads')
			->where_in('id', $rel_ids)
			->get()
			->result_array();
		
		$data = [];
		
		$data['title'] = "Πίνακας ελέγχου";
		$staff_id = $this->session->userdata('staff_user_id');
		$data['staff'] = $this->staff_model->get($staff_id);
		$data['all_leads'] = $all_leads;
		$data['leads_from_remarks'] = $leads_from_remarks;
		$data['future_remarks'] = $future_remarks;
		
		$this->load->view('dashboard', $data);
	}
	
	public function get_notes_for_lead(){
		$lead_id = $this->input->post('lead_id');
		
		$all_notes = $this->db
			->select('*')
			->from('tblnotes')
			->where('rel_type', 'lead')
			->where('rel_id', $lead_id)
			->get()
			->result_array();
			
		echo json_encode([
			'success' => true,
			'all_notes'=> $all_notes
		]);
	}
}