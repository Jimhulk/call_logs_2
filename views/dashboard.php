<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php 
/*if (!has_permission('call_logs','','view')) {
	access_denied('Call Logs');
    exit;
}*/
?>
<?php init_head(); ?>

<?php $CI = &get_instance(); ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div id="wrapper">
	<div class="content">
		
		<div class="row">
		  <div class="col-md-12">
			<h5 class="tw-mb-3 tw-text-lg tw-font-semibold">Remarks της ημέρας <a href="#" id="call_button" class="btn btn-primary">Τηλεφωνική Επικοινωνία</a></h5>

			<div id="scroll-table-wrap" class="tw-w-full tw-max-w-xl" style="height:320px; overflow:auto; border:1px solid rgba(0,0,0,0.08); border-radius:6px; background:#fff;">
			  <table class="tw-min-w-full">
				<thead class="tw-sticky tw-top-0" style="background:#f8fafc; z-index:10;">
				  <tr>
					<th class="tw-px-4 tw-py-2 tw-text-left tw-text-sm tw-font-medium">Όνομα</th>
					<th class="tw-px-4 tw-py-2 tw-text-left tw-text-sm tw-font-medium">Ημερομηνία</th>
					<th class="tw-px-4 tw-py-2 tw-text-left tw-text-sm tw-font-medium">Κάτι εξτρα</th>
				  </tr>
				</thead>
				<tbody>
				  <!-- many rows to force overflow -->
				  <tr>
					<td class="tw-px-4 tw-py-2">Alice Johnson</td><td class="tw-px-4 tw-py-2">alice@example.com</td>
					<td class="tw-px-4 tw-py-2">Note A</td>
				  </tr>
				  <?php
					foreach($future_remarks as $rem){
						$lead_rem = $CI->db->select('*')->from('tblleads')->where('id', $rem['rel_id'])->get()->row_array(); 
						echo '<tr>
							<td class="tw-px-4 tw-py-2">'. $lead_rem['name'] .'</td>
							<td class="tw-px-4 tw-py-2">'. $rem['lm_follow_up_date'] .'</td>
							<td class="tw-px-4 tw-py-2">'. $rem['remark'] .'</td>';
					}
				  ?>
				</tbody>
			  </table>
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
		$('#side-menu').empty();
		
		console.log(<?php echo $future_remarks; ?>);
		
		let href = "";
		
		let leads_from_remarks = <?php echo $leads_from_remarks; ?>;
		
		let customHtml = '<li class="tw-mt-[63px] sm:tw-mt-0 -tw-mx-2 tw-overflow-hidden sm:tw-bg-neutral-900/50">\
            <div id="logo" class="tw-py-2 tw-px-2 tw-h-[63px] tw-flex tw-items-center">\
                <a href="https://hub2.mece.gr/admin/" class="logo img-responsive !tw-mt-0">\
        <img src="https://hub2.mece.gr/uploads/company/d7a25926d413ebc2e27e0906120b60f2.png" class="img-responsive" alt="Mece - Πρότυπα Κέντρα Διαμεσολάβησης">\
        </a>            </div>\
        </li>';
		
		customHtml += '<h4 class="tw-px-3">Δυνητικοί Πελάτες</h4>';
		
		customHtml += '<a href="#" id="telecomButton" class="btn btn-primary tw-mb-8 tw-ml-2 mright5 display-block">Τηλεφωνική Επικοινωνία</a>';
		
		//customHtml += '<ul class="nav metis-menu tw-mt-[15px] tw-max-h-[400px] tw-overflow-y-auto">';
		customHtml += '<ul class="nav metis-menu tw-mt-[15px]" style="max-height:600px; overflow-y:auto;">';
  
		leads_from_remarks.forEach((element) => {
			href = "<?php echo admin_url('leads/index/') ?>"+element.id;
			customHtml += `<li><a href=${href} target="_blank">${element.name}</a></li>`;
		});

		customHtml += '</ul>';

		$('#side-menu').html(customHtml);
		
		$('#menu').remove();
		$('.hide-menu').first().remove();
		$('body').removeClass('show-sidebar').addClass('hide-sidebar');


	});
</script>