<?php
defined('BASEPATH') or exit('No direct script access allowed');
/*
 Module Name: Call Logs
 Description: Adds a "Logs" tab to client profiles
 Version: 1.0.0
 Requires at least: 2.2.0
*/

register_language_files('call_logs', ['call_logs']);

register_activation_hook('call_logs', 'call_logs_activate');
function call_logs_activate()
{
    /*$CI = &get_instance();
    $CI->load->dbforge();
    if (! $CI->db->table_exists('tblcall_logs')) {
        $CI->db->query(
            "CREATE TABLE `tblcall_logs` (
                `id` int NOT NULL AUTO_INCREMENT,
                `client_id` int NOT NULL,
                `call_time` datetime NOT NULL,
                `notes` text,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        );
    }*/
}

hooks()->add_action('admin_init', 'call_logs_init');
function call_logs_init()
{
    $CI = &get_instance();

    //if (has_permission('call_logs','','view')) {
        $CI->app_menu->add_sidebar_menu_item('call_logs', [
            'name'     => _l('call_logs'),
            'href'     => admin_url('call_logs'),
            'icon'     => 'fa fa-phone',
            'position' => 25,
        ]);
    //}
	
	
	//if (staff_can('view', 'my_module_name')) {
       // Allow access to the view feature
    //}
	$capabilities = [];
	$capabilities['capabilities'] = [
		'view' => "View",
	];
	register_staff_capabilities('call_logs', $capabilities, 'Call Logs');
	register_staff_capabilities('call_logs_recordings', $capabilities, 'Call Logs Recordings');
}

hooks()->add_filter('customer_profile_tabs','call_logs_tab',50,1);
function call_logs_tab($tabs)
{
    $CI        = &get_instance();
    $client_id = $CI->uri->segment(4);

	//if (has_permission('call_logs','','view')) {
		if (is_numeric($client_id)) {
			$tabs['call_logs'] = [
				'slug'     => 'call_logs',
				'name'     => _l('call_logs'),
				'view' 	   => 'call_logs/client_logs',
				'icon'     => 'fa fa-phone',
				'position' => count($tabs) + 1,
			];
		}
	//}

    return $tabs;
}

// **Intercept** the built-in “?group=call_logs” request and **redirect** to your controller
hooks()->add_action('admin_init', 'call_logs_profile_redirect', 5, 0);
function call_logs_profile_redirect()
{
    $CI = &get_instance();

    // URL is /admin/clients/client/{id}?group=call_logs
    if ($CI->uri->segment(1) === 'admin'
        && $CI->uri->segment(2) === 'clients'
        && $CI->uri->segment(3) === 'client'
        && is_numeric($CI->uri->segment(4))
        && $CI->input->get('group') === 'call_logs'
    ) {
        $client_id = $CI->uri->segment(4);
        // Send them to your module
        redirect(admin_url('call_logs/client/' . $client_id), 'location');
        exit;
    }
}

hooks()->add_action('lead_modal_profile_bottom','add_lead_calllogs_tab');
function add_lead_calllogs_tab($lead_id)
{
	/*if (!has_permission('call_logs','','view')) {
		return;
	}*/
	
	if ((int)$lead_id <= 0) {
        return;
    }

    // 1) Define the columns exactly as you'd use in render_datatable()
    $columns = [
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

    // 2) Capture the HTML output of render_datatable()
    ob_start();
    render_datatable($columns, 'lead-call-logs');
    $table_html = ob_get_clean();
	
	/*$filter_html = '
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
	</div>';*/
	$filter_html = '';

	$full_html = $filter_html . $table_html;

    // 3) JSON-encode the table HTML for safe insertion into JS
    //$table_js = json_encode($full_html);
	$table_js = json_encode($full_html, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
	
	
	echo '
	<!-- Grouped Call Logs Modal -->
    <div id="callLogsGroupModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="callLogsGroupModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-scrollable"
           style="max-width:90%; width:90%;"><!-- override width -->
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="callLogsGroupModalLabel">' . _l('call_logs_group_details') . '</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="' . _l('close') . '">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="table-responsive">
			  <!-- dynamically populated -->
		    </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">' . _l('close') . '</button>
          </div>
        </div>
      </div>
    </div>';
	// 4) Emit scripts + modal markup
    echo '
	<script>
		$(document).on("shown.bs.modal", ".lead-modal", function () {
			var admin_url = "' . admin_url() . '";
			
			if (!$("#callLogsGroupModal").parent().is("body")) {
				$("#callLogsGroupModal").appendTo("body");
			}
			
			
		  var $m = $(this);
		  var lid = $m.find("input[name=\'leadid\']").val();

		  // Avoid duplicate injection
		  if ($m.find(\'#tab_call_logs_\' + lid).length) return;

		  console.log("📋 Running Call Logs tab injection for lead #" + lid);

		  var tableHtml = ' . $table_js . ';

		  $m.find(".nav-tabs-horizontal").append(
			\'<li role="presentation">\' +
			  \'<a href="#tab_call_logs_\' + lid + \'" aria-controls="tab_call_logs_\' + lid + \'" role="tab" data-toggle="tab">\' +
				\'<i class="fa fa-phone menu-icon"></i> Call Logs\' +
			  \'</a>\' +
			\'</li>\'
		  );

		  $m.find(".tab-content").append(
			\'<div role="tabpanel" class="tab-pane" id="tab_call_logs_\' + lid + \'">\' +
			  tableHtml +
			\'</div>\'
		  );
		  
		  // GAMW TA AUTAKIA
		  setTimeout(function() {
			// 1. Trigger window resize to recalculate tabs
			$(window).trigger(\'resize\');
			
			// 2. Reinitialize Bootstrap tabs
			$m.find(\'.nav-tabs-horizontal a[data-toggle="tab"]\').tab();
			
			// 3. Force redraw of tab container
			var $tabs = $m.find(\'.nav-tabs-horizontal\');
			$tabs.hide().show(0);
			
			// 4. Recalculate scrolling (Perfex-specific fix)
			if (typeof initTabs === \'function\') {
				initTabs();
			}
			
			// 5. Check if scrolling is needed and update controls
			if ($tabs[0].scrollWidth > $tabs.innerWidth()) {
				$tabs.addClass(\'overflowing\');
			} else {
				$tabs.removeClass(\'overflowing\');
			}
			
			console.log("🔄 Tab navigation recalculated");
		  }, 100);
		  //

		  $(document).on("shown.bs.tab", \'a[href="#tab_call_logs_\' + lid + \'"]\', function(){
			var selector = ".table-lead-call-logs";
			if (! $.fn.DataTable.isDataTable(selector)) {
			  initDataTable(
				selector,
				admin_url + "call_logs/lead/" + lid,
				[], [], undefined,
				[0,"desc"]
			  );
			}
		  });

		  // Re-bind view-group modal handler
		  $("body").on("click", ".view-group", function(){
			var group = $(this).data("group");
			if (!Array.isArray(group) || !group.length) {
			  alert("' . _l('no_additional_logs') . '");
			  return;
			}
			var html = \'<table class="table table-striped">\'
			  + \'<thead><tr>\'
			  + \'<th>' . "Χρόνος Κλήσης"               . '</th>\'
			  + \'<th>' . "ID Κλήσης"                 . '</th>\'
			  + \'<th>' . "Από"                    . '</th>\'
			  + \'<th>' . "Πρός"                      . '</th>\'
			  + \'<th>' . "Κατεύθυνση"               . '</th>\'
			  + \'<th>' . "Κατάσταση"                  . '</th>\'
			  + \'<th>' . "Κουδούνισμα"                 . '</th>\'
			  + \'<th>' . "Ομιλία"                 . '</th>\'
			  + \'<th>' . "Κόστος"                    . '</th>\'
			  + \'<th>' . "Λεπτομέρειες"   . '</th>\'
			  + \'<th>Ηχογράφηση</th>\'
			  + \'</tr></thead><tbody>\';

			group.forEach(function(row){
			  html += \'<tr>\'
				+ \'<td>\' + row.call_time             + \'</td>\'
				+ \'<td>\' + row.call_id               + \'</td>\'
				+ \'<td>\' + row.from                  + \'</td>\'
				+ \'<td>\' + row.to                    + \'</td>\'
				+ \'<td>\' + row.direction             + \'</td>\'
				+ \'<td>\' + row.status                + \'</td>\'
				+ \'<td>\' + row.ringing               + \'</td>\'
				+ \'<td>\' + row.talking               + \'</td>\'
				+ \'<td>\' + row.cost                  + \'</td>\'
				+ \'<td>\' + row.call_activity_details + \'</td>\'
				+ \'<td>\' + row.recording             + \'</td>\'
				+ \'</tr>\';
			});

			html += \'</tbody></table>\';

			$("#callLogsGroupModal .table-responsive").html(html);
			$("#callLogsGroupModal").modal("show");
		  });
		});
	</script>';
	
	/*echo '
	<script>
		$(document).ready(function(){
		  if (!$("#callLogsGroupModal").parent().is("body")) {
			  $("#callLogsGroupModal").appendTo("body");
		  }
		});
	</script>
	';*/
}

hooks()->add_action('app_admin_footer','register_call_logs_pusher');
function register_call_logs_pusher()
{
	$CI = &get_instance();

    // 1) Make sure a staff user is logged in
    if (! $CI->session->has_userdata('staff_user_id')) {
        return;
    }

    // 2) Load the staff record and grab their extension
    $CI->load->model('staff_model');
    $staff_id = $CI->session->userdata('staff_user_id');
    $staff    = $CI->staff_model->get($staff_id);
    $ext      = isset($staff->extension) ? $staff->extension : null;
    if (empty($ext)) {
        return;
    }

    // 3) Load your pusher.php config (no grouping)
    //    so that config->item('pusher_app_key') works
    $CI->config->load('pusher', false);
    $key     = $CI->config->item('pusher_app_key');
    $cluster = $CI->config->item('pusher_cluster');
    if (empty($key) || empty($cluster)) {
        return;
    }
	$CI = &get_instance();
	$csrf = $CI->security->get_csrf_hash();
	$csrf_name = $CI->security->get_csrf_token_name(); 
	echo '<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />';
	echo '<script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>';
	echo '<script>
$(function(){
	var bc = new BroadcastChannel("call_modal_channel");
	var skipBroadcast = false;
	
	var staff_ext = ' . json_encode($ext) . ';
	var pusher = new Pusher(' . json_encode($key) . ', {
		cluster: ' . json_encode($cluster) . '
	});
	var channel = pusher.subscribe("call-channel-" + staff_ext);

	var queue = [];
	var modalOpen = false;
	
	bc.onmessage = function(e){
		if (e.data === "close_call_modal") {
			disableUnsavedPrompt();
			console.log("Broadcast close");
			skipBroadcast = true;
			$("#callDetailsModal").modal("hide");
			modalOpen = false;
			$("#callDetailsModal").one("hidden.bs.modal", function(){
				skipBroadcast = false;
			});
		}else if(e.data === "edit_call_modal"){
			var nextData = queue.shift();
			console.log("Broadcast open");
			if(nextData){
				modalOpen = false;
				skipBroadcast = true;
				runQueue(nextData);
				skipBroadcast = false;
			}
		}
	};
	
	function showCallModal(data){
		$("#callDetailsCallTime").html(data.call_time);
		$("#callDetailsCallTalking").html(data.talking);
		$("#callDetailsCallPhone").html(data.phone);
		$("#callDetailsIds").val(JSON.stringify(data.ids));
		
		$("#callDetailsModal").modal("show");
		modalOpen = true;
		console.log("Open modal");
	}
	
	function runQueue(data){
		if(modalOpen){
			console.log("Event queued by function");
			queue.push(data);
			return;
		}
			
		$("#mainSelect").val("0").trigger("change");
		$("#allclientsSelect").val("0").trigger("change");
		$("#typeSelect").val("0").trigger("change");
		$("#noteTextarea").val("").trigger("change");

		// AJAX lookup + preselect logic
		$.post("'. admin_url('call_logs/get_phone_matches') .'", { phone: data.phone }, function(res) {
			var contacts = res.contacts || [], leads = res.leads || [];
			var clientsFound  = res.clients_found || [];

			$("#mainSelect").empty().append(\'<option value="0" selected>---</option>\');
			
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
				$("#mainSelect").append(\'<option value="-1">Άλλος</option>\');
				$("#mainSelect").val(contacts[0].userid).trigger("change");
				$("#client_or_lead").val("client");
				$("#contactId").val(contacts[0].id);
				$("#lm_meta_box").hide();
			} else if (leads.length > 0 && contacts.length === 0) {
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
			} else{
				$.post("'. admin_url('call_logs/add_call_notification') .'", { data: data }, function(res) {
				}, "json").fail(function(){
				});
				return;
			}
			showCallModal(data);
			modalOpen = true;
		}, "json").fail(function(){	//ERROROROROR
			$.post("'. admin_url('call_logs/add_call_notification') .'", { data: data }, function(res) {
			}, "json").fail(function(){
			});
			return;
		});
	}
	
	channel.bind("call-ended-event", function(data) {
		/*if(modalOpen){
			console.log("Event queued by event");
			queue.push(data);
			return;
		}*/

		runQueue(data);
	});
	channel.bind("pusher:subscription_succeeded", function() {
		console.log("✅ Subscribed to", channel.name);
	});
	channel.bind("pusher:subscription_error", (error) => {
		console.error("❌ Subscription failed:", error);
	});
	pusher.connection.bind("connected", function () {
	  console.log("pusher connection ola kala");
	});
	pusher.connection.bind("error", function (error) {
	  console.error("pusher connection error", error);
	});
	
	var $modal = $("#callDetailsModal");
    // load Select2 JS
    $.getScript("https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js", function(){
        $("#mainSelect, #allclientsSelect").select2({
            placeholder: "' . _l('please_select') . '",
            allowClear: true,
            width: "100%",
            dropdownParent: $modal
        });
    });
	
	$("#mainSelect").on("change", function(){
		if ($(this).val() === "-1") {
		  $("#allclientsDiv").show();

		} else {
		  $("#allclientsDiv").hide();
		}
	});
	
	function disableUnsavedPrompt() {
		window.onbeforeunload = null;
		$(window).off("beforeunload");
	}
	
	var $modal = $("#callDetailsModal");
    var $form  = $modal.find("form");

    // 3. Your existing AJAX submit
    $form.on("submit", function(e){
        e.preventDefault();
        var url  = $form.attr("action");
        var data = $form.serialize();

        $.ajax({
            url: url,
            method: "POST",
            data: data,
            dataType: "json"
        }).done(function(res){
            if (res.success) {
				disableUnsavedPrompt();
              $modal.modal("hide");
              modalOpen = false;
			  
			  var responseUrl = res.response_code;
              
			  $modal.one(\'hidden.bs.modal\', function() {
				  if (responseUrl && responseUrl !== "0" && responseUrl !== 0) {
						var urlToOpen = responseUrl;
						if (urlToOpen.indexOf("http") !== 0) {
							if (urlToOpen.charAt(0) === "/") {
								urlToOpen = window.location.origin + urlToOpen;
							} else {
								urlToOpen = window.location.origin + "/" + urlToOpen;
							}
						}
						var newWin = window.open(urlToOpen, "_blank");
				  }
				  
				  if(queue.length > 0){
					let dataItemQueue = queue.shift();
					runQueue(dataItemQueue);
					console.log("Call to Broadcast EDIT");
					bc.postMessage("edit_call_modal");
				  }else{
					console.log("Call to Broadcast CLOSE");
					bc.postMessage("close_call_modal");
				  }
			  });
          } else {
            alert("Error saving call log");
          }
        }).fail(function(xhr){
          	alert("Request failed: " + xhr.statusText);
        });
    });

	$(document).on(\'click\', \'a\', function(e) {
		let href = $(this).attr(\'href\');
		
		// Only run if href is defined and contains "#call-modal"
		if (href && href.includes(\'#call-modal\')) {
			e.preventDefault();
			e.stopImmediatePropagation();
			
			try {
				let base64Data = href.split(\'#call-modal/\')[1];

				if (!base64Data) {
					console.warn(\'No base64 data found in hash.\');
					return;
				}

				let jsonString = atob(base64Data);

				let data = JSON.parse(jsonString);
				
				runQueue(data);
				//showCallModal(data);
			} catch (err) {
				console.error(\'Failed to decode or parse call-modal data:\', err);
			}
		}
	});

});
    </script>';

    $modal_path = APPPATH . '../modules/call_logs/views/call_details_modal.php';
    if (is_file($modal_path)) {
        // load->file() will execute the PHP and return its output
        echo $CI->load->file($modal_path, true);
    }
}