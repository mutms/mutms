# Changelog

## mu-5.0.4-02

Release date: 16/12/2025

* Added \tool_mulib\local\mudb::upsert_record() helper.
* Updated MuTMS plugin helpers.

## mu-5.0.4-01

Release date: 08/12/2025

* Changed \tool_mulib\external\form_autocomplete\user API to use sql fragments.
* Changed \tool_mulib\local\sql methods to never modify existing instance.
* Added get_contexts_by_capability_join() implementing fast user permissions lookup via database query. 
* Added context parents and map database table for fast context relationship lookups.
* Fixed custom notification editor.
* Added option to send copy of subordinate notifications to supervisors.
* Added management of reusable external PDO databases.

## mu-5.0.3-02

Release date: 08/11/2025

* Added \tool_mulib\local\mulib::clean_string() to help with Mustache double encoding
* Plugin documentation was move to GitHub wikis and removed Parsedown library
* Added support for outline AJAX form buttons. 
* Fixed rendering of actions dropdown.

## mu-5.0.3-01

Release date: 06/10/2025

* Added support for Moodle 5.1.
* Added support for creation of buttons and icons from action links.

## mu-5.0.2-03

Release date: 24/09/2025

* Added support for dropdown action icon and class.
* Added SQL fragments. 

## mu-5.0.2-02

Release date: 31/08/2025

* Fixed compatibility with unsupported MS SQL databases.

## mu-5.0.2-01

Release date: 09/08/2025

* New modal ajax forms helper replacing dialog forms.
* Internal refactoring.
* Moodle 5.0.2 support.

## mu-5.0.1-01

Release date: 30/06/2025

* Fixed compatibility with Moodle 5.0.1 release.
