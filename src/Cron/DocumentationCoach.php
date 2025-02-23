<?php

namespace CBSE\Cron;

use Analog\Analog;
use CBSE\Database\CourseInfoDate;
use CBSE\Database\CoursesInTime;
use CBSE\DocumentationMail;
use DateInterval;
use DateTime;
use Exception;

class DocumentationCoach extends CronBase
{

    private static ?DocumentationCoach $instance = null;

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    private function __construct()
    {
        parent::__construct('cbse_cron_documentation_coach_hook');
        //add_action($this->getHook(), [$this, 'quarterlyExec']);
        //define("TEST", true);
    }

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): DocumentationCoach
    {
        if (static::$instance === null)
        {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * prevent from being unserialized (which would create a second instance of it)
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }

    protected function work(DateTime $dateLastRun, DateTime $dateNow)
    {
        $options = get_option('cbse_coach_mail_options');

        try
        {
            $hour = $this->getTimeFromOption($options, 'cron_before_time_hour', 24, 2);
            $minute = $this->getTimeFromOption($options, 'cron_before_time_minute', 60, 0);
            $interval = new DateInterval('PT' . $hour . 'H' . $minute . 'M');
        } catch (Exception $e)
        {
            Analog::error($e);
            $interval = new DateInterval('PT2H');
        }
        $dateFrom = clone $dateLastRun;
        $dateFrom->add($interval);
        $dateTo = clone $dateNow;
        $dateTo->add($interval);

        Analog::log(get_class($this) . ' - ' . __FUNCTION__ . ' - runs at ' . $dateNow->format('c') . ' for interval ' . $interval->format('%H:%I'));

        if (defined('TEST'))
        {
            $intervalString = $interval->format('%H:%I:%S');
            $dateLastRunString = $dateLastRun->format('Y-m-d H:i:s');
            wp_mail(get_option('admin_email'), 'CronTest', "Interval: $intervalString \nLast run: $dateLastRunString");
        }

        $coursesInTime = new CoursesInTime($dateFrom, $dateTo);
        $courses = $coursesInTime->getCourses();

        foreach ($courses as $course)
        {
            $this->workOnCourse($course);
        }
    }

    private function workOnCourse($course): void
    {
        try
        {
            $userId = ($course->substitutes_user_id ?? $course->user_id);
            if (get_userdata($userId) !== false)
            {
                $autoInformWay = empty(get_the_author_meta('cbse-auto-inform', $userId)) ? 'email' : get_the_author_meta('cbse-auto-inform', $userId);

                if ($autoInformWay == 'email')
                {
                    $date = DateTime::createFromFormat('Y-m-d', $course->date);
                    $courseInfo = new CourseInfoDate($course->course_id, $date);
                    $documentationMail = new DocumentationMail($courseInfo, get_option('cbse_coach_mail_options'));
                    $documentationMail->sentToUser($userId);
                }
            }
        } catch (Exception $e)
        {
            Analog::alert(get_class($this) . ' - ' . __FUNCTION__ . ' - ' . $course->course_id . ' - ' . $course->date);
            Analog::alert($e);
            $this->informAdmin($e, $course, __('Fatal error in the cronjob with the coach documentation', CBSE_LANGUAGE_DOMAIN));
        }
    }

    /**
     * prevent the instance from being cloned (which would create a second instance of it)
     */
    private function __clone()
    {
    }
}
