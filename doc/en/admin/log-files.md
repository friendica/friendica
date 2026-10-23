# Reading the log file

This page explains how to make sense of the Friendica log file once you have found an entry that looks interesting, for example an error, a warning, or something a helper on the support channels asked you to look up.

## Step 1: Raise the log level

If you are trying to reproduce a problem, first raise the log level to *Debug*.
You can do this in the admin panel under *Logs* -> *Settings*.
The same page lets you set the path of the log file.
See [Settings & Admin Panel](help/admin/settings#logs) for the full list of available levels.

Debug level produces a lot of output, so switch it back to a lower level once you have what you need.

## Step 2: Identify the kind of process

Every line starts with a timestamp, followed by the name of the process that wrote it.
There are four kinds of processes:

- `app`: a request handled by the web server, i.e. someone visiting or using your node through their browser or an app
- `daemon`: the daemon process that starts worker processes, if you run workers this way instead of via cron
- `worker`: a worker process, executing one background task
- `jetstream`: the daemon that keeps the connection to the AT Protocol (Bluesky) firehose open

```
2026-01-05T03:12:09Z app [DEBUG]: Known frontend found - accept {"isCrawler":false,"agent":"Mozilla/5.0 (X11; Linux x86_64; rv:130.0) Gecko/20100101 Firefox/130.0","method":"GET","uri":"/network?order=commented&p=1","parts":[]} - {"file":"blockbot.php","line":73,"function":"blockbot_init_1","request-id":"3e4f5a6b7c8d9","stack":"blockbot_init_1 (73), Hook::callSingle (207), Hook::callAll (183), HookEventBridge::callHook (563), HookEventBridge::onNamedEvent (275), EventDispatcher::callListeners (206), EventDispatcher::dispatch (56), EventDispatcher::dispatch (35), App::runFrontend (478), App::processRequest (190)","uid":"8a9b0c","process_id":471003}

2026-01-05T03:12:06Z daemon [INFO]: Executed "proc_open" {"command":"'/usr/bin/php' 'bin/console.php' 'worker'"} - {"file":"System.php","line":192,"function":"run","request-id":"4f5a6b7c8d9e0","stack":"System::run (192), Worker::spawnWorker (1222), Daemon::Friendica\\Console\\{closure} (157), Daemon::start (125), Daemon::doExecute (137), Console::execute (86), Console::doExecute (172), Console::execute (86), App::processConsole (233)","uid":"c1d2e3","process_id":483918}

2026-01-05T03:12:07Z worker [DEBUG]: DELETE FROM `process` WHERE (`pid` = 483920 AND `hostname` = 'node1.example.com') {"worker_id":"a1b2c3d","worker_cmd":null} - {"file":"Database.php","line":1265,"function":"delete","request-id":"9f8e7d6c5b4a3","stack":"Database::delete (203), Process::delete (78), Worker::doExecute (90), Console::execute (86), Console::doExecute (172), Console::execute (86), App::processConsole (233)","uid":"1a2b3c","process_id":483920}

2026-01-05T03:12:08Z jetstream [NOTICE]: Pid wasn't found {"worker_id":"7f6e5d4","worker_cmd":null} - {"file":"Daemon.php","line":140,"function":"isRunning","request-id":"1c2d3e4f5a6b7","stack":"Daemon::isRunning (140), JetstreamDaemon::doExecute (135), Console::execute (86), Console::doExecute (172), Console::execute (86), App::processConsole (233)","uid":"4d5e6f","process_id":483925}
2026-01-05T03:12:08Z jetstream [NOTICE]: starting daemon {"pid":null,"pidfile":"/var/www/example.com/tmp/jetstream.pid","worker_id":"7f6e5d4","worker_cmd":null} - {"file":"Daemon.php","line":83,"function":"start","request-id":"2d3e4f5a6b7c8","stack":"Daemon::start (83), JetstreamDaemon::doExecute (143), Console::execute (86), Console::doExecute (172), Console::execute (86), App::processConsole (233)","uid":"4d5e6f","process_id":483925}
```

## Step 3: Find the surrounding context

A single line is rarely enough to understand what happened, you usually want to see the other lines that belong to the same request or the same background job.
Which field ties those lines together depends on the kind of process.

For `app` and `daemon` entries, use `request-id`.
Every line that was written while handling one HTTP request, or one daemon action, shares the same `request-id`.

For `worker` and `jetstream` entries, `request-id` is not useful, since a worker or Jetstream process keeps the same `request-id` for its entire lifetime while working through many unrelated tasks one after another.
Use `worker_id` instead, it changes with every task and lets you pick out the entries that belong to just one of them.

If a process is causing unusually high CPU or I/O load, `process_id` lets you find everything that particular operating system process did, across whatever it was working on at the time.

## Step 4: Look up file, line and call stack

Three fields point you directly at the code that produced the entry:

- `file`: the PHP file that logged the message
- `line`: the line number of the log call in that file
- `function`: the function or method that logged it

The `stack` field goes further and lists the whole call chain that led there, innermost call first, each with its own line number:

```
"stack":"HttpClient::request (185), HttpClient::post (233), ATProtocol::post (227), ATProtocol::refreshUserToken (656), ATProtocol::getUserToken (641), ATProtocol::XRPCGet (131), Actor::syncContacts (58), Jetstream::syncContacts (197), Jetstream::listen (101), JetstreamDaemon::Friendica\\Console\\{closure} (144), Daemon::start (125), JetstreamDaemon::doExecute (143), Console::execute (86), Console::doExecute (172), Console::execute (86)"
```

This is often the fastest way to understand why a piece of code was reached at all, without having to trace it by hand.

## Filtering the log with grep

The log file quickly grows too large to read from top to bottom, so filter it for the value you found in step 3.
Since the fields are JSON, include the quotes to avoid matching on the value alone:

```console
$ grep '"request-id":"3e4f5a6b7c8d9"' friendica.log
$ grep '"worker_id":"7f6e5d4"' friendica.log
$ grep '"process_id":483920' friendica.log
```

`process_id` has no quotes around its value in the log, since it is a number rather than a string.

If you only know part of a value, or want to search across several rotated and compressed log files at once, `zgrep` works the same way on `.gz` files:

```console
$ zgrep '"worker_id":"7f6e5d4"' friendica.log*.gz
```

## File permissions

The log file needs to be writable by two different processes: the web server user, which writes the `app` entries, and whichever user runs the workers and daemons from the command line or cron, which writes everything else.
If both belong to a common group, you can make the file group-writable and add both users to that group, so neither process needs to run as the other.

## Log rotation

Once logging is enabled, the file can grow quickly, especially at *Debug* level.
Set up [log rotation](help/admin/tools#log+rotation) to keep it in check.
`logrotate`'s `create` directive is also a convenient place to set the file's owner, group and permissions after each rotation, so you do not have to fix them by hand every time.
