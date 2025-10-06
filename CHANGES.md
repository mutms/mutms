# Changelog

## mu-5.0.3-01

Release date: 06/10/2025

* Fixed program tags itemtype to match database table name.
* Added support for Moodle 5.1.

## mu-5.0.2-03

Release date: 24/09/2025

* Certification allocation conflicts are now handled gracefully.

## mu-5.0.2-02

Release date: 31/08/2025

* Added Program completion allocation source - users may get allocated to a program when they complete another program.
* Fixed automatic cohort allocation source form.
* Empty custom fields are not displayed anymore.
* Triggered missing even allocation_completed event when overriding program completion.
* Fixed validation of tenant restrictions when selecting users.
* Note that "public" program field was renamed to "publicaccess" which affects web services and exports; program uploads can handle both old and new field names. 
* Fixed compatibility with unsupported MS SQL databases.
* Fixed fatal errors when sending deallocation email and SMTP is down, you may need to wait for next cron run to resolve blocking errors for students.

## mu-5.0.2-01

Release date: 09/08/2025

* Internal refactoring.
* Moodle 5.0.2 support.

## mu-5.0.1-01

Release date: 30/06/2025

* Added support for Moodle 5.0
