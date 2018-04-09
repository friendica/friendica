sample-Lighttpd.config
sample-nginx.config

		Sample configuration files to use Friendica with Lighttpd
		or Nginx. Pleas check software documentation to know how modify
		these examples to make them work on your server.


sample-systemd.timer
sample-systemd.service

		Sample systemd unit files to start worker.php periodically.
		
		Please place them in the correct location for your system,
		typically this is /etc/systemd/system/friendicaworker.timer 
		and /etc/systemd/system/friendicaworker.service.
		Please report problems and improvements to 
		!helpers@forum.friendi.ca and @utzer@social.yl.ms or open an 
		issue in Github (https://github.com/friendica/friendica/issues).
		This is for usage of systemd instead of cron to start the worker.php
		periodically, the solution is work-in-progress and can surely be improved.
