<?php
// File: services/soap-server.php
// Secure SOAP Web Service Node Architecture - Dual DTD & XSD Pipeline Implementation

// Adjusted to point to the centralized config folder
include '../config/db.php';

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

    // Build relational join query accurately matching the database model
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

    if ($filter_term > 0) {
        $sql .= " AND e.FK_Term_ID = :term_id";
    }
    $sql .= " ORDER BY c.CourseCode ASC";

    $stmt = $pdo->prepare($sql);
    $params = [':student_id' => $student_id];
    if ($filter_term > 0) {
        $params[':term_id'] = $filter_term;
    }
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate XML Response Structure matching grades_report.dtd rules
    $xml = '<?php xml version="1.0" encoding="UTF-8"?>';
    // Points directly to the local DTD declaration file in the same services directory folder
    $xml .= '<!DOCTYPE AcademicReport SYSTEM "grades_report.dtd">';
    $xml .= '<AcademicReport>';
    $xml .= '<StudentName>' . htmlspecialchars($first_name . ' ' . $last_name) . '</StudentName>';
    $xml .= '<SchoolYear>' . htmlspecialchars($term_label) . '</SchoolYear>';
    $xml .= '<CourseRecords>';

    foreach ($records as $row) {
        $percentage = $row['FinalGrade'] !== null ? number_format($row['FinalGrade'], 2) : '0.00';
        $remarks    = !empty($row['Remarks']) ? $row['Remarks'] : 'Pending';

        // Determine letter value thresholds to remain strictly compliant with DTD/XSD types
        $grade_val = (float)$percentage;
        if ($remarks === 'Pending') { $letter = '—'; }
        elseif ($grade_val >= 95)  { $letter = 'A'; }
        elseif ($grade_val >= 85)  { $letter = 'B'; }
        elseif ($grade_val >= 75)  { $letter = 'C'; }
        else                      { $letter = 'F'; }

        $xml .= '<Record>';
        $xml .= '<CourseCode>' . htmlspecialchars($row['CourseCode']) . '</CourseCode>';
        $xml .= '<CourseName>' . htmlspecialchars($row['CourseName']) . '</CourseName>';
        $xml .= '<Percentage>' . $percentage . '</Percentage>';
        $xml .= '<LetterGrade>' . $letter . '</LetterGrade>';
        $xml .= '<Remarks>' . htmlspecialchars($remarks) . '</Remarks>';
        $xml .= '<' . '/Record>'; // Split string closure to prevent syntax highlighter breaks
    }

    $xml .= '</CourseRecords>';
    $xml .= '</AcademicReport>';

    // Initialize validation engine
    $dom = new DOMDocument();
    $dom->resolveExternals = true;
    
    if (!$dom->loadXML($xml)) {
        return "<Error>XML Structure Generation Error</Error>";
    }

    // LAYER 1: DTD Structural Validation
    if (!$dom->validate()) {
        return "<Error>Layer 1 Grammar Validation Failed: File structure layout breaks rules declared in grades_report.dtd</Error>";
    }

    // LAYER 2: XSD Data Validation
    if (!$dom->schemaValidate(__DIR__ . '/grades_report.xsd')) {
        return "<Error>Layer 2 Data Validation Failed: Inner tag value properties violate data-type constraints in grades_report.xsd</Error>";
    }

    return $xml;
}

// Initialize server payload mappings in non-WSDL mode
try {
    $server = new SoapServer(null, [
        'uri' => 'http://localhost/st-ives-lms/services/soap-server.php'
    ]);
    $server->addFunction('fetchXmlAcademicTranscript');
    $server->handle();
} catch (SOAPFault $f) {
    echo $f->getMessage();
}