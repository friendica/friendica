# Database tables

| Table | Description |
|-------|-------------|
| [2fa_app_specific_password](help/spec/database/db-2fa_app_specific_password) | Two-factor app-specific _password |
| [2fa_recovery_codes](help/spec/database/db-2fa_recovery_codes) | Two-factor authentication recovery codes |
| [2fa_trusted_browser](help/spec/database/db-2fa_trusted_browser) | Two-factor authentication trusted browsers |
| [account-suggestion](help/spec/database/db-account-suggestion) | Account suggestion |
| [account-user](help/spec/database/db-account-user) | Remote and local accounts |
| [activity](help/spec/database/db-activity) |  |
| [apcontact](help/spec/database/db-apcontact) | ActivityPub compatible contacts - used in the ActivityPub implementation |
| [application](help/spec/database/db-application) | OAuth application |
| [application-marker](help/spec/database/db-application-marker) | Timeline marker |
| [application-token](help/spec/database/db-application-token) | OAuth user token |
| [arrived-activity](help/spec/database/db-arrived-activity) | Id of arrived activities |
| [attach](help/spec/database/db-attach) | file attachments |
| [cache](help/spec/database/db-cache) | Stores temporary data |
| [channel](help/spec/database/db-channel) | User defined Channels |
| [channel-post](help/spec/database/db-channel-post) | Posts in a user defined channel |
| [check-full-text-search](help/spec/database/db-check-full-text-search) | Check for a full text search match in user defined channels before storing the message in the system |
| [config](help/spec/database/db-config) | main configuration storage |
| [contact](help/spec/database/db-contact) | contact table |
| [contact-relation](help/spec/database/db-contact-relation) | Contact relations |
| [conv](help/spec/database/db-conv) | private messages |
| [delayed-post](help/spec/database/db-delayed-post) | Posts that are about to be distributed at a later time |
| [delivery-queue](help/spec/database/db-delivery-queue) | Delivery data for posts for the batch processing |
| [diaspora-contact](help/spec/database/db-diaspora-contact) | Diaspora compatible contacts - used in the Diaspora implementation |
| [diaspora-interaction](help/spec/database/db-diaspora-interaction) | Signed Diaspora Interaction |
| [endpoint](help/spec/database/db-endpoint) | ActivityPub endpoints - used in the ActivityPub implementation |
| [event](help/spec/database/db-event) | Events |
| [fetch-entry](help/spec/database/db-fetch-entry) |  |
| [fetched-activity](help/spec/database/db-fetched-activity) | Id of fetched activities |
| [fsuggest](help/spec/database/db-fsuggest) | friend suggestion stuff |
| [group](help/spec/database/db-group) | privacy circles, circle info |
| [group_member](help/spec/database/db-group_member) | privacy circles, member info |
| [gserver](help/spec/database/db-gserver) | Global servers |
| [gserver-tag](help/spec/database/db-gserver-tag) | Tags that the server has subscribed |
| [hook](help/spec/database/db-hook) | addon hook registry |
| [inbox-entry](help/spec/database/db-inbox-entry) | Incoming activity |
| [inbox-entry-receiver](help/spec/database/db-inbox-entry-receiver) | Receiver for the incoming activity |
| [inbox-status](help/spec/database/db-inbox-status) | Status of ActivityPub inboxes |
| [intro](help/spec/database/db-intro) |  |
| [item-uri](help/spec/database/db-item-uri) | URI and GUID for items |
| [key-value](help/spec/database/db-key-value) | A key value storage |
| [locks](help/spec/database/db-locks) |  |
| [mail](help/spec/database/db-mail) | private messages |
| [mailacct](help/spec/database/db-mailacct) | Mail account data for fetching mails |
| [manage](help/spec/database/db-manage) | table of accounts that can manage each other |
| [notification](help/spec/database/db-notification) | notifications |
| [notify](help/spec/database/db-notify) | [Deprecated] User notifications |
| [notify-threads](help/spec/database/db-notify-threads) |  |
| [openwebauth-token](help/spec/database/db-openwebauth-token) | Store OpenWebAuth token to verify contacts |
| [parsed_url](help/spec/database/db-parsed_url) | cache for 'parse_url' queries |
| [pconfig](help/spec/database/db-pconfig) | personal (per user) configuration storage |
| [permissionset](help/spec/database/db-permissionset) |  |
| [photo](help/spec/database/db-photo) | photo storage |
| [post](help/spec/database/db-post) | Structure for all posts |
| [post-activity](help/spec/database/db-post-activity) | Original remote activity |
| [post-category](help/spec/database/db-post-category) | post relation to categories |
| [post-collection](help/spec/database/db-post-collection) | Collection of posts |
| [post-content](help/spec/database/db-post-content) | Content for all posts |
| [post-counts](help/spec/database/db-post-counts) | Original remote activity |
| [post-delivery](help/spec/database/db-post-delivery) | Delivery data for posts for the batch processing |
| [post-delivery-data](help/spec/database/db-post-delivery-data) | Delivery data for items |
| [post-engagement](help/spec/database/db-post-engagement) | Engagement data per post |
| [post-history](help/spec/database/db-post-history) | Post history |
| [post-link](help/spec/database/db-post-link) | Post related external links |
| [post-media](help/spec/database/db-post-media) | Attached media |
| [post-media-exif](help/spec/database/db-post-media-exif) | Exif data for attached media, see https://exiv2.org/tags.html |
| [post-origin](help/spec/database/db-post-origin) | Posts from local users |
| [post-question](help/spec/database/db-post-question) | Question |
| [post-question-option](help/spec/database/db-post-question-option) | Question option |
| [post-quote](help/spec/database/db-post-quote) | Quotes |
| [post-searchindex](help/spec/database/db-post-searchindex) | Content for all posts |
| [post-tag](help/spec/database/db-post-tag) | post relation to tags |
| [post-thread](help/spec/database/db-post-thread) | Thread related data |
| [post-thread-user](help/spec/database/db-post-thread-user) | Thread related data per user |
| [post-user](help/spec/database/db-post-user) | User specific post data |
| [post-user-notification](help/spec/database/db-post-user-notification) | User post notifications |
| [process](help/spec/database/db-process) | Currently running system processes |
| [profile](help/spec/database/db-profile) | user profiles data |
| [profile_field](help/spec/database/db-profile_field) | Custom profile fields |
| [register](help/spec/database/db-register) | registrations requiring admin approval |
| [report](help/spec/database/db-report) |  |
| [report-post](help/spec/database/db-report-post) | Individual posts attached to a moderation report |
| [report-rule](help/spec/database/db-report-rule) | Terms of service rule lines relevant to a moderation report |
| [search](help/spec/database/db-search) |  |
| [session](help/spec/database/db-session) | web session storage |
| [storage](help/spec/database/db-storage) | Data stored by Database storage backend |
| [subscription](help/spec/database/db-subscription) | Push Subscription for the API |
| [system-channel-post](help/spec/database/db-system-channel-post) | Posts in a system channel |
| [tag](help/spec/database/db-tag) | tags and mentions |
| [user](help/spec/database/db-user) | The local users |
| [user-contact](help/spec/database/db-user-contact) | User specific public contact data |
| [user-gserver](help/spec/database/db-user-gserver) | User settings about remote servers |
| [userd](help/spec/database/db-userd) | Deleted usernames |
| [verb](help/spec/database/db-verb) | Activity Verbs |
| [worker-ipc](help/spec/database/db-worker-ipc) | Inter process communication between the frontend and the worker |
| [workerqueue](help/spec/database/db-workerqueue) | Background tasks queue entries |
