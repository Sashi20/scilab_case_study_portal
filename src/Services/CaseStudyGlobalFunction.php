<?php
 
namespace Drupal\scilab_case_study\Services;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Database\Database;
use Drupal\Core\DrupalKernel;
use Drupal\user\Entity\User;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;


class CaseStudyGlobalFunction{
function scilab_case_study_check_valid_filename($file_name)
{
    if (!preg_match('/^[0-9a-zA-Z\.\_]+$/', $file_name)) {
        return false;
    } else if (substr_count($file_name, ".") > 1) {
        return false;
    } else {
        return true;
    }

}
function scilab_case_study_check_name($name = '')
{
    if (!preg_match('/^[0-9a-zA-Z\ ]+$/', $name)) {
        return false;
    } else {
        return true;
    }

}
function scilab_case_study_check_code_number($number = '')
{
    if (!preg_match('/^[0-9]+$/', $number)) {
        return false;
    } else {
        return true;
    }

}
function scilab_case_study_path()
{
    return $_SERVER['DOCUMENT_ROOT'] . base_path() . 'case_study_uploads/';
}

 function default_value_for_uploaded_files($filetype, $proposal_id) {
    $selected_files_array = null;

    if (in_array($filetype, ['A', 'S'])) {
        $query = Database::getConnection()->select('case_study_submitted_abstracts_file', 'f');
        $query->fields('f');
        $query->condition('proposal_id', $proposal_id);
        $query->condition('filetype', $filetype);
        $selected_files_array = $query->execute()->fetchObject();
    }

    return $selected_files_array;
}

function scilab_case_study_get_proposal()
{
    $user = \Drupal::currentUser();
    $query = \Drupal::database()->select('case_study_proposal');
    $query->fields('case_study_proposal');
    $query->condition('uid', $user->id());
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    if (!$proposal_data) {
        $msg = \Drupal::messenger()->addError("You do not have any approved  Case Study proposal. Please propose a Case Study");
       // drupal_goto('');
        $response = new RedirectResponse(Url::fromRoute('user.page')->toString());

  $response->send();
  return $msg;
    } //!$proposal_data
    switch ($proposal_data->approval_status) {
        case 0:
            \Drupal::messenger()->addStatus(t('Proposal is awaiting approval.'));
            return false;
        case 1:
            return $proposal_data;
        case 2:
            \Drupal::messenger()->addError(t('Proposal has been dis-approved.'));
            return false;
        case 3:
            \Drupal::messenger()->addStatus(t('Proposal has been marked as completed.'));
            return false;
        default:
            \Drupal::messenger()->addError(t('Invalid proposal state. Please contact site administrator for further information.'));
            return false;
    } //$proposal_data->approval_status
    return false;
}

function _cs_sentence_case($string)
{
    $string = ucwords(strtolower($string));
    foreach (array(
        '-',
        '\'',
    ) as $delimiter) {
        if (strpos($string, $delimiter) !== false) {
            $string = implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
        } //strpos($string, $delimiter) !== false
    } //array( '-', '\'') as $delimiter
    return $string;
}
function _cs_list_of_states()
{
    $states = array(
        0 => '-Select-',
    );
    $query = \Drupal::database()->select('list_states_of_india');
    $query->fields('list_states_of_india');
    //$query->orderBy('', '');
    $states_list = $query->execute();
    while ($states_list_data = $states_list->fetchObject()) {
        $states[$states_list_data->state] = $states_list_data->state;
    } //$states_list_data = $states_list->fetchObject()
    return $states;
}
function _cs_list_of_cities()
{
    $city = array(
        0 => '-Select-',
    );
    $query = \Drupal::database()->select('list_cities_of_india');
    $query->fields('list_cities_of_india');
    $query->orderBy('city', 'ASC');
    $city_list = $query->execute();
    while ($city_list_data = $city_list->fetchObject()) {
        $city[$city_list_data->city] = $city_list_data->city;
    } //$city_list_data = $city_list->fetchObject()
    return $city;
}
function _cs_list_of_pincodes()
{
    $pincode = array(
        0 => '-Select-',
    );
    $query = \Drupal::database()->select('list_of_all_india_pincode');
    $query->fields('list_of_all_india_pincode');
    $query->orderBy('pincode', 'ASC');
    $pincode_list = $query->execute();
    while ($pincode_list_data = $pincode_list->fetchObject()) {
        $pincode[$pincode_list_data->pincode] = $pincode_list_data->pincode;
    } //$pincode_list_data = $pincode_list->fetchObject()
    return $pincode;
}
function _cs_list_of_departments()
{
    $department = array();
    $query = \Drupal::database()->select('list_of_departments');
    $query->fields('list_of_departments');
    $query->orderBy('id', 'DESC');
    $department_list = $query->execute();
    while ($department_list_data = $department_list->fetchObject()) {
        $department[$department_list_data->department] = $department_list_data->department;
    } //$department_list_data = $department_list->fetchObject()
    return $department;
}

function _cs_list_of_case_studies()
{
    $existing_case_studies = array();
    $result = \Drupal::database()->query("SELECT * from list_of_project_titles WHERE {project_title_name} NOT IN( SELECT  project_title from case_study_proposal WHERE approval_status = 0 OR approval_status = 1 OR approval_status = 3)");
    while ($case_study_list_data = $result->fetchObject()) {
        $existing_case_studies[$case_study_list_data->project_title_name] = $case_study_list_data->project_title_name;
    }
    return $existing_case_studies;
}

function _cs_list_of_versions(){
    $versions = array();
    $query = \Drupal::database()->select('case_study_software_version');
    $query->fields('case_study_software_version');
    $version_list = $query->execute();
    while($version_data = $version_list->fetchObject()){
        $versions[$version_data->id] = $version_data->case_study_version;
    }
    return $versions;
}

function _cs_list_of_simulation_types(){
    $simulation_types = array();
    $query = \Drupal::database()->select('case_study_simulation_type');
    $query->fields('case_study_simulation_type');
    $simulation_type_list = $query->execute();
    while ($simulation_type_data = $simulation_type_list->fetchObject()) {
        $simulation_types[$simulation_type_data->id] = $simulation_type_data->simulation_type;
    }
    return $simulation_types;
}

function _cs_list_of_solvers($simulation_id){
    $simulation_id = $simulation_id;
    $solvers = array(
        0 => '-Select-',
        );
    $query = \Drupal::database()->select('case_study_solvers');
    $query->fields('case_study_solvers');
    $query->condition('simulation_type_id',$simulation_id);
    $solvers_list = $query->execute();
    while($solvers_data = $solvers_list->fetchObject()){
        $solvers[$solvers_data->solver_name] = $solvers_data->solver_name;
    }
    return $solvers;
}

function _cs_dir_name($project, $proposar_name)
{
    $project_title = $project;
    $proposar_name = $proposar_name;
    $dir_name = $project_title . ' By ' . $proposar_name;
    $directory_name = str_replace("__", "_", str_replace(" ", "_", str_replace("/", "_", trim($dir_name))));
    return $directory_name;
}
function scilab_case_study_document_path()
{
    return $_SERVER['DOCUMENT_ROOT'] . base_path() . 'case_study_uploads/';
}
function CS_RenameDir($proposal_id, $dir_name)
{
    $proposal_id = $proposal_id;
    $dir_name = $dir_name;
    $query = \Drupal::database()->query("SELECT directory_name,id FROM case_study_proposal WHERE id = :proposal_id", array(
        ':proposal_id' => $proposal_id,
    ));
    $result = $query->fetchObject();
//    var_dump($dir_name . $result->directory_name);
    if ($result != null) {
        $files = scandir($this->scilab_case_study_path());
        $files_id_dir = $this->scilab_case_study_path() . $result->id;
        //var_dump($files);die;
        $file_dir = $this->scilab_case_study_path() . $result->directory_name;
        if (is_dir($file_dir)) {
            $new_directory_name = rename($this->scilab_case_study_path() . $result->directory_name, $this->scilab_case_study_path() . $dir_name);
            return $new_directory_name;
        } //is_dir($file_dir)
        else if (is_dir($files_id_dir)) {
            $new_directory_name = rename($this->scilab_case_study_path() . $result->id, $this->scilab_case_study_path() . $dir_name);
            return $new_directory_name;
        } //is_dir($files_id_dir)
        else {
            \Drupal::messenger()->addMessage('Directory not available for rename.');
            return;
        }
    } //$result != NULL
    else {
        \Drupal::messenger()->addMessage('Project directory name not present in databse');
        return;
    }
    return;
}
function CreateReadmeFileCaseStudyProject($proposal_id)
{
    $database = Database::getConnection();
//var_dump($proposal_id);die;
  // Query to fetch proposal data
  $query = $database->select('case_study_proposal');
  $query->fields('case_study_proposal');
  $query->condition('id', $proposal_id);

  $result = $query->execute();
  $proposal_data = $result->fetchObject();
//var_dump($proposal_data);die;
  if (!$proposal_data) {
    \Drupal::logger('textbook_companion')->error('No proposal data found for ID: @proposal_id', ['@proposal_id' => $proposal_id]);
    return;
  }
    // $result = \Drupal::database()->query("
    //                     SELECT * from case_study_proposal WHERE id = :proposal_id", array(
    //     ":proposal_id" => $proposal_id,
    // ));
    // $proposal_data = $result->fetchObject();
    $root_path = $this->scilab_case_study_path();
    $readme_path = $root_path . $proposal_data->directory_name . '/README.txt';
    $directory_path = $root_path . '/' . $proposal_data->directory_name;
  if (!is_dir($directory_path)) {
    \Drupal::logger('textbook_companion')->error('Failed to create directory: @directory_path', ['@directory_path' => $directory_path]);
    return;
  }
    $txt = "";
    $txt .= "About the Case Study";
    $txt .= "\n" . "\n";
    $txt .= "Title Of The Case Study Project: " . $proposal_data->project_title . "\n";
    $txt .= "Proposar Name: " . $proposal_data->name_title . " " . $proposal_data->contributor_name . "\n";
    $txt .= "University: " . $proposal_data->university . "\n";
    $txt .= "\n" . "\n";
    $txt .= " Case Study Project By FOSSEE, IIT Bombay" . "\n";

  // Write the content to the README file
  $file = file_put_contents($readme_path,$txt);
  if (!$file) {
    \Drupal::logger('textbook_companion')->error('Failed to create README file at: @readme_path', ['@readme_path' => $readme_path]);
    return;
  }
}
function rrmdir_project($prop_id)
{
    $proposal_id = $prop_id;
    $result = \Drupal::database()->query("SELECT * from case_study_proposal WHERE id = :proposal_id", array(
        ":proposal_id" => $proposal_id,
    ));
    $proposal_data = $result->fetchObject();
    $root_path = $this->scilab_case_study_document_path();
    $dir = $root_path . $proposal_data->directory_name;
    if ($proposal_data->id == $prop_id) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir") {
                        $this->rrmdir($dir . "/" . $object);
                    } //filetype($dir . "/" . $object) == "dir"
                    else {
                        unlink($dir . "/" . $object);
                    }
                } //$object != "." && $object != ".."
            } //$objects as $object
            reset($objects);
            rmdir($dir);
            $msg = \Drupal::messenger()->addMessage("Directory deleted successfully");
            return $msg;
        } //is_dir($dir)
        $msg = \Drupal::messenger()->addMessage("Directory not present");
        return $msg;
    } //$proposal_data->id == $prop_id
    else {
        $msg = \Drupal::messenger()->addMessage("Data not found");
        return $msg;
    }
}
function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir . "/" . $object) == "dir") {
                    rrmdir($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }

            } //$object != "." && $object != ".."
        } //$objects as $object
        reset($objects);
        rmdir($dir);
    } //is_dir($dir)
}
function scilab_case_study_abstract_delete_project($proposal_id)
{
	$status = TRUE;
	$root_path = $this->scilab_case_study_path();
	$query = \Drupal::database()->select('case_study_proposal');
	$query->fields('case_study_proposal');
	$query->condition('id', $proposal_id);
	$proposal_q = $query->execute();
	$proposal_data = $proposal_q->fetchObject();
	if (!$proposal_data)
	{
		\Drupal::messenger()->addError('Invalid Case Study Project.');
		return FALSE;
	} //!$proposal_data
	$query = \Drupal::database()->select('case_study_submitted_abstracts_file');
	$query->fields('case_study_submitted_abstracts_file');
	$query->condition('proposal_id', $proposal_id);
	$abstract_q = $query->execute();
	$dir_project_files = $root_path . $proposal_data->directory_name;
	while ($abstract_data = $abstract_q->fetchObject())
	{
		if (is_dir($dir_project_files)){

		unlink($root_path . $proposal_data->directory_name . '/project_files/' . $abstract_data->filepath);
		}
		else
		{
			\Drupal::messenger()->addError('Invalid case study project abstract.');
		}
		
		//!dwsim_flowsheet_delete_abstract_file($abstract_data->id)
	}
	$res = rmdir($root_path . $proposal_data->directory_name . '/project_files/');
	
	unlink($root_path .'/' . $proposal_data->samplefilepath);
	$res = rmdir($root_path . $proposal_data->directory_name);
	return $status;
}

}
