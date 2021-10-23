## Friendica\Util\Logger

This namespace contains the different implementations of a Logger.

### Configuration guideline

The following settings are possible for `logger_config`:
-	`monolog`: A Logging framework with lots of additions (see [Monolog](https://github.com/Seldaek/monolog/)). There are just Friendica additions inside the Monolog directory
-	[`stream`](StreamLogger.php): A small logger for files or streams
-	[`syslog`](SyslogLogger.php): Prints the logging output into the syslog

[`VoidLogger`](VoidLogger.php) is a fallback logger without any function if no debugging is enabled.

[`ProfilerLogger`](ProfilerLogger.php) is a wrapper around an existing logger in case profiling is enabled for Friendica.
Every log call will be saved to the `Profiler` with a timestamp.

### Implementation guideline

Each logging implementation should pe capable of printing at least the following information:
-	An unique ID for each Request/Call
-	The process ID (PID)
-	A timestamp of the logging entry
-	The critically of the log entry
-	A log message
-	A context of the log message (f.e which user)

If possible, a Logger should extend [`AbstractLogger`](AbstractLogger.php), because it contains additional, Friendica specific business logic for each logging call.
