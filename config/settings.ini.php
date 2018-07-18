<?php return <<<INI

; SETTINGS.INI.PHP

; This file declares the default values for the admin settings of Friendica.
; These values will be overriden by the admin settings page.

; Please don't edit this file directly as its content may change in the upcoming versions.

[config]

; info (String)
; Plaintext description of this node, used in the /friendica module.
info =

; register_policy (Constant)
; Your choices are REGISTER_OPEN, REGISTER_APPROVE, or REGISTER_CLOSED.
; Be certain to create your own personal account before setting REGISTER_CLOSED.
; REGISTER_APPROVE requires you set system.admin_email to the email address of an already registered person who can authorize and/or approve/deny the request.
register_policy = REGISTER_CLOSED

; register_text (String)
; Will be displayed prominently on the registration page.
register_text = ''

; sitename (String)
; Displayed server name.
sitename = "Friendica Social Network"

[system]

; account_abandon_days (Integer)
; Will not waste system resources polling external sites for abandonded accounts.
; Enter 0 for no time limit.
account_abandon_days = 0

; addon (Comma-separated list)
; Manual list of addons which are enabled on this system.
addon =

; allowed_themes (Comma-separated list)
; Themes users can change to in their settings.
allowed_themes = 'quattro,vier,duepuntozero,smoothly'

; default_timezone (String)
; Choose a default timezone. See https://secure.php.net/manual/en/timezones.php
; It only applies to timestamps for anonymous viewers.
default_timezone = UTC

; directory (String)
; URL of the global directory.
directory = https://dir.friendi.social

; forbidden_nicknames (Comma-separated list)
; Prevents users from registering the specified nicknames on this node.
; Default value comprises classic role names from RFC 2142.
forbidden_nicknames = info, marketing, sales, support, abuse, noc, security, postmaster, hostmaster, usenet, news, webmaster, www, uucp, ftp, root, sysop

; jpeg_quality (Integer)
; Sets the ImageMagick quality level for JPEG images. Values ranges from 50 (awful) to 100 (near perfect).
jpeg_quality = 100

; language (String)
; System default languague, inluding admin-created user default language.
; Two-letters ISO 639-1 code.
language = en

; max_image_length (Integer)
; An alternate way of limiting picture upload sizes.
; Specify the maximum pixel  length that pictures are allowed to be (for non-square pictures, it will apply to the longest side).
; Pictures longer than this length will be resized to be this length (on the longest side, the other side will be scaled appropriately).
; If you don't want to set a maximum length, set to -1.
max_image_length = -1

; maximagesize (Integer)
; Maximum size in bytes of an uploaded photo.
maximagesize = 800000

; no_regfullname (Boolean)
; Allow pseudonyms (true) or enforce a space between firstname and lastname in Full name, as an antispam measure (false).
no_regfullname = true

; optimize_max_tablesize (Integer)
; Maximum table size (in MB) for the automatic optimization.
; -1 to disable automatic optimization.
;  0 to use internal default (100MB)
optimize_max_tablesize = -1

; rino_encrypt (Integer)
; Server-to-server private message encryption (RINO).
; Encryption will only be provided if this setting is set to a non zero value on both servers.
; Set to 0 to disable, 2 to enable, 1 is deprecated but wont need mcrypt.
rino_encrypt = 2

; theme (String)
; System theme name.
theme = vier

; url (String)
; The fully-qualified URL of this Friendica node.
; Used by the worker in a non-HTTP execution environment.
url =

; Used in the admin settings to lock certain features
[featurelock]

INI;
// Keep this line
