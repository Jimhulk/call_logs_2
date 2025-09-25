<?php defined('BASEPATH') or exit; ?>
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
<?php init_head(); // Perfex’s head + primary sidebar ?>

<div id="wrapper" class="customer_profile">
  <div class="content">
  
	<div class="md:tw-max-w-64 tw-w-full">
      <?php if (isset($client)) { ?>
        <h4 class="tw-text-lg tw-font-bold tw-text-neutral-800 tw-mt-0">
          <div class="tw-space-x-3 tw-flex tw-items-center">
            <span class="tw-truncate">
              #<?= $client->userid . ' ' . $client->company; ?>
            </span>
          </div>
        </h4>
      <?php } ?>
    </div>
  
    <div class="md:tw-flex md:tw-gap-6">
      <!-- ←—— Left column: the “Customers” profile tabs -->
      <div class="md:tw-max-w-64 tw-w-full">
        <?php if (isset($client)) { ?>
          <?php $this->load->view('admin/clients/tabs'); ?>
        <?php } ?>
      </div>
      
      <!-- ——→ Right column: YOUR Call Logs panel -->
      <div class="tw-mt-12 md:tw-mt-0 tw-w-full <?= isset($client) ? 'tw-max-w-6xl' : 'tw-mx-auto tw-max-w-4xl'; ?>">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="tw-mb-2"><?= _l('call_logs'); ?></h4>
			<div class="row tw-mb-2">
				<div class="col-md-3">
					<div class="form-group">
						<select id="periodFilter" class="selectpicker form-control" data-width="100%">
						  <option value="1">Σήμερα</option>
						  <option value="2">Τελευταίες 2 μέρες</option>
						  <option value="3">Τελευταίες 3 μέρες</option>
						  <option value="all">Όλες τις ημερομηνίες</option>
						</select>
					</div>
				</div>
			</div>
			
			<div class="row tw-mb-2">
				<div class="col-md-3">
					<div class="form-group">
						<select id="staffFilter" class="selectpicker form-control" data-width="100%">
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

<?php init_tail(); // Perfex’s footer + scripts ?>

<script>
  $(function(){
    // AJAX to the same URL (which your redirect hook will catch and forward)
    const callLogsTable = initDataTable('.table-call-logs', window.location.href, [], [], 'undefined', [0, 'desc']);
	
	callLogsTable.on('preXhr.dt', function(e, settings, data) {
		//console.log('Sending periodFilter:', $('#periodFilter').val());
		data.periodFilter = $('#periodFilter').val();
		data.staffFilter = $('#staffFilter').val();
	});
	
	$('#periodFilter').on('change', function() {
		//console.log('Filter changed to:', $(this).val());
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
	
	let attributeStaffFilter = "<?php echo $hiddenAttrStaffFilter ?>";
    if (attributeStaffFilter == "hidden") {
		$('#staffFilter').closest('.form-group').hide();
    }
  });
</script>