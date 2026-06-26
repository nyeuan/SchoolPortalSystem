<?php
// File: student/download-grades.php
$required_role = 'Student';
include '../includes/session_check.php'; 

$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name  = htmlspecialchars($_SESSION['last_name']);
$student_id = $_SESSION['user_id'];
$filter_term = isset($_GET['term']) ? (int)$_GET['term'] : 0;

try {
    $protocol   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    // Adjusted link re-routing target pointing safely out into services directory path nodes
    $server_url = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/../services/soap-server.php';

    $soap_client = new SoapClient(null, array(
        'location' => $server_url,
        'uri'      => 'http://localhost/st-ives-lms/services/soap-server.php',
        'trace'    => 1
    ));

    $validated_xml_string = $soap_client->fetchXmlAcademicTranscript($student_id, $first_name, $last_name, $filter_term);
    $xml_data = simplexml_load_string($validated_xml_string);
} catch (SoapFault $fault) { die("SOAP Execution Error: " . $fault->getMessage()); }
  catch (Exception $e) { die("Data Error: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transcript - <?= htmlspecialchars($last_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>@media print { .no-print { display: none !important; } body { background: white; color: black; } @page { size: letter; margin: 1in; } }</style>
</head>
<body class="bg-gray-100 min-h-screen p-4 sm:p-8 font-serif">

    <div class="max-w-4xl mx-auto mb-6 bg-white rounded-xl p-4 shadow border flex justify-between items-center no-print">
        <div class="text-xs text-gray-500 font-sans">Click print and save destination to <b>"Save as PDF"</b>.</div>
        <div class="flex gap-2"><a href="grades.php<?= $filter_term > 0 ? '?term=' . $filter_term : '' ?>" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-sans font-bold text-xs">← Back</a><button onclick="window.print()" class="bg-[#0b4222] text-white px-5 py-2 rounded-lg font-sans font-bold text-xs">🖨 Print Transcript</button></div>
    </div>

    <main class="max-w-4xl mx-auto bg-[#fcfbf7] p-12 shadow border rounded-sm">
        <div class="text-center border-b-2 border-[#b8860b] pb-4 mb-6"><h1 class="text-3xl font-bold text-[#0b4222]">ST. IVES SCHOOL</h1><p class="text-xs font-sans mt-1 text-gray-400">Wisdom & Charity</p></div>
        <h2 class="text-xl font-bold text-center text-[#0b4222] tracking-wide font-sans mb-6">OFFICIAL TRANSCRIPT OF RECORDS</h2>
        <div class="grid grid-cols-2 gap-4 text-sm font-sans mb-8"><div><strong>Student:</strong> <?= htmlspecialchars($xml_data->StudentName) ?></div><div class="text-right"><strong>Term:</strong> <?= htmlspecialchars($xml_data->SchoolYear) ?></div></div>
        <table class="w-full border text-sm font-sans">
            <thead><tr class="bg-[#0b4222] text-white uppercase text-xs"><th class="p-3">Code</th><th class="p-3">Course</th><th class="p-3 text-center">Grade</th><th class="p-3 text-center">Letter</th><th class="p-3 text-right">Remarks</th></tr></thead>
            <tbody>
                <?php foreach ($xml_data->CourseRecords->Record as $rec): ?>
                    <tr><td class="p-3 font-bold text-[#0b4222]"><?= htmlspecialchars($rec->CourseCode) ?></td><td class="p-3 text-gray-700"><?= htmlspecialchars($rec->CourseName) ?></td><td class="p-3 text-center"><?= $rec->Remarks == 'Pending' ? '—' : $rec->Percentage . '%' ?></td><td class="p-3 text-center text-amber-600 font-bold"><?= htmlspecialchars($rec->LetterGrade) ?></td><td class="p-3 text-right font-bold"><?= htmlspecialchars($rec->Remarks) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
    <script>window.addEventListener('DOMContentLoaded', () => { setTimeout(() => { window.print(); }, 600); });</script>
</body>
</html>