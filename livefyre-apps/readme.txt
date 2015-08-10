=== Livefyre Apps ===
Contributors: Livefyre
Donate link: http://livefyre.com/
Tags: comments, widget, plugin, community, social, profile,
moderation, engagement, twitter, facebook, conversation
Requires at least: 3.9
Tested up to: 4.1
Stable tag: 1.2

From Community to Enterprise, the Livefyre Apps Plugin infuses your website with real-time social content to increase engagement and drive traffic.

== Description ==

= Enterprise =

Livefyre’s StreamHub platform helps brands, media companies and agencies engage consumers through a combination of real-time conversation, content, social curation and advertising. With StreamHub, brands can integrate real-time content into their websites, mobile apps, advertisements, jumbotrons and television broadcasts to increase viewer engagement, boost website traffic and drive revenue.

*Comments™*

Turn every piece of content into a real-time conversation with Comments™. Livefyre leads the market with a robust feature set including truly real-time posts, mobile compatibility, SEO optimized content, social login, listener count and a comment notifier. And with media-rich embedding options, users can post videos, songs, images and more, right into the comment stream.

*Live Blog™*

Become the live news source.

Feature real-time updates and images from your site’s own editors when covering a live event and turn your site into an engaging news source. This is a great option for multiple editors who will be reporting from live events including product reveals, award programs, television premieres, sporting events and press conferences. For the new XBox launch, Gamespot hosted a Live Blog™ that featured photos, live coverage and analysis from designated bloggers on-location. As a result of this engaging real-time coverage, there were over 30,000 listeners on the site during the announcement.

*Chat™*

Spark real-time conversation about live events.

Audiences can engage in real-time conversation surrounding live events, announcements and entertainment shows. Content appears as a continuous, real-time stream of unthreaded conversation to facilitate rapid conversation and maximize energy on a page.

*Sidenotes™*

Focused comments. In context. Anywhere on the page.

Sidenotes inspires more focused conversations by allowing readers to engage directly with content — a quote, a paragraph, an image — anywhere on a page. By inviting readers to interact and share your content as they read, Sidenotes lowers the barrier for engagement, increases time on site and can boost conversational civility.
 
Sidenotes is mobile-ready, SEO optimized, and is included with every subscription to Comments and Community Comments.

= Community =

*Comments*

Livefyre Community Comments replaces your default comments with real-time conversations. Our social integration features make it easy to capture all the conversations going on about your posts across Twitter and Facebook, and pull your friends into the conversation.

*Sidenotes*

Livefyre Sidenotes inspires focused conversations by allowing users to engage directly with content — a quote, a paragraph, an image — anywhere on a page. By allowing readers to interact with and share your content as they read, Sidenotes lowers the barrier for engagement, increases time on site and boosts conversational civility.

== Livefyre JavaScript Events ==

Livefyre Apps allows you to listen to events that occur within the Livefyre widgets, such as widget initialized or comment posted. 

To do this you must add a piece of JavaScript code which tells Livefyre Apps to listen for the given events. Here is an example of a comment posted even listener for the LiveComments widget:

<script type="text/javascript">
    LFAPPS.add_jsevent_listener('livecomments', 'commentPosted', function(data) {
        console.log("commentPosted", data);
    });
</script>

The function "LFAPPS.add_jsevent_listener" takes in the following parameters:
* widget name (possible values: "livecomments", "livechat", "liveblog", "sidenotes")
* event name - you can get the event names from the below links (sidenotes have different event names compared to comments)
* callback function - this function must take in the "data" parameter which will differer depending on the event triggered. You can use this data object to determine the event details. 

Comments, Blog & Chat - http://answers.livefyre.com/developers/reference/javascript-events/
Sidenotes - http://answers.livefyre.com/developers/app-integrations/sidenotes/#AppEvents

N.B: Make sure that you run the "LFAPPS.add_jsevent_listener" function after lfapps.js script has been called. Best place is the footer of the webpage. 

== Installation ==

1. Search for the ‘Livefyre Apps’ plugin in WordPress.org or download the plugin
2. Install plugin through WordPress’s UI
3. Deactivate all previous Livefyre plugins
4. Activate the ‘Livefyre Apps’ plugin
5. Visit the Livefyre Settings page that should appear in the navigation panel
6. Select which version of Livefyre you'll be using
7. Enter in the credentials required on the settings page or if you are a new community user, register your blog throught he register link
8. Turn on Livefyre Apps that you would like to use
9. Visit the App specific settings page to configure each app

== Frequently Asked Questions ==

For Enterprise, check out [Livefyre Docs](http://docs.livefyre.com)

For Community, visit the [Livefyre FAQ](http://support.livefyre.com) or e-mail us at
support@livefyre.com.

== Screenshots ==

== Changelog ==

= 0.1 =
* Official release!
= 1.0 =
* VIP certified! The Apps plugin is now officially supprted by WordPress VIP. Most code changes made to hammer those details out.
* Added support for customized widget strings.
* Added support for overriding article meta data.
* Change some Livefyre Nomenclature.
* Fixed WP logout bug.
* Fixed https bug.
* Fixed app selection bug.
* Fixed broken link.
= 1.1 =
* Patch an issue with Chat colliding with Comments.
* Patch issue with collections not updating when changed in Livefyre Settings

== Upgrade Notice ==

= 1.0 =
Upgrade from previous versions of the Livefyre Comments and Livefyre Sidenotes plugins.

