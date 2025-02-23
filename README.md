# Course Booking System Extension

[This plugin for WordPress](https://wordpress.org/plugins/course-booking-system-extension/) is an extension for
the [Course Booking System](https://de.wordpress.org/plugins/course-booking-system/)
from [ComMotion](https://commotion.online/).

Feel free [to translate](https://translate.wordpress.org/projects/wp-plugins/course-booking-system-extension) it into
your language

## Requirements

- [Course Booking System](https://de.wordpress.org/plugins/course-booking-system/)
- [Basic-Auth](https://github.com/WP-API/Basic-Auth) is necessary that the data can be read from the api.

## Installation

### Via git

- `WP-ROOT/wp-content/plugins`
- clone in this folder this repository

### Via WordPress installer

- Download the zip from the releases
- Go into your WordPress instance and select plugins and then install.
- click on upload plugin
- select the downloaded file
- press now install and afterwords 'Course Booking System Extension'.

## Functionality

### API

You need the `id` from an event `wp-json/wp/v2/mp-event`. With this event-`id` you can load the overview of the
courses: `/wp-json/wp/v2/course-booking-system-extension/event/<ID>/courses`. On this you select the course-`id` and can
load the info about the course `wp-json/wp/v2/course-booking-system-extension/course/<ID>`. The participants you will
get on `wp-json/wp/v2/course-booking-system-extension/course/<ID>/date/<DATE:yyyy-mm-dd>`.

### Shortcode

#### Bookings overview for head of event

Add the shortcode `[cbse_event_head_courses pastdays=14 futuredays=1]` to a page or post and in this the overview for
the head of event will be shown. With`pastdays` you can specific how log in the past should be the data shown and
with `futuredays` the same for the future.

# Development
https://tobier.de/wordpress-plugin-schreiben-mit-composer-und-autoload/

## Dev

- `composer update`
- `composer install`
- `composer dump-autoload -o`

## Release

- `composer install --no-dev`
- `composer dump-autoload -o --no-dev`

# Dependencies

Thanks go to the following librarians
- [TCPDF](https://github.com/tecnickcom/TCPDF)
- [Analog](https://github.com/jbroadway/analog)
- [icalendar-generator](https://github.com/spatie/icalendar-generator)
