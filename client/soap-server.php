<?php
// Secure SOAP Web Service Node Architecture - Dual DTD & XSD Pipeline Implementation
include 'db.php';

function fetchXmlAcademicTranscript($student_id, $first_name, $last_name, $filter_term) {
    global $pdo;
    
    // Fallback display context label if term tracking parameters are omitted
    $term_label = "All Enrolled Semesters";
    
    // Resolve dynamic term label string from DB if filtered
    if ($filter_term > 0) {
        $term_stmt = $pdo->prepare("SELECT TermName FROM Term WHERE Term_ID = :term_id LIMIT 1");
        $term_stmt->execute([':term_id' => $filter_term]);
        $fetched_term = $term_stmt->fetchColumn();
        if ($fetched_term) {
            $term_label = $fetched_term;
        }
    }

    // Build relational join query accurately matching the updated database model
    $sql = "
        SELECT 
            c.CourseCode,
            c.CourseName,
            cg.FinalGrade,
            cg.Remarks
        FROM Enrollment e
        INNER JOIN Courses c ON e.FK_Course_ID = c.Course_ID
        LEFT JOIN CourseGrade cg ON e.Enrollment_ID = cg.FK_Enrollment_ID
        WHERE e.FK_User_ID = :student_id 
          AND e.EnrollmentStatus = 'Enrolled'
    ";

    $params = [':student_id' => $student_id];

    if ($filter_term > 0) {
        $sql .= " AND e.FK_Term_ID = :term_id";
        $params[':term_id'] = $filter_term;
    }

    $sql .= " ORDER BY c.CourseCode ASC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return "<Error>Database Query Execution Fault: " . htmlspecialchars($e->getMessage()) . "</Error>";
    }

    // Helper method matching the standard presentation model thresholds
    function getLetterGrade($grade) {
        if ($grade === null) return '—';
        if ($grade >= 95.00) return 'A+';
        if ($grade >= 90.00) return 'A';
        if ($grade >= 85.00) return 'B+';
        if ($grade >= 80.00) return 'B';
        if ($grade >= 75.00) return 'C';
        return 'F';
    }

    // 1. Construct valid XML string referencing the external DTD file
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<!DOCTYPE AcademicReport SYSTEM "grades_report.dtd">';
    $xml .= '<AcademicReport>';
    $xml .= '  <StudentName>' . htmlspecialchars($first_name . ' ' . $last_name) . '</StudentName>';
    $xml .= '  <SchoolYear>' . htmlspecialchars($term_label) . '</SchoolYear>';
    $xml .= '  <CourseRecords>';

    if (empty($records)) {
        // Fallback placeholder formatting satisfying schema type enforcement bounds
        $xml .= '    <Record>';
        $xml .= '      <CourseCode>N/A</CourseCode>';
        $xml .= '      <CourseName>No enrolled courses found for this term</CourseName>';
        $xml .= '      <Percentage>0.00</Percentage>';
        $xml .= '      <LetterGrade>—</LetterGrade>';
        $xml .= '      <Remarks>No Records</Remarks>';
        $xml .= '    </Record>';
    } else {
        foreach ($records as $row) {
            $is_pending = ($row['FinalGrade'] === null);
            // Ensure numeric values strictly adhere to XSD xs:decimal parsing parameters
            $grade_val  = $is_pending ? "0.00" : number_format($row['FinalGrade'], 2, '.', '');
            $letter     = getLetterGrade($row['FinalGrade']);
            $remark     = $is_pending ? "Pending" : $row['Remarks'];

            $xml .= '    <Record>';
            $xml .= '      <CourseCode>' . htmlspecialchars($row['CourseCode']) . '</CourseCode>';
            $xml .= '      <CourseName>' . htmlspecialchars($row['CourseName']) . '</CourseName>';
            $xml .= '      <Percentage>' . $grade_val . '</Percentage>';
            $xml .= '      <LetterGrade>' . htmlspecialchars($letter) . '</LetterGrade>';
            $xml .= '      <Remarks>' . htmlspecialchars($remark) . '</Remarks>';
            $xml .= '    </Record>';
        }
    }

    $xml .= '  </CourseRecords>';
    $xml .= '</AcademicReport>';

    // Initialize the validation engine
    $dom = new DOMDocument();
    
    // Enable external entity parsing references so local files can be opened
    $dom->resolveExternals = true;
    
    // Parse the generated text pool array into a queryable DOM object tree framework
    if (!$dom->loadXML($xml)) {
        return "<Error>XML Structure Generation Error</Error>";
    }

    // ── LAYER 1: DTD Structural Validation ──────────────────
    if (!$dom->validate()) {
        return "<Error>Layer 1 Grammar Validation Failed: File structure layout breaks rules declared in grades_report.dtd</Error>";
    }

    // ── LAYER 2: XSD Data Validation ────────────────────────
    // Compiles your grades_report.xsd file to execute deep data type inspections
    if (!$dom->schemaValidate(__DIR__ . '/grades_report.xsd')) {
        return "<Error>Layer 2 Data Validation Failed: Inner tag value properties violate data-type constraints in grades_report.xsd</Error>";
    }

    // Validation successful; return raw compliant text payload stream to consumer client
    return $xml;
}

// Initialize server payload mappings in non-WSDL mode
try {
    $server = new SoapServer(null, [
        'uri' => 'http://localhost/st-ives-lms/soap-server.php'
    ]);
    $server->addFunction('fetchXmlAcademicTranscript');
    $server->handle();
} catch (SOAPFault $f) {
    echo "SOAP Server Exception Control Fault: " . $f->getMessage();
}
?>