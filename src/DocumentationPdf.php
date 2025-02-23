<?php

namespace CBSE;

use Analog\Analog;
use CBSE\Database\CourseInfoDate;
use Exception;

class DocumentationPdf extends CbsePdf
{
    private CourseInfoDate $course;
    private $generalOptions;
    private $pdfOptions;

    public function __construct(CourseInfoDate $courseInfoDate)
    {
        $logMessage = get_class($this) . ' - ' . __FUNCTION__ . ' - ' . $courseInfoDate->__toString() . '[' . get_current_user_id() . ']';
        Analog::log($logMessage);
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->course = $courseInfoDate;
        $this->generalOptions = get_option('cbse_general_options');
        $this->pdfOptions = get_option('cbse_pdf_header_options');
    }

    public function __destruct()
    {
        parent::__destruct();
        $this->unlink();
    }

    /**
     * Delete the generated file
     */
    public function unlink(): bool
    {
        if (file_exists($this->getPdfFile()))
        {
            return unlink($this->getPdfFile());
        }
        return false;
    }

    public function getPdfFile(): string
    {
        return plugin_dir_path(__FILE__) . $this->course->getCourseId() . '_' . $this->course->getCourseDate()->format('Y-m-d') . '.pdf';
        
    }

    public function generatePdf(): string
    {
        $this->setMetaInformation();

        // Add a page
        // This method has several options, check the source code documentation for more information.
        $this->AddPage();

        $this->addHeader();
        $this->courseInfo();
        $this->bookings();

        // Close and output PDF document
        // This method has several options, check the source code documentation for more information.
        return $this->Output($this->getPdfFile(), 'F');
    }

    private function setMetaInformation()
    {
        $courseInfoCategories = $this->course->getEventCategoriesAsString();
        $dateString = $this->course->getCourseDateString();
        $courseInfoDateTime = $this->course->getCourseDateTimeString();

        // set document information
        $this->SetCreator(PDF_CREATOR);
        $this->SetAuthor('Code.Sport');
        $this->SetTitle("$dateString " . get_option('cbse_options')['header_title']);
        $subject = "{$courseInfoCategories} | {$this->course->getEvent()->post_title} | {$courseInfoDateTime}";
        $this->SetSubject($subject);

        // set default header data
        $this->setPrintHeader(false);
        $this->setFooterData(array(0, 0, 0), array(0, 0, 0));
        $this->setFooterText("{$this->course->getEvent()->post_title} | {$courseInfoDateTime}");

        // set header and footer fonts
        $this->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $this->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_LEFT);
        $this->SetHeaderMargin(0);
        $this->SetFooterMargin(7);

        // set auto page breaks
        $this->SetAutoPageBreak(true, 7);

        // set image scale factor
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set default font subsetting mode
        $this->setFontSubsetting(true);

        // Set font
        // dejavusans is a UTF-8 Unicode font, if you only need to
        // print standard ASCII chars, you can use core fonts like
        // helvetica or times to reduce file size.
        $this->SetFont('dejavusans', '', 10, '', true);
    }

    private function addHeader()
    {
        $imageId = $this->pdfOptions['header_image_attachment_id'];
        $image = wp_get_attachment_image($imageId, 700, "", array("class" => "img-responsive"));
        $title = get_option('cbse_pdf_header_options')['title'];

        $html = <<<EOD
            <style>
                h1 {
                    text-align: center;
                    font-size: 28pt;
                }
            </style>
        EOD;
        if (!empty($image))
        {
            $html .= $image;
        }
        if (!empty($title))
        {
            $html .= "<h1>" . $title . "</h1>";
        }
        $this->writeHTML($html, true, false, true, false, '');
    }

    private function courseInfo()
    {
        $courseInfoCategories = $this->course->getEventCategoriesAsString();
        $courseInfoTags = $this->course->getEventTagsAsString();
        $courseInfoDateTime = $this->course->getCourseDateTimeString();
        $userId = $this->course->getCoachId();
        $userMeta = get_userdata($userId);
        $userDisplayName = '______________________________________________________________';
        if ($userMeta !== false)
        {
            $userDisplayName = "{$userMeta->last_name}, {$userMeta->first_name}";
        }
        $userCovid19Status = new UserCovid19Status($userId, $this->course->getCourseDate());

        $w = array(55, 125);
        $this->Ln();
        $this->Cell($w[0], 6, __('Date and Time', CBSE_LANGUAGE_DOMAIN) . ':', 0, 0, 'L', false);
        $this->Cell($w[1], 6, "$courseInfoDateTime", 0, 0, 'L', false);
        $this->Ln();
        $this->Cell($w[0], 6, __('Title', CBSE_LANGUAGE_DOMAIN) . ':', 0, 0, 'L', false);
        $this->Cell($w[1], 6, $this->course->getEvent()->post_title, 0, 0, 'L', false);
        $this->Ln();
        if (!empty($courseInfo->timeslot->description))
        {
            $this->Cell($w[0], 6, __('Description', CBSE_LANGUAGE_DOMAIN) . ':', 0, 0, 'L', false);
            $this->Cell($w[1], 6, $courseInfo->timeslot->description, 0, 0, 'L', false);
            $this->Ln();
        }
        if ($this->generalOptions['categories_exclude'] != '0')
        {
            $this->Cell($w[0], 6, ($this->generalOptions['categories_title'] ?? __('Categories', CBSE_LANGUAGE_DOMAIN)) . ':', 0, 0, 'L', false);
            $this->Cell($w[1], 6, $courseInfoCategories, 0, 0, 'L', false);
            $this->Ln();
        }
        if ($this->generalOptions['tags_exclude'] != '0')
        {
            $this->Cell($w[0], 6, ($this->generalOptions['tags_title'] ?? __('Tags', CBSE_LANGUAGE_DOMAIN)) . ':', 0, 0, 'L', false);
            $this->Cell($w[1], 6, $courseInfoTags, 0, 0, 'L', false);
            $this->Ln();
        }
        $this->Cell($w[0], 6, __('Responsible coach', CBSE_LANGUAGE_DOMAIN) . ':', 0, 0, 'L', false);
        $this->Cell($w[1], 6, "{$userDisplayName} ({$userCovid19Status->getStatusOrAll()})", 0, 0, 'L', false);
        $this->Ln();
        $this->Ln();
        $this->Cell($w[0], 6, __('Signature coach', CBSE_LANGUAGE_DOMAIN) . ':', 0, 0, 'L', false);
        $this->Cell($w[1], 6, "", 'B', 0, 'L', false);
    }

    private function bookings()
    {
        $htmlAttendees = '<h2>' . __('Participants', CBSE_LANGUAGE_DOMAIN) . ':</h2>';

        $this->Ln();
        $this->Ln();
        $this->writeHTML($htmlAttendees, true, false, true, false, 'L');

        // Table header
        $w = array(10, 80, 35, 55);

        $this->SetFillColor(79, 79, 79);
        $this->SetTextColor(255);
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.3);
        $this->SetFont('', 'B');

        $this->Cell($w[0], 10, "", 1, 0, 'C', 1);
        $this->Cell($w[1], 10, __('Surname, Firstname (legible!)', CBSE_LANGUAGE_DOMAIN), 1, 0, 'C', 1);
        $this->SetFont('', 'B', 4, '', true);
        $this->Cell($w[2], 10, UserCovid19Status::getAll(), 1, 0, 'C', 1);
        $this->SetFont('', 'B', 10, '', true);
        $this->Cell($w[3], 10, __('Signature', CBSE_LANGUAGE_DOMAIN), 1, 0, 'C', 1);
        $this->Ln();

        //Table body
        $bookingNumber = 1;
        $fill = 0;

        $this->SetFillColor(237, 237, 237);
        $this->SetTextColor(0);
        $this->SetFont('');

        foreach ($this->course->getBookingsAlphabeticallySortedByLastName() as $booking)
        {
            $this->Cell($w[0], 10, $bookingNumber, 1, 0, 'R', $fill);
            $this->Cell($w[1], 10, trim($booking->lastName) . ", " . trim($booking->firstName), 1, 0, 'L', $fill);
            $this->SetFont('', '', 8, '', true);
            $status = __($booking->covid19_status, CBSE_LANGUAGE_DOMAIN);
            if ($booking->flags)
            {
                $status .= " [$booking->flags]";
            }
            $this->Cell($w[2], 10, $status, 1, 0, 'C', $fill);
            $this->SetFont('', '', 10, '', true);
            $this->Cell($w[3], 10, "", 1, 0, 'C', $fill);
            $this->Ln();
            $fill = !$fill;
            $bookingNumber++;
        }
        for ($i = $bookingNumber; $i <= $this->course->getEvent()->attendance; $i++)
        {
            $this->Cell($w[0], 10, $i, 1, 0, 'R', $fill);
            $this->Cell($w[1], 10, "", 1, 0, 'L', $fill);
            $this->Cell($w[2], 10, "", 1, 0, 'C', $fill);
            $this->Cell($w[3], 10, "", 1, 0, 'C', $fill);
            $this->Ln();
            $fill = !$fill;
        }

    }


}
