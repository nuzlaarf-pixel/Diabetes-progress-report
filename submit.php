<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

// MySQL connection - change credentials if needed
$conn = new mysqli("localhost", "root", "", "diabetes_db");
if ($conn->connect_error) {
    http_response_code(500);
    die("DB Connection failed: " . $conn->connect_error);
}

// collect many fields (use null coalesce to avoid undefined index)
$fields = [
    'name','dob','age','sex','occupation','address','ethnicity','education',
    'symptoms','duration','onset','course','current_symptoms','hyper_symptoms','hypo_symptoms',
    'neuropathy','vision_skin','treatment_history','previously_diagnosed','diet_lifestyle',
    'pmh_htn','pmh_dyslip','pmh_cvd','pmh_kidney','pmh_eye','pmh_endocrine',
    'past_surgery','family_diabetes','family_cvd','family_genetic',
    'ps_diet','ps_exercise','ps_tobacco','ps_alcohol','ps_occupation','ps_stress',
    'medications','allergies','gyn_menses','gyn_gdm','gyn_preg','ros',
    'exam_bmi','exam_waist','exam_bp','exam_feet',
    'fbc','crp','hba1c'
];

$data = [];
foreach($fields as $f) $data[$f] = isset($_POST[$f]) ? trim($_POST[$f]) : null;

// Build INSERT dynamically to match table columns present.
// NOTE: ensure your patients table contains these columns. See SQL below to add them.
$columns = implode(',', array_map(function($c){ return $c; }, $fields));
$placeholders = implode(',', array_fill(0, count($fields), '?'));

// determine types: treat as strings except age (i) and maybe numeric tests left as strings.
$types = '';
foreach($fields as $f){
    if($f === 'age') $types .= 'i';
    else $types .= 's';
}

$stmt = $conn->prepare("INSERT INTO patients ($columns) VALUES ($placeholders)");
if(!$stmt){
    http_response_code(500);
    echo "Prepare failed: " . $conn->error;
    exit;
}

// build params
$params = [];
$params[] = & $types;
foreach($fields as $f){
    $params[] = & $data[$f];
}

// bind dynamically
call_user_func_array(array($stmt, 'bind_param'), $params);

if(!$stmt->execute()){
    http_response_code(500);
    echo "Execute failed: " . $stmt->error;
    exit;
}

echo "success";
$stmt->close();
$conn->close();
?>
