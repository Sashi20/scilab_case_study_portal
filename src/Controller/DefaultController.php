<?php 

namespace Drupal\scilab_case_study\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Service;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\Markup;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Drupal\Component\Utility\Html;

/**
 * Default controller for the scilab_case_study module.
 */
class DefaultController extends ControllerBase {

  public function scilab_case_study_proposal_pending() {
    /* get pending proposals to be approved */
    $pending_rows = [];
    $query = \Drupal::database()->select('case_study_proposal');
    $query->fields('case_study_proposal');
    $query->condition('approval_status', 0);
    $query->orderBy('id', 'DESC');
    $pending_q = $query->execute();
    
    while ($pending_data = $pending_q->fetchObject()) {
      $approval_url = Link::fromTextAndUrl(
  $this->t('Approve'),
  Url::fromUri('internal:/case-study-project/manage-proposal/approve/' . $pending_data->id)
)->toString();
      $edit_url =  Link::fromTextAndUrl(
  $this->t('Edit'),
  Url::fromUri('internal:/case-study-project/manage-proposal/edit/' . $pending_data->id)
)->toString();
      $mainLink = t('@linkApprove | @linkReject', array('@linkApprove' => $approval_url, '@linkReject' => $edit_url));
      $pending_rows[$pending_data->id] = [
        date('d-m-Y', $pending_data->creation_date),
        Link::fromTextAndUrl($pending_data->contributor_name, Url::fromRoute('entity.user.canonical', ['user' => $pending_data->uid])),
        $pending_data->project_title,
        $mainLink
      ];

    } //$pending_data = $pending_q->fetchObject()
    /* check if there are any pending proposals */
    // if (!$pending_rows) {
    //   $msg = \Drupal::messenger()->addStatus(t('There are no pending proposals.'));
    //   return $msg;
    // } //!$pending_rows
     $pending_header = [
      'Date of Submission',
      'Student Name',
      'Title of the Project',
      'Action',
    ];
    $output =  [
      '#type' => 'table',
      '#header' => $pending_header,
      '#rows' => $pending_rows,
      '#empty' => 'no rows found',
    ];
    return $output;
  }

  public function scilab_case_study_proposal_all() {
  // Get pending proposals to be approved
  $proposal_rows = [];

  // Database query
  $query = Database::getConnection()->select('case_study_proposal', 'csp');
  $query->fields('csp');
  $query->orderBy('id', 'DESC');
  $proposal_q = $query->execute();

  foreach ($proposal_q as $proposal_data) {
    // Determine approval status
    $approval_status = $this->getApprovalStatus($proposal_data);

    // Format dates
    $actual_completion_date = $proposal_data->actual_completion_date == 0
      ? $this->t('Not Completed')
      : \Drupal::service('date.formatter')->format($proposal_data->actual_completion_date, 'custom', 'd-m-Y');

    $approval_date = $proposal_data->approval_date == 0
      ? $this->t('Not Approved')
      : \Drupal::service('date.formatter')->format($proposal_data->approval_date, 'custom', 'd-m-Y');

    // Format contributor name as a link
    $contributor_link = Link::fromTextAndUrl(
      $proposal_data->contributor_name,
      Url::fromUri('internal:/user/' . $proposal_data->uid)
    );

    // Format action links
    $status_link = Link::fromTextAndUrl(
      $this->t('Status'),
      Url::fromUri('internal:/case-study-project/manage-proposal/status/' . $proposal_data->id)
    )->toString();

    $edit_link = Link::fromTextAndUrl(
      $this->t('Edit'),
      Url::fromUri('internal:/case-study-project/manage-proposal/edit/' . $proposal_data->id)
    )->toString();
$action_links = t('@linkstatus | @linkedit', array('@linkstatus' => $status_link, '@linkedit' => $edit_link));
    // Build row
    $proposal_rows[] = [
      \Drupal::service('date.formatter')->format($proposal_data->creation_date, 'custom', 'd-m-Y'),
      $contributor_link->toString(),
      $proposal_data->project_title,
      $approval_date,
      $actual_completion_date,
      $approval_status,
      $action_links,
    ];
  }

  // Table header
  $proposal_header = [
    $this->t('Date of Submission'),
    $this->t('Student Name'),
    $this->t('Title of the case-study project'),
    $this->t('Date of Approval'),
    $this->t('Date of Project Completion'),
    $this->t('Status'),
    $this->t('Action'),
  ];

  // Build table
  $build = [
    '#type' => 'table',
    '#header' => $proposal_header,
    '#rows' => $proposal_rows,
    '#empty' => $this->t('No case study proposals found.'),
  ];

  return $build;
}

/**
 * Helper method to get approval status with action links.
 */
protected function getApprovalStatus($proposal_data) {
  switch ($proposal_data->approval_status) {
    case 0:
      return $this->t('Pending');

    case 1:
      $reminder_link = Link::fromTextAndUrl(
        $this->t('Send Reminder Mail'),
        Url::fromUri('internal:/case-study-project/manage-proposal/send-reminder-mail/' . $proposal_data->id)
      );
      return $this->t('Approved | @reminder_link', ['@reminder_link' => $reminder_link->toString()]);

    case 2:
      return $this->t('Disapproved');

    case 3:
      return $this->t('Completed');

    case 5:
      return $this->t('On Hold');

    default:
      return $this->t('Unknown');
  }
}

  public function scilab_case_study_proposal_edit_file_all() {
  // Get proposals that are not in status 0, 1, or 2
  $proposal_rows = [];

  // Database query
  $query = Database::getConnection()->select('case_study_proposal', 'csp');
  $query->fields('csp');
  $query->orderBy('id', 'DESC');
  $query->condition('approval_status', 3);
  $query->orderBy('approval_status', 'DESC');
  $proposal_q = $query->execute();

  foreach ($proposal_q as $proposal_data) {
    // Determine approval status
    $approval_status = $this->getApprovalStatus($proposal_data->approval_status);

    // Format dates
    $actual_completion_date = $proposal_data->actual_completion_date == 0
      ? $this->t('Not Completed')
      : \Drupal::service('date.formatter')->format($proposal_data->actual_completion_date, 'custom', 'd-m-Y');

    $approval_date = $proposal_data->approval_date == 0
      ? $this->t('Not Approved')
      : \Drupal::service('date.formatter')->format($proposal_data->approval_date, 'custom', 'd-m-Y');

    // Format contributor name as a link
    $contributor_link = Link::fromTextAndUrl(
      $proposal_data->contributor_name,
      Url::fromUri('internal:/user/' . $proposal_data->uid)
    );

    // Format edit link
    $edit_link = Link::fromTextAndUrl(
      $this->t('Edit'),
      Url::fromUri('internal:/case-study-project/abstract-code/edit-upload-files/' . $proposal_data->id)
    );

    // Build row
    $proposal_rows[] = [
      \Drupal::service('date.formatter')->format($proposal_data->creation_date, 'custom', 'd-m-Y'),
      $contributor_link->toString(),
      $proposal_data->project_title,
      $approval_date,
      $actual_completion_date,
      $approval_status,
      $edit_link->toString(),
    ];
  }

  // Check if there are any proposals
  if (empty($proposal_rows)) {
    $this->messenger()->addStatus($this->t('There are no proposals.'));
    return [];
  }

  // Table header
  $proposal_header = [
    $this->t('Date of Submission'),
    $this->t('Student Name'),
    $this->t('Title of the case-study project'),
    $this->t('Date of Approval'),
    $this->t('Date of Project Completion'),
    $this->t('Status'),
    $this->t('Action'),
  ];

  // Build table render array
  $build = [
    '#type' => 'table',
    '#header' => $proposal_header,
    '#rows' => $proposal_rows,
    '#empty' => $this->t('There are no proposals.'),
    '#attributes' => [
      'class' => ['case-study-proposals-table'],
    ],
  ];

  return $build;
}

  public function scilab_case_study_abstract() {
    $user = \Drupal::currentUser();
    $return_html = "";
$service = \Drupal::service('case_study_global');
// Get proposal data (assuming scilab_case_study_get_proposal() is updated for D11)
$proposal_data = $service->scilab_case_study_get_proposal();
if (!$proposal_data) {
  // Use RedirectResponse instead of drupal_goto
  return new \Symfony\Component\HttpFoundation\RedirectResponse(\Drupal\Core\Url::fromRoute('<front>')->toString());
}

// Query for submitted abstracts
$query = Database::getConnection()->select('case_study_submitted_abstracts', 'cssa');
$query->fields('cssa');
$query->condition('proposal_id', $proposal_data->id);
$abstracts_q = $query->execute()->fetchObject();

// Query for proposal details
$query_pro = Database::getConnection()->select('case_study_proposal', 'csp');
$query_pro->fields('csp');
$query_pro->condition('id', $proposal_data->id);
$abstracts_pro = $query_pro->execute()->fetchObject();

// Query for abstract PDF file
$query_pdf = Database::getConnection()->select('case_study_submitted_abstracts_file', 'cssaf');
$query_pdf->fields('cssaf');
$query_pdf->condition('proposal_id', $proposal_data->id);
$query_pdf->condition('filetype', 'A');
$abstracts_pdf = $query_pdf->execute()->fetchObject();

// Handle abstract file display
if ($abstracts_pdf) {
  $abstract_filename = !empty($abstracts_pdf->filename) ? $abstracts_pdf->filename : "File not uploaded";
} else {
  $abstract_filename = "File not uploaded";
}

// Query for case directory file
$query_process = Database::getConnection()->select('case_study_submitted_abstracts_file', 'cssaf');
$query_process->fields('cssaf');
$query_process->condition('proposal_id', $proposal_data->id);
$query_process->condition('filetype', 'S');
$abstracts_query_process = $query_process->execute()->fetchObject();

// Handle case directory file display
if ($abstracts_query_process) {
  $abstracts_query_process_filename = !empty($abstracts_query_process->filename) ? $abstracts_query_process->filename : "File not uploaded";

  // Handle upload/edit links
  if (empty($abstracts_q->is_submitted)) {
    $url = Link::fromTextAndUrl('Upload Case Directory', Url::fromUri('internal:/case-study-project/abstract-code/upload'))->toString();
  } else {
    if ($abstracts_q->is_submitted == 1) {
      $url = "";
    } else {
      $url = Link::fromTextAndUrl('Edit', Url::fromUri('internal:/case-study-project/abstract-code/upload'))->toString();
    }
  }
} else {
  $url = Link::fromTextAndUrl('Upload Case Directory', Url::fromUri('internal:/case-study-project/abstract-code/upload'))->toString();
  $abstracts_query_process_filename = "File not uploaded";
}

// Build the HTML output
$return_html .= '<strong>Contributor Name:</strong><br />' . Html::escape($proposal_data->name_title . ' ' . $proposal_data->contributor_name) . '<br /><br />';
$return_html .= '<strong>Title of the Case Study Project:</strong><br />' . Html::escape($proposal_data->project_title) . '<br /><br />';
$return_html .= '<strong>Uploaded abstract of the project:</strong><br />' . Html::escape($abstract_filename) . '<br /><br />';
$return_html .= '<strong>Uploaded Case Directory:</strong><br />' . Html::escape($abstracts_query_process_filename) . '<br /><br />';
$return_html .= $url . '<br />';

 return [
      '#type' => 'markup',
      '#markup' => $return_html,
    ];
  }

  public function scilab_case_study_download_full_project() {
    $user = \Drupal::currentUser();
    $service = \Drupal::service('case_study_global');
    $proposal_id =  \Drupal::routeMatch()->getParameter('proposal_id');
    $root_path = $service->scilab_case_study_path();
    //var_dump($root_path);die;
    $query = \Drupal::database()->select('case_study_proposal');
    $query->fields('case_study_proposal');
    $query->condition('id', $proposal_id);
    $case_study_q = $query->execute();
    $case_study_data = $case_study_q->fetchObject();
    $CASE_STUDY_PATH = $case_study_data->directory_name . '/project_files/';
    /* zip filename */
    $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    /* creating zip archive on the server */
    $zip = new \ZipArchive();
    if ($zip->open($zip_filename, \ZipArchive::CREATE) !== true) {
    \Drupal::messenger()->addError(t('Unable to create zip file.'));
    return new RedirectResponse('/circuit-simulation-project/full-download/project');
  }
    //$zip->open($zip_filename, \ZipArchive::CREATE);
    $query = \Drupal::database()->select('case_study_submitted_abstracts_file');
    $query->fields('case_study_submitted_abstracts_file');
    $query->condition('proposal_id', $proposal_id);
    $project_files = $query->execute();
    while ($scilab_project_files = $project_files->fetchObject()) {
      $zip->addFile($root_path . $CASE_STUDY_PATH . $scilab_project_files->filepath, $CASE_STUDY_PATH . str_replace(' ', '_', basename($scilab_project_files->filename)));
    }
    $zip_file_count = $zip->numFiles;
  $zip->close();

  if ($zip_file_count > 0 && file_exists($zip_filename)) {
    $response = new BinaryFileResponse($zip_filename);
    $response->setContentDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      str_replace(' ', '_', $case_study_data->project_title) . '.zip'
    );

    // Delete the zip file after it's sent
    $response->deleteFileAfterSend(true);
    return $response;
  }
  else {
    $msg = \Drupal::messenger()->addError(t('There are no files in this case study to download.'));
    if($user->hasPermission('Case Study bulk manage abstract')){
      
    return new RedirectResponse(Url::fromRoute('scilab_case_study.abstract_bulk_approval_form')->toString());
    }
    else{
          return new RedirectResponse(Url::fromRoute('scilab_case_study.completed_proposals_all')->toString());
    }
    
   // return $msg;
  }
  }

  public function scilab_case_study_completed_proposals_all() {
    $output = "";
    $count_query = \Drupal::database()->select('case_study_proposal', 't')
  ->condition('approval_status', 3)
  ->countQuery();
  $i = $count_query->execute()->fetchField(); 
    $query = \Drupal::database()->select('case_study_proposal');
    $query->fields('case_study_proposal');
    $query->condition('approval_status', 3);
    $query->orderBy('actual_completion_date', 'DESC');
    //$query->condition('is_completed', 1);
    $result = $query->execute();
      $preference_rows = [];
      //$i = $result->rowCount();
      //var_dump($i);die;
      while ($row = $result->fetchObject()) {
        $completion_date = date("Y", $row->actual_completion_date);
        $url = Url::fromUri('internal:/case-study-project/case-study-run/' . $row->id);
        $link = Link::fromTextAndUrl($row->project_title, $url)->toString();

        $preference_rows[] = array(
                $i,
                $link,
                $row->contributor_name,
                $row->university,
                $completion_date
              );

        $i--;
      } //$row = $result->fetchObject()
      $preference_header = [
        'No',
        'Case Study Project',
        'Contributor Name',
        'University/ Institute',
        'Year of Completion',
      ];
     $output =  [
      '#type' => 'table',
      '#header' => $preference_header,
      '#rows' => $preference_rows,
      '#empty' => 'We welcome your contributions to the Scilab Case Study Project',
    ];


    return $output;
  }

  public function scilab_case_study_progress_all() {
    $page_content = "";
    $count_query = \Drupal::database()->select('case_study_proposal', 't')
  ->condition('approval_status', '1')
  ->condition('is_completed', 0)
  ->countQuery();
  $i = $count_query->execute()->fetchField(); 
    $query = \Drupal::database()->select('case_study_proposal');
    $query->fields('case_study_proposal');
    $query->condition('approval_status', 1);
    $query->condition('is_completed', 0);
    $query->orderBy('approval_date', 'DESC');
    $result = $query->execute();
    if ($i == 0) {
      $markup = "Work is in progress for the following case studies under the Case Study Project<hr>";
    } //$result->rowCount() == 0
    else {
      $markup = "Work is in progress for the following case studies under the Case Study Project<hr>";
      $preference_rows = [];
     while ($row = $result->fetchObject()) {
        $approval_date = date("Y", $row->approval_date);
        $preference_rows[] = [
          $i,
          $row->project_title,
          $row->contributor_name,
          $row->university,
          $approval_date,
        ];
        $i--;
      } //$row = $result->fetchObject()
      $preference_header = [
        'No',
        'Circuit Simulation Project',
        'Contributor Name',
        'Institute',
        'Year',
      ];
      $page_content = [
        '#type' => 'table',
        '#header' => $preference_header,
        '#rows' => $preference_rows
      ];

    }
    return [
      'markup' => [
        '#markup' => $markup,
      ],
      'table' => $page_content,
    ];
  }

  public function scilab_case_study_download_final_report() {
    $proposal_id = arg(3);
    $root_path = scilab_case_study_path();
    $query = \Drupal::database()->select('case_study_proposal');
    $query->fields('case_study_proposal');
    $query->condition('id', $proposal_id);
    $result = $query->execute();
    $scilab_case_study_project_files = $result->fetchObject();
    $query = \Drupal::database()->select('case_study_submitted_abstracts_file');
    $query->fields('case_study_submitted_abstracts_file');
    $query->condition('proposal_id', $proposal_id);
    $query->condition('filetype', 'A');
    $project_files = $query->execute();
    $final_report_data = $project_files->fetchObject();
    $directory_name = $scilab_case_study_project_files->directory_name . '/project_files/';
    /*$str = substr($scilab_case_study_project_files->samplefilepath, strrpos($scilab_case_study_project_files->samplefilepath, '/'));
    $abstract_file = ltrim($str, '/');*/
    //var_dump($final_report_data);die;
    ob_clean();
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Type: application/pdf");
    header('Content-disposition: attachment; filename="' . $final_report_data->filename . '"');
    header("Content-Length: " . filesize($root_path . $directory_name . $final_report_data->filename));
    header("Content-Transfer-Encoding: binary");
    header("Expires: 0");
    header("Pragma: no-cache");
    readfile($root_path . $directory_name . $final_report_data->filename);
    ob_end_flush();
    ob_clean();
  }

  public function download_proposal_abstract() {
    $proposal_id  = \Drupal::routeMatch()->getParameter('proposal_id');
    $service = \Drupal::service('case_study_global');
    $root_path = $service->scilab_case_study_path();
    $query = \Drupal::database()->select('case_study_proposal');
    $query->fields('case_study_proposal');
    $query->condition('id', $proposal_id);
    $result = $query->execute();
    $scilab_case_study_project_files = $result->fetchObject();
    $directory_name = $scilab_case_study_project_files->directory_name . '/';
    $filename = basename($scilab_case_study_project_files->samplefilepath);
    $file_path = $root_path . $directory_name . $filename;
    //var_dump($file_path);die;
    if (!file_exists($file_path)) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    // Create a BinaryFileResponse to force download.
    $response = new BinaryFileResponse($file_path);
  $response->setContentDisposition(
    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
    str_replace(' ', '_', $abstract_file)
  );

    // Set the content type header.
   // $response->headers->set('Content-Type', $example_file_data->filemime);

    return $response;
  }

  public function _list_case_study_certificates() {
    $user = \Drupal::currentUser();
    $query_id = \Drupal::database()->query("SELECT id FROM case_study_proposal WHERE approval_status=3 AND uid= :uid", [
      ':uid' => $user->uid
      ]);
    $exist_id = $query_id->fetchObject();
    //var_dump($exist_id->id);die;
    if ($exist_id) {
      if ($exist_id->id) {
        if ($exist_id->id < 2) {
          \Drupal::messenger()->addStatus('<strong>You need to propose a <a href="https://scilab.in/case-study-project/proposal">Case Study Proposal</a></strong> or if you have already proposed then your Case Study is under reviewing process');
          return '';
        } //$exist_id->id < 3
        else {
          $search_rows = [];
          global $output;
          $output = '';
          $query3 = \Drupal::database()->query("SELECT id,project_title,contributor_name FROM case_study_proposal WHERE approval_status=3 AND uid= :uid", [
            ':uid' => $user->uid
            ]);
          while ($search_data3 = $query3->fetchObject()) {
            if ($search_data3->id) {
              // @FIXME
              // l() expects a Url object, created from a route name or external URI.
              // $search_rows[] = array(
              // 						$search_data3->project_title,
              // 						$search_data3->contributor_name,
              // 						l('Download Certificate', 'case-study-project/certificates/generate-pdf/' . $search_data3->id)
              // 					);

            } //$search_data3->id
          } //$search_data3 = $query3->fetchObject()
          if ($search_rows) {
            $search_header = [
              'Project Title',
              'Contributor Name',
              'Download Certificates',
            ];
            // @FIXME
            // theme() has been renamed to _theme() and should NEVER be called directly.
            // Calling _theme() directly can alter the expected output and potentially
            // introduce security issues (see https://www.drupal.org/node/2195739). You
            // should use renderable arrays instead.
            // 
            // 
            // @see https://www.drupal.org/node/2195739
            // $output        = theme('table', array(
            // 					'header' => $search_header,
            // 					'rows' => $search_rows
            // 				));

            return $output;
          } //$search_rows
          else {
            echo ("Error");
            return '';
          }
        }
      }
    } //$exist_id->id
    else {
      \Drupal::messenger()->addStatus('<strong>You need to propose a <a href="https://scilab.in/case-study-project/proposal">Case Study Proposal</a></strong> or if you have already proposed then your Case Study is under reviewing process');
      $page_content = "<span style='color:red;'> No certificate available </span>";
      return $page_content;
    }
  }

  public function verify_certificates($qr_code = 0) {
    $qr_code = arg(3);
    $page_content = "";
    if ($qr_code) {
      $page_content = verify_qrcode_fromdb($qr_code);
    } //$qr_code
    else {
      $verify_certificates_form = \Drupal::formBuilder()->getForm("verify_certificates_form");
      $page_content = \Drupal::service("renderer")->render($verify_certificates_form);
    }
    return $page_content;
  }

}
