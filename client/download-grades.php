<?php
$required_role = 'Student';
include 'session_check.php'; // Secure gate check

$first_name = htmlspecialchars($_SESSION['first_name']);
$last_name  = htmlspecialchars($_SESSION['last_name']);
$student_id = $_SESSION['user_id'];

try {
    // Determine the active hostname to prevent hardcoded URL pathing failures
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $server_url = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/soap-server.php';

    // 1. Initialize the SOAP Client wrapper in non-WSDL context layout mode
    $soap_client = new SoapClient(null, array(
        'location' => $server_url,
        'uri'      => 'http://localhost/st-ives-lms/soap-server.php',
        'trace'    => 1
    ));

    // 2. Consume the remote XML function payload via SOAP
    $validated_xml_string = $soap_client->fetchXmlAcademicTranscript($student_id, $first_name, $last_name);

    // 3. Mount text elements string into simplexml arrays
    $xml_data = simplexml_load_string($validated_xml_string);
} catch (SoapFault $fault) {
    die("SOAP execution fault interface breakdown: " . $fault->getMessage());
} catch (Exception $e) {
    die("Data recovery error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transcript - <?= htmlspecialchars($last_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Print layout media modifiers filtering out UI components */
        @media print {
            body { background: white; color: black; font-family: serif; }
            .no-print { display: none !important; }
            @page { size: letter; margin: 1in; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-4 sm:p-8 font-serif text-gray-800">

    <div class="max-w-4xl mx-auto mb-6 bg-white rounded-xl p-4 shadow border border-amber-600/20 flex justify-between items-center no-print">
        <div class="text-xs text-gray-500 font-sans">
           Click print and set destination to <span class="font-semibold">"Save as PDF"</span>.
        </div>
        <div class="flex gap-2">
            <a href="grades.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-sans font-bold text-xs hover:bg-gray-300 transition">← Back</a>
            <button onclick="window.print()" class="bg-[#0b4222] text-white px-5 py-2 rounded-lg font-sans font-bold text-xs hover:opacity-90 transition">🖨 Print / Save PDF</button>
        </div>
    </div>

    <main class="max-w-4xl mx-auto bg-[#fcfbf7] p-12 shadow-xl border border-gray-200 rounded-sm">
        
        <div class="text-center border-b-2 border-[#b8860b] pb-4 mb-6">
            <h1 class="text-3xl font-bold tracking-wide text-[#0b4222]">ST. IVES SCHOOL</h1>
            <p class="text-xs italic text-gray-500 font-sans mt-1">Wisdom & Charity</p>
        </div>
        
        <h2 class="text-xl font-bold text-center text-[#0b4222] font-sans tracking-wide mb-6">OFFICIAL TRANSCRIPT OF RECORDS</h2>
        
        <div class="grid grid-cols-2 gap-4 text-sm font-sans mb-8">
            <div><strong>Student Name:</strong> <?= htmlspecialchars($xml_data->StudentName) ?></div>
            <div class="text-right"><strong>Academic Term:</strong> <?= htmlspecialchars($xml_data->SchoolYear) ?></div>
        </div>

        <table class="w-full text-left border-collapse border border-gray-300 text-sm font-sans">
            <thead>
                <tr class="bg-[#0b4222] text-white uppercase text-xs tracking-wider">
                    <th class="p-3 border border-gray-300">Course Code</th>
                    <th class="p-3 border border-gray-300">Course Name</th>
                    <th class="p-3 border border-gray-300 text-center">Grade</th>
                    <th class="p-3 border border-gray-300 text-center">Letter</th>
                    <th class="p-3 border border-gray-300 text-right">Remarks</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($xml_data->CourseRecords->Record as $record): ?>
                    <tr class="hover:bg-gray-50/50">
                        <td class="p-3 border border-gray-300 font-bold text-[#0b4222]"><?= htmlspecialchars($record->CourseCode) ?></td>
                        <td class="p-3 border border-gray-300 text-gray-700"><?= htmlspecialchars($record->CourseName) ?></td>
                        <td class="p-3 border border-gray-300 text-center font-mono"><?= ($record->Remarks == 'Pending' ? '—' : $record->Percentage . '%') ?></td>
                        <td class="p-3 border border-gray-300 text-center font-bold text-amber-600"><?= htmlspecialchars($record->LetterGrade) ?></td>
                        <td class="p-3 border border-gray-300 text-right font-bold"><?= htmlspecialchars($record->Remarks) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </main>

    <script>
        // Trigger browser print overlay automatically
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => { window.print(); }, 600);
        });
    </script>
</body>
</html>