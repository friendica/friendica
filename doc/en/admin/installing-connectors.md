# Installing Connectors

Friendica uses add-ons to connect to some networks, such as Tumblr or Bluesky.

All of these add-ons require an account on the target network.
In addition, you (or usually the server administrator) will need to obtain an API key to allow authenticated access to your Friendica server.

## Site configuration

Addons need to be installed by the site administrator before they can be used.
This is done through the site administration panel.

Some of the connectors also require an "API key" from the service you wish to connect to.
For Tumblr, this information can be found in the site administration pages, while for Twitter (X) each user has to create their own API key.
Other connectors, such as Bluesky, don't require an API key at all.

You can find more information about specific requirements on each addon's settings page, either on the admin page or the user page.

## Bluesky Jetstream

To further improve connectivity to Bluesky, Admins can choose to enable 'Jetstream' connectivity.
Jetstream is a service that connects to the Bluesky firehose.
With Jetstream, messages arrive in real time rather than having to be polled.
It also enables real-time processing of blocks or tracking activities performed by the user via the Bluesky website or application.

To enable Jetstream processing, run `bin/console.php jetstream' from the command line.
You will need to define the process id file in local.config.php in the 'jetstream' section using the key 'pidfile'.

To keep track of the messages processed and the drift (the time difference between the date of the message and the date the system processed that message), some fields are added to the statistics endpoint.
