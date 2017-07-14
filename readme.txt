=== MailChimp Subscribe ===
Contributors: marcqueralt
Tags: mailchimp, email, newsletter, groups, segments, google analytics
Donate link: http://DeMomentSomTres.com
Requires at least: 3.9
Tested up to: 4.7.2
Stable tag: head

== Description ==

The DeMomentSomTres Mailchimp Subscribe plugin manages subscriptions to Mailchimp Lists with and without groups.

This plugin is the perfect companion for [DeMomentSomTres Mailchimp Immediate Send](http://demomentsomtres.com/english/wordpress-plugins/mailchimp-immediate-send/).

You can get more information at [DeMomentSomTres Digital Marketing Agency](http://demomentsomtres.com/english/wordpress-plugins/mailchimp-subscribe/).

= Features =

* Widget to subscribe to an especific list.
* Shortcode to create a page to manage all the subscriptions linked to an email.
* Lists and groups can be renamed.
* Settings allows to prevent list to be shown to the users.
* Google Analytics integration based on events

= History & Raison d’être =

While working for Consorci Administració Oberta de Catalunya we integrated Mailchimp and WordPress to perform RSS Campaigns.

We needed a plugin to manage subscriptions.

After a few days we decided to implement Google Analytics integration.

== Installation ==

This plugin can be installed as any other WordPress plugin.

= Requirements =

* Dependencies are managed by the plugin itself

== Frequently Asked Questions ==

= What is the shortcode syntax? =

The syntax is really simple, without options. Just put `[demomentsomtres-mc-subscription]` where you whant it to be shown.

= Which events are sent to Google Analytics and when =

Each of the buttons used in this plugin send its own event to Google Analytics.

**Widget 'Subscribe' Button**
* Category: dms3mcsubscribe
* Action: subscribe-widget
* Label: listid-email (listid is formatted as list-grouping-group)

**Shortcode 'Verify email' Button**
* Category: dms3mcsubscribe
* Action: verify-email-shortcode
* Label: email

**Shortcode 'Unsubscribe' Button**
* Category: dms3mcsubscribe
* Action: unsubscribe-shortcode
* Label: listid-email

**Shortcode 'Subscribe' Button**
* Category: dms3mcsubscribe
* Action: subscribe-shortcode
* Label: listid-email

== Screenshots ==

TBD

== Changelog ==
= 3.201704281523 = 
* Custom message if widget subscription fails
= 3.201704251008 =
* Bug with TGMPA that did not detect requirements.
* Bug Fatal Error if Titan Framework was not present
= 3.201704 =
* Correct management of apostrophes
* If the user is already subscribed the message must be friendly
= 3.201703012005 =
* Mailchimp library updated
= 3.201702280929 =
* version number updated
= 3.2017022 =
* Mailchimp library update
= 3.20170216 =
* Plugin updated to Mailchimp 3.0
* Internal Mailchimp libraries updated to V3.20170216
* Font Awesome Spinners added
* New version numbering
= 2.0 =
* Plugin recoded
* DeMomentSomTres Tools no required anymore

= 1.3.2 =
* Bug: excesive verbosity in ajax subscribe widget.

= 1.3.1 =
* Catalan translation improvements

= 1.3 =
* Unsubscribe bug - users get unsubscribed from all lists

= 1.2 =
* Google Analytics (analytics.js) event integrations

= 1.1.1 =
* Placeholders added to the form fields

= 1.1 =
* Initial lists shown in form if not email selected

= 1.0.1 =
* Minor language files updates

= 1.0 =
* Initial version translation ready
