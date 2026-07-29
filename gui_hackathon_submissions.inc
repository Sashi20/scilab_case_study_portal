<?php
//SGVH_TEAMNO
function gui_hackathon_final_submission(){
	 global $user;
    if ($user->uid == 0)
    {
        $msg = drupal_set_message(t('It is mandatory to log in on this website to upload your submission.'), 'error');
        drupal_goto('user/login', array('query' => drupal_get_destination()));
        return $msg;
    } //$user->uid == 0
    $today = date("Y-m-d H:i:s");
	//var_dump($today);die;
    //var_dump($today);die;
    $start_date = variable_get('gui_hackathon_start_date', ''); 
    $last_date = variable_get('gui_hackathon_last_date', '');
    $return_html = '';
    if($today < $start_date){
        $return_html .= '<p>We accept submissions between ' . $start_date . ' and ' . $last_date . '.</p>';
    }
    elseif($today > $last_date){
        $return_html .= '<p>Submission window is closed.</p>';
    }
    else{
        $submission_form = drupal_get_form("scilab_gui_hackathon_final_submission_form");
        $return_html .= drupal_render($submission_form);
    }
    return $return_html;
}
function scilab_gui_hackathon_final_submission_form($form, &$form_state, $no_js_use = FALSE)
{
	global $user;
	$query = db_select('gui_hackathon_registration');
	$query->fields('gui_hackathon_registration');
	$query->condition('email', $user->mail);
	$query->orderBy('id', 'DESC');
	$query->range(0, 1);
	$proposal_q = $query->execute();
	$proposal_data = $proposal_q->fetchObject();
	if (!$proposal_data)
	{
			drupal_set_message(t('You are not registered for the hackathon.'), 'status');
			drupal_goto('');
			return;
	} //$proposal_data
	$query = db_select('gui_hackathon_final_submission');
	$query->fields('gui_hackathon_final_submission');
	$query->condition('uid', $user->uid);
	$query->range(0, 1);
	$final_submission_q = $query->execute();
	$final_submission_data = $final_submission_q->fetchObject();
	$form['#attributes'] = array(
		'enctype' => "multipart/form-data"
	);
	if ($final_submission_data)
	{
		$disable_value = TRUE;
	} //$final_submission_data
	else
	{
		$disable_value = FALSE;
	} //$final_submission_data
	$form['participant_details'] = array(
		'#type' => 'fieldset',
		'#title' => t('Participant Details'),
		'#collapsible' => TRUE,
		'#collapsed' => FALSE
	);
	$form['participant_details']['contributor_email_id'] = array(
		'#type' => 'textfield',
		'#title' => t('Email'),
		'#size' => 30,
		'#value' => $user->mail,
		'#disabled' => TRUE
	);
	$form['participant_details']['contributor_name'] = array(
		'#type' => 'textfield',
		'#title' => t('Full Name'),
		'#size' => 100,
		'#default_value' => $final_submission_data->contributor_name ?? '',
		'#attributes' => array(
			'placeholder' => t('Enter your full name.....')
		),
		'#description' => t('Format: First Middle Last
This name will be printed on the certificate. No further changes will be allowed.'),
		'#maxlength' => 250,
		'#required' => TRUE,
		'#disabled' => $disable_value
	);
	$form['participant_details']['contact_no'] = array(
		'#type' => 'textfield',
		'#title' => t('Contact Number'),
		'#size' => 10,
		'#description' => t('Please enter a valid contact number without any country codes.'),
		'#default_value' => $final_submission_data->contact_no ?? '',
		'#maxlength' => 10,
		'#required' => TRUE,
		'#disabled' => $disable_value
	);

	
	$form['final_submission'] = array(
		'#type' => 'fieldset',
		'#title' => t('Final Submission'),
		'#collapsible' => FALSE,
		'#collapsed' => FALSE
	);
	$form['final_submission']['final_submission_title'] = array(
		'#type' => 'textfield',
		'#title' => t('Project title'),
		'#maxlength' => 150,
		'#default_value' => $final_submission_data->project_title ?? '',
		'#required' => TRUE,
		'#disabled' => $disable_value
	);
	$form['final_submission']['final_submission_description'] = array(
    '#type' => 'textarea',
    '#title' => t('Project Description'),
    '#minlength' => 80,  // Minimum number of characters required
    '#maxlength' => 500, // Maximum number of characters allowed
    '#required' => TRUE,
	'#default_value' => $final_submission_data->project_description ?? '',
    '#description' => t('Enter a description between 80 and 500 characters.'),
	);
	//if (!$final_submission_data->filename) {
        // /$existing_file = new stdClass();
        $existing_file = $final_submission_data->filename ?? "No file uploaded";
    //}
	$form['final_submission']['upload_file'] = array(
		'#type' => 'file',
		'#title' => t('Upload your final submission'),
		'#size' => 48,
		'#description' => t(t('<span style="color:red;">Current File:</span> ' . $existing_file . '<br />Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions: ') . variable_get('final_submission_extensions', '') . '</span>')
	);

	$form['submit'] = array(
		'#type' => 'submit',
		'#value' => t('Submit')
	);
	return $form;
}

function scilab_gui_hackathon_final_submission_form_validate($form, &$form_state)
{
if (isset($_FILES['files']))
	{
		/* check if atleast one source or result file is uploaded */
		if (!($_FILES['files']['name']['upload_file']))
			form_set_error('upload_file', t('Please upload the final submission file'));
		/* check for valid filename extensions */
		foreach ($_FILES['files']['name'] as $file_form_name => $file_name)
		{
			if ($file_name)
			{
				/* checking file type */
				$allowed_extensions_str = variable_get('final_submission_extensions', '');
				$allowed_extensions = explode(',', $allowed_extensions_str);
				$fnames = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
				$temp_extension = end($fnames);
				if (!in_array($temp_extension, $allowed_extensions))
					form_set_error($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
				if ($_FILES['files']['size'][$file_form_name] <= 0)
					form_set_error($file_form_name, t('File size cannot be zero.'));
				/* check if valid file name */
				// if (!scilab_case_study_check_valid_filename($_FILES['files']['name'][$file_form_name]))
				// 	form_set_error($file_form_name, t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));
			} //$file_name
		} //$_FILES['files']['name'] as $file_form_name => $file_name
	} 
	return $form_state;
}
function scilab_gui_hackathon_final_submission_form_submit($form, &$form_state)
{
	global $user;
	$root_path = gui_hackathon_submission_path();
	if (!$user->uid)
	{
		drupal_set_message('It is mandatory to login on this website to access the proposal form', 'error');
		return;
	}
	$query = db_select('gui_hackathon_registration');
	$query->fields('gui_hackathon_registration');
	$query->condition('email', $user->mail);
	$query->orderBy('id', 'DESC');
	$query->range(0, 1);
	$proposal_q = $query->execute();
	$proposal_data = $proposal_q->fetchObject();
	$query = db_select('gui_hackathon_final_submission');
	$query->fields('gui_hackathon_final_submission');
	$query->condition('uid', $user->uid);
	$query->range(0, 1);
	$final_submission_q = $query->execute();
	$final_submission_data = $final_submission_q->fetchObject();
	if(!$final_submission_data){
	$revision = 1; // Default for first submission
	/* inserting the user proposal */
	$v = $form_state["values"];
	$project_title = trim($v['final_submission_title']);
	$proposar_name = trim($v['contributor_name']);
	$result = "INSERT INTO gui_hackathon_final_submission
    (
    uid, 
    approver_uid,
    contributor_name,
    contact_no,
    project_title,
    project_description,
	revision,
	approval_status,
    is_completed, 
    creation_date,
    approval_date
    ) VALUES
    (
    :uid, 
    :approver_uid, 
    :contributor_name,
	:contact_no,
    :project_title,
    :project_description,
    :revision,
    :approval_status,
    :is_completed, 
    :creation_date, 
    :approval_date
    )";
	$args = array(
		":uid" => $user->uid,
		":approver_uid" => 0,
		":contributor_name" => trim($v['contributor_name']),
		":contact_no" => trim($v['contact_no']),
		":project_title" => $project_title,
		":project_description" => $v['final_submission_description'],
		":revision" => $revision,
		":approval_status" => 0,
		":is_completed" => 0,
		":creation_date" => time(),
		":approval_date" => 0
		);
	$submission_id = db_query($result, $args, array(
		'return' => Database::RETURN_INSERT_ID
	));
	}
	else{
		$revision = $final_submission_data->revision + 1; // Increment revision for resubmission
		$v = $form_state["values"];
		$submission_id = $final_submission_data->id;
		$query = "UPDATE gui_hackathon_final_submission SET project_description = :project_description WHERE id = :id";
				$args = array(
					":project_description" => $v['final_submission_description'],
					":id" => $submission_id
				);
				$updateresult = db_query($query, $args);
	}
	// if (!is_dir($root_path . $dest_path))
	// 	mkdir($root_path . $dest_path);
	/* uploading files */
	foreach ($_FILES['files']['name'] as $file_form_name => $file_name)
	{
		if($file_name)
		{
			$file_name = 'SGVH' . $proposal_data->id . '.zip';
		if($final_submission_data && $final_submission_data->filename){
			
			unlink($root_path . $file_name); // Delete the old file before uploading the new one
	move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $file_name);
				$query = "UPDATE gui_hackathon_final_submission SET filename = :filename, revision = :revision WHERE id = :id";
				$args = array(
					":filename" => $file_name,
					":revision" => $revision,
					":id" => $submission_id
				);
				$updateresult = db_query($query, $args);
				drupal_set_message(t("File updated successfully."), 'status');
		}
		else{
			//$file_name = 'SGVH' . $submission_id . '.zip';
			move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $file_name);
				$query = "UPDATE gui_hackathon_final_submission SET filename = :filename, revision = :revision WHERE id = :id";
				$args = array(
					":filename" => $file_name,
					":revision" => $revision,
					":id" => $submission_id
				);
				$updateresult = db_query($query, $args);
				drupal_set_message(t("File uploaded successfully."), 'status');
		}
		}
		//$file_name = 'SGVH' . $final_submission_data->id . '.zip';
		// if ($file_name)
		// {
			
		// 	/* checking file type */
		// 	//$file_type = 'S';
		// 	if (file_exists($root_path . $file_name))
		// 	{
		// 		move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $file_name);
		// 		$query = "UPDATE gui_hackathon_final_submission SET filename = :filename, revision = :revision WHERE id = :id";
		// 		$args = array(
		// 			":filename" => $file_name,
		// 			":revision" => $revision,
		// 			":id" => $submission_id
		// 		);
		// 		$updateresult = db_query($query, $args);
		// 		 drupal_set_message(t("File !filename replaced as it already exists", array('!filename' => $_FILES['files']['name'][$file_form_name])), 'error');
		// 		//unlink($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
		// 	} //file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
		// 	//file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
		// 	/* uploading file */
		// 	if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $file_name))
		// 	{
		// 		//unlink($root_path . $file_name);
		// 		$query = "UPDATE gui_hackathon_final_submission SET filename = :filename, revision = :revision WHERE id = :id";
		// 		$args = array(
		// 			":filename" => $file_name,
		// 			":revision" => $revision,
		// 			":id" => $submission_id
		// 		);
				
		// 		$updateresult = db_query($query, $args);
		// 		//var_dump($args);die;

		// 		drupal_set_message($file_name . ' uploaded successfully.', 'status');
		// 	} //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
			else
			{
				drupal_set_message('Error uploading file : ' . $dest_path . '/' . $file_name, 'error');
			}
		} //$file_name
	//} //$_FILES['files']['name'] as $file_form_name => $file_name
	if (!$submission_id)
	{
		drupal_set_message(t('Error receiving your submission. Please try again.'), 'error');
		return;
	} //!$submission_id
	/* sending email */
	$email_to = $user->mail;
	$form = variable_get('gui_hackathon_from_email', '');
	$bcc = variable_get('gui_hackathon_emails', '');
	$cc = variable_get('gui_hackathon_cc_emails', '');
	$params['gui_hackathon_final_submission']['submission_id'] = $submission_id;
	$params['gui_hackathon_final_submission']['user_id'] = $user->uid;
	$params['gui_hackathon_final_submission']['headers'] = array(
		'From' => $form,
		'MIME-Version' => '1.0',
		'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
		'Content-Transfer-Encoding' => '8Bit',
		'X-Mailer' => 'Drupal',
		'Cc' => $cc,
		'Bcc' => $bcc
	);
	if (!drupal_mail('scilab_case_study', 'gui_hackathon_final_submission', $email_to, user_preferred_language($user), $params, $form, TRUE))
		drupal_set_message('Error sending email message.', 'error');
	drupal_set_message(t('We have received your final submission. We will get back to you soon.'), 'status');
	drupal_goto('');
}
