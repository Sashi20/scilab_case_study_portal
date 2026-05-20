<?php

namespace Drupal\scilab_case_study\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Database\Database;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Datetime\DrupalDateTime;

class ScilabCaseStudyProposalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scilab_case_study_proposal_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $no_js_use = NULL) {
    $user = \Drupal::currentUser();
    $service = \Drupal::service('case_study_global');
    /************************ start approve book details ************************/
    if ($user->isAnonymous()) {
      $msg = \Drupal::messenger()->addError(t('It is mandatory to ' . 
    \Drupal\Core\Link::fromTextAndUrl('login', \Drupal\Core\Url::fromRoute('user.page'))->toString() . 
    ' on this website to access the proposal form. If you are a new user, please create an account first.')
  );

  // Redirect to the login page
    $response = new RedirectResponse(Url::fromRoute('user.page')->toString());

  $response->send();
  
  // Return the error message (optional)
  return $msg;
    } //$user->uid == 0
    $query = \Drupal::database()->select('case_study_proposal');
    $query->fields('case_study_proposal');
    $query->condition('uid', $user->id());
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    if ($proposal_data) {
      if ($proposal_data->approval_status == 0 || $proposal_data->approval_status == 1) {
        $msg = \Drupal::messenger()->addError(t('We have already received your proposal.'));
          // Create a redirect response to the front page
  $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
  
  // Send the redirect response
  $response->send();

        return $msg;
        
      } //$proposal_data->approval_status == 0 || $proposal_data->approval_status == 1
    } //$proposal_data
    $form['#attributes'] = ['enctype' => "multipart/form-data"];

    $form['name_title'] = [
      '#type' => 'select',
      '#title' => t('Title'),
      '#options' => [
        'Dr' => 'Dr',
        'Prof' => 'Prof',
        'Mr' => 'Mr',
        'Ms' => 'Ms',
      ],
      '#required' => TRUE,
    ];
    $form['contributor_name'] = [
      '#type' => 'textfield',
      '#title' => t('Name of the contributor'),
      //'#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your full name.....')
        ],
      '#maxlength' => 250,
      '#required' => TRUE,
    ];
    $form['contributor_email_id'] = [
      '#type' => 'textfield',
      '#title' => t('Email'),
      //'#size' => 30,
      '#value' => $user->getEmail(),
      '#disabled' => TRUE,
    ];
    $form['contributor_contact_no'] = [
      '#type' => 'textfield',
      '#title' => t('Contact No.'),
      //'#size' => 10,
      '#attributes' => [
        'placeholder' => t('Enter your contact number')
        ],
      '#maxlength' => 10,
      '#required' => TRUE,
    ];
    $form['university'] = [
      '#type' => 'textfield',
      '#title' => t('University'),
      //'#size' => 80,
      '#maxlength' => 200,
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'Insert full name of your university.... '
        ],
    ];
    $form['institute'] = [
      '#type' => 'textfield',
      '#title' => t('Institute'),
      //'#size' => 80,
      '#maxlength' => 200,
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'Insert full name of your institute.... '
        ],
    ];
    $form['how_did_you_know_about_project'] = [
      '#type' => 'select',
      '#title' => t('How did you come to know about the Case Study Project?'),
      '#options' => [
        'Poster' => 'Poster',
        'Website' => 'Website',
        'Email' => 'Email',
        'Others' => 'Others',
      ],
      '#required' => TRUE,
    ];
    $form['others_how_did_you_know_about_project'] = [
      '#type' => 'textfield',
      '#title' => t('If Other, please specify'),
      '#maxlength' => 50,
      '#description' => t('<span style="color:red">Maximum character limit is 50</span>'),
      '#states' => [
        'visible' => [
          ':input[name="how_did_you_know_about_project"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['faculty_name'] = [
      '#type' => 'textfield',
      '#title' => t('Name of the Faculty Member of your Institution, if any, who helped you with this Case Study Project'),
      //'#size' => 50,
      '#maxlength' => 50,
      '#validated' => TRUE,
      '#description' => t('<span style="color:red">Maximum character limit is 50</span>'),
    ];
    $form['faculty_department'] = [
      '#type' => 'textfield',
      '#title' => t('Department of the Faculty Member of your Institution, if any, who helped you with this Case Study Project'),
      //'#size' => 50,
      '#maxlength' => 50,
      '#validated' => TRUE,
      '#description' => t('<span style="color:red">Maximum character limit is 50</span>'),
    ];
    $form['faculty_email'] = [
      '#type' => 'textfield',
      '#title' => t('Email id of the Faculty Member of your Institution, if any, who helped you with this Case Study Project'),
      //'#size' => 100,
      '#maxlength' => 255,
      '#validated' => TRUE,
      '#description' => t('<span style="color:red">Maximum character limit is 255</span>'),
    ];
    $form['country'] = [
      '#type' => 'select',
      '#title' => t('Country'),
      '#options' => [
        'India' => 'India',
        'Others' => 'Others',
      ],
      '#required' => TRUE,
      '#tree' => TRUE,
      '#validated' => TRUE,
    ];
    $form['other_country'] = [
      '#type' => 'textfield',
      '#title' => t('Other than India'),
      //'#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your country name')
        ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['other_state'] = [
      '#type' => 'textfield',
      '#title' => t('State other than India'),
      //'#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your state/region name')
        ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['other_city'] = [
      '#type' => 'textfield',
      '#title' => t('City other than India'),
      //'#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your city name')
        ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['all_state'] = [
      '#type' => 'select',
      '#title' => t('State'),
      '#options' => $service->_cs_list_of_states(),
      '#validated' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India'
            ]
          ]
        ],
    ];
    $form['city'] = [
      '#type' => 'select',
      '#title' => t('City'),
      '#options' => $service->_cs_list_of_cities(),
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India'
            ]
          ]
        ],
    ];
    $form['pincode'] = [
      '#type' => 'textfield',
      '#title' => t('Pincode'),
      //'#size' => 6,
      '#maxlength' => 6,
    ];
    /***************************************************************************/
    $form['hr'] = [
      '#type' => 'item',
      '#markup' => '<hr>',
    ];

    $form['project_title'] = [
      '#type' => 'textfield',
      '#title' => t('Project Title'),
      //'#size' => 100,
      '#maxlength' => 250,
      '#description' => t('Maximum character limit is 250'),
      '#required' => TRUE,
      '#validated' => TRUE,
    ];
    $form['operating_system'] = [
      '#type' => 'textfield',
      '#title' => t('Operating System version used'),
      '#required' => TRUE,
      //'#size' => 30,
      '#maxlength' => 50,
    ];
    $form['scilab_version'] = [
      '#type' => 'textfield',
      '#title' => t('Scilab version used'),
      '#maxlength' => 20,
      //'#options' => $version_options,
      '#attributes' => ['placeholder' => t('Format: Scilab x.x.x')],
      '#description' => t('<span style="color:red;">Note: Use Scilab 6.0.0 and above. </span><span>Format: Scilab x.x.x</span>'),
      '#required' => TRUE,
    ];
    $form['abstract_file'] = [
      '#type' => 'fieldset',
      '#title' => t('Submit an Abstract'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    
  $form['abstract_file']['abstract_file_path'] = [
  '#type' => 'file',
  //'#size' => 48,
  '#description' => [
    '#type' => 'inline_template',
    '#template' => '<span style="color:red;">{{ description }}</span><br /><span style="color:red;">{{ allowed_extensions }}</span>',
    '#context' => [
      'description' => $this->t('Upload filenames with allowed extensions only. No spaces or any special characters allowed in filename.'),
      'allowed_extensions' => $this->t('Allowed file extensions: @extensions', [
        '@extensions' => \Drupal::config('scilab_case_study.settings')->get('resource_upload_extensions')  ?? '',
      ]),
    ],
  ],
];

    $form['date_of_proposal'] = [
  '#type' => 'date',
  '#title' => $this->t('Date of Proposal'),
  '#default_value' => (new DrupalDateTime())->format('Y-m-d'), // Format as 'Y-m-d'
  '#date_format' => 'd-M-Y', // Display format (for the user)
  '#disabled' => TRUE,
  '#date_label_position' => '',
];
    $form['expected_date_of_completion'] = [
  '#type' => 'date',
  '#title' => $this->t('Expected Date of Completion'),
  '#date_label_position' => 'within',
  '#description' => '',
  //'#default_value' => DrupalDateTime::createFromTimestamp(time()), // Default to current date/time
  '#date_date_format' => 'd-M-Y',
  '#date_time_format' => '', // Leave empty for date-only
  '#required' => TRUE,
  '#date_increment' => 1,
  '#date_year_range' => '+0:+1',
  '#attributes' => [
    'min' => date('Y-m-d', strtotime('+1 day')), // Minimum: tomorrow
    'max' => date('Y-m-d', strtotime('+45 days')), // Maximum: 45 days from now
  ],
];
    $form['term_condition'] = [
      '#type' => 'checkboxes',
      '#title' => t('Terms And Conditions'),
      '#options' => [
        'status' => t('<a href="/case-study-project/term-and-conditions" target="_blank">I agree to the Terms and Conditions</a>')
        ],
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $service = \Drupal::service('case_study_global');
    // if ($form_state->getValue(['term_condition']) == '1') {
    //   $form_state->setErrorByName('term_condition', t('Please check the terms and conditions'));
    //   // $form_state['values']['country'] = $form_state['values']['other_country'];
    // } //$form_state['values']['term_condition'] == '1'
    if ($form_state->getValue(['country']) == 'Others') {
      if ($form_state->getValue(['other_country']) == '') {
        $form_state->setErrorByName('other_country', t('Enter country name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['other_country'] == ''
      else {
        $form_state->setValue(['country'], $form_state->getValue([
          'other_country'
          ]));
      }
      if ($form_state->getValue(['other_state']) == '') {
        $form_state->setErrorByName('other_state', t('Enter state name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['other_state'] == ''
      else {
        $form_state->setValue(['all_state'], $form_state->getValue(['other_state']));
      }
      if ($form_state->getValue(['other_city']) == '') {
        $form_state->setErrorByName('other_city', t('Enter city name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['other_city'] == ''
      else {
        $form_state->setValue(['city'], $form_state->getValue(['other_city']));
      }
    } //$form_state['values']['country'] == 'Others'
    else {
      if ($form_state->getValue(['country']) == '') {
        $form_state->setErrorByName('country', t('Select country name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['country'] == ''
      if ($form_state->getValue(['all_state']) == '') {
        $form_state->setErrorByName('all_state', t('Select state name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['all_state'] == ''
      if ($form_state->getValue(['city']) == '') {
        $form_state->setErrorByName('city', t('Select city name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['city'] == ''
    }

    if ($form_state->getValue(['how_did_you_know_about_project']) == 'Others') {
      if ($form_state->getValue(['others_how_did_you_know_about_project']) == '') {
        $form_state->setErrorByName('others_how_did_you_know_about_project', t('Please enter how did you know about the project'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      } //$form_state['values']['other_country'] == ''
      else {
        $form_state->setValue(['how_did_you_know_about_project'], $form_state->getValue(['others_how_did_you_know_about_project']));
      }
    }

    if (isset($_FILES['files'])) {
      /* check if atleast one source or result file is uploaded */
      if (!($_FILES['files']['name']['abstract_file_path'])) {
        $form_state->setErrorByName('abstract_file_path', t('Please upload the abstract file'));
      }
      /* check for valid filename extensions */
      foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
        if ($file_name) {
          /* checking file type */
          $allowed_extensions_str = \Drupal::config('scilab_case_study.settings')->get('resource_upload_extensions');
          $allowed_extensions = explode(',', $allowed_extensions_str);
          $fnames = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
          $temp_extension = end($fnames);
          if (!in_array($temp_extension, $allowed_extensions)) {
            $form_state->setErrorByName($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
          }
          if ($_FILES['files']['size'][$file_form_name] <= 0) {
            $form_state->setErrorByName($file_form_name, t('File size cannot be zero.'));
          }
          /* check if valid file name */
          if (!$service->scilab_case_study_check_valid_filename($_FILES['files']['name'][$file_form_name])) {
            $form_state->setErrorByName($file_form_name, t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));
          }
        } //$file_name
      } //$_FILES['files']['name'] as $file_form_name => $file_name
    }
    return $form_state;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $service = \Drupal::service('case_study_global');
    $user = \Drupal::currentUser();
    $root_path = $service->scilab_case_study_path();
    if ($user->isAnonymous()) {
      $msg = \Drupal::messenger()->addError(t('It is mandatory to ' . 
    \Drupal\Core\Link::fromTextAndUrl('login', \Drupal\Core\Url::fromRoute('user.page'))->toString() . 
    ' on this website to access the proposal form. If you are a new user, please create an account first.')
  );

  // Redirect to the login page
    $response = new RedirectResponse(Url::fromRoute('user.page')->toString());

  $response->send();
  
  // Return the error message (optional)
  return $msg;
    }
    if ($form_state->getValue(['how_did_you_know_about_project']) == 'Others') {
      $how_did_you_know_about_project = $form_state->getValue(['others_how_did_you_know_about_project']);
    }
    else {
      $how_did_you_know_about_project = $form_state->getValue(['how_did_you_know_about_project']);
    }
    /* inserting the user proposal */
    $v = $form_state->getValues();
    $project_title = trim($v['project_title']);
    $proposar_name = $v['name_title'] . ' ' . $v['contributor_name'];
    $university = $v['university'];
    $directory_name = $service->_cs_dir_name($project_title, $proposar_name);
    $proposal_id = Database::getConnection()->insert('case_study_proposal')
  ->fields([
    'uid' => $user->id(),
    'approver_uid' => 0,
    'name_title' => $v['name_title'],
    'contributor_name' => trim($v['contributor_name']),
    'contact_no' => trim($v['contributor_contact_no']),
    'university' => trim($v['university']),
    'institute' => trim($v['institute']),
    'how_did_you_know_about_project' => trim($how_did_you_know_about_project),
    'faculty_name' => $v['faculty_name'],
    'faculty_department' => $v['faculty_department'],
    'faculty_email' => $v['faculty_email'],
    'city' => $v['city'],
    'pincode' => $v['pincode'],
    'state' => $v['all_state'],
    'country' => $v['country'],
    'project_title' => $project_title,
    'operating_system' => $v['operating_system'],
    'scilab_version' => $v['scilab_version'],
    'directory_name' => $directory_name,
    'approval_status' => 0,
    'is_completed' => 0,
    'dissapproval_reason' => NULL, // Use NULL directly, not as a string.
    'creation_date' => \Drupal::time()->getCurrentTime(),
    'expected_date_of_completion' => strtotime($v['expected_date_of_completion']),
    'approval_date' => 0,
  ])
  ->execute();
    $dest_path = $directory_name . '/';
    $dest_path1 = $root_path . $dest_path;
    //var_dump($dest_path1);die;	
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }
    /* uploading files */
    foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
      if ($file_name) {
        /* checking file type */
        //$file_type = 'S';
        if (file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
          \Drupal::messenger()->addError(t("Error uploading file. File !filename already exists.", [
            '!filename' => $_FILES['files']['name'][$file_form_name]
            ]));
          //unlink($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
        } //file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
			/* uploading file */
        if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
          $query = "UPDATE case_study_proposal SET samplefilepath = :samplefilepath WHERE id = :id";
          $args = [
            ":samplefilepath" => $dest_path . $_FILES['files']['name'][$file_form_name],
            ":id" => $proposal_id,
          ];

          $updateresult = \Drupal::database()->query($query, $args);
          //var_dump($args);die;

          \Drupal::messenger()->addStatus($file_name . ' uploaded successfully.');
        } //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
        else {
          \Drupal::messenger()->addError('Error uploading file : ' . $dest_path . '/' . $file_name);
        }
      } //$file_name
    } //$_FILES['files']['name'] as $file_form_name => $file_name
    if (!$proposal_id) {
      \Drupal::messenger()->addError(t('Error receiving your proposal. Please try again.'));
      return;
    } //!$proposal_id
    /* sending email */
    $email_to = $user->getEmail();
   
    $config = \Drupal::config('scilab_case_study.settings');

// Get the values with fallback to empty strings.
$form = $config->get('case_study_from_email') ?? '';
$bcc = $config->get('case_study_emails') ?? '';
$cc = $config->get('case_study_cc_emails') ?? '';

    $params['case_study_proposal_received']['result1'] = $proposal_id;
    $params['case_study_proposal_received']['user_id'] = $user->id();
    $params['case_study_proposal_received']['headers'] = [
      'From' => $form,
      'MIME-Version' => '1.0',
      'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      'Content-Transfer-Encoding' => '8Bit',
      'X-Mailer' => 'Drupal',
      'Cc' => $cc,
      'Bcc' => $bcc,
    ];

     $langcode = $user->getPreferredLangcode();
    if (!\Drupal::service('plugin.manager.mail')->mail('scilab_case_study', 'case_study_proposal_received', $email_to, $langcode, $params, $form, TRUE)) {
      \Drupal::messenger()->addError('Error sending email message.');
    }
    $msg = \Drupal::messenger()->addStatus(t('We have received your case study proposal. We will get back to you soon.'));
    $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
    // Send the redirect response
    $response->send();
    return $msg;
  }

}
