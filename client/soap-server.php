<?php
include 'db.php'; // Persistent DB connection hook

class StIvesLmsService {
    private $pdo_conn;

    public function __construct($pdo) {
        $this->pdo_conn = $pdo;
    }

    // Dynamic data handler matching student query structures
    public function fetchXmlAcademicTranscript($student_id, $first_name, $last_name) {
        try {
            $grades_stmt = $this->pdo_conn->prepare("
                SELECT c.CourseCode, c.CourseName, cg.FinalGrade, cg.Remarks
                FROM Enrollment e
                INNER JOIN Courses c ON e.FK_Course_ID = c.Course_ID
                LEFT JOIN CourseGrade cg ON e.Enrollment_ID = cg.FK_Enrollment_ID
                WHERE e.FK_User_ID = :student_id AND e.EnrollmentStatus = 'Enrolled'
                ORDER BY c.CourseCode ASC
            ");
            $grades_stmt->execute([':student_id' => $student_id]);
            $academic_report = $grades_stmt->fetchAll();
        } catch (PDOException $e) {
            return new SoapFault("Server", "Database extraction fault occurred: " . $e->getMessage());
        }

        // Percentage tiers validation fallback layout
        $getLetter = function($grade) {
            if ($grade === null) return '—';
            if ($grade >= 95.00) return 'A+';
            if ($grade >= 90.00) return 'A';
            if ($grade >= 85.00) return 'B+';
            if ($grade >= 80.00) return 'B';
            if ($grade >= 75.00) return 'C';
            return 'F';
        };

        // DOMDocument serialization tree processing loop
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElement('AcademicReport');
        $dom->appendChild($root);

        $root->appendChild($dom->createElement('StudentName', "$first_name $last_name"));
        $root->appendChild($dom->createElement('SchoolYear', "S.Y. 2026-2027"));

        $recordsNode = $dom->createElement('CourseRecords');
        foreach ($academic_report as $row) {
            $is_pending = ($row['FinalGrade'] === null);
            
            $recordNode = $dom->createElement('Record');
            $recordNode->appendChild($dom->createElement('CourseCode', htmlspecialchars($row['CourseCode'])));
            $recordNode->appendChild($dom->createElement('CourseName', htmlspecialchars($row['CourseName'])));
            $recordNode->appendChild($dom->createElement('Percentage', $is_pending ? "0.00" : number_format($row['FinalGrade'], 2)));
            $recordNode->appendChild($dom->createElement('LetterGrade', $getLetter($row['FinalGrade'])));
            $recordNode->appendChild($dom->createElement('Remarks', $is_pending ? "Pending" : htmlspecialchars($row['Remarks'])));
            
            $recordsNode->appendChild($recordNode);
        }
        $root->appendChild($recordsNode);
        $xml_string = $dom->saveXML();

        // Strict XSD Schema validation layer inside the SOAP pipeline
        if (!$dom->schemaValidate(__DIR__ . '/grades_report.xsd')) {
            return new SoapFault("Server", "XML verification breakdown against XSD rules.");
        }

        // Return the clean verified text payload stream to the consumer client
        return $xml_string;
    }
}

// Disable WSDL caching for testing environments
ini_set("soap.wsdl_cache_enabled", "0");

// Initialize server payload mappings in non-WSDL mode
$options = array('uri' => 'http://localhost/st-ives-lms/soap-server.php');
$server  = new SoapServer(null, $options);
$server->setObject(new StIvesLmsService($pdo));
$server->handle();
exit;
?>