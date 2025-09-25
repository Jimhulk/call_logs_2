<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php 
/*if (!has_permission('call_logs','','view')) {
	access_denied('Call Logs');
    exit;
}*/
if (!has_permission('call_logs','','view')) {
	$hiddenAttrStaffFilter = 'hidden';
}else{
	$hiddenAttrStaffFilter = 'OXI';
}
?>
<?php init_head(); ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<div id="wrapper">
  <div class="content">
  
	<div class="row">
	  <div class="col-md-12">
		<h4 class="tw-mb-2">Unregistered calls</h4>
		<div class="panel_s">
		  <div class="panel-body">
			<div class="table-responsive">
			  <table class="table table-striped table-bordered">
				<thead>
				  <tr>
					<th>Χρόνος Κλήσης</th>
					<th>Από</th>
					<th>Πρός</th>
					<th>Καταχώρηση</th>
				  </tr>
				</thead>
				<tbody>
				  <?php
					$this->db->from('tblcall_logs');
					$this->db->group_start()
							 ->where('tblcall_logs.from',    $staff->extension)
							 ->or_where('tblcall_logs.to',  $staff->extension)
						   ->group_end();
					$this->db->where('client_id', null);
					$this->db->where('lead_id',   null);

					$calls = $this->db->get()->result();

					if (count($calls) > 0) {
						foreach ($calls as $call) {
							$checkNext = $this->db->from('tblcall_logs')->where('id', $call->id + 1)->get()->row();
							if ($checkNext && $checkNext->call_id === $call->call_id) {
								continue;
							}
							
							//NA FTIAXTEI, exei na kanei me ta calls pou ksekinane apo staff kai ginonte redirect
							if( (strlen($call->from) === 3) && (strlen($call->to)   === 3) ){
								continue;
							}
							
							$phoneUnreg = 0;
							if($call->to == $staff->extension){
								$phoneUnreg = $call->from;
							}else{
								$phoneUnreg = $call->to;
							}
							
							$unregData = [
								"ids" => [$call->id],
								"call_time" => $call->call_time,
								"talking" => $call->talking,
								"phone" => $phoneUnreg
							];
							
							$unregData = base64_encode(json_encode($unregData));
							
							echo '<tr>';
							echo '<td>' . _dt($call->call_time)         . '</td>';
							echo '<td>' . html_escape($call->from)     . '</td>';
							echo '<td>' . html_escape($call->to)       . '</td>';
							echo '<td><button type="button" class="btn btn-sm btn-info register-call-btn" data-unreg="' . html_escape($unregData) . '">Καταχώρηση</button></td>';
							echo '</tr>';
						}
					} else {
						echo '<tr><td colspan="4" class="text-center">Δεν βρέθηκαν κλήσεις</td></tr>';
					}
				  ?>
				</tbody>
			  </table>
			</div>
		  </div>
		</div>
	  </div>
	</div>

    <div class="row">
      <div class="col-md-12">
        <h4 class="tw-mb-2"><?= _l('call_logs'); ?></h4>
		
		<div class="row tw-mb-2">
			<div class="col-md-9">
				<div class="form-group tw-flex tw-items-center">
					<label for="periodFilter" class="mb-0 tw-mr-2">Περίοδος:</label>
					<select id="periodFilter" class="selectpicker form-control tw-w-auto tw-mr-2" data-width="100%">
					  <option value="1">Σήμερα</option>
					  <option value="2">Τελευταίες 2 μέρες</option>
					  <option value="3">Τελευταίες 3 μέρες</option>
					  <option value="all">Όλες τις ημερομηνίες</option>
					</select>
					<label for="staffFilter" class="mb-0 tw-mr-2">Υπάλληλος:</label>
					<select id="staffFilter" class="selectpicker form-control tw-w-auto" data-width="100%">
					  <option value="all" selected>ΟΛΑ</option>
					  <?php 
							$CI = &get_instance();
							$CI->load->model('staff_model');
							$staff_members_filter = $CI->staff_model->get();
							foreach ($staff_members_filter as $s) {
								if( ($s['active'] == 1) && ($s['is_not_staff'] == 0) ){
									echo '<option value="' . $s['extension'] . '">' . $s['firstname'] . ' ' . $s['lastname'] . '</option>';
								}
							}
					  ?>
					</select>
				</div>
			</div>
		</div>
		
        <div class="panel_s">
          <div class="panel-body">
            <?php
              $table_data = [
                "Χρόνος Κλήσης",//_l('call_time'),
                "Πελάτης",//_l('call_id'),
				"Επαφή",//_l('cost'),
                "Απο",//_l('from'),
                "Προς",//_l('to'),
                "Κατεύθυνση",//_l('direction'),
                "Κατάσταση",//_l('status'),
                "Κουδούνισμα",//_l('ringing'),
                "Ομιλία",//_l('talking'),
                
                "Λεπτομέρειες",//_l('call_activity_details'),
				"Ηχογράφηση",
				"Ανάλυση"
              ];
              render_datatable($table_data, 'call-logs');
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  
	  <!-- Grouped Call Logs Modal -->
	<div id="callLogsGroupModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="callLogsGroupModalLabel" aria-hidden="true">
	  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
		<div class="modal-content">
		  <div class="modal-header">
			<h5 class="modal-title" id="callLogsGroupModalLabel"><?= _l('call_logs_group_details'); ?></h5>
			<button type="button" class="close" data-dismiss="modal" aria-label="<?= _l('close'); ?>">
			  <span aria-hidden="true">&times;</span>
			</button>
		  </div>
		  <div class="modal-body">
			<div class="table-responsive">
			  <!-- dynamically populated -->
		    </div>
		  </div>
		  <div class="modal-footer">
			<button type="button" class="btn btn-secondary" data-dismiss="modal"><?= _l('close'); ?></button>
		  </div>
		</div>
	  </div>
	</div>
</div>

<link
  href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"
  rel="stylesheet"
/>
<?php init_tail(); ?>
<script>
  $(function(){
    const callLogsTable = initDataTable('.table-call-logs', window.location.href, [], [], 'undefined', [0, 'desc']);
	
	callLogsTable.on('preXhr.dt', function(e, settings, data) {
		data.periodFilter = $('#periodFilter').val();
		data.staffFilter = $('#staffFilter').val();
	});
	
	$('#periodFilter').on('change', function() {
		callLogsTable.ajax.reload();
	});
	$('#staffFilter').on('change', function() {
		callLogsTable.ajax.reload();
	});
  });
</script>
<script>
  $(function(){
    $('body').on('click', '.view-group', function(){
      const group = $(this).data('group');
      if (!Array.isArray(group) || !group.length) {
        alert('<?= _l('no_additional_logs'); ?>');
        return;
      }

      // Build a full table with ALL columns
      let html = '<table class="table table-striped">';
      html += '<thead><tr>';
      html += '<th><?= "Χρόνος Κλήσης";//_l('call_time'); ?></th>';
      html += '<th><?= "ID Κλήσης";//_l('call_id'); ?></th>';
      html += '<th><?= "Από";//_l('from'); ?></th>';
      html += '<th><?= "Πρός";//_l('to'); ?></th>';
      html += '<th><?= "Κατεύθυνση";//_l('direction'); ?></th>';
      html += '<th><?= "Κατάσταση";//_l('status'); ?></th>';
      html += '<th><?= "Κουδούνισμα";//_l('ringing'); ?></th>';
      html += '<th><?= "Ομιλία";//_l('talking'); ?></th>';
      html += '<th><?= "Κόστος";//_l('cost'); ?></th>';
      html += '<th><?= "Λεπτομέρειες";//_l('call_activity_details'); ?></th>';
      html += '<th>Ηχογράφηση</th>';
      html += '</tr></thead><tbody>';

      group.forEach(row => {
        html += '<tr>';
        html += `<td>${row.call_time}</td>`;
        html += `<td>${row.call_id}</td>`;
        html += `<td>${row.from}</td>`;
        html += `<td>${row.to}</td>`;
        html += `<td>${row.direction}</td>`;
        html += `<td>${row.status}</td>`;
        html += `<td>${row.ringing}</td>`;
        html += `<td>${row.talking}</td>`;
        html += `<td>${row.cost}</td>`;
        html += `<td>${row.call_activity_details}</td>`;
        html += `<td>${row.recording}</td>`;
        html += '</tr>';
      });

      html += '</tbody></table>';

      // Show it
      $('#callLogsGroupModal .table-responsive').html(html);
	  $('#callLogsGroupModal').modal('show');
    });
  });
  
  //********************************************************** YPARXOUN IDI
  function showCallModal(data){
		$("#callDetailsCallTime").html(data.call_time);
		$("#callDetailsCallTalking").html(data.talking);
		$("#callDetailsCallPhone").html(data.phone);
		$("#callDetailsIds").val(JSON.stringify(data.ids));
		
		$("#callDetailsModal").modal("show");
		modalOpen = true;
		console.log("Open modal");
  }
  
  function runQueue(data) {
	  let modalOpen = false;
	  
	  if (modalOpen) {
		console.log("Event queued by function");
		queue.push(data);
		return;
	  }

	  // reset form fields
	  $("#mainSelect").val("0").trigger("change");
	  $("#allclientsSelect").val("0").trigger("change");
	  $("#typeSelect").val("0").trigger("change");
	  $("#noteTextarea").val("").trigger("change");

	  // first AJAX: get matches
	  return new Promise((resolve) => {		// Arxi promise
	  $.post('<?php echo admin_url("call_logs/get_phone_matches"); ?>', { phone: data.phone }, function(res) {
		  var contacts = res.contacts || [],
			  leads    = res.leads    || [],
			  clientsFound = res.clients_found || [];

		  // re-populate the main select
		  $("#mainSelect").empty().append('<option value="0" selected>---</option>');

		  if (contacts.length > 0) {
			$("#callDetailsModalLabel").html(contacts[0].firstname+" "+contacts[0].lastname);
			$("#tab-main-tab").html("Πελάτης");
			$("#mainSelectLabel").html("Πελάτης ("+contacts.length+")");
			clientsFound.forEach(function(c) {
			  $("#mainSelect").append(
				$("<option>")
				  .val(c.userid)
				  .text(c.company + ", " + c.phonenumber)
			  );
			});
			$("#mainSelect").append('<option value="-1">Άλλος</option>');
			$("#mainSelect").val(contacts[0].userid).trigger("change");
			$("#client_or_lead").val("client");
			$("#contactId").val(contacts[0].id);
			$("#lm_meta_box").hide();
		  } else if (leads.length > 0) {
			$("#callDetailsModalLabel").html(leads[0].name);
			$("#tab-main-tab").html("Δυνητικός Πελάτης");
			$("#mainSelectLabel").html("Δυνητικός Πελάτης");
			leads.forEach(function(c) {
			  $("#mainSelect").append(
				$("<option>")
				  .val(c.id)
				  .text(c.name)
			  );
			});
			$("#mainSelect").val(leads[0].id).trigger("change");
			$("#client_or_lead").val("lead");
			$("#lm_meta_box").show();
		  } else {
			// no matches → add notification
			alert("Πρέπει να φτιαχτεί ο πελάτης πρώτα, με τον ίδιο αριθμο τηλεφώνου, πρωτού γίνει η καταχώρηση !!!");
			resolve(0);		// Den vrike kapoio contact
			return;
			$.post('<?php echo admin_url("call_logs/add_call_notification"); ?>', { data: data }, function() {}, "json").fail(function() {});
			return;
		  }

		  // if we got this far, show the modal
		  showCallModal(data);
		  modalOpen = true;
		  resolve(1);		// Vrike contact

		},
		"json"
	  )
	  .fail(function() {
		// on error → still notify back end
		$.post('<?php echo admin_url("call_logs/add_call_notification"); ?>', { data: data }, function() {
			  alert("Προέκυψε σφάλμα, προσπαθήστε ξανά σε λίγο");
			  resolve(0);
			  return;
		  },
		  "json"
		).fail(function() {});
	  });
	  });		// Telos promise
  }
  //***************************************************************YPARXOUN IDI TELOS
  
  $(window).on('load', function(){
	  $(document).on('click', '.register-call-btn', function(e){
		e.preventDefault();
		e.stopImmediatePropagation();

		let $btn = $(this);
		let base64Data = $(this).data('unreg');
		if (!base64Data) {
		  console.warn('No data-unreg attribute found on button.');
		  return;
		}

		try {
		  let data = JSON.parse(atob(base64Data));
		  if (typeof runQueue !== 'function') {
			console.error('runQueue is still undefined');
			return;
		  }
		  //runQueue(data);
		  //$btn.closest('tr').remove();
		  runQueue(data).then(found => {
			console.log("Found contact:", found);
			if(found === 1) {
				// Remove row only if contact was found
				let $tr = $btn.closest('tr');
				let $tbody = $tr.parent();
				$tr.remove();
				
				if ($tbody.children('tr').length === 0) {
					$tbody.append('<tr><td colspan="4" class="text-center">Δεν βρέθηκαν κλήσεις</td></tr>');
				}
			}
		  });
			  
		  /*let $tr = $btn.closest('tr');
		  let $tbody = $tr.parent(); // usually the <tbody>
		  $tr.remove();
		  
		  if ($tbody.children('tr').length === 0) {
			$tbody.append(
			  '<tr>' +
				'<td colspan="4" class="text-center">Δεν βρέθηκαν κλήσεις</td>' +
			  '</tr>'
			);
		  }*/
		  
		  
		} catch (err) {
		  console.error('Failed to decode or parse data-unreg payload:', err);
		}
	  });
	  
	  let attributeStaffFilter = "<?php echo $hiddenAttrStaffFilter ?>";
	  if (attributeStaffFilter == "hidden") {
		$('#staffFilter').closest('.form-group').hide();
	  }
   });
</script>