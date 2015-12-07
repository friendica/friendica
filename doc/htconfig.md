Config values that can only be set in .htconfig.php
===================================================

There are some config values that haven't found their way into the administration page. This has several reasons. Maybe they are part of a 
current development that isn't considered stable and will be added later in the administration page when it is considered safe. Or it triggers 
something that isn't expected to be of public interest. Or it is for testing purposes only.

**Attention:** Please be warned that you shouldn't use one of these values without the knowledge what it could trigger. Especially don't do that with 
undocumented values.

The header of the section describes the category, the value is the parameter. Example: To set the directory value please add this 
line to your .htconfig.php:

    $a->config['system']['directory'] = 'http://dir.friendi.ca';



## Jabber ##
* debug (Boolean) - Enable debug level for the jabber account synchronisation.
* logfile - Logfile for the jabber account synchronisation.

## System ##

* birthday_input_format - Default value is "ymd".
* block_local_dir (Boolean) - Blocks the access to the directory of the local users.
* default_service_class - 
* delivery_batch_count - Number of deliveries per process. Default value is 1. (Disabled when using the worker)
* diaspora_test (Boolean) - For development only. Disables the message transfer.
* directory - The path to global directory. If not set then "http://dir.friendi.ca" is used.
* disable_email_validation (Boolean) - Disables the check if a mail address is in a valid format and can be resolved via DNS.
* disable_url_validation (Boolean) - Disables the DNS lookup of an URL.
* event_input_format - Default value is "ymd".
* ignore_cache (Boolean) - For development only. Disables the item cache.
* like_no_comment (Boolean) - Don't update the "commented" value of an item when it is liked.
* local_block (Boolean) - Used in conjunction with "block_public".
* local_search (Boolean) - Blocks the search for not logged in users to prevent crawlers from blocking your system.
* max_contact_queue - Default value is 500.
* max_batch_queue - Default value is 1000.
* no_oembed (Boolean) - Don't use OEmbed to fetch more information about a link.
* no_oembed_rich_content (Boolean) - Don't show the rich content (e.g. embedded PDF).
* no_smilies (Boolean) - Don't show smilies.
* no_view_full_size (Boolean) - Don't add the link "View full size" under a resized image.
* optimize_items (Boolean) - Triggers an SQL command to optimize the item table before expiring items.
* ostatus_poll_timeframe - Defines how old an item can be to try to complete the conversation with it.
* paranoia (Boolean) - Log out users if their IP address changed.
* permit_crawling (Boolean) - Restricts the search for not logged in users to one search per minute.
* png_quality - Default value is 8.
* proc_windows (Boolean) - Should be enabled if Friendica is running under Windows.
* proxy_cache_time - Time after which the cache is cleared. Default value is one day.
* pushpoll_frequency - 
* qsearch_limit - Default value is 100.
* relay_server - Experimental Diaspora feature. Address of the relay server where public posts should be send to. For example https://podrelay.net
* relay_subscribe (Boolean) - Enables the receiving of public posts from the relay. They will be included in the search and on the community page when it is set up to show all public items.
* relay_scope - Can be "all" or "tags". "all" means that every public post should be received. "tags" means that only posts witt selected tags should be received.
* relay_server_tags - Comma separated list of tags for the "tags" subscription (see "relay_scrope")
* relay_user_tags (Boolean) - If enabled, the tags from the saved searches will used for the "tags" subscription in addition to the "relay_server_tags".
* remove_multiplicated_lines (Boolean) - If enabled, multiple linefeeds in items are stripped to a single one.
* show_unsupported_addons (Boolean) - Show all addons including the unsupported ones.
* show_unsupported_themes (Boolean) - Show all themes including the unsupported ones.
* throttle_limit_day - Maximum number of posts that a user can send per day with the API.
* throttle_limit_week - Maximum number of posts that a user can send per week with the API.
* throttle_limit_month - Maximum number of posts that a user can send per month with the API.
* wall-to-wall_share (Boolean) - Displays forwarded posts like "wall-to-wall" posts.
* worker (Boolean) - (Experimental) Use the worker system instead of calling several background processes. Reduces the overall load and speeds up item delivery.
* worker_dont_fork (Boolean) - if enabled, the workers are only called from the poller process. Useful on systems that permit the use of "proc_open".
* worker_queues - Number of parallel workers. Default value is 10 queues.
* xrd_timeout - Timeout for fetching the XRD links. Default value is 20 seconds.

## service_class ##

* upgrade_link - 

## experimentals ##

* exp_themes (Boolean) - Show experimental themes as well.

## theme ##

* hide_eventlist (Boolean) - Don't show the birthdays and events on the profile and network page

# Administrator Options #

Enabling the admin panel for an account, and thus making the account holder
admin of the node, is done by setting the variable

    $a->config['admin_email'] = "someone@example.com";

where you have to match the email address used for the account with the one you
enter to the .htconfig file. If more then one account should be able to access
the admin panel, seperate the email addresses with a comma.

    $a->config['admin_email'] = "someone@example.com,someonelese@example.com";

If you want to have a more personalized closing line for the notification
emails you can set a variable for the admin_name.

    $a->config['admin_name'] = "Marvin";

