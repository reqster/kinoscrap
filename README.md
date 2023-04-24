# This is a test task for a job interview

# Contents:

## Scanning command.

### Source files

/src/Command/ScanCommand.php

### Usage

bin/console scan

### Description
Performs a scrape of all movied from https://www.kinopoisk.ru/lists/movies/top250/
Saves their rating data
Can be run as a cron job
If the site refuses to present data without filling out a captcha (which the script will hint at), load the page manually, solve the captcha, then copy the browser request's cookie valie into App\Util\ScanUtil::COOKIE