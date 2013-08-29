=== Livefyre Comments 3 ===
Contributors: Livefyre, Mike Soldner, Michael Liao, Chris Becker
Donate link: http://livefyre.com/
Tags: comments, widget, community, social, profile, moderation, engagement, twitter, facebook, conversation, avatars, email notification, spam protection, rich-text, sharing
Requires at least: 2.8
Tested up to: 3.5
Stable tag: 4.1.0

Livefyre Comments 3 replaces your default comments with real-time conversations. Our social integration features make it easy to capture all the conversations going on around your content across Twitter and Facebook, while pulling your friends into the conversation.

== Description ==

Livefyre Comments 3 is a comment platform for real-time conversations. Comments 3 centralizes conversations from around the social web back to your site, and
encouragwa engagement between readers on your site to build community around your content. 

Livefyre’s Admin dashboard allows you to track and monitor every interaction on your site from one location, in real-time. Filter by comment, network or user, and moderate with ease. We make it seamless to maintain high quality conversation on your content.

The Livefyre Comments 3 plugin connects your WordPress database to the Livefyre Network writes all of your comments back to your WP database in real-time.

== Livefyre Comments 3 for WordPress ==

=Livefyre Comments 3=

Livefyre Comments 3 was designed from the ground-up for the social web, turning your blog into the hub of engagement and giving you the tools to moderate real-time conversations.

=Features=
- Real-time technology: Comments is built on XMPP chat technology for fast, lightweight conversations on your site.
- Social Sync: the conversation happening around your articles on Facebook and Twitter automatically syncs directly to the comments on your site.
- Social Signin: Users have the option to sign in with several social networks so that they can jump right in and join the conversation. No new login required.
- Guest Commenting: Sites can also use our guest commenting option to let their community comment without even creating an account.
- Friend Tagging: Easily invite Facebook, Twitter, and Livefyre contacts to join the conversation right from the comment box. 
- Comment Sharing: With one click, readers can easily share comments with their contacts on Facebook, Twitter and LinkedIn.
- Comment Liking: Let your community members recognize those commenters who elevate the conversation. 
- Rich Text Editor: Emphasize your point of view in flourishes of bold italics, or create a bulleted list to drive home your argument.
- LinkBack: Encourage community interaction by allowing other Livefyre bloggers display a link to their latest post when they leave a comment.
- Live Listeners: Show readers exactly how many people are viewing the page and are following the conversation. 
- Comment Notifiers: Show your readers who is participating in the conversation and what they are saying in real-time, all without losing your place on the page.
- Media Embedding: Display Instagram photos, play videos from YouTube, listen to songs from Spotify, and cite Wikipedia articles directly in the conversation stream.
- Comment Editing: Commenters can fix typos mistakes with a five-minute window to edit their comment after it has been posted.
- User Profiles:  Visit the other Livefyre Network sites where your community is commenting.
- SEO Optimization: Comments 3 is Google crawl-able so you receive SEO credit for all comments, including those originating from Facebook & Twitter.

=Admin Dashboard=

Livefyre’s centralized dashboard allows you to track and monitor every interaction on your site from one location. 

=Features=
- Spam Protection: Imperium spam filtering keeps the conversation going and the spam out.
- Community Flagging: Develop trust with community members by letting them notify you when a comment is offensive or off topic. 
- Edit Comments: Moderators can edit all comments to correct spelling and grammar, and make sure that shared links and embedded media keep the conversation clear and relevant.
- Leave Comment Notes: No need to leave your admin panel to coordinate with your moderators, see all your communication around comments in one place.
- Multiple Moderation roles: Delegate moderation responsibilities among Site Owners, Admins, Moderators, and  
- Whitelists & Banned Users: Authors and trusted community members can bypass pre-moderation with Whitelists, while trolls are kept at bay with Ban lists and their comments are Bozo'ed so that they are only visible to the comment author and not your community.
- Profanity Lists: Customize the settings for filtering comments for your community. 
- User Activity: See which community members are your top commenters and who you should reach out to invite them back to your site.
- Moderation & Conversation Reports: Generate reports that give an overview of your moderation practices and see which posts are sparking the most conversations.

For more info check out [Livefyre's full feature
list](http://livefyre.com/comments).

== Installation ==

1) Download the Livefyre Community Comments plugin file.
2) Log in to your WP Admin panel and click on Plugins from the Admin menu on the left.
3) Deactivate any third party comment plugins you may have previously installed from the Plugins menu.
4) Click on Add New from the Plugins menu.
5) Click on Upload and choose the Livefyre.zip plugin file, and then click Install Now.
6) When the automated installation has completed, click Activate Plugin.
7) At the top of the Plugins screen you'll see a message box prompting you to confirm your blog configuration with livefyre.com - click on this link and sign in to your Livefyre account, or create a new account if needed.
8) Once you Sign in or create a new account Livefyre will begin importing comments from your WP database into the Livefyre system. We always write your comments back to your database as they are made, so you don't have to worry about any re-imports or exports of your comments.

Our [Livefyre Comments 3 Knowledge Base](http://support.livefyre.com/customer/portal/articles/163187-how-do-i-install-community-comments-on-my-wordpress-blog-) has more information about installation.

== FAQ ==
= What is the difference between Community Comments and LiveComments?=

[Livefyre Community Comments](http://www.livefyre.com/comments/) is our free comments plugin for bloggers and small publishers. [LiveComments](http://www.livefyre.com/streamhub/#liveComments) is part of our paid [StreamHub product suite](http://www.livefyre.com/streamhub/) for large publishers. Community Comments runs on the Livefyre network, so you will need a Livefyre account to leave a comment. Each our LiveComments customers run on their own custom network, so you will need an account with that network to leave a comment.  

= Why doesn't my Livefyre plugin work? =

The Livefyre Community Comments plugin depends on a couple different WordPress conventions to work properly. Here are the most common reasons why Livefyre commenting widget does not show up.

1. You are lacking wp_footer() function in your footer.php of your WordPress theme.
 You can add it to your theme by editing your footer.php file, then appending <?php wp_footer() ?> on a new line at the end of the file.

2. Your theme is not using the comments_template() function to load comments.
 The Livefyre Community Comments plugin hooks onto the comments_template() function to insert Community Comments onto the page. Look at the default theme to see how it is implemented and add it into your theme accordingly. You will need to add <?php comments_template() ?> where you want Community Comments to appear on the page.

3. The post or page in question has comments disabled.
 Community Comments will only show up on WordPress posts and pages that have comments enabled. If you go to the Livefyre Settings page in the WordPress admin, you will see a list of post and pages which have comments disabled.

4. You have turned off "Display Posts" or "Display Pages" in the Livefyre Settings page.
 There are options to enable or disable Community Comments on all posts or all pages. If you are missing comments on all your pages, please make sure the Display Pages is checked in the settings page. By default, comments are enabled on posts and pages.

= What are all of these transient_livefyre values in my WordPress options table? =

By default, the Community Comments plugin will cache the static HTML of the comment widget for every page and post Community Comments appears on into transient values in the WordPress options table for performance purposes. These values are temporary, and will be deleted by WordPress cron jobs periodically. You can turn off caching in the Livefyre Settings page, but doing so will incur a sizable performance penalty. You can also delete all transient_livefyre values in the Livefyre Settings page.

= Can I change the language of the Community Comments module? =

Yes. With new feature in v4.1.0, plugin site owners can now choose which language to display on the comment module. Currently, site owners can choose between English, French, Spanish, and Brazilian Portuguese.

Visit the [Livefyre FAQ](http://support.livefyre.com) to access our entire Knowledge Base, or e-mail us at
support@livefyre.com with any questions you have.

== Changelog ==
= 4.1.0 =
* Add in support for French, Spanish, and Portuguese.
* Allow users to toggle caching and give them the ability to clear the cache through the settings page.
= 4.0.7 = 
* Fix for legacy values of import status affecting the settings page
= 4.0.6 =
* Changed comments div name to livefyre-comments to avoid theme code conflicts.
* Check for comment before running update_comment_status in postback.
* Skip comment import for sites with no comments.
* More specific instructions for how to fix CloudFlare in conflicting plugins.
= 4.0.5 =
* Removed potential naming conflicts in the livefyre-api folder.
* Fixed bug with LF JS not being put out to the page.
* Changed debugging messages for Livefyre not displaying.
* Restored some multi-site functionality.
= 4.0.4 =
* Fixed multi-site blog registration.
* Bumped up priority of the comments widget to support certain themes.
= 4.0.3 =
* Removed security plugins acting up with Raven.
* Added some more CSS in the settings page.
* Removed potential naming conflicts.
* Outputs an invisable div to tell us if we shouldn't be displaying for debugging purposes.
* Fresh installs won't activate a backfill.
* Fixed small postback issue.
= 4.0.2 =
* Changed a option to automatically be set to true.
= 4.0.1 =
* Added an ability to turn off comments on pages or posts with a checkbox.
* Renamed Logger to Livefyre_Logger to avoid a common name.
* Fixed an update option to be more compatible with older code.
= 4.0.0 =
* Implemented the Comments 3 platform for all conversations.
* Added a backend upgrade to migrate old conversations to the Comments 3 platform.
* Increased the reliability of site syncing by making a check for it's scheduling.
* Added debugging features in the WP logs if debug mode is on.
* Updated and redesigned admin settings page.
* Added useful links in the settings page.
* Fixed issues with importing of comments to warn or potential problems.
= 3.52 =
* Fixes some issues with comment count, increases site sync request timeout to improve reliability.
= 3.50 =
* Implements the Livefyre V3 comments platform Beta.
= 3.12 =
* Added a validator which tests the UTF cleaner before applying it.  If it fails to return the string that was passed in, we turn off the UTF cleaner.  This will fix the import process for a number of blogs where iconv is broken or other encoding issues exist in the target environment.
= 3.11 =
* Fixing an issue with postback parent comments not being associated - which caused replies to appear at the top level in the WordPress comments section when livefyre is turned off.
= 3.10 =
* Skip invalid dates - the importer will choose a date (that of the article) if WordPress can't supply a valid date on export.
= 3.09 =
* Suppress comment changes to author fields when an approval activity is posted back to the plugin.
= 3.08 =
* Fix very old bug with livefyre_get_wp_comment_id (does not return!) this fixes postback in some cases.
* Add signature to sync request url.
* Uses the new www.livefyre.com domain to avoid costly redirects.
= 3.07 =
* Fixed misconfigured Livefyre domain for bootstrap html fetching.
= 3.06 =
* Added a deactivate action to reset the status of the import process.  This prevents upgrading an old plugin (in an inconsistent state) and erroneously getting the admin notification "we're still importing your comments..."
* Added testing for iconv() support for before attempting to sanitize comments that are being exported (to Livefyre)
= 3.05 =
* Added better messaging during import process: * notifying users of the fact that a job is queued, when it is * better display when an error is encountered * allow users to come back and see continuously updated import status via the Livefyre link under Comments (WordPress Admin) * better messaging in admin dashboard during import
* Added a unicode-cleansing character filter as per the spec http://www.w3.org/TR/xml/#charsets.  This resolves a rare issue where very old (upgraded) WordPress blogs sent Livefyre invalid characters during data export.
= 3.04 =
* Handling quotes better for postback, as using the correct "init" hook causes WordPress to unilaterally escape all quotes in $_POST.  This fixes broken postback in a number of cases.
= 3.03 =
* Moving postback hook into the more appropriate "init" wp hook for better performance
= 3.02 =
* Fixing syntax that was incompatible with php 4.x.
= 3.01 =
* New platform release with updated postback synchronization.
= 2.41 =
* Fixed bug where livefyre css loads on every page (which made pingdom claim that every image in the css, whether loaded or not, was being fetched on page load.  LIES!)
= 2.40 =
* Fixed bug related to load order changes in the Livefyre streaming library.
= 2.39 =
* Fixed bug on upgrade to 3.0.3 that caused a permission error on activation (or re-activation) of Livefyre.
= 2.38 =
* Corrected use of 'siteurl' to 'home' instead when obtaining the site's base url for web service endpoints in the plugin.
= 2.37 =
* Added 'copy my comments' button for those who decide to import or who need to sync comments.  Unfortunately until our 'full sync' solution is complete, the button is kind of dumb and is there all the time.  It can't hurt tho - we'll never duplicate a comment thats already in the livefyre system.
* Added automated test for wp_head() template hook, to proactively notify users of compatibility issues.
= 2.36 =
* Improved the automated importer - it now limits the maximum number of queries to run for one chunk of xml (20) in addition to limiting the number of characters that are allowed in one chunk.
* Improved the automated importer - using local dates where GMT is unavailable on very old articles, presumably from versions of WP that didn't track gmt.
* Added a 'copy my comments' button to the options page for users who opted out of automatic import on the initial registration step.
= 2.33 =
* Added automatic comment sync for users who deactivate, then collect more comments in the wp db, then re-activate Livefyre.
= 2.31 =
* Securing the export process with a signature.
= 2.27 =
* Changes to ignore bogus/zero import dates.
= 2.26 =
* Adding phone-home on activation/deactivation of plugin, we now store the status on a Livefyre server for debugging purposes.
* Adding large blog import support - we now use chunk files with a central index delivered to Livefyre.com instead of one giant XML file of arbitrary length (regularly growing to the point of exhausting RAM).
* Making the livefyre interface behave correctly on pages (eg 'About') as well as posts.
* Not showing the livefyre interface on preview mode - this was breaking the title grabber.
* Only showing approved pingbacks/trackbacks.
= 2.25 =
* Excluding pingback and trackback data from comment data import. Removed unnecessary extra call to Livefyre server on successful authentication on the plugin options page.
= 2.24 =
* Added cache reset calls to reset wp-cache and WP Super Cache plugins
= 2.22 =
* Shows trackbacks
= 2.20 =
* Copies comments to WordPress database.
