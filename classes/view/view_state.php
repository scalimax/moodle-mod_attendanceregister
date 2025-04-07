<?php
namespace mod_attendanceregister\view;
 
require_once($CFG->libdir . '/pdflib.php');

// reference the Dompdf namespace
use Dompdf\Dompdf;

abstract class view_state {

    protected $OUTPUT;

    protected $view_helper;

    function __construct($OUTPUT, $view_helper) {
        $this->OUTPUT = $OUTPUT;
        $this->view_helper = $view_helper;
    }

    abstract function display_header(); 

    abstract function display_footer();

    abstract function display_groups_menu();

    abstract function notify_recalc_scheduled();

    abstract function set_pagelayout($page);

    abstract function open_printer();

    abstract function close_printer();

    abstract function display_user_sessions();


    static function make_printable($OUTPUT, $view_helper) {
        return new Printable($OUTPUT, $view_helper);
    }

    static function make_pdf($OUTPUT, $view_helper) {
        return new PrintablePdf($OUTPUT, $view_helper);
    }

    static function make_display_onscreen($OUTPUT, $view_helper) {
        return new DisplayOnScreen($OUTPUT, $view_helper);
    }
}

class Printable extends view_state {

    function open_printer() {
        //ob_start();
    }

    function close_printer() {
        // $html = ob_get_clean();

        // // include autoloader
        // require_once './dompdf/autoload.inc.php';


        // // instantiate and use the dompdf class
        // $dompdf = new Dompdf();
        // $dompdf->getOptions()->set([
        //     'defaultFont' => 'helvetica',
        // ]);
        // $dompdf->loadHtml($html);

        // // (Optional) Setup the paper size and orientation
        // $dompdf->setPaper('A4', 'portrait');

        // // Render the HTML as PDF
        // $dompdf->render();

        // // Output the generated PDF to Browser
        // $dompdf->stream();
    }

    function display_header() {
        echo $this->OUTPUT->header();
        echo $this->OUTPUT->heading(format_string($this->view_helper->register->name . ' - ' . $this->view_helper->user_fullname()));
    }

    function display_footer() {}

    function display_groups_menu() {}

    function notify_recalc_scheduled() {}

    function set_pagelayout($page) {
        $page->set_pagelayout('print');
    }

    function display_user_sessions() {
        $this->view_helper->display_usersessions();
    }

}

class PrintablePdf extends view_state {

    function open_printer() {
        //ob_start();
    }

    function close_printer() {
        // $html = ob_get_clean();

        // // include autoloader
        // require_once './dompdf/autoload.inc.php';


        // // instantiate and use the dompdf class
        // $dompdf = new Dompdf();
        // $dompdf->getOptions()->set([
        //     'defaultFont' => 'helvetica',
        // ]);
        // $dompdf->loadHtml($html);

        // // (Optional) Setup the paper size and orientation
        // $dompdf->setPaper('A4', 'portrait');

        // // Render the HTML as PDF
        // $dompdf->render();

        // // Output the generated PDF to Browser
        // $dompdf->stream();
    }

    function display_header() {
    }

    function display_footer() {}

    function display_groups_menu() {}

    function notify_recalc_scheduled() {}

    function set_pagelayout($page) {
        $page->set_pagelayout('print');
    }

    function display_user_sessions() {
        global $CFG;

        ob_start();
        $my_path = dirname(__FILE__);
        $css = \file_get_contents(realpath($my_path . '/' . '../../pdf.css'));
        echo '<style>'.$css.'</style>';
        
        $my_path = dirname(__FILE__);
        $css = \file_get_contents($my_path . '/' . '../../styles.css');
        echo '<style>'.$css.'</style>';    

        $this->view_helper->display_usersessions();
        $html = ob_get_clean();
        file_put_contents('out.html', $html);

        // create new PDF document
        $pdf = new \pdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $site = get_site();
        $pdf->SetCreator($site->fullname);
        $pdf->SetAuthor('Moodle LMS - ' . $site->fullname);
        $title = $this->view_helper->register->name . ' - ' . $this->view_helper->user_fullname();
        $pdf->SetTitle($title);
        $pdf->SetSubject('Registro presenze');
        $pdf->SetKeywords($this->view_helper->register->name . ',' . $this->view_helper->user_fullname());

        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, $title, $site->fullname . ' (' . $CFG->wwwroot . ')');

        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // reset pointer to the last page
        $pdf->lastPage();

        // ---------------------------------------------------------

        //Close and output PDF document
        $pdf->Output($title . '.pdf', 'I');
    }

}

class DisplayOnScreen extends view_state {

    function __construct($OUTPUT, $view_helper) {
        parent::__construct($OUTPUT, $view_helper);
        $this->view_helper->prepare_show_offline_session_form();
    }

    function open_printer() {}

    function close_printer() {}

    function set_pagelayout($PAGE) {}

    private function display_offline_session_form() {
        if ($this->view_helper->mform && $this->view_helper->register->offlinesessions) {
            echo $this->OUTPUT->box_start('generalbox attendanceregister_offlinesessionform');
            $this->view_helper->mform->display();
            echo $this->OUTPUT->box_end();
        }
    }

    function display_header() {
        echo $this->OUTPUT->header();
        echo $this->OUTPUT->heading(format_string($this->view_helper->register->name . $this->view_helper->user_fullname()));
    }

    function display_footer() {
        echo $this->OUTPUT->footer();
    }

    function display_groups_menu() {
        if ($this->view_helper->usercaps->canrecalc) {
            echo groups_allgroups_course_menu($this->view_helper->course, $url, true, $groupid);
        }
    }

    function notify_recalc_scheduled() {
        if ($this->view_helper->register->pendingrecalc && $this->view_helper->usercaps->canrecalc) {
            echo $this->OUTPUT->notification(get_string('recalc_scheduled_on_next_cron', 'attendanceregister'));
        }
    }

    function display_user_sessions() {
        $this->view_helper->display_log_button();
        echo '<br />';
        $this->display_offline_session_form();
        $this->view_helper->display_usersessions();
    }

}