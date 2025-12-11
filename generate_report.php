<?php
require_once 'includes/config.php';

if (!isLoggedIn() || getUserRole() !== 'Admin') {
    header("Location: login.php");
    exit();
}

$patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
$report_type = isset($_POST['report_type']) ? $_POST['report_type'] : '';

if ($patient_id <= 0 || empty($report_type)) {
    header("Location: reports.php");
    exit();
}

$conn = getDBConnection();

// Get patient details
$stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    header("Location: reports.php");
    exit();
}

// Get patient's reports
$reports = $conn->query("SELECT * FROM reports WHERE patient_id = $patient_id ORDER BY date DESC");

// Get dispensation history
$dispensation_history = $conn->query("
    SELECT dl.quantity_disbursed, dl.date, i.item_name, i.category
    FROM dispensation_log dl
    JOIN inventory i ON dl.item_id = i.item_id
    WHERE dl.patient_id = $patient_id
    ORDER BY dl.date DESC
");

$conn->close();

$patient_name = str_replace(' ', '_', trim($patient['first_name'] . ' ' . $patient['middle_name'] . ' ' . $patient['last_name']));

// Generate report content based on type
$report_content = '';
$filename = '';

switch ($report_type) {
    case 'full_record':
        $filename = 'Full_Patient_Record_' . $patient_name . '.txt';
        $report_content = generateFullRecord($patient, $reports, $dispensation_history);
        break;
    case 'health_summary':
        $filename = 'Health_Summary_' . $patient_name . '.txt';
        $report_content = generateHealthSummary($patient);
        break;
    case 'medical_history':
        $filename = 'Medical_History_' . $patient_name . '.txt';
        $report_content = generateMedicalHistory($patient, $reports);
        break;
    default:
        header("Location: reports.php");
        exit();
}

// Output the report as a downloadable file
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($report_content));
echo $report_content;
exit();

function generateFullRecord($patient, $reports, $dispensation_history) {
    $content = "HEALTHDESK - FULL PATIENT RECORD\n";
    $content .= "================================\n\n";
    $content .= "Patient ID: " . $patient['patient_id'] . "\n";
    $content .= "Name: " . $patient['first_name'] . ' ' . $patient['middle_name'] . ' ' . $patient['last_name'] . "\n";
    $content .= "Age: " . $patient['age'] . "\n";
    $content .= "Sex: " . $patient['sex'] . "\n";
    $content .= "Date Added: " . date('M j, Y', strtotime($patient['created_at'])) . "\n\n";

    $content .= "PERSONAL INFORMATION\n";
    $content .= "--------------------\n";
    $content .= "Date of Birth: " . ($patient['date_of_birth'] ? date('M j, Y', strtotime($patient['date_of_birth'])) : 'N/A') . "\n";
    $content .= "Place of Birth: " . ($patient['place_of_birth'] ?: 'N/A') . "\n";
    $content .= "Home Address: " . ($patient['home_address'] ?: 'N/A') . "\n";
    $content .= "Citizenship: " . ($patient['citizenship'] ?: 'N/A') . "\n";
    $content .= "Civil Status: " . ($patient['civil_status'] ?: 'N/A') . "\n";
    $content .= "Parent/Guardian: " . ($patient['parent_guardian'] ?: 'N/A') . "\n";
    $content .= "Contact No: " . ($patient['contact_no'] ?: 'N/A') . "\n\n";

    $content .= "PHYSICAL EXAMINATION\n";
    $content .= "--------------------\n";
    $content .= "Height: " . ($patient['height'] ? $patient['height'] . ' cm' : 'N/A') . "\n";
    $content .= "Weight: " . ($patient['weight'] ? $patient['weight'] . ' kg' : 'N/A') . "\n";
    $content .= "BMI: " . ($patient['bmi'] ?: 'N/A') . "\n";
    $content .= "Blood Pressure: " . ($patient['bp'] ?: 'N/A') . "\n";
    $content .= "Temperature: " . ($patient['temp'] ? $patient['temp'] . ' °C' : 'N/A') . "\n";
    $content .= "Pulse Rate: " . ($patient['pr'] ?: 'N/A') . "\n";
    $content .= "Respiratory Rate: " . ($patient['rr'] ?: 'N/A') . "\n";
    $content .= "HEENT: " . ($patient['heent'] ?: 'N/A') . "\n";
    $content .= "Chest: " . ($patient['chest'] ?: 'N/A') . "\n";
    $content .= "Heart: " . ($patient['heart'] ?: 'N/A') . "\n";
    $content .= "Lungs: " . ($patient['lungs'] ?: 'N/A') . "\n";
    $content .= "Abdomen: " . ($patient['abdomen'] ?: 'N/A') . "\n";
    $content .= "Genital: " . ($patient['genital'] ?: 'N/A') . "\n";
    $content .= "Skin: " . ($patient['skin'] ?: 'N/A') . "\n\n";

    $content .= "PAST MEDICAL HISTORY\n";
    $content .= "--------------------\n";
    $content .= "Chronic Illness: " . ($patient['chronic_illness'] ?: 'None') . "\n";
    $content .= "Allergies: " . ($patient['allergies'] ?: 'None') . "\n";
    $content .= "Operations: " . ($patient['operations'] ?: 'None') . "\n";
    $content .= "Accidents/Injuries: " . ($patient['accidents_injuries'] ?: 'None') . "\n";
    $content .= "Regular Medicines: " . ($patient['medicines_regular'] ?: 'None') . "\n\n";

    $content .= "HEALTH REPORTS\n";
    $content .= "--------------\n";
    if ($reports->num_rows > 0) {
        while ($report = $reports->fetch_assoc()) {
            $content .= "Date: " . date('M j, Y', strtotime($report['date'])) . "\n";
            $content .= "Report:\n" . $report['report_content'] . "\n\n";
        }
    } else {
        $content .= "No reports available.\n\n";
    }

    $content .= "DISPENSATION HISTORY\n";
    $content .= "--------------------\n";
    if ($dispensation_history->num_rows > 0) {
        while ($dispensation = $dispensation_history->fetch_assoc()) {
            $content .= date('M j, Y', strtotime($dispensation['date'])) . " - " . $dispensation['item_name'] . " (" . $dispensation['category'] . ") - Quantity: " . $dispensation['quantity_disbursed'] . "\n";
        }
    } else {
        $content .= "No dispensation history.\n";
    }

    return $content;
}

function generateHealthSummary($patient) {
    $content = "HEALTHDESK - HEALTH SUMMARY\n";
    $content .= "===========================\n\n";
    $content .= "Patient: " . $patient['first_name'] . ' ' . $patient['last_name'] . "\n";
    $content .= "Age: " . $patient['age'] . " | Sex: " . $patient['sex'] . "\n\n";

    $content .= "VITAL SIGNS\n";
    $content .= "-----------\n";
    $content .= "Height: " . ($patient['height'] ? $patient['height'] . ' cm' : 'N/A') . "\n";
    $content .= "Weight: " . ($patient['weight'] ? $patient['weight'] . ' kg' : 'N/A') . "\n";
    $content .= "BMI: " . ($patient['bmi'] ?: 'N/A') . "\n";
    $content .= "Blood Pressure: " . ($patient['bp'] ?: 'N/A') . "\n";
    $content .= "Temperature: " . ($patient['temp'] ? $patient['temp'] . ' °C' : 'N/A') . "\n";
    $content .= "Pulse Rate: " . ($patient['pr'] ?: 'N/A') . "\n";
    $content .= "Respiratory Rate: " . ($patient['rr'] ?: 'N/A') . "\n\n";

    $content .= "MEDICAL HISTORY SUMMARY\n";
    $content .= "-----------------------\n";
    $content .= "Chronic Illness: " . ($patient['chronic_illness'] ?: 'None') . "\n";
    $content .= "Allergies: " . ($patient['allergies'] ?: 'None') . "\n";
    $content .= "Regular Medicines: " . ($patient['medicines_regular'] ?: 'None') . "\n";

    return $content;
}

function generateMedicalHistory($patient, $reports) {
    $content = "HEALTHDESK - MEDICAL HISTORY\n";
    $content .= "=============================\n\n";
    $content .= "Patient: " . $patient['first_name'] . ' ' . $patient['last_name'] . "\n";
    $content .= "Patient ID: " . $patient['patient_id'] . "\n\n";

    $content .= "PAST MEDICAL HISTORY\n";
    $content .= "--------------------\n";
    $content .= "Chronic Illness: " . ($patient['chronic_illness'] ?: 'None') . "\n";
    $content .= "Allergies: " . ($patient['allergies'] ?: 'None') . "\n";
    $content .= "Operations: " . ($patient['operations'] ?: 'None') . "\n";
    $content .= "Accidents/Injuries: " . ($patient['accidents_injuries'] ?: 'None') . "\n";
    $content .= "Regular Medicines: " . ($patient['medicines_regular'] ?: 'None') . "\n\n";

    $content .= "HEALTH REPORTS HISTORY\n";
    $content .= "----------------------\n";
    if ($reports->num_rows > 0) {
        while ($report = $reports->fetch_assoc()) {
            $content .= "Date: " . date('M j, Y', strtotime($report['date'])) . "\n";
            $content .= "Report:\n" . $report['report_content'] . "\n\n";
            $content .= "---\n\n";
        }
    } else {
        $content .= "No reports available.\n";
    }

    return $content;
}
?>
