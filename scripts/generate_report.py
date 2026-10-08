#!/usr/bin/env python3
"""
VTU B.E. Computer Science & Engineering Project Report Generator (docxtpl).

Design Philosophy:
  - Academic report format tailored strictly to Visvesvaraya Technological University (VTU) guidelines:
    * Standard A4 page setup with 1.25" left margin for binding, 1.0" right/top/bottom margins.
    * Times New Roman typography throughout, 1.5 line spacing for body, clean heading hierarchies.
    * Dual OpenXML page borders: double border for front-matter/certificates, single box border for body.
    * Running header with department and course code, running footer with project title and dynamic Word page numbers.
  - Pre-filled with project and weekly meeting data:
    * Project details, team members with USNs and leader designation, guide/mentor, coordinator, HOD.
    * Complete Saturday Weekly Guide Meeting Review Log (Week #, Date, Status, Work Done, Next Steps, Blockers, Guide Directives/Instructions, Guide Remarks).
    * Continuous Internal Evaluation (CIE) Marks Sheet (Report /50, Presentation /25, Viva Q&A /25, Total /100).
    * Student and team attendance compliance metrics with university viva eligibility check.
  - Swappable Template:
    * Any custom VTU .docx template can be passed via command line or configured in config/report.php.
    * If no template exists, `generate_report.py --build-template` builds the master template automatically.

Usage:
  python generate_report.py <context.json> <output.docx> [template.docx]
  python generate_report.py <context.json> <template.docx> <output.docx>
  python generate_report.py --build-template [template.docx]
"""
import sys
import os
import json
import docx
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_TAB_ALIGNMENT
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.oxml import parse_xml
from docx.oxml.ns import nsdecls

try:
    from docxtpl import DocxTemplate
except ImportError:
    print(json.dumps({
        "success": False,
        "error": "docxtpl not installed. Run: pip install docxtpl"
    }), file=sys.stderr)
    sys.exit(1)


def style_run(run, font_name='Times New Roman', size_pt=12, bold=False, italic=False, color_rgb=(0, 0, 0)):
    run.font.name = font_name
    run.font.size = Pt(size_pt)
    run.bold = bold
    run.italic = italic
    run.font.color.rgb = RGBColor(*color_rgb)


def add_p(container, text='', align=WD_ALIGN_PARAGRAPH.LEFT, line_spacing=1.5, space_before=0, space_after=6,
          font_name='Times New Roman', size_pt=12, bold=False, italic=False, color_rgb=(0, 0, 0)):
    p = container.add_paragraph()
    p.alignment = align
    p.paragraph_format.line_spacing = line_spacing
    p.paragraph_format.space_before = Pt(space_before)
    p.paragraph_format.space_after = Pt(space_after)
    if text:
        r = p.add_run(text)
        style_run(r, font_name, size_pt, bold, italic, color_rgb)
    return p


def add_page_number(run):
    """Inserts a dynamic Word PAGE field inside a run."""
    fldChar1 = parse_xml(r'<w:fldChar %s w:fldCharType="begin"/>' % nsdecls('w'))
    instrText = parse_xml(r'<w:instrText %s xml:space="preserve"> PAGE </w:instrText>' % nsdecls('w'))
    fldChar2 = parse_xml(r'<w:fldChar %s w:fldCharType="separate"/>' % nsdecls('w'))
    fldChar3 = parse_xml(r'<w:fldChar %s w:fldCharType="end"/>' % nsdecls('w'))
    run._r.append(fldChar1)
    run._r.append(instrText)
    run._r.append(fldChar2)
    run._r.append(fldChar3)


def apply_section_borders(section, border_type='single'):
    """
    Applies OpenXML page borders to a section:
      - 'double': thick outer line with solid 1px inner line (thickThinMediumGap, sz=24)
      - 'single': thick black box border, clean and professional (single, sz=16 = 2pt)
    """
    for existing in section._sectPr.findall('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}pgBorders'):
        section._sectPr.remove(existing)

    if border_type == 'double':
        borders_xml = (
            f'<w:pgBorders {nsdecls("w")} w:offsetFrom="page">'
            '  <w:top w:val="thickThinMediumGap" w:sz="24" w:space="24" w:color="000000"/>'
            '  <w:left w:val="thickThinMediumGap" w:sz="24" w:space="24" w:color="000000"/>'
            '  <w:bottom w:val="thickThinMediumGap" w:sz="24" w:space="24" w:color="000000"/>'
            '  <w:right w:val="thickThinMediumGap" w:sz="24" w:space="24" w:color="000000"/>'
            '</w:pgBorders>'
        )
    else:
        borders_xml = (
            f'<w:pgBorders {nsdecls("w")} w:offsetFrom="page">'
            '  <w:top w:val="single" w:sz="16" w:space="24" w:color="000000"/>'
            '  <w:left w:val="single" w:sz="16" w:space="24" w:color="000000"/>'
            '  <w:bottom w:val="single" w:sz="16" w:space="24" w:color="000000"/>'
            '  <w:right w:val="single" w:sz="16" w:space="24" w:color="000000"/>'
            '</w:pgBorders>'
        )
    section._sectPr.append(parse_xml(borders_xml))


def build_vtu_template(template_path):
    """
    Constructs the master report template (.docx) with docxtpl placeholders,
    VTU-mandated margins, Times New Roman typography, dual section borders,
    running header/footer, student academic scaffolding, pre-filled weekly
    meeting tables, attendance records, and CIE marks sheets.
    """
    doc = docx.Document()

    # =========================================================================
    # SECTION 1: FRONT MATTER & CERTIFICATES (Double Border)
    # =========================================================================
    s1 = doc.sections[0]
    s1.top_margin = Inches(0.75)
    s1.bottom_margin = Inches(0.75)
    s1.left_margin = Inches(1.25)
    s1.right_margin = Inches(1.0)
    s1.page_width = Inches(8.27)
    s1.page_height = Inches(11.69)
    apply_section_borders(s1, 'double')

    # --- 1. COVER PAGE / OUTER TITLE PAGE ---
    add_p(doc, '{{ university|upper }}', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 10, 2, bold=True, size_pt=16)
    add_p(doc, '{{ university_addr|upper }}', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 0, 26, bold=True, size_pt=11)

    add_p(doc, 'A Mini-Project ({{ course_code }}) Report on', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 0, 10, italic=True, size_pt=13)
    add_p(doc, '“{{ project_name }}”', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 0, 26, bold=True, size_pt=17, color_rgb=(180, 0, 0))

    add_p(doc, 'Submitted in partial fulfilment of the requirements for the degree of', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 0, 4, italic=True, size_pt=12)
    add_p(doc, 'BACHELOR OF ENGINEERING', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 0, 2, bold=True, size_pt=14)
    add_p(doc, 'in', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 0, 2, italic=True, size_pt=12)
    add_p(doc, '{{ branch|upper }}', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 0, 26, bold=True, size_pt=14)

    add_p(doc, 'Submitted by', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 0, 10, bold=True, size_pt=13)

    # Dynamic member table with docxtpl row looping
    t_cov = doc.add_table(rows=3, cols=2)
    t_cov.alignment = WD_TABLE_ALIGNMENT.CENTER
    t_cov.rows[0].cells[0].paragraphs[0].text = '{%tr for m in members %}'

    c0 = t_cov.rows[1].cells[0].paragraphs[0]
    c0.alignment = WD_ALIGN_PARAGRAPH.LEFT
    r0 = c0.add_run('{{ m.name }} {% if m.is_leader %}(Leader){% endif %}')
    style_run(r0, bold=True, size_pt=12)

    c1 = t_cov.rows[1].cells[1].paragraphs[0]
    c1.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    r1 = c1.add_run('{{ m.usn }}')
    style_run(r1, bold=True, size_pt=12)

    t_cov.rows[2].cells[0].paragraphs[0].text = '{%tr endfor %}'

    add_p(doc, 'Under the Guidance of', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 18, 4, bold=True, size_pt=13)
    add_p(doc, '{{ mentor_name }}', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 0, 2, bold=True, size_pt=13)
    add_p(doc, 'Guide / Faculty Mentor, {{ department }}', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 0, 26, size_pt=11)

    add_p(doc, '{{ college_name|upper }}', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 0, 4, bold=True, size_pt=13)
    add_p(doc, '{{ department }}  |  Academic Year {{ academic_year }}', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 0, 0, bold=True, size_pt=11)

    doc.add_page_break()

    # --- 2. CERTIFICATE PAGE ---
    add_p(doc, '{{ department|upper }}', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 20, 8, bold=True, size_pt=14)
    add_p(doc, 'CERTIFICATE', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 0, 24, bold=True, size_pt=16)

    cert_text = (
        'This is to certify that the Mini-Project ({{ course_code }}) entitled “{{ project_name }}” '
        'has been successfully submitted by {{ member_details_str }}, '
        'bonafide students of {{ university }}, in partial '
        'fulfilment for the V semester of Bachelor of Engineering in {{ branch }} during the academic '
        'year {{ academic_year }}. The Mini-Project report has been approved as it satisfies the academic requirements as per Institutional '
        'and university guidelines.'
    )
    add_p(doc, cert_text, WD_ALIGN_PARAGRAPH.JUSTIFY, 1.5, 0, 36, size_pt=12)

    t_cert = doc.add_table(rows=2, cols=3)
    t_cert.alignment = WD_TABLE_ALIGNMENT.CENTER
    t_cert.rows[0].cells[0].paragraphs[0].text = 'Project Guide\n\n\n\n({{ mentor_name }})\nProject Mentor'
    t_cert.rows[0].cells[1].paragraphs[0].text = 'Project Coordinator\n\n\n\n({{ coordinator_name }})\nAssociate Professor'
    t_cert.rows[0].cells[2].paragraphs[0].text = 'Head of the Department\n\n\n\n({{ hod_name }})\nProfessor & HOD'

    t_cert.rows[1].cells[0].paragraphs[0].text = '\nName of the Examiners with date:\n1. _________________________\n2. _________________________'
    t_cert.rows[1].cells[2].paragraphs[0].text = '\nSignature:\n\n1. _____________\n2. _____________'

    for r in t_cert.rows:
        for c in r.cells:
            for p in c.paragraphs:
                p.paragraph_format.line_spacing = 1.15
                for run in p.runs:
                    style_run(run, bold=True, size_pt=9.5)

    doc.add_page_break()

    # --- 3. DECLARATION PAGE ---
    add_p(doc, '{{ department|upper }}', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 20, 8, bold=True, size_pt=14)
    add_p(doc, 'DECLARATION', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 0, 24, bold=True, size_pt=16)

    decl_text = (
        'We hereby declare that the Mini-Project work entitled “{{ project_name }}” which is '
        'being submitted in partial fulfilment for the award of Bachelor of Engineering in {{ branch }} '
        'of the {{ university }}, is the authenticated Project work carried out by us under '
        'the guidance of {{ mentor_name }}, {{ department }}, and has not '
        'submitted the results of the same, partially or fully in any other university or college.'
    )
    add_p(doc, decl_text, WD_ALIGN_PARAGRAPH.JUSTIFY, 1.5, 0, 26, size_pt=12)

    t_decl = doc.add_table(rows=4, cols=4)
    t_decl.alignment = WD_TABLE_ALIGNMENT.CENTER
    headers = ['Sl. No.', 'Name of Student', 'USN', 'Signature with date']
    for idx, h in enumerate(headers):
        t_decl.rows[0].cells[idx].paragraphs[0].text = h
        style_run(t_decl.rows[0].cells[idx].paragraphs[0].runs[0], bold=True, size_pt=10.5)

    t_decl.rows[1].cells[0].paragraphs[0].text = '{%tr for m in members %}'

    row = t_decl.rows[2]
    p0 = row.cells[0].paragraphs[0]
    r0 = p0.add_run('{{ loop.index }}')
    style_run(r0, size_pt=10.5)

    p1 = row.cells[1].paragraphs[0]
    r1 = p1.add_run('{{ m.name }}')
    style_run(r1, size_pt=10.5)

    p2 = row.cells[2].paragraphs[0]
    r2 = p2.add_run('{{ m.usn }}')
    style_run(r2, size_pt=10.5)

    p3 = row.cells[3].paragraphs[0]
    p3.text = ''

    for cell in row.cells:
        cell.paragraphs[0].paragraph_format.line_spacing = 1.3

    t_decl.rows[3].cells[0].paragraphs[0].text = '{%tr endfor %}'

    doc.add_page_break()

    # --- 4. ACKNOWLEDGEMENT PAGE ---
    add_p(doc, 'ACKNOWLEDGEMENT', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 20, 24, bold=True, size_pt=16)
    ack_text = (
        'We take this opportunity to express our sincere gratitude and profound thanks to our guide {{ mentor_name }}, '
        '{{ department }}, for the invaluable guidance, constructive criticism, and constant mentorship during the weekly review meetings '
        'without which this Mini-Project could not have been successfully realized.\n\n'
        'We extend heartfelt appreciation to Mini-Project Coordinator {{ coordinator_name }}, '
        '{{ department }}, for the systematic scheduling, milestone monitoring, and encouragement throughout the semester.\n\n'
        'We express our sincere gratitude to {{ hod_name }}, Head of the Department, '
        'for providing computing infrastructure, laboratory facilities, and academic encouragement.\n\n'
        'We also thank our faculty members, parents, and classmates for their direct and indirect support throughout this academic endeavor.'
    )
    add_p(doc, ack_text, WD_ALIGN_PARAGRAPH.JUSTIFY, 1.5, 0, 30, size_pt=12)

    p_ack_names = doc.add_paragraph()
    p_ack_names.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    p_ack_names.paragraph_format.line_spacing = 1.3
    r_ack = p_ack_names.add_run('{% for m in members %}{{ m.name }} ({{ m.usn }})\n{% endfor %}')
    style_run(r_ack, bold=True, size_pt=11)

    # =========================================================================
    # SECTION 2: REPORT BODY (Single Border, Header & Footer)
    # =========================================================================
    s2 = doc.add_section(docx.enum.section.WD_SECTION.NEW_PAGE)
    s2.top_margin = Inches(0.75)
    s2.bottom_margin = Inches(0.75)
    s2.left_margin = Inches(1.25)
    s2.right_margin = Inches(1.0)
    s2.page_width = Inches(8.27)
    s2.page_height = Inches(11.69)
    apply_section_borders(s2, 'single')

    # Header Configuration
    header = s2.header
    header.is_linked_to_previous = False
    p_hdr = header.paragraphs[0]
    p_hdr.paragraph_format.space_after = Pt(4)
    p_hdr.paragraph_format.tab_stops.add_tab_stop(Inches(6.02), WD_TAB_ALIGNMENT.RIGHT)
    r_hdr_left = p_hdr.add_run('{{ course_name }} Report ({{ course_code }})')
    style_run(r_hdr_left, size_pt=9, italic=True, color_rgb=(100, 100, 100))
    p_hdr.add_run('\t')
    r_hdr_right = p_hdr.add_run('{{ department }}')
    style_run(r_hdr_right, size_pt=9, italic=True, color_rgb=(100, 100, 100))

    # Bottom border rule on header
    p_hdr._p.get_or_add_pPr().append(parse_xml(
        f'<w:pBdr {nsdecls("w")}><w:bottom w:val="single" w:sz="6" w:space="4" w:color="CCCCCC"/></w:pBdr>'
    ))

    # Footer Configuration
    footer = s2.footer
    footer.is_linked_to_previous = False
    p_ftr = footer.paragraphs[0]
    p_ftr.paragraph_format.space_before = Pt(4)
    p_ftr.paragraph_format.tab_stops.add_tab_stop(Inches(6.02), WD_TAB_ALIGNMENT.RIGHT)
    r_ftr_left = p_ftr.add_run('{{ project_name }}')
    style_run(r_ftr_left, size_pt=9, italic=True, color_rgb=(100, 100, 100))
    p_ftr.add_run('\t')
    r_ftr_pg = p_ftr.add_run('Page ')
    style_run(r_ftr_pg, size_pt=9, italic=True, color_rgb=(100, 100, 100))
    add_page_number(r_ftr_pg)

    # Top border rule on footer
    p_ftr._p.get_or_add_pPr().append(parse_xml(
        f'<w:pBdr {nsdecls("w")}><w:top w:val="single" w:sz="6" w:space="4" w:color="CCCCCC"/></w:pBdr>'
    ))

    # --- 5. ABSTRACT PAGE ---
    add_p(doc, '{{ project_name }}', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 10, 8, bold=True, size_pt=13)
    add_p(doc, 'ABSTRACT', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 0, 20, bold=True, size_pt=16)

    add_p(doc, '{{ project_description }}', WD_ALIGN_PARAGRAPH.JUSTIFY, 1.5, 0, 16, size_pt=12)

    add_p(doc, '[INSTRUCTION FOR STUDENTS: Elaborate this abstract to describe the technical problem statement, chosen methodology, implementation architecture, and key performance outcomes in approximately 200-300 words.]',
          WD_ALIGN_PARAGRAPH.JUSTIFY, 1.5, 0, 16, italic=True, color_rgb=(90, 90, 90))

    add_p(doc, 'Keywords: Academic Project Review Engine, Saturday Guide Meetings, Continuous Internal Evaluation (CIE), VTU Curriculum, Attendance Compliance, Role-Based Access Control',
          WD_ALIGN_PARAGRAPH.JUSTIFY, 1.5, 10, 0, italic=True, size_pt=11, color_rgb=(90, 90, 90))

    doc.add_page_break()

    # --- 6. TABLE OF CONTENTS ---
    add_p(doc, 'TABLE OF CONTENTS', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 10, 20, bold=True, size_pt=16)
    toc_items = [
        ('1. INTRODUCTION', '1'),
        ('   1.1 Overview & Background', '1'),
        ('   1.2 Problem Statement', '2'),
        ('   1.3 Objectives of the Project', '2'),
        ('   1.4 Scope of the Work', '3'),
        ('2. LITERATURE SURVEY & SRS', '4'),
        ('   2.1 Existing System Analysis', '4'),
        ('   2.2 Proposed System Advantages', '5'),
        ('   2.3 Software Requirement Specification (SRS)', '6'),
        ('3. SYSTEM DESIGN & ARCHITECTURE', '7'),
        ('   3.1 System Architecture', '7'),
        ('   3.2 Data Flow & Use Case Models', '8'),
        ('   3.3 Relational Database Schema & ER Design', '9'),
        ('4. IMPLEMENTATION & EXPERIMENTAL SETUP', '11'),
        ('   4.1 Core Modules & Workflows', '11'),
        ('   4.2 Multi-Role Context Engine & Key Algorithms', '12'),
        ('5. TESTING & RESULTS', '13'),
        ('   5.1 Test Methodology & Test Cases', '13'),
        ('   5.2 User Interface Screenshots', '14'),
        ('6. CONCLUSION & FUTURE SCOPE', '16'),
        ('   6.1 Conclusion', '16'),
        ('   6.2 Future Enhancements', '16'),
        ('REFERENCES (IEEE Format)', '17'),
        ('APPENDIX A: PHOTO GALLERY', '18'),
        ('APPENDIX B: TEAM BIO-DATA', '19'),
        ('APPENDIX C: CONTINUOUS INTERNAL EVALUATION (CIE) & WEEKLY GUIDE MEETING RECORDS', '20'),
    ]
    for title, page in toc_items:
        p = doc.add_paragraph()
        p.paragraph_format.line_spacing = 1.3
        p.paragraph_format.space_after = Pt(3)
        r1 = p.add_run(title)
        is_heading = any(title.startswith(f'{i}.') for i in range(1, 7)) or 'REF' in title or 'APP' in title
        style_run(r1, bold=is_heading, size_pt=11)
        r2 = p.add_run(f' {"." * max(2, (52 - len(title)))} {page}')
        style_run(r2, size_pt=11)

    doc.add_page_break()

    # --- 7. CHAPTER 1: INTRODUCTION ---
    add_p(doc, 'CHAPTER 1', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 10, 6, bold=True, size_pt=18)
    add_p(doc, 'INTRODUCTION', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 0, 24, bold=True, size_pt=16)

    add_p(doc, '1.1 Overview & Background', WD_ALIGN_PARAGRAPH.LEFT, 1.3, 12, 6, bold=True, size_pt=14)
    add_p(doc, 'Project Title: {{ project_name }}\n\n'
               '[STUDENT CONTENT PLACEHOLDER: Provide a detailed overview and background of your chosen project domain. Explain why this domain was selected, current trends, and the relevance of your software solution in the field of Computer Science and Engineering.]',
          WD_ALIGN_PARAGRAPH.JUSTIFY, 1.5, 0, 12, italic=True, color_rgb=(70, 70, 70))

    add_p(doc, '1.2 Problem Statement', WD_ALIGN_PARAGRAPH.LEFT, 1.3, 12, 6, bold=True, size_pt=14)
    add_p(doc, '[STUDENT CONTENT PLACEHOLDER: Define the precise problem statement your project addresses. Describe the limitations, manual bottlenecks, or inefficiencies of current existing practices that motivated the creation of this application.]',
          WD_ALIGN_PARAGRAPH.JUSTIFY, 1.5, 0, 12, italic=True, color_rgb=(70, 70, 70))

    add_p(doc, '1.3 Objectives of the Project', WD_ALIGN_PARAGRAPH.LEFT, 1.3, 12, 6, bold=True, size_pt=14)
    add_p(doc, '[STUDENT CONTENT PLACEHOLDER: Enumerate 4 to 6 specific, measurable, and achievable objectives of your project:\n• Objective 1: To design and implement...\n• Objective 2: To develop an intuitive user interface for...\n• Objective 3: To integrate role-based access control...\n• Objective 4: To validate performance and usability with rigorous test cases.]',
          WD_ALIGN_PARAGRAPH.JUSTIFY, 1.5, 0, 12, italic=True, color_rgb=(70, 70, 70))

    add_p(doc, '1.4 Scope of the Work', WD_ALIGN_PARAGRAPH.LEFT, 1.3, 12, 6, bold=True, size_pt=14)
    add_p(doc, '[STUDENT CONTENT PLACEHOLDER: Delineate the functional and operational scope of your project. Mention the intended user base, the operating environment, and explicitly state any current boundaries or constraints (e.g. self-hosted deployment, campus intranet scope).]',
          WD_ALIGN_PARAGRAPH.JUSTIFY, 1.5, 0, 12, italic=True, color_rgb=(70, 70, 70))

    doc.add_page_break()

    # --- 8. CHAPTER 2: LITERATURE SURVEY & SRS ---
    add_p(doc, 'CHAPTER 2', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 10, 6, bold=True, size_pt=18)
    add_p(doc, 'LITERATURE SURVEY & SYSTEM REQUIREMENTS', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 0, 24, bold=True, size_pt=16)

    add_p(doc, '2.1 Existing System Analysis', WD_ALIGN_PARAGRAPH.LEFT, 1.3, 12, 6, bold=True, size_pt=14)
    add_p(doc, '[STUDENT CONTENT PLACEHOLDER: Analyze at least 2 to 3 existing systems, papers, or commercial software tools currently used to solve similar problems. Detail their architectural strengths, weaknesses, and why they do not completely satisfy your target problem scenario.]',
          WD_ALIGN_PARAGRAPH.JUSTIFY, 1.5, 0, 12, italic=True, color_rgb=(70, 70, 70))

    add_p(doc, '2.2 Proposed System Advantages', WD_ALIGN_PARAGRAPH.LEFT, 1.3, 12, 6, bold=True, size_pt=14)
    add_p(doc, '[STUDENT CONTENT PLACEHOLDER: Articulate how your proposed system addresses the limitations identified above. Emphasize advantages in cost, user experience, security, automation, or contextual relevance.]',
          WD_ALIGN_PARAGRAPH.JUSTIFY, 1.5, 0, 12, italic=True, color_rgb=(70, 70, 70))

    add_p(doc, '2.3 Software Requirement Specification (SRS)', WD_ALIGN_PARAGRAPH.LEFT, 1.3, 12, 6, bold=True, size_pt=14)

    t_srs = doc.add_table(rows=5, cols=2)
    t_srs.alignment = WD_TABLE_ALIGNMENT.CENTER
    srs_data = [
        ('Category', 'Specification Details'),
        ('Processor & Memory', 'Intel Core i3 / AMD Ryzen 3 or higher; Minimum 4 GB RAM'),
        ('Operating System & Server', 'Windows 10/11 or Linux; Apache HTTP Server (XAMPP)'),
        ('Programming Environment', 'PHP 8.x (PDO), HTML5, Tailwind CSS, Vanilla JavaScript (ES6+)'),
        ('Database Management', 'MySQL / MariaDB (utf8mb4 collation)'),
    ]
    for row_idx, (c1_txt, c2_txt) in enumerate(srs_data):
        row = t_srs.rows[row_idx]
        row.cells[0].paragraphs[0].text = c1_txt
        row.cells[1].paragraphs[0].text = c2_txt
        style_run(row.cells[0].paragraphs[0].runs[0], bold=(row_idx == 0), size_pt=10)
        style_run(row.cells[1].paragraphs[0].runs[0], bold=(row_idx == 0), size_pt=10)
        for c in row.cells:
            c.paragraphs[0].paragraph_format.line_spacing = 1.2

    doc.add_page_break()

    # --- 9. CHAPTER 3: SYSTEM DESIGN & ARCHITECTURE ---
    add_p(doc, 'CHAPTER 3', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 10, 6, bold=True, size_pt=18)
    add_p(doc, 'SYSTEM DESIGN & ARCHITECTURE', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 0, 24, bold=True, size_pt=16)

    add_p(doc, '3.1 System Architecture', WD_ALIGN_PARAGRAPH.LEFT, 1.3, 12, 6, bold=True, size_pt=14)
    add_p(doc, '[STUDENT CONTENT PLACEHOLDER: Explain the architectural style of your project (e.g. 3-tier web architecture, MVC pattern). Describe how requests flow between presentation, application logic, and persistence layers.]',
          WD_ALIGN_PARAGRAPH.JUSTIFY, 1.5, 0, 8, italic=True, color_rgb=(70, 70, 70))
    add_p(doc, '[Insert Figure 3.1: System Architecture Diagram Here]', WD_ALIGN_PARAGRAPH.CENTER, 1.5, 20, 20, bold=True, italic=True, size_pt=11, color_rgb=(120, 120, 120))

    add_p(doc, '3.2 Data Flow & Use Case Models', WD_ALIGN_PARAGRAPH.LEFT, 1.3, 12, 6, bold=True, size_pt=14)
    add_p(doc, '[STUDENT CONTENT PLACEHOLDER: Include Data Flow Diagrams (DFD Level 0 Context Diagram and Level 1 DFD) or UML Use Case Diagrams defining primary actors and interactions.]',
          WD_ALIGN_PARAGRAPH.JUSTIFY, 1.5, 0, 8, italic=True, color_rgb=(70, 70, 70))
    add_p(doc, '[Insert Figure 3.2: Data Flow Diagram / Use Case Model Here]', WD_ALIGN_PARAGRAPH.CENTER, 1.5, 20, 20, bold=True, italic=True, size_pt=11, color_rgb=(120, 120, 120))

    add_p(doc, '3.3 Relational Database Schema & ER Design', WD_ALIGN_PARAGRAPH.LEFT, 1.3, 12, 6, bold=True, size_pt=14)
    add_p(doc, '[STUDENT CONTENT PLACEHOLDER: Describe your Entity-Relationship (ER) model and list table schemas with primary keys, foreign key constraints, and datatypes.]',
          WD_ALIGN_PARAGRAPH.JUSTIFY, 1.5, 0, 8, italic=True, color_rgb=(70, 70, 70))
    add_p(doc, '[Insert Figure 3.3: Entity-Relationship (ER) Diagram Here]', WD_ALIGN_PARAGRAPH.CENTER, 1.5, 20, 20, bold=True, italic=True, size_pt=11, color_rgb=(120, 120, 120))

    doc.add_page_break()

    # --- 10. CHAPTER 4: IMPLEMENTATION ---
    add_p(doc, 'CHAPTER 4', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 10, 6, bold=True, size_pt=18)
    add_p(doc, 'IMPLEMENTATION', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 0, 24, bold=True, size_pt=16)

    add_p(doc, '4.1 Core Modules & Workflows', WD_ALIGN_PARAGRAPH.LEFT, 1.3, 12, 6, bold=True, size_pt=14)
    add_p(doc, '[STUDENT CONTENT PLACEHOLDER: Break down the application into its primary functional modules. Detail the workflow, authorization constraints, and responsibilities of each module.]',
          WD_ALIGN_PARAGRAPH.JUSTIFY, 1.5, 0, 12, italic=True, color_rgb=(70, 70, 70))

    add_p(doc, '4.2 Key Algorithms & Implementation Logic', WD_ALIGN_PARAGRAPH.LEFT, 1.3, 12, 6, bold=True, size_pt=14)
    add_p(doc, '[STUDENT CONTENT PLACEHOLDER: Highlight noteworthy code implementations, algorithm pseudo-code, security controls (such as password hashing or input sanitization), or AJAX event handling.]',
          WD_ALIGN_PARAGRAPH.JUSTIFY, 1.5, 0, 12, italic=True, color_rgb=(70, 70, 70))

    doc.add_page_break()

    # --- 11. CHAPTER 5: TESTING & RESULTS ---
    add_p(doc, 'CHAPTER 5', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 10, 6, bold=True, size_pt=18)
    add_p(doc, 'TESTING & RESULTS', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 0, 24, bold=True, size_pt=16)

    add_p(doc, '5.1 Test Methodology & Test Cases', WD_ALIGN_PARAGRAPH.LEFT, 1.3, 12, 6, bold=True, size_pt=14)

    t_tc = doc.add_table(rows=6, cols=5)
    t_tc.alignment = WD_TABLE_ALIGNMENT.CENTER
    tc_headers = ['Test ID', 'Scenario Description', 'Input Data', 'Expected Outcome', 'Status']
    for idx, h in enumerate(tc_headers):
        t_tc.rows[0].cells[idx].paragraphs[0].text = h
        style_run(t_tc.rows[0].cells[idx].paragraphs[0].runs[0], bold=True, size_pt=9.5)

    sample_tcs = [
        ('TC_01', 'User Authentication & Session Guard', 'Valid credentials for student/guide', 'Appropriate role dashboard granted', 'PASS'),
        ('TC_02', 'Contextual Role Authorization Check', 'Unauthorized project modification', 'Access denied with 403 status', 'PASS'),
        ('TC_03', 'Saturday Meeting Weekly Update Submission', 'Work done, next steps, blockers', 'Meeting update recorded with author', 'PASS'),
        ('TC_04', 'Guide Attendance Fast-Entry & Audit Log', 'Batch attendance with post-save edit', 'Mandatory change reason logged in audit', 'PASS'),
        ('TC_05', 'CIE Evaluation Marks Sheet Entry & Lock', 'Report, pres & QA marks submitted', 'Calculated 100 total; finalized edit locked', 'PASS'),
    ]
    for r_idx, tc in enumerate(sample_tcs, 1):
        row = t_tc.rows[r_idx]
        for c_idx, val in enumerate(tc):
            row.cells[c_idx].paragraphs[0].text = val
            style_run(row.cells[c_idx].paragraphs[0].runs[0], size_pt=9)
            row.cells[c_idx].paragraphs[0].paragraph_format.line_spacing = 1.15

    add_p(doc, '5.2 User Interface Screenshots', WD_ALIGN_PARAGRAPH.LEFT, 1.3, 18, 6, bold=True, size_pt=14)
    add_p(doc, '[STUDENT CONTENT PLACEHOLDER: Paste screenshots of your working system with figure labels (e.g. Figure 5.1: Main Project View, Figure 5.2: Saturday Guide Review Modal).]',
          WD_ALIGN_PARAGRAPH.JUSTIFY, 1.5, 0, 10, italic=True, color_rgb=(70, 70, 70))
    add_p(doc, '[Insert Screenshots Here with Captions]', WD_ALIGN_PARAGRAPH.CENTER, 1.5, 20, 20, bold=True, italic=True, size_pt=11, color_rgb=(120, 120, 120))

    doc.add_page_break()

    # --- 12. CHAPTER 6: CONCLUSION & FUTURE SCOPE ---
    add_p(doc, 'CHAPTER 6', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 10, 6, bold=True, size_pt=18)
    add_p(doc, 'CONCLUSION & FUTURE SCOPE', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 0, 24, bold=True, size_pt=16)

    add_p(doc, '6.1 Conclusion', WD_ALIGN_PARAGRAPH.LEFT, 1.3, 12, 6, bold=True, size_pt=14)
    add_p(doc, '[STUDENT CONTENT PLACEHOLDER: Summarize the achievements of your project. Reflect on the software development lifecycle experienced, how your initial objectives were satisfied, and the overall outcome of the project implementation.]',
          WD_ALIGN_PARAGRAPH.JUSTIFY, 1.5, 0, 12, italic=True, color_rgb=(70, 70, 70))

    add_p(doc, '6.2 Future Scope', WD_ALIGN_PARAGRAPH.LEFT, 1.3, 12, 6, bold=True, size_pt=14)
    add_p(doc, '[STUDENT CONTENT PLACEHOLDER: Discuss potential enhancements, additional feature roadmaps, scalability optimizations, or cloud/mobile extensions that could build upon this foundation in future semesters.]',
          WD_ALIGN_PARAGRAPH.JUSTIFY, 1.5, 0, 12, italic=True, color_rgb=(70, 70, 70))

    doc.add_page_break()

    # --- 13. REFERENCES ---
    add_p(doc, 'REFERENCES', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 10, 24, bold=True, size_pt=16)
    refs = [
        '[1] R. S. Pressman and B. R. Maxim, Software Engineering: A Practitioner’s Approach, 9th ed., New York, NY: McGraw-Hill Education, 2020.',
        '[2] Visvesvaraya Technological University, Scheme of Teaching and Examinations 2021: 5th Semester Computer Science and Engineering, Belagavi, India, 2021.',
        '[3] I. Sommerville, Software Engineering, 10th ed., Boston, MA: Pearson, 2016.',
        '[4] [Insert additional textbook or IEEE conference citations here]',
    ]
    for ref in refs:
        add_p(doc, ref, WD_ALIGN_PARAGRAPH.LEFT, 1.3, 0, 8, size_pt=11)

    doc.add_page_break()

    # --- 14. APPENDIX A: PHOTO GALLERY ---
    add_p(doc, 'APPENDIX A: PHOTO GALLERY', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 10, 24, bold=True, size_pt=16)
    add_p(doc, '[Photo 1: Initial Synopsis & Project Topic Approval Phase with Guide]', WD_ALIGN_PARAGRAPH.CENTER, 1.5, 30, 16, italic=True, size_pt=11, color_rgb=(100, 100, 100))
    add_p(doc, '[Photo 2: Implementation & Saturday Guide Review Meeting]', WD_ALIGN_PARAGRAPH.CENTER, 1.5, 30, 16, italic=True, size_pt=11, color_rgb=(100, 100, 100))
    add_p(doc, '[Photo 3: Final Demo Demonstration & CIE Viva Evaluation]', WD_ALIGN_PARAGRAPH.CENTER, 1.5, 30, 16, italic=True, size_pt=11, color_rgb=(100, 100, 100))

    doc.add_page_break()

    # --- 15. APPENDIX B: BIO DATA TABLE ---
    add_p(doc, 'APPENDIX B: TEAM MEMBERS BIO DATA', WD_ALIGN_PARAGRAPH.CENTER, 1.2, 10, 24, bold=True, size_pt=16)

    t_bio = doc.add_table(rows=4, cols=6)
    t_bio.alignment = WD_TABLE_ALIGNMENT.CENTER
    bio_headers = ['MEMBER', 'USN', 'NAME', 'DEPARTMENT', 'CONTACT', 'PHOTO']
    for idx, h in enumerate(bio_headers):
        t_bio.rows[0].cells[idx].paragraphs[0].text = h
        style_run(t_bio.rows[0].cells[idx].paragraphs[0].runs[0], bold=True, size_pt=9.5)

    t_bio.rows[1].cells[0].paragraphs[0].text = '{%tr for m in members %}'

    row_bio = t_bio.rows[2]
    row_bio.cells[0].paragraphs[0].add_run('{{ loop.index }}')
    row_bio.cells[1].paragraphs[0].add_run('{{ m.usn }}')
    row_bio.cells[2].paragraphs[0].add_run('{{ m.name }}')
    row_bio.cells[3].paragraphs[0].add_run('{{ department }}')
    row_bio.cells[4].paragraphs[0].add_run('[Email/Phone]')
    row_bio.cells[5].paragraphs[0].add_run('\n[Passport\nPhoto]\n')

    for cell in row_bio.cells:
        style_run(cell.paragraphs[0].runs[0], size_pt=9)
        cell.paragraphs[0].paragraph_format.line_spacing = 1.15

    t_bio.rows[3].cells[0].paragraphs[0].text = '{%tr endfor %}'

    doc.add_page_break()

    # =========================================================================
    # --- 16. APPENDIX C: CONTINUOUS INTERNAL EVALUATION (CIE) & MEETING LOGS ---
    # =========================================================================
    add_p(doc, 'APPENDIX C: CONTINUOUS INTERNAL EVALUATION (CIE) & WEEKLY GUIDE REVIEW RECORDS',
          WD_ALIGN_PARAGRAPH.CENTER, 1.2, 10, 12, bold=True, size_pt=15)

    add_p(doc, 'Official Saturday Guide Meeting Log & Internal Evaluation Marks',
          WD_ALIGN_PARAGRAPH.CENTER, 1.2, 0, 20, italic=True, size_pt=11)

    # 16a. CIE Evaluation Marks Sheet Table
    add_p(doc, '1. Continuous Internal Evaluation (CIE) Marks Summary (Scheme: 50 Report / 25 Pres. / 25 Viva Q&A)',
          WD_ALIGN_PARAGRAPH.LEFT, 1.3, 10, 8, bold=True, size_pt=12)

    t_marks = doc.add_table(rows=4, cols=7)
    t_marks.alignment = WD_TABLE_ALIGNMENT.CENTER
    marks_headers = ['Sl.', 'Student Name', 'USN', 'Report (/50)', 'Pres. (/25)', 'Q&A (/25)', 'Total (/100)']
    for idx, h in enumerate(marks_headers):
        t_marks.rows[0].cells[idx].paragraphs[0].text = h
        style_run(t_marks.rows[0].cells[idx].paragraphs[0].runs[0], bold=True, size_pt=9.5)

    t_marks.rows[1].cells[0].paragraphs[0].text = '{%tr for s in student_evaluations %}'

    row_m = t_marks.rows[2]
    row_m.cells[0].paragraphs[0].add_run('{{ loop.index }}')
    row_m.cells[1].paragraphs[0].add_run('{{ s.name }}')
    row_m.cells[2].paragraphs[0].add_run('{{ s.usn }}')
    row_m.cells[3].paragraphs[0].add_run('{{ s.report_marks_display }}')
    row_m.cells[4].paragraphs[0].add_run('{{ s.presentation_marks_display }}')
    row_m.cells[5].paragraphs[0].add_run('{{ s.qa_marks_display }}')
    row_m.cells[6].paragraphs[0].add_run('{{ s.total_marks_display }}')

    for cell in row_m.cells:
        style_run(cell.paragraphs[0].runs[0], size_pt=9)
        cell.paragraphs[0].paragraph_format.line_spacing = 1.15

    t_marks.rows[3].cells[0].paragraphs[0].text = '{%tr endfor %}'

    # 16b. Overall Attendance Summary
    add_p(doc, '', space_after=6)
    add_p(doc, '2. Weekly Guide Review Meetings & Attendance Compliance',
          WD_ALIGN_PARAGRAPH.LEFT, 1.3, 14, 8, bold=True, size_pt=12)

    add_p(doc, 'Total Saturday Meetings: {{ attendance_summary.total_meetings }}  |  Meetings Held: {{ attendance_summary.held }}  |  Average Team Attendance: {{ attendance_summary.percentage }}%  |  Eligibility Status: {{ attendance_summary.compliance_status }}',
          WD_ALIGN_PARAGRAPH.LEFT, 1.3, 0, 14, bold=True, size_pt=10.5, color_rgb=(0, 100, 0))

    # 16c. Pre-filled Weekly Meeting Review Log Table
    t_meet = doc.add_table(rows=4, cols=6)
    t_meet.alignment = WD_TABLE_ALIGNMENT.CENTER
    meet_headers = ['Week & Date', 'Status', 'Work Accomplished (Team)', 'Next Steps & Blockers', 'Guide Directives & Feedback', 'Attendance']
    for idx, h in enumerate(meet_headers):
        t_meet.rows[0].cells[idx].paragraphs[0].text = h
        style_run(t_meet.rows[0].cells[idx].paragraphs[0].runs[0], bold=True, size_pt=9)

    t_meet.rows[1].cells[0].paragraphs[0].text = '{%tr for w in weekly_meetings %}'

    row_w = t_meet.rows[2]
    row_w.cells[0].paragraphs[0].add_run('Week {{ w.week_number }}\n{{ w.meeting_date }}')
    row_w.cells[1].paragraphs[0].add_run('{{ w.status|upper }}')
    row_w.cells[2].paragraphs[0].add_run('{{ w.work_done_display }}')
    row_w.cells[3].paragraphs[0].add_run('Next:\n{{ w.next_steps_display }}\n\nBlockers:\n{{ w.blockers_display }}')
    row_w.cells[4].paragraphs[0].add_run('{{ w.guide_feedback_display }}\n\nDirectives:\n{{ w.instructions_display }}')
    row_w.cells[5].paragraphs[0].add_run('{{ w.attendance_summary_display }}')

    for cell in row_w.cells:
        style_run(cell.paragraphs[0].runs[0], size_pt=8.5)
        cell.paragraphs[0].paragraph_format.line_spacing = 1.15

    t_meet.rows[3].cells[0].paragraphs[0].text = '{%tr endfor %}'

    add_p(doc, '', space_after=14)
    add_p(doc, 'Guide Signature with Date: _________________________             HOD Signature: _________________________',
          WD_ALIGN_PARAGRAPH.CENTER, 1.3, 20, 0, bold=True, size_pt=10.5)

    os.makedirs(os.path.dirname(os.path.abspath(template_path)), exist_ok=True)
    doc.save(template_path)
    return template_path


def render_vtu_report(context, output_path, template_path=None):
    """
    Renders context data into the Word template using docxtpl.
    """
    if not template_path:
        script_dir = os.path.dirname(os.path.abspath(__file__))
        template_path = os.path.join(script_dir, '..', 'templates', 'report_template.docx')

    if not os.path.isfile(template_path):
        build_vtu_template(template_path)

    ctx = dict(context)
    members = ctx.get('members', [])
    if not members:
        members = [{'name': 'Student Name', 'usn': '1RG24CS001', 'is_leader': False}]
        ctx['members'] = members

    ctx.setdefault('university', 'Visvesvaraya Technological University')
    ctx.setdefault('university_addr', 'Jnana Sangama, Belagavi - 590 018')
    ctx.setdefault('college_name', 'Sahyadri College of Engineering & Management')
    ctx.setdefault('department', 'Department of Computer Science and Engineering')
    ctx.setdefault('branch', 'Computer Science and Engineering')
    ctx.setdefault('course_name', 'Mini-Project')
    ctx.setdefault('course_code', '21CSMP58')
    ctx.setdefault('academic_year', '2026-2027')
    ctx.setdefault('section', '5th Semester A and B Section')
    ctx.setdefault('project_name', 'Academic Project Management System')
    ctx.setdefault('project_description', 'Academic project implementation for VTU curriculum.')
    ctx.setdefault('mentor_name', 'Dr. Latha P H')
    ctx.setdefault('coordinator_name', 'Dr. Latha P H')
    ctx.setdefault('hod_name', 'Dr. Rathishchandra R Gatti')

    # Precomputed convenience strings
    ctx['member_details_str'] = ', '.join([f"{m.get('name')} ({m.get('usn')})" for m in members])
    ctx['member_names_str'] = ', '.join([m.get('name', '') for m in members])
    ctx['member_usns_str'] = ', '.join([m.get('usn', '') for m in members])

    # Clean student evaluation records
    eval_list = ctx.get('student_evaluations', [])
    if not eval_list:
        eval_list = []
        for m in members:
            eval_list.append({
                'name': m.get('name', ''),
                'usn': m.get('usn', ''),
                'report_marks_display': 'N/A',
                'presentation_marks_display': 'N/A',
                'qa_marks_display': 'N/A',
                'total_marks_display': 'N/A',
            })
    else:
        for s in eval_list:
            rep = s.get('report_marks')
            pres = s.get('presentation_marks')
            qa = s.get('qa_marks')
            tot = s.get('total_marks')
            s['report_marks_display'] = f"{float(rep):.1f}" if rep is not None else '-'
            s['presentation_marks_display'] = f"{float(pres):.1f}" if pres is not None else '-'
            s['qa_marks_display'] = f"{float(qa):.1f}" if qa is not None else '-'
            s['total_marks_display'] = f"{float(tot):.1f}" if tot is not None else '-'
    ctx['student_evaluations'] = eval_list

    # Clean weekly meetings
    meetings = ctx.get('weekly_meetings', [])
    if not meetings:
        meetings = [{
            'week_number': 1,
            'meeting_date': 'Saturday Review',
            'status': 'scheduled',
            'work_done_display': 'Initial project scope & requirements setup.',
            'next_steps_display': 'Module design and literature survey.',
            'blockers_display': 'None',
            'guide_feedback_display': 'Approved to commence implementation.',
            'instructions_display': 'None',
            'attendance_summary_display': '100% Present',
        }]
    else:
        for w in meetings:
            w['work_done_display'] = w.get('work_done') or 'Pending update'
            w['next_steps_display'] = w.get('next_steps') or 'Pending next steps'
            w['blockers_display'] = w.get('blockers') or 'None'
            w['guide_feedback_display'] = w.get('guide_feedback') or 'Pending guide review'
            inst = w.get('instructions')
            if isinstance(inst, list) and inst:
                w['instructions_display'] = '\n'.join([f"• {i.get('text', '')}" for i in inst])
            elif isinstance(inst, str) and inst.strip():
                w['instructions_display'] = inst
            else:
                w['instructions_display'] = 'None'
            w['attendance_summary_display'] = w.get('attendance_summary') or 'Present'
    ctx['weekly_meetings'] = meetings

    # Attendance summary
    att_summary = ctx.get('attendance_summary', {})
    att_pct = float(att_summary.get('percentage', 100.0))
    att_summary.setdefault('total_meetings', len(meetings))
    att_summary.setdefault('held', sum(1 for w in meetings if w.get('status') == 'held'))
    att_summary.setdefault('percentage', f"{att_pct:.1f}")
    att_summary.setdefault('compliance_status', 'ELIGIBLE FOR CIE / VIVA' if att_pct >= 75.0 else 'ATTENDANCE SHORTAGE (<75%)')
    ctx['attendance_summary'] = att_summary

    tpl = DocxTemplate(template_path)
    tpl.render(ctx)
    os.makedirs(os.path.dirname(os.path.abspath(output_path)), exist_ok=True)
    tpl.save(output_path)
    return output_path


def main():
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "error": "Usage: generate_report.py <context.json> <output.docx> [template.docx]"}), file=sys.stderr)
        sys.exit(1)

    if sys.argv[1] == '--build-template':
        tpl_path = sys.argv[2] if len(sys.argv) > 2 else os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'templates', 'report_template.docx')
        build_vtu_template(tpl_path)
        print(json.dumps({"success": True, "template": tpl_path}))
        return

    if len(sys.argv) < 3:
        print(json.dumps({"success": False, "error": "Usage: generate_report.py <context.json> <output.docx> [template.docx]"}), file=sys.stderr)
        sys.exit(1)

    context_path = sys.argv[1]
    arg2 = sys.argv[2]
    arg3 = sys.argv[3] if len(sys.argv) > 3 else None

    if arg3 and os.path.isfile(arg2) and not os.path.isfile(arg3):
        template_path = arg2
        output_path = arg3
    elif arg3 and os.path.isfile(arg3):
        output_path = arg2
        template_path = arg3
    else:
        output_path = arg2
        template_path = arg3

    with open(context_path, 'r', encoding='utf-8') as f:
        context = json.load(f)

    render_vtu_report(context, output_path, template_path)
    print(json.dumps({"success": True, "output": output_path}))


if __name__ == '__main__':
    try:
        main()
    except Exception as e:
        print(json.dumps({"success": False, "error": str(e)}), file=sys.stderr)
        sys.exit(1)
