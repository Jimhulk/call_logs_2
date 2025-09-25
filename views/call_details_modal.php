<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>


<?php
// bootstrap CI
$CI = &get_instance();
// load & fetch only once
if (! isset($CI->callModalClients)) {
    $CI->load->model('clients_model');
    $CI->callModalClients = $CI->clients_model->get();
}
if (! isset($CI->callModalLeads)) {
    $CI->load->model('leads_model');
    $CI->callModalLeads = $CI->leads_model->get();
}

// now $CI->callModalClients and $CI->callModalLeads are arrays
$clients = $CI->callModalClients;
$leads   = $CI->callModalLeads;

$CI->load->database();
$customFields = $CI->db
    ->where('slug', 'contacts_idos_epafis')
    ->get('tblcustomfields')
    ->row();
$options = [];
if (isset($customFields->options) && $customFields->options !== '') {
    // Split and trim each option
    $options = array_map('trim', explode(',', $customFields->options));
}
?>


<!-- CALL DETAILS MODAL -->
<div id="callDetailsModal" style="z-index: 99999;" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-labelledby="callDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="callDetailsModalLabel"></h3>
        <!--button type="button" class="close" data-dismiss="modal" aria-label="<?php //echo _l('close'); ?>">
          <span aria-hidden="true">&times;</span>
        </button-->
      </div>
      <form id="callDetailsForm" action="<?php echo admin_url('call_logs/save_call'); ?>" method="POST">
        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>" />
        <input type="hidden" id="callDetailsIds" name="callDetailsIds" value="" />
		<input type="hidden" id="contactId" name="contactId" value="" />

        <div class="modal-body">
          <p><strong>Ημερομηνία:</strong> <span id="callDetailsCallTime"></span></p>
          <p><strong>Διάρκεια:</strong> <span id="callDetailsCallTalking"></span></p>
          <p><strong>Τηλέφωνο:</strong> <span id="callDetailsCallPhone"></span></p>

          <ul hidden class="nav nav-tabs" role="tablist">
            <li class="nav-item active">
              <a class="nav-link active" id="tab-main-tab" data-toggle="tab" href="#tab-main" role="tab" aria-controls="tab-main" aria-selected="true">
                Main
              </a>
            </li>
            <!--li class="nav-item">
              <a class="nav-link" id="tab-prospective-tab" data-toggle="tab" href="#tab-prospective" role="tab" aria-controls="tab-prospective" aria-selected="false">
                Δυνητικοί Πελάτες
              </a>
            </li-->
			<!--li class="nav-item">
              <a class="nav-link" id="tab-allclients-tab" data-toggle="tab" href="#tab-allclients" role="tab" aria-controls="tab-allclients" aria-selected="false">
                'Αλλοι Πελάτες
              </a>
            </li-->
          </ul>

          <div class="tab-content mt-3">
            <!-- Main Tab -->
            <div class="tab-pane fade show active in" id="tab-main" role="tabpanel" aria-labelledby="tab-main-tab">
              <div class="form-group">
                <label for="mainSelect" id="mainSelectLabel" class="tw-mt-3">Πελάτης</label>
                <select class="form-control" id="mainSelect" name="main_id">
                  <option value="0" selected>---</option>
                </select>
				<input hidden type="text" id="client_or_lead" name="client_or_lead" />
				
				<div id="allclientsDiv" hidden>
					<label class="tw-mt-4" id="allclientsSelectLabel" for="allclientsSelect">'Αλλοι Πελάτες</label>
					<select class="form-control" id="allclientsSelect" name="allclients_id">
					  <option value="0" selected>---</option>
					  <?php foreach ($clients as $c): ?>
					  <option value="<?php echo $c['userid']; ?>"><?php echo html_escape($c['company']); ?></option>
					  <?php endforeach; ?>
					</select>
					<label class="tw-mt-4" id="typeSelectLabel" for="typeSelect">Τύπος Πελάτη</label>
					<select class="form-control" id="typeSelect" name="type_id">
					  <option value="0" selected>---</option>
					  <?php foreach ($options as $c): ?>
					  <option value="<?php echo html_escape($c); ?>"><?php echo html_escape($c); ?></option>
					  <?php endforeach; ?>
					</select>
				</div>
				
				<div id="lm_meta_box" class="panel panel-default tw-mt-3 tw-p-4 tw-border-2 tw-border-gray-600 tw-rounded-lg tw-shadow-md tw-bg-white" style="display:none;">
					<label for="katastasi" class="mb-0 tw-mr-2">Κατάσταση:</label>
					<select id="katastasi" name="katastasi" class="selectpicker form-control tw-w-auto tw-mr-2" data-width="100%">
					  <option value="" disabled="" selected="">Επιλογή</option>
					  <option value="no_call">Δεν απάντησε</option>
					  <option value="closed">Το έκλεισε/Κατειλημμένο</option>
					  <option value="recall">Επανάληψη Επικοινωνίας</option>
					  <option value="interest">Ενδιαφέρεται</option>
					  <option value="not_interested">Δεν ενδιαφέρεται</option>
					  <option value="ineligible">Μη επιλέξιμος</option>
					  <option value="appointment">Κλείσιμο Ραντεβού</option>
					  <option value="no_call_on_appointment">Δεν απάντησε στο Ραντεβού</option>
					  <option value="send_proposal">Αποστολή Προσφοράς</option>
					  <option hidden value="appointment_anew">Εκ νέου Ραντεβού</option>	<!-- KRUPSIMO APO TO UI -->
					</select>
					<div id="lm_type_wrapper" style="display:none;" class="tw-mt-2 tw-mt-6">
						<label for="lm_type" class="mb-0 tw-mr-2">Είδος Ραντεβού:</label>
						<select id="lm_type" name="lm_type" class="selectpicker form-control tw-w-auto tw-mr-2" data-width="100%">
						  <option value="" disabled="" selected="">Επιλογή</option>
						  <option value="1">Αξιολόγηση Σύμβασης Αναδιάρθρωσης</option>
						  <option value="2">Παρουσίαση Ανταποδοτικού Συστήματος Affiliate – MECE</option>
						  <option value="3">Πρόσκληση σε Συνέντευξη Εργασίας</option>
						  <option value="4">Συνεδρία για Ρύθμιση και Εξυγίανση Οφειλών</option>
						  <option value="5">Συνεδρία για Τραπεζική Διαμεσολάβηση</option>
						  <option value="6">Ενημέρωση Αίτησης</option>
						  <option value="7">Ραντεβού για Ανάλυση & Στρατηγική Διαχείρισης Ακινήτου</option>
						</select>
					</div>
					<div id="lm_location_wrapper" style="display:none;" class="tw-mt-2 tw-mt-6">
						<label for="lm_location" class="mb-0 tw-mr-2">Τοποθεσία:</label>
						<select id="lm_location" name="lm_location" class="selectpicker form-control tw-w-auto tw-mr-2" data-width="100%">
						  <option value="" disabled="" selected="">Επιλογή</option>
						  <option value="telephone">Τηλεφωνικά</option>
						  <option value="conference">Τηλεδιάσκεψη</option>
						  <option value="deligianni">Δεληγιαννη 8, Τρίπολη 22100</option>
						  <option value="apostolopoulou">Αποστολοπούλου 8, Τρίπολη 22100</option>
						  <option value="arapaki">Αραπάκη 6, Καλλιθέα 17676</option>
						  <option value="karaoli">Καραολή και Δημητρίου 119, Εύοσμος 56224</option>
						</select>
					</div>
					<div id="lm_attendees_wrapper" style="display:none;" class="tw-mt-2 tw-mt-6">
					  <label for="lm_attendees" class="mb-0 tw-mr-2">Συμμετέχοντες:</label>
					  <select id="lm_attendees" name="lm_attendees[]" class="form-control selectpicker" multiple="multiple" style="width:100%">
						<?php
							$CI = &get_instance();
							$CI->load->model('staff_model');
							$staff_members_filter = $CI->staff_model->get();
							foreach ($staff_members_filter as $s) {
								//if( ($s['active'] == 1) && ($s['is_not_staff'] == 0) ){
									echo '<option value="' . $s['staffid'] . '">' . $s['firstname'] . ' ' . $s['lastname'] . '</option>';
								//}
							}
						?>
					  </select>
					</div>
					<div id="lm_datetime_wrapper" style="display:none;" class="tw-mt-2 tw-mt-6">
					  <label for="lm_datetime" class="mb-0 tw-mr-2">Ημερομηνία / Ώρα:</label>
					  <div class="input-group">
						<input type="text" id="lm_datetime" name="lm_datetime" class="form-control datetimepicker" autocomplete="off"/>
					  </div>
					</div>
					<div id="lm_remark_staff_wrapper" style="display:none;" class="tw-mt-2 tw-mt-6">
					  <label for="lm_remark_staff" class="mb-0 tw-mr-2">Υπάλληλος:</label>
					  <select id="lm_remark_staff" name="lm_remark_staff" class="form-control selectpicker" style="width:100%">
						<?php
							$CI = &get_instance();
							$CI->load->model('staff_model');
							$staff_members_filter = $CI->staff_model->get();
							foreach ($staff_members_filter as $s) {
								if( ($s['active'] == 1) && ($s['is_not_staff'] == 0) ){
									echo '<option value="' . $s['staffid'] . '">' . $s['firstname'] . ' ' . $s['lastname'] . '</option>';
								}
							}
						?>
					  </select>
					</div>
					<div id="pr_type_wrapper" style="display:none;" class="tw-mt-2 tw-mt-6">
					  <label for="pr_type" class="mb-0 tw-mr-2">Είδος προσφοράς:</label>
					  <select id="pr_type" name="pr_type" class="selectpicker form-control" data-width="100%">
						  <option value="" disabled="" selected="">Επιλογή</option>
						  <option value="fusiko">Οικονομικός Έλεγχος - Φυσικού Προσώπου</option>
						  <option value="nomiko">Οικονομικός Έλεγχος - Νομικού Προσώπου</option>
						  <option value="teiresia">Έλεγχος οφειλέτη στα μητρώα του Τειρεσία</option>
					  </select>
					</div>
					<div id="pr_cost_wrapper" style="display:none;" class="tw-mt-2 tw-mt-6">
					  <label for="pr_cost" class="mb-0 tw-mr-2">Κόστος Προσφοράς (€):</label>
					  <div class="input-group">
						<input type="text" id="pr_cost" name="pr_cost" class="form-control" autocomplete="off"/>
					  </div>
					</div>
					<div id="pr_datetime_remark_wrapper" style="display:none;" class="tw-mt-2 tw-mt-6">
					  <label for="pr_datetime_remark" class="mb-0 tw-mr-2">Ημερομηνία / Ώρα Remark:</label>
					  <div class="input-group">
						<?php $two_days_later = date('d/m/Y H:i', strtotime('+2 days')); ?>
						<input type="text" id="pr_datetime_remark" name="pr_datetime_remark" class="form-control datetimepicker" value="<?php echo htmlspecialchars($two_days_later, ENT_QUOTES); ?>" autocomplete="off"/>
					  </div>
					</div>
					<div id="pr_datetime_wrapper" style="display:none;" class="tw-mt-2 tw-mt-6">
					  <label for="pr_datetime" class="mb-0 tw-mr-2">Ημερομηνία / Ώρα Λήξης Προσφοράς:</label>
					  <div class="input-group">
						<?php $fifteen_days_later = date('d/m/Y H:i', strtotime('+15 days')); ?>
						<input type="text" id="pr_datetime" name="pr_datetime" class="form-control datetimepicker" value="<?php echo htmlspecialchars($fifteen_days_later, ENT_QUOTES); ?>" autocomplete="off"/>
					  </div>
					</div>
				</div>
				
              </div>
			  
			  <div class="form-group mt-4">
				<label for="noteTextarea" id="noteLabel">Σημειώσεις</label>
				<textarea class="form-control" id="noteTextarea" name="noteTextarea" rows="4" placeholder="Εισάγετε σημειώσεις εδώ..."></textarea>
			  </div>
            </div>
            <!-- Prospective Clients Tab -->
            <!--div class="tab-pane fade" id="tab-prospective" role="tabpanel" aria-labelledby="tab-prospective-tab">
              <div class="form-group">
                <label for="prospectiveSelect" id="prospectiveSelectLabel">Δυνητικός Πελάτης</label>
                <select class="form-control" id="prospectiveSelect" name="lead_id">
                  <option value="0" selected>---</option>
                  <?php //foreach ($leads as $lead): ?>
                  <option value="<?php //echo $lead['id']; ?>"><?php //echo html_escape($lead['name']); ?></option>
                  <?php //endforeach; ?>
                </select>
              </div>
            </div-->
			<!-- All Clients Tab -->
            <!--div class="tab-pane fade" id="tab-allclients" role="tabpanel" aria-labelledby="tab-allclients-tab">
              <div class="form-group">
                <label for="allclientsSelect">'Αλλοι Πελάτες</label>
                <select class="form-control" id="allclientsSelect" name="allclients_id">
                  <option value="0" selected>---</option>
                  <?php //foreach ($clients as $c): ?>
                  <option value="<?php //echo $c['userid']; ?>"><?php //echo html_escape($c['company']); ?></option>
                  <?php //endforeach; ?>
                </select>
              </div>
            </div-->
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Καταχώρηση</button>
          <!--button type="button" class="btn btn-secondary" data-dismiss="modal">Κλείσιμο</button-->
        </div>
      </form>
    </div>
  </div>
</div>

<script>
jQuery(document).ready(function($) {
	//let showValues = ['recall', 'appointment', 'no_call_on_appointment', 'appointment_anew'];
	let showValues = ['recall', 'interest', 'appointment', 'appointment_anew'];
	
	/*$('#lm_datetime').datetimepicker({
	  stepping: 30
	});*/
	
	$('#katastasi').on('changed.bs.select change', function() {
		let val = $(this).val();
		if (val && showValues.indexOf(val) !== -1) {
		  $('#lm_datetime_wrapper').show();

		  if(val == "appointment"){
			  $('#lm_attendees_wrapper').show();
			  $('#lm_location_wrapper').show();
			  $('#lm_type_wrapper').show();
			  $('#lm_remark_staff_wrapper').hide();
		  }else{
			  $('#lm_attendees_wrapper').hide();
			  $('#lm_location_wrapper').hide();
			  $('#lm_remark_staff_wrapper').hide();
			  $('#lm_type_wrapper').hide();
			  if(val == "recall"){
				  $('#lm_remark_staff_wrapper').show();
			  }
		  }
		} else {
		  $('#lm_datetime_wrapper').hide();
		  $('#lm_attendees_wrapper').hide();
		  $('#lm_location_wrapper').hide();
		  $('#lm_type_wrapper').hide();
		  $('#lm_remark_staff_wrapper').hide();
		}
		
		if(val == "send_proposal"){
			$('#pr_type_wrapper').show();
			$('#pr_datetime_wrapper').show();
			$('#pr_cost_wrapper').show();
			$('#pr_datetime_remark_wrapper').show();
		}else{
			$('#pr_type_wrapper').hide();
			$('#pr_datetime_wrapper').hide();
			$('#pr_cost_wrapper').hide();
			$('#pr_datetime_remark_wrapper').hide();
		}
		
		if(val == "interest"){
			$('#lm_remark_staff_wrapper').show();
		}
	});
	
	$('#lm_attendees').selectpicker({
      noneSelectedText: 'Επιλέξτε',
      liveSearch: true,
      actionsBox: true,
      width: '100%'
    });
});
</script>