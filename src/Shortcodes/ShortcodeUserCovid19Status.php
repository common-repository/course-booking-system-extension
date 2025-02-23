<?php

namespace CBSE\Shortcodes;

use CBSE\UserCovid19Status;
use DateTime;
use Exception;

final class ShortcodeUserCovid19Status
{
    protected static ?ShortcodeUserCovid19Status $instance = null;

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    private function __construct()
    {
        $this->init();
    }

    /**
     * Init
     */
    private function init()
    {
        add_shortcode('cbse_user_covid19_status', array($this, "showShortcode"));
        add_action('wp_enqueue_scripts', array($this, "addScripts"));
    }

    /**
     * Return instance
     *
     * @return ShortcodeUserCovid19Status
     */
    public static function getInstance(): ShortcodeUserCovid19Status
    {
        if (null === ShortcodeUserCovid19Status::$instance)
        {
            ShortcodeUserCovid19Status::$instance = new ShortcodeUserCovid19Status();
        }

        return ShortcodeUserCovid19Status::$instance;
    }

    public function addScripts()
    {
        wp_register_style('cbse_user_covid19_status_style', plugins_url('./assets/css/cbse_user_covid19.css', CBSE_PLUGIN_BASE_FILE));
    }

    /**
     * prevent from being unserialized (which would create a second instance of it)
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }

    /**
     * Show shortcode [cbse_user_covid19_status]
     *
     * @param array  $atts    Shortcode attributes. Default empty.
     * @param string $content Shortcode content. Default null.
     * @param string $tag     Shortcode tag (name). Default empty.
     *
     * @return string Shortcode output.
     */
    public function showShortcode($atts = [], $content = null, $tag = '')
    {
        wp_enqueue_style('cbse_user_covid19_status_style');

        // start box
        $o = '<div class="cbse-box">';

        if (is_user_logged_in())
        {
            $now = new DateTime('now');

            $userCovid19Status = new UserCovid19Status(get_current_user_id(), $now);
            $covid19Status = $userCovid19Status->getStatus()->getName();
            $certificateDate = $userCovid19Status->getCertificateDate();

            if (empty($covid19Status))
            {
                $covid19Status = 'unknown';
            }

            $o .= '<p>';
            $message = __('Your deposited Covid-19-Status is %s.', CBSE_LANGUAGE_DOMAIN);
            $o .= wp_sprintf($message, $covid19Status);
            $o .= '</p>';

            if (!$userCovid19Status->isValid())
            {
                $o .= '<p style="color: red;">';
                $o .= __('Your status is invalid. Please check it and renew it.', CBSE_LANGUAGE_DOMAIN);
                $o .= '</p>';
            }
            $o .= '<table>';
            if (!empty($userCovid19Status->getCertificateDateFormatted()))
            {
                $o .= '<tr>';
                $o .= '<td>' . __('Status date', CBSE_LANGUAGE_DOMAIN) . '</td>';
                $o .= '<td>' . $userCovid19Status->getCertificateDateFormatted() . '</td>';
                $o .= '</tr>';
            }
            if (!empty($userCovid19Status->getStatus()->getValidFromFormatted($certificateDate)))
            {
                $o .= '<tr>';
                $o .= '<td>' . __('Valid from', CBSE_LANGUAGE_DOMAIN) . '</td>';
                $o .= '<td>' . $userCovid19Status->getStatus()->getValidFromFormatted($certificateDate) . '</td>';
                $o .= '</tr>';
            }
            if (!empty($userCovid19Status->getStatus()->getValidToFormatted($certificateDate)))
            {
                $o .= '<tr>';
                $o .= '<td>' . __('Valid to', CBSE_LANGUAGE_DOMAIN) . '</td>';
                $o .= '<td>' . $userCovid19Status->getStatus()->getValidToFormatted($certificateDate) . '</td>';
                $o .= '</tr>';
            }
            if (!empty($userCovid19Status->getStatus()->getPlusValidToFormatted($certificateDate)))
            {
                $o .= '<tr>';
                $o .= '<td>' . __('Plus valid to', CBSE_LANGUAGE_DOMAIN) . '</td>';
                $o .= '<td>' . $userCovid19Status->getStatus()->getPlusValidToFormatted($certificateDate) . '</td>';
                $o .= '</tr>';
            }
            $o .= '</table>';
        }

        // enclosing tags
        if (!is_null($content))
        {

            $o .= '<p>';
            // secure output by executing the_content filter hook on $content
            // run shortcode parser recursively
            $o .= apply_filters('the_content', do_shortcode($content));
            $o .= '</p>';
        }

        // end box
        $o .= '</div>';

        // return output
        return $o;

    }

    /**
     * prevent the instance from being cloned (which would create a second instance of it)
     */
    private function __clone()
    {
    }
}

