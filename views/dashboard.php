<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php 
/*if (!has_permission('call_logs','','view')) {
	access_denied('Call Logs');
    exit;
}*/
?>
<?php init_head(); ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div id="wrapper">
	<div class="content">
		
		<div class="row">
		  <div class="col-md-12">
			<h5 class="tw-mb-3 tw-text-lg tw-font-semibold">Remarks της ημέρας <a href="#" id="call_button" class="btn btn-primary">Τηλεφωνική Επικοινωνία</a>
			</h5>

			<div class="row">
			  <!-- LEFT: table -->
			  <div class="col-md-6">
				<div id="scroll-table-wrap" class="tw-w-full" style="height:520px; overflow:auto; border:1px solid rgba(0,0,0,0.08); border-radius:6px; background:#fff;">
				  <table class="tw-min-w-full">
					<thead class="tw-sticky tw-top-0" style="background:#f8fafc; z-index:10;">
					  <tr>
						<th class="tw-px-4 tw-py-2 tw-text-left tw-text-sm tw-font-medium">Όνομα</th>
						<th class="tw-px-4 tw-py-2 tw-text-left tw-text-sm tw-font-medium">Ημερομηνία</th>
						<th class="tw-px-4 tw-py-2 tw-text-left tw-text-sm tw-font-medium">Κάτι εξτρα</th>
					  </tr>
					</thead>
					<tbody>
					  <!-- static example row -->
					  <tr>
						<td class="tw-px-4 tw-py-2">Alice Johnson</td>
						<td class="tw-px-4 tw-py-2">2025-09-29 11:52</td>
						<td class="tw-px-4 tw-py-2">Placeholder</td>
					  </tr>

					  <?php
					  $CI = &get_instance();
					  foreach ($future_remarks as $rem) {
						  $lead_rem = $CI->db->select('*')->from('tblleads')->where('id', $rem['rel_id'])->get()->row_array();

						  $lead_id    = $lead_rem ? $lead_rem['id'] : 0;
						  $lead_name  = $lead_rem ? html_escape($lead_rem['name']) : '—';
						  $lead_email = $lead_rem && isset($lead_rem['email']) ? html_escape($lead_rem['email']) : '';
						  $lead_phone = $lead_rem && isset($lead_rem['phonenumber']) ? html_escape($lead_rem['phonenumber']) : '';
						  $follow_up  = !empty($rem['lm_follow_up_date']) ? html_escape($rem['lm_follow_up_date']) : '—';
						  $remark     = !empty($rem['remark']) ? html_escape($rem['remark']) : '—';
						  $lead_url   = admin_url('leads/index/' . $lead_id);

						  echo '<tr class="lead-row">';
						  echo '<td class="tw-px-4 tw-py-2">';
						  echo '<a href="#" class="lead-link"'
							  . ' data-id="'. $lead_id .'"'
							  . ' data-name="'. $lead_name .'"'
							  . ' data-email="'. $lead_email .'"'
							  . ' data-phone="'. $lead_phone .'"'
							  . ' data-followup="'. $follow_up .'"'
							  . ' data-remark="'. $remark .'"'
							  . ' data-href="'. $lead_url .'"'
							  . '>'
							  . $lead_name
							  . '</a>';
						  echo '</td>';
						  echo '<td class="tw-px-4 tw-py-2">'. $follow_up .'</td>';
						  echo '<td class="tw-px-4 tw-py-2">'. $remark .'</td>';
						  echo '</tr>';
					  }
					  ?>
					</tbody>
				  </table>
				</div>
			  </div>

			  <!-- RIGHT: lead card -->
			  <div class="col-md-6">
				<div id="lead-card" class="tw-w-full" style="height:520px; overflow:auto;">
				  <div class="tw-bg-white tw-rounded-lg tw-shadow-sm tw-border tw-border-gray-100 tw-p-4">
					<div id="lead-card-empty" class="tw-text-center tw-text-gray-500">
					  <p class="tw-mb-2">Επίλεξε ένα lead για λεπτομέρειες</p>
					  <p class="tw-text-sm">Κάνε κλικ στο όνομα αριστερά</p>
					</div>

					<div id="lead-card-content" style="display:none;">
					  <div class="tw-flex tw-justify-between tw-items-start">
						<h3 id="card-name" class="tw-text-xl tw-font-semibold tw-mb-1">Name Placeholder</h3>
						<div>
						  <a id="card-open-link" href="#" target="_blank" class="btn btn-sm btn-outline-primary">Άνοιγμα</a>
						</div>
					  </div>

					  <div class="tw-mt-3 tw-space-y-2">
						<div><span class="tw-font-medium tw-text-gray-700">Email:</span> <span id="card-email" class="tw-text-gray-600">email@...</span></div>
						<div><span class="tw-font-medium tw-text-gray-700">Phone:</span> <span id="card-phone" class="tw-text-gray-600">+30 ...</span></div>
						<div><span class="tw-font-medium tw-text-gray-700">Follow-up:</span> <span id="card-followup" class="tw-text-gray-600">—</span></div>
						<div><span class="tw-font-medium tw-text-gray-700">Remark:</span></div>
						<div id="card-remark" class="tw-text-gray-700 tw-py-2 tw-bg-gray-50 tw-rounded-sm">—</div>
					  </div>

					  <div class="tw-mt-4 tw-flex">
						<button id="card-call" type="button" class="btn btn-success btn-sm tw-mr-2">Call</button>
						<button id="card-edit" type="button" class="btn btn-secondary btn-sm tw-mr-2">Edit</button>
						<button id="card-notes" type="button" class="btn btn-light btn-sm" onclick="showNotes(this)" data-lead="">Notes</button>
					  </div>
					</div>
					
					<div class="tw-mt-4">
					  <div id="card-notes-area" style="display:none;">
						<div id="card-notes-list">
						
						  <!-- notes will be injected here -->
							  <!--div class="tw-border tw-border-gray-100 tw-rounded-sm tw-p-3 tw-mb-2">
								<div class="tw-flex tw-justify-between tw-items-start tw-mb-1">
								  <div class="tw-text-sm tw-font-medium tw-text-gray-800">author</div>
								  <div class="tw-text-xs tw-text-gray-500">date</div>
								</div>
								<p style="white-space:normal; overflow-wrap:anywhere; word-break:break-word;">DESCRIPTIONdescriptionDESCRIPTIONdescriptionDESCRIPTIONdescriptionDESCRIPTIONdescriptionDESCRIPTIONdescription</p>
							  </div-->
						  
						</div>
					  </div>
					</div>

				  </div>
				</div>
			  </div>
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
<?php
  $leads_from_remarks = json_encode($leads_from_remarks, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
  $future_remarks = json_encode($future_remarks, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
?>
<script>
	$(window).on('load', function(){
		$('#menu').remove();
		$('.hide-menu').first().remove();
		$('body').removeClass('show-sidebar').addClass('hide-sidebar');


	});
</script>

<script>
jQuery(function($){
	
  $('#scroll-table-wrap').on('click', '.lead-link', function(e){
	  
    // if user ctrl/cmd or middle-click, open actual lead page in new tab
    var openInNewTab = e.ctrlKey || e.metaKey || e.which === 2;
    var href = $(this).data('href') || '#';
    if (openInNewTab) {
      window.open(href, '_blank', 'noopener');
      return;
    }

    e.preventDefault();

    $('.lead-row').removeClass('tw-bg-blue-50 tw-bg-opacity-30');
    $(this).closest('tr').addClass('tw-bg-blue-50 tw-bg-opacity-30');

    // populate card from data-attrs
    var id = $(this).data('id');
    var name = $(this).data('name') || '—';
    var email = $(this).data('email') || '—';
    var phone = $(this).data('phone') || '—';
    var followup = $(this).data('followup') || '—';
    var remark = $(this).data('remark') || '—';

    $('#lead-card-empty').hide();
    $('#lead-card-content').show();

    $('#card-name').text(name);
    $('#card-email').text(email);
    $('#card-phone').text(phone);
    $('#card-followup').text(followup);
    $('#card-remark').text(remark);
    $('#card-open-link').attr('href', href);
	
	$('#card-notes').attr('data-lead', id);

  });
  
});

function showNotes(e) {
  if (e && e.preventDefault) e.preventDefault();

  var $btn  = $('#card-notes');
  var $area = $('#card-notes-area');
  if (!$area.length || !$btn.length) return;

  if ($area.is(':visible')) {
    $area.attr('aria-hidden','true').hide();
    $btn.attr('aria-expanded','false').removeClass('active');
	return;
  } else {
    $area.attr('aria-hidden','false').show();
    $btn.attr('aria-expanded','true').addClass('active');
	
	
	let leadId = $(e).attr('data-lead');
	let url = "<?php echo admin_url('call_logs/get_notes_for_lead') ?>";
	$.post(url, { lead_id: leadId }, function(res) {
		let all_notes = res.all_notes;
		let html = '';
		
		$('#card-notes-list').empty();
		
		let htmlNotes = '';
		all_notes.forEach(function(row){
			htmlNotes += '<div class="tw-border tw-border-gray-100 tw-rounded-sm tw-p-3 tw-mb-2">\
						<div class="tw-flex tw-justify-between tw-items-start tw-mb-1">\
						  <div class="tw-text-sm tw-font-medium tw-text-gray-800">'+row.rel_id+'</div>\
						  <div class="tw-text-xs tw-text-gray-500">'+row.dateadded+'</div>\
						</div>\
						<p style="white-space:normal; overflow-wrap:anywhere; word-break:break-word;">'+row.description+'</p>\
					  </div>';
		});
		
		$('#card-notes-list').html(htmlNotes);
		
	}, "json").fail(function(){
		console.log("MPOUTS");
	});
  }
}

</script>