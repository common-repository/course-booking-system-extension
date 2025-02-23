<?php
$current = strtotime(date("Y-m-d"));
$date = strtotime($args['timeslot']->date);
$difference = floor(($date - $current) / (60 * 60 * 24));

$time = '';
if ($difference == 0)
{
    $time = 'today';
}
elseif ($difference < 0)
{
    $time = 'past';
}
elseif ($difference > 0)
{
    $time = 'future';
}
?>
<div
        class="cbse-courses-single <?= $time ?> <?= date("Y-m-d", $date) ?> course-<?= $args['timeslot']->courseId ?>">
    <p class="cbse-date"><?= $args['courseInfo']->getColumn()->post_title ?>
        , <?= date_i18n(get_option('date_format'), strtotime($args['timeslot']->date)) ?>
        <?= date_i18n(get_option('time_format'), strtotime($args['timeslot']->eventStart)) ?>
        - <?= date_i18n(get_option('time_format'), strtotime($args['timeslot']->eventEnd)) ?></p>
    <h3 class="cbse-course-title"><?= $args['courseInfo']->getEvent()->post_title ?></h3>
    <p><?= __('Bookings', CBSE_LANGUAGE_DOMAIN) ?></p>
    <ol>
        <?php
        foreach ($args['courseInfo']->getBookings() as $booking)
        { ?>
            <li><?= trim($booking->lastName) ?>, <?= trim($booking->firstName) ?>
                <?php
                if (!empty($booking->covid19_status)) : ?>
                    (<?php
                    _e($booking->covid19_status, CBSE_LANGUAGE_DOMAIN) ?>)
                    <?php
                    if ($booking->flags)
                    {
                        echo " [$booking->flags]";
                    }
                    ?>
                <?php
                endif; ?>
            </li>
            <?php
        } ?>
    </ol>

    <p>
        <span class="cbse-course-summary">
        (<?= $args['timeslot']->bookings ?> | <?= $args['timeslot']->waitings ?>
                | <?= $args['courseInfo']->getEvent()->attendance ?>)
        </span>
        <button
                type="button"
                class="cbse cbse_participants_via_email"
                data-button='<?= json_encode(array('courseId' => $args['timeslot']->courseId, 'date' => $args['timeslot']->date)) ?>'
        >
            <?= __('Participants via email', CBSE_LANGUAGE_DOMAIN) ?>
        </button>
    </p>

</div>
