Database Tables
===============

* [Home](help)

| Table | Description |
|-------|-------------|
| [2fa_app_specific_password](help/database/db_2fa_app_specific_password) | Two-factor app-specific _password |
| [2fa_recovery_codes](help/database/db_2fa_recovery_codes) | Two-factor authentication recovery codes |
| [2fa_trusted_browser](help/database/db_2fa_trusted_browser) | Two-factor authentication trusted browsers |
| [addon](help/database/db_addon) | registered addons |
| [apcontact](help/database/db_apcontact) | ActivityPub compatible contacts - used in the ActivityPub implementation |
| [application](help/database/db_application) | OAuth application |
| [application-token](help/database/db_application-token) | OAuth user token |
| [attach](help/database/db_attach) | file attachments |
| [cache](help/database/db_cache) | Stores temporary data |
| [config](help/database/db_config) | main configuration storage |
| [contact](help/database/db_contact) | contact table |
| [contact-relation](help/database/db_contact-relation) | Contact relations |
| [conv](help/database/db_conv) | private messages |
| [conversation](help/database/db_conversation) | Raw data and structure information for messages |
| [dcontact](help/database/db_dcontact) | Diaspora compatible contacts - used in the Diaspora implementation |
| [delayed-post](help/database/db_delayed-post) | Posts that are about to be distributed at a later time |
| [diaspora-interaction](help/database/db_diaspora-interaction) | Signed Diaspora Interaction |
| [event](help/database/db_event) | Events |
| [fcontact](help/database/db_fcontact) | Diaspora compatible contacts - used in the Diaspora implementation |
| [fsuggest](help/database/db_fsuggest) | friend suggestion stuff |
| [group](help/database/db_group) | privacy groups, group info |
| [group_member](help/database/db_group_member) | privacy groups, member info |
| [gserver](help/database/db_gserver) | Global servers |
| [gserver-tag](help/database/db_gserver-tag) | Tags that the server has subscribed |
| [hook](help/database/db_hook) | addon hook registry |
| [host](help/database/db_host) | Hostname |
| [inbox-status](help/database/db_inbox-status) | Status of ActivityPub inboxes |
| [intro](help/database/db_intro) |  |
| [item-uri](help/database/db_item-uri) | URI and GUID for items |
| [locks](help/database/db_locks) |  |
| [mail](help/database/db_mail) | private messages |
| [mailacct](help/database/db_mailacct) | Mail account data for fetching mails |
| [manage](help/database/db_manage) | table of accounts that can manage each other |
| [notification](help/database/db_notification) | notifications |
| [notify](help/database/db_notify) | notifications |
| [notify-threads](help/database/db_notify-threads) |  |
| [oembed](help/database/db_oembed) | cache for OEmbed queries |
| [openwebauth-token](help/database/db_openwebauth-token) | Store OpenWebAuth token to verify contacts |
| [parsed_url](help/database/db_parsed_url) | cache for 'parse_url' queries |
| [pconfig](help/database/db_pconfig) | personal (per user) configuration storage |
| [permissionset](help/database/db_permissionset) |  |
| [photo](help/database/db_photo) | photo storage |
| [post](help/database/db_post) | Structure for all posts |
| [post-category](help/database/db_post-category) | post relation to categories |
| [post-content](help/database/db_post-content) | Content for all posts |
| [post-delivery-data](help/database/db_post-delivery-data) | Delivery data for items |
| [post-link](help/database/db_post-link) | Post related external links |
| [post-media](help/database/db_post-media) | Attached media |
| [post-tag](help/database/db_post-tag) | post relation to tags |
| [post-thread](help/database/db_post-thread) | Thread related data |
| [post-thread-user](help/database/db_post-thread-user) | Thread related data per user |
| [post-user](help/database/db_post-user) | User specific post data |
| [post-user-notification](help/database/db_post-user-notification) | User post notifications |
| [process](help/database/db_process) | Currently running system processes |
| [profile](help/database/db_profile) | user profiles data |
| [profile_field](help/database/db_profile_field) | Custom profile fields |
| [push_subscriber](help/database/db_push_subscriber) | Used for OStatus: Contains feed subscribers |
| [register](help/database/db_register) | registrations requiring admin approval |
| [search](help/database/db_search) |  |
| [session](help/database/db_session) | web session storage |
| [storage](help/database/db_storage) | Data stored by Database storage backend |
| [subscription](help/database/db_subscription) | Push Subscription for the API |
| [tag](help/database/db_tag) | tags and mentions |
| [user](help/database/db_user) | The local users |
| [user-contact](help/database/db_user-contact) | User specific public contact data |
| [userd](help/database/db_userd) | Deleted usernames |
| [verb](help/database/db_verb) | Activity Verbs |
| [worker-ipc](help/database/db_worker-ipc) | Inter process communication between the frontend and the worker |
| [workerqueue](help/database/db_workerqueue) | Background tasks queue entries |
