=== My Calendar Pro ===
Contributors: joedolson
Donate link: http://www.joedolson.com/my-calendar/pro/
Tags: event submissions, user generated content, my calendar pro
Requires at least: 4.1
Tested up to: 4.5
Stable tag: 1.5.3

Add on to My Calendar plugin: adds many great features such as a responsive mode, create events from posts, and more.

== Description ==

My Calendar provides event management and provides numerous methods to display your events. The plug-in can support individual calendars within WordPress Multi-User, or multiple calendars displaying different categories of events. 

This plug-in expands on the capabilities of My Calendar to give you more powerful capabilities and control.

New or updated translations are always appreciated. The translation files are included in the download. 

== Installation ==

1. Upload the `/my-calendar-pro/` directory into your WordPress plugins directory.

2. Activate the plugin on your WordPress plugins page
  
3. Edit or create a page on your blog which includes the shortcode [submit_events] and visit
   the page you have edited or created. You should see your calendar. Visit My Calendar -> Help for assistance
   with shortcode options or widget configuration.

* My Calendar Submissions has been replaced by My Calendar PRO. All licenses have been transferred to the new plug-in.

== Changelog ==

= 1.5.4 =

* Performance & stability improvements to event imports
* Bug fix with handling of newline character in event descriptions
* Event import now requires > PHP 5.3

= 1.5.3 =

* Bug fix: could not send event notifications to multiple users
* Bug fix: undeleted variable
* Bug fixes to effective handling of license verification
* Bug fix: obtain full first instance of an event instead of just event core when processing templates.
* Bug fix: Better feedback after license activation.
* Bug fix: changed default location value to false (no default)
* Bug fix: event repeats input type on front-end form should be 'number'
* Bug fix: Improve import CSV if user on PHP 5.3+ using str_getcsv
* Add support for selective refresh in customizer in WordPress 4.5
* Add feature: Option to create post excerpt at time of event creation for Event to Post feature.
* Add feature: Provide custom template with My Calendar template tag support in custom post content & excerpt fields.
* Add feature: selectively hide event content based on user permissions. (Requires > My Calendar 2.4.18)
* Add feature: support for recurring events in 'create event from post' feature.
* Update: modify default location fields to simplify default form; eliminate GPS & Region
* Update: Improved UI for location submission from front-end
* Translation: Add Dutch translation

= 1.5.2 =

* Feature: Set categories on posts created from events
* Action: do_action( 'mcp_post_published', $post_id, $event );
* Bug fix: Only add an event from post when the event title is defined.
* Bug fix: Event description field forced overwrite of post content if event not created.

= 1.5.1 =

* Bug fix: Handling of license activation response
* Bug fix: advanced search queries

= 1.5.0 =

* Add event importer by iCAL or CSV
* Bug fix: misc. PHP notices
* Bug fix: currency code in Submission payments reports

= 1.4.0 =

* Adds Advanced Event Search widget
* Adds Responsive Mode UI & backend code.
* Adds custom responsive stylesheets
* Bug fix: Use WordPress installation time as base for current time instead of server time.
* Bug fix: Cleared out stray PHP notices
* Bug fix: Issue with missing database update function
* Bug fix: Saving mcs_time_format did not show saved value
* Bug fix: Select dropdowns display issue on date picker
* Bug fix: Saving payments settings moved focus to Submissions tab
* Bug fix: Advanced search page not generated on save of settings

= 1.3.1 =

* Files were included that were doing existence checking, but existence checker function only existed after includes.
* Undefined index when using custom content
* Pass default value definitions for event recurrence data when creating event from post

= 1.3.0 =

* Enqueue front-end styles in this plug-in, rather than in My Calendar.
* Major feature: Add functionality to send new events as blog posts (or any post type).
* Update Authorize.net API Urls per June 2016 change.
* Incorrect default data type for some widget parameters raised notices.
* Change from jQuery UI to Pickadate
* Add time selector & time format settings
* Create event from post screen (no editing, just creation)
* Edit submitted events in submissions form
* Add field to define location of submit event page
* Add Edit subject line for notifications
* Add advanced search with customizable results template.

Todo for this release:
- Figure out submissions/pro update process. New plug-in with migration path? Do MCS final update with the uninstall.php eliminated, notice for users to download PRO; create download link using their license key.


= 1.2.8 =

* Fix licensing URL.
* Update class constructors to PHP 5 syntax.

= 1.2.7 =

* Bug fix: My Calendar: Submissions widget is now compatible with SiteOrigin PageBuilder.
* Option: require name & email on submissions
* Prevent deprecation notice when fetching text_direction
* Bug fix: My Calendar Manual link was out of date.
* Bug fix: Add _wpnonce to generator function.

= 1.2.6 =

* Add ability to use Host field in My Calendar Submissions (with capability-based filter; defaults to require mc_add_events)
* Add ability to disable email notifications when events are auto approved. [needs testing]
* Bug fix: notice thrown if user login submitted as name.

= 1.2.5 =

* Added several new filters and actions to support greater extensibility.
* Updated licensing conditions.
* Bug fix: Locations with no default location supplied raised PHP warnings

= 1.2.4 =

* Bug fix: notices in shortcode generator
* Bug fix: fatal error in shortcode generator under PHP 5.4+

= 1.2.3 =

* Bug fix: event_allday and event_hide_end values could return date/time results.
* Bug fix: numerous bug fixes to My Calendar widget.
* Bug fix: date popup attached globally to .date class. OMG.
* New feature: Shortcode Generator (My Calendar > Help > Shortcode Generator > "Submissions")
* Supports same label replacement in locations as in event fields
* Set image as featured image for event post.
* Removed the required label from event locations label.
* 

= 1.2.2 =

* Bug fix: Event Title label did not show up unless added as an attribute title.
* Clarification: Provide link to user's guide from settings page.
* New filters: mc_submit_fields and mc_submit_location_fields. Filter value of the 'fields' and 'location_fields' attributes to customize form text without using a complex shortcode attribute.

= 1.2.1 =

* Bug fix: Submission widget not correctly registered.

= 1.2.0 =

* Adds support for Authorize.net DPM as payment processor.
* Adds printable receipts for purchasers.
* Change: if a default location is set & no input method is allowed, default location is used for all submitted events.

= 1.1.1 =

* Updated automatic updater.
* Bug fix: locations preset generated MySQL error if not provided.
* Send emails from sender, not from WordPress default.
* Update to submissions payments table format.

= 1.1.0 =

* Form labels are now customizable. (Excludes recurring events and GPS coordinates.)
* Option to set forms to submit automatically approved events.
* Change: users with 'Manage Events' permissions submitting events through submissions form do not require approval.
* Automatic plug-in upgrade now supported.
* Updated recurring interface to match options in My Calendar 2.2.0
* HTML formatting now available in notification emails
* Added support for file uploads in image field.
* Assigned email notifications as an action so it can be removed or modified.


= 1.0.5 =

* Fixed license validation problem resulting from bug in WP HTTP prior to version 3.3.

= 1.0.4 =

* Shortcode event entry form was not protected when user not logged in. 
* Turned submit_event shortcode into an enclosing shortcode. Enclosed text shown when user not logged in.

= 1.0.3 =

* Bug fix: removing category field broke event submission

= 1.0.2 =

* Bug fix: locations field restrictions did not work
* Bug fix: possible to create an event with a null repetition value

= 1.0.1 = 

* Bug fix which could potentially introduce a 'headers already sent' error in admin.

= 1.0.0 =

* Initial launch.

= Future =

* Feature: limiting of viewing categories to specific member groups
* Feature: limit ability to enter categories by member groups
* Create selectable event details templates 
* Make only portions of events private (e.g., time and location for children's events, etc.)
* Add option to set event alarm or notification in .ics outputs
* Provide notifications of new events by category (to specific users or to lists of users; set up as user meta option?) (use save_event hooks; set up reminder schedule; use cron; see RQ by Don Brown via email)
* Add controls on My Calendar defined page to alter shortcode settings on that page?
* Feature: list of user's submitted events to provide access for editing; build into Submissions form & as separate shortcode
* Feature: restrict access for non-admins to only be able to edit from front-end; no access to WP Admin for calendar.
* Feature: schedule periodic imports by URL
* Add event to personal calendar

== Frequently Asked Questions ==

= Hey! Why don't you have any Frequently Asked Questions here! =

Because the majority of users end up on my web site asking for help anyway -- and it's simply more difficult to maintain two copies of my Frequently Asked Questions. Please visit [my web site FAQ](http://www.joedolson.com/my-calendar/faq/) to read my Frequently Asked Questions!

== Screenshots ==

1. Event Submissions Widget

== Upgrade Notice ==
