<?php
/**
 * VTU Project Report template (.docx) generator.
 *
 * Builds a Word document from scratch (no external libraries) following the
 * commonly-used VTU report format: A4, Times New Roman 12pt, 1.5 line
 * spacing, 1" margins plus a binding gutter, cover page, certificate,
 * declaration, acknowledgement, abstract, TOC / list of figures / list of
 * tables and the standard six-chapter breakdown.
 *
 * Project name, USNs, guide and classroom are pre-filled from the database
 * for members of the project (or the classroom's teachers).
 */
session_start();
require 'dbs.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
if (!class_exists('ZipArchive')) {
    http_response_code(500);
    exit('The PHP zip extension is not enabled, so the .docx template cannot be generated. Enable extension=zip in php.ini.');
}

$uid          = (int)$_SESSION['user_id'];
$classroom_id = (int)($_GET['classroom_id'] ?? 0);
$project_id   = (int)($_GET['project_id'] ?? 0);

// Authorise: member of the classroom. Project details are filled only if the
// user is on the project or is a classroom Admin.
$stmtM = $pdo->prepare("SELECT role FROM classroom_members WHERE classroom_id = ? AND user_id = ?");
$stmtM->execute([$classroom_id, $uid]);
$role = $stmtM->fetchColumn();
if (!$role) {
    http_response_code(403);
    exit('Not allowed');
}

$projectName = '[Project Title]';
$collegeName = '[College Name]';
$guideName   = '[Guide Name]';
$members     = []; // [name, usn]

if ($project_id) {
    $stmtP = $pdo->prepare("
        SELECT p.name, c.name AS classroom_name, mu.username AS mentor_name
        FROM projects p
        JOIN classrooms c ON p.classroom_id = c.id
        LEFT JOIN users mu ON p.mentor_id = mu.id
        WHERE p.id = ? AND p.classroom_id = ?
    ");
    $stmtP->execute([$project_id, $classroom_id]);
    $proj = $stmtP->fetch(PDO::FETCH_ASSOC);

    $stmtOn = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ? AND join_status = 'Active'");
    $stmtOn->execute([$project_id, $uid]);
    $allowed = ($role === 'Admin') || (bool)$stmtOn->fetch();

    if ($proj && $allowed) {
        $projectName = $proj['name'];
        if (!empty($proj['mentor_name'])) $guideName = $proj['mentor_name'];
        $stmtMem = $pdo->prepare("
            SELECT u.username, cm.usn FROM project_members pm
            JOIN projects p ON pm.project_id = p.id
            JOIN users u ON pm.user_id = u.id
            LEFT JOIN classroom_members cm ON cm.user_id = u.id AND cm.classroom_id = p.classroom_id
            WHERE pm.project_id = ? AND pm.join_status = 'Active'
            ORDER BY pm.is_leader DESC, u.username
        ");
        $stmtMem->execute([$project_id]);
        foreach ($stmtMem->fetchAll(PDO::FETCH_ASSOC) as $m) {
            $members[] = [$m['username'], $m['usn'] ?: '[USN]'];
        }
    }
}
if (empty($members)) $members[] = ['[Student Name]', '[USN]'];

// ---------- tiny WordprocessingML builders ----------
function x($s) { return htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES, 'UTF-8'); }

/** Paragraph. Options: align, bold, size(pt), caps, italic, pb(page break before), style, space(after pt), indent */
function wp($text = '', array $o = [])
{
    $ppr = '';
    if (!empty($o['style'])) $ppr .= '<w:pStyle w:val="' . $o['style'] . '"/>';
    if (!empty($o['pb']))    $ppr .= '<w:pageBreakBefore/>';
    if (!empty($o['keep']))  $ppr .= '<w:keepNext/>';
    if (isset($o['space']))  $ppr .= '<w:spacing w:after="' . ((int)$o['space'] * 20) . '"/>';
    if (!empty($o['align'])) $ppr .= '<w:jc w:val="' . $o['align'] . '"/>';
    $rpr = '';
    if (!empty($o['bold']))   $rpr .= '<w:b/>';
    if (!empty($o['italic'])) $rpr .= '<w:i/>';
    if (!empty($o['caps']))   $rpr .= '<w:caps/>';
    if (!empty($o['size']))   $rpr .= '<w:sz w:val="' . ((int)$o['size'] * 2) . '"/><w:szCs w:val="' . ((int)$o['size'] * 2) . '"/>';
    $run = $text === '' ? '' : '<w:r>' . ($rpr ? "<w:rPr>$rpr</w:rPr>" : '') . '<w:t xml:space="preserve">' . x($text) . '</w:t></w:r>';
    return '<w:p>' . ($ppr ? "<w:pPr>$ppr</w:pPr>" : '') . $run . '</w:p>';
}
function wh1($t, $pb = true) { return wp($t, ['style' => 'Heading1', 'pb' => $pb]); }
function wh2($t)             { return wp($t, ['style' => 'Heading2']); }

function wfield($instr, $placeholder)
{
    return '<w:p><w:r><w:fldChar w:fldCharType="begin" w:dirty="true"/></w:r>'
        . '<w:r><w:instrText xml:space="preserve"> ' . $instr . ' </w:instrText></w:r>'
        . '<w:r><w:fldChar w:fldCharType="separate"/></w:r>'
        . '<w:r><w:t>' . x($placeholder) . '</w:t></w:r>'
        . '<w:r><w:fldChar w:fldCharType="end"/></w:r></w:p>';
}

/** Table of cells; $rows is array of arrays of strings. */
function wtable(array $rows, array $widths, $borders = true, $boldFirstRow = false, $center = false)
{
    $b = $borders
        ? '<w:tblBorders><w:top w:val="single" w:sz="4"/><w:left w:val="single" w:sz="4"/><w:bottom w:val="single" w:sz="4"/><w:right w:val="single" w:sz="4"/><w:insideH w:val="single" w:sz="4"/><w:insideV w:val="single" w:sz="4"/></w:tblBorders>'
        : '';
    $grid = '';
    foreach ($widths as $w) $grid .= '<w:gridCol w:w="' . $w . '"/>';
    $out = '<w:tbl><w:tblPr><w:tblW w:w="' . array_sum($widths) . '" w:type="dxa"/>' . ($center ? '<w:jc w:val="center"/>' : '') . $b . '</w:tblPr><w:tblGrid>' . $grid . '</w:tblGrid>';
    foreach ($rows as $ri => $row) {
        $out .= '<w:tr>';
        foreach ($row as $ci => $cell) {
            $out .= '<w:tc><w:tcPr><w:tcW w:w="' . $widths[$ci] . '" w:type="dxa"/></w:tcPr>'
                . wp($cell, ['bold' => ($boldFirstRow && $ri === 0), 'align' => $center ? 'center' : 'left', 'space' => 4])
                . '</w:tc>';
        }
        $out .= '</w:tr>';
    }
    return $out . '</w:tbl>';
}

// ---------- document body ----------
$body  = '';

// Cover page
$body .= wtable([['[College Logo]', '[VTU Logo]']], [4500, 4500], false, false, true);
$body .= wp('VISVESVARAYA TECHNOLOGICAL UNIVERSITY', ['align' => 'center', 'bold' => true, 'size' => 16, 'space' => 4]);
$body .= wp('"Jnana Sangama", Belagavi - 590018', ['align' => 'center', 'size' => 12, 'space' => 14]);
$body .= wp('A Mini Project Report on', ['align' => 'center', 'size' => 12, 'space' => 6]);
$body .= wp('"' . $projectName . '"', ['align' => 'center', 'bold' => true, 'size' => 18, 'space' => 14]);
$body .= wp('Submitted in partial fulfilment of the requirements for the award of the degree of Bachelor of Engineering in [Branch]', ['align' => 'center', 'size' => 12, 'space' => 14]);
$body .= wp('Submitted by', ['align' => 'center', 'bold' => true, 'space' => 4]);
$body .= wtable(array_merge([['Name', 'USN']], $members), [5000, 3000], true, true, true);
$body .= wp('', ['space' => 8]);
$body .= wp('Under the guidance of', ['align' => 'center', 'bold' => true, 'space' => 2]);
$body .= wp($guideName, ['align' => 'center', 'space' => 2]);
$body .= wp('[Designation], Department of [Branch]', ['align' => 'center', 'space' => 14]);
$body .= wp($collegeName, ['align' => 'center', 'bold' => true, 'size' => 14, 'space' => 2]);
$body .= wp('Department of [Branch]  |  [Academic Year]', ['align' => 'center']);

// Certificate
$body .= wp('CERTIFICATE', ['pb' => true, 'align' => 'center', 'bold' => true, 'size' => 16, 'space' => 12]);
$names = implode(', ', array_map(function ($m) { return $m[0] . ' (' . $m[1] . ')'; }, $members));
$body .= wp('Certified that the Mini Project work entitled "' . $projectName . '" carried out by ' . $names
    . ' in partial fulfilment for the award of the degree of Bachelor of Engineering in [Branch] of Visvesvaraya Technological University, Belagavi during the year [Academic Year]. '
    . 'It is certified that all corrections/suggestions indicated for internal assessment have been incorporated in the report. '
    . 'The project report has been approved as it satisfies the academic requirements in respect of project work prescribed for the said degree.', ['align' => 'both']);
$body .= wp('', ['space' => 30]);
$body .= wtable([['Signature of Guide', 'Signature of HOD', 'Signature of Principal'], [$guideName, '[HOD Name]', '[Principal Name]']], [3000, 3000, 3000], false, true, true);
$body .= wp('', ['space' => 12]);
$body .= wp('External Viva', ['bold' => true, 'space' => 4]);
$body .= wtable([['Name of the Examiners', 'Signature with Date'], ['1.', ''], ['2.', '']], [5000, 4000], true, true);

// Declaration
$body .= wp('DECLARATION', ['pb' => true, 'align' => 'center', 'bold' => true, 'size' => 16, 'space' => 12]);
$body .= wp('We, the undersigned, hereby declare that the Mini Project work entitled "' . $projectName . '" is a bonafide work carried out by us under the guidance of ' . $guideName
    . ', in partial fulfilment of the requirements for the award of the degree of Bachelor of Engineering in [Branch] of Visvesvaraya Technological University, Belagavi. '
    . 'We further declare that this report has not been submitted to any other university or institution for the award of any degree.', ['align' => 'both']);
$body .= wp('Place: [Place]', ['space' => 2]);
$body .= wp('Date: [Date]', ['space' => 10]);
$body .= wtable(array_merge([['Name', 'USN', 'Signature']], array_map(function ($m) { return [$m[0], $m[1], '']; }, $members)), [3500, 2500, 3000], true, true);

// Acknowledgement
$body .= wp('ACKNOWLEDGEMENT', ['pb' => true, 'align' => 'center', 'bold' => true, 'size' => 16, 'space' => 12]);
$body .= wp('[Thank the Principal, Head of Department, project guide, project coordinator, faculty, family and friends. Keep this to one page, in the first person plural.]', ['align' => 'both', 'italic' => true]);

// Abstract
$body .= wp('ABSTRACT', ['pb' => true, 'align' => 'center', 'bold' => true, 'size' => 16, 'space' => 12]);
$body .= wp('[Summarise the problem, the approach, the technologies used and the key results in 200-300 words. One paragraph, no citations, no figures.]', ['align' => 'both', 'italic' => true]);

// Contents
$body .= wp('TABLE OF CONTENTS', ['pb' => true, 'align' => 'center', 'bold' => true, 'size' => 16, 'space' => 12]);
$body .= wfield('TOC \\o "1-3" \\h \\z \\u', 'Right-click here and choose "Update Field" to build the table of contents.');
$body .= wp('LIST OF FIGURES', ['pb' => true, 'align' => 'center', 'bold' => true, 'size' => 16, 'space' => 12]);
$body .= wfield('TOC \\h \\z \\c "Figure"', 'Insert captions (References > Insert Caption > Figure), then update this field.');
$body .= wp('LIST OF TABLES', ['pb' => true, 'align' => 'center', 'bold' => true, 'size' => 16, 'space' => 12]);
$body .= wfield('TOC \\h \\z \\c "Table"', 'Insert captions (References > Insert Caption > Table), then update this field.');

// Chapters
$chapters = [
    ['Chapter 1: Introduction', ['1.1 Overview', '1.2 Problem Statement', '1.3 Objectives', '1.4 Scope and Limitations', '1.5 Organisation of the Report']],
    ['Chapter 2: Literature Survey', ['2.1 Existing Systems', '2.2 Review of Related Work', '2.3 Summary and Research Gap']],
    ['Chapter 3: System Requirement Specification / Architecture', ['3.1 Functional Requirements', '3.2 Non-Functional Requirements', '3.3 Hardware and Software Requirements', '3.4 System Architecture']],
    ['Chapter 4: Design and Implementation', ['4.1 Design (DFD / UML / ER Diagrams)', '4.2 Module Description', '4.3 Implementation Details', '4.4 Testing Strategy']],
    ['Chapter 5: Results and Performance', ['5.1 Results and Screenshots', '5.2 Test Cases and Outcomes', '5.3 Performance Analysis']],
    ['Chapter 6: Conclusion and Future Scope', ['6.1 Conclusion', '6.2 Future Scope']],
];
foreach ($chapters as $i => $ch) {
    $body .= wh1($ch[0]);
    foreach ($ch[1] as $sec) {
        $body .= wh2($sec);
        $body .= wp('[Write content here.]', ['align' => 'both', 'italic' => true]);
    }
}

$body .= wh1('References');
$body .= wp('[1] A. Author, "Title of paper," Journal Name, vol. x, no. x, pp. xxx-xxx, Month Year.', ['align' => 'both']);
$body .= wp('[2] B. Author, Title of Book, xth ed. City, Country: Publisher, Year.', ['align' => 'both']);
$body .= wp('[3] "Title of web page," Website Name. [Online]. Available: URL (accessed Month Day, Year).', ['align' => 'both']);
$body .= wp('Use IEEE style. Cite in the text as [1], [2] in order of first appearance.', ['italic' => true]);

$sect = '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/>'
    . '<w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="708" w:footer="708" w:gutter="360"/></w:sectPr>';

$ns = 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"';
$documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document ' . $ns . '><w:body>' . $body . $sect . '</w:body></w:document>';

$stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:styles ' . $ns . '>'
    . '<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman" w:eastAsia="Times New Roman"/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr></w:rPrDefault>'
    . '<w:pPrDefault><w:pPr><w:spacing w:after="120" w:line="360" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults>'
    . '<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:qFormat/></w:style>'
    . '<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:qFormat/><w:pPr><w:keepNext/><w:spacing w:before="0" w:after="240"/><w:jc w:val="center"/><w:outlineLvl w:val="0"/></w:pPr><w:rPr><w:b/><w:caps/><w:sz w:val="28"/><w:szCs w:val="28"/></w:rPr></w:style>'
    . '<w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:qFormat/><w:pPr><w:keepNext/><w:spacing w:before="200" w:after="120"/><w:outlineLvl w:val="1"/></w:pPr><w:rPr><w:b/><w:sz w:val="26"/><w:szCs w:val="26"/></w:rPr></w:style>'
    . '</w:styles>';

$settingsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:settings ' . $ns . '><w:updateFields w:val="true"/></w:settings>';

$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml" ContentType="application/xml"/>'
    . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
    . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
    . '<Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>'
    . '</Types>';

$rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>';

$docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
    . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings" Target="settings.xml"/></Relationships>';

$tmp = tempnam(sys_get_temp_dir(), 'vtu');
$zip = new ZipArchive();
if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    exit('Could not build the template.');
}
$zip->addFromString('[Content_Types].xml', $contentTypes);
$zip->addFromString('_rels/.rels', $rootRels);
$zip->addFromString('word/document.xml', $documentXml);
$zip->addFromString('word/_rels/document.xml.rels', $docRels);
$zip->addFromString('word/styles.xml', $stylesXml);
$zip->addFromString('word/settings.xml', $settingsXml);
$zip->close();

$fileName = 'VTU_Project_Report_Template.docx';
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . filesize($tmp));
header('Cache-Control: no-store');
readfile($tmp);
unlink($tmp);
exit;
